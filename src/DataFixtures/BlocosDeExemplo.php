<?php

namespace App\DataFixtures;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Enum\ArticleStatus;
use App\Entity\Page;
use App\Entity\PageBlock;
use App\Entity\PageBlockImage;
use App\Entity\PageBlockPartnerLogo;
use App\Entity\PageBlockTeamMember;
use App\Entity\PageBlockTestimonial;
use App\Entity\PageSection;
use App\Entity\Tenant;
use App\Service\PlaceholderImageFactory;
use Doctrine\Persistence\ObjectManager;

/**
 * Páginas de exemplo para conferir os temas institucionais (CetecFixtures, AtelieFixtures):
 *
 *   - "todos-os-blocos": uma seção por tipo de bloco, preenchida;
 *   - "inicio": home em Modo Landing Page com o conteúdo do layout de referência
 *     (openspec/changes/tema-atelie/referencia/proposta-redesign-marcenaria-pichoneri.html).
 *
 * As imagens são degradês gerados com GD (nenhuma foto real, nada da internet).
 */
trait BlocosDeExemplo
{
    abstract protected function imagens(): PlaceholderImageFactory;

    /** @var array<string, string> cache de arquivos gerados por nome */
    private array $gerados = [];

    private function img(string $dir, string $nome, int $w, int $h, array $de, array $para): ?string
    {
        $chave = $dir . '/' . $nome;
        return $this->gerados[$chave] ??= (string) $this->imagens()->gradient($dir, $nome, $w, $h, $de, $para);
    }

    private function secao(ObjectManager $m, Page $page, int $pos, ?string $t1 = null, ?string $t2 = null, bool $noMenu = true): PageSection
    {
        $s = (new PageSection())->setPage($page)->setPosition($pos)->setTitlePart1($t1)->setTitlePart2($t2)->setShowInMenu($noMenu);
        $m->persist($s);
        return $s;
    }

    private function bloco(ObjectManager $m, PageSection $s, string $tipo, array $d = []): PageBlock
    {
        $b = (new PageBlock())->setSection($s)->setType($tipo)
            ->setPreTitle($d['preTitle'] ?? null)
            ->setTitle($d['title'] ?? null)
            ->setText($d['text'] ?? null)
            ->setImage($d['image'] ?? null)
            ->setConfig($d['config'] ?? null)
            ->setEmbedUrl($d['embedUrl'] ?? null)
            ->setItemCount($d['itemCount'] ?? null)
            ->setRelatedCategory($d['relatedCategory'] ?? null)
            ->setPosition($d['position'] ?? 0);
        $m->persist($b);
        return $b;
    }

    /** Sanfona de exemplo (change bloco-sanfona): um item de texto e dois de tabela. */
    public static function sanfonaDeExemplo(): array
    {
        $cabeca = ['Cidade', 'Logo', 'Empresa', 'Resp. Técnico', 'Resp. Empresa', 'Assistência Técnica', 'Distribuidor'];
        return ['preTitle' => 'Onde encontrar', 'title' => 'Distribuidores e assistência técnica', 'text' => 'Uma rede de parceiros treinados em todo o país.',
            'config' => ['firstOpen' => '1', 'panels' => [
                ['title' => 'Como funciona a garantia', 'kind' => 'text', 'table' => ['header' => true, 'rows' => []],
                 'html' => '<p>Todos os técnicos são treinados na fábrica e recebem <strong>certificação</strong>.</p><ul><li>Atendimento em até 48 horas</li><li>Peças originais</li><li>Laudo técnico por escrito</li></ul><p>Dúvidas? <a href="https://exemplo.com.br/contato">Fale com a gente</a>.</p>'],
                ['title' => 'São Paulo', 'kind' => 'table', 'html' => null, 'table' => ['header' => true, 'rows' => [
                    $cabeca,
                    ['SÃO PAULO', '', "Exemplo Equipamentos Ltda\nRua das Flores, 100 — Centro\nCEP 01000-000\nE-mail: contato@exemplo.com.br\nSite: www.exemplo.com.br", 'Ana Souza', 'Carlos Lima', 'X', 'X'],
                    ['CAMPINAS', '', "Modelo Serviços Técnicos\nAv. Brasil, 2000\nFone: (19) 3000-0000", 'João Pereira', 'João Pereira', 'X', ''],
                    ['RIBEIRÃO PRETO', '', "Demonstração Hospitalar ME\nE-mail: vendas@demonstracao.com.br", 'Marta Reis', 'Paulo Reis', '', 'X'],
                ]]],
                ['title' => 'Amapá', 'kind' => 'table', 'html' => null, 'table' => ['header' => false, 'rows' => [
                    ['Entre em contato para saber o distribuidor ou a assistência técnica mais próxima.'],
                    ["Tel.: (11) 2000-0000\nE-mail: assistencia@exemplo.com.br"],
                ]]],
            ]]];
    }

