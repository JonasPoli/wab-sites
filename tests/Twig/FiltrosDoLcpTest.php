<?php

declare(strict_types=1);

namespace App\Tests\Twig;

use App\Entity\Page;
use App\Entity\PageBlock;
use App\Entity\PageSection;
use App\Twig\AtivosExtension;
use PHPUnit\Framework\TestCase;

/** Filtros da pré-carga do LCP: o `preload` tem de pedir o mesmo arquivo que a `<img>` (change banner-so-imagem). */
final class FiltrosDoLcpTest extends TestCase
{
    private static function pagina(string $tipo, ?array $config = null, ?string $imagem = null): Page
    {
        $pagina = (new Page())->setTitle('Teste')->setSlug('teste');
        $secao = (new PageSection())->setPage($pagina)->setPosition(0);
        $pagina->getSections()->add($secao);
        $bloco = (new PageBlock())->setSection($secao)->setType($tipo)->setConfig($config)->setImage($imagem);
        $secao->getBlocks()->add($bloco);

        return $pagina;
    }

    public function testBannerComTextoUsaOsFiltrosQueRecortam(): void
    {
        $lcp = (new AtivosExtension())->filtrosDoLcp(['page' => self::pagina('banner', ['banners' => [['image' => 'a.png', 'active' => '1']]])]);

        self::assertSame(['banner_mobile' => 768, 'banner_desktop' => 1600], $lcp['filtros']);
        self::assertSame('100vw', $lcp['sizes']);
    }

    public function testBannerSoComAImagemUsaOsFiltrosQueNaoRecortam(): void
    {
        $config = ['imageOnly' => '1', 'banners' => [['image' => 'a.png', 'active' => '1']]];
        $lcp = (new AtivosExtension())->filtrosDoLcp(['page' => self::pagina('banner', $config)]);

        self::assertSame(['banner_inteiro_mobile' => 768, 'banner_inteiro' => 1600], $lcp['filtros']);
        self::assertSame('100vw', $lcp['sizes']);
    }

    public function testOpcaoDesligadaContinuaComOsFiltrosDeHoje(): void
    {
        $config = ['imageOnly' => '0', 'banners' => [['image' => 'a.png', 'active' => '1']]];
        $lcp = (new AtivosExtension())->filtrosDoLcp(['page' => self::pagina('banner', $config)]);

        self::assertSame(['banner_mobile' => 768, 'banner_desktop' => 1600], $lcp['filtros']);
    }

    public function testTodosOsSlidesSoComAImagemPorSlide(): void
    {
        $config = ['banners' => [['image' => 'a.png', 'active' => '1', 'imageOnly' => '1'], ['image' => 'b.png', 'active' => '1', 'imageOnly' => '1']]];
        $lcp = (new AtivosExtension())->filtrosDoLcp(['page' => self::pagina('banner', $config)]);

        self::assertSame(['banner_inteiro_mobile' => 768, 'banner_inteiro' => 1600], $lcp['filtros']);
    }

    public function testBannerMistoUsaOsFiltrosDoBannerComTexto(): void
    {
        // O primeiro slide é "só a imagem", mas num banner misto ele preenche a área do banner com texto
        $config = ['banners' => [['image' => 'a.png', 'active' => '1', 'imageOnly' => '1'], ['image' => 'b.png', 'active' => '1', 'imageOnly' => '0']]];
        $lcp = (new AtivosExtension())->filtrosDoLcp(['page' => self::pagina('banner', $config)]);

        self::assertSame(['banner_mobile' => 768, 'banner_desktop' => 1600], $lcp['filtros']);
    }

    public function testDestaqueComFotoContinuaComOsFiltrosDoDestaque(): void
    {
        $lcp = (new AtivosExtension())->filtrosDoLcp(['page' => self::pagina('hero_split', null, 'foto.jpg')]);

        self::assertSame(['destaque_foto_mobile' => 800, 'destaque_foto' => 1400], $lcp['filtros']);
    }
}
