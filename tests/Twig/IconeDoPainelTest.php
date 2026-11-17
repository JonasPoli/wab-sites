<?php

declare(strict_types=1);

namespace App\Tests\Twig;

use App\Twig\IconeDoPainelExtension;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\UX\Icons\IconRendererInterface;

/**
 * Tradução da classe de CSS gravada no painel para o nome do Iconify, que é o que decide
 * se o ícone escolhido pelo cliente continua aparecendo depois da troca para SVG embutido.
 */
final class IconeDoPainelTest extends TestCase
{
    private IconeDoPainelExtension $extensao;

    protected function setUp(): void
    {
        $renderizador = new class implements IconRendererInterface {
            public function renderIcon(string $name, array $attributes = []): string
            {
                return '<svg data-nome="' . $name . '"></svg>';
            }
        };

        $this->extensao = new IconeDoPainelExtension($renderizador, new NullLogger());
    }

    /**
     * @return iterable<string, array{0: ?string, 1: ?string}>
     */
    public static function classes(): iterable
    {
        yield 'sólido do FontAwesome 6' => ['fa-solid fa-building', 'fa6-solid:building'];
        yield 'marca do FontAwesome 6' => ['fa-brands fa-whatsapp', 'fa6-brands:whatsapp'];
        yield 'regular do FontAwesome 6' => ['fa-regular fa-star', 'fa6-regular:star'];
        yield 'abreviação do FontAwesome 5' => ['fab fa-instagram', 'fa6-brands:instagram'];
        yield 'abreviação sólida' => ['fas fa-gears', 'fa6-solid:gears'];
        yield 'Bootstrap Icons' => ['bi bi-chevron-right', 'bi:chevron-right'];
        yield 'Bootstrap sem o prefixo solto' => ['bi-search', 'bi:search'];
        yield 'sem estilo vira sólido' => ['fa-building', 'fa6-solid:building'];
        yield 'nome renomeado no FontAwesome 6' => ['fa-solid fa-file-alt', 'fa6-solid:file-lines'];
        yield 'outro renomeado' => ['fas fa-money-check-alt', 'fa6-solid:money-check-dollar'];
        yield 'já no formato do Iconify' => ['fa6-solid:road', 'fa6-solid:road'];
        yield 'com classe de layout junto' => ['fa-solid fa-dog mr-1', 'fa6-solid:dog'];
        yield 'vazio' => ['', null];
        yield 'nulo' => [null, null];
        yield 'só classe de layout' => ['text-xl mr-2', null];
    }

    /**
     * @dataProvider classes
     */
    public function testNome(?string $classes, ?string $esperado): void
    {
        self::assertSame($esperado, $this->extensao->nome($classes));
    }

    public function testDesenharDevolveVazioQuandoNaoHaIcone(): void
    {
        self::assertSame('', $this->extensao->desenhar(null));
        self::assertSame('', $this->extensao->desenhar('text-xl'));
    }

    public function testDesenharUsaONomeTraduzido(): void
    {
        self::assertStringContainsString('data-nome="fa6-solid:building"', $this->extensao->desenhar('fa-solid fa-building'));
    }

    public function testIconeInexistenteNaoQuebraAPagina(): void
    {
        $quebrado = new class implements IconRendererInterface {
            public function renderIcon(string $name, array $attributes = []): string
            {
                throw new \RuntimeException('não existe');
            }
        };

        $extensao = new IconeDoPainelExtension($quebrado, new NullLogger());

        self::assertSame('', $extensao->desenhar('fa-solid fa-inexistente'));
    }
}
