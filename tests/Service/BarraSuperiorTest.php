<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Tenant;
use App\Service\BarraSuperior;
use PHPUnit\Framework\TestCase;

/** Barra superior (change barra-superior-configuravel): normalização e resolução dos itens. */
final class BarraSuperiorTest extends TestCase
{
    private function site(array $nav): Tenant
    {
        return (new Tenant())
            ->setPhone('(16) 3336-2414')
            ->setWhatsappLink('https://wa.me/5516996494545')
            ->setContactEmail('contato@exemplo.com.br')
            ->setAddress('Av. Brasil, 782 - Centro, Araraquara - SP')
            ->setInstagramLink('https://instagram.com/x')
            ->setOpeningHours([['days' => 'Seg a Sex', 'hours' => '8h às 18h'], ['days' => 'Sáb', 'hours' => '8h às 12h']])
            ->setNavigationSettings($nav + ['topBarEnabled' => true]);
    }

    public function testFormatoAntigoViraItens(): void
    {
        self::assertSame(
            ['hours', 'social', 'phone'],
            array_column(BarraSuperior::normalizarLado(['hours', 'social', 'phone']), 'type'),
        );
    }

    public function testDescartesELimites(): void
    {
        $itens = BarraSuperior::normalizarLado([
            ['type' => 'banner'],
            ['type' => 'custom', 'text' => '  ', 'icon' => ''],
            ['type' => 'custom', 'icon' => '<script>', 'text' => ''],
            ['type' => 'custom', 'text' => str_repeat('á', 100), 'url' => 'portal.escola.com.br', 'newTab' => '1'],
            ['type' => 'custom', 'icon' => 'fa-solid  fa-user-lock', 'url' => 'mailto:a@b.c'],
            ['type' => 'hours', 'text' => 'ignorado'],
        ]);
        self::assertCount(3, $itens);
        self::assertSame(80, mb_strlen($itens[0]['text']));
        self::assertSame('https://portal.escola.com.br', $itens[0]['url']);
        self::assertTrue($itens[0]['newTab']);
        self::assertSame('fa-solid fa-user-lock', $itens[1]['icon']);
        self::assertSame('mailto:a@b.c', $itens[1]['url']);
        self::assertFalse($itens[1]['newTab']);
        self::assertSame('', $itens[2]['text'], 'horário não tem texto próprio');

        self::assertCount(10, BarraSuperior::normalizarLado(array_fill(0, 15, 'phone')));
        self::assertSame([], BarraSuperior::normalizarLado('phone'));
    }

    public function testCor(): void
    {
        self::assertSame('#3d3e90', BarraSuperior::normalizarCor(' #3D3E90 '));
        self::assertSame('', BarraSuperior::normalizarCor('red'));
        self::assertSame('', BarraSuperior::normalizarCor('#fff'));
    }

    public function testDesligadaNaoMostraNada(): void
    {
        self::assertNull(BarraSuperior::resolverSite($this->site(['topBarEnabled' => false, 'topBarLeft' => ['phone']])));
    }

    public function testItensComOsDadosDoSite(): void
    {
        $barra = BarraSuperior::resolverSite($this->site([
            'topBarLeft'  => [['type' => 'address', 'text' => 'Araraquara'], 'phone', ['type' => 'whatsapp']],
            'topBarRight' => ['email', 'hours', 'social'],
        ]));

        [$end, $fone, $whats] = $barra['esquerda'];
        self::assertSame('Araraquara', $end['texto']);
        self::assertSame('https://www.google.com/maps/search/?api=1&query=Av.%20Brasil%2C%20782%20-%20Centro%2C%20Araraquara%20-%20SP', $end['url']);
        self::assertTrue($end['novaAba']);
        self::assertSame('tel:1633362414', $fone['url']);
        self::assertSame('(16) 3336-2414', $fone['texto']);
        self::assertSame('(16) 99649-4545', $whats['texto']);
        self::assertSame('https://wa.me/5516996494545', $whats['url']);

        [$email, $horas, $redes] = $barra['direita'];
        self::assertSame('mailto:contato@exemplo.com.br', $email['url']);
        self::assertSame('Seg a Sex 8h às 18h – Sáb 8h às 12h', $horas['texto']);
        self::assertSame(['whatsapp', 'instagram'], array_column($redes['redes'], 'rede'));
    }

    public function testDadoVazioOmiteOItem(): void
    {
        $t = $this->site(['topBarLeft' => ['email', 'phone', 'social']])->setContactEmail(null);
        $t->setWhatsappLink(null)->setInstagramLink(null);
        self::assertSame(['phone'], array_column(BarraSuperior::resolverSite($t)['esquerda'], 'tipo'));
    }

    public function testNumeroDoWhatsapp(): void
    {
        self::assertSame('(16) 99649-4545', BarraSuperior::numeroDoWhatsapp('https://wa.me/5516996494545'));
        self::assertSame('(16) 3332-7798', BarraSuperior::numeroDoWhatsapp('https://api.whatsapp.com/send?phone=551633327798&text=Ol%C3%A1!'));
        self::assertNull(BarraSuperior::numeroDoWhatsapp('https://wa.me/message/ABC'));
    }

    public function testCustomECor(): void
    {
        $barra = BarraSuperior::resolverSite($this->site([
            'topBarBg'    => '#3D3E90',
            'topBarRight' => [['type' => 'custom', 'icon' => 'fa-solid fa-user-lock', 'text' => 'Portal do Aluno', 'url' => 'https://x.com', 'newTab' => true]],
        ]));
        self::assertSame('#3d3e90', $barra['fundo']);
        self::assertSame(['Portal do Aluno', 'https://x.com', true, 'fa-solid fa-user-lock'], [
            $barra['direita'][0]['texto'], $barra['direita'][0]['url'], $barra['direita'][0]['novaAba'], $barra['direita'][0]['icone'],
        ]);
    }

    public function testJsonDoEditor(): void
    {
        [$e, $d] = BarraSuperior::lerJsonDoEditor('{"left":[{"type":"phone","text":"Ligue"}],"right":["social"]}');
        self::assertSame('Ligue', $e[0]['text']);
        self::assertSame('social', $d[0]['type']);
        self::assertSame([[], []], BarraSuperior::lerJsonDoEditor('xxx'));
    }
}
