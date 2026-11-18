<?php

namespace App\Twig;

use App\Entity\Article;
use App\Entity\Category;
use App\Repository\ArticleRepository;
use App\Repository\AuthorRepository;
use App\Repository\CategoryRepository;
use App\Repository\PageRepository;
use App\Service\TenantContext;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Funções e filtros do tema noticias. As consultas passam pelos repositórios
 * filtrados pelo tenant do domínio aberto.
 */
class ArticleExtension extends AbstractExtension
{
    public const TIMEZONE = 'America/Sao_Paulo';

    /** Diferença mínima entre publicação e edição para mostrar "Atualizado há…". */
    private const UPDATED_THRESHOLD_SECONDS = 300;

    public function __construct(
        private readonly ArticleRepository $articles,
        private readonly CategoryRepository $categories,
        private readonly PageRepository $pages,
        private readonly AuthorRepository $authors,
        private readonly UrlGeneratorInterface $urls,
        private readonly TenantContext $tenantContext,
    ) {}

    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('news_menu_categories', $this->menuCategories(...)),
            new TwigFunction('news_menu_pages', fn () => $this->pages->findBy(['showInHeader' => true], ['position' => 'ASC', 'title' => 'ASC'])),
            new TwigFunction('news_featured', fn (int $limit = 3) => $this->articles->findFeatured($limit)),
            new TwigFunction('news_most_read', fn (int $limit = 5) => $this->articles->findMostRead(7, $limit)),
            new TwigFunction('article_updated_label', $this->updatedLabel(...)),
            new TwigFunction('news_pending_authors', fn () => $this->authors->countPending()),
            // Blog em site institucional e portal: o endereço certo num lugar só
            new TwigFunction('article_url', $this->articleUrl(...)),
            new TwigFunction('articles_url', $this->articlesUrl(...)),
            new TwigFunction('latest_articles', $this->latestArticles(...)),
            new TwigFunction('blog_categories', $this->blogCategories(...)),
        ];
    }

    #[\Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter('relative_time', $this->relativeTime(...)),
        ];
    }

    private function temBlog(): bool
    {
        return (bool) $this->tenantContext->getTenant()?->hasBlog();
    }

    private function ehPortal(): bool
    {
        return $this->tenantContext->getTenant()?->getTheme() === 'noticias';
    }

    /** Endereço do post: /blog/{slug} no site com blog, /noticias/{slug} no portal. */
    public function articleUrl(Article $article, int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        return $this->urls->generate($this->temBlog() ? 'pub_blog_post' : 'pub_article_show', ['slug' => $article->getSlug()], $referenceType);
    }

    /** Lista de posts (de uma categoria, se informada): /blog… no site com blog, /noticias ou /categoria/… no portal. */
    public function articlesUrl(?Category $category = null): string
    {
        if ($this->temBlog()) {
            return $category
                ? $this->urls->generate('pub_blog_category', ['slug' => $category->getSlug()])
                : $this->urls->generate('pub_blog');
        }
        return $category
            ? $this->urls->generate('pub_category', ['slug' => $category->getSlug()])
            : $this->urls->generate('pub_articles');
    }

    /**
     * Posts visíveis mais recentes, de todas as categorias ou de uma (e das subcategorias).
     * Vazio num site sem blog que não é portal.
     *
     * @return Article[]
     */
    public function latestArticles(int $limit = 3, ?Category $category = null): array
    {
        if (!$this->temBlog() && !$this->ehPortal()) {
            return [];
        }
        return $this->articles->findVisiblePage($category, 1, max(1, min(12, $limit)));
    }

    /**
     * Categorias que têm post visível, em ordem alfabética (atalhos da lista do blog).
     *
     * @return Category[]
     */
    public function blogCategories(): array
    {
        $ids = $this->articles->findCategoryIdsWithVisibleArticles();
        if ($ids === []) {
            return [];
        }
        return $this->categories->findBy(['id' => $ids], ['name' => 'ASC']);
    }

    /**
     * Categorias raiz com showInHeader que têm notícias visíveis (principal ou
     * entre as outras) ou páginas, nelas ou nas subcategorias.
     *
     * @return list<array{category: Category, children: Category[]}>
     */
    public function menuCategories(): array
    {
        $withArticles = array_flip($this->articles->findCategoryIdsWithVisibleArticles());
        $hasContent = fn (Category $c) => isset($withArticles[$c->getId()]) || $c->getPages()->count() > 0;

        $menu = [];
        foreach ($this->categories->findForHeader() as $root) {
            $children = [];
            foreach ($root->getChildren() as $child) {
                if ($hasContent($child)) {
                    $children[] = $child;
                }
            }
            if ($hasContent($root) || $children) {
                $menu[] = ['category' => $root, 'children' => $children];
            }
        }
        return $menu;
    }

    /** "Agora", "Há N minutos", "Há N horas" ou dd/mm/aaaa (horário de Brasília). */
    public function relativeTime(?\DateTimeInterface $date, ?\DateTimeInterface $now = null): string
    {
        if (!$date) {
            return '';
        }
        $now ??= new \DateTimeImmutable();
        $seconds = $now->getTimestamp() - $date->getTimestamp();

        if ($seconds < 60) {
            return 'Agora';
        }
        if ($seconds < 3600) {
            $n = intdiv($seconds, 60);
            return $n === 1 ? 'Há 1 minuto' : "Há $n minutos";
        }
        if ($seconds < 86400) {
            $n = intdiv($seconds, 3600);
            return $n === 1 ? 'Há 1 hora' : "Há $n horas";
        }

        return \DateTimeImmutable::createFromInterface($date)
            ->setTimezone(new \DateTimeZone(self::TIMEZONE))
            ->format('d/m/Y');
    }

    /** "Atualizado há 2 horas" quando a notícia foi editada depois de publicada. */
    public function updatedLabel(Article $article): ?string
    {
        $diff = $article->getUpdatedAt()->getTimestamp() - $article->getPublishedAt()->getTimestamp();
        if ($diff <= self::UPDATED_THRESHOLD_SECONDS) {
            return null;
        }
        $relative = $this->relativeTime($article->getUpdatedAt());
        return str_starts_with($relative, 'Há') || $relative === 'Agora'
            ? 'Atualizado ' . mb_strtolower($relative)
            : 'Atualizado em ' . $relative;
    }
}
