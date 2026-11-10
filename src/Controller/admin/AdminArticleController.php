<?php

namespace App\Controller\admin;

use App\Entity\Article;
use App\Repository\ArticleRepository;
use App\Repository\AuthorRepository;
use App\Repository\CategoryRepository;
use App\Service\ArticleFormHandler;
use App\Service\TenantContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Notícias do portal no painel: administrador, editor e revisor do site
 * listam, criam, editam e excluem todas as notícias do site.
 * Article é TenantAware: o {id} de outro site responde 404 pelo filtro.
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/noticias', name: 'admin_article_')]
class AdminArticleController extends AbstractController
{
    private const PER_PAGE = 50;

    public function __construct(
        private readonly ArticleRepository $articles,
        private readonly CategoryRepository $categories,
        private readonly AuthorRepository $authors,
        private readonly ArticleFormHandler $form,
        private readonly TenantContext $tenantContext,
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $r): Response
    {
        $page = max(1, $r->query->getInt('pagina', 1));
        $total = $this->articles->countForAdmin();
        return $this->render('admin/article/index.html.twig', [
            'articles' => $this->articles->findForAdmin($page, self::PER_PAGE),
            'page'     => $page,
            'pages'    => max(1, (int) ceil($total / self::PER_PAGE)),
            'total'    => $total,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $r, EntityManagerInterface $em): Response
    {
        $article = new Article();
        $errors = [];
        if ($r->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('article_form', (string) $r->request->get('_token'))) {
                $errors[] = 'Sessão expirada. Atualize a página e tente novamente.';
            } else {
                $article->setTenant($this->tenantContext->requireTenant());
                $errors = $this->form->apply($article, $r, staff: true);
                if (!$errors) {
                    $em->persist($article);
                    $em->flush();
                    $this->addFlash('success', 'Notícia criada.');
                    return $this->redirectToRoute('admin_article_index');
                }
            }
        }
        return $this->renderForm('admin/article/new.html.twig', $article, $errors);
    }

    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Article $article, Request $r, EntityManagerInterface $em): Response
    {
        $errors = [];
        if ($r->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('article_form', (string) $r->request->get('_token'))) {
                $errors[] = 'Sessão expirada. Atualize a página e tente novamente.';
            } else {
                $errors = $this->form->apply($article, $r, staff: true);
                if (!$errors) {
                    $em->flush();
                    $this->addFlash('success', 'Notícia atualizada.');
                    return $this->redirectToRoute('admin_article_index');
                }
            }
        }
        return $this->renderForm('admin/article/edit.html.twig', $article, $errors);
    }

    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Article $article, Request $r, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('del_article_' . $article->getId(), (string) $r->request->get('_token'))) {
            $em->remove($article);
            $em->flush();
            $this->addFlash('success', 'Notícia excluída.');
        }
        return $this->redirectToRoute('admin_article_index');
    }

    /** @param string[] $errors */
    private function renderForm(string $template, Article $article, array $errors): Response
    {
        // Seletor de autor: aprovados + o autor atual (mesmo suspenso)
        $authors = $this->authors->findApproved();
        if ($article->getAuthor() && !in_array($article->getAuthor(), $authors, true)) {
            $authors[] = $article->getAuthor();
        }

        return $this->render($template, [
            'article'    => $article,
            'errors'     => $errors,
            'categories' => $this->categories->findRootCategories(),
            'authors'    => $authors,
        ], new Response(status: $errors ? 422 : 200));
    }
}
