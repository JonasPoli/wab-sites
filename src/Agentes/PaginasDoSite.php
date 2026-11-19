<?php

declare(strict_types=1);

namespace App\Agentes;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Page;
use App\Entity\Tenant;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use App\Repository\PageRepository;

/**
 * As páginas públicas de um site (tenant): a mesma lista do sitemap, com título e descrição.
 *
 * Fonte única para o sitemap, o llms.txt, o servidor MCP, a API e o agente A2A
 * (design D1 da mudança sites-prontos-para-agentes). Entram a home, as páginas já
 * publicadas (menos a que serve de home), as categorias com conteúdo e, no tema
 * noticias ou com blog, as notícias e posts visíveis que não são de exemplo, as listas
 * e os autores que publicaram. O isolamento vem do tenant passado: toda consulta filtra
 * por ele.
 */
final class PaginasDoSite
{
    public function __construct(
        private readonly PageRepository $pageRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly ArticleRepository $articleRepository,
    ) {
    }

    /**
     * @return list<array{caminho: string, tipo: string, titulo: string, descricao: string, lastmod: ?\DateTimeInterface, changefreq: string, priority: string}>
     */
    public function todas(?Tenant $tenant): array
    {
        $agora = new \DateTimeImmutable();
        $itens = [];
        $maisRecente = null;
        $idDaHome = $tenant?->getHomePage()?->getId();

        $paginas = $tenant ? $this->pageRepository->findBy(['tenant' => $tenant]) : $this->pageRepository->findAll();
        foreach ($paginas as $pagina) {
            if (!$pagina instanceof Page || $pagina->getSlug() === '') {
                continue;
            }
            if ($pagina->getId() === $idDaHome) {
                $maisRecente = self::maior($maisRecente, $pagina->getUpdatedAt());
                continue;
            }
            if ($pagina->getPublishedAt() > $agora) {
                continue;
            }
            $itens[] = self::item('/p/' . rawurlencode($pagina->getSlug()), 'pagina',
                $pagina->getSeoTitle() ?: $pagina->getTitle(), (string) $pagina->getSeoDescription(),
                $pagina->getUpdatedAt(), 'monthly', '0.8');
            $maisRecente = self::maior($maisRecente, $pagina->getUpdatedAt());
        }

        $ehPortal = $tenant?->getTheme() === 'noticias';
        $noticias = $ehPortal ? $this->articleRepository->findAllVisible(withSamples: false) : [];

        $categoriasComNoticia = $ehPortal ? self::idsDasEditorias($noticias) : [];
        foreach ($this->categoriasDoSite($tenant) as $categoria) {
            $temPagina = self::temPaginaPublicada($categoria, $agora);
            $temNoticia = isset($categoriasComNoticia[$categoria->getId()]);
            if (!$temPagina && !$temNoticia) {
                continue;
            }
            $itens[] = self::item('/categoria/' . rawurlencode($categoria->getSlug()), 'categoria',
                $categoria->getName(), self::texto($categoria->getDescription()),
                $maisRecente ?? $agora, $temNoticia ? 'daily' : 'monthly', $temNoticia ? '0.6' : '0.5');
        }

        if ($ehPortal) {
            $autores = [];
            foreach ($noticias as $noticia) {
                if (!$noticia instanceof Article || $noticia->getSlug() === '') {
                    continue;
                }
                $itens[] = self::item('/noticias/' . rawurlencode($noticia->getSlug()), 'noticia',
                    $noticia->getTitle(), self::texto($noticia->getSummary()),
                    $noticia->getUpdatedAt(), 'weekly', '0.7');
                $maisRecente = self::maior($maisRecente, $noticia->getUpdatedAt());

                $autor = $noticia->getAuthor();
                if ($autor !== null && $autor->getSlug() !== '' && !$autor->isSample()) {
                    $slug = $autor->getSlug();
                    $autores[$slug] = [
                        'nome' => $autor->getName(),
                        'quando' => self::maior($autores[$slug]['quando'] ?? null, $noticia->getUpdatedAt()),
                    ];
                }
            }
            foreach ($autores as $slug => $autor) {
                $itens[] = self::item('/autor/' . rawurlencode((string) $slug), 'autor',
                    (string) $autor['nome'], '', $autor['quando'], 'weekly', '0.5');
            }
            if ($noticias !== []) {
                $itens[] = self::item('/noticias', 'lista', 'Notícias', '', $maisRecente ?? $agora, 'daily', '0.9');
            }
        }

        if (!$ehPortal && $tenant?->hasBlog()) {
            $posts = $this->articleRepository->findAllVisible(withSamples: false);
            $categoriasDoBlog = self::idsDasEditorias($posts);
            $maisRecenteDoBlog = null;
            foreach ($posts as $post) {
                if (!$post instanceof Article || $post->getSlug() === '') {
                    continue;
                }
                $itens[] = self::item('/blog/' . rawurlencode($post->getSlug()), 'post',
                    $post->getTitle(), self::texto($post->getSummary()),
                    $post->getUpdatedAt(), 'weekly', '0.7');
                $maisRecenteDoBlog = self::maior($maisRecenteDoBlog, $post->getUpdatedAt());
            }
            if ($posts !== []) {
                $maisRecente = self::maior($maisRecente, $maisRecenteDoBlog);
                $itens[] = self::item('/blog', 'lista', 'Blog', '', $maisRecenteDoBlog ?? $agora, 'daily', '0.9');
                foreach ($this->categoriasDoSite($tenant) as $categoria) {
                    if (!isset($categoriasDoBlog[$categoria->getId()])) {
                        continue;
                    }
                    $itens[] = self::item('/blog/categoria/' . rawurlencode($categoria->getSlug()), 'categoria',
                        'Blog: ' . $categoria->getName(), self::texto($categoria->getDescription()),
                        $maisRecenteDoBlog ?? $agora, 'weekly', '0.6');
                }
            }
        }

        // A home encabeça a lista, com a alteração mais recente do site
        array_unshift($itens, self::item('/', 'home',
            $tenant ? ($tenant->getSeoTitle() ?: $tenant->getName()) : 'Início',
            $tenant ? self::texto($tenant->getSeoDescription() ?: $tenant->getAboutText()) : '',
            $maisRecente ?? $agora, 'weekly', '1.0'));

        return $itens;
    }

