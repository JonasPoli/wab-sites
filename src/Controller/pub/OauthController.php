<?php

namespace App\Controller\pub;

use App\Agentes\SiteAtual;
use App\Oauth\Base64Url;
use App\Oauth\ClientRegistry;
use App\Oauth\KeyStore;
use App\Oauth\OauthException;
use App\Oauth\TokenService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Servidor de autorização para agentes de cada site (porte do wab2024; spec
 * autenticacao-de-agentes da mudança sites-prontos-para-agentes):
 * registro anônimo do Auth.md, registro de clientes (RFC 7591), consentimento com PKCE,
 * token, revogação (RFC 7009) e JWKS. Nada é gravado em banco; o escopo único,
 * api.read, só lê dados que já são públicos.
 */
class OauthController extends AbstractController
{
    private const MAX_BYTES = 16384;

    private const CORS = [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
        'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
        'Access-Control-Max-Age' => '86400',
    ];

    public function __construct(
        private readonly TokenService $tokens,
        private readonly ClientRegistry $clients,
        private readonly KeyStore $keys,
        private readonly SiteAtual $site,
    ) {
    }

    // ------------------------------------------------------------ Auth.md

    #[Route('/agent/identity', name: 'oauth_agent_identity', methods: ['POST', 'OPTIONS'])]
    public function identity(Request $request): Response
    {
        return $this->guard($request, function (array $params): Response {
            $type = $params['type'] ?? null;
            if ('anonymous' !== $type) {
                throw match ($type) {
                    'identity_assertion' => new OauthException('issuer_not_enabled', 'Este site não confia em provedores de identidade externos. Use {"type": "anonymous"}.'),
                    'service_auth' => new OauthException('service_auth_not_enabled', 'Este site não tem contas de usuário. Use {"type": "anonymous"}.'),
                    default => new OauthException('invalid_request', 'Envie {"type": "anonymous"}.'),
                };
            }

            $registration = $this->tokens->issueAnonymousAssertion();

            return $this->oauthJson([
                'registration_id' => $registration['id'],
                'registration_type' => 'anonymous',
                'identity_assertion' => $registration['assertion'],
                'assertion_expires' => gmdate('Y-m-d\TH:i:s.000\Z', $registration['expires']),
                'pre_claim_scopes' => [TokenService::SCOPE],
                'scopes' => [TokenService::SCOPE],
                'token_endpoint' => $this->absolute('oauth_token'),
                'grant_type' => TokenService::GRANT_JWT_BEARER,
                'note' => 'Troque a identity_assertion por um access_token no token_endpoint. Não há posse de conta (claim): o acesso anônimo já é o acesso completo.',
            ]);
        });
    }

    #[Route('/agent/identity/claim', name: 'oauth_agent_claim', methods: ['POST', 'OPTIONS'])]
    public function claim(Request $request): Response
    {
        return $this->guard($request, function (): Response {
            throw new OauthException('claim_not_supported', 'Este site não tem contas de usuário, então não há posse de registro a reivindicar. O registro anônimo já dá o acesso completo (escopo api.read).');
        });
    }

    // ------------------------------------------------------------ RFC 7591

    #[Route('/oauth/register', name: 'oauth_register', methods: ['POST', 'OPTIONS'])]
    public function register(Request $request): Response
    {
        return $this->guard($request, fn (array $params): Response => $this->oauthJson($this->clients->register($params), Response::HTTP_CREATED));
    }

    // ------------------------------------------------------------ consentimento

