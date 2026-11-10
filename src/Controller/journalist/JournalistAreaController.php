<?php

namespace App\Controller\journalist;

use App\Entity\Article;
use App\Entity\Author;
use App\Entity\User;
use App\Repository\ArticleRepository;
use App\Repository\AuthorRepository;
use App\Repository\CategoryRepository;
use App\Service\ArticleFormHandler;
use App\Service\TenantContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Área do jornalista aprovado: as próprias notícias e o perfil.
 *
 * Cada ação confere: usuário do tenant do domínio (o User não passa pelo filtro),
 * autor encontrado pelo repositório filtrado e aprovado (senão 403), e notícia
 * com author = o autor do usuário (senão 404).
 */
#[IsGranted('ROLE_JOURNALIST')]
#[Route('/jornalista')]
class JournalistAreaController extends AbstractController
{
    private const PER_PAGE = 50;

    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly AuthorRepository $authors,
        private readonly ArticleRepository $articles,
        private readonly CategoryRepository $categories,
        private readonly ArticleFormHandler $form,
    ) {}

    #[Route('', name: 'journalist_area', methods: ['GET'])]
    public function index(Request $r): Response
    {
        $author = $this->currentAuthor();
        $page = max(1, $r->query->getInt('pagina', 1));
        $total = $this->articles->countForAdmin($author);

        return $this->render('journalist/index.html.twig', [
            'author'   => $author,
            'articles' => $this->articles->findForAdmin($page, self::PER_PAGE, $author),
            'page'     => $page,
            'pages'    => max(1, (int) ceil($total / self::PER_PAGE)),
            'total'    => $total,
        ]);
    }

    #[Route('/noticias/new', name: 'journalist_article_new', methods: ['GET', 'POST'])]
    public function new(Request $r, EntityManagerInterface $em): Response
    {
        $author = $this->currentAuthor();
        $article = new Article();
        $errors = [];
        if ($r->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('article_form', (string) $r->request->get('_token'))) {
                $errors[] = 'Sessão expirada. Atualize a página e tente novamente.';
            } else {
                $article->setTenant($this->tenantContext->requireTenant());
                $errors = $this->form->apply($article, $r, staff: false, forceAuthor: $author);
                if (!$errors) {
                    $em->persist($article);
                    $em->flush();
                    $this->addFlash('success', 'Notícia criada.');
                    return $this->redirectToRoute('journalist_area');
                }
            }
        }
        return $this->renderForm('journalist/article_new.html.twig', $article, $errors);
    }

    #[Route('/noticias/{id}/edit', name: 'journalist_article_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(int $id, Request $r, EntityManagerInterface $em): Response
    {
        $author = $this->currentAuthor();
        $article = $this->ownArticle($id, $author);
        $errors = [];
        if ($r->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('article_form', (string) $r->request->get('_token'))) {
                $errors[] = 'Sessão expirada. Atualize a página e tente novamente.';
            } else {
                $errors = $this->form->apply($article, $r, staff: false, forceAuthor: $author);
                if (!$errors) {
                    $em->flush();
                    $this->addFlash('success', 'Notícia atualizada.');
                    return $this->redirectToRoute('journalist_area');
                }
            }
        }
        return $this->renderForm('journalist/article_edit.html.twig', $article, $errors);
    }

    #[Route('/noticias/{id}/delete', name: 'journalist_article_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(int $id, Request $r, EntityManagerInterface $em): Response
    {
        $article = $this->ownArticle($id, $this->currentAuthor());
        if ($this->isCsrfTokenValid('del_article_' . $article->getId(), (string) $r->request->get('_token'))) {
            $em->remove($article);
            $em->flush();
            $this->addFlash('success', 'Notícia excluída.');
        }
        return $this->redirectToRoute('journalist_area');
    }

    #[Route('/perfil', name: 'journalist_profile', methods: ['GET', 'POST'])]
    public function profile(Request $r, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $author = $this->currentAuthor();
        /** @var User $user */
        $user = $this->getUser();
        $errors = [];

        if ($r->isMethod('POST')) {
            $in = $r->request;
            if (!$this->isCsrfTokenValid('journalist_profile', (string) $in->get('_token'))) {
                $errors[] = 'Sessão expirada. Atualize a página e tente novamente.';
            } else {
                $name = trim((string) $in->get('name'));
                $bio = trim((string) $in->get('bio'));
                if ($name === '') {
                    $errors[] = 'Informe o seu nome.';
                }
                if (mb_strlen($bio) > Author::BIO_MAX_LENGTH) {
                    $errors[] = sprintf('A minibio pode ter até %d caracteres.', Author::BIO_MAX_LENGTH);
                }
                // Mesmas regras do cadastro: WhatsApp com DDD, endereço e estado obrigatórios
                $phone = trim((string) $in->get('phone'));
                $address = trim((string) $in->get('address'));
                $state = strtoupper(trim((string) $in->get('state')));
                $center = trim((string) $in->get('spiritistCenter'));
                if (!Author::isValidWhatsapp($phone)) {
                    $errors[] = 'Informe o WhatsApp com DDD.';
                }
                if ($address === '' || mb_strlen($address) > 255) {
                    $errors[] = 'Informe o endereço.';
                }
                if (!isset(Author::STATES[$state])) {
                    $errors[] = 'Escolha o estado.';
                }
                if (mb_strlen($center) > 150) {
                    $errors[] = 'O nome da casa espírita pode ter até 150 caracteres.';
                }

                // Troca de senha só com a senha atual
                $newPassword = (string) $in->get('newPassword');
                if ($newPassword !== '') {
                    if (!$hasher->isPasswordValid($user, (string) $in->get('currentPassword'))) {
                        $errors[] = 'Senha atual incorreta.';
                    } elseif (mb_strlen($newPassword) < 8) {
                        $errors[] = 'A nova senha precisa ter pelo menos 8 caracteres.';
                    } elseif ($newPassword !== (string) $in->get('newPasswordConfirm')) {
                        $errors[] = 'A confirmação da nova senha não confere.';
                    }
                }

                $photo = $r->files->get('photo');
                if ($photo instanceof UploadedFile && (!$photo->isValid() || !str_starts_with((string) $photo->getMimeType(), 'image/') || $photo->getSize() > 5 * 1024 * 1024)) {
                    $errors[] = 'A foto precisa ser uma imagem de até 5 MB.';
                    $photo = null;
                }

                if (!$errors) {
                    // E-mail, slug e situação não mudam por aqui
                    $author->setName($name)
                        ->setPhone($phone)
                        ->setAddress($address)
                        ->setState($state)
                        ->setSpiritistCenter($center !== '' ? $center : null)
                        ->setProfessionalRegistration($this->nullable($in->get('professionalRegistration')))
                        ->setOutlet($this->nullable($in->get('outlet')))
                        ->setCity($this->nullable($in->get('city')))
                        ->setBio($bio !== '' ? $bio : null)
                        ->setUpdatedAt(new \DateTimeImmutable());
                    if ($photo instanceof UploadedFile) {
                        $author->setPhotoFile($photo);
                    }
                    $user->setName($name);
                    if ($newPassword !== '') {
                        $user->setPassword($hasher->hashPassword($user, $newPassword));
                    }
                    $em->flush();
                    $this->addFlash('success', 'Perfil atualizado.');
                    return $this->redirectToRoute('journalist_profile');
                }
            }
        }

        return $this->render('journalist/profile.html.twig', [
            'author'  => $author,
            'errors'  => $errors,
            'bio_max' => Author::BIO_MAX_LENGTH,
            'states'  => Author::STATES,
        ], new Response(status: $errors ? 422 : 200));
    }

    /** Autor aprovado do usuário logado, no site do domínio; senão 403. */
    private function currentAuthor(): Author
    {
        $user = $this->getUser();
        $tenant = $this->tenantContext->getTenant();
        if (!$user instanceof User || !$tenant || $user->getTenant()?->getId() !== $tenant->getId()) {
            throw $this->createAccessDeniedException('Esta área não é deste site.');
        }

        $author = $this->authors->findOneByUser($user);
        if (!$author || !$author->isApproved()) {
            throw $this->createAccessDeniedException('Seu acesso de autor não está ativo.');
        }
        return $author;
    }

    /** Notícia do site (filtro) e do próprio autor; senão 404. */
    private function ownArticle(int $id, Author $author): Article
    {
        $article = $this->articles->find($id);
        if (!$article || $article->getAuthor()?->getId() !== $author->getId()) {
            throw $this->createNotFoundException('Notícia não encontrada.');
        }
        return $article;
    }

    /** @param string[] $errors */
    private function renderForm(string $template, Article $article, array $errors): Response
    {
        return $this->render($template, [
            'article'    => $article,
            'errors'     => $errors,
            'categories' => $this->categories->findRootCategories(),
            'authors'    => [],
        ], new Response(status: $errors ? 422 : 200));
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }
}
