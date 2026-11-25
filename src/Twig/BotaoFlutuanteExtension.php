<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\BotaoFlutuante;
use App\Service\TenantContext;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * `botao_flutuante()`: o botão flutuante do site do domínio (change botao-flutuante-do-site),
 * usado por `themes/_comum/_botao_flutuante.html.twig`.
 */
final class BotaoFlutuanteExtension extends AbstractExtension
{
    public function __construct(
        private readonly BotaoFlutuante $botao,
        private readonly TenantContext $tenantContext,
    ) {
    }

    #[\Override]
    public function getFunctions(): array
    {
        return [new TwigFunction('botao_flutuante', fn (): ?array => $this->botao->doSite($this->tenantContext->getTenant()))];
    }
}
