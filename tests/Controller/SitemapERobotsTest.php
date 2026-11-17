<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Page;
use App\Entity\Tenant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * `/sitemap.xml` e `/robots.txt` de ponta a ponta (change seo-e-performance-dos-sites).
 *
 * Cobre os cenários da requirement "Sitemap" do delta de `construtor-de-paginas` e da
 * requirement "Regras de rastreamento por domínio" da spec `seo-das-paginas`.
 *
 * Como no teste do antispam, a classe do kernel é dita aqui (o projeto não define
 * KERNEL_CLASS no phpunit.xml.dist) e tudo o que o teste cria é apagado no fim.
 */
final class SitemapERobotsTest extends WebTestCase
{
    private const DOMINIO = 'sitemap-teste.localhost';

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
                    foreach ($em->getRepository(Page::class)->findBy(['tenant' => $tenant]) as $pagina) {
                        $em->remove($pagina);
                    }
                    $em->flush();
                    $em->remove($tenant);
                    $em->flush();
                }
                $this->tenantCriadoAqui = null;
            }
        } catch (\Throwable) {
            // Ambiente sem banco: não há o que limpar.
        }

        parent::tearDown();
        static::ensureKernelShutdown();
    }

    /**
     * Site com três páginas: uma serve de home, uma está publicada e uma tem publicação
     * marcada para o futuro.
     */
    private function montarSite(): \Symfony\Bundle\FrameworkBundle\KernelBrowser
    {
        $cliente = static::createClient(['environment' => 'test'], ['HTTP_HOST' => self::DOMINIO]);
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $existente = $em->getRepository(Tenant::class)->findOneBy(['domain' => self::DOMINIO]);
        if ($existente !== null) {
            self::markTestSkipped('Já existe um tenant com o domínio de teste.');
        }

        $tenant = (new Tenant())->setDomain(self::DOMINIO)->setName('Site de teste do sitemap');
        $tenant->setTheme('cetec');
        $em->persist($tenant);
        $em->flush();
        $this->tenantCriadoAqui = $tenant->getId();

        $home = $this->novaPagina($em, $tenant, 'Home', 'home-de-teste', new \DateTimeImmutable('-10 days'));
        $this->novaPagina($em, $tenant, 'Serviços', 'servicos-de-teste', new \DateTimeImmutable('-5 days'));
        $this->novaPagina($em, $tenant, 'Novidade', 'novidade-de-teste', new \DateTimeImmutable('+30 days'));

        $tenant->setHomePage($home);
        $em->flush();

        return $cliente;
    }

    private function novaPagina(
        EntityManagerInterface $em,
        Tenant $tenant,
        string $titulo,
        string $slug,
        \DateTimeImmutable $publicadaEm,
    ): Page {
        $pagina = (new Page())
            ->setTenant($tenant)
            ->setTitle($titulo)
            ->setSlug($slug)
            ->setPublishedAt($publicadaEm)
            ->setUpdatedAt($publicadaEm);
        $em->persist($pagina);
        $em->flush();

        return $pagina;
    }

    public function testSitemapTrazHomeEPaginaPublicada(): void
    {
        $cliente = $this->montarSite();
        $cliente->request('GET', '/sitemap.xml');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('X-Robots-Tag', 'noindex');

        $xml = (string) $cliente->getResponse()->getContent();

        self::assertStringContainsString('<loc>http://' . self::DOMINIO . '/</loc>', $xml);
        self::assertStringContainsString('/p/servicos-de-teste', $xml);
    }

    public function testPaginaQueServeDeHomeNaoApareceDuasVezes(): void
    {
        $cliente = $this->montarSite();
        $cliente->request('GET', '/sitemap.xml');

        self::assertStringNotContainsString('/p/home-de-teste', (string) $cliente->getResponse()->getContent());
    }

    public function testPaginaComPublicacaoNoFuturoFicaDeFora(): void
    {
        $cliente = $this->montarSite();
        $cliente->request('GET', '/sitemap.xml');

        self::assertStringNotContainsString('/p/novidade-de-teste', (string) $cliente->getResponse()->getContent());
    }

    public function testLastmodTrazDataEHoraComFuso(): void
    {
        $cliente = $this->montarSite();
        $cliente->request('GET', '/sitemap.xml');

        // ISO 8601 com fuso, e não só a data de hoje
        self::assertMatchesRegularExpression(
            '~<lastmod>\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}</lastmod>~',
            (string) $cliente->getResponse()->getContent(),
        );
    }

    public function testSiteSemTemaDeNoticiasNaoTrazUrlDeNoticia(): void
    {
        $cliente = $this->montarSite();
        $cliente->request('GET', '/sitemap.xml');

        $xml = (string) $cliente->getResponse()->getContent();
        self::assertStringNotContainsString('/noticias/', $xml);
        self::assertStringNotContainsString('/autor/', $xml);
    }

    public function testRobotsApontaParaOSitemapDoProprioDominio(): void
    {
        $cliente = $this->montarSite();
        $cliente->request('GET', '/robots.txt');

        self::assertResponseIsSuccessful();
        $corpo = (string) $cliente->getResponse()->getContent();

        self::assertStringContainsString('Sitemap: http://' . self::DOMINIO . '/sitemap.xml', $corpo);
        self::assertStringContainsString('Disallow: /admin', $corpo);
        self::assertStringContainsString('Disallow: /busca', $corpo);
    }

    public function testDominioQueNaoEhTenantProibeTudo(): void
    {
        $cliente = static::createClient(['environment' => 'test'], ['HTTP_HOST' => 'nao-e-tenant.localhost']);
        $cliente->request('GET', '/robots.txt');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString("User-agent: *\nDisallow: /", (string) $cliente->getResponse()->getContent());
    }
}
