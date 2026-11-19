<?php

declare(strict_types=1);

namespace App\Controller\pub;

use App\Agentes\DescobertaPorAgentes;
use App\Agentes\SiteAtual;
use App\Mcp\McpServer;
use App\Oauth\OauthException;
use App\Oauth\TokenService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * API para agentes de cada site (/api/agentes): só leitura, exige token Bearer com escopo
 * api.read emitido pelo mesmo domínio. Os dados vêm das ferramentas do servidor MCP.
 * Sem token, responde 401 com o endereço dos metadados do recurso (RFC 9728 §5.1).
 */
final class AgentApiController extends AbstractController
{
    private const CORS = [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'GET, OPTIONS',
        'Access-Control-Allow-Headers' => 'Authorization, Content-Type',
        'Access-Control-Expose-Headers' => 'WWW-Authenticate, Link',
        'Access-Control-Max-Age' => '86400',
    ];

    public function __construct(
        private readonly TokenService $tokens,
        private readonly McpServer $mcp,
        private readonly SiteAtual $site,
        private readonly DescobertaPorAgentes $descoberta,
    ) {
    }

    #[Route('/api/agentes', name: 'pub_api_agentes', methods: ['GET', 'HEAD', 'OPTIONS'])]
    public function index(Request $request): Response
    {
        return $this->protegido($request, fn (): array => [
            'nome' => 'API para agentes de ' . $this->site->tenant()->getName(),
            'descricao' => 'Dados públicos do site em JSON: perfil e contato, páginas em Markdown e notícias.',
            'endpoints' => [
                'perfil' => $this->site->url('/api/agentes/perfil'),
                'paginas' => $this->site->url('/api/agentes/paginas{?caminho}'),
                'noticias' => $this->site->url('/api/agentes/noticias{?busca}'),
            ],
            'openapi' => $this->site->url('/api/agentes/openapi.json'),
            'documentacao' => $this->site->url('/auth.md'),
        ]);
    }

    #[Route('/api/agentes/perfil', name: 'pub_api_agentes_perfil', methods: ['GET', 'HEAD', 'OPTIONS'])]
    public function perfil(Request $request): Response
    {
        return $this->protegido($request, fn (): array => $this->ferramenta('dados_do_site'));
    }

    #[Route('/api/agentes/paginas', name: 'pub_api_agentes_paginas', methods: ['GET', 'HEAD', 'OPTIONS'])]
    public function paginas(Request $request): Response
    {
        return $this->protegido($request, function () use ($request): array {
            $caminho = $request->query->getString('caminho');
            if ('' === $caminho) {
                return $this->ferramenta('listar_paginas');
            }
            $resultado = $this->mcp->tools()['ler_pagina']->call(['caminho' => $caminho]);
            if ($resultado->isError) {
                throw new OauthException('not_found', $resultado->text, Response::HTTP_NOT_FOUND);
            }

            return ['caminho' => $caminho, 'markdown' => $resultado->text];
        });
    }

    #[Route('/api/agentes/noticias', name: 'pub_api_agentes_noticias', methods: ['GET', 'HEAD', 'OPTIONS'])]
    public function noticias(Request $request): Response
    {
        return $this->protegido($request, fn (): array => $this->ferramenta('listar_noticias', array_filter(['busca' => $request->query->getString('busca')])));
    }

    #[Route('/api/agentes/openapi.json', name: 'pub_api_agentes_openapi', methods: ['GET', 'HEAD', 'OPTIONS'])]
    public function openapi(): Response
    {
        $resposta = DescobertaPorAgentesController::documento($this->descoberta->openApi(), 'application/vnd.oai.openapi+json');
        $resposta->headers->add(self::CORS);

        return $resposta;
    }

    private function ferramenta(string $nome, array $argumentos = []): array
    {
        return $this->mcp->tools()[$nome]->call($argumentos)->structured ?? [];
    }

    private function protegido(Request $request, callable $dados): Response
    {
        if ('OPTIONS' === $request->getMethod()) {
            return new Response('', Response::HTTP_NO_CONTENT, self::CORS);
        }

        $headers = self::CORS + ['Cache-Control' => 'no-store', 'X-Robots-Tag' => 'noindex'];
        $metadados = $this->site->url('/.well-known/oauth-protected-resource/api/agentes');
        $authorization = (string) $request->headers->get('Authorization');

        try {
            if (!preg_match('/^Bearer\s+(\S+)$/i', $authorization, $m)) {
                $headers['WWW-Authenticate'] = sprintf('Bearer realm="site", resource_metadata="%s", scope="%s"', $metadados, TokenService::SCOPE);
                $headers['Link'] = '<' . $this->site->url('/auth.md') . '>; rel="service-doc"';

                return $this->enviar(['error' => 'unauthorized', 'error_description' => 'Envie Authorization: Bearer <token>. Veja como obter um token em ' . $this->site->url('/auth.md') . '.'], Response::HTTP_UNAUTHORIZED, $headers);
            }

            $claims = $this->tokens->verifyAccessToken($m[1]);
            if (!\in_array(TokenService::SCOPE, explode(' ', (string) ($claims['scope'] ?? '')), true)) {
                $headers['WWW-Authenticate'] = sprintf('Bearer realm="site", error="insufficient_scope", scope="%s", resource_metadata="%s"', TokenService::SCOPE, $metadados);

                return $this->enviar(['error' => 'insufficient_scope', 'error_description' => 'O token não tem o escopo api.read.'], Response::HTTP_FORBIDDEN, $headers);
            }

            return $this->enviar($dados(), Response::HTTP_OK, $headers);
        } catch (OauthException $e) {
            if ('invalid_token' === $e->error) {
                // A descrição (com acentos) fica no corpo: cabeçalho HTTP é ASCII.
                $headers['WWW-Authenticate'] = sprintf('Bearer realm="site", error="invalid_token", resource_metadata="%s"', $metadados);
            }

            return $this->enviar($e->toArray(), $e->status, $headers);
        }
    }

    private function enviar(array $dados, int $status, array $headers): JsonResponse
    {
        $resposta = new JsonResponse($dados, $status, $headers);
        $resposta->setEncodingOptions(\JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES);

        return $resposta;
    }
}
