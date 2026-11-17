<?php

declare(strict_types=1);

namespace App\Service\Seo;

/**
 * O que o `<head>` de uma página pública precisa imprimir. Montado pelo {@see PageSeo}
 * e consumido por `templates/pub/_seo_head.html.twig`, que é o único lugar que imprime
 * título, descrição, canônico, Open Graph, Twitter Card e JSON-LD nos quatro temas.
 */
final class SeoData
{
    /**
     * @param list<array<string, mixed>> $jsonLd um nó do schema.org por item
     */
    public function __construct(
        public readonly string $title,
        public readonly string $description,
        public readonly string $canonical,
        public readonly string $robots = 'index, follow',
        public readonly string $ogType = 'website',
        public readonly ?string $image = null,
        public readonly ?int $imageWidth = null,
        public readonly ?int $imageHeight = null,
        public readonly array $jsonLd = [],
        public readonly ?string $siteName = null,
    ) {
    }

    public function hasImage(): bool
    {
        return $this->image !== null && $this->image !== '';
    }
}
