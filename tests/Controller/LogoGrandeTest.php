<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Page;
use App\Entity\Tenant;
use App\Entity\User;
use App\Service\TenantExporter;
use App\Service\TenantImporter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/** Logo maior na abertura (change logo-maior-na-abertura): desenho nos cinco temas, /superadmin e exportação. */
final class LogoGrandeTest extends WebTestCase
{
    private const NORMAIS = ['cetec' => 56, 'moderno' => 40, 'wab' => 52, 'noticias' => 44, 'atelie' => 58];

    /** @var string[] */
    private array $criados = [];
    private ?int $superAdminId = null;
    private ?string $zip = null;

    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }

    protected function tearDown(): void
    {
        try {
            if ($this->superAdminId !== null) {
                $this->em()->getConnection()->delete('user', ['id' => $this->superAdminId]);
            }
            foreach ($this->criados as $d) {
                $this->apagar($d);
            }
        } catch (\Throwable) {
        }
        if ($this->arquivoLogo && is_file($this->arquivoLogo)) {
            @unlink($this->arquivoLogo);
        }
        $this->arquivoLogo = null;
        if ($this->zip && is_file($this->zip)) {
            @unlink($this->zip);
        }
        $this->criados = [];
        $this->superAdminId = null;
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

    private ?string $arquivoLogo = null;

    /** PNG de 1×1 em public/uploads/tenant/logo, apagado no tearDown. */
    private function logo(): string
    {
        if ($this->arquivoLogo === null) {
            $pasta = static::getContainer()->getParameter('kernel.project_dir') . '/public/uploads/tenant/logo';
            @mkdir($pasta, 0775, true);
            $this->arquivoLogo = $pasta . '/lg_teste_' . bin2hex(random_bytes(4)) . '.png';
            file_put_contents($this->arquivoLogo, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));
        }

        return basename($this->arquivoLogo);
    }

    private function site(string $dominio, string $tema, ?array $logo): int
    {
        $this->apagar($dominio);
        $em = $this->em();
        $t = (new Tenant())->setDomain($dominio)->setName('Logo ' . $tema)->setTheme($tema)->setHeaderLogo($logo)
            ->setLogo($this->logo());
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

    public function testDesenhoNosCincoTemas(): void
    {
        $c = static::createClient();
        foreach (self::NORMAIS as $tema => $normal) {
            $this->site("lg-on-$tema.localhost", $tema, ['large' => true, 'height' => 120]);
            $this->site("lg-off-$tema.localhost", $tema, null);

            $on = $this->html($c, "http://lg-on-$tema.localhost/p/sobre");
            self::assertStringContainsString(':root { --wab-logo-d: ' . (120 - $normal) . 'px; }', $on, "$tema: diferença de altura");
            self::assertStringContainsString("setProperty('--wab-logo-extra'", $on, "$tema: script da rolagem");
            self::assertStringContainsString('calc(' . $normal . 'px + var(--wab-logo-extra', $on, "$tema: logo do cabeçalho");

            $off = $this->html($c, "http://lg-off-$tema.localhost/p/sobre");
            self::assertStringNotContainsString('--wab-logo-d:', $off, "$tema: desligado não emite nada");
            self::assertStringNotContainsString('logo_site_grande', $off);
        }
    }

    public function testCelularEMiniaturaGrande(): void
    {
        $c = static::createClient();
        $this->site('lg-cetec.localhost', 'cetec', ['large' => true, 'height' => 120]);
        $html = $this->html($c, 'http://lg-cetec.localhost/p/sobre');
        self::assertStringContainsString('@media (max-width: 767px) { :root { --wab-logo-d: 36px; } }', $html, '(120 − 48) / 2');
        self::assertMatchesRegularExpression('~<img class="logo-light[^"]*"[^>]*logo_site_grande~', $html, 'logo do cabeçalho na versão grande');
        self::assertDoesNotMatchRegularExpression('~<footer.*logo_site_grande~s', $html, 'rodapé com a miniatura de sempre');

        // Desligado com altura gravada: nada muda, nem a miniatura
        $this->site('lg-desl.localhost', 'cetec', ['large' => false, 'height' => 150]);
        $desl = $this->html($c, 'http://lg-desl.localhost/p/sobre');
        self::assertStringNotContainsString('--wab-logo-d:', $desl);
        self::assertStringNotContainsString('logo_site_grande', $desl);
    }

    private function salvar(KernelBrowser $c, int $id, array $campos): Tenant
    {
        $c->request('POST', "http://lg-super.localhost/superadmin/tenant/$id/edit", $campos + ['domain' => 'lg-super.localhost', 'name' => 'Logo', 'theme' => 'cetec']);
        self::assertResponseRedirects();
        $this->em()->clear();

        return $this->em()->find(Tenant::class, $id);
    }

    public function testGravacaoPeloSuperadmin(): void
    {
        $c = static::createClient();
        $id = $this->site('lg-super.localhost', 'cetec', null);
        $em = $this->em();
        $su = (new User())->setUsername('lg.superadmin')->setName('SuperAdmin de teste')->setEmail('lg.superadmin@exemplo.com')->setWorkGroup(0)->setPassword('x');
        $em->persist($su);
        $em->flush();
        $this->superAdminId = $su->getId();
        $c->loginUser($su);

        $c->request('GET', "http://lg-super.localhost/superadmin/tenant/$id/edit");
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('input[name="headerLogoLarge"]:not([checked])');
        self::assertSelectorExists('input[name="headerLogoHeight"][value="110"]');

        $t = $this->salvar($c, $id, ['headerLogoForm' => '1', 'headerLogoLarge' => '1', 'headerLogoHeight' => '130']);
        self::assertSame(['large' => true, 'height' => 130], $t->getHeaderLogo());
        $c->request('GET', "http://lg-super.localhost/superadmin/tenant/$id/edit");
        self::assertSelectorExists('input[name="headerLogoLarge"][checked]');

        self::assertSame(200, $this->salvar($c, $id, ['headerLogoForm' => '1', 'headerLogoLarge' => '1', 'headerLogoHeight' => '500'])->getHeaderLogo()['height']);
        self::assertSame(['large' => true, 'height' => 200], $this->salvar($c, $id, [])->getHeaderLogo(), 'sem o grupo, mantém');
        self::assertFalse($this->salvar($c, $id, ['headerLogoForm' => '1', 'headerLogoHeight' => '90'])->getHeaderLogo()['large'], 'caixa desmarcada desliga');
    }

    public function testExportacaoEImportacao(): void
    {
        static::createClient();
        $id = $this->site('lg-exp.localhost', 'cetec', ['large' => true, 'height' => 130]);
        $this->apagar('lg-imp.localhost');
        $this->criados[] = 'lg-imp.localhost';

        $this->zip = static::getContainer()->get(TenantExporter::class)->export($this->em()->find(Tenant::class, $id));
        $importador = static::getContainer()->get(TenantImporter::class);
        $analise = $importador->analyze($this->zip);
        $novo = $importador->import($analise['metadata'], ['domain' => 'lg-imp.localhost', 'tenant_name' => 'Importado', 'users' => []], $this->zip);

        $this->em()->clear();
        self::assertSame(['large' => true, 'height' => 130], $this->em()->find(Tenant::class, $novo->getId())->getHeaderLogo());
    }
}
