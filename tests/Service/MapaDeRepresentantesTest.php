<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\ArticleBodySanitizer;
use App\Service\MapaDeRepresentantes;
use PHPUnit\Framework\TestCase;

/** Normalização do bloco "Mapa de representantes" (change bloco-mapa-de-representantes). */
final class MapaDeRepresentantesTest extends TestCase
{
    private MapaDeRepresentantes $mapa;

    protected function setUp(): void
    {
        $this->mapa = new MapaDeRepresentantes(new ArticleBodySanitizer(sys_get_temp_dir() . '/wab-mapa-test-' . getmypid()));
    }

    private function reps(array $lista, array $extra = []): array
    {
        return $this->mapa->normalizar(['repsJson' => json_encode($lista)] + $extra);
    }

    public function testUfInvalidaDescartada(): void
    {
        $cfg = $this->reps([['uf' => 'XX', 'html' => '<p>a</p>'], ['uf' => 'ba', 'html' => '<p>b</p>']]);
        self::assertCount(1, $cfg['reps']);
        self::assertSame('BA', $cfg['reps'][0]['uf']);
    }

    public function testTitulosViramH3EScriptSai(): void
    {
        $cfg = $this->reps([['uf' => 'MS', 'html' => '<h1>A</h1><h2>Big Handra</h2><script>alert(1)</script><p onclick="x()">Oi</p>']]);
        self::assertSame('<h3>A</h3><h3>Big Handra</h3><p>Oi</p>', $cfg['reps'][0]['html']);
    }

    public function testTelefoneMantido(): void
    {
        $cfg = $this->reps([['uf' => 'MS', 'html' => '<p><a href="tel:+556799810726">67 99810 7261</a> <a href="javascript:alert(1)">x</a></p>']]);
        self::assertStringContainsString('href="tel:+556799810726"', $cfg['reps'][0]['html']);
        self::assertStringNotContainsString('javascript', $cfg['reps'][0]['html']);
    }

    public function testImagemComCaminhoDescartada(): void
    {
        $cfg = $this->reps([['uf' => 'SP', 'image' => '../../.env', 'html' => '<p>a</p>'], ['uf' => 'SP', 'image' => 'midia_1.png']]);
        self::assertNull($cfg['reps'][0]['image']);
        self::assertSame('midia_1.png', $cfg['reps'][1]['image']);
        self::assertNull($cfg['reps'][1]['html']);
    }

    public function testRegistroVazioDescartado(): void
    {
        self::assertSame([], $this->reps([['uf' => 'SP', 'html' => '<p><br></p>', 'image' => '']])['reps']);
    }

    public function testOrdemDoAdminEAgrupamento(): void
    {
        $cfg = $this->reps([['uf' => 'SP', 'html' => '<p>1</p>'], ['uf' => 'AC', 'html' => '<p>2</p>'], ['uf' => 'SP', 'html' => '<p>3</p>']]);
        self::assertSame(['SP', 'AC', 'SP'], array_column($cfg['reps'], 'uf'));
        $grupos = MapaDeRepresentantes::porEstado($cfg['reps']);
        self::assertSame(['AC', 'SP'], array_keys($grupos));
        self::assertSame(['<p>1</p>', '<p>3</p>'], array_column($grupos['SP'], 'html'));
    }

    public function testJsonInvalidoEMensagem(): void
    {
        $cfg = $this->mapa->normalizar(['repsJson' => '{x', 'emptyMessage' => "  Fale com a EMAI\r\n(11) 2335-1000  "]);
        self::assertSame(['reps' => [], 'emptyMessage' => "Fale com a EMAI\n(11) 2335-1000"], $cfg);
    }

    public function testLimite(): void
    {
        $lista = array_fill(0, 1200, ['uf' => 'RJ', 'html' => '<p>x</p>']);
        self::assertCount(MapaDeRepresentantes::MAX_REGISTROS, $this->reps($lista)['reps']);
    }
}