    #[Route('/oauth/authorize', name: 'oauth_authorize', methods: ['GET', 'POST'])]
    public function authorize(Request $request): Response
    {
        $params = 'POST' === $request->getMethod() ? $request->request->all() : $request->query->all();
        $params = array_map(fn ($v) => \is_string($v) ? $v : '', $params);

        // Sem cliente e endereço de retorno válidos, o erro fica na página: nunca se
        // redireciona para um endereço que não foi registrado.
        $client = $this->clients->find($params['client_id'] ?? '');
        $redirectUri = $params['redirect_uri'] ?? '';
        if (null !== $client && '' === $redirectUri && 1 === \count($client['redirect_uris'])) {
            $redirectUri = $client['redirect_uris'][0];
        }
        if (null === $client || !\in_array('authorization_code', $client['grant_types'], true) || !\in_array($redirectUri, $client['redirect_uris'], true)) {
            return $this->consentPage(['erro' => null === $client ? 'Cliente desconhecido: registre o agente em /oauth/register.' : 'Endereço de retorno não registrado para este cliente.'], Response::HTTP_BAD_REQUEST);
        }

        $state = $params['state'] ?? '';
        $fail = fn (string $error, string $description): Response => $this->redirectBack($redirectUri, ['error' => $error, 'error_description' => $description, 'state' => $state]);

        if ('code' !== ($params['response_type'] ?? '')) {
            return $fail('unsupported_response_type', 'Use response_type=code.');
        }
        $challenge = $params['code_challenge'] ?? '';
        if ('S256' !== ($params['code_challenge_method'] ?? '') || !preg_match('/^[A-Za-z0-9_-]{43}$/', $challenge)) {
            return $fail('invalid_request', 'PKCE obrigatório: code_challenge com code_challenge_method=S256.');
        }
        $scope = trim($params['scope'] ?? '');
        if ('' !== $scope && TokenService::SCOPE !== $scope) {
            return $fail('invalid_scope', 'Escopo disponível: api.read.');
        }
        $resource = $params['resource'] ?? '';
        if ('' !== $resource && !\in_array($resource, $this->tokens->acceptedAudiences(), true)) {
            return $fail('invalid_target', 'Recurso desconhecido.');
        }

        if ('POST' !== $request->getMethod()) {
            return $this->consentPage(['cliente' => $client, 'params' => [
                'client_id' => $client['id'], 'redirect_uri' => $redirectUri, 'response_type' => 'code', 'state' => $state,
                'code_challenge' => $challenge, 'code_challenge_method' => 'S256', 'scope' => TokenService::SCOPE, 'resource' => $resource,
            ]]);
        }

        if ('autorizar' !== ($params['decisao'] ?? '')) {
            return $fail('access_denied', 'A pessoa recusou o acesso.');
        }

        $code = $this->tokens->seal([
            'c' => hash('sha256', $client['id']),
            'r' => $redirectUri,
            'h' => $challenge,
            'x' => '' === $resource ? null : $resource,
            'e' => time() + TokenService::CODE_TTL,
            'j' => bin2hex(random_bytes(16)),
        ], 'code');

        return $this->redirectBack($redirectUri, ['code' => $code, 'state' => $state, 'iss' => $this->tokens->issuer()]);
    }

    // ------------------------------------------------------------ token

    #[Route('/oauth/token', name: 'oauth_token', methods: ['POST', 'OPTIONS'])]
    public function token(Request $request): Response
    {
        return $this->guard($request, function (array $params) use ($request): Response {
            $client = $this->clients->authenticate($request, $params);
            $grant = $params['grant_type'] ?? null;
            $resource = $this->resource($params);

            if (TokenService::GRANT_JWT_BEARER === $grant) {
                $claims = $this->tokens->verifyAssertion((string) ($params['assertion'] ?? ''), $this->absolute('oauth_token'));

                return $this->oauthJson($this->tokens->issueAccessToken($claims['sub'], $client['id'] ?? $claims['sub'], $resource));
            }

            if ('client_credentials' === $grant) {
                if (null === $client || 'none' === $client['auth_method']) {
                    throw new OauthException('invalid_client', 'client_credentials exige client_id e client_secret.', 401);
                }
                $this->assertGrant($client, $grant);
                $this->assertScope($params);

                return $this->oauthJson($this->tokens->issueAccessToken('client:'.substr(hash('sha256', $client['id']), 0, 24), $client['id'], $resource));
            }

            if ('authorization_code' === $grant) {
                if (null === $client) {
                    throw new OauthException('invalid_client', 'Informe o client_id.', 401);
                }
                $this->assertGrant($client, $grant);
                $code = $this->tokens->unseal((string) ($params['code'] ?? ''), 'code');
                if (null === $code || $code['c'] !== hash('sha256', $client['id']) || $code['e'] < time() || $this->tokens->isRevoked($code['j'])) {
                    throw new OauthException('invalid_grant', 'Código inválido, expirado, já usado ou de outro cliente.');
                }
                if (isset($params['redirect_uri']) && $params['redirect_uri'] !== $code['r']) {
                    throw new OauthException('invalid_grant', 'redirect_uri diferente do usado na autorização.');
                }
                $verifier = (string) ($params['code_verifier'] ?? '');
                if (!preg_match('/^[A-Za-z0-9._~-]{43,128}$/', $verifier) || !hash_equals($code['h'], Base64Url::encode(hash('sha256', $verifier, true)))) {
                    throw new OauthException('invalid_grant', 'code_verifier não corresponde ao code_challenge.');
                }
                $this->tokens->revoke($code['j'], $code['e']);

                return $this->oauthJson($this->tokens->issueAccessToken('client:'.substr(hash('sha256', $client['id']), 0, 24), $client['id'], $resource ?? $code['x']));
            }

            throw new OauthException('unsupported_grant_type', 'Concessões aceitas: '.TokenService::GRANT_JWT_BEARER.', client_credentials e authorization_code.');
        });
    }

