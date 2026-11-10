<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Sanitiza o HTML do texto das notícias (editor Quill) antes de gravar.
 *
 * Mantém parágrafos, títulos, negrito, itálico, sublinhado, tachado, listas
 * (inclusive o <li data-list="bullet"> do Quill 2), citações, links http/https/mailto
 * e imagens http/https, e só as classes de alinhamento do Quill (ql-align-*).
 * Remove scripts, atributos de evento, iframe, style e URLs javascript:/data:.
 *
 * Usa HTMLPurifier: o symfony/html-sanitizer 7.2 (a versão travada no projeto)
 * tem alertas de segurança sem correção nessa linha.
 */
class ArticleBodySanitizer
{
    private ?\HTMLPurifier $purifier = null;

    public function __construct(
        #[Autowire('%kernel.cache_dir%/htmlpurifier')]
        private readonly string $cacheDir,
    ) {}

    public function sanitize(?string $html): ?string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return null;
        }

        // O Quill oferece "Título 1"; na página da notícia o h1 é o título dela.
        $html = preg_replace(['~<h1(\s[^>]*)?>~i', '~</h1\s*>~i'], ['<h2$1>', '</h2>'], $html);

        $clean = trim($this->purifier()->purify($html));

        // Editor vazio do Quill: <p><br></p>
        if ($clean === '' || preg_match('~^(<p>(\s|<br\s*/?>|&nbsp;)*</p>\s*)+$~i', $clean)) {
            return null;
        }

        return $clean;
    }

    private function purifier(): \HTMLPurifier
    {
        if ($this->purifier) {
            return $this->purifier;
        }

        (new Filesystem())->mkdir($this->cacheDir);

        $config = \HTMLPurifier_Config::createDefault();
        $config->set('Cache.SerializerPath', $this->cacheDir);
        $config->set('HTML.DefinitionID', 'wab-article-body');
        $config->set('HTML.DefinitionRev', 1);
        $config->set('HTML.Doctype', 'HTML 4.01 Transitional');
        $config->set('HTML.Allowed', implode(',', [
            'p[class]', 'br', 'h2[class]', 'h3[class]',
            'strong', 'b', 'em', 'i', 'u', 's',
            'ol[class]', 'ul[class]', 'li[class|data-list]',
            'blockquote[class]',
            'a[href|title|target]',
            'img[src|alt|title|width|height]',
        ]));
        $config->set('Attr.AllowedClasses', [
            'ql-align-center', 'ql-align-right', 'ql-align-justify',
        ]);
        $config->set('Attr.AllowedFrameTargets', ['_blank']);
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
        $config->set('AutoFormat.RemoveEmpty', false);

        if ($def = $config->maybeGetRawHTMLDefinition()) {
            // Quill 2 grava as listas como <ol><li data-list="bullet|ordered">
            $def->addAttribute('li', 'data-list', 'Enum#bullet,ordered,checked,unchecked');
        }

        return $this->purifier = new \HTMLPurifier($config);
    }
}
