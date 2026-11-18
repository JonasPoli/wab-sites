<?php

namespace App\EventSubscriber;

use App\Repository\RedirectRepository;
use App\Service\CaminhoNormalizado;
use App\Service\TenantContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Redirecionamentos de endereços antigos (site-marcenaria-pichoneri): age só quando o pedido
 * GET/HEAD daria 404 no site do domínio. Página, rota e arquivo que existem nunca são
 * desviados, e um pedido sem cadastro continua com o 404 de sempre.
 */
final class RedirecionamentoSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly RedirectRepository $redirects,
    ) {}

    public static function getSubscribedEvents(): array
    {
        // Antes do tratamento padrão de exceções (prioridade 0 do ErrorListener)
        return [KernelEvents::EXCEPTION => ['onException', 64]];
    }

    public function onException(ExceptionEvent $event): void
    {
        if (!$event->isMainRequest() || !$event->getThrowable() instanceof NotFoundHttpException) {
            return;
        }
        $request = $event->getRequest();
        if (!in_array($request->getMethod(), ['GET', 'HEAD'], true)) {
            return;
        }
        $tenant = $this->tenantContext->getTenant();
        if ($tenant === null) {
            return;
        }

        try {
            $redirect = $this->redirects->findOneByPath($tenant, CaminhoNormalizado::de($request->getPathInfo()));
        } catch (\Throwable) {
            return; // banco fora do ar: segue o 404
        }
        if ($redirect === null) {
            return;
        }

        try {
            $this->redirects->incrementHits((int) $redirect->getId());
        } catch (\Throwable) {
            // a contagem não pode impedir o redirecionamento
        }
        $event->setResponse(new RedirectResponse($redirect->getToPath(), 301));
    }
}
