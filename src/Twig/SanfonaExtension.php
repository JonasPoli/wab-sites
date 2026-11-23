<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Célula da tabela do bloco Sanfona (change bloco-sanfona): o texto do admin é sempre texto,
 * nunca HTML. Escapa tudo, transforma e-mails em `mailto:` e endereços `http(s)://` ou
 * `www.` em links, e quebras de linha em `<br>`.
 *
 *   {{ celula|celula_da_sanfona }}
 */
final class SanfonaExtension extends AbstractExtension
{
    private const LINKS = '~(?<url>\bhttps?://[^\s<>"\']+|\bwww\.[^\s<>"\']+)|(?<email>[A-Za-z0-9._%+\-]+@[A-Za-z0-9\-]+(?:\.[A-Za-z0-9\-]+)*\.[A-Za-z]{2,})~u';

    #[\Override]
    public function getFilters(): array
    {
        return [new TwigFilter('celula_da_sanfona', $this->celula(...), ['is_safe' => ['html']])];
    }

    public function celula(?string $texto): string
    {
        $texto = htmlspecialchars((string) $texto, \ENT_NOQUOTES | \ENT_SUBSTITUTE | \ENT_HTML5, 'UTF-8');

        $texto = (string) preg_replace_callback(self::LINKS, static function (array $m): string {
            if (($m['email'] ?? '') !== '') {
                return '<a href="mailto:' . $m['email'] . '">' . $m['email'] . '</a>';
            }
            // Pontuação no fim ("veja www.site.com.br.") não faz parte do endereço
            $url = $m['url'];
            $resto = '';
            if (preg_match('~[.,;:!?)\]]+$~', $url, $p)) {
                $resto = $p[0];
                $url = substr($url, 0, -strlen($resto));
            }
            $href = str_starts_with($url, 'www.') ? 'https://' . $url : $url;

            return '<a href="' . $href . '" target="_blank" rel="noopener">' . $url . '</a>' . $resto;
        }, $texto);

        return nl2br($texto, false);
    }
}
