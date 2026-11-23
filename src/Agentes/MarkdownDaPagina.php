<?php

declare(strict_types=1);

namespace App\Agentes;

use App\Entity\Tenant;
use League\HTMLToMarkdown\Converter\TableConverter;
use League\HTMLToMarkdown\HtmlConverter;

/**
 * Converte o HTML que o site já renderiza na versão Markdown da página (design D2):
 * conteúdo principal (`<main>`, ou o `<body>` sem cabeçalho, menu, rodapé e formulários),
 * com frontmatter (título, descrição, URL) e os dados de contato do site no fim.
 * Vale para qualquer tema e tipo de página.
 */
final class MarkdownDaPagina
{
    private const FORA = '//script|//style|//noscript|//svg|//iframe|//form|//nav|//header|//footer|//button|//template|//dialog|//select|//input|//textarea|//label|//*[@aria-hidden="true"]|//*[@hidden]|//*[@data-md="skip"]';

    public function converter(string $html, Tenant $tenant, string $base, array $pagina): string
    {
        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?>' . $html, \LIBXML_NOERROR | \LIBXML_NOWARNING);
        libxml_clear_errors();
        $xp = new \DOMXPath($doc);

        $raizes = $xp->query('//main');
        $raiz = $raizes && $raizes->length > 0 ? $raizes->item(0) : $doc->getElementsByTagName('body')->item(0);
        $markdown = '';
        if ($raiz instanceof \DOMNode) {
            foreach (iterator_to_array($xp->query(self::FORA, $raiz) ?: []) as $no) {
                $no->parentNode?->removeChild($no);
            }
            // Blocos e destaques vizinhos sem espaço no HTML ("<b>88</b><span>anos</span>") viram
            // texto colado no Markdown: um espaço depois de cada um resolve.
            foreach (iterator_to_array($xp->query('.//div|.//section|.//article|.//figure|.//figcaption|.//li|.//p|.//span|.//strong|.//b|.//em|.//a|.//h1|.//h2|.//h3|.//h4|.//h5|.//h6|.//td|.//th', $raiz) ?: []) as $el) {
                $el->parentNode?->insertBefore($doc->createTextNode(' '), $el->nextSibling);
            }
            $conteudo = '';
            foreach ($raiz->childNodes as $filho) {
                $conteudo .= $doc->saveHTML($filho);
            }
            $converter = new HtmlConverter(['strip_tags' => true, 'header_style' => 'atx', 'hard_break' => true, 'remove_nodes' => 'script style']);
            // Tabelas (sanfona do construtor) saem como tabelas Markdown — change bloco-sanfona
            $converter->getEnvironment()->addConverter(new TableConverter());
            $markdown = $converter->convert($conteudo);
        }

        $markdown = html_entity_decode($markdown, \ENT_QUOTES | \ENT_HTML5, 'UTF-8');
        // Endereços relativos viram absolutos, para o texto valer fora do site.
        $markdown = preg_replace('/\]\(\/(?!\/)/', '](' . $base . '/', $markdown) ?? $markdown;
        $markdown = preg_replace('/[ \t]+$/m', '', $markdown) ?? $markdown;
        // Recuo de layout não é estrutura: tira o recuo (menos de itens de lista) e espaços repetidos.
        $markdown = preg_replace('/^[ \t]+(?![-*+] |\d+\. )/m', '', $markdown) ?? $markdown;
        $markdown = preg_replace('/(?<=\S) {2,}(?=\S)/', ' ', $markdown) ?? $markdown;
        $markdown = trim((string) preg_replace("/\n{3,}/", "\n\n", $markdown));
        if (!preg_match('/^# /m', $markdown)) {
            $markdown = '# ' . $pagina['titulo'] . "\n\n" . $markdown;
        }

        $frontmatter = "---\n"
            . 'title: ' . json_encode($pagina['titulo'], \JSON_UNESCAPED_UNICODE) . "\n"
            . ('' !== $pagina['descricao'] ? 'description: ' . json_encode($pagina['descricao'], \JSON_UNESCAPED_UNICODE) . "\n" : '')
            . 'url: ' . $base . $pagina['caminho'] . "\n"
            . ($pagina['lastmod'] instanceof \DateTimeInterface ? 'updated: ' . $pagina['lastmod']->format('Y-m-d') . "\n" : '')
            . "---\n\n";

        $dados = DadosDoSite::de($tenant, $base);

        return $frontmatter . $markdown . "\n\n## Contato: " . $tenant->getName() . "\n\n" . DadosDoSite::emMarkdown($dados)
            . "\n\nÍndice do site para agentes: " . $base . "/llms.txt\n";
    }
}
