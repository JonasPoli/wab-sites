<?php

declare(strict_types=1);

namespace App\Controller\pub;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Page;
use App\Repository\ArticleRepository;
use App\Repository\AuthorRepository;
use App\Repository\CategoryRepository;
use App\Repository\PageRepository;
use App\Service\TenantContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * `/sitemap.xml` do domínio aberto.
 *
 * Ver a requirement "Sitemap" da spec `construtor-de-paginas`: entram a home, as páginas
 * já publicadas (menos a que serve de home, que seria conteúdo repetido), as categorias
 * com conteúdo e, no tema `noticias`, as notícias visíveis que não são de exemplo, as
 * editorias delas e os autores aprovados que publicaram. O `lastmod` é a alteração real.
 */
class SitemapController extends AbstractController
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly PageRepository $pageRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly ArticleRepository $articleRepository,
        private readonly AuthorRepository $authorRepository,
    ) {
    }

    #[Route('/sitemap.xml', name: 'pub_sitemap', defaults: ['_format' => 'xml'])]
    public function sitemap(Request $request): Response
    {
        $tenant = $this->tenantContext->getTenant();
        $base = rtrim($request->getSchemeAndHttpHost(), '/');
        $agora = new \DateTimeImmutable();

        $urls = [];
        $maisRecente = null;

        $idDaHome = $tenant?->getHomePage()?->getId();

        // Páginas: só as já publicadas e sem repetir a que serve de home
        $paginas = $tenant
            ? $this->pageRepository->findBy(['tenant' => $tenant])
            : $this->pageRepository->findAll();

        foreach ($paginas as $pagina) {
            if (!$pagina instanceof Page || $pagina->getSlug() === '') {
                continue;
            }
            if ($pagina->getId() === $idDaHome) {
                $maisRecente = $this->maior($maisRecente, $pagina->getUpdatedAt());
                continue;
            }
            if ($pagina->getPublishedAt() > $agora) {
                continue;
            }
            $urls[] = [
                'loc' => $base . '/p/' . rawurlencode($pagina->getSlug()),
                'changefreq' => 'monthly',
                'priority' => '0.8',
                'lastmod' => $pagina->getUpdatedAt(),
            ];
            $maisRecente = $this->maior($maisRecente, $pagina->getUpdatedAt());
        }

        $ehPortal = $tenant?->getTheme() === 'noticias';
        $noticias = $ehPortal ? $this->articleRepository->findAllVisible(withSamples: false) : [];

        // Categorias que têm conteúdo publicado (página ou notícia visível)
        $categoriasComNoticia = $ehPortal ? $this->idsDasEditorias($noticias) : [];
        foreach ($this->categoriasDoSite($tenant) as $categoria) {
            $temPagina = $this->temPaginaPublicada($categoria, $agora);
            $temNoticia = isset($categoriasComNoticia[$categoria->getId()]);
            if (!$temPagina && !$temNoticia) {
                continue;
            }
            $urls[] = [
                'loc' => $base . '/categoria/' . rawurlencode($categoria->getSlug()),
                'changefreq' => $temNoticia ? 'daily' : 'monthly',
                'priority' => $temNoticia ? '0.6' : '0.5',
                'lastmod' => $maisRecente ?? $agora,
            ];
        }

        // Portal de notícias: notícia, editoria e autor
        if ($ehPortal) {
            $autores = [];
            foreach ($noticias as $noticia) {
                if (!$noticia instanceof Article || $noticia->getSlug() === '') {
                    continue;
                }
                $urls[] = [
                    'loc' => $base . '/noticias/' . rawurlencode($noticia->getSlug()),
                    'changefreq' => 'weekly',
                    'priority' => '0.7',
                    'lastmod' => $noticia->getUpdatedAt(),
                ];
                $maisRecente = $this->maior($maisRecente, $noticia->getUpdatedAt());

                $autor = $noticia->getAuthor();
                if ($autor !== null && $autor->getSlug() !== '' && !$autor->isSample()) {
                    $slug = $autor->getSlug();
                    $autores[$slug] = $this->maior($autores[$slug] ?? null, $noticia->getUpdatedAt());
                }
            }

            foreach ($autores as $slug => $quando) {
                $urls[] = [
                    'loc' => $base . '/autor/' . rawurlencode((string) $slug),
                    'changefreq' => 'weekly',
                    'priority' => '0.5',
                    'lastmod' => $quando,
                ];
            }

            if ($noticias !== []) {
                $urls[] = [
                    'loc' => $base . '/noticias',
                    'changefreq' => 'daily',
                    'priority' => '0.9',
                    'lastmod' => $maisRecente ?? $agora,
                ];
            }
        }

        // Blog de site institucional (blog-nos-sites-institucionais): posts, lista e categorias do blog
        if (!$ehPortal && $tenant?->hasBlog()) {
            $posts = $this->articleRepository->findAllVisible(withSamples: false);
            $categoriasDoBlog = $this->idsDasEditorias($posts);
            $maisRecenteDoBlog = null;
            foreach ($posts as $post) {
                if (!$post instanceof Article || $post->getSlug() === '') {
                    continue;
                }
                $urls[] = [
                    'loc' => $base . '/blog/' . rawurlencode($post->getSlug()),
                    'changefreq' => 'weekly',
                    'priority' => '0.7',
                    'lastmod' => $post->getUpdatedAt(),
                ];
                $maisRecenteDoBlog = $this->maior($maisRecenteDoBlog, $post->getUpdatedAt());
            }
            if ($posts !== []) {
                $maisRecente = $this->maior($maisRecente, $maisRecenteDoBlog);
                $urls[] = [
                    'loc' => $base . '/blog',
                    'changefreq' => 'daily',
                    'priority' => '0.9',
                    'lastmod' => $maisRecenteDoBlog ?? $agora,
                ];
                foreach ($this->categoriasDoSite($tenant) as $categoria) {
                    if (!isset($categoriasDoBlog[$categoria->getId()])) {
                        continue;
                    }
                    $urls[] = [
                        'loc' => $base . '/blog/categoria/' . rawurlencode($categoria->getSlug()),
                        'changefreq' => 'weekly',
                        'priority' => '0.6',
                        'lastmod' => $maisRecenteDoBlog ?? $agora,
                    ];
                }
            }
        }

        // A home encabeça a lista, com a alteração mais recente do site
        array_unshift($urls, [
            'loc' => $base . '/',
            'changefreq' => 'weekly',
            'priority' => '1.0',
            'lastmod' => $maisRecente ?? $agora,
        ]);

        return new Response($this->renderSitemap($urls), Response::HTTP_OK, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'X-Robots-Tag' => 'noindex',
        ]);
    }

    /** @return Category[] */
    private function categoriasDoSite(?object $tenant): array
    {
        $categorias = $tenant
            ? $this->categoryRepository->findBy(['tenant' => $tenant])
            : $this->categoryRepository->findAll();

        return array_values(array_filter(
            $categorias,
            static fn ($c) => $c instanceof Category && $c->getSlug() !== '' && !$c->isSample(),
        ));
    }

    /**
     * @param Article[] $noticias
     *
     * @return array<int, true> ids das editorias (principal e secundárias) com notícia visível
     */
    private function idsDasEditorias(array $noticias): array
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

    private function temPaginaPublicada(Category $categoria, \DateTimeImmutable $agora): bool
    {
        foreach ($categoria->getPages() as $pagina) {
            if ($pagina->getSlug() !== '' && $pagina->getPublishedAt() <= $agora) {
                return true;
            }
        }

        return false;
    }

    private function maior(?\DateTimeInterface $a, ?\DateTimeInterface $b): ?\DateTimeInterface
    {
        if ($a === null) {
            return $b;
        }
        if ($b === null) {
            return $a;
        }

        return $a >= $b ? $a : $b;
    }

    /** @param list<array{loc: string, changefreq: string, priority: string, lastmod: ?\DateTimeInterface}> $urls */
    private function renderSitemap(array $urls): string
    {
        $itens = '';
        foreach ($urls as $url) {
            $loc = htmlspecialchars($url['loc'], \ENT_XML1);
            $lastmod = isset($url['lastmod']) && $url['lastmod'] instanceof \DateTimeInterface
                ? "\n        <lastmod>" . $url['lastmod']->format(\DATE_ATOM) . '</lastmod>'
                : '';
            $itens .= "    <url>\n"
                . "        <loc>{$loc}</loc>{$lastmod}\n"
                . "        <changefreq>{$url['changefreq']}</changefreq>\n"
                . "        <priority>{$url['priority']}</priority>\n"
                . "    </url>\n";
        }

        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
            . "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n"
            . $itens
            . '</urlset>';
    }
}
