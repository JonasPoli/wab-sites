<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Category;
use App\Entity\Page;
use App\Entity\PageBlock;
use App\Entity\PageSection;
use App\Entity\Tenant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Rotas públicas num site com o tema atelie (tema-atelie): página, categoria, home montada,
 * blog e as rotas que o tema não tem. Tudo o que o teste cria é apagado no fim.
 */
final class TemaAtelieTest extends WebTestCase
{
    private const DOMINIO = 'atelie-teste.localhost';

    private ?int $tenantCriadoAqui = null;

    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }

    protected function tearDown(): void
    {
        try {
            if (static::getClient() && $this->tenantCriadoAqui !== null) {
                $em = static::getContainer()->get(EntityManagerInterface::class);
                $tenant = $em->getRepository(Tenant::class)->find($this->tenantCriadoAqui);
                if ($tenant !== null) {
                    $tenant->setHomePage(null);
                    $em->flush();
                    foreach ($em->getRepository(Page::class)->findBy(['tenant' => $tenant]) as $p) {
                        $em->remove($p);
                    }
                    $em->flush();
                    foreach ($em->getRepository(Category::class)->findBy(['tenant' => $tenant]) as $c) {
                        $em->remove($c);
                    }
                    $em->flush();
                    $em->remove($tenant);
                    $em->flush();
                }
            }
        } catch (\Throwable) {
            // Ambiente sem banco: não há o que limpar.
        }
        parent::tearDown();
        static::ensureKernelShutdown();
    }

    public function testRotasDoTema(): void
    {
        $cliente = static::createClient(['environment' => 'test'], ['HTTP_HOST' => self::DOMINIO, 'HTTP_USER_AGENT' => 'teste-bot']);
        $em = static::getContainer()->get(EntityManagerInterface::class);
        if ($em->getRepository(Tenant::class)->findOneBy(['domain' => self::DOMINIO]) !== null) {
            self::markTestSkipped('Já existe um tenant com o domínio de teste.');
        }

        $tenant = (new Tenant())->setDomain(self::DOMINIO)->setName('Ateliê de teste')->setTheme('atelie')->setBlogEnabled(true);
        $em->persist($tenant);
        $em->flush();
        $this->tenantCriadoAqui = $tenant->getId();

        $cat = (new Category())->setTenant($tenant)->setName('Institucional')->setSlug('institucional');
        $em->persist($cat);
        $page = (new Page())->setTenant($tenant)->setTitle('Sobre')->setSlug('sobre')->setCategory($cat);
        $em->persist($page);
        $secao = (new PageSection())->setPage($page)->setPosition(0);
        $em->persist($secao);
        foreach (['hero_split' => ['title' => 'Madeira e design'], 'steps' => ['title' => 'Etapas', 'config' => ['steps' => [['title' => 'Escuta']]]], 'cta' => ['title' => 'Vamos conversar?'], 'contact' => ['title' => 'Contato']] as $tipo => $d) {
            $em->persist((new PageBlock())->setSection($secao)->setType($tipo)->setTitle($d['title'])->setConfig($d['config'] ?? null));
        }
        $em->flush();

        foreach (['/', '/p/sobre', '/categoria/institucional', '/blog'] as $url) {
            $cliente->request('GET', $url);
            self::assertResponseIsSuccessful($url);
            self::assertStringContainsString('at-root', (string) $cliente->getResponse()->getContent(), $url);
        }
        $html = (string) $cliente->request('GET', '/p/sobre')->html();
        self::assertStringContainsString('Escuta', $html);
        self::assertStringContainsString('name="wab_t"', $html);
        self::assertStringNotContainsString('fonts.googleapis.com', $html);

        foreach (['/videos', '/estudos', '/noticias'] as $url) {
            $cliente->request('GET', $url);
            self::assertResponseStatusCodeSame(404, $url);
        }

        // O cliente reinicia o kernel a cada pedido: grava pela conexão do container atual
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->getRepository(Tenant::class)->find($this->tenantCriadoAqui)->setHomePage($em->getRepository(Page::class)->find($page->getId()));
        $em->flush();
        $cliente->request('GET', '/');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Madeira e design', (string) $cliente->getResponse()->getContent());
    }
}
