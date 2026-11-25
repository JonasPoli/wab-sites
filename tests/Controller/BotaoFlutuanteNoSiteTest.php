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

/**
 * Botão flutuante do site (change botao-flutuante-do-site): desenho nos cinco temas,
 * gravação pelo /superadmin e ida e volta pela exportação.
 */
final class BotaoFlutuanteNoSiteTest extends WebTestCase
{
    private const TEMAS = ['cetec', 'moderno', 'wab', 'noticias', 'atelie'];
    private const WHATS = 'https://wa.me/5516999999999';

    /** @var string[] domínios criados */
    private array $criados = [];
    private ?string $zip = null;
    private ?int $superAdminId = null;

    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }

    protected function tearDown(): void
    {
        try {
            foreach ($this->criados as $d) {
                $this->apagar($d);
            }
        } catch (\Throwable) {
        }
        try {
            if ($this->superAdminId !== null) {
                $this->em()->getConnection()->delete('user', ['id' => $this->superAdminId]);
            }
        } catch (\Throwable) {
        }
        $this->superAdminId = null;
        if ($this->zip && is_file($this->zip)) {
            @unlink($this->zip);
        }
        $this->criados = [];
        parent::tearDown();
        static::ensureKernelShutdown();
    }

    private function em(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    /** Apaga um site de teste: usuários e páginas antes do tenant (nunca deixar usuário sem tenant). */
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

    /**
     * Site com as páginas "sobre" e "contato"; `$fb` com pageId 'contato' é trocado pelo id da página.
     *
     * @return array{0: int, 1: int} id do tenant e da página de contato
     */
    private function site(string $dominio, string $tema, ?array $fb, ?string $whats = self::WHATS): array
    {
        $this->apagar($dominio); // sobra de uma execução interrompida
        $em = $this->em();
        $t = (new Tenant())->setDomain($dominio)->setName('Botão ' . $tema)->setTheme($tema)->setWhatsappLink($whats);
        $em->persist($t);
        $sobre = (new Page())->setTenant($t)->setTitle('Sobre')->setSlug('sobre');
        $contato = (new Page())->setTenant($t)->setTitle('Contato')->setSlug('contato');
        $em->persist($sobre);
        $em->persist($contato);
        $em->flush();
        if ($fb !== null && ($fb['pageId'] ?? null) === 'contato') {
            $fb['pageId'] = $contato->getId();
        }
        $t->setFloatingButton($fb);
        $em->flush();
        $this->criados[] = $dominio;
        $ids = [$t->getId(), $contato->getId()];
        $em->clear();

        return $ids;
    }

    private function html(KernelBrowser $c, string $url): string
    {
        $c->request('GET', $url);
        self::assertResponseIsSuccessful($url);

        return (string) $c->getResponse()->getContent();
    }

    private static function temWhatsapp(string $html): bool
    {
        return (bool) preg_match('~<a href="' . preg_quote(self::WHATS, '~') . '"[^>]*class="(whatsapp-float|at-float-whatsapp)"|class="at-float-whatsapp"|class="whatsapp-float"~', $html);
    }

    public function testWhatsappNosCincoTemas(): void
    {
        $c = static::createClient();
        foreach (self::TEMAS as $tema) {
            $this->site("bf-whats-$tema.localhost", $tema, ['mode' => 'whatsapp']);
            $this->site("bf-semlink-$tema.localhost", $tema, null, null);
            $this->site("bf-nenhum-$tema.localhost", $tema, ['mode' => 'none']);

            $html = $this->html($c, "http://bf-whats-$tema.localhost/p/sobre");
            self::assertTrue(self::temWhatsapp($html), "$tema: botão do WhatsApp");
            self::assertStringContainsString('href="' . self::WHATS . '"', $html);
            self::assertStringNotContainsString('wab-flutuante-form', $html);

            self::assertFalse(self::temWhatsapp($this->html($c, "http://bf-semlink-$tema.localhost/p/sobre")), "$tema: sem link, sem botão");
            $nenhum = $this->html($c, "http://bf-nenhum-$tema.localhost/p/sobre");
            self::assertFalse(self::temWhatsapp($nenhum), "$tema: Nenhum");
            self::assertStringNotContainsString('wab-flutuante-form', $nenhum);
        }
    }

    public function testFormularioNosCincoTemas(): void
    {
        $c = static::createClient();
        foreach (self::TEMAS as $tema) {
            $this->site("bf-form-$tema.localhost", $tema, ['mode' => 'form', 'pageId' => 'contato', 'label' => 'Solicite um orçamento', 'icon' => 'fa-solid fa-envelope']);

            $html = $this->html($c, "http://bf-form-$tema.localhost/p/sobre");
            self::assertMatchesRegularExpression('~<a href="/p/contato" class="wab-flutuante-form"><svg[^>]*>.*?</svg>\s*<span>Solicite um orçamento</span>~s', $html, "$tema: botão de formulário com ícone");
            self::assertFalse(self::temWhatsapp($html), "$tema: formulário no lugar do WhatsApp");

            self::assertStringNotContainsString('class="wab-flutuante-form"', $this->html($c, "http://bf-form-$tema.localhost/p/contato"), "$tema: oculto na página de destino");
        }
    }

    public function testCorDoTextoSegueACorPrimaria(): void
    {
        $c = static::createClient();
        [$id] = $this->site('bf-cor.localhost', 'cetec', ['mode' => 'form', 'pageId' => 'contato', 'label' => 'Contato', 'icon' => '']);
        $em = $this->em();
        $em->find(Tenant::class, $id)->setPrimaryColor('#ffcc00')->setPrimaryColorDark('#1e3a8a');
        $em->flush();
        $em->clear();

        $html = $this->html($c, 'http://bf-cor.localhost/p/sobre');
        self::assertStringContainsString('a.wab-flutuante-form:focus{color:#111827', $html, 'texto escuro sobre amarelo');
        self::assertStringContainsString('.dark a.wab-flutuante-form{color:#fff}', $html, 'texto branco sobre a escura');
        self::assertStringContainsString('background:var(--color-primary,#ffcc00)', $html);
    }

    public function testFormularioSemTextoOuComPaginaExcluida(): void
    {
        $c = static::createClient();
        $this->site('bf-semtexto.localhost', 'cetec', ['mode' => 'form', 'pageId' => 'contato', 'label' => ' ', 'icon' => '']);
        self::assertStringNotContainsString('class="wab-flutuante-form"', $this->html($c, 'http://bf-semtexto.localhost/p/sobre'));

        [, $contato] = $this->site('bf-excluida.localhost', 'cetec', ['mode' => 'form', 'pageId' => 'contato', 'label' => 'Contato', 'icon' => '']);
        // Pela conexão: o filtro de tenant do último domínio aberto esconderia a página
        $this->em()->getConnection()->delete('page', ['id' => $contato]);
        $this->em()->clear();
        $html = $this->html($c, 'http://bf-excluida.localhost/p/sobre');
        self::assertStringNotContainsString('class="wab-flutuante-form"', $html);
        self::assertFalse(self::temWhatsapp($html));
    }

    /** SuperAdmin temporário (sem tenant, workGroup 0), apagado no tearDown. */
    private function superAdmin(): User
    {
        $em = $this->em();
        if ($antigo = $em->getRepository(User::class)->findOneBy(['username' => 'bf.superadmin'])) {
            $em->remove($antigo);
            $em->flush();
        }
        $u = (new User())->setUsername('bf.superadmin')->setName('SuperAdmin de teste')
            ->setEmail('bf.superadmin@exemplo.com')->setWorkGroup(0)->setPassword('x');
        $em->persist($u);
        $em->flush();
        $this->superAdminId = $u->getId();

        return $u;
    }

    /** POST da edição do tenant, com os campos que o formulário sempre manda. */
    private function salvar(KernelBrowser $c, string $dominio, int $id, array $campos): Tenant
    {
        $c->request('POST', "http://$dominio/superadmin/tenant/$id/edit", $campos + [
            'domain' => $dominio, 'name' => 'Botão superadmin', 'theme' => 'cetec', 'whatsappLink' => self::WHATS,
        ]);
        self::assertResponseRedirects();
        $this->em()->clear();

        return $this->em()->find(Tenant::class, $id);
    }

    public function testGravacaoPeloSuperadmin(): void
    {
        $c = static::createClient();
        [$outro, $paginaDoOutro] = $this->site('bf-outro.localhost', 'cetec', null);
        [$id, $contato] = $this->site('bf-super.localhost', 'cetec', null);
        $c->loginUser($this->superAdmin());

        $c->request('GET', "http://bf-super.localhost/superadmin/tenant/$id/edit");
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('input[name="floatingButtonMode"][value="whatsapp"][checked]');
        self::assertSelectorExists('#icon-modal-overlay');

        $t = $this->salvar($c, 'bf-super.localhost', $id, [
            'floatingButtonMode' => 'form', 'floatingButtonPageId' => (string) $contato,
            'floatingButtonLabel' => '  Fale com a gente  ', 'floatingButtonIcon' => 'fa-solid fa-envelope',
        ]);
        self::assertSame(['mode' => 'form', 'pageId' => $contato, 'label' => 'Fale com a gente', 'icon' => 'fa-solid fa-envelope'], $t->getFloatingButton());

        $c->request('GET', "http://bf-super.localhost/superadmin/tenant/$id/edit");
        self::assertSelectorExists('input[name="floatingButtonMode"][value="form"][checked]');
        self::assertSelectorExists('#fb-pagina option[value="' . $contato . '"][selected]');
        self::assertSelectorExists('#fb-texto[value="Fale com a gente"]');

        // Página de outro tenant: fica sem destino, e o outro tenant não muda
        $t = $this->salvar($c, 'bf-super.localhost', $id, [
            'floatingButtonMode' => 'form', 'floatingButtonPageId' => (string) $paginaDoOutro, 'floatingButtonLabel' => 'X', 'floatingButtonIcon' => '',
        ]);
        self::assertNull($t->getFloatingButton()['pageId']);
        self::assertSame('whatsapp', $this->em()->find(Tenant::class, $outro)->getFloatingButton()['mode']);

        // Tipo desconhecido vira WhatsApp; envio sem o tipo mantém a escolha
        self::assertSame('whatsapp', $this->salvar($c, 'bf-super.localhost', $id, ['floatingButtonMode' => 'banner'])->getFloatingButton()['mode']);
        $this->salvar($c, 'bf-super.localhost', $id, ['floatingButtonMode' => 'none']);
        self::assertSame('none', $this->salvar($c, 'bf-super.localhost', $id, [])->getFloatingButton()['mode']);
    }

    public function testExportacaoEImportacao(): void
    {
        static::createClient();
        [$id, $contato] = $this->site('bf-exp.localhost', 'cetec', ['mode' => 'form', 'pageId' => 'contato', 'label' => 'Contato', 'icon' => 'fa-solid fa-envelope']);
        $this->apagar('bf-imp.localhost');
        $this->criados[] = 'bf-imp.localhost';

        $this->zip = static::getContainer()->get(TenantExporter::class)->export($this->em()->find(Tenant::class, $id));
        $importador = static::getContainer()->get(TenantImporter::class);
        $analise = $importador->analyze($this->zip);
        $novo = $importador->import($analise['metadata'], ['domain' => 'bf-imp.localhost', 'tenant_name' => 'Importado', 'users' => []], $this->zip);

        $this->em()->clear();
        $novo = $this->em()->find(Tenant::class, $novo->getId());
        $fb = $novo->getFloatingButton();
        self::assertSame(['form', 'Contato', 'fa-solid fa-envelope'], [$fb['mode'], $fb['label'], $fb['icon']]);
        self::assertNotSame($contato, $fb['pageId']);
        $pagina = $this->em()->find(Page::class, $fb['pageId']);
        self::assertSame('contato', $pagina->getSlug());
        self::assertSame($novo->getId(), $pagina->getTenant()->getId());
    }
}
