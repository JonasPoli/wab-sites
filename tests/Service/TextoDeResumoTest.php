<?php

declare(strict_types=1);

namespace App\Tests\Service;

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
}
