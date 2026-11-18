<?php

namespace App\Tests\Entity;

use App\Entity\Enum\BlockType;
use PHPUnit\Framework\TestCase;

/**
 * Todo tipo de bloco tem formulário no painel e desenho em todos os temas institucionais.
 * Um tipo novo que esqueça um desses lugares sai do painel sem formulário (erro 500) ou
 * some em silêncio num tema (blocos-novos-do-construtor).
 */
final class BlockTypeCoberturaTest extends TestCase
{
    /** Temas que despacham os blocos por um _block.html.twig próprio. */
    private const TEMAS = ['cetec', 'moderno', 'wab'];

    private static function raiz(): string
    {
        return \dirname(__DIR__, 2);
    }

    public function testFormularioDoPainel(): void
    {
        foreach (BlockType::cases() as $tipo) {
            self::assertFileExists(self::raiz() . '/templates/admin/block/_form_' . $tipo->value . '.html.twig', "Falta o formulário do bloco {$tipo->value}");
        }
    }

    public function testParcialDoCetec(): void
    {
        foreach (BlockType::cases() as $tipo) {
            self::assertFileExists(self::raiz() . '/templates/themes/cetec/blocks/_' . $tipo->value . '.html.twig', "Falta o desenho do bloco {$tipo->value} no cetec");
        }
    }

    public function testTiposNosDespachantes(): void
    {
        foreach (self::TEMAS as $tema) {
            $despachante = (string) file_get_contents(self::raiz() . '/templates/themes/' . $tema . '/_block.html.twig');
            foreach (BlockType::cases() as $tipo) {
                self::assertStringContainsString("'" . $tipo->value . "'", $despachante, "O _block.html.twig do tema {$tema} não cita o bloco {$tipo->value}");
            }
        }
    }

    public function testTiposNaEscolhaDoPainel(): void
    {
        $escolha = (string) file_get_contents(self::raiz() . '/templates/admin/block/new.html.twig');
        foreach (BlockType::cases() as $tipo) {
            self::assertStringContainsString("key: '" . $tipo->value . "'", $escolha, "O bloco {$tipo->value} não está na escolha de tipo do painel");
        }
    }
}
