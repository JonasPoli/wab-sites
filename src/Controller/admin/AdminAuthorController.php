<?php

namespace App\Controller\admin;

use App\Entity\Author;
use App\Entity\Enum\AuthorStatus;
use App\Entity\User;
use App\Repository\ArticleRepository;
use App\Repository\AuthorRepository;
use App\Service\JournalistNotifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Vich\UploaderBundle\Handler\UploadHandler;

/**
 * Aprovação de jornalistas: só o admin do site (workGroup 0) ou o SuperAdmin.
 * Editor e revisor recebem 403. Author é TenantAware: {id} de outro site = 404.
 *
 * Transições: pendente → aprovado | recusado; aprovado → suspenso; suspenso → aprovado.
 * Excluir: só pendente ou recusado, junto com o usuário e a foto.
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/jornalistas', name: 'admin_author_')]
class AdminAuthorController extends AbstractController
{
    private const TRANSITIONS = [
        'aprovar'   => [[AuthorStatus::Pending, AuthorStatus::Suspended], AuthorStatus::Approved, 'Jornalista aprovado.'],
        'recusar'   => [[AuthorStatus::Pending], AuthorStatus::Refused, 'Cadastro recusado.'],
        'suspender' => [[AuthorStatus::Approved], AuthorStatus::Suspended, 'Jornalista suspenso.'],
    ];

    public function __construct(private readonly AuthorRepository $authors) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $r): Response
    {
        $this->denyUnlessSiteAdmin();
        $status = AuthorStatus::tryFrom((string) $r->query->get('situacao'));

        return $this->render('admin/author/index.html.twig', [
            'authors'  => $this->authors->findForAdmin($status),
            'status'   => $status,
            'statuses' => AuthorStatus::cases(),
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Author $author, ArticleRepository $articles): Response
    {
        $this->denyUnlessSiteAdmin();
        return $this->render('admin/author/show.html.twig', [
            'author'        => $author,
            'articlesCount' => $articles->countForAdmin($author),
        ]);
    }

    #[Route('/{id}/{action}', name: 'action', requirements: ['id' => '\d+', 'action' => 'aprovar|recusar|suspender'], methods: ['POST'])]
    public function action(Author $author, string $action, Request $r, EntityManagerInterface $em, JournalistNotifier $notifier): Response
    {
        $this->denyUnlessSiteAdmin();
        if (!$this->isCsrfTokenValid('author_' . $action . '_' . $author->getId(), (string) $r->request->get('_token'))) {
            $this->addFlash('error', 'Sessão expirada. Atualize a página e tente novamente.');
            return $this->redirectToRoute('admin_author_show', ['id' => $author->getId()]);
        }

        [$from, $to, $message] = self::TRANSITIONS[$action];
        if (!in_array($author->getStatus(), $from, true)) {
            $this->addFlash('error', sprintf('Não é possível %s um jornalista com situação "%s".', $action, $author->getStatus()->label()));
            return $this->redirectToRoute('admin_author_show', ['id' => $author->getId()]);
        }

        /** @var User $reviewer */
        $reviewer = $this->getUser();
        $previous = $author->getStatus();
        $author->setStatus($to)
            ->setReviewedAt(new \DateTimeImmutable())
            ->setReviewedBy($reviewer)
            ->setUpdatedAt(new \DateTimeImmutable());
        $em->flush();

        // E-mail ao jornalista; o link de login sai do domínio do site (o pedido do admin chega por ele)
        $sent = $notifier->statusChanged($author, $previous, $this->generateUrl('app_login', [], UrlGeneratorInterface::ABSOLUTE_URL));
        if ($sent) {
            $this->addFlash('success', $message . ' O jornalista recebeu um e-mail de aviso.');
        } else {
            $this->addFlash('warning', rtrim($message, '.') . ', mas o e-mail de aviso não pôde ser enviado.');
        }
        return $this->redirectToRoute('admin_author_show', ['id' => $author->getId()]);
    }

    #[Route('/{id}/excluir', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Author $author, Request $r, EntityManagerInterface $em, UploadHandler $uploads): Response
    {
        $this->denyUnlessSiteAdmin();
        if (!$this->isCsrfTokenValid('author_delete_' . $author->getId(), (string) $r->request->get('_token'))) {
            $this->addFlash('error', 'Sessão expirada. Atualize a página e tente novamente.');
            return $this->redirectToRoute('admin_author_show', ['id' => $author->getId()]);
        }
        if (!in_array($author->getStatus(), [AuthorStatus::Pending, AuthorStatus::Refused], true)) {
            $this->addFlash('error', 'Só é possível excluir candidatos pendentes ou recusados.');
            return $this->redirectToRoute('admin_author_show', ['id' => $author->getId()]);
        }

        $name = $author->getName();
        if ($author->getPhoto()) {
            $uploads->remove($author, 'photoFile');
        }
        $user = $author->getUser();
        $em->remove($author);
        if ($user) {
            $em->remove($user); // libera o e-mail para um novo cadastro
        }
        $em->flush();

        $this->addFlash('success', sprintf('Candidato "%s" excluído.', $name));
        return $this->redirectToRoute('admin_author_index');
    }

    /** Só o admin do site (workGroup 0, com tenant) ou o SuperAdmin; editor e revisor: 403. */
    private function denyUnlessSiteAdmin(): void
    {
        $user = $this->getUser();
        if (!$user instanceof User || !($user->isSuperAdmin() || ($user->isAdmin() && $user->getTenant() !== null))) {
            throw $this->createAccessDeniedException('Só o administrador do site aprova jornalistas.');
        }
    }
}
