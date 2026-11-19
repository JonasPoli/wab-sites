<?php

declare(strict_types=1);

namespace App\Mcp\Tool;

use App\Agentes\PaginasDoSite;
use App\Agentes\SiteAtual;
use App\Entity\Article;
use App\Repository\ArticleRepository;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(priority: 10)]
final class ListarNoticiasTool implements ToolInterface
{
    private const LIMITE = 20;

    public function __construct(private readonly SiteAtual $site, private readonly ArticleRepository $articles)
    {
    }

    public function name(): string { return 'listar_noticias'; }

    public function title(): string { return 'Listar notícias e posts'; }

    public function description(): string
    {
        return 'Lista as notícias ou posts do blog deste site, dos mais recentes para os mais antigos (até 20), com título, resumo, data e endereço. Filtro opcional por palavra.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => ['busca' => ['type' => 'string', 'description' => 'Palavra para filtrar pelo título ou resumo. Opcional.']],
            'additionalProperties' => false,
        ];
    }

    public function call(array $arguments): ToolResult
    {
        $tenant = $this->site->tenant();
        $prefixo = 'noticias' === $tenant->getTheme() ? '/noticias/' : ($tenant->hasBlog() ? '/blog/' : null);
        if (null === $prefixo) {
            return ToolResult::ok('Este site não publica notícias nem posts.', ['noticias' => []]);
        }
        $busca = mb_strtolower(trim((string) ($arguments['busca'] ?? '')));
        $lista = [];
        foreach ($this->articles->findAllVisible(withSamples: false) as $artigo) {
            if (!$artigo instanceof Article || '' === $artigo->getSlug()) {
                continue;
            }
            $resumo = PaginasDoSite::texto($artigo->getSummary());
            if ('' !== $busca && !str_contains(mb_strtolower($artigo->getTitle() . ' ' . $resumo), $busca)) {
                continue;
            }
            $caminho = $prefixo . rawurlencode($artigo->getSlug());
            $lista[] = array_filter([
                'titulo' => $artigo->getTitle(),
                'resumo' => $resumo ?: null,
                'data' => $artigo->getPublishedAt()?->format('Y-m-d'),
                'url' => $this->site->url($caminho),
                'markdown' => $this->site->url($caminho . '.md'),
            ]);
            if (\count($lista) >= self::LIMITE) {
                break;
            }
        }
        if ([] === $lista) {
            return ToolResult::ok('' === $busca ? 'Nenhuma notícia publicada.' : 'Nenhuma notícia menciona "' . $busca . '".', ['noticias' => []]);
        }
        $texto = \count($lista) . " notícia(s):\n";
        foreach ($lista as $n) {
            $texto .= "\n- " . ($n['data'] ?? '') . ' ' . $n['titulo'] . (isset($n['resumo']) ? ': ' . $n['resumo'] : '') . ' (' . $n['url'] . ')';
        }

        return ToolResult::ok($texto, ['noticias' => $lista]);
    }
}
