<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Agentes\MarkdownDaPagina;
use App\Agentes\PaginasDoSite;
use App\Controller\pub\ConteudoParaAgentesController;
use App\Service\TenantContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Nas respostas HTML das páginas públicas (spec conteudo-para-agentes): cabeçalhos `Link`
 * de descoberta e `Vary: Accept`; e, quando o pedido prefere `text/markdown`, troca o HTML
 * pela versão Markdown da mesma página.
 */
final class AgentesNasRespostasSubscriber implements EventSubscriberInterface
{
    public const ROTAS_DE_PAGINA = [
        'pub_home', 'pub_page_show', 'pub_category', 'pub_article_show', 'pub_author', 'pub_articles',
        'pub_blog', 'pub_blog_post', 'pub_blog_category',
    ];

    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly PaginasDoSite $paginas,
        private readonly MarkdownDaPagina $conversor,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Depois do TenantSubscriber (100) e antes do roteador (32).
            KernelEvents::REQUEST => ['onKernelRequest', 40],
            KernelEvents::RESPONSE => ['onKernelResponse', -10],
        ];
    }

    /**
     * Endereço terminado em .md vai direto para a versão Markdown: sem isso, rotas como
     * /p/{slug} capturariam "slug.md" e responderiam 404.
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $caminho = $request->getPathInfo();
        if (!$event->isMainRequest() || !str_ends_with($caminho, '.md') || \strlen($caminho) < 5
            || str_starts_with($caminho, '/.well-known/') || '/auth.md' === $caminho
            || !\in_array($request->getMethod(), ['GET', 'HEAD'], true) || null === $this->tenantContext->getTenant()) {
            return;
        }
        $request->attributes->add([
            '_route' => 'pub_markdown',
            '_controller' => ConteudoParaAgentesController::class . '::markdown',
            'caminho' => substr($caminho, 1, -3),
        ]);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();
        $tenant = $this->tenantContext->getTenant();
        if (!$event->isMainRequest() || null === $tenant || !\in_array($request->getMethod(), ['GET', 'HEAD'], true)
            || !\in_array($request->attributes->get('_route'), self::ROTAS_DE_PAGINA, true)
            || 200 !== $response->getStatusCode() || !str_starts_with((string) $response->headers->get('Content-Type', 'text/html'), 'text/html')) {
            return;
        }

        $base = $request->getSchemeAndHttpHost();
        $caminho = $request->getPathInfo();
        $response->headers->set('Link', implode(', ', [
            '<' . $base . PaginasDoSite::enderecoMarkdown($caminho) . '>; rel="alternate"; type="text/markdown"',
            '<' . $base . '/llms.txt>; rel="describedby"; type="text/plain"',
            '<' . $base . '/sitemap.xml>; rel="sitemap"; type="application/xml"',
            '<' . $base . '/.well-known/api-catalog>; rel="api-catalog"',
        ]), false);
        $response->setVary(array_unique([...$response->getVary(), 'Accept']));

        if (!self::prefereMarkdown($request)) {
            return;
        }
        $pagina = $this->paginas->porCaminho($tenant, $caminho);
        if (null === $pagina) {
            return;
        }
        $markdown = $this->conversor->converter((string) $response->getContent(), $tenant, $base, $pagina);
        $response->setContent($markdown);
        $response->headers->set('Content-Type', 'text/markdown; charset=utf-8');
        $response->headers->set('X-Markdown-Tokens', (string) (int) ceil(mb_strlen($markdown) / 4));
    }

    /** Vale o primeiro, em ordem de preferência, entre text/markdown, text/html, text/* e * / *. */
    public static function prefereMarkdown(Request $request): bool
    {
        $accept = (string) $request->headers->get('Accept');
        if ('' === trim($accept)) {
            return false;
        }
        foreach (AcceptHeader::fromString($accept)->all() as $item) {
            if ($item->getQuality() <= 0) {
                continue;
            }
            $valor = strtolower($item->getValue());
            if ('text/markdown' === $valor) {
                return true;
            }
            if (\in_array($valor, ['text/html', 'application/xhtml+xml', 'text/*', '*/*'], true)) {
                return false;
            }
        }

        return false;
    }
}
