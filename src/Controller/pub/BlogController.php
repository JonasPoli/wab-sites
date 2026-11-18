<?php

namespace App\Controller\pub;

use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use App\Service\TenantContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

/**
 * Blog de site institucional (blog-nos-sites-institucionais): as notícias cadastradas no
 * /admin servidas em /blog quando o site tem o interruptor "Blog" ligado e o tema não é o
 * portal de notícias (Tenant::hasBlog()). Sem blog, as três rotas respondem 404.
 *
 * Article e Category passam pelo filtro de tenant do domínio: um slug de outro site não acha nada.
 * O template é o do tema (themes/<tema>/blog/) quando existe; senão, o comum (themes/_comum/blog/).
 */
class BlogController extends AbstractController
{
    public const PER_PAGE = 9;

    /** Robôs e pré-visualizações de link não contam visualização (mesma regra do portal). */
    private const BOT_USER_AGENT = '~bot|crawl|spider|slurp|facebookexternalhit|whatsapp|preview~i';

    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly Environment $twig,
    ) {}

    #[Route('/blog', name: 'pub_blog', methods: ['GET'])]
    public function index(Request $request, ArticleRepository $articles): Response
    {
        $this->exigirBlog();
        [$page, $pages] = $this->currentPage($request, $articles->countVisible(), self::PER_PAGE);

        return $this->render($this->template('list'), [
            'category' => null,
            'articles' => $articles->findVisiblePage(null, $page, self::PER_PAGE),
            'page'     => $page,
            'pages'    => $pages,
        ]);
    }

    #[Route('/blog/categoria/{slug}', name: 'pub_blog_category', methods: ['GET'], priority: 10)]
    public function category(string $slug, Request $request, CategoryRepository $categories, ArticleRepository $articles): Response
    {
        $this->exigirBlog();
        $category = $categories->findOneBy(['slug' => $slug]) ?? throw $this->createNotFoundException('Categoria não encontrada.');
        [$page, $pages] = $this->currentPage($request, $articles->countVisible($category), self::PER_PAGE);

        return $this->render($this->template('list'), [
            'category' => $category,
            'articles' => $articles->findVisiblePage($category, $page, self::PER_PAGE),
            'page'     => $page,
            'pages'    => $pages,
        ]);
    }

    #[Route('/blog/{slug}', name: 'pub_blog_post', methods: ['GET'])]
    public function post(string $slug, Request $request, ArticleRepository $articles): Response
    {
        $this->exigirBlog();
        $article = $articles->findOneVisibleBySlug($slug) ?? throw $this->createNotFoundException('Post não encontrado.');

        $userAgent = trim((string) $request->headers->get('User-Agent'));
        if ($userAgent !== '' && !preg_match(self::BOT_USER_AGENT, $userAgent)) {
            $articles->incrementViews($article);
        }

        return $this->render($this->template('post'), [
            'article' => $article,
            'next'    => $articles->findNextOlder($article),
        ]);
    }

    private function exigirBlog(): void
    {
        if (!$this->tenantContext->getTenant()?->hasBlog()) {
            throw $this->createNotFoundException('Página não encontrada.');
        }
    }

    private function template(string $nome): string
    {
        $tema = $this->tenantContext->getTenant()?->getTheme() ?? 'cetec';
        $proprio = "themes/{$tema}/blog/{$nome}.html.twig";

        return $this->twig->getLoader()->exists($proprio) ? $proprio : "themes/_comum/blog/{$nome}.html.twig";
    }

    /** Página pedida em ?pagina=N; 404 fora do intervalo (mesma regra do portal). */
    private function currentPage(Request $request, int $total, int $perPage): array
    {
        $raw = $request->query->get('pagina', '1');
        $page = ctype_digit((string) $raw) ? (int) $raw : 0;
        $pages = max(1, (int) ceil($total / $perPage));
        if ($page < 1 || $page > $pages) {
            throw $this->createNotFoundException('Página não encontrada.');
        }
        return [$page, $pages];
    }
}
