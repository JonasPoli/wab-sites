<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Page;
use App\Entity\PageBlock;
use App\Entity\PageSection;
use App\Entity\Tenant;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/** Bloco Sanfona (change bloco-sanfona): gravação pelo painel, prévia e desenho nos cinco temas. */
final class BlocoSanfonaTest extends WebTestCase
{
    private const TEMAS = ['cetec', 'moderno', 'wab', 'noticias', 'atelie'];

    /** @var int[] */
    private array $criados = [];

    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }

    protected function tearDown(): void
    {
        try {
            $em = $this->em();
            $em->clear();
            foreach ($this->criados as $id) {
                if ($t = $em->getRepository(Tenant::class)->find($id)) {
                    $this->apagar($t);
                }
            }
        } catch (\Throwable) {
        }
        $this->criados = [];
        parent::tearDown();
        static::ensureKernelShutdown();
    }

    /** Apaga um site de teste: usuários e páginas antes do tenant (nunca deixar usuário sem tenant). */
    private function apagar(Tenant $t): void
    {
        $em = $this->em();
        foreach ($em->getRepository(User::class)->findBy(['tenant' => $t]) as $u) { $em->remove($u); }
        foreach ($em->getRepository(Page::class)->findBy(['tenant' => $t]) as $p) { $em->remove($p); }
        $em->flush();
        $em->remove($t);
        $em->flush();
        $em->clear();
    }

    private function em(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    /** @return array{0: Tenant, 1: PageSection, 2: PageBlock} */
    private function site(string $dominio, string $tema, array $config): array
    {
        $em = $this->em();
        if ($antigo = $em->getRepository(Tenant::class)->findOneBy(['domain' => $dominio])) {
            $this->apagar($antigo); // sobra de uma execução interrompida
        }
        $t = (new Tenant())->setDomain($dominio)->setName('Sanfona ' . $tema)->setTheme($tema);
        $em->persist($t);
        $p = (new Page())->setTenant($t)->setTitle('Revendas')->setSlug('revendas');
        $em->persist($p);
        $s = (new PageSection())->setPage($p)->setPosition(0);
        $em->persist($s);
        $b = (new PageBlock())->setSection($s)->setType('accordion')->setTitle('Distribuidores no Brasil')->setConfig($config);
        $em->persist($b);
        $em->flush();
        $this->criados[] = $t->getId();
        $em->clear(); // a página recarrega seções e blocos do banco, como numa requisição de verdade

        return [$em->find(Tenant::class, $t->getId()), $em->find(PageSection::class, $s->getId()), $em->find(PageBlock::class, $b->getId())];
    }

    private function entrar(KernelBrowser $cliente, Tenant $t): void
    {
        $t = $this->em()->find(Tenant::class, $t->getId());
        $admin = (new User())->setUsername('sanfona.' . $t->getId())->setName('Admin')->setEmail('sanfona' . $t->getId() . '@exemplo.com')
            ->setWorkGroup(0)->setTenant($t)->setPassword('x');
        $this->em()->persist($admin);
        $this->em()->flush();
        $cliente->loginUser($admin);
    }

    private static function configDeExemplo(): array
    {
        return [
            'firstOpen' => '1',
            'panels' => [
                ['title' => 'Acre', 'kind' => 'table', 'html' => null, 'table' => ['header' => true, 'rows' => [
                    ['Cidade', 'Logo', 'Empresa', 'Assistência Técnica'],
                    ['RIO BRANCO', ['text' => '', 'image' => 'midia_teste.png'], "J. G. PACHECO & CIA LTDA\nE-mail: comercial@jgp.com.br", 'X'],
                ]]],
                ['title' => '', 'kind' => 'text', 'html' => '<p>Sem título</p>', 'table' => ['header' => true, 'rows' => []]],
                ['title' => 'Amapá', 'kind' => 'text', 'html' => '<p>Entre em contato com a <strong>EMAI</strong>.</p>', 'table' => ['header' => true, 'rows' => []]],
            ],
        ];
    }

    public function testDesenhoNosCincoTemas(): void
    {
        $cliente = static::createClient(['environment' => 'test']);
        foreach (self::TEMAS as $tema) {
            $dominio = 'sanfona-' . $tema . '.localhost';
            $this->site($dominio, $tema, self::configDeExemplo());
            $cliente->request('GET', 'http://' . $dominio . '/p/revendas');
            self::assertResponseIsSuccessful("tema {$tema}");
            $html = (string) $cliente->getResponse()->getContent();

            self::assertSame(2, substr_count($html, '<details'), "{$tema}: um <details> por item com título");
            self::assertStringNotContainsString('Sem título', $html, "{$tema}: item sem título não aparece");
            preg_match_all('~<details[^>]*name="(sanfona-\d+)"~', $html, $nomes);
            self::assertCount(2, $nomes[1], "{$tema}: um de cada vez");
            self::assertCount(1, array_unique($nomes[1]), "{$tema}: mesmo name nos itens");
            self::assertSame(1, preg_match_all('~<details[^>]*\sopen[\s>]~', $html), "{$tema}: só o primeiro aberto");
            self::assertStringContainsString('data-rotulo="Empresa"', $html, "{$tema}: rótulo da coluna para o celular");
            self::assertStringContainsString('href="mailto:comercial@jgp.com.br"', $html, "{$tema}: e-mail da célula vira link");
            self::assertStringContainsString('J. G. PACHECO &amp; CIA LTDA<br>', $html, "{$tema}: quebra de linha da célula");
            self::assertStringContainsString('<strong>EMAI</strong>', $html, "{$tema}: texto formatado");
            self::assertMatchesRegularExpression('~<img class="[^"]*" src="[^"]*midia_teste[^"]*" width="160" height="80"[^>]*alt="Logo"~', $html, "{$tema}: logo na célula, alt = cabeçalho da coluna");
            self::assertStringNotContainsString('data-rotulo="Logo" class="is-vazia"', $html, "{$tema}: célula só com imagem não é vazia");
            self::assertStringContainsString('Distribuidores no Brasil', $html);
        }
    }

    public function testVariosAbertos(): void
    {
        $cliente = static::createClient(['environment' => 'test']);
        $config = self::configDeExemplo();
        $config['multiple'] = '1';
        unset($config['firstOpen']);
        $this->site('sanfona-varios.localhost', 'cetec', $config);
        $cliente->request('GET', 'http://sanfona-varios.localhost/p/revendas');
        $html = (string) $cliente->getResponse()->getContent();
        self::assertStringNotContainsString('name="sanfona-', $html);
        self::assertSame(0, preg_match_all('~<details[^>]*\sopen[\s>]~', $html));
    }

    public function testGravacaoPeloPainel(): void
    {
        $cliente = static::createClient(['environment' => 'test']);
        [$t, , $b] = $this->site('sanfona-painel.localhost', 'cetec', []);
        $this->entrar($cliente, $t);

        // 27 itens, 400 linhas de 7 colunas no total (mais que o max_input_vars se fosse um campo por célula)
        $itens = [];
        for ($i = 0; $i < 27; $i++) {
            $linhas = [['Cidade', 'Logo', 'Empresa', 'Resp. Técnico', 'Resp. Empresa', 'Assistência', 'Distribuidor']];
            $n = $i < 22 ? 15 : 14;
            for ($r = 0; $r < $n; $r++) {
                $linhas[] = ["Cidade {$i}-{$r}", '', "Empresa {$i}-{$r}", 'Fulano', 'Beltrano', 'X', 'X'];
            }
            $itens[] = ['title' => 'Estado ' . $i, 'kind' => 'table', 'html' => '', 'table' => json_encode(['header' => true, 'rows' => $linhas])];
        }
        $itens[0]['kind'] = 'text';
        $itens[0]['html'] = '<p onclick="x()">Oi</p><script>alert(1)</script>';

        $cliente->request('POST', 'http://sanfona-painel.localhost/admin/block/' . $b->getId() . '/edit', [
            'type' => 'accordion', 'title' => 'Revendas', 'config' => ['firstOpen' => '1', 'panels' => $itens],
        ]);
        self::assertResponseRedirects();

        $this->em()->clear();
        $cfg = $this->em()->getRepository(PageBlock::class)->find($b->getId())->getConfig();
        self::assertCount(27, $cfg['panels']);
        $total = array_sum(array_map(static fn ($p) => count($p['table']['rows']) - 1, $cfg['panels']));
        self::assertSame(400, $total, 'todas as linhas de dados gravadas');
        self::assertCount(7, $cfg['panels'][5]['table']['rows'][3]);
        self::assertSame('<p>Oi</p>', $cfg['panels'][0]['html']);
        self::assertSame('text', $cfg['panels'][0]['kind']);
        self::assertSame('1', $cfg['firstOpen']);
        self::assertArrayNotHasKey('multiple', $cfg);

        $cliente->request('GET', 'http://sanfona-painel.localhost/p/revendas');
        $html = (string) $cliente->getResponse()->getContent();
        self::assertStringContainsString('Empresa 26-13', $html);
        self::assertStringNotContainsString('alert(1)', $html);
    }

    public function testPreviaComExemplosEIsolada(): void
    {
        $cliente = static::createClient(['environment' => 'test']);
        [$t, $s] = $this->site('sanfona-previa.localhost', 'cetec', []);
        [, $sOutro] = $this->site('sanfona-outro.localhost', 'cetec', []);
        $this->entrar($cliente, $t);
        $antes = $this->em()->getRepository(PageBlock::class)->count([]);

        $cliente->request('POST', 'http://sanfona-previa.localhost/admin/block/previa?section=' . $s->getId(), ['type' => 'accordion']);
        self::assertResponseIsSuccessful();
        $html = (string) $cliente->getResponse()->getContent();
        self::assertStringContainsString('Item de texto', $html);
        self::assertStringContainsString('Item de tabela', $html);
        self::assertStringContainsString('<table', $html);

        $cliente->request('POST', 'http://sanfona-previa.localhost/admin/block/previa?section=' . $sOutro->getId(), ['type' => 'accordion']);
        self::assertResponseStatusCodeSame(404);

        self::assertSame($antes, $this->em()->getRepository(PageBlock::class)->count([]), 'a prévia não grava bloco');
    }
}
