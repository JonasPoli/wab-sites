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
 * Regra da casa para site de cliente (mudança sites-prontos-para-agentes, D3): busca e
 * assistentes de IA podem ler o site; robôs de treino de modelos, não.
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

    /** Robôs de busca com IA e de assistentes que buscam a página quando alguém pergunta. */
    public const ASSISTENTES = [
        'OAI-SearchBot', 'ChatGPT-User', 'Claude-SearchBot', 'Claude-User', 'PerplexityBot',
        'Perplexity-User', 'DuckAssistBot', 'MistralAI-User', 'Meta-ExternalFetcher',
    ];

    /** Robôs (e marcadores) de coleta para treino de modelos: proibidos em sites de cliente. */
    public const TREINO = [
        'GPTBot', 'ClaudeBot', 'anthropic-ai', 'Google-Extended', 'Applebot-Extended', 'CCBot',
        'Bytespider', 'Meta-ExternalAgent', 'FacebookBot', 'Amazonbot', 'cohere-ai', 'Diffbot',
        'ImagesiftBot', 'img2dataset', 'omgili',
    ];

    public const CONTENT_SIGNAL = 'search=yes, ai-input=yes, ai-train=no';

    #[Route('/robots.txt', name: 'pub_robots', defaults: ['_format' => 'txt'], methods: ['GET', 'HEAD'])]
    public function robots(Request $request, TenantContext $tenantContext): Response
    {
        $tenant = $tenantContext->getTenant();
        $base = $request->getSchemeAndHttpHost();

        if ($tenant === null) {
            // Domínio que aponta para o servidor sem ser site: nada para indexar.
            $corpo = "User-agent: *\nDisallow: /\n";
        } else {
            $linhas = ['# robots.txt de ' . $request->getHost(), '#',
                '# Buscadores e assistentes de IA podem ler o site; robôs de treino de modelos, não.',
                '# Versão para agentes: ' . $base . '/llms.txt', ''];
            $linhas[] = '# Buscadores e demais robôs';
            $linhas[] = 'User-agent: *';
            $linhas[] = 'Content-Signal: ' . self::CONTENT_SIGNAL;
            foreach (self::PROIBIDOS as $caminho) {
                $linhas[] = 'Disallow: ' . $caminho;
            }
            $linhas[] = 'Allow: /';
            $linhas[] = '';
            $linhas[] = '# Busca com IA e assistentes';
            foreach (self::ASSISTENTES as $robo) {
                $linhas[] = 'User-agent: ' . $robo;
            }
            $linhas[] = 'Content-Signal: ' . self::CONTENT_SIGNAL;
            foreach (self::PROIBIDOS as $caminho) {
                $linhas[] = 'Disallow: ' . $caminho;
            }
            $linhas[] = 'Allow: /';
            $linhas[] = '';
            $linhas[] = '# Treino de modelos de IA';
            foreach (self::TREINO as $robo) {
                $linhas[] = 'User-agent: ' . $robo;
            }
            $linhas[] = 'Disallow: /';
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
