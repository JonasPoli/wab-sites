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

/** Banner "Só a imagem" (change banner-so-imagem): desenho nos cinco temas, Markdown, gravação pelo painel e prévia. */
final class BannerSoImagemTest extends WebTestCase
{
    private const TEMAS = ['cetec', 'moderno', 'wab', 'noticias', 'atelie'];

    /** Imagens de teste em public/uploads/page_block: nome => [largura, altura] */
    private const IMAGENS = ['teste-bsi-largo.png' => [1884, 835], 'teste-bsi-alto.png' => [1200, 900], 'teste-bsi-terceiro.png' => [1884, 835]];

    private const DESCRICAO = 'Arte da campanha de prevenção, com o laço azul';

    /** @var int[] */
    private array $criados = [];

    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }

    /** Em cada teste: apagar o site de teste no tearDown apaga do disco as imagens dos blocos dele. */
    protected function setUp(): void
    {
        parent::setUp();
        foreach (self::IMAGENS as $nome => [$w, $h]) {
            $im = imagecreatetruecolor($w, $h);
            imagefill($im, 0, 0, imagecolorallocate($im, 40, 120, 220));
            imagepng($im, self::pasta() . '/' . $nome);
        }
    }

    public static function tearDownAfterClass(): void
    {
        foreach (array_keys(self::IMAGENS) as $nome) {
            @unlink(self::pasta() . '/' . $nome);
        }
    }

    private static function pasta(): string
    {
        $pasta = dirname(__DIR__, 2) . '/public/uploads/page_block';
        if (!is_dir($pasta)) {
            mkdir($pasta, 0775, true);
        }

        return $pasta;
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

    /** Slide; $imageOnly null = sem a chave própria (bloco gravado antes da escolha por slide). */
    private static function slide(string $titulo, string $imagem, string $alt = '', bool $ativo = true, ?string $imageOnly = null): array
    {
        $slide = ['title' => $titulo, 'text' => 'Subtítulo de ' . $titulo, 'ctaText' => 'Conheça Nossos Produtos', 'ctaLink' => '/p/produtos',
                  'active' => $ativo ? '1' : '0', 'image' => $imagem, 'alt' => $alt];

        return $imageOnly === null ? $slide : $slide + ['imageOnly' => $imageOnly];
    }

    /** Carrossel misto (change banner-so-imagem-por-slide): slide 1 com texto, slide 2 "só a imagem", slide 3 com texto. */
    private static function misto(bool $primeiroSoImagem = false): array
    {
        return ['active' => '1', 'fullWidth' => '0', 'fullHeight' => '0', 'banners' => [
            ['title' => 'Linha cirúrgica', 'text' => 'Subtítulo com texto', 'ctaText' => 'Ver linha', 'ctaLink' => '/p/linha', 'active' => '1',
             'image' => 'teste-bsi-largo.png', 'alt' => '', 'imageOnly' => $primeiroSoImagem ? '1' : '0'],
            self::slide('Promoção', 'teste-bsi-alto.png', 'Arte da promoção de dezembro', true, '1'),
            ['title' => 'Assistência técnica', 'text' => 'Outro subtítulo', 'ctaText' => '', 'ctaLink' => '', 'active' => '1',
             'image' => 'teste-bsi-terceiro.png', 'alt' => '', 'imageOnly' => '0'],
        ]];
    }

    /** Banner de um slide ativo (com título, subtítulo e botão gravados) e um desativado, na largura inteira. */
    private static function bannerDeUmSlide(string $imageOnly = '1'): array
    {
        return ['imageOnly' => $imageOnly, 'active' => '1', 'fullWidth' => '1', 'fullHeight' => '0', 'banners' => [
            self::slide('Novembro Azul', 'teste-bsi-largo.png', self::DESCRICAO),
            self::slide('Slide desativado', 'teste-bsi-terceiro.png', 'Descrição do slide desativado', false),
        ]];
    }

    /** Carrossel de três slides ativos, o segundo mais alto e o terceiro sem descrição. */
    private static function carrossel(): array
    {
        return ['imageOnly' => '1', 'active' => '1', 'fullWidth' => '0', 'fullHeight' => '0', 'banners' => [
            self::slide('Carrossel um', 'teste-bsi-largo.png', 'Primeira arte do carrossel'),
            self::slide('Carrossel dois', 'teste-bsi-alto.png', 'Segunda arte, mais alta'),
            self::slide('Carrossel três', 'teste-bsi-terceiro.png'),
        ]];
    }

    /**
     * Site de teste com a página "vitrine": uma seção por banner, na ordem dada.
     *
     * @param array<int, array> $banners config de cada bloco
     * @return array{0: Tenant, 1: PageSection[], 2: PageBlock[]}
     */
    private function site(string $dominio, string $tema, array $banners): array
    {
        $em = $this->em();
        if ($antigo = $em->getRepository(Tenant::class)->findOneBy(['domain' => $dominio])) {
            $this->apagar($antigo); // sobra de uma execução interrompida
        }
        $t = (new Tenant())->setDomain($dominio)->setName('Banner ' . $tema)->setTheme($tema);
        $em->persist($t);
        $p = (new Page())->setTenant($t)->setTitle('Vitrine')->setSlug('vitrine');
        $em->persist($p);
        $secoes = $blocos = [];
        foreach ($banners as $i => $config) {
            $s = (new PageSection())->setPage($p)->setPosition($i)->setShowInMenu(false);
            $em->persist($s);
            $primeiro = $config['banners'][0] ?? [];
            // Como o painel grava: título, texto e imagem do 1º slide copiados para o bloco
            $b = (new PageBlock())->setSection($s)->setType('banner')->setTitle($primeiro['title'] ?? null)
                ->setText($primeiro['text'] ?? null)->setImage($primeiro['image'] ?? null)->setConfig($config);
            $em->persist($b);
            $secoes[] = $s;
            $blocos[] = $b;
        }
        $em->flush();
        $this->criados[] = $t->getId();
        $ids = [array_map(static fn ($s) => $s->getId(), $secoes), array_map(static fn ($b) => $b->getId(), $blocos)];
        $em->clear(); // a página recarrega seções e blocos do banco, como numa requisição de verdade

        return [
            $em->find(Tenant::class, $t->getId()),
            array_map(static fn ($id) => $em->find(PageSection::class, $id), $ids[0]),
            array_map(static fn ($id) => $em->find(PageBlock::class, $id), $ids[1]),
        ];
    }

    private function entrar(KernelBrowser $cliente, Tenant $t): void
    {
        $t = $this->em()->find(Tenant::class, $t->getId());
        $admin = (new User())->setUsername('banner.' . $t->getId())->setName('Admin')->setEmail('banner' . $t->getId() . '@exemplo.com')
            ->setWorkGroup(0)->setTenant($t)->setPassword('x');
        $this->em()->persist($admin);
        $this->em()->flush();
        $cliente->loginUser($admin);
    }

    /** O HTML de um bloco "só a imagem": do contêiner até o fim da seção dele. */
    private static function trecho(string $html, int $blocoId): string
    {
        $inicio = strpos($html, 'banner-so-imagem-' . $blocoId . '"');
        if ($inicio === false) {
            $inicio = strpos($html, 'banner-so-imagem-' . $blocoId . ' ');
        }
        self::assertNotFalse($inicio, "bloco {$blocoId} desenhado como só a imagem");
        $inicio = strrpos(substr($html, 0, $inicio), '<div');
        $fim = strpos($html, '</section>', $inicio);

        return substr($html, $inicio, ($fim === false ? strlen($html) : $fim) - $inicio);
    }

    public function testDesenhoNosCincoTemas(): void
    {
        $cliente = static::createClient(['environment' => 'test']);
        foreach (self::TEMAS as $tema) {
            $dominio = 'bsi-' . $tema . '.localhost';
            [, , [$um, $carrossel]] = $this->site($dominio, $tema, [self::bannerDeUmSlide(), self::carrossel()]);
            $cliente->request('GET', 'http://' . $dominio . '/p/vitrine');
            self::assertResponseIsSuccessful("tema {$tema}");
            $html = (string) $cliente->getResponse()->getContent();

            // Nenhum texto, botão ou efeito dos slides
            foreach (['Novembro Azul', 'Subtítulo de', 'Conheça Nossos Produtos', 'Ver Nossos Cursos', 'Carrossel um', 'hero-overlay', 'Slide desativado'] as $proibido) {
                self::assertStringNotContainsString($proibido, $html, "{$tema}: '{$proibido}' não aparece");
            }
            $a = self::trecho($html, $um->getId());
            $b = self::trecho($html, $carrossel->getId());
            foreach ([$a, $b] as $trecho) {
                self::assertStringNotContainsString('Fale Conosco', $trecho, "{$tema}: sem botão fixo do tema");
                self::assertStringNotContainsString('backdrop-filter', $trecho, "{$tema}: sem desfoque");
                self::assertStringNotContainsString('linear-gradient', $trecho, "{$tema}: sem degradê");
                self::assertStringNotContainsString(':hover .banner', $trecho, "{$tema}: sem zoom");
                self::assertStringNotContainsString('<h1', $trecho);
                self::assertStringNotContainsString('<h2', $trecho);
                self::assertStringNotContainsString('<a ', $trecho, "{$tema}: nenhum link ou botão");
                preg_match_all('~<img\b[^>]*>~', $trecho, $imgs);
                self::assertNotEmpty($imgs[0]);
                foreach ($imgs[0] as $img) {
                    self::assertMatchesRegularExpression('~\swidth="\d+"~', $img, "{$tema}: width em toda <img>");
                    self::assertMatchesRegularExpression('~\sheight="\d+"~', $img, "{$tema}: height em toda <img>");
                }
            }

            // Banner de um slide: imagem inteira na largura da tela, com a descrição como alt
            self::assertStringContainsString('aspect-ratio:1600 / 709;', $a, "{$tema}: área na proporção da imagem");
            self::assertStringContainsString('width:100vw;', $a);
            self::assertStringContainsString('alt="' . self::DESCRICAO . '"', $a);
            self::assertStringNotContainsString('aria-hidden', $a);
            self::assertStringNotContainsString('swiper', $a, "{$tema}: um slide ativo não vira carrossel");
            self::assertStringContainsString('fetchpriority="high"', $a);
            self::assertMatchesRegularExpression('~\ssrcset="([^"]+)"~', $a);
            preg_match('~\ssrcset="([^"]+)"~', $a, $srcset);
            self::assertStringContainsString('/banner_inteiro_mobile/', $srcset[1]);
            self::assertStringContainsString('/banner_inteiro/', $srcset[1]);
            self::assertMatchesRegularExpression('~imagesrcset="([^"]+)"~', $html, "{$tema}: preload no <head>");
            preg_match('~imagesrcset="([^"]+)"~', $html, $preload);
            self::assertSame($srcset[1], $preload[1], "{$tema}: preload pede o mesmo arquivo que a <img>");

            // Carrossel: três slides, setas e bolinhas, primeiro com prioridade e os outros adiados
            self::assertSame(3, substr_count($b, 'class="swiper-slide"'), "{$tema}: três slides");
            self::assertStringContainsString('swiper-pagination-' . $carrossel->getId(), $b);
            self::assertStringContainsString('swiper-button-next-' . $carrossel->getId(), $b);
            self::assertStringContainsString('swiper-button-prev-' . $carrossel->getId(), $b);
            self::assertStringContainsString('autoplay: { delay: 5000', $b);
            self::assertStringContainsString('aspect-ratio:1600 / 709;', $b, "{$tema}: área pela proporção do 1º slide");
            self::assertSame(1, substr_count($b, 'fetchpriority="high"'));
            self::assertSame(2, substr_count($b, 'data-adiada="'));
            self::assertStringContainsString('alt="Primeira arte do carrossel"', $b);
            self::assertStringContainsString('alt="" aria-hidden="true"', $b, "{$tema}: slide sem descrição é decorativo");

            // Versão Markdown: a descrição entra, o slide sem descrição não
            $cliente->request('GET', 'http://' . $dominio . '/p/vitrine.md');
            self::assertResponseIsSuccessful("{$tema}: .md");
            $md = (string) $cliente->getResponse()->getContent();
            self::assertStringContainsString(self::DESCRICAO, $md, "{$tema}: descrição da imagem no .md");
            self::assertStringNotContainsString('teste-bsi-terceiro', $md, "{$tema}: imagem sem descrição fora do .md");
            self::assertStringNotContainsString('Novembro Azul', $md);
        }
    }

    public function testBannerMistoNosCincoTemas(): void
    {
        $cliente = static::createClient(['environment' => 'test']);
        foreach (self::TEMAS as $tema) {
            $dominio = 'bsi-misto-' . $tema . '.localhost';
            [, , [$b]] = $this->site($dominio, $tema, [self::misto()]);
            $cliente->request('GET', 'http://' . $dominio . '/p/vitrine');
            self::assertResponseIsSuccessful("tema {$tema}");
            $html = (string) $cliente->getResponse()->getContent();

            self::assertStringNotContainsString('banner-so-imagem-', $html, "{$tema}: misto fica no desenho do banner com texto");
            self::assertStringContainsString('banner-swiper-' . $b->getId(), $html, "{$tema}: carrossel do tema");
            self::assertStringContainsString('Linha cirúrgica', $html, "{$tema}: slide com texto como antes");
            self::assertStringContainsString('Ver linha', $html);
            self::assertStringContainsString('Assistência técnica', $html);
            self::assertStringNotContainsString('Promoção', $html, "{$tema}: título guardado do slide só com a imagem não aparece");
            self::assertSame(3, substr_count($html, 'class="swiper-slide"'), "{$tema}: três slides");

            // O slide 2 é só a imagem: <img> com a descrição, sem banner-bg (o zoom do tema), adiada, sem camada nem conteúdo
            self::assertMatchesRegularExpression('~<img\b[^>]*data-adiada="[^"]*teste-bsi-alto[^>]*alt="Arte da promoção de dezembro"[^>]*class="banner-slide-so-imagem"~s', $html, "{$tema}: imagem do slide 2");
            $partes = explode('<div class="swiper-slide"', $html);
            $slide2 = current(array_filter($partes, static fn ($p) => str_contains($p, 'teste-bsi-alto')));
            self::assertNotFalse($slide2, "{$tema}: slide 2 desenhado");
            $slide2 = substr($slide2, 0, (int) strpos($slide2, '</style>'));
            foreach (['banner-bg', 'hero-overlay', 'linear-gradient', 'opacity:.15', '<h2', '<a ', 'banner-glass-card'] as $proibido) {
                self::assertStringNotContainsString($proibido, $slide2, "{$tema}: o slide 2 tem só a imagem ({$proibido})");
            }
            self::assertStringContainsString('swiper-slide{height:auto}', $slide2, "{$tema}: slides esticados até o mais alto");
        }
    }

    public function testTodosOsSlidesPorSlideSaemComAImagemInteira(): void
    {
        $cliente = static::createClient(['environment' => 'test']);
        $config = ['active' => '1', 'fullWidth' => '1', 'fullHeight' => '0', 'banners' => [
            self::slide('Um', 'teste-bsi-largo.png', 'Arte um', true, '1'),
            self::slide('Dois', 'teste-bsi-terceiro.png', 'Arte dois', true, '1'),
            self::slide('Desativado com texto', 'teste-bsi-alto.png', '', false, '0'),
        ]];
        [, , [$b]] = $this->site('bsi-por-slide.localhost', 'cetec', [$config]);
        $cliente->request('GET', 'http://bsi-por-slide.localhost/p/vitrine');
        $trecho = self::trecho((string) $cliente->getResponse()->getContent(), $b->getId());

        self::assertStringContainsString('aspect-ratio:1600 / 709;', $trecho, 'desenho comum, imagem inteira');
        self::assertStringContainsString('alt="Arte um"', $trecho);
        self::assertStringNotContainsString('Desativado', $trecho);
    }

    public function testPrimeiroSlideSoComAImagemNumMistoEhOLcp(): void
    {
        $cliente = static::createClient(['environment' => 'test']);
        $this->site('bsi-misto-lcp.localhost', 'cetec', [self::misto(true)]);
        $cliente->request('GET', 'http://bsi-misto-lcp.localhost/p/vitrine');
        $html = (string) $cliente->getResponse()->getContent();

        self::assertMatchesRegularExpression('~<img\b[^>]*teste-bsi-largo[^>]*fetchpriority="high"[^>]*class="banner-slide-so-imagem"~s', $html, 'primeiro slide só com a imagem, com prioridade');
        preg_match('~<img\b[^>]*\ssrcset="([^"]+)"[^>]*class="banner-slide-so-imagem"~s', $html, $img);
        preg_match('~imagesrcset="([^"]+)"~', $html, $preload);
        self::assertNotEmpty($img);
        self::assertSame($img[1], $preload[1] ?? null, 'preload pede o mesmo arquivo que a <img>');
        self::assertStringContainsString('/banner_desktop/', $img[1], 'banner misto usa as miniaturas do banner com texto');
    }

    public function testCemPorCentoDeAltura(): void
    {
        $cliente = static::createClient(['environment' => 'test']);
        $config = self::bannerDeUmSlide();
        $config['fullHeight'] = '1';
        [, , [$b]] = $this->site('bsi-altura.localhost', 'cetec', [$config]);
        $cliente->request('GET', 'http://bsi-altura.localhost/p/vitrine');
        $trecho = self::trecho((string) $cliente->getResponse()->getContent(), $b->getId());

        self::assertStringContainsString('height:100vh;', $trecho);
        self::assertStringNotContainsString('aspect-ratio', $trecho);
        self::assertStringContainsString('object-fit:cover', $trecho);
    }

    public function testGravacaoPeloPainel(): void
    {
        $cliente = static::createClient(['environment' => 'test']);
        [$t, , [$b]] = $this->site('bsi-painel.localhost', 'cetec', [self::bannerDeUmSlide('0')]);
        $this->entrar($cliente, $t);
        $url = 'http://bsi-painel.localhost/admin/block/' . $b->getId() . '/edit';
        $longa = '<b>Arte</b> ' . str_repeat('descrição longa ', 20); // passa de 250 caracteres
        $config = ['fullWidth' => '1', 'fullHeight' => '0', 'active' => '1'];

        // Liga a opção nos dois slides (por slide). Os campos de texto vão no POST (no formulário ficam
        // inertes, não desabilitados); o segundo slide é novo e sem título.
        $cliente->request('POST', $url, ['type' => 'banner', 'config' => $config, 'banners' => [
            ['image' => 'teste-bsi-largo.png', 'title' => 'Novembro Azul', 'text' => 'Subtítulo de Novembro Azul', 'ctaText' => 'Conheça Nossos Produtos', 'ctaLink' => '/p/produtos', 'active' => '1', 'alt' => $longa, 'imageOnly' => '1'],
            ['image' => 'teste-bsi-alto.png', 'title' => '', 'text' => '', 'ctaText' => '', 'ctaLink' => '', 'active' => '1', 'alt' => '  Segunda arte  ', 'imageOnly' => '1'],
        ]]);
        self::assertResponseRedirects();

        $this->em()->clear();
        $cfg = $this->em()->getRepository(PageBlock::class)->find($b->getId())->getConfig();
        self::assertArrayNotHasKey('imageOnly', $cfg, 'a escolha fica nos slides, não no bloco');
        self::assertSame(['1', '1'], array_column($cfg['banners'], 'imageOnly'));
        self::assertCount(2, $cfg['banners']);
        self::assertSame('', $cfg['banners'][1]['title'], 'slide sem título é aceito');
        self::assertSame('teste-bsi-alto.png', $cfg['banners'][1]['image']);
        self::assertSame('Segunda arte', $cfg['banners'][1]['alt']);
        self::assertSame(250, mb_strlen($cfg['banners'][0]['alt']));
        self::assertStringStartsWith('Arte descrição longa', $cfg['banners'][0]['alt'], 'descrição sem tag');
        self::assertSame('Novembro Azul', $cfg['banners'][0]['title'], 'texto guardado com a opção ligada');
        self::assertSame('Conheça Nossos Produtos', $cfg['banners'][0]['ctaText']);

        $cliente->request('GET', 'http://bsi-painel.localhost/p/vitrine');
        $html = (string) $cliente->getResponse()->getContent();
        self::assertStringContainsString('banner-so-imagem', $html);
        self::assertStringNotContainsString('Conheça Nossos Produtos', $html);

        // Só o segundo slide: banner misto, o primeiro volta a mostrar texto e botão
        $cliente->request('POST', $url, ['type' => 'banner', 'config' => $config, 'banners' => [
            ['image' => 'teste-bsi-largo.png', 'title' => 'Novembro Azul', 'text' => 'Subtítulo de Novembro Azul', 'ctaText' => 'Conheça Nossos Produtos', 'ctaLink' => '/p/produtos', 'active' => '1', 'alt' => 'Arte', 'imageOnly' => '0'],
            ['image' => 'teste-bsi-alto.png', 'title' => 'Promoção', 'text' => '', 'ctaText' => '', 'ctaLink' => '', 'active' => '1', 'alt' => 'Segunda arte', 'imageOnly' => '1'],
        ]]);
        self::assertResponseRedirects();
        $cliente->request('GET', 'http://bsi-painel.localhost/p/vitrine');
        $html = (string) $cliente->getResponse()->getContent();
        self::assertStringNotContainsString('banner-so-imagem-', $html);
        self::assertStringContainsString('Subtítulo de Novembro Azul', $html);
        self::assertStringContainsString('Conheça Nossos Produtos', $html);
        self::assertStringNotContainsString('Promoção', $html);
        self::assertStringContainsString('alt="Segunda arte"', $html);
    }

    public function testBlocoComAOpcaoAntigaPassaParaOsSlides(): void
    {
        $cliente = static::createClient(['environment' => 'test']);
        [$t, , [$b]] = $this->site('bsi-antigo.localhost', 'cetec', [self::bannerDeUmSlide('1')]);
        $this->entrar($cliente, $t);

        // O formulário abre com o interruptor de cada slide ligado
        $cliente->request('GET', 'http://bsi-antigo.localhost/admin/block/' . $b->getId() . '/edit');
        self::assertResponseIsSuccessful();
        $form = (string) $cliente->getResponse()->getContent();
        self::assertSame(2, preg_match_all('~<sl-switch[^>]*name="banners\[\d\]\[imageOnly\]"[^>]*checked~', $form), 'os dois slides aparecem ligados');
        self::assertStringNotContainsString('name="config[imageOnly]"', $form, 'sem o interruptor do bloco');

        // Aba aberta antes do deploy: manda a opção do bloco e nenhum campo por slide
        $cliente->request('POST', 'http://bsi-antigo.localhost/admin/block/' . $b->getId() . '/edit', ['type' => 'banner',
            'config' => ['imageOnly' => '1', 'fullWidth' => '1', 'fullHeight' => '0', 'active' => '1'], 'banners' => [
                ['image' => 'teste-bsi-largo.png', 'title' => 'Novembro Azul', 'text' => '', 'ctaText' => '', 'ctaLink' => '', 'active' => '1', 'alt' => 'Arte'],
            ]]);
        self::assertResponseRedirects();
        $this->em()->clear();
        $cfg = $this->em()->getRepository(PageBlock::class)->find($b->getId())->getConfig();
        self::assertArrayNotHasKey('imageOnly', $cfg);
        self::assertSame('1', $cfg['banners'][0]['imageOnly']);
    }

    public function testPreviaSegueAOpcao(): void
    {
        $cliente = static::createClient(['environment' => 'test']);
        [$t, , [$b]] = $this->site('bsi-previa.localhost', 'cetec', [self::bannerDeUmSlide('0')]);
        $this->entrar($cliente, $t);
        $antes = $this->em()->getRepository(PageBlock::class)->find($b->getId())->getConfig();
        $slide = ['image' => 'teste-bsi-largo.png', 'title' => 'Novembro Azul', 'text' => 'Subtítulo', 'ctaText' => 'Conheça Nossos Produtos', 'ctaLink' => '#', 'active' => '1', 'alt' => 'Arte da prévia'];
        $url = 'http://bsi-previa.localhost/admin/block/previa?block=' . $b->getId();

        $cliente->request('POST', $url, ['type' => 'banner', 'config' => ['active' => '1'], 'banners' => [$slide + ['imageOnly' => '1']]]);
        self::assertResponseIsSuccessful();
        $html = (string) $cliente->getResponse()->getContent();
        self::assertStringContainsString('banner-so-imagem', $html);
        self::assertStringContainsString('alt="Arte da prévia"', $html);
        self::assertStringNotContainsString('Conheça Nossos Produtos', $html);
        self::assertStringNotContainsString('hero-overlay', $html);

        $cliente->request('POST', $url, ['type' => 'banner', 'config' => ['active' => '1'], 'banners' => [$slide + ['imageOnly' => '0']]]);
        $html = (string) $cliente->getResponse()->getContent();
        self::assertStringNotContainsString('banner-so-imagem', $html);
        self::assertStringContainsString('Conheça Nossos Produtos', $html);

        // Misto na prévia: o segundo slide só com a imagem
        $cliente->request('POST', $url, ['type' => 'banner', 'config' => ['active' => '1'], 'banners' => [
            $slide + ['imageOnly' => '0'],
            ['image' => 'teste-bsi-alto.png', 'title' => 'Promoção', 'active' => '1', 'alt' => 'Arte dois', 'imageOnly' => '1'],
        ]]);
        $html = (string) $cliente->getResponse()->getContent();
        self::assertStringContainsString('Conheça Nossos Produtos', $html);
        self::assertStringNotContainsString('Promoção', $html);
        self::assertStringContainsString('class="banner-slide-so-imagem"', $html);

        $this->em()->clear();
        self::assertSame($antes, $this->em()->getRepository(PageBlock::class)->find($b->getId())->getConfig(), 'a prévia não grava');
    }

    public function testOpcaoDesligadaContinuaComoAntes(): void
    {
        $cliente = static::createClient(['environment' => 'test']);
        $this->site('bsi-desligada.localhost', 'cetec', [self::bannerDeUmSlide('0')]);
        $cliente->request('GET', 'http://bsi-desligada.localhost/p/vitrine');
        $html = (string) $cliente->getResponse()->getContent();

        self::assertStringNotContainsString('banner-so-imagem', $html);
        self::assertStringContainsString('Novembro Azul', $html);
        self::assertStringContainsString('Conheça Nossos Produtos', $html);
        self::assertStringContainsString('hero-overlay', $html);
        self::assertStringContainsString('/banner_desktop/', $html, 'banner com texto continua com os filtros que recortam');
    }
}
