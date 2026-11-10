<?php

namespace App\DataFixtures;

use App\Entity\Article;
use App\Entity\Author;
use App\Entity\Category;
use App\Entity\Enum\ArticleStatus;
use App\Entity\Enum\AuthorStatus;
use App\Entity\Page;
use App\Entity\PageBlock;
use App\Entity\PageBlockTeamMember;
use App\Entity\PageBlockTestimonial;
use App\Entity\PageSection;
use App\Entity\Tenant;
use App\Entity\User;
use App\Service\PlaceholderImageFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Portal de notícias de exemplo (tema noticias) em http://noticias.localhost:8000.
 *
 *   - Tenant "AJoPE — Local" com as cores e o logo de var/jornalista/logo.png
 *   - Equipe: noticias.admin / noticias.editor / noticias.revisor (senha: <usuário>123 sem o prefixo)
 *   - Jornalistas (senha jornalista123): aprovada, pendente, recusado e suspenso @exemplo.com
 *   - Editorias, ~25 notícias em várias situações e as páginas "Sobre" e "Todos os blocos"
 *
 * As imagens de capa e as fotos são geradas com GD (nenhuma foto real).
 */
class PortalNoticiasFixtures extends Fixture
{
    public const DOMAIN = 'noticias.localhost';

    private Filesystem $fs;

