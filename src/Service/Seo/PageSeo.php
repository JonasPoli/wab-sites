<?php

declare(strict_types=1);

namespace App\Service\Seo;

use App\Entity\Article;
use App\Entity\Author;
use App\Entity\Category;
use App\Entity\Page;
use App\Entity\Tenant;
use App\Service\TenantContext;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Monta, a partir do que a página tem em mãos, tudo o que o `<head>` precisa:
 * título, descrição (cascata que nunca fica vazia), canônico, imagem de compartilhamento,
 * robots e o grafo JSON-LD do schema.org. Único lugar com essa regra; os quatro temas
 * imprimem o resultado por `templates/pub/_seo_head.html.twig`.
 *
 * Ver a spec `seo-das-paginas` da change seo-e-performance-dos-sites.
 */
final class PageSeo
{
    private const LOCALE = 'pt_BR';
    private const TITULO_MAXIMO = 65;

    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly RequestStack $requestStack,
        private readonly UrlGeneratorInterface $urls,
    ) {
    }

    /**
     * @param array<string, mixed> $contexto chaves conhecidas: page, category, article,
     *                                       author, articles, pagination, is_home, robots
     */
    public function montar(array $contexto = []): SeoData
    {
        $tenant = $this->tenantContext->getTenant();
        $pagina = $contexto['page'] ?? null;
        $categoria = $contexto['category'] ?? null;
        $noticia = $contexto['article'] ?? null;
        $autor = $contexto['author'] ?? null;

        if ($noticia instanceof Article) {
            return $this->daNoticia($noticia, $tenant);
        }
        if ($autor instanceof Author) {
            return $this->doAutor($autor, $tenant, $this->lista($contexto));
        }
        // Lista do blog de site institucional (/blog e /blog/categoria/{slug})
        $rota = $this->requestStack->getCurrentRequest()?->attributes->get('_route');
        if (in_array($rota, ['pub_blog', 'pub_blog_category'], true)) {
            return $this->doBlog($tenant, $categoria instanceof Category ? $categoria : null, $this->lista($contexto));
        }
        if ($categoria instanceof Category) {
            return $this->daCategoria($categoria, $tenant, $this->lista($contexto));
        }
        if ($pagina instanceof Page) {
            return $this->daPagina($pagina, $tenant);
        }

        return $this->daListagemOuHome($tenant, $contexto, $this->lista($contexto));
    }

    // ---------------------------------------------------------------- páginas

    private function daPagina(Page $pagina, ?Tenant $tenant): SeoData
    {
        $ehHome = $tenant?->getHomePage()?->getId() === $pagina->getId();
        $canonico = $ehHome
            ? $this->base() . '/'
            : $this->absoluta('pub_page_show', ['slug' => $pagina->getSlug()]);

        $descricao = $this->primeiroTexto([
            $pagina->getSeoDescription(),
            $ehHome ? $tenant?->getSeoDescription() : null,
            TextoDeResumo::daPagina($pagina),
            $ehHome ? null : $tenant?->getSeoDescription(),
            $this->descricaoDeReserva($tenant, $pagina->getTitle()),
        ]);

        $imagem = $this->imagem($tenant, $pagina->getCoverImage() ? 'uploads/page_cover/' . $pagina->getCoverImage() : null);

        $titulo = $ehHome
            ? $this->tituloDaHome($tenant)
            : $this->titulo($pagina->getSeoTitle() ?: $pagina->getTitle(), $tenant);

        $grafo = $ehHome
            ? $this->grafoDaHome($tenant, $descricao)
            : [
                $this->noWebPage($titulo, $descricao, $canonico, $tenant),
                $this->noTrilha($this->trilhaDaPagina($pagina)),
            ];

        return new SeoData(
            title: $titulo,
            description: $descricao,
            canonical: $canonico,
            ogType: $ehHome ? 'website' : 'article',
            image: $imagem['url'],
            imageWidth: $imagem['largura'],
            imageHeight: $imagem['altura'],
            jsonLd: array_values(array_filter($grafo)),
            siteName: $tenant?->getName(),
        );
    }

    private function daCategoria(Category $categoria, ?Tenant $tenant, array $itens): SeoData
    {
        $canonico = $this->absoluta('pub_category', ['slug' => $categoria->getSlug()]);
        $titulo = $this->titulo($categoria->getName(), $tenant);
        $descricao = $this->primeiroTexto([
            TextoDeResumo::resumir($categoria->getDescription()),
            $tenant?->getSeoDescription(),
            $this->descricaoDeReserva($tenant, $categoria->getName()),
        ]);

        $imagem = $this->imagem($tenant, null);

        return new SeoData(
            title: $titulo,
            description: $descricao,
            canonical: $canonico,
            image: $imagem['url'],
            imageWidth: $imagem['largura'],
            imageHeight: $imagem['altura'],
            jsonLd: [
                $this->noColecao($titulo, $descricao, $canonico, $itens),
                $this->noTrilha([
                    ['nome' => 'Início', 'url' => $this->base() . '/'],
                    ['nome' => $categoria->getName(), 'url' => $canonico],
                ]),
            ],
            siteName: $tenant?->getName(),
        );
    }

    /** Lista do blog: CollectionPage com os posts, trilha Início → Blog (→ categoria). */
    private function doBlog(?Tenant $tenant, ?Category $categoria, array $itens): SeoData
    {
        $canonico = $this->canonicaDoPedido();
        $nome = $categoria ? $categoria->getName() : 'Blog';
        $titulo = $this->titulo($categoria ? $nome . ' — Blog' : 'Blog', $tenant);
        $descricao = $this->primeiroTexto([
            $categoria ? TextoDeResumo::resumir($categoria->getDescription()) : null,
            $tenant?->getSeoDescription(),
            $this->descricaoDeReserva($tenant, $nome),
        ]);
        $imagem = $this->imagem($tenant, null);

        $trilha = [['nome' => 'Início', 'url' => $this->base() . '/'], ['nome' => 'Blog', 'url' => $this->absoluta('pub_blog')]];
        if ($categoria) {
            $trilha[] = ['nome' => $nome, 'url' => $this->absoluta('pub_blog_category', ['slug' => $categoria->getSlug()])];
        }

        return new SeoData(
            title: $titulo,
            description: $descricao,
            canonical: $canonico,
            image: $imagem['url'],
            imageWidth: $imagem['largura'],
            imageHeight: $imagem['altura'],
            jsonLd: [$this->noColecao($titulo, $descricao, $canonico, $itens), $this->noTrilha($trilha)],
            siteName: $tenant?->getName(),
        );
    }

    private function daNoticia(Article $noticia, ?Tenant $tenant): SeoData
    {
        // No blog de site institucional o post mora em /blog/{slug} e é um BlogPosting
        $ehBlog = (bool) $tenant?->hasBlog();
        $canonico = $this->absoluta($ehBlog ? 'pub_blog_post' : 'pub_article_show', ['slug' => $noticia->getSlug()]);
        $titulo = $this->titulo($noticia->getTitle(), $tenant);
        $descricao = $this->primeiroTexto([
            TextoDeResumo::resumir($noticia->getSummary()),
            TextoDeResumo::resumir($noticia->getBody()),
            $tenant?->getSeoDescription(),
            $this->descricaoDeReserva($tenant, $noticia->getTitle()),
        ]);

        $capa = $noticia->getCoverImage() ? 'uploads/article/' . $noticia->getCoverImage() : null;
        $imagem = $this->imagem($tenant, $capa);

        // Notícia de exemplo sai com noindex e sem NewsArticle: ela não é conteúdo de verdade.
        if ($noticia->isSample()) {
            return new SeoData(
                title: $titulo,
                description: $descricao,
                canonical: $canonico,
                robots: 'noindex, follow',
                ogType: 'article',
                image: $imagem['url'],
                imageWidth: $imagem['largura'],
                imageHeight: $imagem['altura'],
                jsonLd: [],
                siteName: $tenant?->getName(),
            );
        }

        $no = [
            '@context' => 'https://schema.org',
            '@type' => $ehBlog ? 'BlogPosting' : 'NewsArticle',
            'headline' => TextoDeResumo::cortar($noticia->getTitle(), 110),
            'description' => $descricao,
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $canonico],
            'url' => $canonico,
            'datePublished' => $noticia->getPublishedAt()->format(\DATE_ATOM),
            'dateModified' => $noticia->getUpdatedAt()->format(\DATE_ATOM),
            'inLanguage' => 'pt-BR',
            'isAccessibleForFree' => true,
        ];

        if ($noticia->getCategory() !== null) {
            $no['articleSection'] = $noticia->getCategory()->getName();
        }
        if ($imagem['url'] !== null) {
            $no['image'] = [$imagem['url']];
        }
        if ($noticia->getAuthor() !== null && !$ehBlog) {
            $autor = $noticia->getAuthor();
            $no['author'] = array_filter([
                '@type' => 'Person',
                'name' => $autor->getName(),
                'url' => $autor->getSlug() ? $this->absoluta('pub_author', ['slug' => $autor->getSlug()]) : null,
            ], static fn ($v) => $v !== null && $v !== '');
        }
        $editor = $this->noOrganizacao($tenant);
        if ($editor !== null) {
            $no['publisher'] = $editor;
        }

        $trilha = [['nome' => 'Início', 'url' => $this->base() . '/']];
        if ($ehBlog) {
            $trilha[] = ['nome' => 'Blog', 'url' => $this->absoluta('pub_blog')];
        }
        if ($noticia->getCategory() !== null) {
            $trilha[] = [
                'nome' => $noticia->getCategory()->getName(),
                'url' => $this->absoluta($ehBlog ? 'pub_blog_category' : 'pub_category', ['slug' => $noticia->getCategory()->getSlug()]),
            ];
        }
        $trilha[] = ['nome' => $noticia->getTitle(), 'url' => $canonico];

        return new SeoData(
            title: $titulo,
            description: $descricao,
            canonical: $canonico,
            ogType: 'article',
            image: $imagem['url'],
            imageWidth: $imagem['largura'],
            imageHeight: $imagem['altura'],
            jsonLd: [$no, $this->noTrilha($trilha)],
            siteName: $tenant?->getName(),
        );
    }

    private function doAutor(Author $autor, ?Tenant $tenant, array $itens): SeoData
    {
        $canonico = $this->absoluta('pub_author', ['slug' => $autor->getSlug()]);
        $titulo = $this->titulo($autor->getName(), $tenant);
        $descricao = $this->primeiroTexto([
            TextoDeResumo::resumir($autor->getBio()),
            $this->descricaoDeReserva($tenant, 'Notícias de ' . $autor->getName()),
        ]);

        $foto = $autor->getPhoto() ? 'uploads/author/' . $autor->getPhoto() : null;
        $imagem = $this->imagem($tenant, $foto);

        $pessoa = array_filter([
            '@type' => 'Person',
            'name' => $autor->getName(),
            'url' => $canonico,
            'description' => TextoDeResumo::resumir($autor->getBio()) ?: null,
            'image' => $imagem['url'],
            'worksFor' => $autor->getOutlet() ? ['@type' => 'Organization', 'name' => $autor->getOutlet()] : null,
        ], static fn ($v) => $v !== null && $v !== '');

        return new SeoData(
            title: $titulo,
            description: $descricao,
            canonical: $canonico,
            ogType: 'profile',
            image: $imagem['url'],
            imageWidth: $imagem['largura'],
            imageHeight: $imagem['altura'],
            jsonLd: [
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'ProfilePage',
                    'name' => $titulo,
                    'url' => $canonico,
                    'mainEntity' => $pessoa,
                    'hasPart' => $this->listaDeItens($itens),
                ],
                $this->noTrilha([
                    ['nome' => 'Início', 'url' => $this->base() . '/'],
                    ['nome' => $autor->getName(), 'url' => $canonico],
                ]),
            ],
            siteName: $tenant?->getName(),
        );
    }

    /** Home sem página montada (portal de notícias) e `/noticias`. */
    private function daListagemOuHome(?Tenant $tenant, array $contexto, array $itens): SeoData
    {
        $pedido = $this->requestStack->getCurrentRequest();
        $rota = $pedido?->attributes->get('_route');
        $ehHome = $rota === 'pub_home' || ($contexto['is_home'] ?? false) === true;

        $canonico = $ehHome ? $this->base() . '/' : $this->canonicaDoPedido();
        $titulo = $ehHome ? $this->tituloDaHome($tenant) : $this->titulo('Notícias', $tenant);
        $descricao = $this->primeiroTexto([
            $tenant?->getSeoDescription(),
            $this->descricaoDeReserva($tenant, $ehHome ? null : 'Notícias'),
        ]);
        $imagem = $this->imagem($tenant, null);

        $grafo = $ehHome
            ? $this->grafoDaHome($tenant, $descricao)
            : [
                $this->noColecao($titulo, $descricao, $canonico, $itens),
                $this->noTrilha([
                    ['nome' => 'Início', 'url' => $this->base() . '/'],
                    ['nome' => 'Notícias', 'url' => $canonico],
                ]),
            ];

        return new SeoData(
            title: $titulo,
            description: $descricao,
            canonical: $canonico,
            image: $imagem['url'],
            imageWidth: $imagem['largura'],
            imageHeight: $imagem['altura'],
            jsonLd: array_values(array_filter($grafo)),
            siteName: $tenant?->getName(),
        );
    }

    // ------------------------------------------------------------- JSON-LD

    /** @return list<array<string, mixed>> */
    private function grafoDaHome(?Tenant $tenant, string $descricao): array
    {
        $grafo = [];
        $organizacao = $this->noOrganizacao($tenant, completo: true);
        if ($organizacao !== null) {
            $organizacao['@context'] = 'https://schema.org';
            $grafo[] = $organizacao;
        }
        $grafo[] = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $tenant?->getName(),
            'url' => $this->base() . '/',
            'description' => $descricao,
            'inLanguage' => 'pt-BR',
        ], static fn ($v) => $v !== null && $v !== '');

        return $grafo;
    }

    /** @return array<string, mixed>|null */
    private function noOrganizacao(?Tenant $tenant, bool $completo = false): ?array
    {
        if ($tenant === null) {
            return null;
        }

        $no = [
            '@type' => 'Organization',
            'name' => $tenant->getName(),
            'url' => $this->base() . '/',
        ];

        if ($tenant->getLogo()) {
            $no['logo'] = $this->urlAbsolutaDeArquivo('uploads/tenant/logo/' . $tenant->getLogo());
        }
        if (!$completo) {
            return $no;
        }

        if ($tenant->getAddress()) {
            $no['address'] = ['@type' => 'PostalAddress', 'streetAddress' => $tenant->getAddress()];
        }
        if ($tenant->getPhone()) {
            $no['telephone'] = $tenant->getPhone();
        }
        if ($tenant->getContactEmail()) {
            $no['email'] = $tenant->getContactEmail();
        }
        $redes = array_values(array_filter([
            $tenant->getFacebookLink(),
            $tenant->getInstagramLink(),
            $tenant->getLinkedinLink(),
            $tenant->getYoutubeLink(),
        ], static fn ($v) => is_string($v) && $v !== ''));
        if ($redes !== []) {
            $no['sameAs'] = $redes;
        }

        return $no;
    }

    /**
     * @param list<array{nome: string, url: string}> $itens
     *
     * @return array<string, mixed>|null
     */
    private function noTrilha(array $itens): ?array
    {
        if (count($itens) < 2) {
            return null;
        }

        $elementos = [];
        foreach (array_values($itens) as $i => $item) {
            $elementos[] = array_filter([
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $item['nome'],
                'item' => $i === count($itens) - 1 ? null : $item['url'],
            ], static fn ($v) => $v !== null);
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $elementos,
        ];
    }

    /** @param list<array{nome: string, url: string}> $itens */
    private function noColecao(string $titulo, string $descricao, string $canonico, array $itens): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $titulo,
            'description' => $descricao,
            'url' => $canonico,
            'inLanguage' => 'pt-BR',
            'mainEntity' => $this->listaDeItens($itens),
        ], static fn ($v) => $v !== null && $v !== '' && $v !== []);
    }

    /** @param list<array{nome: string, url: string}> $itens */
    private function listaDeItens(array $itens): array
    {
        $primeiro = $this->primeiraPosicao();
        $elementos = [];
        foreach (array_values($itens) as $i => $item) {
            $elementos[] = [
                '@type' => 'ListItem',
                'position' => $primeiro + $i,
                'url' => $item['url'],
                'name' => $item['nome'],
            ];
        }

        return ['@type' => 'ItemList', 'itemListElement' => $elementos];
    }

    private function noWebPage(string $titulo, string $descricao, string $canonico, ?Tenant $tenant): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $titulo,
            'description' => $descricao,
            'url' => $canonico,
            'inLanguage' => 'pt-BR',
            'isPartOf' => $tenant ? ['@type' => 'WebSite', 'name' => $tenant->getName(), 'url' => $this->base() . '/'] : null,
        ], static fn ($v) => $v !== null && $v !== '');
    }

    // ------------------------------------------------------------- auxiliares

    /** Primeira opção com texto de verdade; a última entra mesmo curta, para nunca ficar vazia. */
    private function primeiroTexto(array $opcoes): string
    {
        $ultima = '';
        foreach ($opcoes as $opcao) {
            $texto = TextoDeResumo::resumir(is_string($opcao) ? $opcao : null);
            if ($texto === '') {
                continue;
            }
            $ultima = $texto;
            if (mb_strlen($texto) >= TextoDeResumo::MINIMO) {
                return $texto;
            }
        }

        return $ultima;
    }

    /** Última linha da cascata: sempre passa dos 50 caracteres. */
    private function descricaoDeReserva(?Tenant $tenant, ?string $assunto): string
    {
        $nome = $tenant?->getName() ?: 'Site';
        $complemento = TextoDeResumo::limpar($tenant?->getSeoTitle());

        $partes = array_filter([
            $assunto && $assunto !== $nome ? $assunto : null,
            $nome,
            $complemento !== '' && $complemento !== $nome ? $complemento : null,
        ]);
        $base = implode(' — ', $partes);

        if (mb_strlen($base) < TextoDeResumo::MINIMO) {
            $base .= '. Conheça nossos serviços, tire suas dúvidas e fale com a nossa equipe.';
        }

        return TextoDeResumo::cortar($base);
    }

    private function titulo(string $parte, ?Tenant $tenant): string
    {
        $nome = $tenant?->getName() ?: '';
        $parte = trim(TextoDeResumo::limpar($parte));
        if ($nome === '' || $parte === $nome) {
            return TextoDeResumo::cortar($parte ?: $nome, self::TITULO_MAXIMO);
        }

        $completo = $parte . ' | ' . $nome;
        if (mb_strlen($completo) <= self::TITULO_MAXIMO) {
            return $completo;
        }

        $sobra = self::TITULO_MAXIMO - mb_strlen($nome) - 3;

        return $sobra > 20
            ? TextoDeResumo::cortar($parte, $sobra) . ' | ' . $nome
            : TextoDeResumo::cortar($parte, self::TITULO_MAXIMO);
    }

    private function tituloDaHome(?Tenant $tenant): string
    {
        $nome = $tenant?->getName() ?: 'Site';
        $complemento = trim(TextoDeResumo::limpar($tenant?->getSeoTitle()));

        if ($complemento === '' || $complemento === $nome) {
            return TextoDeResumo::cortar($nome, self::TITULO_MAXIMO);
        }
        if (str_contains($complemento, $nome)) {
            return TextoDeResumo::cortar($complemento, self::TITULO_MAXIMO);
        }

        return TextoDeResumo::cortar($nome . ' — ' . $complemento, self::TITULO_MAXIMO);
    }

    /** @return array{url: ?string, largura: ?int, altura: ?int} */
    private function imagem(?Tenant $tenant, ?string $caminhoRelativo): array
    {
        $candidatos = array_filter([
            $caminhoRelativo,
            $tenant?->getOgImage() ? 'uploads/tenant/og/' . $tenant->getOgImage() : null,
            $tenant?->getLogo() ? 'uploads/tenant/logo/' . $tenant->getLogo() : null,
        ]);

        foreach ($candidatos as $caminho) {
            $absoluto = $this->raizPublica() . '/' . ltrim($caminho, '/');
            if (!is_file($absoluto)) {
                continue;
            }
            $tamanho = @getimagesize($absoluto);

            return [
                'url' => $this->urlAbsolutaDeArquivo($caminho),
                'largura' => $tamanho[0] ?? null,
                'altura' => $tamanho[1] ?? null,
            ];
        }

        return ['url' => null, 'largura' => null, 'altura' => null];
    }

    /** @return list<array{nome: string, url: string}> */
    private function lista(array $contexto): array
    {
        $itens = [];
        foreach (['articles', 'pagination', 'items'] as $chave) {
            $colecao = $contexto[$chave] ?? null;
            if ($colecao === null) {
                continue;
            }
            foreach ($colecao as $item) {
                if ($item instanceof Article && $item->getSlug() !== '') {
                    $itens[] = [
                        'nome' => $item->getTitle(),
                        'url' => $this->absoluta($this->tenantContext->getTenant()?->hasBlog() ? 'pub_blog_post' : 'pub_article_show', ['slug' => $item->getSlug()]),
                    ];
                } elseif ($item instanceof Page && $item->getSlug() !== '') {
                    $itens[] = [
                        'nome' => $item->getTitle(),
                        'url' => $this->absoluta('pub_page_show', ['slug' => $item->getSlug()]),
                    ];
                }
            }
            if ($itens !== []) {
                break;
            }
        }

        return $itens;
    }

    /** @return list<array{nome: string, url: string}> */
    private function trilhaDaPagina(Page $pagina): array
    {
        $trilha = [['nome' => 'Início', 'url' => $this->base() . '/']];

        $categoria = $pagina->getCategory();
        if ($categoria !== null) {
            $pai = $categoria->getParent();
            if ($pai !== null) {
                $trilha[] = ['nome' => $pai->getName(), 'url' => $this->absoluta('pub_category', ['slug' => $pai->getSlug()])];
            }
            $trilha[] = ['nome' => $categoria->getName(), 'url' => $this->absoluta('pub_category', ['slug' => $categoria->getSlug()])];
        }

        $trilha[] = [
            'nome' => $pagina->getTitle(),
            'url' => $this->absoluta('pub_page_show', ['slug' => $pagina->getSlug()]),
        ];

        return $trilha;
    }

    /** Posição do primeiro item da página atual numa listagem paginada (20 por página). */
    private function primeiraPosicao(): int
    {
        return ($this->paginaAtual() - 1) * 20 + 1;
    }

    public function base(): string
    {
        return $this->requestStack->getCurrentRequest()?->getSchemeAndHttpHost() ?? '';
    }

    /**
     * URL da página atual sem parâmetro de consulta e sem barra final sobrando. A única
     * exceção é a paginação (`?pagina=2`): ela muda o conteúdo, então faz parte do canônico.
     */
    private function canonicaDoPedido(): string
    {
        $pedido = $this->requestStack->getCurrentRequest();
        if ($pedido === null) {
            return $this->base() . '/';
        }
        $caminho = rtrim($pedido->getPathInfo(), '/');
        $url = $this->base() . ($caminho === '' ? '/' : $caminho);

        $paginacao = $this->paginaAtual();

        return $paginacao > 1 ? $url . '?pagina=' . $paginacao : $url;
    }

    /** Número da página de uma listagem paginada (1 quando não há paginação). */
    private function paginaAtual(): int
    {
        $pedido = $this->requestStack->getCurrentRequest();
        if ($pedido === null) {
            return 1;
        }

        return max(1, (int) ($pedido->query->get('pagina') ?? $pedido->query->get('page') ?? 1));
    }

    private function absoluta(string $rota, array $parametros = []): string
    {
        try {
            return $this->urls->generate($rota, $parametros, UrlGeneratorInterface::ABSOLUTE_URL);
        } catch (\Throwable) {
            return $this->base() . '/';
        }
    }

    private function urlAbsolutaDeArquivo(string $caminhoRelativo): string
    {
        return $this->base() . '/' . ltrim($caminhoRelativo, '/');
    }

    private function raizPublica(): string
    {
        return \dirname(__DIR__, 3) . '/public';
    }

    public function locale(): string
    {
        return self::LOCALE;
    }
}
