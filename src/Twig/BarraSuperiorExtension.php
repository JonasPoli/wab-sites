<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\Tenant;
use App\Service\BarraSuperior;
use App\Service\TenantContext;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * `barra_superior()`: a barra acima do cabeçalho do site do domínio (change
 * barra-superior-configuravel), usada por `themes/_topbar.html.twig`.
 */
final class BarraSuperiorExtension extends AbstractExtension
{
    public function __construct(
        private readonly BarraSuperior $barra,
        private readonly TenantContext $tenantContext,
    ) {
    }

    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('barra_superior', fn (): ?array => $this->barra->doSite($this->tenantContext->getTenant())),
            new TwigFunction('barra_superior_editor', self::editor(...)),
        ];
    }

    /**
     * Estado inicial do editor do painel (admin/_editor_da_barra_superior.html.twig): itens já
     * normalizados (o formato antigo vira itens) e, por tipo, o dado do site que o item usa.
     *
     * @return array{ligada: bool, cor: string, left: list<array>, right: list<array>, dados: array<string, string>}
     */
    public static function editor(Tenant $tenant): array
    {
        $nav = $tenant->getNavigationSettings() ?? [];
        $horas = [];
        foreach ($tenant->getOpeningHours() ?? [] as $h) {
            $horas[] = trim(($h['days'] ?? '') . ' ' . ($h['hours'] ?? ''));
        }
        $redes = array_keys(array_filter([
            'WhatsApp'  => $tenant->getWhatsappLink(),
            'Instagram' => $tenant->getInstagramLink(),
            'Facebook'  => $tenant->getFacebookLink(),
            'YouTube'   => $tenant->getYoutubeLink(),
            'LinkedIn'  => $tenant->getLinkedinLink(),
        ], static fn ($v) => trim((string) $v) !== ''));
        $whats = (string) $tenant->getWhatsappLink();

        return [
            'ligada' => !empty($nav['topBarEnabled']),
            'cor'    => BarraSuperior::normalizarCor($nav['topBarBg'] ?? ''),
            'left'   => BarraSuperior::normalizarLado($nav['topBarLeft'] ?? []),
            'right'  => BarraSuperior::normalizarLado($nav['topBarRight'] ?? []),
            'dados'  => [
                'phone'    => (string) $tenant->getPhone(),
                'whatsapp' => $whats === '' ? '' : (BarraSuperior::numeroDoWhatsapp($whats) ?? $whats),
                'email'    => (string) $tenant->getContactEmail(),
                'address'  => (string) $tenant->getAddress(),
                'hours'    => implode(' – ', array_filter($horas)),
                'social'   => implode(', ', $redes),
            ],
        ];
    }
}