    public function __construct(
        private readonly UserPasswordHasherInterface $hasher,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        private readonly PlaceholderImageFactory $images,
    ) {
        $this->fs = new Filesystem();
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        // ---- Tenant ---------------------------------------------------------
        $tenant = new Tenant();
        $tenant->setDomain(self::DOMAIN);
        $tenant->setName('AJoPE — Local');
        $tenant->setTheme('noticias');
        $tenant->setPrimaryColor('#072156');
        $tenant->setSecondaryColor('#FDB60F');
        $tenant->setContactEmail('contato@exemplo.com');
        $tenant->setInstagramLink('https://instagram.com/exemplo');
        $tenant->setFacebookLink('https://facebook.com/exemplo');
        $tenant->setYoutubeLink('https://youtube.com/@exemplo');
        $tenant->setSeoDescription('Portal de notícias de exemplo do tema noticias.');
        $tenant->setNavigationSettings([
            'showMenuIcons'   => false,
            'topBarEnabled'   => false,
            'topBarLeft'      => [],
            'topBarRight'     => [],
            'customMenuItems' => [
                ['label' => 'Fale conosco', 'url' => '/p/sobre', 'target' => '_self', 'icon' => '', 'active' => true],
                ['label' => 'Item desligado', 'url' => 'https://exemplo.com', 'target' => '_blank', 'icon' => '', 'active' => false],
            ],
        ]);
        $logo = $this->copyFile('var/jornalista/logo.png', 'uploads/tenant/logo', 'fixture-ajope-logo.png');
        if ($logo) {
            $tenant->setLogo($logo);
        }
        $manager->persist($tenant);
        $manager->flush();

        // ---- Equipe do painel -----------------------------------------------
        $staff = [];
        foreach ([['admin', 'Admin do Portal', 0], ['editor', 'Editora do Portal', 1], ['revisor', 'Revisor do Portal', 2]] as [$login, $name, $wg]) {
            $u = (new User())
                ->setUsername('noticias.' . $login)
                ->setName($name)
                ->setEmail('noticias.' . $login . '@exemplo.com')
                ->setWorkGroup($wg)
                ->setTenant($tenant);
            $u->setPassword($this->hasher->hashPassword($u, $login . '123'));
            $manager->persist($u);
            $staff[$login] = $u;
        }

        // ---- Jornalistas ------------------------------------------------------
        $authors = [];
        $specs = [
            'aprovada' => ['Ana Souza', AuthorStatus::Approved, 'Rádio Exemplo FM', 'Cidade Exemplo', 'Repórter de cidades há 12 anos, cobre política local, cultura e o dia a dia dos bairros.', true],
            'pendente' => ['Bruno Lima', AuthorStatus::Pending, 'Jornal do Interior', 'Vila Nova', 'Fotojornalista e editor de vídeo.', false],
            'recusado' => ['Carla Mendes', AuthorStatus::Refused, null, null, null, false],
            'suspenso' => ['Diego Rocha', AuthorStatus::Suspended, 'TV Exemplo', 'Cidade Exemplo', 'Apresentador e repórter de esportes.', false],
        ];
        $i = 0;
        foreach ($specs as $key => [$name, $status, $outlet, $city, $bio, $withPhoto]) {
            $email = 'jornalista.' . $key . '@exemplo.com';
            $user = (new User())
                ->setUsername($email)
                ->setName($name)
                ->setEmail($email)
                ->setWorkGroup(User::WORKGROUP_JOURNALIST)
                ->setTenant($tenant);
            $user->setPassword($this->hasher->hashPassword($user, 'jornalista123'));
            $manager->persist($user);

            $author = (new Author())
                ->setTenant($tenant)
                ->setUser($user)
                ->setName($name)
                ->setSlug($this->slugify($name))
                ->setPhone('(11) 9' . str_pad((string) (1000 + $i), 4, '0') . '-0000')
                ->setProfessionalRegistration($status === AuthorStatus::Refused ? null : 'MTb ' . (10000 + $i))
                ->setOutlet($outlet)
                ->setCity($city)
                ->setBio($bio)
                ->setStatus($status)
                ->setCreatedAt(new \DateTimeImmutable(sprintf('-%d days', 30 - $i * 5)));
            if ($status !== AuthorStatus::Pending) {
                $author->setReviewedAt(new \DateTimeImmutable('-20 days'))->setReviewedBy($staff['admin']);
            }
            if ($withPhoto) {
                $author->setPhoto($this->makeImage('uploads/author', 'fixture-autor-' . $key . '.jpg', 400, 400, [7, 33, 86], [253, 182, 15]));
            }
            $manager->persist($author);
            $authors[$key] = $author;
            $i++;
        }

        // ---- Editorias ------------------------------------------------------
        $cat = [];
        foreach ([
            'institucional' => ['Institucional', 'Notícias da associação, assembleias e comunicados.'],
            'politica'      => ['Política', 'Poder público, eleições e bastidores.'],
            'cidades'       => ['Cidades', 'O que acontece nos municípios da região.'],
            'cultura'       => ['Cultura', 'Arte, música, livros e eventos.'],
            'esportes'      => ['Esportes', 'Editoria ainda sem notícias (não deve aparecer no menu).'],
        ] as $slug => [$name, $desc]) {
            $c = (new Category())->setTenant($tenant)->setName($name)->setSlug($slug)->setDescription($desc)->setShowInHeader(true);
            $manager->persist($c);
            $cat[$slug] = $c;
        }
        $capital = (new Category())->setTenant($tenant)->setParent($cat['cidades'])->setName('Capital')->setSlug('capital')
            ->setDescription('Notícias da capital.')->setShowInHeader(true);
        $manager->persist($capital);
        $cat['capital'] = $capital;

        // ---- Notícias -------------------------------------------------------
        // [título, chapéu, principal, outras, autor, idade, situação, capa, destaque, views, links]
        $news = [
            ['Assembleia geral da associação aprova novo estatuto', 'Eleições na AJoPE', 'institucional', ['cidades'], 'aprovada', '-30 seconds', 'p', true, true, 3, 2],
            ['Curso gratuito de checagem de fatos abre inscrições', 'Formação', 'institucional', ['cultura'], 'aprovada', '-1 minute', 'p', true, false, 0, 1],
            ['Câmara aprova orçamento participativo para 2027', null, 'politica', ['cidades', 'capital'], 'suspenso', '-5 minutes', 'p', true, false, 64, 0],
            ['Prefeitura anuncia reforma do mercado municipal', 'Obras', 'cidades', [], null, '-65 minutes', 'p', true, false, 120, 3],
            ['Festival de inverno terá 40 apresentações gratuitas', 'Agenda', 'capital', ['cultura'], 'aprovada', '-3 hours', 'p', true, true, 80, 2],
            ['Feira do livro homenageia cronistas da região', null, 'cidades', ['cultura'], 'aprovada', '-5 hours', 'p', false, false, 30, 0],
            ['Vereadores discutem regras para propaganda nas ruas', 'Eleições 2028', 'politica', [], null, '-8 hours', 'p', true, false, 12, 1],
            ['Biblioteca pública amplia horário aos sábados', null, 'capital', [], 'aprovada', '-20 hours', 'p', true, false, 5, 0],
            ['Prêmio regional de jornalismo divulga finalistas', 'Prêmio', 'institucional', [], 'aprovada', '-26 hours', 'p', true, true, 0, 2],
            ['Linha de ônibus noturna passa a atender bairros da zona norte', null, 'cidades', ['capital'], 'suspenso', '-2 days', 'p', true, false, 0, 0],
            ['Mostra de cinema exibe documentários de estudantes', null, 'cidades', ['cultura'], null, '-3 days', 'p', false, false, 0, 0],
            ['Associação lança guia de segurança para repórteres', 'Guia', 'institucional', [], 'aprovada', '-4 days', 'p', true, true, 0, 1],
            ['Audiência pública debate plano diretor', null, 'politica', ['cidades'], 'aprovada', '-5 days', 'p', true, false, 0, 0],
            ['Coral municipal comemora 30 anos com concerto', null, 'capital', ['cultura'], null, '-6 days', 'p', true, false, 0, 0],
            ['Notícia muito vista há 10 dias não entra em Mais lidas', 'Teste', 'cidades', [], 'aprovada', '-10 days', 'p', true, false, 999, 0],
            ['Encontro reúne assessores de imprensa de 12 cidades', null, 'institucional', [], 'aprovada', '-11 days', 'p', true, false, 0, 0],
            ['Programa de estágio em comunicação seleciona estudantes', null, 'institucional', ['cidades'], null, '-12 days', 'p', false, false, 0, 1],
            ['Ponte entre bairros é liberada após seis meses de obras', null, 'cidades', [], 'suspenso', '-13 days', 'p', true, false, 0, 0],
            ['Orquestra jovem se apresenta na praça central', null, 'capital', ['cultura'], 'aprovada', '-15 days', 'p', true, false, 0, 0],
            ['Conselho de comunicação toma posse', null, 'politica', ['institucional'], 'aprovada', '-17 days', 'p', true, false, 0, 0],
            ['Mutirão recupera praças e canteiros do centro', null, 'cidades', [], null, '-19 days', 'p', true, false, 0, 0],
            ['Oficina ensina técnicas de entrevista para iniciantes', 'Formação', 'institucional', [], 'aprovada', '-21 days', 'p', false, false, 0, 0],
            ['Primeira notícia do portal: boas-vindas aos leitores', null, null, [], null, '-30 days', 'p', true, false, 0, 0],
            ['Rascunho: pauta sobre o calendário de eventos', 'Rascunho', 'cultura', [], 'aprovada', '-1 hour', 'd', true, false, 0, 0],
            ['Agendada: resultado do prêmio sai às 20h', 'Agendada', 'institucional', [], 'aprovada', '+2 hours', 'p', true, true, 0, 0],
        ];

        $palette = [[7, 33, 86], [19, 93, 21], [253, 182, 15], [80, 46, 120], [30, 90, 160], [160, 60, 40]];
        $usedSlugs = [];
        foreach ($news as $n => [$title, $kicker, $principal, $others, $authorKey, $age, $status, $withCover, $featured, $views, $links]) {
            $slug = $this->slugify($title);
            $base = $slug;
            $k = 2;
            while (isset($usedSlugs[$slug])) { $slug = $base . '-' . $k++; }
            $usedSlugs[$slug] = true;

            $published = new \DateTimeImmutable($age);
            $article = (new Article())
                ->setTenant($tenant)
                ->setTitle($title)
                ->setSlug($slug)
                ->setKicker($kicker)
                ->setSummary('Linha fina de exemplo: ' . mb_strtolower($title) . '. O texto completo traz os detalhes, os prazos e o que muda para a população.')
                ->setBody($this->body($title))
                ->setCategory($principal ? $cat[$principal] : null)
                ->setCategories(array_map(fn ($s) => $cat[$s], $others))
                ->setAuthor($authorKey ? $authors[$authorKey] : null)
                ->setStatus($status === 'd' ? ArticleStatus::Draft : ArticleStatus::Published)
                ->setPublishedAt($published)
                ->setFeatured($featured)
                ->setViewCount($views)
                ->setRelatedLinks(array_slice([
                    ['label' => 'Veja o documento completo', 'url' => 'https://exemplo.com.br/documento'],
                    ['label' => 'Entenda o que muda', 'url' => '/noticias'],
                    ['label' => 'Leia a cobertura anterior', 'url' => 'https://exemplo.com.br/cobertura'],
                ], 0, $links))
                ->setCreatedAt($published)
                // Uma notícia editada 2 horas depois de publicada ("Atualizado há…")
                ->setUpdatedAt($n === 4 ? $published->modify('+2 hours') : $published);
            if ($withCover) {
                $c = $palette[$n % count($palette)];
                $d = $palette[($n + 2) % count($palette)];
                $article->setCoverImage($this->makeImage('uploads/article', sprintf('fixture-noticia-%02d.jpg', $n + 1), 1200, 675, $c, $d));
                $article->setCoverCaption('Imagem ilustrativa da notícia ' . ($n + 1));
                $article->setCoverCredit('Divulgação');
            }
            $manager->persist($article);
        }

        // ---- Páginas institucionais ---------------------------------------
        $this->pageSobre($manager, $tenant);
        $this->pageTodosOsBlocos($manager, $tenant, $cat['institucional']);

        $manager->flush();
    }

