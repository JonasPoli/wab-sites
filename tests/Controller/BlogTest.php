<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Enum\ArticleStatus;
use App\Entity\Tenant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Blog de site institucional de ponta a ponta (blog-nos-sites-institucionais): endereços,
 * rascunho e agendado, interruptor, tema noticias e sitemap. Tudo o que o teste cria é
 * apagado no fim, como nos outros testes funcionais.
 */
final class BlogTest extends WebTestCase
{
    private const DOMINIO = 'blog-teste.localhost';

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
                    foreach ($em->getRepository(Article::class)->findBy(['tenant' => $tenant]) as $a) {
                        $em->remove($a);
                    }
                    $em->flush();
                    foreach ($em->getRepository(Category::class)->findBy(['tenant' => $tenant]) as $c) {
                        $em->remove($c);
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

    private function montarSite(bool $blog, string $tema = 'cetec'): KernelBrowser
    {
        $cliente = static::createClient(['environment' => 'test'], ['HTTP_HOST' => self::DOMINIO, 'HTTP_USER_AGENT' => 'teste-bot']);
        $em = static::getContainer()->get(EntityManagerInterface::class);
        if ($em->getRepository(Tenant::class)->findOneBy(['domain' => self::DOMINIO]) !== null) {
            self::markTestSkipped('Já existe um tenant com o domínio de teste.');
        }

        $tenant = (new Tenant())->setDomain(self::DOMINIO)->setName('Blog de teste')->setTheme($tema)->setBlogEnabled($blog);
        $em->persist($tenant);
        $em->flush();
        $this->tenantCriadoAqui = $tenant->getId();

        $cat = (new Category())->setTenant($tenant)->setName('Tendências')->setSlug('tendencias');
        $em->persist($cat);
        $novo = static fn (string $slug, ArticleStatus $s, string $quando) => (new Article())->setTenant($tenant)->setTitle('Post ' . $slug)
            ->setSlug($slug)->setBody('<p>Texto</p>')->setCategory($cat)->setStatus($s)->setPublishedAt(new \DateTimeImmutable($quando));
        $em->persist($novo('publicado', ArticleStatus::Published, '-2 days'));
        $em->persist($novo('rascunho', ArticleStatus::Draft, '-2 days'));
        $em->persist($novo('agendado', ArticleStatus::Published, '+5 days'));
        $em->flush();

        return $cliente;
    }

    public function testBlogDesligado(): void
    {
        $cliente = $this->montarSite(false);
        $cliente->request('GET', '/blog');
        self::assertResponseStatusCodeSame(404);
        $cliente->request('GET', '/sitemap.xml');
        self::assertStringNotContainsString('localhost/blog', (string) $cliente->getResponse()->getContent());
    }

    public function testBlogLigado(): void
    {
        $cliente = $this->montarSite(true);

        $cliente->request('GET', '/blog');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Post publicado', (string) $cliente->getResponse()->getContent());
        self::assertStringNotContainsString('Post rascunho', (string) $cliente->getResponse()->getContent());

        $cliente->request('GET', '/blog/publicado');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('BlogPosting', (string) $cliente->getResponse()->getContent());

        foreach (['/blog/rascunho', '/blog/agendado', '/blog?pagina=2', '/noticias/publicado'] as $url) {
            $cliente->request('GET', $url);
            self::assertResponseStatusCodeSame(404, $url);
        }

        $cliente->request('GET', '/blog/categoria/tendencias');
        self::assertResponseIsSuccessful();

        $cliente->request('GET', '/sitemap.xml');
        $xml = (string) $cliente->getResponse()->getContent();
        self::assertStringContainsString('/blog/publicado</loc>', $xml);
        self::assertStringContainsString('/blog</loc>', $xml);
        self::assertStringContainsString('/blog/categoria/tendencias</loc>', $xml);
        self::assertStringNotContainsString('/blog/rascunho', $xml);
    }

    public function testPortalComInterruptorLigado(): void
    {
        $cliente = $this->montarSite(true, 'noticias');
        $cliente->request('GET', '/blog');
        self::assertResponseStatusCodeSame(404);
        $cliente->request('GET', '/noticias/publicado');
        self::assertResponseIsSuccessful();
    }
}
