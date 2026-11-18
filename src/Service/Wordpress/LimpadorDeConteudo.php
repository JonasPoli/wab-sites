<?php

namespace App\Service\Wordpress;

/**
 * Limpa o HTML de um post do WordPress antes de virar notícia (importacao-de-wordpress).
 * Função pura: não sanitiza (quem chama passa o resultado pelo ArticleBodySanitizer).
 *
 *  - tira os shortcodes do construtor Divi ([et_pb_...]) e as incorporações de páginas do
 *    próprio WordPress (blockquote/iframe com a classe wp-embedded-content);
 *  - links para o próprio site: o permalink de um post importado vira /blog/{slug}; um
 *    caminho do mapa ($mapa: '/orcamento' => '/#contato') vira o destino; o resto vira
 *    caminho relativo (e pode cair num redirecionamento);
 *  - URL solta no texto, do próprio site e do mapa, vira link com o texto $textoDoLink;
 *  - link sem texto é removido.
 */
final class LimpadorDeConteudo
{
    /**
     * @param string[]              $slugsImportados slugs dos posts que existem no site novo
     * @param array<string, string> $mapa            caminho antigo normalizado => caminho novo
     */
    public function limpar(string $html, string $host, array $slugsImportados = [], array $mapa = [], string $textoDoLink = 'clique aqui'): string
    {
        $host = preg_replace('~^www\.~i', '', strtolower($host));
        $slugs = array_flip($slugsImportados);

        // Shortcodes do Divi (o WordPress devolve as aspas como entidades)
        $html = (string) preg_replace('~\[/?et_pb_[^\]]*\]~u', '', $html);
        // Incorporações de páginas do próprio site
        $html = (string) preg_replace('~<blockquote[^>]*wp-embedded-content[^>]*>.*?</blockquote>~is', '', $html);
        $html = (string) preg_replace('~<iframe[^>]*wp-embedded-content[^>]*>.*?</iframe>~is', '', $html);
        $html = (string) preg_replace('~<iframe[^>]*wp-embedded-content[^>]*/?>~is', '', $html);

        $dominio = '(?:https?://)?(?:www\.)?' . preg_quote($host, '~');

        // href para o próprio site
        $html = (string) preg_replace_callback('~href=(["\'])(' . $dominio . ')?(/[^"\']*)?\1~i', function (array $m) use ($slugs, $mapa) {
            $temDominio = ($m[2] ?? '') !== '';
            $caminho = $m[3] ?? '';
            if (!$temDominio && !str_starts_with($caminho, '/')) {
                return $m[0];
            }
            return 'href="' . htmlspecialchars($this->caminhoNovo($caminho === '' ? '/' : $caminho, $slugs, $mapa), ENT_QUOTES) . '"';
        }, $html);

        // URL do próprio site solta no texto (fora de atributos): só as do mapa viram link
        $html = (string) preg_replace_callback('~(?<![="\'/\w.])(' . $dominio . ')(/[^\s<"\']*)~i', function (array $m) use ($slugs, $mapa, $textoDoLink) {
            $chave = $this->normalizar($m[2]);
            if (!isset($mapa[$chave]) && !$this->ehPostImportado($m[2], $slugs)) {
                return $m[0];
            }
            return '<a href="' . htmlspecialchars($this->caminhoNovo($m[2], $slugs, $mapa), ENT_QUOTES) . '">' . htmlspecialchars($textoDoLink) . '</a>';
        }, $html);

        // Link sem texto (só espaço, quebra ou &nbsp;)
        $html = (string) preg_replace('~<a\b[^>]*>(?:\s|&nbsp;|<br\s*/?>|</?span[^>]*>)*</a>~i', '', $html);

        return trim($html);
    }

    /** Resumo: o excerpt do WordPress sem HTML; vazio ou com marcação do construtor, null. */
    public function resumo(?string $excerpt): ?string
    {
        $texto = (string) $excerpt;
        if ($texto === '' || str_contains($texto, '[et_pb') || str_contains($texto, '[/et_pb')) {
            return null;
        }
        $texto = html_entity_decode(strip_tags($texto), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $texto = trim((string) preg_replace(['~\s+~u', '~\s*(\[…\]|\[&hellip;\]|\[\.\.\.\])\s*$~u'], [' ', '…'], $texto));
        return $texto === '' ? null : $texto;
    }

    private function caminhoNovo(string $caminho, array $slugs, array $mapa): string
    {
        $chave = $this->normalizar($caminho);
        if (isset($mapa[$chave])) {
            return $mapa[$chave];
        }
        if (preg_match('~^/\d{4}/\d{2}/\d{2}/([^/]+)$~', $chave, $m) && isset($slugs[$m[1]])) {
            return '/blog/' . $m[1];
        }
        return $chave;
    }

    private function ehPostImportado(string $caminho, array $slugs): bool
    {
        return (bool) preg_match('~^/\d{4}/\d{2}/\d{2}/([^/]+)$~', $this->normalizar($caminho), $m) && isset($slugs[$m[1]]);
    }

    private function normalizar(string $caminho): string
    {
        $caminho = (string) preg_replace('~[?#].*$~', '', $caminho);
        $caminho = rtrim($caminho, '/');
        return $caminho === '' ? '/' : $caminho;
    }
}
