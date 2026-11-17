<?php

declare(strict_types=1);

namespace App\Controller\pub;

use App\Service\TenantContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * `/robots.txt` por domínio. Substitui o `public/robots.txt`, que era o mesmo texto de
 * exemplo nos 9 sites e tinha a linha `Sitemap:` comentada.
 *
 * Ver a requirement "Regras de rastreamento por domínio" da spec `seo-das-paginas`.
 */
final class RobotsController extends AbstractController
{
    /** Caminhos sem valor de busca: painéis, login e o que é formulário ou resultado. */
    private const PROIBIDOS = [
        '/admin',
        '/superadmin',
        '/jornalista',
        '/login',
        '/logout',
        '/busca',
        '/contato',
        '/newsletter/',
        '/_profiler',
    ];

    #[Route('/robots.txt', name: 'pub_robots', defaults: ['_format' => 'txt'], methods: ['GET'])]
    public function robots(Request $request, TenantContext $tenantContext): Response
    {
        $tenant = $tenantContext->getTenant();
        $base = $request->getSchemeAndHttpHost();

        if ($tenant === null) {
            // Domínio que aponta para o servidor sem ser site: nada para indexar.
            $corpo = "User-agent: *\nDisallow: /\n";
        } else {
            $linhas = ['User-agent: *'];
            foreach (self::PROIBIDOS as $caminho) {
                $linhas[] = 'Disallow: ' . $caminho;
            }
            $linhas[] = 'Allow: /';
            $linhas[] = '';
            $linhas[] = 'Sitemap: ' . $base . '/sitemap.xml';
            $corpo = implode("\n", $linhas) . "\n";
        }

        return new Response($corpo, Response::HTTP_OK, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