    /** Mapa de representantes de exemplo (change bloco-mapa-de-representantes): 5 registros em 3 estados. */
    public static function mapaDeExemplo(): array
    {
        $ficha = static fn (string $nome, string $cidade, string $fone) => '<h3>' . $nome . '</h3><p>Cidade: ' . $cidade
            . '<br>Fone: <a href="tel:+55' . preg_replace('/\D/', '', $fone) . '">' . $fone . '</a></p><p><strong>Assistência técnica · Distribuidor</strong></p>';
        return ['preTitle' => 'Onde encontrar', 'title' => 'Mapa de representantes',
            'text' => 'Clique no mapa e encontre o representante mais próximo da sua região.',
            'config' => ['emptyMessage' => "Ainda não temos representante neste estado.\nFale com a fábrica: contato@exemplo.com.br",
                'reps' => [
                    ['uf' => 'BA', 'image' => null, 'html' => $ficha('Exemplo Equipamentos Ltda', 'Salvador – BA', '(71) 3000-0000')],
                    ['uf' => 'BA', 'image' => null, 'html' => $ficha('Modelo Serviços Técnicos', 'Feira de Santana – BA', '(75) 3000-0000')],
                    ['uf' => 'MS', 'image' => null, 'html' => $ficha('Demonstração Equipamentos', 'Fátima do Sul – MS', '(67) 99810-0000')],
                    ['uf' => 'SP', 'image' => null, 'html' => $ficha('Paulista Hospitalar', 'Campinas – SP', '(19) 3000-0000')],
                    ['uf' => 'SP', 'image' => null, 'html' => $ficha('Capital Médica', 'São Paulo – SP', '(11) 3000-0000')],
                ]]];
    }

        /** Fotos do portfólio: [título, categoria] */
    private function fotosDoPortfolio(ObjectManager $m, PageBlock $b, string $prefixo, array $fotos): void
    {
        $cores = [[[110, 70, 40], [210, 170, 120]], [[60, 60, 55], [190, 180, 160]], [[150, 90, 30], [240, 210, 160]], [[40, 50, 70], [170, 180, 200]], [[90, 60, 50], [220, 190, 170]]];
        foreach ($fotos as $i => [$titulo, $categoria]) {
            [$de, $para] = $cores[$i % count($cores)];
            $arq = $this->img('uploads/page_block_gallery', sprintf('%s-portfolio-%d.jpg', $prefixo, $i + 1), 1200, 900, $de, $para);
            $m->persist((new PageBlockImage())->setBlock($b)->setFilename($arq)->setTitle($titulo)->setCategory($categoria)
                ->setCaption($titulo . ' — foto de exemplo')->setPosition($i));
        }
    }

