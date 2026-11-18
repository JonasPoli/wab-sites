<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Page;
use App\Entity\PageBlock;
use App\Entity\PageSection;
use App\Entity\Tenant;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/** Prévia do bloco no painel (previa-do-bloco-no-painel): renderiza pelo tema e não grava nada. */
final class PreviaDoBlocoTest extends WebTestCase
{
    private const SITE = 'previa-a.localhost';
    private const OUTRO = 'previa-b.localhost';

    /** @var int[] */
    private array $criados = [];

    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }

    protected function tearDown(): void
    {
        try {
            if (static::getClient() && $this->criados) {
                $em = static::getContainer()->get(EntityManagerInterface::class);
                foreach ($this->criados as $id) {
                    $t = $em->getRepository(Tenant::class)->find($id);
                    if (!$t) { continue; }
                    foreach ($em->getRepository(User::class)->findBy(['tenant' => $t]) as $u) { $em->remove($u); }
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

    public function testPreviaPeloTemaSemGravar(): void
    {
        $cliente = static::createClient(['environment' => 'test'], ['HTTP_HOST' => self::SITE]);
        $em = static::getContainer()->get(EntityManagerInterface::class);
        foreach ([self::SITE, self::OUTRO] as $d) {
            if ($em->getRepository(Tenant::class)->findOneBy(['domain' => $d])) {
                self::markTestSkipped('Domínio de teste já existe.');
            }
        }
        $secoes = [];
        foreach ([self::SITE, self::OUTRO] as $d) {
            $t = (new Tenant())->setDomain($d)->setName('Prévia ' . $d)->setTheme('atelie');
            $em->persist($t);
            $p = (new Page())->setTenant($t)->setTitle('Página')->setSlug('pagina');
            $em->persist($p);
            $s = (new PageSection())->setPage($p)->setPosition(0);
            $em->persist($s);
            $em->flush();
            $this->criados[] = $t->getId();
            $secoes[$d] = $s->getId();
        }
        $admin = (new User())->setUsername('previa.admin')->setName('Admin')->setEmail('previa.admin@exemplo.com')->setWorkGroup(0)
            ->setTenant($em->getRepository(Tenant::class)->findOneBy(['domain' => self::SITE]))->setPassword('x');
        $em->persist($admin);
        $em->flush();
        $cliente->loginUser($admin);
        $antes = $em->getRepository(PageBlock::class)->count([]);

        $cliente->request('POST', '/admin/block/previa?section=' . $secoes[self::SITE], ['type' => 'steps', 'title' => 'Como trabalhamos aqui', 'config' => ['steps' => [['title' => 'Escuta e briefing']]]]);
        self::assertResponseIsSuccessful();
        $html = (string) $cliente->getResponse()->getContent();
        self::assertStringContainsString('Como trabalhamos aqui', $html);
        self::assertStringContainsString('Escuta e briefing', $html);
        self::assertStringContainsString('at-process', $html, 'desenhado pelo parcial do tema atelie');
        self::assertStringNotContainsString('class="at-header"', $html, 'sem o cabeçalho do site');

        // Depoimentos sem nenhum item: um de exemplo com os nomes dos campos
        $cliente->request('POST', '/admin/block/previa?section=' . $secoes[self::SITE], ['type' => 'testimonials']);
        self::assertStringContainsString('Cargo / Função', (string) $cliente->getResponse()->getContent());

        // Seção de outro site
        $cliente->request('POST', '/admin/block/previa?section=' . $secoes[self::OUTRO], ['type' => 'cta', 'title' => 'x']);
        self::assertResponseStatusCodeSame(404);

        self::assertSame($antes, static::getContainer()->get(EntityManagerInterface::class)->getRepository(PageBlock::class)->count([]), 'a prévia não grava bloco');
    }
}
