<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\SlidesDoBanner;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * "Só a imagem" do bloco Banner nos templates dos temas (change banner-so-imagem-por-slide):
 *
 *   {% if banner_so_imagem(block.config ?? {}) %}  todos os slides ativos só com a imagem
 *   {% if slide_so_imagem(b, block.config ?? {}) %} este slide só com a imagem
 */
final class BannerExtension extends AbstractExtension
{
    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('banner_so_imagem', static fn (?array $config): bool => SlidesDoBanner::todosSoImagem($config ?? [])),
            new TwigFunction('slide_so_imagem', static fn (?array $slide, ?array $config): bool => SlidesDoBanner::soImagem($slide ?? [], $config ?? [])),
        ];
    }
}