    private function paginaTodosOsBlocos(ObjectManager $m, Tenant $tenant, Category $categoria, string $prefixo, bool $comBlog = false): Page
    {
        $page = (new Page())->setTenant($tenant)->setTitle('Todos os blocos')->setSlug('todos-os-blocos')->setCategory($categoria);
        $m->persist($page);

        $foto = $this->img('uploads/page_block', $prefixo . '-destaque.jpg', 1400, 1100, [120, 70, 30], [230, 200, 160]);
        $fotoManifesto = $this->img('uploads/page_block', $prefixo . '-manifesto.jpg', 1400, 1000, [50, 45, 40], [180, 150, 110]);

        $tipos = [
            'hero_split'     => ['preTitle' => 'Do detalhe ao extraordinário', 'title' => 'Madeira, design e histórias feitas para durar.',
                                 'text' => 'Mobiliário e soluções de marcenaria sob medida, com a precisão de hoje e a experiência de gerações.', 'image' => $foto,
                                 'config' => ['titleHighlight' => 'histórias', 'ctaText' => 'Conheça nossos projetos', 'ctaLink' => '#projetos',
                                              'stampNumber' => '88', 'stampLabel' => 'anos de tradição', 'caption' => 'Peças únicas, criadas para cada espaço']],
            'highlights'     => ['config' => ['items' => [
                                    ['title' => '88 anos de história', 'text' => 'Experiência que atravessa gerações'],
                                    ['title' => 'Projetos sob medida', 'text' => 'Para necessidades e ambientes únicos'],
                                    ['title' => 'Projeto em 3D', 'text' => 'Planejamento antes da execução'],
                                    ['title' => 'Execução completa', 'text' => 'Da fabricação à instalação'],
                                 ]]],
            'portfolio'      => ['preTitle' => 'Portfólio selecionado', 'title' => 'O cuidado está em cada detalhe.',
                                 'text' => 'Da concepção ao acabamento, uma seleção de trabalhos.', 'config' => ['note' => 'Fotos de exemplo.', 'ctaText' => 'Quero um projeto assim', 'ctaLink' => '#contato']],
            'statement'      => ['preTitle' => 'Nossa essência', 'title' => 'Tradição artesanal. Precisão contemporânea.',
                                 'text' => 'Uma história construída com dedicação, confiança e respeito à matéria-prima.', 'image' => $fotoManifesto,
                                 'config' => ['statNumber' => '88', 'statLabel' => 'anos de história', 'imagePosition' => 'right']],
            'steps'          => ['preTitle' => 'Como trabalhamos', 'title' => 'Uma boa execução começa pelo diálogo.', 'text' => 'Um percurso simples de entender.',
                                 'config' => ['steps' => [
                                    ['label' => '01 / Entendimento', 'title' => 'Escuta e briefing', 'text' => 'Conhecemos a ideia, o espaço e os objetivos do projeto.'],
                                    ['label' => '', 'title' => 'Projeto e 3D', 'text' => 'Visualização, definições técnicas e detalhamento.'],
                                    ['label' => '03 / Produção', 'title' => 'Fabricação', 'text' => 'Execução com atenção aos materiais e acabamentos.'],
                                    ['label' => '04 / Finalização', 'title' => 'Instalação', 'text' => 'Montagem e ajustes para a entrega do ambiente.'],
                                 ]]],
            'cta'            => ['preTitle' => 'Vamos conversar?', 'title' => 'Seu próximo ambiente começa com uma boa ideia.',
                                 'config' => ['ctaText' => 'Falar sobre meu projeto', 'ctaLink' => 'https://wa.me/5500000000000', 'ctaNewTab' => '1']],
            'video'          => ['preTitle' => 'Projeto 3D', 'title' => 'Planeje sem erros, execute com perfeição.',
                                 'text' => 'Visualize seu projeto em 3D antes da execução.', 'embedUrl' => 'https://www.youtube.com/shorts/7-y0py3nPrQ',
                                 'config' => ['orientation' => 'vertical', 'videoPosition' => 'right']],
            'banner'         => ['title' => 'Banner de exemplo', 'text' => 'Texto do banner', 'config' => ['ctaText' => 'Saiba mais', 'ctaLink' => '#contato']],
            'text_image'     => ['title' => 'Imagem e texto', 'text' => 'Texto ao lado da imagem.', 'image' => $foto, 'config' => ['imagePosition' => 'left']],
            'text_only'      => ['title' => 'Só texto', 'text' => '<p>Bloco de texto simples.</p>'],
            'gallery'        => ['title' => 'Galeria'],
            'newsletter'     => ['title' => 'Receba as novidades'],
            'stats'          => ['title' => 'Números', 'config' => ['stats' => [['number' => '88', 'label' => 'Anos'], ['number' => '1.500+', 'label' => 'Projetos']]]],
            'news_call'      => ['title' => 'Chamada para páginas', 'itemCount' => 3, 'relatedCategory' => $categoria],
            'map'            => ['title' => 'Mapa', 'embedUrl' => 'https://www.google.com/maps/embed?pb=!1m14'],
            'sub_categories' => ['title' => 'Subcategorias', 'relatedCategory' => $categoria],
            'page_list'      => ['title' => 'Lista de páginas', 'itemCount' => 5, 'relatedCategory' => $categoria],
            'blurbs4'        => ['preTitle' => 'Soluções', 'title' => 'Versatilidade para diferentes espaços.', 'config' => ['items' => [
                                    ['icon' => 'fa-solid fa-house', 'title' => 'Residencial', 'text' => 'Cozinhas, armários, painéis e móveis personalizados.'],
                                    ['icon' => 'fa-solid fa-building', 'title' => 'Corporativo', 'text' => 'Ambientes de trabalho e mobiliário sob medida.'],
                                    ['icon' => 'fa-solid fa-industry', 'title' => 'Industrial', 'text' => 'Soluções especiais para necessidades técnicas.'],
                                    ['icon' => 'fa-solid fa-house-medical', 'title' => 'Hospitalar', 'text' => 'Mobiliário adequado às exigências de cada projeto.'],
                                 ]]],
            'testimonials'   => ['preTitle' => 'Quem confia em nosso trabalho', 'title' => 'Depoimentos'],
            'partner_logos'  => ['title' => 'Parceiros', 'config' => ['columns' => 4, 'style' => 'grid']],
            'team'           => ['title' => 'Equipe'],
            'contact'        => ['title' => 'Contato'],
        ];
        $tipos['accordion'] = self::sanfonaDeExemplo();
        $tipos['representatives'] = self::mapaDeExemplo();
        if ($comBlog) {
            $tipos['blog_posts'] = ['preTitle' => 'Blog', 'title' => 'Últimas do blog', 'itemCount' => 3];
        }

        $pos = 0;
        foreach ($tipos as $tipo => $dados) {
            $s = $this->secao($m, $page, $pos++);
            $b = $this->bloco($m, $s, $tipo, $dados);
            match ($tipo) {
                'portfolio' => $this->fotosDoPortfolio($m, $b, $prefixo, [
                    ['Design orgânico e funcional', 'Corporativo'], ['Cozinha planejada', 'Residencial'], ['Texturas e acabamento', 'Detalhes'],
                    ['Estação de trabalho autoral', 'Corporativo'], ['Painéis e revestimentos', 'Residencial'],
                ]),
                'gallery' => $this->fotosDoPortfolio($m, $b, $prefixo . '-galeria', [['Foto 1', null], ['Foto 2', null], ['Foto 3', null]]),
                'testimonials' => $m->persist((new PageBlockTestimonial())->setBlock($b)->setName('Vicente Carlos Meca')->setRole('Cliente')
                    ->setText('Com a marcenaria não existe isso de "não fazemos". Tudo é possível.')->setRating(5)),
                'partner_logos' => array_map(fn (int $i) => $m->persist((new PageBlockPartnerLogo())->setBlock($b)->setName('Parceiro ' . $i)
                    ->setLogoFilename($this->img('uploads/partner_logo', sprintf('%s-parceiro-%d.jpg', $prefixo, $i), 280, 96, [90, 90, 90], [200, 200, 200]))
                    ->setPosition($i)), [1, 2, 3, 4]),
                'team' => $m->persist((new PageBlockTeamMember())->setBlock($b)->setName('Responsável Exemplo')->setRole('Marceneiro')
                    ->setSlug($prefixo . '-responsavel')->setDetailEnabled(true)->setDetailLayout('classic')),
                default => null,
            };
        }

        return $page;
    }

