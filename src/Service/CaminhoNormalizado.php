<?php

namespace App\Service;

/**
 * Forma única de um caminho para comparar redirecionamentos: sem consulta nem fragmento,
 * decodificado, sem barras repetidas e sem barra final ("/" para vazio).
 */
final class CaminhoNormalizado
{
    public static function de(string $caminho): string
    {
        $caminho = trim($caminho);
        if (preg_match('~^[a-z][a-z0-9+.-]*://[^/]*(.*)$~i', $caminho, $m)) {
            $caminho = $m[1];
        }
        $caminho = (string) preg_replace('~[?#].*$~', '', $caminho);
        $caminho = rawurldecode($caminho);
        $caminho = '/' . ltrim($caminho, '/');
        $caminho = (string) preg_replace('~/{2,}~', '/', $caminho);
        $caminho = rtrim($caminho, '/');

        return $caminho === '' ? '/' : mb_substr($caminho, 0, 255);
    }
}
