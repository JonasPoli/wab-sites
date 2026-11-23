<?php

declare(strict_types=1);

namespace App\Tests\Twig;

use App\Twig\SanfonaExtension;
use PHPUnit\Framework\TestCase;

/** Célula da tabela da sanfona: texto escapado, links e quebras de linha (change bloco-sanfona). */
final class CelulaDaSanfonaTest extends TestCase
{
    private function celula(string $texto): string
    {
        return (new SanfonaExtension())->celula($texto);
    }

    public function testHtmlDigitadoSaiComoTexto(): void
    {
        self::assertSame('&lt;b&gt;X&lt;/b&gt;', $this->celula('<b>X</b>'));
    }

    public function testEmailViraMailto(): void
    {
        self::assertSame('E-mail: <a href="mailto:comercial@jgp.com.br">comercial@jgp.com.br</a>', $this->celula('E-mail: comercial@jgp.com.br'));
    }

    public function testEnderecosViramLink(): void
    {
        self::assertSame('<a href="https://exemplo.com/a?b=1" target="_blank" rel="noopener">https://exemplo.com/a?b=1</a>', $this->celula('https://exemplo.com/a?b=1'));
        self::assertSame('Site: <a href="https://www.daninet.com.br" target="_blank" rel="noopener">www.daninet.com.br</a>.', $this->celula('Site: www.daninet.com.br.'));
    }

    public function testQuebraDeLinhaViraBr(): void
    {
        self::assertSame("Linha 1<br>\nLinha 2", $this->celula("Linha 1\nLinha 2"));
    }

    public function testEComercialEscapadoUmaVez(): void
    {
        self::assertSame('J. G. PACHECO &amp; CIA LTDA', $this->celula('J. G. PACHECO & CIA LTDA'));
    }

    public function testAspasNaoQuebramOLink(): void
    {
        self::assertStringContainsString('href="https://x.com"', $this->celula('"https://x.com"'));
    }
}
