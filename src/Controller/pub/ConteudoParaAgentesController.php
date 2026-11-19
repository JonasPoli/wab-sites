<?php

declare(strict_types=1);

namespace App\Controller\pub;

use App\Agentes\DadosDoSite;
use App\Agentes\HtmlDaPagina;
use App\Agentes\MarkdownDaPagina;
use App\Agentes\PaginasDoSite;
use App\Service\TenantContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Conteúdo para agentes por domínio (spec conteudo-para-agentes): versão Markdown das
 * páginas em `<caminho>.md`, `/llms.txt` e `/llms-full.txt`. Só as páginas listadas em
 * PaginasDoSite do tenant têm versão Markdown; o resto é 404.
 */
final class ConteudoParaAgentesController extends AbstractController
{
    private const LIMITE_LLMS_FULL = 60;

    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly PaginasDoSite $paginas,
        private readonly HtmlDaPagina $html,
        private readonly MarkdownDaPagina $conversor,
        private readonly CacheInterface $cache,
    ) {
    }

    #[Route('/llms.txt', name: 'pub_llms', methods: ['GET', 'HEAD'])]
    public function llms(Request $request): Response
    {
        $tenant = $this->tenantContext->getTenant() ?? throw $this->createNotFoundException();
        $base = $request->getSchemeAndHttpHost();
        $dados = DadosDoSite::de($tenant, $base);

        $texto = '# ' . $tenant->getName() . "\n\n";
        if (isset($dados['descricao'])) {
            $texto .= '> ' . $dados['descricao'] . "\n\n";
        }
        $texto .= 'Site ' . $base . '/ . Toda página tem versão em Markdown: acrescente .md ao endereço (a home é /index.md) ou peça com Accept: text/markdown.' . "\n\n";
        $texto .= "## Páginas\n\n";
        foreach ($this->paginas->todas($tenant) as $item) {
            $texto .= '- [' . $item['titulo'] . '](' . $base . PaginasDoSite::enderecoMarkdown($item['caminho']) . ')'
                . ('' !== $item['descricao'] ? ': ' . $item['descricao'] : '') . "\n";
        }
        $texto .= "\n## Para agentes\n\n"
            . '- [Conteúdo completo](' . $base . "/llms-full.txt): todas as páginas em Markdown num arquivo\n"
            . '- [Servidor MCP](' . $base . '/mcp): Streamable HTTP, público e só de leitura. Cartão: ' . $base . "/.well-known/mcp/server-card.json\n"
            . '- [Agente A2A](' . $base . '/a2a): responde em português sobre o site. Cartão: ' . $base . "/.well-known/agent-card.json\n"
            . '- [API para agentes](' . $base . '/api/agentes): JSON com OAuth 2.0; registro anônimo em ' . $base . "/auth.md\n"
            . '- [Habilidades](' . $base . "/.well-known/agent-skills/index.json)\n\n";
        $texto .= '## Contato' . "\n\n" . DadosDoSite::emMarkdown($dados) . "\n";

        return $this->texto($texto, 'text/plain');
    }

    #[Route('/llms-full.txt', name: 'pub_llms_full', methods: ['GET', 'HEAD'])]
    public function llmsFull(Request $request): Response
    {
        $tenant = $this->tenantContext->getTenant() ?? throw $this->createNotFoundException();
        $base = $request->getSchemeAndHttpHost();
        $itens = \array_slice($this->paginas->todas($tenant), 0, self::LIMITE_LLMS_FULL);
        $versao = md5($base . serialize(array_map(static fn ($i) => [$i['caminho'], $i['lastmod']?->format('c')], $itens)));

        $texto = $this->cache->get('llms_full_' . $tenant->getId() . '_' . $versao, function (ItemInterface $item) use ($tenant, $base, $itens): string {
            $item->expiresAfter(600);
            $partes = ['# ' . $tenant->getName() . ': todas as páginas em Markdown', ''];
            foreach ($itens as $pagina) {
                $html = $this->html->de($pagina['caminho']);
                if (null !== $html) {
                    $partes[] = $this->conversor->converter($html, $tenant, $base, $pagina);
                    $partes[] = "\n---\n";
                }
            }

            return implode("\n", $partes);
        });

        return $this->texto($texto, 'text/plain');
    }

    #[Route('/{caminho}.md', name: 'pub_markdown', requirements: ['caminho' => '.+'], methods: ['GET', 'HEAD'], priority: -100)]
    public function markdown(Request $request, string $caminho): Response
    {
        $tenant = $this->tenantContext->getTenant() ?? throw $this->createNotFoundException();
        $pagina = $this->paginas->porCaminho($tenant, '/' . $caminho . '.md') ?? throw $this->createNotFoundException();
        $html = $this->html->de($pagina['caminho']) ?? throw $this->createNotFoundException();

        return $this->texto($this->conversor->converter($html, $tenant, $request->getSchemeAndHttpHost(), $pagina), 'text/markdown');
    }

    private function texto(string $texto, string $tipo): Response
    {
        return new Response($texto, Response::HTTP_OK, [
            'Content-Type' => $tipo . '; charset=utf-8',
            'Cache-Control' => 'public, max-age=300',
            'Access-Control-Allow-Origin' => '*',
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}
