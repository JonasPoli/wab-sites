<?php

declare(strict_types=1);

namespace App\Agentes;

use App\Entity\Tenant;
use App\Service\TenantContext;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * O site (tenant) do pedido e o endereço base dele (https://<domínio>), para os
 * documentos e protocolos de agentes. Sem tenant, 404: nada disso existe fora de um site.
 */
final class SiteAtual
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function tenant(): Tenant
    {
        return $this->tenantContext->getTenant() ?? throw new NotFoundHttpException('Domínio não configurado.');
    }

    public function base(): string
    {
        return (string) $this->requestStack->getMainRequest()?->getSchemeAndHttpHost();
    }

    public function host(): string
    {
        return (string) $this->requestStack->getMainRequest()?->getHost();
    }

    public function url(string $caminho): string
    {
        return $this->base() . $caminho;
    }

    /** @return array<string, mixed> */
    public function dados(): array
    {
        return DadosDoSite::de($this->tenant(), $this->base());
    }
}
