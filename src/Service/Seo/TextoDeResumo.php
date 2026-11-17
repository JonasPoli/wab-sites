<?php

declare(strict_types=1);

namespace App\Service\Seo;

use App\Entity\Page;

/**
 * Tira de um conteúdo o texto que serve de `<meta name="description">` quando ninguém
 * preencheu o campo de SEO: texto corrido, sem HTML, sem quebra de linha e cortado na
 * fronteira de palavra. É o terceiro degrau da cascata descrita na spec `seo-das-paginas`.
 */
final class TextoDeResumo
{
    public const MINIMO = 50;
    public const MAXIMO = 160;

    /** Texto limpo: sem tags, sem entidade HTML, sem espaço repetido. */
    public static function limpar(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }
        // <br> e fim de bloco viram espaço, para duas frases não grudarem
        $texto = preg_replace('~<(br|/p|/div|/li|/h[1-6])\b[^>]*>~i', ' ', $html) ?? $html;
        $texto = strip_tags($texto);
        $texto = html_entity_decode($texto, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $texto = preg_replace('~\s+~u', ' ', $texto) ?? $texto;

        return trim($texto);
    }

    /**
     * Corta em no máximo {@see MAXIMO} caracteres sem partir palavra. Texto que já cabe
     * volta inteiro; o corte termina sem pontuação solta e ganha reticências.
     */
    public static function cortar(string $texto, int $maximo = self::MAXIMO): string
    {
        $texto = trim($texto);
        if ($texto === '' || mb_strlen($texto) <= $maximo) {
            return $texto;
        }

        $corte = mb_substr($texto, 0, $maximo - 1);
        $ultimoEspaco = mb_strrpos($corte, ' ');
        if ($ultimoEspaco !== false && $ultimoEspaco > $maximo / 2) {
            $corte = mb_substr($corte, 0, $ultimoEspaco);
        }

        return rtrim($corte, " \t\n\r\0\x0B.,;:–—-") . '…';
    }

    /** Limpa e corta de uma vez. */
    public static function resumir(?string $html, int $maximo = self::MAXIMO): string
    {
        return self::cortar(self::limpar($html), $maximo);
    }

    /**
     * O primeiro texto útil de uma página montada em seções e blocos: percorre as seções
     * ativas na ordem e devolve o primeiro texto de bloco com conteúdo de verdade.
     */
    public static function daPagina(Page $pagina): string
    {
        $partes = [];

        foreach ($pagina->getSections() as $secao) {
            if (method_exists($secao, 'isActive') && !$secao->isActive()) {
                continue;
            }
            foreach ($secao->getBlocks() as $bloco) {
                foreach ([$bloco->getText(), $bloco->getTitle()] as $candidato) {
                    $limpo = self::limpar($candidato);
                    if (mb_strlen($limpo) >= 20) {
                        $partes[] = $limpo;
                    }
                }
                if (mb_strlen(implode(' ', $partes)) >= self::MAXIMO) {
                    break 2;
                }
            }
        }

        return self::cortar(trim(implode(' ', $partes)));
    }
}
