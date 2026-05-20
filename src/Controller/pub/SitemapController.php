<?php

namespace App\Controller\pub;

use App\Repository\PageRepository;
use App\Service\TenantContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SitemapController extends AbstractController
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly PageRepository $pageRepository,
    ) {}

    #[Route('/sitemap.xml', name: 'pub_sitemap', defaults: ['_format' => 'xml'])]
    public function sitemap(Request $request): Response
    {
        $tenant  = $this->tenantContext->getTenant();
        $baseUrl = $request->getSchemeAndHttpHost();

        $urls = [];

        // Home
        $urls[] = [
            'loc'        => $baseUrl . '/',
            'changefreq' => 'weekly',
            'priority'   => '1.0',
            'lastmod'    => date('Y-m-d'),
        ];

        // Páginas públicas — filtradas pelo tenant atual
        $pages = $tenant
            ? $this->pageRepository->findBy(['tenant' => $tenant])
            : $this->pageRepository->findAll();

        foreach ($pages as $page) {
            if (!$page->getSlug()) {
                continue;
            }
            $urls[] = [
                'loc'        => $baseUrl . '/p/' . $page->getSlug(),
                'changefreq' => 'monthly',
                'priority'   => '0.8',
                'lastmod'    => $page->getUpdatedAt()?->format('Y-m-d') ?? date('Y-m-d'),
            ];
        }

        $xml = $this->renderSitemap($urls);

        return new Response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'X-Robots-Tag' => 'noindex',
        ]);
    }

    private function renderSitemap(array $urls): string
    {
        $items = '';
        foreach ($urls as $url) {
            $lastmod    = isset($url['lastmod']) ? "\n        <lastmod>{$url['lastmod']}</lastmod>" : '';
            $changefreq = isset($url['changefreq']) ? "\n        <changefreq>{$url['changefreq']}</changefreq>" : '';
            $priority   = isset($url['priority']) ? "\n        <priority>{$url['priority']}</priority>" : '';

            $items .= <<<XML
    <url>
        <loc>{$url['loc']}</loc>{$lastmod}{$changefreq}{$priority}
    </url>

XML;
        }

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
{$items}</urlset>
XML;
    }
}
