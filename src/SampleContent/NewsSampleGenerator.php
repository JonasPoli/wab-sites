<?php

namespace App\SampleContent;

use App\Entity\Article;
use App\Entity\Author;
use App\Entity\Category;
use App\Entity\Enum\ArticleStatus;
use App\Entity\Enum\AuthorStatus;
use App\Entity\Page;
use App\Entity\PageBlock;
use App\Entity\PageSection;
use App\Entity\Tenant;
use App\Entity\User;
use App\Service\PlaceholderImageFactory;
use Doctrine\ORM\EntityManagerInterface;
use Random\Engine\Mt19937;
use Random\Randomizer;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Notícias de exemplo do tema noticias. Tudo o que cria fica com sample = true;
 * a remoção apaga só o que tem a marca e mantém editorias e jornalistas que
 * passaram a ter conteúdo real. Toda consulta diz o tenant: no console o filtro
 * de tenant do Doctrine está desligado.
 */
final class NewsSampleGenerator implements SampleContentGeneratorInterface
{
    /** Fuso do pool do site em produção; o PHP de linha de comando do servidor está em UTC. */
    public const TIMEZONE = 'America/Sao_Paulo';
    public const EMAIL_DOMAIN = 'exemplo.invalid';

    private const COVER_DIR = 'uploads/article';
    private const PHOTO_DIR = 'uploads/author';
    private const FILE_PREFIX = 'exemplo-';

    /** Ordem em que as editorias se alternam na lista. */
    private const CATEGORY_ORDER = ['institucional', 'cidades', 'cultura', 'capital', 'formacao'];
    /** Idade das 7 notícias mais recentes, em minutos; as seguintes vão de 26 horas a 30 dias. */
    private const RECENT_AGES = [2, 25, 70, 180, 300, 540, 1200];
    private const MIN_OLD_AGE = 26 * 60;
    private const MAX_AGE = 30 * 24 * 60;
    /** Visualizações das 7 primeiras (todas publicadas nas últimas 24 horas). */
    private const RECENT_VIEWS = [12, 180, 95, 140, 60, 210, 35];
    private const FEATURED_POSITIONS = [1, 4, 6, 9];
    private const EDITED_POSITION = 4;
    private const LINK_COUNTS = [2, 1, 0, 3];
    private const PALETTE = [[0, 39, 92], [19, 93, 21], [254, 177, 1], [80, 46, 120], [30, 90, 160], [160, 60, 40]];

    /** @var list<string> Arquivos gravados por generate() nesta execução */
    private array $writtenFiles = [];

