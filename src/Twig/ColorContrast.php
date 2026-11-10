<?php

namespace App\Twig;

/**
 * Contraste entre duas cores pela fórmula do WCAG 2.x (luminância relativa).
 * Usado pelo tema noticias para decidir a cor dos títulos no modo escuro.
 */
final class ColorContrast
{
    /** Razão de contraste de 1 a 21. Cor inválida ou vazia dá 1 (sem contraste). */
    public static function ratio(?string $a, ?string $b): float
    {
        $la = self::luminance($a);
        $lb = self::luminance($b);
        if ($la === null || $lb === null) {
            return 1.0;
        }
        [$hi, $lo] = $la >= $lb ? [$la, $lb] : [$lb, $la];
        return ($hi + 0.05) / ($lo + 0.05);
    }

    /** Luminância relativa de 0 a 1, ou null para cor que não é #rgb ou #rrggbb. */
    public static function luminance(?string $hex): ?float
    {
        $hex = ltrim(trim((string) $hex), '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (strlen($hex) !== 6 || !ctype_xdigit($hex)) {
            return null;
        }
        $channel = function (string $h): float {
            $c = hexdec($h) / 255;
            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        };
        return 0.2126 * $channel(substr($hex, 0, 2))
             + 0.7152 * $channel(substr($hex, 2, 2))
             + 0.0722 * $channel(substr($hex, 4, 2));
    }
}