    /** O item cujo caminho é este (aceita o endereço .md e o URL completo), ou null. */
    public function porCaminho(?Tenant $tenant, string $caminho): ?array
    {
        $caminho = (string) (parse_url(trim($caminho), \PHP_URL_PATH) ?? '');
        $caminho = '' === $caminho || '/index.md' === $caminho ? '/' : $caminho;
        $caminho = str_ends_with($caminho, '.md') ? substr($caminho, 0, -3) : $caminho;
        $caminho = '/' !== $caminho ? rtrim($caminho, '/') : '/';
        foreach ($this->todas($tenant) as $item) {
            if ($item['caminho'] === $caminho || rawurldecode($item['caminho']) === rawurldecode($caminho)) {
                return $item;
            }
        }

        return null;
    }

    public static function enderecoMarkdown(string $caminho): string
    {
        return '/' === $caminho ? '/index.md' : $caminho . '.md';
    }

    public static function texto(?string $html, int $limite = 300): string
    {
        $texto = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $html), \ENT_QUOTES | \ENT_HTML5, 'UTF-8')));
        if (mb_strlen($texto) > $limite) {
            $texto = rtrim(mb_substr($texto, 0, $limite - 1)) . '…';
        }

        return $texto;
    }

    private static function item(string $caminho, string $tipo, string $titulo, string $descricao, ?\DateTimeInterface $lastmod, string $changefreq, string $priority): array
    {
        return ['caminho' => $caminho, 'tipo' => $tipo, 'titulo' => trim($titulo), 'descricao' => $descricao, 'lastmod' => $lastmod, 'changefreq' => $changefreq, 'priority' => $priority];
    }

    /** @return Category[] */
    private function categoriasDoSite(?Tenant $tenant): array
    {
        $categorias = $tenant ? $this->categoryRepository->findBy(['tenant' => $tenant]) : $this->categoryRepository->findAll();

        return array_values(array_filter($categorias, static fn ($c) => $c instanceof Category && $c->getSlug() !== '' && !$c->isSample()));
    }

    /** @param Article[] $noticias */
    private static function idsDasEditorias(array $noticias): array
    {
        $ids = [];
        foreach ($noticias as $noticia) {
            if ($noticia->getCategory() !== null) {
                $ids[$noticia->getCategory()->getId()] = true;
            }
            foreach ($noticia->getCategories() as $extra) {
                $ids[$extra->getId()] = true;
            }
        }

        return $ids;
    }

    private static function temPaginaPublicada(Category $categoria, \DateTimeImmutable $agora): bool
    {
        foreach ($categoria->getPages() as $pagina) {
            if ($pagina->getSlug() !== '' && $pagina->getPublishedAt() <= $agora) {
                return true;
            }
        }

        return false;
    }

    private static function maior(?\DateTimeInterface $a, ?\DateTimeInterface $b): ?\DateTimeInterface
    {
        if ($a === null) {
            return $b;
        }
        if ($b === null) {
            return $a;
        }

        return $a >= $b ? $a : $b;
    }
}