    private function pageSobre(ObjectManager $manager, Tenant $tenant): void
    {
        $page = (new Page())->setTenant($tenant)->setTitle('Sobre a AJoPE')->setSlug('sobre')
            ->setShowInHeader(true)->setShowInFooter(true);
        $manager->persist($page);

        $s1 = (new PageSection())->setPage($page)->setPosition(0)->setTitlePart1('Quem')->setTitlePart2('somos');
        $manager->persist($s1);
        $manager->persist((new PageBlock())->setSection($s1)->setType('text_only')->setTitle('A associação')
            ->setText('<p>Texto institucional de exemplo. A associação reúne jornalistas da região e defende a liberdade de imprensa.</p>'));

        $s2 = (new PageSection())->setPage($page)->setPosition(1)->setTitlePart1('Nossa')->setTitlePart2('diretoria');
        $manager->persist($s2);
        $team = (new PageBlock())->setSection($s2)->setType('team')->setTitle('Diretoria');
        $manager->persist($team);
        $manager->persist((new PageBlockTeamMember())->setBlock($team)->setName('Presidente Exemplo')->setRole('Presidente')
            ->setSummary('Jornalista e professora.')->setSlug('presidente-exemplo')->setDetailEnabled(true)->setDetailLayout('classic'));
        $manager->persist((new PageBlockTeamMember())->setBlock($team)->setName('Vice Exemplo')->setRole('Vice-presidente')
            ->setSlug('vice-exemplo')->setDetailEnabled(true)->setDetailLayout('modern'));

        $s3 = (new PageSection())->setPage($page)->setPosition(2);
        $manager->persist($s3);
        $manager->persist((new PageBlock())->setSection($s3)->setType('contact')->setTitle('Fale conosco'));
    }

