<?php

namespace App\Service;

/**
 * Famílias oferecidas para os títulos H1 a H5 na edição do tenant (/superadmin),
 * com a fonte de reserva e a faixa de pesos que cada uma tem no Google Fonts.
 * Lido pelo formulário, pela gravação (SuperAdminController) e pelo tenant_css_vars().
 */
final class HeadingFonts
{
    public const DEFAULT = 'Outfit';

    /** família => [rótulo do formulário, reserva, peso mínimo, peso máximo] */
    private const FAMILIES = [
        'Inter'            => ['Inter (Moderna, Geométrica)', 'sans-serif', 100, 900],
        'Roboto'           => ['Roboto (Neutra, Legível)', 'sans-serif', 100, 900],
        'Playfair Display' => ['Playfair Display (Elegante, Serifa)', 'serif', 400, 900],
        'Montserrat'       => ['Montserrat (Expressiva, Geométrica)', 'sans-serif', 100, 900],
        'Open Sans'        => ['Open Sans (Humanista, Legível)', 'sans-serif', 300, 800],
        'Outfit'           => ['Outfit (Contemporânea, Original do Tema)', 'sans-serif', 100, 900],
    ];

    /** @return array<string, string> família => rótulo, na ordem do formulário */
    public static function choices(): array
    {
        return array_map(fn (array $f) => $f[0], self::FAMILIES);
    }

    /** A família, se estiver no catálogo; senão, Outfit. */
    public static function normalize(?string $family): string
    {
        $family = trim((string) $family);
        return isset(self::FAMILIES[$family]) ? $family : self::DEFAULT;
    }

    /** 'serif' ou 'sans-serif'. */
    public static function fallback(string $family): string
    {
        return self::FAMILIES[self::normalize($family)][1];
    }

    /** O peso mais próximo dentro da faixa da família (o valor gravado não muda). */
    public static function clampWeight(string $family, int|string $weight): int
    {
        [, , $min, $max] = self::FAMILIES[self::normalize($family)];
        $w = is_numeric($weight) ? (int) $weight : 400;
        return max($min, min($max, $w));
    }
}
