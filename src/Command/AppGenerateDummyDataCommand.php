<?php

namespace App\Command;

use App\Entity\Tenant;
use App\Entity\Category;
use App\Entity\Page;
use App\Entity\PageSection;
use App\Entity\PageBlock;
use App\Entity\PageBlockImage;
use App\Entity\PageBlockTestimonial;
use App\Entity\PageBlockPartnerLogo;
use App\Entity\HeroBanner;
use App\Entity\Enum\BlockType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'app:generate-dummy-data',
    description: 'Gera um site completo e criativo com imagens reais via Picsum para o Tenant 127.0.0.1.',
)]
class AppGenerateDummyDataCommand extends Command
{
    private array $nomes = ['Alice Cooper', 'Bruno Marra', 'Carolina Sales', 'Diego Ribas', 'Elisa Mendes', 'Fabio Junior', 'Gabriela Duarte', 'Hugo Boss', 'Isabela Swan', 'João Doria'];
    private array $roles = ['CEO', 'Diretor de Marketing', 'CFO', 'Product Manager', 'Tech Lead', 'Designer UI/UX', 'Desenvolvedor Senior', 'Head de Vendas', 'Consultor Técnico', 'Especialista SEO'];

    public function __construct(
        private EntityManagerInterface $em,
        private ParameterBagInterface $params
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('clean', null, InputOption::VALUE_NONE, 'Limpar registros do tenant 127.0.0.1 antes de gerar');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('WAB Sites — Invocando Gerador de Conteúdo Criativo');

        // 1. Obter ou Criar Tenant
        $tenant = $this->em->getRepository(Tenant::class)->findOneBy(['domain' => '127.0.0.1']);
        if (!$tenant) {
            $tenant = new Tenant();
            $tenant->setDomain('127.0.0.1');
            $this->em->persist($tenant);
        }

        // Configurar branding e design rico
        $tenant->setName('WAB Digital');
        $tenant->setPrimaryColor('#0f172a'); // Azul Slate Escuro (Premium)
        $tenant->setSecondaryColor('#3b82f6'); // Azul Royal Vivo
        $tenant->setContactEmail('contato@wabdigital.com');
        $tenant->setTheme('moderno');
        $tenant->setRequiredApprovals(1);
        $tenant->setSeoTitle('WAB Digital — Hub de Tecnologia e Soluções Web');
        $tenant->setSeoDescription('Criamos ecossistemas digitais de alta performance, portais multi-tenant, design arrojado e soluções de ponta para sua empresa.');
        $tenant->setSeoKeywords('tecnologia, design, symfony, portais, web dev, agência, premium');
        
        // WhatsApp e Redes Sociais
        $tenant->setWhatsappLink('https://wa.me/5516999999999');
        $tenant->setInstagramLink('https://instagram.com/wabdigital');
        $tenant->setFacebookLink('https://facebook.com/wabdigital');
        $tenant->setLinkedinLink('https://linkedin.com/company/wabdigital');
        
        $tenant->setAboutText('A WAB Digital é líder em engenharia de software e design de interfaces arrojadas. Nosso propósito é impulsionar negócios através de soluções web de alta escalabilidade.');
        $tenant->setAboutFullText("Fundada com o ideal de revolucionar o desenvolvimento de plataformas corporativas, a WAB Digital combina design sofisticado (glassmorphism, dark/light native transitions) com infraestrutura robusta em Symfony.\n\nContamos com um time internacional de engenheiros apaixonados por código limpo, otimização de banco de dados e UX impactante.");

        // Baixar imagens de branding do Tenant
        $io->text('Baixando imagens de branding do Tenant...');
        $tenant->setLogo($this->downloadImage('tenant_logo', 'uploads/tenant/logo', 300, 100));
        $tenant->setFavicon($this->downloadImage('tenant_favicon', 'uploads/tenant/favicon', 32, 32));
        $tenant->setAboutImage($this->downloadImage('tenant_about', 'uploads/tenant/about', 800, 600));

        $this->em->persist($tenant);
        $this->em->flush();

        // 2. Limpar dados anteriores se solicitado
        if ($input->getOption('clean')) {
            $io->info('Limpando páginas, seções, blocos e categorias anteriores do tenant...');
            
            // Buscar tudo desse tenant
            $pages = $this->em->getRepository(Page::class)->findBy(['tenant' => $tenant]);
            foreach ($pages as $p) {
                $this->em->remove($p);
            }
            $categories = $this->em->getRepository(Category::class)->findBy(['tenant' => $tenant]);
            foreach ($categories as $c) {
                $this->em->remove($c);
            }
            $banners = $this->em->getRepository(HeroBanner::class)->findBy(['tenant' => $tenant]);
            foreach ($banners as $b) {
                $this->em->remove($b);
            }
            $this->em->flush();
        }

        // 3. Criar Categorias Criativas
        $io->text('Gerando Categorias...');
        $categories = [];
        $catData = [
            ['Tecnologia', 'fa-solid fa-laptop-code', 'Soluções inovadoras em nuvem, microsserviços e desenvolvimento sob demanda.'],
            ['Design & UX', 'fa-solid fa-bezier-curve', 'Interfaces fluidas, transições sutis, design system escalável e pesquisas de usabilidade.'],
            ['Estratégia', 'fa-solid fa-chess', 'Consultoria estratégica para otimizar conversões, SEO e presença de marca.'],
            ['Inovação', 'fa-solid fa-lightbulb', 'Pesquisa e desenvolvimento em inteligência artificial e ecossistemas modularizados.']
        ];

        foreach ($catData as $idx => $cItem) {
            $cat = new Category();
            $cat->setTenant($tenant);
            $cat->setName($cItem[0]);
            $cat->setSlug(strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $cItem[0])));
            $cat->setIcon($cItem[1]);
            $cat->setDescription($cItem[2]);
            $cat->setShowInHeader(true);
            $cat->setShowInFooter(true);
            $this->em->persist($cat);
            $categories[] = $cat;
        }
        $this->em->flush();

        // 4. Criar Hero Banners
        $io->text('Gerando Banners de Destaque...');
        $banner = new HeroBanner();
        $banner->setTenant($tenant);
        $banner->setTitle('Aceleramos sua jornada rumo ao futuro digital');
        $banner->setSubtitle('Engenharia de software elegante, design state-of-the-art e plataformas modularizadas com máxima performance.');
        $banner->setCtaText('Entrar em Contato');
        $banner->setCtaLink('#contato');
        $banner->setActive(true);
        $banner->setPosition(1);
        $banner->setBackgroundImage($this->downloadImage('hero_', 'uploads/hero', 1920, 1080));
        $banner->setUpdatedAt(new \DateTimeImmutable());
        $this->em->persist($banner);
        $this->em->flush();

        // 5. Criar a HOME PAGE do Tenant
        $io->text('Criando a Home Page...');
        $homePage = new Page();
        $homePage->setTenant($tenant);
        $homePage->setTitle('Home WAB Digital');
        $homePage->setSlug('home');
        $homePage->setShowInHeader(false);
        $homePage->setShowInFooter(false);
        $homePage->setSeoTitle('WAB Digital — Portais Corporativos e Design Exclusivo');
        $homePage->setSeoDescription('O melhor em desenvolvimento web Symfony, APIs de alto volume e interfaces responsivas com suporte nativo a dark mode.');
        $homePage->setCoverImage($this->downloadImage('page_cover_', 'uploads/page_cover', 800, 600));
        $homePage->setUpdatedAt(new \DateTimeImmutable());
        $this->em->persist($homePage);
        $this->em->flush();

        // Associar como homePage do Tenant
        $tenant->setHomePage($homePage);
        $this->em->persist($tenant);
        $this->em->flush();

        // 6. ADICIONAR SEÇÕES E BLOCOS Ricos na Home Page
        $io->text('Adicionando Seções e Blocos ricos na Home Page...');

        // ── SEÇÃO 1: Quem Somos (Bg Cor Sólida) ──
        $sec1 = new PageSection();
        $sec1->setPage($homePage);
        $sec1->setTitlePart1('Conheça');
        $sec1->setTitlePart2('A WAB Digital');
        $sec1->setPosition(1);
        $sec1->setActive(true);
        $sec1->setBgType('color');
        $sec1->setBgColor('#0f172a');
        $this->em->persist($sec1);

        $blk1 = new PageBlock();
        $blk1->setSection($sec1);
        $blk1->setType('text_image');
        $blk1->setPreTitle('QUEM SOMOS');
        $blk1->setTitle('Excelência em Desenvolvimento');
        $blk1->setText("<p>Unimos a robustez do ecossistema corporativo Symfony à flexibilidade de componentes interativos e micro-animações refinadas. Nosso foco é entregar plataformas que surpreendem no visual e excedem na performance.</p><p>Explore nossas categorias abaixo para entender nossa especialidade e como podemos alavancar sua infraestrutura.</p>");
        $blk1->setImage($this->downloadImage('block_img_', 'uploads/page_block', 800, 600));
        $blk1->setConfig(['align' => 'right']);
        $blk1->setPosition(1);
        $blk1->setUpdatedAt(new \DateTimeImmutable());
        $this->em->persist($blk1);

        // ── SEÇÃO 2: Grid de Especialidades / Blurba4 (Bg Gradiente) ──
        $sec2 = new PageSection();
        $sec2->setPage($homePage);
        $sec2->setTitlePart1('Nossos');
        $sec2->setTitlePart2('Diferenciais');
        $sec2->setPosition(2);
        $sec2->setActive(true);
        $sec2->setBgType('gradient');
        $sec2->setBgGradient('135deg, #0f172a 0%, #1e293b 100%');
        $this->em->persist($sec2);

        $blk2 = new PageBlock();
        $blk2->setSection($sec2);
        $blk2->setType('blurbs4');
        $blk2->setPreTitle('RECURSOS EXCLUSIVOS');
        $blk2->setTitle('Por que escolher a WAB?');
        $blk2->setConfig([
            'items' => [
                [
                    'icon' => 'fa-laptop-code',
                    'title' => 'Código de Elite',
                    'text' => 'Padrões rígidos de arquitetura limpa, testes e documentação automatizada.'
                ],
                [
                    'icon' => 'fa-bolt',
                    'title' => 'Ultra Rápido',
                    'text' => 'Otimização avançada de cache HTTP, banco de dados e build front-end premium.'
                ],
                [
                    'icon' => 'fa-shield-halved',
                    'title' => 'Segurança Ativa',
                    'text' => 'Criptografia de ponta, conformidade com LGPD/GDPR e auditorias periódicas.'
                ],
                [
                    'icon' => 'fa-moon',
                    'title' => 'Dark & Light Nativos',
                    'text' => 'Transições de tema suaves e amigáveis para todos os dispositivos.'
                ]
            ]
        ]);
        $blk2->setPosition(1);
        $blk2->setUpdatedAt(new \DateTimeImmutable());
        $this->em->persist($blk2);

        // ── SEÇÃO 3: Galeria de Projetos / Portfólio (Bg Imagem com Opacidade) ──
        $sec3 = new PageSection();
        $sec3->setPage($homePage);
        $sec3->setTitlePart1('Nosso');
        $sec3->setTitlePart2('Portfólio');
        $sec3->setPosition(3);
        $sec3->setActive(true);
        $sec3->setBgType('image');
        $sec3->setBgImage($this->downloadImage('bg_sec_', 'uploads/section/bg', 1920, 1080));
        $sec3->setBgImageOpacity(15);
        $sec3->setBgImagePosition('center');
        $this->em->persist($sec3);

        $blk3 = new PageBlock();
        $blk3->setSection($sec3);
        $blk3->setType('gallery');
        $blk3->setPreTitle('GALERIA DE SUCESSO');
        $blk3->setTitle('Últimos Projetos Entregues');
        $blk3->setUpdatedAt(new \DateTimeImmutable());
        $this->em->persist($blk3);

        // Adicionar imagens reais à galeria
        for ($j = 1; $j <= 4; $j++) {
            $gImg = new PageBlockImage();
            $gImg->setBlock($blk3);
            $gImg->setFilename($this->downloadImage('gallery_item_', 'uploads/page_block_gallery', 800, 600));
            $gImg->setCaption('Portal de Alta Performance WAB #' . $j);
            $gImg->setPosition($j);
            $gImg->setUpdatedAt(new \DateTimeImmutable());
            $this->em->persist($gImg);
        }

        // ── SEÇÃO 4: Depoimentos / Testimonials (Bg Cor Escura) ──
        $sec4 = new PageSection();
        $sec4->setPage($homePage);
        $sec4->setTitlePart1('Histórias');
        $sec4->setTitlePart2('De Sucesso');
        $sec4->setPosition(4);
        $sec4->setActive(true);
        $sec4->setBgType('color');
        $sec4->setBgColor('#090d16');
        $this->em->persist($sec4);

        $blk4 = new PageBlock();
        $blk4->setSection($sec4);
        $blk4->setType('testimonials');
        $blk4->setPreTitle('DEPOIMENTOS');
        $blk4->setTitle('O que nossos clientes dizem');
        $blk4->setUpdatedAt(new \DateTimeImmutable());
        $this->em->persist($blk4);

        // Adicionar depoimentos com fotos reais
        $testTexts = [
            'A WAB Digital superou todas as expectativas. O sistema modular nos poupou centenas de horas de desenvolvimento!',
            'Visual impecável, transição de cores perfeita, painel administrativo super dinâmico e suporte excelente.',
            'O suporte multi-tenant integrado em Symfony nos ajudou a implantar mais de 40 portais regionais em tempo recorde.'
        ];
        foreach ($testTexts as $idx => $txt) {
            $test = new PageBlockTestimonial();
            $test->setBlock($blk4);
            $test->setName($this->nomes[$idx]);
            $test->setRole($this->roles[$idx]);
            $test->setText($txt);
            $test->setRating(rand(4, 5));
            $test->setAvatar($this->downloadImage('avatar_', 'uploads/testimonial_avatar', 150, 150));
            $this->em->persist($test);
        }

        // ── SEÇÃO 5: Logos de Parceiros (Bg Sólido Claro/Alternativo) ──
        $sec5 = new PageSection();
        $sec5->setPage($homePage);
        $sec5->setTitlePart1('Grandes');
        $sec5->setTitlePart2('Parceiros');
        $sec5->setPosition(5);
        $sec5->setActive(true);
        $sec5->setBgType('color');
        $sec5->setBgColor('#0f172a');
        $this->em->persist($sec5);

        $blk5 = new PageBlock();
        $blk5->setSection($sec5);
        $blk5->setType('partner_logos');
        $blk5->setPreTitle('CONFIANÇA');
        $blk5->setTitle('Marcas que evoluem conosco');
        $blk5->setConfig(['style' => 'carousel', 'columns' => 5]);
        $blk5->setUpdatedAt(new \DateTimeImmutable());
        $this->em->persist($blk5);

        // Adicionar parceiros fictícios
        $brands = ['Citrus Corp', 'Vortex Systems', 'NextGen Soft', 'Aura Labs', 'Apex Global'];
        foreach ($brands as $idx => $bName) {
            $partner = new PageBlockPartnerLogo();
            $partner->setBlock($blk5);
            $partner->setName($bName);
            $partner->setUrl('https://example.com');
            $partner->setLogoFilename($this->downloadImage('brand_', 'uploads/partner_logo', 300, 150));
            $partner->setPosition($idx + 1);
            $partner->setUpdatedAt(new \DateTimeImmutable());
            $this->em->persist($partner);
        }

        // ── SEÇÃO 6: Captura de Contatos & Estatísticas (Bg Vídeo Opcional / Gradiente) ──
        $sec6 = new PageSection();
        $sec6->setPage($homePage);
        $sec6->setTitlePart1('Métricas');
        $sec6->setTitlePart2('Incríveis');
        $sec6->setPosition(6);
        $sec6->setActive(true);
        $sec6->setBgType('gradient');
        $sec6->setBgGradient('180deg, #1e293b 0%, #0f172a 100%');
        $this->em->persist($sec6);

        // Bloco de Estatísticas
        $blkStats = new PageBlock();
        $blkStats->setSection($sec6);
        $blkStats->setType('stats');
        $blkStats->setPreTitle('RESULTADOS');
        $blkStats->setTitle('WAB em números');
        $blkStats->setConfig([
            'items' => [
                ['number' => '99%', 'label' => 'SLA de Uptime'],
                ['number' => '150+', 'label' => 'Portais Ativos'],
                ['number' => '40M+', 'label' => 'Requisições Diárias'],
                ['number' => '15+', 'label' => 'Países Atendidos']
            ]
        ]);
        $blkStats->setPosition(1);
        $blkStats->setUpdatedAt(new \DateTimeImmutable());
        $this->em->persist($blkStats);

        // Bloco de Newsletter
        $blkNews = new PageBlock();
        $blkNews->setSection($sec6);
        $blkNews->setType('newsletter');
        $blkNews->setPreTitle('NOVIDADES');
        $blkNews->setTitle('Fique atualizado com nosso Hub');
        $blkNews->setText('Inscreva seu e-mail para receber análises exclusivas de arquitetura modular e boas práticas de UX.');
        $blkNews->setPosition(2);
        $blkNews->setUpdatedAt(new \DateTimeImmutable());
        $this->em->persist($blkNews);

        $this->em->flush();

        // 7. Criar Página Pública de Contatos
        $io->text('Criando Página Pública Extra (/p/servicos)...');
        $servicosPage = new Page();
        $servicosPage->setTenant($tenant);
        $servicosPage->setTitle('Serviços Avançados');
        $servicosPage->setSlug('servicos');
        $servicosPage->setShowInHeader(true);
        $servicosPage->setShowInFooter(true);
        $servicosPage->setSeoTitle('Nossos Serviços — WAB Digital');
        $servicosPage->setSeoDescription('Consulte nossa grade completa de serviços: DevOps, Cloud Serverless, Desenvolvimento Symfony e Design Responsivo.');
        $servicosPage->setCoverImage($this->downloadImage('serv_cover_', 'uploads/page_cover', 800, 600));
        $servicosPage->setUpdatedAt(new \DateTimeImmutable());
        $this->em->persist($servicosPage);
        $this->em->flush();

        // Seção Serviços
        $secServ = new PageSection();
        $secServ->setPage($servicosPage);
        $secServ->setTitlePart1('Catálogo de');
        $secServ->setTitlePart2('Serviços');
        $secServ->setPosition(1);
        $secServ->setActive(true);
        $secServ->setBgType('color');
        $secServ->setBgColor('#0f172a');
        $this->em->persist($secServ);

        // Bloco de listagem de subcategorias
        $blkCatList = new PageBlock();
        $blkCatList->setSection($secServ);
        $blkCatList->setType('sub_categories');
        $blkCatList->setPreTitle('ESPECIALIDADES');
        $blkCatList->setTitle('Consulte nossas frentes de atuação');
        $blkCatList->setPosition(1);
        $blkCatList->setUpdatedAt(new \DateTimeImmutable());
        $this->em->persist($blkCatList);

        $this->em->flush();

        $io->success('Mocking de dados e imagens Picsum finalizado! Site WAB Digital pronto em https://127.0.0.1:8000/.');

        return Command::SUCCESS;
    }

    /**
     * Otimizado para baixar rapidamente a imagem para a pasta correta.
     */
    private function downloadImage(string $prefix, string $subfolder, int $w = 800, int $h = 600): string
    {
        $filename = $prefix . uniqid() . '.jpg';
        $uploadDir = $this->params->get('kernel.project_dir') . '/public/' . $subfolder;
        
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }

        $path = $uploadDir . '/' . $filename;
        $seed = 'wab_' . uniqid();
        $url = "https://picsum.photos/seed/{$seed}/{$w}/{$h}";

        try {
            $imageContent = @file_get_contents($url);
            if ($imageContent !== false) {
                file_put_contents($path, $imageContent);
                return $filename;
            }
        } catch (\Exception $e) {
            // Silencioso
        }

        return '';
    }
}
