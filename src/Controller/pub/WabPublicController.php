<?php

namespace App\Controller\pub;

use App\Entity\ContactMessage;
use App\Repository\ArticleRepository;
use App\Repository\AuthorRepository;
use App\Entity\NewsletterSubscriber;
use App\Repository\CategoryRepository;
use App\Repository\HeroBannerRepository;
use App\Repository\NewsletterSubscriberRepository;
use App\Repository\PageRepository;
use App\Repository\PageBlockTeamMemberRepository;
use App\Service\RecaptchaVerifier;
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
use Twig\Environment;

class WabPublicController extends AbstractController
{
    /** Robôs e pré-visualizações de link não contam visualização de notícia. */
    private const BOT_USER_AGENT = '~bot|crawl|spider|slurp|facebookexternalhit|whatsapp|preview~i';

    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly LoggerInterface $logger,
        private readonly Environment $twig,
    ) {}

    private function theme(string $template): string
    {
        $tenant = $this->tenantContext->getTenant();
        $theme  = $tenant?->getTheme() ?? 'moderno';
        return "themes/{$theme}/{$template}";
    }

    /** Renderiza o template do tema ou responde 404 quando o tema não o tem. */
    private function renderThemeOr404(string $template, array $parameters = []): Response
    {
        $path = $this->theme($template);
        if (!$this->twig->getLoader()->exists($path)) {
            throw $this->createNotFoundException('Página não encontrada.');
        }
        return $this->render($path, $parameters);
    }

    private function isNewsTheme(): bool
    {
        return $this->tenantContext->getTenant()?->getTheme() === 'noticias';
    }

    /** Página pedida em ?pagina=N; 404 fora do intervalo. */
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

    // ── Home ──────────────────────────────────────────────────────────────────

    #[Route('/', name: 'pub_home')]
    public function home(
        HeroBannerRepository $banners,
        PageRepository $pages,
        ArticleRepository $articles,
    ): Response {
        $tenant = $this->tenantContext->getTenant();

        // Home page configurável via tenant.homePage
        if ($tenant?->getHomePage()) {
            $page = $tenant->getHomePage();
            return $this->render($this->theme('page.html.twig'), ['page' => $page]);
        }

        // Portal de notícias: as 20 notícias visíveis mais recentes
        if ($this->isNewsTheme()) {
            return $this->render($this->theme('home.html.twig'), [
                'articles' => $articles->findVisiblePage(null, 1, ArticleRepository::PER_PAGE),
                'hasMore'  => $articles->countVisible() > ArticleRepository::PER_PAGE,
            ]);
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
        // Só membro de bloco de página ou categoria do site do domínio
        $member = $repo->findBySlug($slug, $this->tenantContext->requireTenant());
        if (!$member) {
            throw $this->createNotFoundException('Perfil não encontrado.');
        }

        $layout = $member->getDetailLayout() ?: 'classic';
        $template = "team_member_{$layout}.html.twig";

        return $this->render($this->theme($template), ['member' => $member]);
    }

    // ── Categoria pública ─────────────────────────────────────────────────────

    #[Route('/categoria/{slug}', name: 'pub_category')]
    public function category(string $slug, CategoryRepository $cats, Request $request, ArticleRepository $articles): Response
    {
        $cat = $cats->findOneBy(['slug' => $slug]) ?? throw $this->createNotFoundException();

        // Portal de notícias: notícias da editoria (principal ou outras) e das subcategorias
        if ($this->isNewsTheme()) {
            [$page, $pages] = $this->currentPage($request, $articles->countVisible($cat), ArticleRepository::PER_PAGE);
            return $this->render($this->theme('category.html.twig'), [
                'category' => $cat,
                'articles' => $articles->findVisiblePage($cat, $page),
                'page'     => $page,
                'pages'    => $pages,
            ]);
        }
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
        $tenant = $this->tenantContext->requireTenant();

        if (!$tenant->isNewsletterEnabled()) {
            $this->addFlash('error', 'Newsletter não está disponível neste momento.');
            return $this->redirect($request->headers->get('referer') ?: '/');
        }

        $email = trim((string) $request->request->get('email'));

        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL) && !$repo->emailExists($email)) {
            $sub = new NewsletterSubscriber();
            $sub->setTenant($tenant);
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
        RecaptchaVerifier $recaptcha,
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

        // reCAPTCHA v3 com as chaves do site do domínio (recaptcha-no-contato)
        if ($tenant->hasRecaptcha()) {
            $result = $recaptcha->verify($tenant, (string) $request->request->get('g-recaptcha-response'), 'contato', $request->getClientIp());
            if ($result === RecaptchaVerifier::REJECTED) {
                $this->addFlash('error', 'Não foi possível confirmar o envio. Tente de novo em alguns segundos.');
                return $redirect;
            }
            // UNAVAILABLE: o Google não respondeu direito; o contato segue como antes (o serviço já registrou no log)
        } else {
            $this->logger->warning('reCAPTCHA: contato aceito sem conferência, site sem chave.', ['domain' => $tenant->getDomain()]);
        }

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
    public function articles(Request $request, ArticleRepository $articles): Response
    {
        if (!$this->isNewsTheme()) {
            return $this->renderThemeOr404('articles.html.twig', ['articles' => []]);
        }

        [$page, $pages] = $this->currentPage($request, $articles->countVisible(), ArticleRepository::PER_PAGE);
        return $this->render($this->theme('articles.html.twig'), [
            'articles' => $articles->findVisiblePage(null, $page),
            'page'     => $page,
            'pages'    => $pages,
        ]);
    }

    /** Busca do portal (tema noticias): notícias visíveis e páginas pelo título. */
    #[Route('/busca', name: 'pub_search', methods: ['GET'])]
    public function search(Request $request, ArticleRepository $articles, PageRepository $pageRepository): Response
    {
        if (!$this->isNewsTheme()) {
            throw $this->createNotFoundException('Página não encontrada.');
        }

        $term = trim(mb_substr((string) $request->query->get('q', ''), 0, 100));
        $words = array_values(array_filter(
            preg_split('/\s+/u', $term) ?: [],
            fn (string $w) => mb_strlen($w) >= 2,
        ));
        $words = array_slice($words, 0, 5);

        $total = $words ? $articles->countSearchVisible($words) : 0;
        [$page, $pages] = $this->currentPage($request, $total, ArticleRepository::PER_PAGE);

        return $this->render($this->theme('search.html.twig'), [
            'term'     => $term,
            'searched' => $words !== [],
            'total'    => $total,
            'articles' => $words ? $articles->searchVisible($words, $page) : [],
            'pages_found' => $words ? $pageRepository->searchByTitle($words) : [],
            'page'     => $page,
            'pages'    => $pages,
        ]);
    }

    #[Route('/noticias/{slug}', name: 'pub_article_show')]
    public function articleShow(string $slug, Request $request, ArticleRepository $articles): Response
    {
        if (!$this->isNewsTheme()) {
            throw $this->createNotFoundException('Notícia não encontrada.');
        }

        // Só notícia publicada, com data no passado, do site do domínio (filtro de tenant)
        $article = $articles->findOneVisibleBySlug($slug) ?? throw $this->createNotFoundException('Notícia não encontrada.');

        $userAgent = trim((string) $request->headers->get('User-Agent'));
        if ($userAgent !== '' && !preg_match(self::BOT_USER_AGENT, $userAgent)) {
            $articles->incrementViews($article);
        }

        return $this->render($this->theme('article.html.twig'), [
            'article' => $article,
            'next'    => $articles->findNextOlder($article),
        ]);
    }

    // ── Autor (jornalista) ───────────────────────────────────────────────────

    #[Route('/autor/{slug}', name: 'pub_author')]
    public function author(string $slug, Request $request, AuthorRepository $authors, ArticleRepository $articles): Response
    {
        if (!$this->isNewsTheme()) {
            throw $this->createNotFoundException('Autor não encontrado.');
        }

        // Só autor aprovado do site do domínio
        $author = $authors->findOneApprovedBySlug($slug) ?? throw $this->createNotFoundException('Autor não encontrado.');
        [$page, $pages] = $this->currentPage($request, $articles->countVisibleByAuthor($author), ArticleRepository::PER_PAGE);

        return $this->render($this->theme('author.html.twig'), [
            'author'   => $author,
            'articles' => $articles->findVisibleByAuthor($author, $page),
            'page'     => $page,
            'pages'    => $pages,
        ]);
    }

    // ── Vídeos ───────────────────────────────────────────────────────────────

    #[Route('/videos', name: 'pub_videos')]
    public function videos(): Response
    {
        return $this->renderThemeOr404('videos.html.twig', [
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
        return $this->renderThemeOr404('studies.html.twig', [
            'studies' => [],
        ]);
    }

    #[Route('/estudos/{slug}', name: 'pub_study_show')]
    public function studyShow(string $slug): Response
    {
        throw $this->createNotFoundException('Estudo não encontrado.');
    }
}