    // ------------------------------------------------------------ RFC 7009 e JWKS

    #[Route('/oauth/revoke', name: 'oauth_revoke', methods: ['POST', 'OPTIONS'])]
    public function revoke(Request $request): Response
    {
        return $this->guard($request, function (array $params): Response {
            $token = $params['token'] ?? null;
            if (!\is_string($token) || '' === $token) {
                throw new OauthException('invalid_request', 'Informe o token.');
            }
            $this->tokens->revokeToken($token);

            return new Response('', Response::HTTP_OK, self::CORS + ['Cache-Control' => 'no-store']);
        });
    }

    #[Route('/oauth/jwks.json', name: 'oauth_jwks', methods: ['GET', 'HEAD'])]
    public function jwks(): Response
    {
        $response = new JsonResponse($this->keys->jwks(), Response::HTTP_OK, self::CORS + ['Cache-Control' => 'public, max-age=3600']);
        $response->setEncodingOptions(\JSON_UNESCAPED_SLASHES | \JSON_PRETTY_PRINT);
        $response->headers->set('Content-Type', 'application/jwk-set+json');

        return $response;
    }

    // ------------------------------------------------------------ apoio

    /**
     * Lê o corpo (formulário ou JSON), trata o OPTIONS do CORS e converte
     * OauthException na resposta de erro da RFC 6749.
     */
    private function guard(Request $request, callable $handler): Response
    {
        if ('OPTIONS' === $request->getMethod()) {
            return new Response('', Response::HTTP_NO_CONTENT, self::CORS);
        }

        try {
            if (\strlen($request->getContent()) > self::MAX_BYTES) {
                throw new OauthException('invalid_request', 'Pedido grande demais.', Response::HTTP_REQUEST_ENTITY_TOO_LARGE);
            }
            $params = $request->request->all();
            if ([] === $params && str_contains((string) $request->headers->get('Content-Type'), 'json')) {
                $params = json_decode($request->getContent(), true);
                if (!\is_array($params) || array_is_list($params) && [] !== $params) {
                    throw new OauthException('invalid_request', 'O corpo precisa ser um objeto JSON ou um formulário.');
                }
            }

            return $handler($params);
        } catch (OauthException $e) {
            $response = $this->oauthJson($e->toArray(), $e->status);
            if ('invalid_client' === $e->error && 0 === stripos((string) $request->headers->get('Authorization'), 'Basic ')) {
                $response->headers->set('WWW-Authenticate', 'Basic realm="wab"');
            }

            return $response;
        }
    }

    private function oauthJson(array $data, int $status = Response::HTTP_OK): JsonResponse
    {
        $response = new JsonResponse($data, $status, self::CORS + ['Cache-Control' => 'no-store', 'Pragma' => 'no-cache']);
        $response->setEncodingOptions(\JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES);

        return $response;
    }

    private function resource(array $params): ?string
    {
        $resource = $params['resource'] ?? null;
        if (null === $resource || '' === $resource) {
            return null;
        }
        if (!\is_string($resource) || !\in_array($resource, $this->tokens->acceptedAudiences(), true)) {
            throw new OauthException('invalid_target', 'Recurso desconhecido. Use '.$this->tokens->apiResource().'.');
        }

        return $resource;
    }

    private function assertGrant(array $client, string $grant): void
    {
        if (!\in_array($grant, $client['grant_types'], true)) {
            throw new OauthException('unauthorized_client', 'Este cliente não foi registrado para '.$grant.'.');
        }
    }

    private function assertScope(array $params): void
    {
        $scope = trim((string) ($params['scope'] ?? ''));
        if ('' !== $scope && TokenService::SCOPE !== $scope) {
            throw new OauthException('invalid_scope', 'Escopo disponível: api.read.');
        }
    }

    private function absolute(string $route): string
    {
        return $this->tokens->issuer().$this->generateUrl($route, [], UrlGeneratorInterface::ABSOLUTE_PATH);
    }

    private function redirectBack(string $redirectUri, array $query): RedirectResponse
    {
        $query = array_filter($query, fn ($v) => '' !== $v && null !== $v);

        return new RedirectResponse($redirectUri.(str_contains($redirectUri, '?') ? '&' : '?').http_build_query($query), Response::HTTP_FOUND, ['Cache-Control' => 'no-store']);
    }

    private function consentPage(array $vars, int $status = Response::HTTP_OK): Response
    {
        $vars['site_nome'] = $this->site->tenant()->getName();
        $response = $this->render('agentes/consentimento.html.twig', $vars, new Response('', $status));
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Content-Security-Policy', "frame-ancestors 'none'");
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
