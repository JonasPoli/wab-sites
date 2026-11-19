<?php

namespace App\Oauth;

use Symfony\Component\HttpFoundation\Request;

/**
 * Registro dinâmico de clientes (RFC 7591) sem banco: o client_id é o próprio registro,
 * assinado (TokenService::seal), e o client_secret é derivado dele.
 */
final class ClientRegistry
{
    public const GRANTS = ['authorization_code', 'client_credentials'];
    public const AUTH_METHODS = ['client_secret_basic', 'client_secret_post', 'none'];
    private const PREFIX = 'wab-';

    public function __construct(private readonly TokenService $tokens)
    {
    }

    /**
     * @throws OauthException invalid_client_metadata ou invalid_redirect_uri
     */
    public function register(array $metadata): array
    {
        $name = trim((string) ($metadata['client_name'] ?? ''));
        $name = '' === $name ? 'Agente sem nome' : mb_substr($name, 0, 100);

        $redirectUris = $metadata['redirect_uris'] ?? [];
        if (!\is_array($redirectUris) || \count($redirectUris) > 5) {
            throw new OauthException('invalid_redirect_uri', 'redirect_uris precisa ser uma lista com até 5 endereços.');
        }
        $redirectUris = array_values($redirectUris);
        foreach ($redirectUris as $uri) {
            if (!\is_string($uri) || !self::validRedirectUri($uri)) {
                throw new OauthException('invalid_redirect_uri', 'Endereço de retorno inválido: use https, ou http só em localhost, sem fragmento.');
            }
        }

        // Concessões pedidas que o servidor não oferece (refresh_token, por exemplo) são
        // retiradas; a resposta informa o que ficou registrado (RFC 7591 §3.2.1).
        $requested = $metadata['grant_types'] ?? ([] === $redirectUris ? ['client_credentials'] : ['authorization_code']);
        $grants = array_values(array_intersect(self::GRANTS, \is_array($requested) ? $requested : []));
        if ([] === $grants) {
            throw new OauthException('invalid_client_metadata', 'grant_types precisa incluir authorization_code ou client_credentials.');
        }
        if (\in_array('authorization_code', $grants, true) && [] === $redirectUris) {
            throw new OauthException('invalid_redirect_uri', 'authorization_code exige ao menos um redirect_uris.');
        }

        $method = $metadata['token_endpoint_auth_method'] ?? 'client_secret_basic';
        if (!\in_array($method, self::AUTH_METHODS, true)) {
            throw new OauthException('invalid_client_metadata', 'token_endpoint_auth_method aceita: '.implode(', ', self::AUTH_METHODS).'.');
        }
        if ('none' === $method && \in_array('client_credentials', $grants, true)) {
            throw new OauthException('invalid_client_metadata', 'client_credentials exige cliente confidencial (client_secret_basic ou client_secret_post).');
        }

        $issuedAt = time();
        $clientId = self::PREFIX.$this->tokens->seal(['n' => $name, 'r' => $redirectUris, 'g' => $grants, 'a' => $method, 't' => $issuedAt, 'h' => $this->tokens->issuer()], 'client');

        return array_filter([
            'client_id' => $clientId,
            'client_secret' => 'none' === $method ? null : $this->tokens->clientSecret($clientId),
            'client_id_issued_at' => $issuedAt,
            'client_secret_expires_at' => 'none' === $method ? null : 0,
            'client_name' => $name,
            'redirect_uris' => $redirectUris,
            'grant_types' => $grants,
            'response_types' => \in_array('authorization_code', $grants, true) ? ['code'] : [],
            'token_endpoint_auth_method' => $method,
            'scope' => TokenService::SCOPE,
        ], fn ($v) => null !== $v);
    }

    /**
     * @return array{id: string, name: string, redirect_uris: string[], grant_types: string[], auth_method: string}|null
     */
    public function find(string $clientId): ?array
    {
        if (!str_starts_with($clientId, self::PREFIX)) {
            return null;
        }
        $data = $this->tokens->unseal(substr($clientId, \strlen(self::PREFIX)), 'client');
        if (null === $data || !isset($data['n'], $data['r'], $data['g'], $data['a']) || ($data['h'] ?? null) !== $this->tokens->issuer()) {
            return null;
        }

        return ['id' => $clientId, 'name' => (string) $data['n'], 'redirect_uris' => (array) $data['r'], 'grant_types' => (array) $data['g'], 'auth_method' => (string) $data['a']];
    }

    /**
     * Autentica o cliente no endpoint de token: Basic, client_secret no corpo ou,
     * para cliente público, só o client_id.
     *
     * @return array|null o cliente, ou null quando o pedido não identifica cliente
     *
     * @throws OauthException invalid_client (401)
     */
    public function authenticate(Request $request, array $params): ?array
    {
        $header = (string) $request->headers->get('Authorization');
        if (0 === stripos($header, 'Basic ')) {
            $decoded = base64_decode(substr($header, 6), true);
            [$id, $secret] = false !== $decoded && str_contains($decoded, ':') ? explode(':', $decoded, 2) : ['', ''];

            return $this->checkSecret(urldecode($id), urldecode($secret), 'client_secret_basic');
        }
        if (isset($params['client_secret'])) {
            return $this->checkSecret((string) ($params['client_id'] ?? ''), (string) $params['client_secret'], 'client_secret_post');
        }
        if (isset($params['client_id'])) {
            $client = $this->find((string) $params['client_id']);
            if (null === $client || 'none' !== $client['auth_method']) {
                throw new OauthException('invalid_client', 'Cliente desconhecido ou sem credencial.', 401);
            }

            return $client;
        }

        return null;
    }

    public static function validRedirectUri(string $uri): bool
    {
        $parts = parse_url($uri);
        if (false === $parts || isset($parts['fragment']) || !isset($parts['scheme'], $parts['host']) || \strlen($uri) > 500) {
            return false;
        }

        return 'https' === strtolower($parts['scheme'])
            || ('http' === strtolower($parts['scheme']) && \in_array(strtolower($parts['host']), ['localhost', '127.0.0.1', '[::1]'], true));
    }

    private function checkSecret(string $clientId, string $secret, string $method): array
    {
        $client = $this->find($clientId);
        if (null === $client || 'none' === $client['auth_method'] || !hash_equals($this->tokens->clientSecret($clientId), $secret)) {
            throw new OauthException('invalid_client', 'client_id ou client_secret inválido.', 401);
        }

        return $client;
    }
}