    /**
     * Posts de exemplo do blog: 12 publicados em duas categorias, com capa, mais um
     * rascunho e um agendado para o futuro (que não podem aparecer).
     */
    private function postsDoBlog(ObjectManager $m, Tenant $tenant, string $prefixo): void
    {
        $tendencias = (new Category())->setTenant($tenant)->setName('Tendências')->setSlug('tendencias');
        $dicas = (new Category())->setTenant($tenant)->setName('Dicas de decoração')->setSlug('dicas-de-decoracao');
        $m->persist($tendencias);
        $m->persist($dicas);

        $titulos = [
            'Madeira clara deixa o ambiente mais acolhedor', 'Como escolher o acabamento da cozinha planejada',
            'Painel ripado: onde usar e como cuidar', 'Móveis sob medida para apartamentos pequenos',
            'Cinco cuidados com móveis de madeira maciça', 'Home office com marcenaria planejada',
            'Cores que combinam com madeira natural', 'Closet planejado: medidas que fazem diferença',
            'Recepção de clínica com móveis sob medida', 'Tendências de marcenaria para o próximo ano',
            'Banheiro com gabinete de madeira: vale a pena?', 'Do projeto em 3D à instalação: como funciona',
        ];
        $agora = new \DateTimeImmutable('-1 hour');
        foreach ($titulos as $i => $titulo) {
            $slug = trim((string) preg_replace('~[^a-z0-9]+~', '-', (string) transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $titulo)), '-');
            $capa = $this->img('uploads/article', sprintf('%s-post-%d.jpg', $prefixo, $i + 1), 1200, 750, [100 + $i * 8, 70, 40], [230, 200 - $i * 5, 150]);
            $m->persist((new Article())->setTenant($tenant)->setTitle($titulo)->setSlug($slug)
                ->setSummary('Resumo de exemplo do post "' . $titulo . '", com uma ou duas frases.')
                ->setBody('<p>Texto de exemplo do post <strong>' . htmlspecialchars($titulo) . '</strong>.</p><h2>Um subtítulo</h2><p>Mais um parágrafo, com uma lista:</p><ul><li>Primeiro ponto</li><li>Segundo ponto</li></ul>')
                ->setCategory($i % 2 ? $dicas : $tendencias)
                ->setStatus(ArticleStatus::Published)
                ->setPublishedAt($agora->modify('-' . ($i * 3) . ' days'))
                ->setCoverImage($capa));
        }
        $m->persist((new Article())->setTenant($tenant)->setTitle('Rascunho que não aparece')->setSlug('rascunho-que-nao-aparece')
            ->setBody('<p>Rascunho.</p>')->setCategory($dicas)->setStatus(ArticleStatus::Draft)->setPublishedAt($agora));
        $m->persist((new Article())->setTenant($tenant)->setTitle('Post agendado que não aparece')->setSlug('post-agendado-que-nao-aparece')
            ->setBody('<p>Agendado.</p>')->setCategory($dicas)->setStatus(ArticleStatus::Published)->setPublishedAt(new \DateTimeImmutable('+10 days')));
    }

    /** Home do layout de referência, em Modo Landing Page. */
    private function paginaInicio(ObjectManager $m, Tenant $tenant, string $prefixo, bool $comBlog = false): Page
    {
        $page = (new Page())->setTenant($tenant)->setTitle('Início')->setSlug('inicio')->setShowTitle(false);
        $m->persist($page);

        $foto = $this->img('uploads/page_block', $prefixo . '-destaque.jpg', 1400, 1100, [120, 70, 30], [230, 200, 160]);
        $fotoManifesto = $this->img('uploads/page_block', $prefixo . '-manifesto.jpg', 1400, 1000, [50, 45, 40], [180, 150, 110]);

        $pos = 0;
        $this->bloco($m, $this->secao($m, $page, $pos++, null, null, false), 'hero_split', [
            'preTitle' => 'Do detalhe ao extraordinário', 'title' => 'Madeira, design e histórias feitas para durar.',
            'text' => 'Mobiliário e soluções de marcenaria sob medida, com a precisão de hoje e a experiência de gerações.', 'image' => $foto,
            'config' => ['titleHighlight' => 'histórias', 'ctaText' => 'Conheça nossos projetos', 'ctaLink' => '#projetos',
                         'stampNumber' => '88', 'stampLabel' => 'anos de tradição', 'caption' => 'Peças únicas, criadas para cada espaço'],
        ]);
        $this->bloco($m, $this->secao($m, $page, $pos++, null, null, false), 'highlights', ['config' => ['items' => [
            ['title' => '88 anos de história', 'text' => 'Experiência que atravessa gerações'],
            ['title' => 'Projetos sob medida', 'text' => 'Para necessidades e ambientes únicos'],
            ['title' => 'Projeto em 3D', 'text' => 'Planejamento antes da execução'],
            ['title' => 'Execução completa', 'text' => 'Da fabricação à instalação'],
        ]]]);
        $s = $this->secao($m, $page, $pos++, 'Projetos');
        $s->setShowTitle(false);
        $b = $this->bloco($m, $s, 'portfolio', [
            'preTitle' => 'Portfólio selecionado', 'title' => 'O cuidado está em cada detalhe.',
            'text' => 'Da concepção ao acabamento, uma seleção de trabalhos reais.',
            'config' => ['note' => 'Fotos de exemplo.', 'ctaText' => 'Quero um projeto assim', 'ctaLink' => '#contato'],
        ]);
        $this->fotosDoPortfolio($m, $b, $prefixo, [
            ['Design orgânico e funcional', 'Corporativo'], ['Cozinha planejada', 'Residencial'], ['Texturas e acabamento', 'Detalhes'],
            ['Estação de trabalho autoral', 'Corporativo'], ['Painéis e revestimentos', 'Residencial'],
        ]);
        $s = $this->secao($m, $page, $pos++, 'Nossa história');
        $s->setShowTitle(false);
        $this->bloco($m, $s, 'statement', [
            'preTitle' => 'Nossa essência', 'title' => 'Tradição artesanal. Precisão contemporânea.',
            'text' => 'Uma história construída com dedicação, confiança e respeito à matéria-prima. Unimos experiência em marcenaria, tecnologia e atenção aos detalhes para materializar ideias de diferentes escalas.',
            'image' => $fotoManifesto, 'config' => ['statNumber' => '88', 'statLabel' => 'anos de história', 'imagePosition' => 'right'],
        ]);
        $s = $this->secao($m, $page, $pos++, 'O que fazemos');
        $s->setShowTitle(false);
        $this->bloco($m, $s, 'blurbs4', ['preTitle' => 'Soluções', 'title' => 'Versatilidade para diferentes espaços.',
            'text' => 'Propostas pensadas para a forma como cada ambiente é vivido, utilizado e transformado.',
            'config' => ['items' => [
                ['icon' => 'fa-solid fa-house', 'title' => 'Residencial', 'text' => 'Cozinhas, armários, painéis e móveis personalizados.'],
                ['icon' => 'fa-solid fa-building', 'title' => 'Corporativo', 'text' => 'Ambientes de trabalho e mobiliário funcional sob medida.'],
                ['icon' => 'fa-solid fa-industry', 'title' => 'Industrial', 'text' => 'Soluções especiais para espaços e necessidades técnicas.'],
                ['icon' => 'fa-solid fa-house-medical', 'title' => 'Hospitalar', 'text' => 'Mobiliário adequado às exigências específicas de cada projeto.'],
            ]]]);
        $s = $this->secao($m, $page, $pos++, 'Nosso processo');
        $s->setShowTitle(false);
        $this->bloco($m, $s, 'steps', ['preTitle' => 'Como trabalhamos', 'title' => 'Uma boa execução começa pelo diálogo.',
            'text' => 'Um percurso simples de entender e orientado à qualidade do resultado.',
            'config' => ['steps' => [
                ['label' => '01 / Entendimento', 'title' => 'Escuta e briefing', 'text' => 'Conhecemos a ideia, o espaço e os objetivos do projeto.'],
                ['label' => '02 / Planejamento', 'title' => 'Projeto e 3D', 'text' => 'Visualização, definições técnicas e detalhamento.'],
                ['label' => '03 / Produção', 'title' => 'Fabricação', 'text' => 'Execução com atenção aos materiais e acabamentos.'],
                ['label' => '04 / Finalização', 'title' => 'Instalação', 'text' => 'Montagem e ajustes para a entrega do ambiente.'],
            ]]]);
        $b = $this->bloco($m, $this->secao($m, $page, $pos++, null, null, false), 'testimonials', ['preTitle' => 'Quem confia em nosso trabalho']);
        $m->persist((new PageBlockTestimonial())->setBlock($b)->setName('Vicente Carlos Meca')->setRole('Depoimento de exemplo')
            ->setText('Com a marcenaria não existe isso de "não fazemos". Tudo é possível.')->setRating(5));
        if ($comBlog) {
            $s = $this->secao($m, $page, $pos++, 'Blog');
            $s->setShowTitle(false);
            $this->bloco($m, $s, 'blog_posts', ['preTitle' => 'Blog', 'title' => 'Ideias para o seu ambiente.', 'itemCount' => 3]);
        }
        $this->bloco($m, $this->secao($m, $page, $pos++, null, null, false), 'cta', [
            'preTitle' => 'Vamos conversar?', 'title' => 'Seu próximo ambiente começa com uma boa ideia.',
            'config' => ['ctaText' => 'Falar sobre meu projeto', 'ctaLink' => 'https://wa.me/5500000000000', 'ctaNewTab' => '1'],
        ]);
        $this->bloco($m, $this->secao($m, $page, $pos++, 'Contato'), 'contact', ['title' => 'Peça seu orçamento']);

        return $page;
    }
}
