<?php

declare(strict_types=1);

namespace App\Agentes;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Renderiza uma página pública do mesmo site por subpedido interno e devolve o HTML,
 * para a conversão em Markdown (design D2). O tenant do pedido principal continua valendo.
 */
final class HtmlDaPagina
{
    public function __construct(
        private readonly HttpKernelInterface $kernel,
        private readonly RequestStack $requestStack,
    ) {
    }

    /** HTML da página no caminho, ou null se ela não responder 200. */
    public function de(string $caminho): ?string
    {
        $principal = $this->requestStack->getMainRequest();
        if (null === $principal) {
            return null;
        }
        $sub = Request::create($principal->getSchemeAndHttpHost() . $caminho, 'GET', [], [], [], [
            'HTTP_ACCEPT' => 'text/html',
            'HTTP_USER_AGENT' => 'wab-sites-markdown',
            'REMOTE_ADDR' => $principal->getClientIp() ?? '127.0.0.1',
        ]);
        // O formulário de contato gera token CSRF, que precisa da sessão.
        if ($principal->hasSession()) {
            $sub->setSession($principal->getSession());
        }
        try {
            $resposta = $this->kernel->handle($sub, HttpKernelInterface::SUB_REQUEST, false);
        } catch (\Throwable) {
            return null;
        }

        return 200 === $resposta->getStatusCode() ? (string) $resposta->getContent() : null;
    }
}
