<?php

namespace App\Tests\Service\Wordpress;

use App\Service\Wordpress\LimpadorDeConteudo;
use PHPUnit\Framework\TestCase;

/** Trechos reais de posts da Marcenaria Pichoneri (WordPress + Divi). */
final class LimpadorDeConteudoTest extends TestCase
{
    private const HOST = 'www.marcenariapichoneri.com.br';
    private const MAPA = ['/orcamento' => '/#contato'];

    private function limpar(string $html, array $slugs = []): string
    {
        return (new LimpadorDeConteudo())->limpar($html, self::HOST, $slugs, self::MAPA, 'peça seu orçamento');
    }

    public function testShortcodesDoDivi(): void
    {
        $html = '[et_pb_section bb_built=&#8221;1&#8243;][et_pb_row][et_pb_column type=&#8221;4_4&#8243;][et_pb_text _builder_version=&#8221;3.23.3&#8243;]<p>Texto</p>[/et_pb_text][/et_pb_column][/et_pb_row][/et_pb_section]';
        self::assertSame('<p>Texto</p>', $this->limpar($html));
    }

    public function testIncorporacaoDoOrcamento(): void
    {
        $html = '<p>Faça um orçamento!</p>'
            . '<blockquote class="wp-embedded-content" data-secret="SpBGsp5i5g"><p><a href="http://www.marcenariapichoneri.com.br/orcamento/">Orçamento</a></p></blockquote>'
            . '<p><iframe loading="lazy" class="wp-embedded-content" sandbox="allow-scripts" src="http://www.marcenariapichoneri.com.br/orcamento/embed/#?secret=SpBGsp5i5g" width="600" height="338"></iframe></p>';
        $limpo = $this->limpar($html);
        self::assertStringNotContainsString('blockquote', $limpo);
        self::assertStringNotContainsString('iframe', $limpo);
        self::assertStringContainsString('Faça um orçamento!', $limpo);
    }

    public function testLinksDoProprioSite(): void
    {
        self::assertStringContainsString('href="/#contato"', $this->limpar('<a class="et_pb_button" href="/orcamento/">Solicite um orçamento</a>'));
        self::assertStringContainsString('href="/blog/mesa-de-madeira"', $this->limpar('<a href="https://www.marcenariapichoneri.com.br/2023/07/13/mesa-de-madeira/">veja</a>', ['mesa-de-madeira']));
        self::assertStringContainsString('href="/2023/07/13/outro"', $this->limpar('<a href="https://marcenariapichoneri.com.br/2023/07/13/outro/">veja</a>'));
        self::assertStringContainsString('href="https://outro.com/x"', $this->limpar('<a href="https://outro.com/x">fora</a>'));
    }

    public function testUrlSoltaNoTexto(): void
    {
        $limpo = $this->limpar('<p>Venha fazer sua cozinha.<br />http://www.marcenariapichoneri.com.br/orcamento/</p><p>marcenariapichoneri.com.br/orcamento/</p>');
        self::assertSame(2, substr_count($limpo, '<a href="/#contato">peça seu orçamento</a>'));
        self::assertStringNotContainsString('marcenariapichoneri.com.br', $limpo);
    }

    public function testLinkVazio(): void
    {
        self::assertSame('<p>Venha fazer a sua conosco</p><p></p>', $this->limpar('<p>Venha fazer a sua conosco</p><p><a href="marcenariapichoneri.com.br/orcamento/"><br />' . "\n" . '</a></p>'));
    }

    public function testResumo(): void
    {
        $l = new LimpadorDeConteudo();
        self::assertNull($l->resumo(''));
        self::assertNull($l->resumo('<p>[et_pb_section] texto</p>'));
        self::assertSame('Transformar um ambiente vai além da estética…', $l->resumo("<p>Transformar um ambiente vai além da estética [&hellip;]</p>\n"));
    }
}
