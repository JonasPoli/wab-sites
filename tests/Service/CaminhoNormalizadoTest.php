<?php

namespace App\Tests\Service;

use App\Service\CaminhoNormalizado;
use PHPUnit\Framework\TestCase;

final class CaminhoNormalizadoTest extends TestCase
{
    /** @dataProvider caminhos */
    public function testNormaliza(string $entrada, string $esperado): void
    {
        self::assertSame($esperado, CaminhoNormalizado::de($entrada));
    }

    public static function caminhos(): array
    {
        return [
            'barra final'      => ['/2023/07/13/espaco-acolhedor/', '/2023/07/13/espaco-acolhedor'],
            'consulta'         => ['/category/tendencias/?utm_source=x', '/category/tendencias'],
            'fragmento'        => ['/home2/#topo', '/home2'],
            'raiz'             => ['/', '/'],
            'vazio'            => ['', '/'],
            'sem barra'        => ['home2', '/home2'],
            'url completa'     => ['https://www.exemplo.com.br/2023/08/31/209467/', '/2023/08/31/209467'],
            'codificado'       => ['/categoria/decora%C3%A7%C3%A3o/', '/categoria/decoração'],
            'barras repetidas' => ['//feed//', '/feed'],
        ];
    }
}
