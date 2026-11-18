<?php

namespace App\Tests\Twig;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Tenant;
use App\Repository\ArticleRepository;
use App\Repository\AuthorRepository;
use App\Repository\CategoryRepository;
use App\Repository\PageRepository;
use App\Service\TenantContext;
use App\Twig\ArticleExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/** Endereços do post e da lista no blog, no portal e num site sem nenhum dos dois (blog-nos-sites-institucionais). */
final class ArticleUrlTest extends TestCase
{
    private function extensao(string $tema, bool $blog, ?ArticleRepository $artigos = null): ArticleExtension
    {
        $urls = $this->createMock(UrlGeneratorInterface::class);
        $urls->method('generate')->willReturnCallback(fn (string $rota, array $p = []) => $rota . (isset($p['slug']) ? ':' . $p['slug'] : ''));

        $contexto = new TenantContext();
        $contexto->setTenant((new Tenant())->setTheme($tema)->setBlogEnabled($blog));

        return new ArticleExtension(
            $artigos ?? $this->createMock(ArticleRepository::class),
            $this->createMock(CategoryRepository::class),
            $this->createMock(PageRepository::class),
            $this->createMock(AuthorRepository::class),
            $urls,
            $contexto,
        );
    }

    public function testSiteComBlog(): void
    {
        $ext = $this->extensao('cetec', true);
        $post = (new Article())->setSlug('mesa-de-madeira');
        $cat = (new Category())->setSlug('tendencias');

        self::assertSame('pub_blog_post:mesa-de-madeira', $ext->articleUrl($post));
        self::assertSame('pub_blog', $ext->articlesUrl());
        self::assertSame('pub_blog_category:tendencias', $ext->articlesUrl($cat));
    }

    public function testPortalDeNoticiasIgnoraOInterruptor(): void
    {
        $ext = $this->extensao('noticias', true);
        $post = (new Article())->setSlug('noticia');

        self::assertSame('pub_article_show:noticia', $ext->articleUrl($post));
        self::assertSame('pub_articles', $ext->articlesUrl());
        self::assertSame('pub_category:capital', $ext->articlesUrl((new Category())->setSlug('capital')));
    }

    public function testSiteSemBlogNaoListaNada(): void
    {
        $artigos = $this->createMock(ArticleRepository::class);
        $artigos->expects(self::never())->method('findVisiblePage');

        self::assertSame([], $this->extensao('cetec', false, $artigos)->latestArticles(3));
    }

    public function testLimiteDaListaRecente(): void
    {
        $artigos = $this->createMock(ArticleRepository::class);
        $artigos->expects(self::once())->method('findVisiblePage')->with(null, 1, 12)->willReturn([]);

        $this->extensao('cetec', true, $artigos)->latestArticles(50);
    }
}
