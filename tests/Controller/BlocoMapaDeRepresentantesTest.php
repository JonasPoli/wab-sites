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

/** Bloco "Mapa de representantes" (change bloco-mapa-de-representantes): desenho, gravação e prévia. */
final class BlocoMapaDeRepresentantesTest extends WebTestCase
{
    private const TEMAS = ['cetec', 'moderno', 'wab', 'noticias', 'atelie'];

    /** @var string[] */
    private array $dominios = [];

    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }

    protected function tearDown(): void
    {
        try {
            foreach ($this->dominios as $d) {
                $this->apagar($d);
            }
        } catch (\Throwable) {
        }
        $this->dominios = [];
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
        if (!$t) { return; }
        foreach ($em->getRepository(User::class)->findBy(['tenant' => $t]) as $u) { $em->remove($u); }
        foreach ($em->getRepository(Page::class)->findBy(['tenant' => $t]) as $p) { $em->remove($p); }
        $em->flush();
        $em->remove($t);
        $em->flush();
        $em->clear();
    }

    /** @return array{0: int, 1: int, 2: int} tenant, seção e bloco */
    private function site(string $dominio, string $tema, ?array $config): array
    {
        $this->apagar($dominio);
        $this->dominios[] = $dominio;
        $em = $this->em();
        $t = (new Tenant())->setDomain($dominio)->setName('Mapa ' . $tema)->setTheme($tema);
        $em->persist($t);
        $p = (new Page())->setTenant($t)->setTitle('Revendas')->setSlug('revendas');
        $em->persist($p);
        $s = (new PageSection())->setPage($p)->setPosition(0);
        $em->persist($s);
        $b = (new PageBlock())->setSection($s)->setType('representatives')->setPreTitle('Onde encontrar')->setTitle('Mapa de representantes')
            ->setText('Clique no mapa e encontre o representante mais próximo da sua região.')->setConfig($config);
        $em->persist($b);
        $em->flush();
        $ids = [$t->getId(), $s->getId(), $b->getId()];
        $em->clear();

        return $ids;
    }

    private static function config(): array
    {
        return [
            'emptyMessage' => "Entre em contato com a EMAI\nE-mail: assistec@transmai.com.br",
            'reps' => [
                ['uf' => 'BA', 'image' => 'midia_teste.png', 'html' => '<h3>Hosana Equipamentos</h3><p>Salvador – BA<br>Fone: <a href="tel:+557133212336">(71) 3321-2336</a></p>'],
                ['uf' => 'AC', 'image' => null, 'html' => '<h3>J. G. Pacheco</h3><p>Rio Branco – AC</p>'],
                ['uf' => 'BA', 'image' => null, 'html' => '<h3>Speed Service</h3><p>Salvador – BA</p>'],
            ],
        ];
    }

    public function testDesenhoNosCincoTemas(): void
    {
        $cliente = static::createClient(['environment' => 'test']);
        foreach (self::TEMAS as $tema) {
            $dominio = 'mapa-' . $tema . '.localhost';
            [, , $bloco] = $this->site($dominio, $tema, self::config());
            $cliente->request('GET', 'http://' . $dominio . '/p/revendas');
            self::assertResponseIsSuccessful("tema {$tema}");
            $html = (string) $cliente->getResponse()->getContent();

            self::assertSame(27, substr_count($html, 'data-mapa-uf="'), "{$tema}: 27 estados no mapa");
            self::assertStringContainsString('href="#mapa-' . $bloco . '-BA"', $html, "{$tema}: link do estado com registro");
            self::assertStringContainsString('href="#mapa-' . $bloco . '-vazio" data-mapa-uf="AP"', $html, "{$tema}: estado sem registro leva à mensagem");
            self::assertStringContainsString('aria-label="Bahia: 2 representantes"', $html);
            self::assertSame(1, substr_count($html, 'data-mapa-painel="BA"'), "{$tema}: um cartão por estado");
            self::assertSame(1, substr_count($html, 'data-mapa-painel="AC"'));
            self::assertSame(3, preg_match_all('~<section[^>]*data-mapa-painel=~', $html), "{$tema}: BA, AC e vazio");
            self::assertStringContainsString('data-mapa-painel="vazio"', $html);
            self::assertStringContainsString('assistec@transmai.com.br</a>', $html, "{$tema}: mensagem com link");
            self::assertSame(0, preg_match('~data-mapa-painel="[^"]*"[^>]*\shidden~', $html), "{$tema}: nenhum cartão escondido no HTML");
            // Sem o arquivo no disco o filtro thumb devolve o original; com ele, a miniatura logo_bloco
            self::assertMatchesRegularExpression('~<img src="[^"]*(logo_bloco|page_block_media)[^"]*midia_teste[^"]*" width="160" height="80"~', $html, "{$tema}: logo");
            self::assertStringContainsString('href="tel:+557133212336"', $html);
            self::assertStringContainsString('Clique no mapa e encontre', $html);
            // Bahia: um registro com logo e um sem → um espaço vazio; Acre (sem logos) → nenhum
            self::assertSame(1, preg_match_all('~logo--vazio" aria-hidden="true"~', $html), "{$tema}: recuo só no registro sem logo da Bahia");
            // Na Bahia, a ordem do admin
            self::assertLessThan(strpos($html, 'Speed Service'), strpos($html, 'Hosana Equipamentos'));
        }
    }

    public function testGravacaoPeloPainel(): void
    {
        $cliente = static::createClient(['environment' => 'test']);
        [$tenant, , $bloco] = $this->site('mapa-painel.localhost', 'cetec', []);
        $admin = (new User())->setUsername('mapa.admin')->setName('Admin')->setEmail('mapa.admin@exemplo.com')->setWorkGroup(0)
            ->setTenant($this->em()->find(Tenant::class, $tenant))->setPassword('x');
        $this->em()->persist($admin);
        $this->em()->flush();
        $cliente->loginUser($admin);

        $ufs = array_keys(\App\Service\MapaDeRepresentantes::ESTADOS);
        $regs = [];
        for ($i = 0; $i < 120; $i++) {
            $regs[] = ['uf' => $ufs[$i % 27], 'image' => '', 'html' => '<h2>Empresa ' . $i . '</h2><p>Texto</p>'];
        }
        $regs[] = ['uf' => 'ZZ', 'image' => '', 'html' => '<p>sem estado</p>'];
        $cliente->request('POST', 'http://mapa-painel.localhost/admin/block/' . $bloco . '/edit', [
            'type' => 'representatives', 'title' => 'Mapa', 'text' => 'Chamada',
            'config' => ['emptyMessage' => 'Fale conosco', 'repsJson' => json_encode($regs)],
        ]);
        self::assertResponseRedirects();

        $this->em()->clear();
        $cfg = $this->em()->find(PageBlock::class, $bloco)->getConfig();
        self::assertCount(120, $cfg['reps']);
        self::assertSame('<h3>Empresa 0</h3><p>Texto</p>', $cfg['reps'][0]['html']);
        self::assertSame('Fale conosco', $cfg['emptyMessage']);
        self::assertArrayNotHasKey('repsJson', $cfg);

        $cliente->request('GET', 'http://mapa-painel.localhost/p/revendas');
        self::assertStringContainsString('Empresa 119', (string) $cliente->getResponse()->getContent());

        // Prévia sem registros: exemplos; seção de outro site: 404
        [, $secaoOutro] = $this->site('mapa-outro.localhost', 'cetec', []);
        $secao = $this->em()->find(PageBlock::class, $bloco)->getSection()->getId();
        $cliente->request('POST', 'http://mapa-painel.localhost/admin/block/previa?section=' . $secao, ['type' => 'representatives', 'text' => 'Chamada']);
        self::assertResponseIsSuccessful();
        $previa = (string) $cliente->getResponse()->getContent();
        self::assertStringContainsString('Representante de exemplo', $previa);
        self::assertSame(3, preg_match_all('~<section[^>]*data-mapa-painel=~', $previa), 'três estados de exemplo');
        $cliente->request('POST', 'http://mapa-painel.localhost/admin/block/previa?section=' . $secaoOutro, ['type' => 'representatives']);
        self::assertResponseStatusCodeSame(404);
    }
}
