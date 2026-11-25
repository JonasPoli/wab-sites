<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Page;
use App\Entity\Tenant;
use App\Repository\PageRepository;
use App\Service\BotaoFlutuante;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/** Resolução do botão flutuante do site (change botao-flutuante-do-site). */
final class BotaoFlutuanteTest extends TestCase
{
    private function comId(object $o, int $id): object
    {
        $r = new \ReflectionProperty($o, 'id');
        $r->setValue($o, $id);

        return $o;
    }

    private function tenant(?array $fb, ?string $whats = 'https://wa.me/5516999999999'): Tenant
    {
        /** @var Tenant $t */
        $t = $this->comId(new Tenant(), 5);

        return $t->setWhatsappLink($whats)->setFloatingButton($fb);
    }

    /** @param array<int, Page> $paginasDoTenant5 */
    private function botao(array $paginasDoTenant5 = []): BotaoFlutuante
    {
        $repo = $this->createStub(PageRepository::class);
        $repo->method('findOneBy')->willReturnCallback(
            static fn (array $c) => ($c['tenant']->getId() === 5 ? ($paginasDoTenant5[$c['id']] ?? null) : null),
        );
        $urls = $this->createStub(UrlGeneratorInterface::class);
        $urls->method('generate')->willReturnCallback(
            static fn (string $rota, array $p = []) => $rota === 'pub_home' ? '/' : '/p/' . $p['slug'],
        );

        return new BotaoFlutuante($repo, $urls);
    }

    private function pagina(int $id, string $slug): Page
    {
        /** @var Page $p */
        $p = $this->comId(new Page(), $id);

        return $p->setSlug($slug)->setTitle($slug);
    }

    public function testSemEscolhaGravadaValeWhatsapp(): void
    {
        self::assertSame(
            ['tipo' => 'whatsapp', 'url' => 'https://wa.me/5516999999999'],
            $this->botao()->doSite($this->tenant(null)),
        );
    }

    public function testWhatsappSemLinkNaoMostraBotao(): void
    {
        self::assertNull($this->botao()->doSite($this->tenant(['mode' => 'whatsapp'], '')));
    }

    public function testNenhumNaoMostraBotaoMesmoComWhatsapp(): void
    {
        self::assertNull($this->botao()->doSite($this->tenant(['mode' => 'none'])));
    }

    public function testTipoDesconhecidoViraWhatsapp(): void
    {
        self::assertSame('whatsapp', $this->botao()->doSite($this->tenant(['mode' => 'banner']))['tipo']);
    }

    public function testFormularioConfigurado(): void
    {
        $fb = ['mode' => 'form', 'pageId' => 7, 'label' => ' Solicite um orçamento ', 'icon' => 'fa-solid fa-envelope'];
        $botao = $this->botao([7 => $this->pagina(7, 'fale-conosco')])->doSite($this->tenant($fb));

        self::assertSame([
            'tipo' => 'form', 'url' => '/p/fale-conosco', 'texto' => 'Solicite um orçamento',
            'icone' => 'fa-solid fa-envelope', 'paginaId' => 7,
        ], $botao);
    }

    public function testFormularioSemTextoNaoMostraBotao(): void
    {
        $fb = ['mode' => 'form', 'pageId' => 7, 'label' => '  ', 'icon' => ''];
        self::assertNull($this->botao([7 => $this->pagina(7, 'contato')])->doSite($this->tenant($fb)));
    }

    public function testPaginaInexistenteOuDeOutroSiteNaoMostraBotao(): void
    {
        $fb = ['mode' => 'form', 'pageId' => 99, 'label' => 'Contato', 'icon' => ''];
        self::assertNull($this->botao([7 => $this->pagina(7, 'contato')])->doSite($this->tenant($fb)));
    }

    public function testPaginaHomeLevaParaAHome(): void
    {
        $home = $this->pagina(7, 'inicio');
        $t = $this->tenant(['mode' => 'form', 'pageId' => 7, 'label' => 'Contato', 'icon' => ''])->setHomePage($home);

        self::assertSame('/', $this->botao([7 => $home])->doSite($t)['url']);
    }

    public function testNormalizar(): void
    {
        $n = BotaoFlutuante::normalizar('xyz', null, str_repeat('á', 50), '<script>');
        self::assertSame('whatsapp', $n['mode']);
        self::assertSame(40, mb_strlen($n['label']));
        self::assertSame('', $n['icon']);
        self::assertSame('fa-solid fa-envelope', BotaoFlutuante::normalizar('form', 3, 'x', ' fa-solid  fa-envelope ')['icon']);
    }
}
