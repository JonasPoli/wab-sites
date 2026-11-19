<?php

namespace App\Oauth;

use App\Agentes\SiteAtual;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Tokens do servidor de autorização, sem banco. Porte do wab2024 (design D1 de
 * autenticacao-e-a2a-para-agentes) com o emissor por domínio (D4 de sites-prontos-para-agentes).
 *
 * - Tokens de acesso e asserções de identidade: JWT RS256, verificáveis pelo JWKS.
 * - client_id e código de autorização: valores compactos assinados com HMAC do segredo
 *   do kernel ("dados.assinatura"); o próprio valor carrega o registro.
 * - Revogação e uso único do código: o identificador vai para um cache em arquivo
 *   em var/oauth/, até a data de expiração.
 */
final class TokenService
{
    public const SCOPE = 'api.read';
    public const ACCESS_TOKEN_TTL = 3600;
    public const ASSERTION_TTL = 30 * 86400;
    public const CODE_TTL = 300;

    public const TYP_ACCESS = 'at+jwt';
    public const TYP_ASSERTION = 'agent-identity+jwt';

    public const GRANT_JWT_BEARER = 'urn:ietf:params:oauth:grant-type:jwt-bearer';

    private ?FilesystemAdapter $revoked = null;

    public function __construct(
        private readonly KeyStore $keys,
        #[Autowire('%kernel.secret%')] private readonly string $secret,
        private readonly SiteAtual $site,
    ) {
    }

    /** O emissor é o próprio site do pedido: um token de um domínio não vale em outro. */
    public function issuer(): string
    {
        return $this->site->base();
    }

    /** Identificador do recurso protegido (RFC 9728) e audiência padrão dos tokens. */
    public function apiResource(): string
    {
        return $this->issuer().'/api/agentes';
    }

    /** Audiências aceitas pela API: a da API e a do site inteiro. */
    public function acceptedAudiences(): array
    {
        return [$this->apiResource(), $this->issuer(), $this->issuer().'/'];
    }

    // ---------------------------------------------------------------- JWT

    public function sign(array $claims, string $typ): string
    {
        $header = ['alg' => 'RS256', 'typ' => $typ, 'kid' => $this->keys->kid()];
        $input = Base64Url::encode(json_encode($header, \JSON_UNESCAPED_SLASHES)).'.'.Base64Url::encode(json_encode($claims, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE));
        openssl_sign($input, $signature, $this->keys->privateKey(), \OPENSSL_ALGO_SHA256);

        return $input.'.'.Base64Url::encode($signature);
    }

    /**
     * Verifica assinatura, tipo, emissor, audiência, validade e revogação.
     *
     * @param string[] $audiences
     *
     * @return array as claims
     *
     * @throws OauthException invalid_token (ou o código pedido em $error)
     */
    public function verify(string $jwt, string $typ, array $audiences, string $error = 'invalid_token'): array
    {
        $parts = explode('.', $jwt);
        if (3 !== \count($parts)) {
            throw new OauthException($error, 'Token mal formado.', 401);
        }
        [$h, $p, $s] = $parts;
        $header = json_decode((string) Base64Url::decode($h), true);
        $claims = json_decode((string) Base64Url::decode($p), true);
        $signature = Base64Url::decode($s);
        if (!\is_array($header) || !\is_array($claims) || null === $signature) {
            throw new OauthException($error, 'Token mal formado.', 401);
        }
        if (($header['alg'] ?? null) !== 'RS256' || ($header['kid'] ?? null) !== $this->keys->kid()) {
            throw new OauthException($error, 'Token assinado com chave desconhecida.', 401);
        }
        if (1 !== openssl_verify($h.'.'.$p, $signature, $this->keys->publicKey(), \OPENSSL_ALGO_SHA256)) {
            throw new OauthException($error, 'Assinatura do token inválida.', 401);
        }
        if (($header['typ'] ?? null) !== $typ) {
            throw new OauthException($error, 'Tipo de token errado para este uso.', 401);
        }
        if (($claims['iss'] ?? null) !== $this->issuer()) {
            throw new OauthException($error, 'Emissor do token desconhecido.', 401);
        }
        $aud = (array) ($claims['aud'] ?? []);
        if ([] === array_intersect($aud, $audiences)) {
            throw new OauthException($error, 'Token emitido para outra audiência.', 401);
        }
        if (!\is_int($claims['exp'] ?? null) || $claims['exp'] < time()) {
            throw new OauthException($error, 'Token expirado.', 401);
        }
        if (!\is_string($claims['jti'] ?? null) || $this->isRevoked($claims['jti'])) {
            throw new OauthException($error, 'Token revogado.', 401);
        }

        return $claims;
    }

