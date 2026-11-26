<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Page;
use App\Entity\PageBlock;
use App\Entity\PageSection;
use App\Service\Seo\TextoDeResumo;
use PHPUnit\Framework\TestCase;

/**
 * Cascata da descrição da spec `seo-das-paginas`: a descrição nunca sai vazia, nunca
 * passa de 160 caracteres e nunca corta palavra ao meio.
 */
final class TextoDeResumoTest extends TestCase
{
    public function testLimparTiraTagsEEntidades(): void
    {
        self::assertSame(
            'Primeiro parágrafo. Segundo parágrafo com negrito & companhia.',
            TextoDeResumo::limpar('<p>Primeiro parágrafo.</p><p>Segundo parágrafo com <b>negrito</b> &amp; companhia.</p>'),
        );
    }

    public function testLimparNaoGrudaFrasesSeparadasPorBr(): void
    {
        self::assertSame('Uma frase outra frase', TextoDeResumo::limpar('Uma frase<br>outra frase'));
    }

    public function testLimparDevolveVazioParaNulo(): void
    {
        self::assertSame('', TextoDeResumo::limpar(null));
        self::assertSame('', TextoDeResumo::limpar('   '));
    }

    public function testTextoCurtoNaoEhCortado(): void
    {
        $texto = 'Advocacia em Araraquara.';
        self::assertSame($texto, TextoDeResumo::cortar($texto));
    }

    public function testTextoLongoCortaNaFronteiraDePalavra(): void
    {
        $texto = str_repeat('palavra ', 40);
        $resumo = TextoDeResumo::cortar($texto);

        self::assertLessThanOrEqual(TextoDeResumo::MAXIMO, mb_strlen($resumo));
        self::assertStringEndsWith('…', $resumo);
        // Sem palavra partida: tudo o que sobrou antes das reticências é "palavra" inteira
        self::assertMatchesRegularExpression('~^(palavra )+palavra…$~u', $resumo);
    }

    public function testCorteNaoDeixaPontuacaoSolta(): void
    {
        $texto = str_repeat('abcde, ', 40);
        $resumo = TextoDeResumo::cortar($texto);

        self::assertStringEndsWith('…', $resumo);
        self::assertStringNotContainsString(',…', $resumo);
    }

    public function testResumirLimpaECorta(): void
    {
        $html = '<p>' . str_repeat('texto longo ', 30) . '</p>';
        $resumo = TextoDeResumo::resumir($html);

        self::assertStringNotContainsString('<', $resumo);
        self::assertLessThanOrEqual(TextoDeResumo::MAXIMO, mb_strlen($resumo));
    }

    /** Página com um banner (título e subtítulo do 1º slide copiados para o bloco, como o painel grava) e um bloco de texto. */
    private static function paginaComBanner(array $configDoBanner): Page
    {
        $pagina = (new Page())->setTitle('Início')->setSlug('inicio');
        $secao = (new PageSection())->setPage($pagina)->setPosition(0);
        $pagina->getSections()->add($secao);
        $secao->getBlocks()->add((new PageBlock())->setSection($secao)->setType('banner')
            ->setTitle('Novembro Azul')->setText('Há 26 anos valorizando a saúde e o bem-estar.')->setConfig($configDoBanner));
        $secao->getBlocks()->add((new PageBlock())->setSection($secao)->setType('text_only')
            ->setText('<p>Equipamentos médicos hospitalares fabricados no Brasil.</p>'));

        return $pagina;
    }

    public function testBannerComTextoEntraNoResumo(): void
    {
        $resumo = TextoDeResumo::daPagina(self::paginaComBanner(['banners' => [['image' => 'a.png', 'active' => '1', 'alt' => 'Arte da campanha']]]));

        self::assertStringStartsWith('Há 26 anos valorizando a saúde', $resumo);
    }

    public function testBannerSoComAImagemEntraPelaDescricaoDaImagem(): void
    {
        $resumo = TextoDeResumo::daPagina(self::paginaComBanner(['imageOnly' => '1', 'banners' => [
            ['image' => 'velha.png', 'active' => '0', 'alt' => 'Slide desativado com uma descrição bem comprida'],
            ['image' => 'a.png', 'active' => '1', 'alt' => 'Campanha Novembro Azul: cuidar de você também é prevenção'],
        ]]));

        self::assertStringStartsWith('Campanha Novembro Azul: cuidar de você também é prevenção', $resumo);
        self::assertStringNotContainsString('Há 26 anos', $resumo, 'subtítulo guardado e invisível fica fora');
        self::assertStringNotContainsString('desativado', $resumo);
    }

    public function testPrimeiroSlideSoComAImagemPorSlide(): void
    {
        $resumo = TextoDeResumo::daPagina(self::paginaComBanner(['banners' => [
            ['image' => 'a.png', 'active' => '1', 'imageOnly' => '1', 'alt' => 'Campanha Novembro Azul: cuidar de você também é prevenção'],
            ['image' => 'b.png', 'active' => '1', 'imageOnly' => '0', 'alt' => ''],
        ]]));

        self::assertStringStartsWith('Campanha Novembro Azul', $resumo);
        self::assertStringNotContainsString('Há 26 anos', $resumo);
    }

    public function testPrimeiroSlideComTextoNumBannerMisto(): void
    {
        $resumo = TextoDeResumo::daPagina(self::paginaComBanner(['banners' => [
            ['image' => 'a.png', 'active' => '1', 'imageOnly' => '0', 'alt' => ''],
            ['image' => 'b.png', 'active' => '1', 'imageOnly' => '1', 'alt' => 'Arte do segundo slide, que não entra no resumo'],
        ]]));

        self::assertStringStartsWith('Há 26 anos valorizando a saúde', $resumo);
        self::assertStringNotContainsString('Arte do segundo slide', $resumo);
    }

    public function testBannerSoComAImagemSemDescricaoFicaForaDoResumo(): void
    {
        $resumo = TextoDeResumo::daPagina(self::paginaComBanner(['imageOnly' => '1', 'banners' => [['image' => 'a.png', 'active' => '1', 'alt' => '']]]));

        self::assertSame('Equipamentos médicos hospitalares fabricados no Brasil.', $resumo);
    }
}
