<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\ArticleBodySanitizer;
use App\Service\SanfonaDoBloco;
use PHPUnit\Framework\TestCase;

/** Normalização dos itens do bloco Sanfona (change bloco-sanfona). */
final class SanfonaDoBlocoTest extends TestCase
{
    private SanfonaDoBloco $sanfona;

    protected function setUp(): void
    {
        $this->sanfona = new SanfonaDoBloco(new ArticleBodySanitizer(sys_get_temp_dir() . '/wab-sanfona-test-' . getmypid()));
    }

    private function item(array $dados): ?array
    {
        return $this->sanfona->normalizar(['panels' => [$dados]])['panels'][0] ?? null;
    }

    public function testHtmlComScriptEEventoELimpo(): void
    {
        $item = $this->item(['title' => 'A', 'html' => '<p onclick="x()">Oi</p><script>alert(1)</script>']);
        self::assertSame('<p>Oi</p>', $item['html']);
    }

    public function testJsonInvalidoViraTabelaVazia(): void
    {
        $item = $this->item(['title' => 'A', 'kind' => 'table', 'table' => '{quebrado']);
        self::assertSame([], $item['table']['rows']);
        self::assertSame('table', $item['kind']);
    }

    public function testLinhaVaziaSai(): void
    {
        $item = $this->item(['title' => 'A', 'table' => json_encode(['header' => true, 'rows' => [['a', 'b'], ['', ' '], ['c', 'd']]])]);
        self::assertSame([['a', 'b'], ['c', 'd']], $item['table']['rows']);
    }

    public function testLimitesDeColunasELinhas(): void
    {
        $linhas = array_fill(0, 600, array_fill(0, 20, 'x'));
        $item = $this->item(['title' => 'A', 'table' => json_encode(['rows' => $linhas])]);
        self::assertCount(SanfonaDoBloco::MAX_LINHAS, $item['table']['rows']);
        self::assertCount(SanfonaDoBloco::MAX_COLUNAS, $item['table']['rows'][0]);
    }

    public function testLinhasCompletadasAteAMaiorLargura(): void
    {
        $item = $this->item(['title' => 'A', 'table' => json_encode(['rows' => [['a'], ['b', 'c', 'd'], ['e', '', '', '']]])]);
        self::assertSame([['a', '', ''], ['b', 'c', 'd'], ['e', '', '']], $item['table']['rows']);
    }

    public function testQuebraDeLinhaDaCelulaMantida(): void
    {
        $item = $this->item(['title' => 'A', 'table' => json_encode(['rows' => [["Linha 1\r\nLinha 2"]]])]);
        self::assertSame("Linha 1\nLinha 2", $item['table']['rows'][0][0]);
    }

    public function testItemSemTituloESemConteudoSai(): void
    {
        self::assertNull($this->item(['title' => '  ', 'html' => '<p><br></p>', 'table' => '{"rows":[]}']));
    }

    public function testItemSemTituloComConteudoFica(): void
    {
        $item = $this->item(['title' => '', 'html' => '<p>Texto</p>']);
        self::assertSame('', $item['title']);
        self::assertSame('<p>Texto</p>', $item['html']);
    }

    public function testTipoDesconhecidoViraTexto(): void
    {
        self::assertSame('text', $this->item(['title' => 'A', 'kind' => 'video'])['kind']);
    }

    public function testCabecalhoDesligado(): void
    {
        self::assertFalse($this->item(['title' => 'A', 'table' => '{"header":false,"rows":[["a"]]}'])['table']['header']);
    }

    public function testOpcoes(): void
    {
        $cfg = $this->sanfona->normalizar(['firstOpen' => '1', 'multiple' => '0', 'lixo' => 'x', 'panels' => []]);
        self::assertSame(['panels' => [], 'firstOpen' => '1'], $cfg);
    }

    public function testCelulaSoComImagemMantemALinha(): void
    {
        $item = $this->item(['title' => 'A', 'table' => json_encode(['rows' => [['Cidade', 'Logo'], ['', ['text' => '', 'image' => 'midia_abc.png']]]])]);
        self::assertSame([['Cidade', 'Logo'], ['', ['text' => '', 'image' => 'midia_abc.png']]], $item['table']['rows']);
    }

    public function testNomeDeImagemInvalidoDescartado(): void
    {
        $item = $this->item(['title' => 'A', 'table' => json_encode(['rows' => [[['text' => 'X', 'image' => '../.env'], ['text' => 'Y', 'image' => 'a/b.png']]]])]);
        self::assertSame([['X', 'Y']], $item['table']['rows']);
    }

    public function testCelulaDeTextoContinuaString(): void
    {
        $item = $this->item(['title' => 'A', 'table' => json_encode(['rows' => [['a', ['text' => 'b']]]])]);
        self::assertSame([['a', 'b']], $item['table']['rows']);
    }
}