    // ------------------------------------------------- emissão

    /** Resposta de token (RFC 6749 §5.1) com um JWT de acesso (RFC 9068). */
    public function issueAccessToken(string $subject, string $clientId, ?string $resource = null): array
    {
        $now = time();
        $token = $this->sign([
            'iss' => $this->issuer(),
            'sub' => $subject,
            'aud' => $resource ?? $this->apiResource(),
            'client_id' => $clientId,
            'scope' => self::SCOPE,
            'jti' => bin2hex(random_bytes(16)),
            'iat' => $now,
            'exp' => $now + self::ACCESS_TOKEN_TTL,
        ], self::TYP_ACCESS);

        return [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => self::ACCESS_TOKEN_TTL,
            'scope' => self::SCOPE,
        ];
    }

    /** Registro anônimo do Auth.md: asserção de identidade assinada pelo próprio site. */
    public function issueAnonymousAssertion(): array
    {
        $now = time();
        $jti = bin2hex(random_bytes(16));
        $assertion = $this->sign([
            'iss' => $this->issuer(),
            'sub' => 'agent:'.$jti,
            'aud' => $this->issuer(),
            'registration_type' => 'anonymous',
            'scope' => self::SCOPE,
            'jti' => $jti,
            'iat' => $now,
            'exp' => $now + self::ASSERTION_TTL,
        ], self::TYP_ASSERTION);

        return ['id' => 'reg_'.substr($jti, 0, 24), 'assertion' => $assertion, 'expires' => $now + self::ASSERTION_TTL];
    }

    /** @throws OauthException invalid_grant */
    public function verifyAssertion(string $assertion, string $tokenEndpoint): array
    {
        return $this->verify($assertion, self::TYP_ASSERTION, [$this->issuer(), $tokenEndpoint], 'invalid_grant');
    }

    /** @throws OauthException invalid_token */
    public function verifyAccessToken(string $token): array
    {
        return $this->verify($token, self::TYP_ACCESS, $this->acceptedAudiences());
    }

    // ------------------------------------------------- valores assinados com HMAC

    public function seal(array $data, string $purpose): string
    {
        $payload = Base64Url::encode(json_encode($data, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE));

        return $payload.'.'.Base64Url::encode(substr(hash_hmac('sha256', $payload, $this->hmacKey($purpose), true), 0, 16));
    }

    public function unseal(string $value, string $purpose): ?array
    {
        $parts = explode('.', $value);
        if (2 !== \count($parts)) {
            return null;
        }
        $expected = Base64Url::encode(substr(hash_hmac('sha256', $parts[0], $this->hmacKey($purpose), true), 0, 16));
        if (!hash_equals($expected, $parts[1])) {
            return null;
        }
        $data = json_decode((string) Base64Url::decode($parts[0]), true);

        return \is_array($data) ? $data : null;
    }

    /** Segredo do cliente confidencial: derivado do client_id, nunca guardado. */
    public function clientSecret(string $clientId): string
    {
        return Base64Url::encode(hash_hmac('sha256', $clientId, $this->hmacKey('client_secret'), true));
    }

    private function hmacKey(string $purpose): string
    {
        return hash_hmac('sha256', 'oauth:'.$purpose, $this->secret, true);
    }

    // ------------------------------------------------- revogação

    public function revoke(string $jti, int $expiresAt): void
    {
        $ttl = $expiresAt - time();
        if ($ttl <= 0) {
            return;
        }
        $item = $this->revoked()->getItem($this->cacheKey($jti));
        $item->set(true)->expiresAfter($ttl);
        $this->revoked()->save($item);
    }

    public function isRevoked(string $jti): bool
    {
        return $this->revoked()->hasItem($this->cacheKey($jti));
    }

    /**
     * Revoga um token de acesso ou uma asserção (RFC 7009). Token inválido ou
     * desconhecido é ignorado, como manda a RFC.
     */
    public function revokeToken(string $token): void
    {
        foreach ([[self::TYP_ACCESS, $this->acceptedAudiences()], [self::TYP_ASSERTION, [$this->issuer()]]] as [$typ, $aud]) {
            try {
                $claims = $this->verify($token, $typ, $aud);
                $this->revoke($claims['jti'], $claims['exp']);

                return;
            } catch (OauthException) {
            }
        }
    }

    private function cacheKey(string $jti): string
    {
        return hash('sha256', $jti);
    }

    private function revoked(): FilesystemAdapter
    {
        return $this->revoked ??= new FilesystemAdapter('revogados', 0, $this->keys->directory().'/cache');
    }
}
