<?php

declare(strict_types=1);

namespace App\Controller\pub;

use App\Agentes\DescobertaPorAgentes;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Documentos de descoberta em /.well-known/ de cada site (App\Agentes\DescobertaPorAgentes).
 *
 * No servidor, o Nginx do RunCloud recusa /.well-known/*; só estes caminhos chegam à
 * aplicação, pelo arquivo /etc/nginx-rc/extra.d/app-wab-sites.location.main-before.descoberta-agentes.conf
 * (design D7 da mudança sites-prontos-para-agentes). Caminho novo aqui precisa de bloco novo lá.
 */
final class DescobertaPorAgentesController extends AbstractController
{
    public const HEADERS = ['Access-Control-Allow-Origin' => '*', 'Cache-Control' => 'public, max-age=300'];

    public function __construct(private readonly DescobertaPorAgentes $descoberta)
    {
    }

    #[Route('/.well-known/agent-skills/index.json', name: 'pub_agentes_skills', methods: ['GET', 'HEAD'])]
    public function skillsIndex(): Response
    {
        return self::documento($this->descoberta->skillsIndex(), 'application/json');
    }

    #[Route('/.well-known/agent-skills/{nome}/SKILL.md', name: 'pub_agentes_skill', requirements: ['nome' => '[a-z0-9-]+'], methods: ['GET', 'HEAD'])]
    public function skill(string $nome): Response
    {
        $texto = $this->descoberta->skill($nome) ?? throw $this->createNotFoundException();

        return new Response($texto, Response::HTTP_OK, self::HEADERS + ['Content-Type' => 'text/markdown; charset=utf-8']);
    }

    #[Route('/.well-known/mcp/server-card.json', name: 'pub_agentes_mcp_cartao', methods: ['GET', 'HEAD'])]
    public function serverCard(): Response
    {
        return self::documento($this->descoberta->serverCard(), 'application/json');
    }

    #[Route('/.well-known/api-catalog', name: 'pub_agentes_api_catalog', methods: ['GET', 'HEAD'])]
    public function apiCatalog(): Response
    {
        return self::documento($this->descoberta->apiCatalog(), 'application/linkset+json');
    }

    #[Route('/.well-known/ai-catalog.json', name: 'pub_agentes_ai_catalog', methods: ['GET', 'HEAD'])]
    public function aiCatalog(): Response
    {
        return self::documento($this->descoberta->aiCatalog(), 'application/json');
    }

    #[Route('/.well-known/poppy.json', name: 'pub_agentes_poppy', methods: ['GET', 'HEAD'])]
    public function poppy(): Response
    {
        return self::documento($this->descoberta->poppy(), 'application/json');
    }

    #[Route('/.well-known/oauth-authorization-server', name: 'pub_agentes_oauth_as', methods: ['GET', 'HEAD'])]
    public function authorizationServer(): Response
    {
        return self::documento($this->descoberta->authorizationServer(), 'application/json');
    }

    /** RFC 9728: o site inteiro na raiz; a API no sufixo /api/agentes (§3.1). */
    #[Route('/.well-known/oauth-protected-resource{sufixo}', name: 'pub_agentes_oauth_prm', requirements: ['sufixo' => '|/api/agentes'], defaults: ['sufixo' => ''], methods: ['GET', 'HEAD'])]
    public function protectedResource(string $sufixo): Response
    {
        return self::documento($this->descoberta->protectedResource($sufixo), 'application/json');
    }

    #[Route('/.well-known/agent-card.json', name: 'pub_agentes_a2a_cartao', methods: ['GET', 'HEAD'])]
    public function agentCard(): Response
    {
        $resposta = self::documento($this->descoberta->agentCard(), 'application/json');
        $resposta->setEtag(md5((string) $resposta->getContent()));

        return $resposta;
    }

    #[Route('/auth.md', name: 'pub_agentes_auth_md', methods: ['GET', 'HEAD'])]
    public function authMd(): Response
    {
        return new Response($this->descoberta->authMd(), Response::HTTP_OK, self::HEADERS + ['Content-Type' => 'text/markdown; charset=utf-8']);
    }

    public static function documento(array $dados, string $tipo): JsonResponse
    {
        $resposta = new JsonResponse($dados, Response::HTTP_OK, self::HEADERS);
        $resposta->setEncodingOptions(\JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_PRETTY_PRINT);
        $resposta->headers->set('Content-Type', $tipo);

        return $resposta;
    }
}