    /** @var list<array{string, string}> [pasta, arquivo] das fotos dos registros removidos */
    private array $filesToDelete = [];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PlaceholderImageFactory $images,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly SluggerInterface $slugger,
    ) {
    }

    #[\Override]
    public function theme(): string
    {
        return 'noticias';
    }

    #[\Override]
    public function label(): string
    {
        return 'notícias';
    }

    #[\Override]
    public function maxQuantity(): int
    {
        return count(NewsSampleCatalog::articles());
    }

    #[\Override]
    public function countSamples(Tenant $tenant): int
    {
        return (int) $this->em->createQuery('SELECT COUNT(a.id) FROM ' . Article::class . ' a WHERE a.tenant = :t AND a.sample = true')
            ->setParameter('t', $tenant)
            ->getSingleScalarResult();
    }

    // ------------------------------------------------------------------
    // Geração
    // ------------------------------------------------------------------

    #[\Override]
    public function plan(Tenant $tenant, int $quantity): SampleReport
    {
        $plan = $this->buildPlan($tenant, $quantity);
        $report = new SampleReport();
        $report->line(sprintf('Notícias de exemplo: %d (o catálogo tem %d)', count($plan['items']), $this->maxQuantity()));
        foreach ($plan['categories'] as $slug => $c) {
            $parent = NewsSampleCatalog::CATEGORIES[$slug][2];
            $report->line(sprintf(
                'Editoria %s: %s%s',
                NewsSampleCatalog::CATEGORIES[$slug][0],
                $c['existing'] ? 'já existe no site e será usada (não é marcada como exemplo)' : 'será criada como exemplo',
                $parent ? ' (subeditoria de ' . NewsSampleCatalog::CATEGORIES[$parent][0] . ')' : '',
            ));
        }
        foreach ($plan['authors'] as $a) {
            $report->line(sprintf('Jornalista de exemplo: %s (/autor/%s, %s, %s)', $a['name'], $a['slug'], $a['email'], $a['photo'] ? 'com foto' : 'sem foto'));
        }
        foreach ($plan['items'] as $i => $it) {
            $report->line(sprintf(
                '%02d. %s · %s · %s%s%s%s — %s',
                $i + 1,
                $this->ageLabel($it['age']),
                $it['cover'] ? 'com capa' : 'sem capa',
                $it['kicker'] ? 'chapéu' : 'sem chapéu',
                ' · ' . count($it['links']) . ' link(s)',
                $it['featured'] ? ' · Leia também' : '',
                $it['views'] ? ' · ' . $it['views'] . ' visualizações' : '',
                $it['title'],
            ));
        }
        return $report;
    }

    #[\Override]
    public function generate(Tenant $tenant, int $quantity): SampleReport
    {
        $plan = $this->buildPlan($tenant, $quantity);
        $report = new SampleReport();

        // ---- Editorias (pais antes das filhas) ----------------------------
        $categories = [];
        $created = 0;
        foreach ($plan['categories'] as $slug => $c) {
            if ($c['existing']) {
                $categories[$slug] = $c['existing'];
                continue;
            }
            [$name, $description, $parentSlug] = NewsSampleCatalog::CATEGORIES[$slug];
            $category = (new Category())
                ->setTenant($tenant)
                ->setName($name)
                ->setSlug($slug)
                ->setDescription($description)
                ->setShowInHeader(true)
                ->setParent($parentSlug ? $categories[$parentSlug] : null)
                ->setSample(true);
            $this->em->persist($category);
            $categories[$slug] = $category;
            $created++;
        }

        // ---- Jornalistas e os usuários deles ------------------------------
        $authors = [];
        $photos = 0;
        foreach ($plan['authors'] as $key => $a) {
            $user = (new User())
                ->setUsername($a['email'])
                ->setEmail($a['email'])
                ->setName($a['name'])
                ->setWorkGroup(User::WORKGROUP_JOURNALIST)
                ->setTenant($tenant);
            // Senha aleatória que ninguém conhece: o jornalista de exemplo não entra no site
            $user->setPassword($this->hasher->hashPassword($user, bin2hex(random_bytes(32))));
            $this->em->persist($user);

            $now = $this->now();
            $author = (new Author())
                ->setTenant($tenant)
                ->setUser($user)
                ->setName($a['name'])
                ->setSlug($a['slug'])
                ->setOutlet($a['outlet'])
                ->setCity($a['city'])
                ->setBio($a['bio'])
                ->setStatus(AuthorStatus::Approved)
                ->setCreatedAt($now->modify('-40 days'))
                ->setReviewedAt($now->modify('-39 days'))
                ->setSample(true);
            if ($a['photo']) {
                $file = $this->image(self::PHOTO_DIR, $a['slug'], 'autor-' . $a['slug'], 400, 400, $key, $usedPhoto);
                if ($file) {
                    $author->setPhoto($file);
                    $photos++;
                }
            }
            $this->em->persist($author);
            $authors[$key] = $author;
        }

        // ---- Notícias -------------------------------------------------------
        $fromPhoto = 0;
        $fromGradient = 0;
        foreach ($plan['items'] as $i => $it) {
            $published = $this->now()->modify(sprintf('-%d minutes', $it['age']));
            $article = (new Article())
                ->setTenant($tenant)
                ->setTitle($it['title'])
                ->setSlug($it['slug'])
                ->setKicker($it['kicker'])
                ->setSummary($it['summary'])
                ->setBody($it['body'])
                ->setCategory($categories[$it['category']])
                ->setCategories(array_map(fn (string $s) => $categories[$s], $it['others']))
                ->setAuthor($it['author'] !== null ? $authors[$it['author']] : null)
                ->setStatus(ArticleStatus::Published)
                ->setPublishedAt($published)
                ->setFeatured($it['featured'])
                ->setViewCount($it['views'])
                ->setRelatedLinks($it['links'])
                ->setCreatedAt($published)
                ->setUpdatedAt($it['edited'] ? $published->modify('+2 hours') : $published)
                ->setSample(true);
            if ($it['cover']) {
                $file = $this->image(self::COVER_DIR, $it['slug'], $it['slug'], 1200, 675, $i, $usedPhoto);
                if ($file) {
                    $article->setCoverImage($file)
                        ->setCoverCaption('Imagem ilustrativa: ' . mb_strtolower(NewsSampleCatalog::CATEGORIES[$it['category']][0]))
                        ->setCoverCredit($usedPhoto ? 'Picsum Photos (exemplo)' : 'Arte ilustrativa (exemplo)');
                    $usedPhoto ? $fromPhoto++ : $fromGradient++;
                } else {
                    $report->warn(sprintf('Sem capa para "%s": não foi possível baixar a foto nem gerar o degradê.', $it['title']));
                }
            }
            $this->em->persist($article);
        }

        $this->em->flush();

        $report->line(sprintf(
            'Criadas %d notícias de exemplo (%d capas do picsum.photos, %d em degradê, %d sem capa de propósito).',
            count($plan['items']),
            $fromPhoto,
            $fromGradient,
            count(array_filter($plan['items'], fn ($it) => !$it['cover'])),
        ));
        $report->line(sprintf('Editorias: %d criadas como exemplo, %d já existiam.', $created, count($categories) - $created));
        $report->line(sprintf('Jornalistas de exemplo: %d (%d com foto), sem acesso ao site.', count($authors), $photos));
        if ($fromGradient > 0) {
            $report->warn(sprintf('%d capas vieram do degradê porque o picsum.photos não respondeu.', $fromGradient));
        }
        return $report;
    }

    /**
     * Monta tudo o que generate() vai gravar, sem gravar nem baixar nada.
     *
     * @return array{
     *   items: list<array{title: string, slug: string, kicker: ?string, summary: string, body: string, category: string, others: list<string>, author: ?int, age: int, cover: bool, featured: bool, views: int, edited: bool, links: list<array{label: string, url: string}>}>,
     *   categories: array<string, array{existing: ?Category}>,
     *   authors: array<int, array{name: string, slug: string, email: string, outlet: string, city: string, bio: string, photo: bool}>
     * }
     */
    private function buildPlan(Tenant $tenant, int $quantity): array
    {
        $selected = $this->pick($tenant, $quantity);
        $n = count($selected);

        // Editorias usadas, com os pais
        $needed = [];
        foreach ($selected as $item) {
            $needed[$item['c']] = true;
            if ($parent = NewsSampleCatalog::CATEGORIES[$item['c']][2]) {
                $needed[$parent] = true;
            }
        }
        $categories = [];
        foreach (NewsSampleCatalog::CATEGORIES as $slug => $_) { // pais vêm antes das filhas no catálogo
            if (isset($needed[$slug])) {
                $categories[$slug] = ['existing' => $this->em->getRepository(Category::class)->findOneBy(['tenant' => $tenant, 'slug' => $slug])];
            }
        }

        // Slugs únicos dentro do site
        $articleSlugs = array_flip($this->em->createQuery('SELECT a.slug FROM ' . Article::class . ' a WHERE a.tenant = :t')
            ->setParameter('t', $tenant)->getSingleColumnResult());
        $authorSlugs = array_flip($this->em->createQuery('SELECT au.slug FROM ' . Author::class . ' au WHERE au.tenant = :t')
            ->setParameter('t', $tenant)->getSingleColumnResult());

        $items = [];
        foreach ($selected as $i => $item) {
            $items[] = [
                'title'    => $item['t'],
                'slug'     => $this->unique($this->slug($item['t']), $articleSlugs),
                'kicker'   => $i % 3 === 2 ? null : $item['k'],
                'summary'  => $item['s'],
                'body'     => $this->body($item, $i),
                'category' => $item['c'],
                'others'   => $this->otherCategories($item['c'], $i, array_keys($categories)),
                'author'   => $i % 6 === 5 ? null : $i % count(NewsSampleCatalog::AUTHORS),
                'age'      => $this->ageMinutes($i, $n),
                'cover'    => $i % 5 !== 3,
                'featured' => in_array($i, self::FEATURED_POSITIONS, true),
                'views'    => self::RECENT_VIEWS[$i] ?? (($n - $i) * 7),
                'edited'   => $i === self::EDITED_POSITION,
                'links'    => [],
            ];
        }
        // Links relacionados: as notícias seguintes da própria lista
        foreach ($items as $i => &$it) {
            $count = min(self::LINK_COUNTS[$i % 4], $n - 1);
            for ($k = 1; $k <= $count; $k++) {
                $target = $items[($i + $k) % $n];
                $it['links'][] = ['label' => $target['title'], 'url' => '/noticias/' . $target['slug']];
            }
        }
        unset($it);

        // Só os jornalistas que assinam alguma notícia
        $used = array_unique(array_filter(array_column($items, 'author'), fn ($a) => $a !== null));
        sort($used);
        $authors = [];
        foreach ($used as $key) {
            [$name, $outlet, $city, $bio, $photo] = NewsSampleCatalog::AUTHORS[$key];
            $slug = $this->unique($this->slug($name), $authorSlugs);
            $authors[$key] = [
                'name' => $name, 'slug' => $slug, 'email' => $this->uniqueEmail($slug . '.' . $tenant->getId()),
                'outlet' => $outlet, 'city' => $city, 'bio' => $bio, 'photo' => $photo,
            ];
        }

        return ['items' => $items, 'categories' => $categories, 'authors' => $authors];
    }

    /**
     * Escolhe as notícias do catálogo sem repetir, alternando as editorias, para
     * que mesmo um lote pequeno tenha várias. A escolha é a mesma para o mesmo
     * domínio, então --simular mostra exatamente o que será criado.
     *
     * @return list<array<string, mixed>>
     */
    private function pick(Tenant $tenant, int $quantity): array
    {
        $random = new Randomizer(new Mt19937(crc32((string) $tenant->getDomain())));
        $groups = [];
        foreach (NewsSampleCatalog::articles() as $item) {
            $groups[$item['c']][] = $item;
        }
        foreach ($groups as $c => $list) {
            $groups[$c] = $random->shuffleArray($list);
        }
        $picked = [];
        while (count($picked) < $quantity) {
            $progress = false;
            foreach (self::CATEGORY_ORDER as $c) {
                if (count($picked) < $quantity && !empty($groups[$c])) {
                    $picked[] = array_shift($groups[$c]);
                    $progress = true;
                }
            }
            if (!$progress) {
                break;
            }
        }
        return $picked;
    }

    /** Uma segunda editoria na 3ª notícia e a cada 7 a partir da 6ª. */
    private function otherCategories(string $principal, int $i, array $available): array
    {
        if ($i !== 2 && $i % 7 !== 5) {
            return [];
        }
        foreach (['cultura', 'cidades', 'institucional', 'formacao', 'capital'] as $slug) {
            if ($slug !== $principal && in_array($slug, $available, true)) {
                return [$slug];
            }
        }
        return [];
    }

    private function ageMinutes(int $i, int $n): int
    {
        $recent = count(self::RECENT_AGES);
        if ($i < $recent) {
            return self::RECENT_AGES[$i];
        }
        $olds = $n - $recent;
        if ($olds <= 1) {
            return self::MAX_AGE;
        }
        return (int) round(self::MIN_OLD_AGE + (self::MAX_AGE - self::MIN_OLD_AGE) * ($i - $recent) / ($olds - 1));
    }

    private function ageLabel(int $minutes): string
    {
        return match (true) {
            $minutes < 60 => "há $minutes min",
            $minutes < 1440 => 'há ' . intdiv($minutes, 60) . ' h',
            $minutes < 2880 => 'há 1 dia',
            default => 'há ' . intdiv($minutes, 1440) . ' dias',
        };
    }

    private function body(array $item, int $i): string
    {
        $e = fn (string $s) => htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        [$quote, $who] = $item['q'];
        $quote = rtrim($quote, '.'); // “... atrás”, diz Fulano. (sem o ponto antes das aspas)
        $html = '<p>' . $e($item['p'][0]) . '</p>';
        if ($i % 2 === 0) {
            $html .= '<blockquote>“' . $e($quote) . '”, diz ' . $e($who) . '.</blockquote>';
        }
        $html .= '<p>' . $e($item['p'][1]) . '</p>';
        $html .= '<h2>' . $e($item['h']) . '</h2><ol>';
        foreach ($item['l'] as $point) {
            $html .= '<li data-list="bullet">' . $e($point) . '</li>';
        }
        $html .= '</ol>';
        if ($i % 2 === 1) {
            $html .= '<p>“' . $e($quote) . '”, resume ' . $e($who) . '.</p>';
        }
        return $html . '<p class="ql-align-center"><em>Texto fictício, criado para mostrar o layout do portal.</em></p>';
    }

    private function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', new \DateTimeZone(self::TIMEZONE));
    }

    private function slug(string $text): string
    {
        return strtolower((string) $this->slugger->slug($text)) ?: 'exemplo';
    }

    /** @param array<string, mixed> $taken slugs já usados (a chave), atualizado aqui */
    private function unique(string $base, array &$taken): string
    {
        $slug = $base;
        $n = 2;
        while (isset($taken[$slug])) {
            $slug = $base . '-' . $n++;
        }
        $taken[$slug] = true;
        return $slug;
    }

    /** E-mail e login do jornalista de exemplo, únicos em todo o sistema (o login é global). */
    private function uniqueEmail(string $local): string
    {
        $n = 1;
        do {
            $email = $local . ($n > 1 ? '-' . $n : '') . '@' . self::EMAIL_DOMAIN;
            $taken = (int) $this->em->createQuery('SELECT COUNT(u.id) FROM ' . User::class . ' u WHERE u.username = :e OR u.email = :e')
                ->setParameter('e', $email)->getSingleScalarResult() > 0;
            $n++;
        } while ($taken);
        return $email;
    }

    /** Foto do picsum.photos ou degradê; anota o arquivo para apagar se a transação falhar. */
    private function image(string $dir, string $slug, string $seed, int $w, int $h, int $i, ?bool &$usedPhoto): ?string
    {
        $name = self::FILE_PREFIX . substr($slug, 0, 80) . '-' . bin2hex(random_bytes(3)) . '.jpg';
        $file = $this->images->photoOrGradient(
            $dir, $name, $seed, $w, $h,
            self::PALETTE[$i % count(self::PALETTE)],
            self::PALETTE[($i + 2) % count(self::PALETTE)],
            $usedPhoto,
        );
        if ($file) {
            $this->writtenFiles[] = $this->images->path($dir, $file);
        }
        return $file;
    }

    // ------------------------------------------------------------------
    // Remoção
    // ------------------------------------------------------------------

    #[\Override]
    public function planRemoval(Tenant $tenant): SampleReport
    {
        $r = $this->computeRemoval($tenant);
        $report = new SampleReport();
        $report->line(sprintf(
            'Seriam removidas %d notícias, %d editorias e %d jornalistas de exemplo (com os usuários deles).',
            count($r['articles']), count($r['categories']), count($r['authors']),
        ));
        foreach ($r['warnings'] as $w) {
            $report->warn($w);
        }
        return $report;
    }

    #[\Override]
    public function remove(Tenant $tenant): SampleReport
    {
        $r = $this->computeRemoval($tenant);

        foreach ($r['articles'] as $a) {
            if ($a['cover']) {
                $this->filesToDelete[] = [self::COVER_DIR, $a['cover']];
            }
        }
        foreach ($r['authors'] as $a) {
            if ($a['photo']) {
                $this->filesToDelete[] = [self::PHOTO_DIR, $a['photo']];
            }
        }

        // DELETE por DQL: as fotos só saem depois do commit (afterCommit), não no flush
        if ($ids = array_column($r['articles'], 'id')) {
            $this->em->createQuery('DELETE FROM ' . Article::class . ' a WHERE a.tenant = :t AND a.sample = true AND a.id IN (:ids)')
                ->setParameter('t', $tenant)->setParameter('ids', $ids)->execute();
        }
        if ($ids = array_column($r['authors'], 'id')) {
            $userIds = array_column($r['authors'], 'user');
            $this->em->createQuery('DELETE FROM ' . Author::class . ' au WHERE au.tenant = :t AND au.sample = true AND au.id IN (:ids)')
                ->setParameter('t', $tenant)->setParameter('ids', $ids)->execute();
            $this->em->createQuery('DELETE FROM ' . User::class . ' u WHERE u.tenant = :t AND u.workGroup = :wg AND u.id IN (:ids)')
                ->setParameter('t', $tenant)->setParameter('wg', User::WORKGROUP_JOURNALIST)->setParameter('ids', $userIds)->execute();
        }
        // Editorias das folhas para a raiz (a lista já vem nessa ordem)
        foreach ($r['categories'] as $c) {
            $this->em->createQuery('DELETE FROM ' . Category::class . ' c WHERE c.tenant = :t AND c.sample = true AND c.id = :id')
                ->setParameter('t', $tenant)->setParameter('id', $c['id'])->execute();
        }

        $report = new SampleReport();
        $report->line(sprintf(
            'Removidas %d notícias, %d editorias e %d jornalistas de exemplo (com os usuários deles).',
            count($r['articles']), count($r['categories']), count($r['authors']),
        ));
        foreach ($r['warnings'] as $w) {
            $report->warn($w);
        }
        return $report;
    }

    /**
     * @return array{
     *   articles: list<array{id: int, cover: ?string}>,
     *   authors: list<array{id: int, user: int, photo: ?string}>,
     *   categories: list<array{id: int}>,
     *   warnings: list<string>
     * }
     */
    private function computeRemoval(Tenant $tenant): array
    {
        $warnings = [];

        $articles = array_map(
            fn (Article $a) => ['id' => $a->getId(), 'cover' => $a->getCoverImage()],
            $this->em->getRepository(Article::class)->findBy(['tenant' => $tenant, 'sample' => true]),
        );

        $authors = [];
        foreach ($this->em->getRepository(Author::class)->findBy(['tenant' => $tenant, 'sample' => true]) as $au) {
            $real = (int) $this->em->createQuery('SELECT COUNT(a.id) FROM ' . Article::class . ' a WHERE a.tenant = :t AND a.sample = false AND a.author = :au')
                ->setParameter('t', $tenant)->setParameter('au', $au)->getSingleScalarResult();
            if ($real > 0) {
                $warnings[] = sprintf('O jornalista de exemplo "%s" foi mantido: assina %d notícia(s) real(is).', $au->getName(), $real);
                continue;
            }
            $authors[] = ['id' => $au->getId(), 'user' => $au->getUser()?->getId(), 'photo' => $au->getPhoto()];
        }

        /** @var Category[] $samples */
        $samples = $this->em->getRepository(Category::class)->findBy(['tenant' => $tenant, 'sample' => true]);
        $keep = [];
        foreach ($samples as $c) {
            $reasons = $this->realUsage($tenant, $c);
            if ($reasons) {
                $keep[$c->getId()] = true;
                $warnings[] = sprintf('A editoria de exemplo "%s" foi mantida: tem %s.', $c->getName(), implode(', ', $reasons));
            }
        }
        // Editoria com filha mantida ou filha real também fica (apagar a mãe apagaria a filha)
        do {
            $changed = false;
            foreach ($samples as $c) {
                if (isset($keep[$c->getId()])) {
                    continue;
                }
                foreach ($c->getChildren() as $child) {
                    if (!$child->isSample() || isset($keep[$child->getId()])) {
                        $keep[$c->getId()] = true;
                        $warnings[] = sprintf('A editoria de exemplo "%s" foi mantida: tem a subcategoria "%s", que fica.', $c->getName(), $child->getName());
                        $changed = true;
                        break;
                    }
                }
            }
        } while ($changed);

        $categories = [];
        $depth = fn (Category $c) => $c->getParent() ? 1 + ($c->getParent()->getParent() ? 1 : 0) : 0;
        usort($samples, fn (Category $a, Category $b) => $depth($b) <=> $depth($a));
        foreach ($samples as $c) {
            if (!isset($keep[$c->getId()])) {
                $categories[] = ['id' => $c->getId()];
            }
        }

        return ['articles' => $articles, 'authors' => $authors, 'categories' => $categories, 'warnings' => $warnings];
    }

    /** @return list<string> o que de real usa a editoria */
    private function realUsage(Tenant $tenant, Category $c): array
    {
        $reasons = [];
        $articles = (int) $this->em->createQuery(
            'SELECT COUNT(DISTINCT a.id) FROM ' . Article::class . ' a LEFT JOIN a.categories o
             WHERE a.tenant = :t AND a.sample = false AND (a.category = :c OR o.id = :cid)'
        )->setParameter('t', $tenant)->setParameter('c', $c)->setParameter('cid', $c->getId())->getSingleScalarResult();
        if ($articles) {
            $reasons[] = "$articles notícia(s) real(is)";
        }
        $pages = (int) $this->em->createQuery('SELECT COUNT(p.id) FROM ' . Page::class . ' p WHERE p.tenant = :t AND p.category = :c')
            ->setParameter('t', $tenant)->setParameter('c', $c)->getSingleScalarResult();
        if ($pages) {
            $reasons[] = "$pages página(s)";
        }
        $sections = (int) $this->em->createQuery('SELECT COUNT(s.id) FROM ' . PageSection::class . ' s WHERE s.category = :c')
            ->setParameter('c', $c)->getSingleScalarResult();
        if ($sections) {
            $reasons[] = "$sections seção(ões)";
        }
        $blocks = (int) $this->em->createQuery('SELECT COUNT(b.id) FROM ' . PageBlock::class . ' b WHERE b.relatedCategory = :c')
            ->setParameter('c', $c)->getSingleScalarResult();
        if ($blocks) {
            $reasons[] = "$blocks bloco(s) que a citam";
        }
        return $reasons;
    }

    // ------------------------------------------------------------------
    // Arquivos
    // ------------------------------------------------------------------

    #[\Override]
    public function afterCommit(): void
    {
        foreach ($this->filesToDelete as [$dir, $name]) {
            $path = $this->images->path($dir, $name);
            if (is_file($path)) {
                @unlink($path);
            }
        }
        $this->filesToDelete = [];
        $this->writtenFiles = [];
    }

    #[\Override]
    public function afterRollback(): void
    {
        foreach ($this->writtenFiles as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
        $this->writtenFiles = [];
        $this->filesToDelete = [];
    }
}
