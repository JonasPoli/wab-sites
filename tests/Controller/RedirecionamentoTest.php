<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Page;
use App\Entity\Redirect;
use App\Entity\Tenant;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/** Redirecionamentos de endereços antigos de ponta a ponta (site-marcenaria-pichoneri). */
final class RedirecionamentoTest extends WebTestCase
{
    private const SITE_A = 'redir-a.localhost';
    private const SITE_B = 'redir-b.localhost';

    /** @var int[] */
    private array $criados = [];

    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }

    protected function tearDown(): void
    {
        try {
            if (static::getClient() && $this->criados !== []) {
                $em = static::getContainer()->get(EntityManagerInterface::class);
                foreach ($this->criados as $id) {
                    $t = $em->getRepository(Tenant::class)->find($id);
                    if (!$t) { continue; }
                    foreach ($em->getRepository(Redirect::class)->findBy(['tenant' => $t]) as $r) { $em->remove($r); }
                    foreach ($em->getRepository(Page::class)->findBy(['tenant' => $t]) as $p) { $em->remove($p); }
                    $em->flush();
                    $em->remove($t);
                    $em->flush();
                }
            }
        } catch (\Throwable) {
        }
        $this->criados = [];
        parent::tearDown();
        static::ensureKernelShutdown();
    }

    public function testRedirecionamentos(): void
    {
        $cliente = static::createClient(['environment' => 'test'], ['HTTP_HOST' => self::SITE_A]);
        $em = static::getContainer()->get(EntityManagerInterface::class);
        foreach ([self::SITE_A, self::SITE_B] as $d) {
            if ($em->getRepository(Tenant::class)->findOneBy(['domain' => $d])) {
                self::markTestSkipped('Domínio de teste já existe.');
            }
        }
        $a = (new Tenant())->setDomain(self::SITE_A)->setName('A')->setTheme('cetec');
        $b = (new Tenant())->setDomain(self::SITE_B)->setName('B')->setTheme('cetec');
        $em->persist($a);
        $em->persist($b);
        $em->flush();
        $this->criados = [$a->getId(), $b->getId()];

        $em->persist((new Page())->setTenant($a)->setTitle('Sobre')->setSlug('sobre'));
        $em->persist((new Redirect())->setTenant($a)->setFromPath('/2023/07/13/espaco-acolhedor')->setToPath('/blog/espaco-acolhedor'));
        $em->persist((new Redirect())->setTenant($a)->setFromPath('/p/sobre')->setToPath('/'));
        $em->persist((new Redirect())->setTenant($a)->setFromPath('/home2')->setToPath('/'));
        $em->flush();

        // Barra final e consulta são ignoradas
        $cliente->request('GET', '/2023/07/13/espaco-acolhedor/?utm_source=x');
        self::assertResponseStatusCodeSame(301);
        self::assertResponseHeaderSame('Location', '/blog/espaco-acolhedor');

        // Sem cadastro: 404 de sempre
        $cliente->request('GET', '/nao-existe');
        self::assertResponseStatusCodeSame(404);

        // POST não é redirecionado
        $cliente->request('POST', '/home2');
        self::assertNotSame(301, $cliente->getResponse()->getStatusCode());

        // Página que existe tem prioridade
        $cliente->request('GET', '/p/sobre');
        self::assertResponseIsSuccessful();

        // Contagem de uso
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $r = $em->getRepository(Redirect::class)->findOneBy(['fromPath' => '/2023/07/13/espaco-acolhedor']);
        self::assertSame(1, $r->getHits());

        // Outro site não enxerga o redirecionamento
        $cliente->setServerParameter('HTTP_HOST', self::SITE_B);
        $cliente->request('GET', '/home2');
        self::assertResponseStatusCodeSame(404);
    }
}
