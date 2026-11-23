<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\MapaDeRepresentantes;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Funções do bloco "Mapa de representantes" (change bloco-mapa-de-representantes):
 *
 *   estados_do_brasil()        sigla => nome, em ordem alfabética do nome
 *   mapa_por_estado(reps)      registros agrupados por sigla, na ordem dos estados
 */
final class MapaExtension extends AbstractExtension
{
    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('estados_do_brasil', static fn (): array => MapaDeRepresentantes::ESTADOS),
            new TwigFunction('mapa_por_estado', static fn (?array $reps): array => MapaDeRepresentantes::porEstado($reps ?? [])),
        ];
    }
}
