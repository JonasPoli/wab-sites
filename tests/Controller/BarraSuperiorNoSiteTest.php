<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Page;
use App\Entity\Tenant;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Barra superior (change barra-superior-configuravel): desenho nos cinco temas e gravação
 * pelo /admin/settings e pelo /superadmin.
 */
final class BarraSuperiorNoSiteTest extends WebTestCase
{
    private const TEMAS = ['cetec', 'moderno', 'wab', 'noticias', 'atelie'];

    /** @var string[] */
    private array $criados = [];
    /** @var int[] usuários sem tenant criados para o teste */
    private array $semTenant = [];

    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }

    protected function tearDown(): void
    {
        try {
            foreach ($this->semTenant as $id) {
                $this->em()->getConnection()->delete('user', ['id' => $id]);
            }
            foreach ($this->criados as $d) {
                $this->apagar($d);
            }
        } catch (\Throwable) {
        }
        $this->criados = $this->semTenant = [];
        parent::tearDown();
        static::ensureKernelShutdown();
    }

    private function em(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    private function apagar(string $dominio): void
    {
        $em = $this->em();
        $em->clear();
        $t = $em->getRepository(Tenant::class)->findOneBy(['domain' => $dominio]);
        if (!$t) {
            return;
        }
        foreach ($em->getRepository(User::class)->findBy(['tenant' => $t]) as $u) { $em->remove($u); }
        $t->setHomePage(null);
        $em->flush();
        foreach ($em->getRepository(Page::class)->findBy(['tenant' => $t]) as $p) { $em->remove($p); }
        $em->flush();
        $em->remove($t);
        $em->flush();
        $em->clear();
    }

    private function site(string $dominio, string $tema, array $nav): int
    {
        $this->apagar($dominio);
        $em = $this->em();
        $t = (new Tenant())->setDomain($dominio)->setName('Barra ' . $tema)->setTheme($tema)
            ->setPhone('(16) 3336-2414')->setWhatsappLink('https://wa.me/5516996494545')
            ->setContactEmail('contato@exemplo.com.br')->setAddress('Av. Brasil, 782 - Centro, Araraquara - SP')
            ->setInstagramLink('https://instagram.com/exemplo')
            ->setNavigationSettings($nav);
        $em->persist($t);
        $em->persist((new Page())->setTenant($t)->setTitle('Sobre')->setSlug('sobre'));
        $em->flush();
        $this->criados[] = $dominio;
        $id = $t->getId();
        $em->clear();

        return $id;
    }

    private function html(KernelBrowser $c, string $url): string
    {
        $c->request('GET', $url);
        self::assertResponseIsSuccessful($url);

        return (string) $c->getResponse()->getContent();
    }

    /** O HTML da barra, ou '' sem barra. */
    private static function barra(string $html): string
    {
        $i = strpos($html, 'class="topbar-wrapper"');
        if ($i === false) {
            return '';
        }

        return substr($html, $i, 12000);
    }

    private const COMPLETA = [
        'topBarEnabled' => true,
        'topBarLeft'    => [['type' => 'address', 'text' => 'Araraquara'], ['type' => 'phone'], ['type' => 'whatsapp'], ['type' => 'social']],
        'topBarRight'   => [['type' => 'custom', 'icon' => 'fa-solid fa-envelope', 'text' => 'Portal do Aluno', 'url' => 'https://portal.exemplo.com.br', 'newTab' => true]],
    ];

    public function testBarraNosCincoTemas(): void
    {
        $c = static::createClient();
        foreach (self::TEMAS as $tema) {
            $this->site("bs-$tema.localhost", $tema, self::COMPLETA);
            $barra = self::barra($this->html($c, "http://bs-$tema.localhost/p/sobre"));
            self::assertNotSame('', $barra, "$tema: barra desenhada");

            $ordem = array_map(static fn (string $s) => strpos($barra, $s), [
                'https://www.google.com/maps/search/?api=1&amp;query=Av.%20Brasil',
                'href="tel:1633362414"',
                'href="https://wa.me/5516996494545" target="_blank"',
                'href="https://instagram.com/exemplo"',
                'href="https://portal.exemplo.com.br" target="_blank"',
            ]);
            self::assertNotContains(false, $ordem, "$tema: todos os itens");
            $ordenada = $ordem;
            sort($ordenada);
            self::assertSame($ordenada, $ordem, "$tema: itens na ordem gravada, esquerda antes da direita");
            self::assertStringContainsString('>Araraquara<', $barra);
            self::assertStringContainsString('(16) 99649-4545', $barra);
            self::assertMatchesRegularExpression('~href="https://portal\.exemplo\.com\.br"[^>]*>\s*<svg[^>]*class="text-xs"[^>]*>.*?</svg>.*?<span[^>]*>Portal do Aluno</span>~s', $barra, "$tema: ícone do personalizado desenhado");
            self::assertStringNotContainsString('&lt;svg', $barra, "$tema: SVG não escapado");
        }
    }

    public function testDesligadaCorEFormatoAntigo(): void
    {
        $c = static::createClient();
        $this->site('bs-off.localhost', 'cetec', ['topBarEnabled' => false] + self::COMPLETA);
        self::assertSame('', self::barra($this->html($c, 'http://bs-off.localhost/p/sobre')));

        $this->site('bs-cor.localhost', 'cetec', ['topBarBg' => '#3d3e90'] + self::COMPLETA);
        self::assertStringContainsString('style="--color-primary: #3d3e90; --topbar-text: #ffffff;', $this->html($c, 'http://bs-cor.localhost/p/sobre'));

        $this->site('bs-antigo.localhost', 'atelie', ['topBarEnabled' => true, 'topBarLeft' => ['email'], 'topBarRight' => ['phone']]);
        $barra = self::barra($this->html($c, 'http://bs-antigo.localhost/p/sobre'));
        self::assertLessThan(strpos($barra, 'tel:1633362414'), strpos($barra, 'mailto:contato@exemplo.com.br'));
    }

    private const JSON = '{"left":[{"type":"phone","text":"Ligue"},{"type":"banner"},{"type":"custom","text":"","icon":""}],'
        . '"right":[{"type":"custom","icon":"fa-solid fa-user-lock","text":"Portal","url":"portal.escola.com.br","newTab":true}]}';

    public function testGravacaoPeloAdminDoSite(): void
    {
        $c = static::createClient();
        $id = $this->site('bs-admin.localhost', 'cetec', ['customMenuItems' => []]);
        $em = $this->em();
        $admin = (new User())->setUsername('bs.admin')->setName('Admin')->setEmail('bs.admin@exemplo.com')->setWorkGroup(0)
            ->setTenant($em->find(Tenant::class, $id))->setPassword('x');
        $em->persist($admin);
        $em->flush();
        $c->loginUser($admin);

        $c->request('GET', 'http://bs-admin.localhost/admin/settings');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('#bs-editor input[name="topBarJson"]');

        $c->request('POST', 'http://bs-admin.localhost/admin/settings', [
            'theme' => 'cetec', 'topBarEnabled' => '1', 'topBarBg' => '#3D3E90', 'topBarJson' => self::JSON,
        ]);
        self::assertResponseRedirects();
        $em->clear();
        $nav = $em->find(Tenant::class, $id)->getNavigationSettings();
        self::assertTrue($nav['topBarEnabled']);
        self::assertSame('#3d3e90', $nav['topBarBg']);
        self::assertEquals([['type' => 'phone', 'text' => 'Ligue', 'icon' => '', 'url' => '', 'newTab' => false]], $nav['topBarLeft']);
        self::assertSame('https://portal.escola.com.br', $nav['topBarRight'][0]['url']);
        self::assertTrue($nav['topBarRight'][0]['newTab']);
    }

    public function testGravacaoPeloSuperadmin(): void
    {
        $c = static::createClient();
        $id = $this->site('bs-super.localhost', 'cetec', [
            'topBarEnabled' => false, 'customMenuItems' => [['label' => 'Blog', 'url' => '/blog', 'target' => '_self', 'icon' => '', 'active' => true]],
            'headerCtaLabel' => 'Orçamento', 'headerCtaUrl' => '#contato', 'headerTagline' => 'Frase',
        ]);
        $em = $this->em();
        $su = (new User())->setUsername('bs.superadmin')->setName('SuperAdmin de teste')->setEmail('bs.superadmin@exemplo.com')->setWorkGroup(0)->setPassword('x');
        $em->persist($su);
        $em->flush();
        $this->semTenant[] = $su->getId();
        $c->loginUser($su);

        $c->request('GET', "http://bs-super.localhost/superadmin/tenant/$id/edit");
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('#bs-editor input[name="topBarJson"]');

        $base = ['domain' => 'bs-super.localhost', 'name' => 'Barra', 'theme' => 'cetec'];
        $c->request('POST', "http://bs-super.localhost/superadmin/tenant/$id/edit", $base + ['topBarEnabled' => '1', 'topBarBg' => '', 'topBarJson' => self::JSON]);
        self::assertResponseRedirects();
        $em->clear();
        $nav = $em->find(Tenant::class, $id)->getNavigationSettings();
        self::assertTrue($nav['topBarEnabled']);
        self::assertSame('', $nav['topBarBg']);
        self::assertSame(['phone'], array_column($nav['topBarLeft'], 'type'));
        self::assertSame('Blog', $nav['customMenuItems'][0]['label'], 'itens de menu mantidos');
        self::assertSame(['Orçamento', '#contato', 'Frase'], [$nav['headerCtaLabel'], $nav['headerCtaUrl'], $nav['headerTagline']]);

        // Sem topBarJson (outro formulário): itens e cor continuam
        $c->request('POST', "http://bs-super.localhost/superadmin/tenant/$id/edit", $base + ['topBarEnabled' => '1']);
        $em->clear();
        $nav = $em->find(Tenant::class, $id)->getNavigationSettings();
        self::assertSame(['phone'], array_column($nav['topBarLeft'], 'type'));
        self::assertCount(1, $nav['topBarRight']);
    }
}
