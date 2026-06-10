<?php

namespace App\Controller\pub;

use App\Entity\ContactMessage;
use App\Entity\NewsletterSubscriber;
use App\Repository\CategoryRepository;
use App\Repository\HeroBannerRepository;
use App\Repository\NewsletterSubscriberRepository;
use App\Repository\PageRepository;
use App\Repository\PageBlockTeamMemberRepository;
use App\Service\TenantContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Psr\Log\LoggerInterface;

class WabPublicController extends AbstractController
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly LoggerInterface $logger,
    ) {}

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

    // ── Membro da Equipe (perfil detalhado) ──────────────────────────────────

    #[Route('/membro/{slug}', name: 'pub_team_member')]
    public function teamMember(string $slug, PageBlockTeamMemberRepository $repo): Response
    {
        $member = $repo->findBySlug($slug);
        if (!$member) {
            throw $this->createNotFoundException('Perfil não encontrado.');
        }

        $layout = $member->getDetailLayout() ?: 'classic';
        $template = "team_member_{$layout}.html.twig";

        return $this->render($this->theme($template), ['member' => $member]);
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
    public function contactPost(
        Request $request,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        ParameterBagInterface $parameters,
    ): Response
    {
        $redirect = $this->redirectToRoute('pub_home', ['_fragment' => 'contato']);

        if (!$this->isCsrfTokenValid('contact_form', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Sessão expirada. Atualize a página e tente novamente.');
            return $redirect;
        }

        $name    = trim((string) $request->request->get('name'));
        $email   = trim((string) $request->request->get('email'));
        $message = trim((string) $request->request->get('message'));
        $phone   = trim((string) $request->request->get('phone'));

        if (!$name || !$email || !$message || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('error', 'Preencha todos os campos corretamente.');
            return $redirect;
        }

        $tenant = $this->tenantContext->requireTenant();

        $contact = new ContactMessage();
        $contact->setTenant($tenant);
        $contact->setSenderName($name);
        $contact->setSenderEmail($email);
        $contact->setMessage($message);
        $em->persist($contact);
        $em->flush();

        // Enviar e-mail de contato usando o Symfony Mailer integrado ao Wmailer
        try {
            $emailContactTo = $tenant->getContactEmail() ?: $parameters->get('emailContactTo');
            
            $context = [
                'name' => $name,
                'sender_email' => $email,
                'phone' => $phone ?: '—',
                'message' => $message,
                'submitted_at' => new \DateTimeImmutable('now'),
                'tenant' => $tenant,
            ];

            $emailMessage = (new TemplatedEmail())
                ->from(new Address($parameters->get('emailFrom'), $tenant->getName() ?? 'Site'))
                ->to($emailContactTo)
                ->subject(sprintf('[%s] Novo contato: %s', $tenant->getName() ?? 'Site', $name))
                ->htmlTemplate('email/contact.html.twig')
                ->context($context)
            ;

            $mailer->send($emailMessage);
        } catch (\Throwable $e) {
            $this->logger->error('Erro ao enviar e-mail de contato: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
        }

        $this->addFlash('success', 'Mensagem enviada com sucesso!');
        return $redirect;
    }

    // ── Notícias / Artigos ───────────────────────────────────────────────────

    #[Route('/noticias', name: 'pub_articles')]
    public function articles(): Response
    {
        return $this->render($this->theme('articles.html.twig'), [
            'articles' => [],
        ]);
    }

    #[Route('/noticias/{slug}', name: 'pub_article_show')]
    public function articleShow(string $slug): Response
    {
        throw $this->createNotFoundException('Notícia não encontrada.');
    }

    // ── Vídeos ───────────────────────────────────────────────────────────────

    #[Route('/videos', name: 'pub_videos')]
    public function videos(): Response
    {
        return $this->render($this->theme('videos.html.twig'), [
            'videos' => [],
        ]);
    }

    #[Route('/videos/{slug}', name: 'pub_video_show')]
    public function videoShow(string $slug): Response
    {
        throw $this->createNotFoundException('Vídeo não encontrado.');
    }

    // ── Estudos ──────────────────────────────────────────────────────────────

    #[Route('/estudos', name: 'pub_studies')]
    public function studies(): Response
    {
        return $this->render($this->theme('studies.html.twig'), [
            'studies' => [],
        ]);
    }

    #[Route('/estudos/{slug}', name: 'pub_study_show')]
    public function studyShow(string $slug): Response
    {
        throw $this->createNotFoundException('Estudo não encontrado.');
    }
}
