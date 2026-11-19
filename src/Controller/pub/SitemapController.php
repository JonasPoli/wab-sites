<?php

declare(strict_types=1);

namespace App\Controller\pub;

use App\Agentes\PaginasDoSite;
use App\Service\TenantContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * `/sitemap.xml` do domínio aberto. A lista vem de App\Agentes\PaginasDoSite.
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
        private readonly PaginasDoSite $paginasDoSite,
    ) {
    }

    #[Route('/sitemap.xml', name: 'pub_sitemap', defaults: ['_format' => 'xml'])]
    public function sitemap(Request $request): Response
    {
        $base = rtrim($request->getSchemeAndHttpHost(), '/');
        $urls = array_map(static fn (array $item) => [
            'loc' => $base . $item['caminho'],
            'changefreq' => $item['changefreq'],
            'priority' => $item['priority'],
            'lastmod' => $item['lastmod'],
        ], $this->paginasDoSite->todas($this->tenantContext->getTenant()));

        return new Response($this->renderSitemap($urls), Response::HTTP_OK, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'X-Robots-Tag' => 'noindex',
        ]);
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