    /** Uma seção por tipo de bloco, para conferir os 15 tipos no tema noticias. */
    private function pageTodosOsBlocos(ObjectManager $manager, Tenant $tenant, Category $category): void
    {
        $page = (new Page())->setTenant($tenant)->setTitle('Todos os blocos')->setSlug('todos-os-blocos')->setCategory($category);
        $manager->persist($page);

        $types = [
            'banner'         => ['title' => 'Banner de exemplo', 'text' => 'Texto do banner', 'config' => ['ctaText' => 'Saiba mais', 'ctaLink' => '/noticias']],
            'text_image'     => ['title' => 'Imagem e texto', 'text' => 'Texto ao lado da imagem (texto simples neste bloco).', 'config' => ['imagePosition' => 'left']],
            'text_only'      => ['title' => 'Só texto', 'text' => '<p>Bloco de texto simples.</p>'],
            'gallery'        => ['title' => 'Galeria vazia'],
            'newsletter'     => ['title' => 'Receba as novidades'],
            'stats'          => ['title' => 'Números', 'config' => ['stats' => [['number' => '120', 'label' => 'Associados'], ['number' => '35', 'label' => 'Anos']]]],
            'news_call'      => ['title' => 'Chamada para páginas', 'itemCount' => 3, 'relatedCategory' => $category],
            'map'            => ['title' => 'Mapa', 'embedUrl' => 'https://www.google.com/maps/embed?pb=!1m14'],
            'sub_categories' => ['title' => 'Subcategorias', 'relatedCategory' => $category],
            'page_list'      => ['title' => 'Lista de páginas', 'itemCount' => 5, 'relatedCategory' => $category],
            'blurbs4'        => ['title' => 'Quatro blocos', 'config' => ['items' => [
                ['icon' => 'fa-solid fa-newspaper', 'title' => 'Notícias', 'text' => 'Cobertura diária.'],
                ['icon' => 'fa-solid fa-users', 'title' => 'Associados', 'text' => 'Rede de jornalistas.'],
                ['icon' => 'fa-solid fa-graduation-cap', 'title' => 'Cursos', 'text' => 'Formação contínua.'],
                ['icon' => 'fa-solid fa-scale-balanced', 'title' => 'Defesa', 'text' => 'Apoio jurídico.'],
            ]]],
            'testimonials'   => ['title' => 'Depoimentos'],
            'partner_logos'  => ['title' => 'Parceiros', 'config' => ['columns' => 4, 'style' => 'grid']],
            'team'           => ['title' => 'Equipe'],
            'contact'        => ['title' => 'Contato'],
        ];
        $pos = 0;
        foreach ($types as $type => $data) {
            $section = (new PageSection())->setPage($page)->setPosition($pos++);
            $manager->persist($section);
            $block = (new PageBlock())->setSection($section)->setType($type)
                ->setTitle($data['title'] ?? null)
                ->setText($data['text'] ?? null)
                ->setConfig($data['config'] ?? null)
                ->setEmbedUrl($data['embedUrl'] ?? null)
                ->setItemCount($data['itemCount'] ?? null)
                ->setRelatedCategory($data['relatedCategory'] ?? null);
            $manager->persist($block);
            if ($type === 'testimonials') {
                $manager->persist((new PageBlockTestimonial())->setBlock($block)->setName('Leitora Exemplo')->setRole('Assinante')
                    ->setText('O portal mudou a forma como acompanho a cidade.')->setRating(5));
            }
            if ($type === 'team') {
                $manager->persist((new PageBlockTeamMember())->setBlock($block)->setName('Editor Exemplo')->setRole('Editor-chefe'));
            }
        }
    }

