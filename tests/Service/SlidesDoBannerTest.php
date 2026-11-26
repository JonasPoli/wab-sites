<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\SlidesDoBanner;
use PHPUnit\Framework\TestCase;

/** Regra do "Só a imagem" por slide, com a opção antiga do bloco como reserva (change banner-so-imagem-por-slide). */
final class SlidesDoBannerTest extends TestCase
{
    public function testChaveDoSlideVenceADoBloco(): void
    {
        self::assertFalse(SlidesDoBanner::soImagem(['imageOnly' => '0'], ['imageOnly' => '1']));
        self::assertTrue(SlidesDoBanner::soImagem(['imageOnly' => '1'], ['imageOnly' => '0']));
    }

    public function testSemChaveNoSlideValeADoBloco(): void
    {
        self::assertTrue(SlidesDoBanner::soImagem(['title' => 'x'], ['imageOnly' => '1']));
        self::assertFalse(SlidesDoBanner::soImagem(['title' => 'x'], ['imageOnly' => '0']));
    }

    public function testSemNenhumaChaveEhFalso(): void
    {
        self::assertFalse(SlidesDoBanner::soImagem([], []));
    }

    public function testTodosSoImagem(): void
    {
        $config = ['banners' => [['imageOnly' => '1', 'active' => '1'], ['imageOnly' => '1', 'active' => '1']]];
        self::assertTrue(SlidesDoBanner::todosSoImagem($config));
    }

    public function testUmSoNaoBasta(): void
    {
        $config = ['banners' => [['imageOnly' => '1', 'active' => '1'], ['imageOnly' => '0', 'active' => '1']]];
        self::assertFalse(SlidesDoBanner::todosSoImagem($config));
    }

    public function testSlideDesativadoDeOutroTipoNaoConta(): void
    {
        $config = ['banners' => [['imageOnly' => '1', 'active' => '1'], ['imageOnly' => '0', 'active' => '0']]];
        self::assertTrue(SlidesDoBanner::todosSoImagem($config));
    }

    public function testSemSlideAtivoEhFalso(): void
    {
        self::assertFalse(SlidesDoBanner::todosSoImagem(['banners' => [['imageOnly' => '1', 'active' => '0']]]));
    }

    public function testBlocoGravadoComAOpcaoDoBloco(): void
    {
        $config = ['imageOnly' => '1', 'banners' => [['active' => '1'], ['active' => '1']]];
        self::assertTrue(SlidesDoBanner::todosSoImagem($config));
    }

    public function testBannerAntigoSemListaSegueOBloco(): void
    {
        self::assertTrue(SlidesDoBanner::todosSoImagem(['imageOnly' => '1']));
        self::assertFalse(SlidesDoBanner::todosSoImagem([]));
    }

    public function testPrimeiroComImagem(): void
    {
        $config = ['banners' => [['image' => 'a.png', 'active' => '0'], ['image' => '', 'active' => '1'], ['image' => 'c.png', 'active' => '1']]];
        self::assertSame('c.png', SlidesDoBanner::primeiroComImagem($config)['image']);
        self::assertNull(SlidesDoBanner::primeiroComImagem([]));
    }
}
