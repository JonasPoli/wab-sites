<?php

namespace App\Controller\pub;

use App\Entity\ContactMessage;
use App\Entity\NewsletterSubscriber;
use App\Repository\CategoryRepository;
use App\Repository\HeroBannerRepository;
use App\Repository\NewsletterSubscriberRepository;
use App\Repository\PageRepository;
use App\Service\TenantContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class NepePublicController extends AbstractController
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    private function theme(string $template): string
    {
        $tenant = $this->tenantContext->getTenant();
        $theme  = $tenant?->getTheme() ?? 'moderno';
        return "themes/{$theme}/{$template}";
    }

    // ── Home ──────────────────────────────────────────────────────────────────

    #[Route('/', name: 'pub_home')]
    public function home(
        HeroBannerRepository $banners,
        PageRepository $pages,
    ): Response {
        $tenant = $this->tenantContext->getTenant();

        // Home page configurável via tenant.homePage
        if ($tenant?->getHomePage()) {
            $page = $tenant->getHomePage();
            return $this->render($this->theme('page.html.twig'), ['page' => $page]);
        }

        $activeBanners = $banners->findActiveAll();
        return $this->render($this->theme('home.html.twig'), [
            'banner'  => $activeBanners[0] ?? null,
            'banners' => $activeBanners,
        ]);
    }

    // ── Página pública ────────────────────────────────────────────────────────

    #[Route('/p/{slug}', name: 'pub_page_show')]
    public function pageShow(string $slug, PageRepository $repo): Response
    {
        $page = $repo->findOneBy(['slug' => $slug]) ?? throw $this->createNotFoundException('Página não encontrada.');
        return $this->render($this->theme('page.html.twig'), ['page' => $page]);
    }

    // ── Categoria pública ─────────────────────────────────────────────────────

    #[Route('/categoria/{slug}', name: 'pub_category')]
    public function category(string $slug, CategoryRepository $cats): Response
    {
        $cat = $cats->findOneBy(['slug' => $slug]) ?? throw $this->createNotFoundException();
        // Renderiza como page se tiver seções, senão lista de páginas
        if ($cat->getSections()->count() > 0) {
            // Use category sections in page-like template
            return $this->render($this->theme('category.html.twig'), ['category' => $cat]);
        }
        return $this->render($this->theme('category.html.twig'), ['category' => $cat]);
    }

    // ── Newsletter ────────────────────────────────────────────────────────────

    #[Route('/newsletter/subscribe', name: 'pub_newsletter_subscribe', methods: ['POST'])]
    public function newsletterSubscribe(
        Request $request,
        EntityManagerInterface $em,
        NewsletterSubscriberRepository $repo,
    ): Response {
        $email = trim((string) $request->request->get('email'));

        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL) && !$repo->emailExists($email)) {
            $sub = new NewsletterSubscriber();
            $sub->setTenant($this->tenantContext->requireTenant());
            $sub->setEmail($email);
            $em->persist($sub);
            $em->flush();
            $this->addFlash('success', 'Inscrição realizada com sucesso!');
        } else {
            $this->addFlash('error', 'E-mail inválido ou já inscrito.');
        }

        return $this->redirect($request->headers->get('referer') ?: '/');
    }

    // ── Contato ───────────────────────────────────────────────────────────────

    #[Route('/contato', name: 'pub_contact', methods: ['GET'])]
    public function contactGet(): Response
    {
        return $this->redirectToRoute('pub_home', ['_fragment' => 'contato']);
    }

    #[Route('/contato', name: 'pub_contact_post', methods: ['POST'])]
    public function contactPost(Request $request, EntityManagerInterface $em): Response
    {
        $redirect = $this->redirectToRoute('pub_home', ['_fragment' => 'contato']);

        if (!$this->isCsrfTokenValid('contact_form', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Sessão expirada. Atualize a página e tente novamente.');
            return $redirect;
        }

        $name    = trim((string) $request->request->get('name'));
        $email   = trim((string) $request->request->get('email'));
        $message = trim((string) $request->request->get('message'));

        if (!$name || !$email || !$message || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('error', 'Preencha todos os campos corretamente.');
            return $redirect;
        }

        $contact = new ContactMessage();
        $contact->setTenant($this->tenantContext->requireTenant());
        $contact->setSenderName($name);
        $contact->setSenderEmail($email);
        $contact->setMessage($message);
        $em->persist($contact);
        $em->flush();

        $this->addFlash('success', 'Mensagem enviada com sucesso!');
        return $redirect;
    }
}