    private function body(string $title): string
    {
        return '<p>Este é um texto de exemplo para a notícia <strong>' . htmlspecialchars($title) . '</strong>. '
            . 'Ele existe só para conferir o layout do tema de notícias.</p>'
            . '<h2>O que muda</h2>'
            . '<p>Os principais pontos estão na lista abaixo:</p>'
            . '<ol><li data-list="bullet">Primeiro ponto importante</li><li data-list="bullet">Segundo ponto importante</li></ol>'
            . '<ol><li data-list="ordered">Etapa um</li><li data-list="ordered">Etapa dois</li></ol>'
            . '<blockquote>Uma citação de exemplo, atribuída a uma fonte fictícia.</blockquote>'
            . '<p class="ql-align-center">Parágrafo centralizado.</p>'
            . '<p>Mais informações em <a href="https://exemplo.com.br" target="_blank" rel="noopener noreferrer">exemplo.com.br</a>.</p>';
    }

    private function slugify(string $text): string
    {
        $text = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
        return trim((string) preg_replace('~[^a-z0-9]+~', '-', $text), '-');
    }

    private function copyFile(string $from, string $toDir, string $name): ?string
    {
        $src = $this->projectDir . '/' . $from;
        if (!is_file($src)) {
            return null;
        }
        $this->fs->copy($src, $this->projectDir . '/public/' . $toDir . '/' . $name, true);
        return $name;
    }

    /** Gera uma imagem em degradê (GD) e devolve o nome do arquivo. */
    private function makeImage(string $dir, string $name, int $w, int $h, array $from, array $to): ?string
    {
        return $this->images->gradient($dir, $name, $w, $h, $from, $to);
    }
}
