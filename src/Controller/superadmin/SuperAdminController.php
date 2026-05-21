<?php

namespace App\Controller\superadmin;

use App\Entity\Tenant;
use App\Entity\User;
use App\Repository\TenantRepository;
use App\Repository\PageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SUPER_ADMIN')]
#[Route('/superadmin', name: 'superadmin_')]
class SuperAdminController extends AbstractController
{
    #[Route('', name: 'dash')]
    public function dashboard(TenantRepository $tenants, UserRepository $users): Response
    {
        return $this->render('superadmin/dashboard.html.twig', [
            'tenants' => $tenants->findAll(),
            'users'   => $users->findAll(),
        ]);
    }

    // ── Tenant CRUD ─────────────────────────────────────────────────────────

    #[Route('/tenant/new', name: 'tenant_new', methods: ['GET', 'POST'])]
    public function tenantNew(Request $request, EntityManagerInterface $em, PageRepository $pageRepo): Response
    {
        $tenant = new Tenant();
        if ($request->isMethod('POST')) {
            $this->populateTenantFromRequest($tenant, $request, $pageRepo);
            $em->persist($tenant);
            $em->flush();
            $this->addFlash('success', 'Tenant criado com sucesso.');
            return $this->redirectToRoute('superadmin_dash');
        }
        return $this->render('superadmin/tenant/new.html.twig', ['tenant' => $tenant]);
    }

    #[Route('/tenant/{id}/edit', name: 'tenant_edit', methods: ['GET', 'POST'])]
    public function tenantEdit(Tenant $tenant, Request $request, EntityManagerInterface $em, PageRepository $pageRepo): Response
    {
        if ($request->isMethod('POST')) {
            $this->populateTenantFromRequest($tenant, $request, $pageRepo);

            /** @var UploadedFile|null $logoFile */
            $logoFile = $request->files->get('logoFile');
            if ($logoFile instanceof UploadedFile && $logoFile->isValid()) {
                $tenant->setLogoFile($logoFile);
            }

            /** @var UploadedFile|null $darkLogoFile */
            $darkLogoFile = $request->files->get('darkLogoFile');
            if ($darkLogoFile instanceof UploadedFile && $darkLogoFile->isValid()) {
                $tenant->setDarkLogoFile($darkLogoFile);
            }

            /** @var UploadedFile|null $faviconFile */
            $faviconFile = $request->files->get('faviconFile');
            if ($faviconFile instanceof UploadedFile && $faviconFile->isValid()) {
                $tenant->setFaviconFile($faviconFile);
            }

            $em->flush();
            $this->addFlash('success', 'Tenant atualizado.');
            return $this->redirectToRoute('superadmin_dash');
        }

        $pages = $pageRepo->findBy(['tenant' => $tenant], ['position' => 'ASC', 'title' => 'ASC']);

        return $this->render('superadmin/tenant/edit.html.twig', [
            'tenant' => $tenant,
            'pages'  => $pages,
        ]);
    }

    #[Route('/tenant/{id}/delete', name: 'tenant_delete', methods: ['POST'])]
    public function tenantDelete(Tenant $tenant, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('del_tenant_' . $tenant->getId(), (string) $request->request->get('_token'))) {
            return $this->redirectToRoute('superadmin_dash');
        }

        $confirmName = $request->request->get('confirm_name');
        if ($confirmName !== $tenant->getName()) {
            $this->addFlash('error', 'A exclusão foi cancelada: o nome informado do tenant não confere.');
            return $this->redirectToRoute('superadmin_dash');
        }

        $projectDir = $this->getParameter('kernel.project_dir');

        // 1. Gather all related records first
        $users = $em->getRepository(\App\Entity\User::class)->findBy(['tenant' => $tenant]);
        $banners = $em->getRepository(\App\Entity\HeroBanner::class)->findBy(['tenant' => $tenant]);
        $lines = $em->getRepository(\App\Entity\ResearchLine::class)->findBy(['tenant' => $tenant]);
        $fields = $em->getRepository(\App\Entity\ContactFormField::class)->findBy(['tenant' => $tenant]);
        $categories = $em->getRepository(\App\Entity\Category::class)->findBy(['tenant' => $tenant]);
        $pages = $em->getRepository(\App\Entity\Page::class)->findBy(['tenant' => $tenant]);

        // Gather sections
        $sections = [];
        if (!empty($pages) || !empty($categories)) {
            $qb = $em->createQueryBuilder()
                ->select('s')
                ->from(\App\Entity\PageSection::class, 's')
                ->leftJoin('s.page', 'p')
                ->leftJoin('s.category', 'c')
                ->where('p.tenant = :tenant OR c.tenant = :tenant')
                ->setParameter('tenant', $tenant);
            $sections = $qb->getQuery()->getResult();
        }

        // Gather blocks
        $blocks = [];
        if (!empty($sections)) {
            $blocks = $em->createQueryBuilder()
                ->select('b')
                ->from(\App\Entity\PageBlock::class, 'b')
                ->where('b.section IN (:sections)')
                ->setParameter('sections', $sections)
                ->getQuery()
                ->getResult();
        }

        // Gather sub-block entities
        $blockImages = [];
        $testimonials = [];
        $partnerLogos = [];
        $teamMembers = [];

        if (!empty($blocks)) {
            $blockImages = $em->createQueryBuilder()
                ->select('i')
                ->from(\App\Entity\PageBlockImage::class, 'i')
                ->where('i.block IN (:blocks)')
                ->setParameter('blocks', $blocks)
                ->getQuery()
                ->getResult();

            $testimonials = $em->createQueryBuilder()
                ->select('t')
                ->from(\App\Entity\PageBlockTestimonial::class, 't')
                ->where('t.block IN (:blocks)')
                ->setParameter('blocks', $blocks)
                ->getQuery()
                ->getResult();

            $partnerLogos = $em->createQueryBuilder()
                ->select('pl')
                ->from(\App\Entity\PageBlockPartnerLogo::class, 'pl')
                ->where('pl.block IN (:blocks)')
                ->setParameter('blocks', $blocks)
                ->getQuery()
                ->getResult();

            $teamMembers = $em->createQueryBuilder()
                ->select('tm')
                ->from(\App\Entity\PageBlockTeamMember::class, 'tm')
                ->where('tm.block IN (:blocks)')
                ->setParameter('blocks', $blocks)
                ->getQuery()
                ->getResult();
        }

        // 2. Collect and delete all physical media files (exclusão das imagens primeiro)
        $filesDeleted = 0;

        // Tenant branding assets
        if ($this->deletePhysicalFile($projectDir, 'tenant_logo', $tenant->getLogo())) $filesDeleted++;
        if ($this->deletePhysicalFile($projectDir, 'tenant_dark_logo', $tenant->getDarkLogo())) $filesDeleted++;
        if ($this->deletePhysicalFile($projectDir, 'tenant_favicon', $tenant->getFavicon())) $filesDeleted++;
        if ($this->deletePhysicalFile($projectDir, 'tenant_about_image', $tenant->getAboutImage())) $filesDeleted++;

        // Banner assets
        foreach ($banners as $b) {
            if ($this->deletePhysicalFile($projectDir, 'hero_banner', $b->getBackgroundImage())) $filesDeleted++;
        }

        // Page assets
        foreach ($pages as $p) {
            if ($this->deletePhysicalFile($projectDir, 'page_cover_image', $p->getCoverImage())) $filesDeleted++;
        }

        // Section assets
        foreach ($sections as $s) {
            if ($this->deletePhysicalFile($projectDir, 'section_bg_image', $s->getBgImage())) $filesDeleted++;
            if ($this->deletePhysicalFile($projectDir, 'section_bg_video', $s->getBgVideo())) $filesDeleted++;
        }

        // Block assets
        foreach ($blocks as $bl) {
            if ($this->deletePhysicalFile($projectDir, 'page_block_image', $bl->getImage())) $filesDeleted++;
        }

        // Sub-block assets
        foreach ($blockImages as $bi) {
            if ($this->deletePhysicalFile($projectDir, 'page_block_gallery', $bi->getFilename())) $filesDeleted++;
        }
        foreach ($testimonials as $te) {
            if ($this->deletePhysicalFile($projectDir, 'testimonial_avatar', $te->getAvatar())) $filesDeleted++;
        }
        foreach ($partnerLogos as $pl) {
            if ($this->deletePhysicalFile($projectDir, 'partner_logo', $pl->getLogoFilename())) $filesDeleted++;
        }
        foreach ($teamMembers as $tm) {
            if ($this->deletePhysicalFile($projectDir, 'team_member_image', $tm->getImage())) $filesDeleted++;
        }

        // 3. Sequenced Database Deletion inside a transaction
        $em->beginTransaction();
        try {
            // Nullify homepage reference to prevent FK errors
            $tenant->setHomePage(null);
            $em->flush();

            // Nullify category parent relationships to avoid parent-child locks
            foreach ($categories as $cat) {
                $cat->setParent(null);
            }
            $em->flush();

            // A. Remover sub-elementos dos blocos
            $subEntitiesCount = 0;
            foreach ($blockImages as $bi) {
                $em->remove($bi);
                $subEntitiesCount++;
            }
            foreach ($testimonials as $te) {
                $em->remove($te);
                $subEntitiesCount++;
            }
            foreach ($partnerLogos as $pl) {
                $em->remove($pl);
                $subEntitiesCount++;
            }
            foreach ($teamMembers as $tm) {
                $em->remove($tm);
                $subEntitiesCount++;
            }
            $em->flush();

            // B. Remover blocos
            $blocksCount = 0;
            foreach ($blocks as $bl) {
                $em->remove($bl);
                $blocksCount++;
            }
            $em->flush();

            // C. Remover sessões
            $sectionsCount = 0;
            foreach ($sections as $s) {
                $em->remove($s);
                $sectionsCount++;
            }
            $em->flush();

            // D. Remover páginas
            $pagesCount = 0;
            foreach ($pages as $p) {
                $em->remove($p);
                $pagesCount++;
            }
            $em->flush();

            // E. Remover categorias
            $categoriesCount = 0;
            foreach ($categories as $cat) {
                $em->remove($cat);
                $categoriesCount++;
            }
            $em->flush();

            // F. Remover banners, linhas de pesquisa e campos do formulário
            $bannersCount = 0;
            foreach ($banners as $b) {
                $em->remove($b);
                $bannersCount++;
            }
            $researchLinesCount = 0;
            foreach ($lines as $l) {
                $em->remove($l);
                $researchLinesCount++;
            }
            $fieldsCount = 0;
            foreach ($fields as $f) {
                $em->remove($f);
                $fieldsCount++;
            }
            $em->flush();

            // G. Remover usuários
            $usersCount = 0;
            foreach ($users as $u) {
                $em->remove($u);
                $usersCount++;
            }
            $em->flush();

            // H. Remover o Tenant
            $tenantName = $tenant->getName();
            $em->remove($tenant);
            $em->flush();

            $em->commit();

            // Rich summary formatting
            $summaryMsg = sprintf(
                'O site do tenant "%s" foi excluído de forma limpa e segura! Resumo da remoção: ' .
                '%d usuários, %d páginas, %d categorias, %d seções, %d blocos, %d sub-elementos de blocos (depoimentos, galeria, membros, parceiros), ' .
                '%d banners, %d linhas de pesquisa, %d campos de formulário e %d arquivos de mídia físicos apagados do disco.',
                $tenantName,
                $usersCount,
                $pagesCount,
                $categoriesCount,
                $sectionsCount,
                $blocksCount,
                $subEntitiesCount,
                $bannersCount,
                $researchLinesCount,
                $fieldsCount,
                $filesDeleted
            );

            $this->addFlash('success', $summaryMsg);
        } catch (\Exception $e) {
            $em->rollback();
            $this->addFlash('error', 'Ocorreu um erro ao excluir o tenant e seus dados foram preservados: ' . $e->getMessage());
        }

        return $this->redirectToRoute('superadmin_dash');
    }

    private function deletePhysicalFile(string $projectDir, string $mapping, ?string $filename): bool
    {
        if (empty($filename)) {
            return false;
        }

        $map = [
            'tenant_logo' => 'tenant/logo',
            'tenant_dark_logo' => 'tenant/dark_logo',
            'tenant_about_image' => 'tenant/about',
            'tenant_favicon' => 'tenant/favicon',
            'page_cover_image' => 'page_cover',
            'section_bg_image' => 'section/bg',
            'section_bg_video' => 'section/video',
            'page_block_image' => 'page_block',
            'page_block_gallery' => 'page_block_gallery',
            'testimonial_avatar' => 'testimonial_avatar',
            'partner_logo' => 'partner_logo',
            'team_member_image' => 'team_member_image',
            'hero_banner' => 'hero',
        ];

        $folder = $map[$mapping] ?? $mapping;
        $path = sprintf('%s/public/uploads/%s/%s', $projectDir, $folder, $filename);

        if (file_exists($path) && is_file($path)) {
            return @unlink($path);
        }

        return false;
    }

    #[Route('/tenant/{id}/export', name: 'tenant_export', methods: ['GET'])]
    public function tenantExport(Tenant $tenant, \App\Service\TenantExporter $exporter, \Psr\Log\LoggerInterface $logger): Response
    {
        $logger->info('[SuperAdminController] Ação de exportação iniciada para o tenant.', [
            'tenant_id' => $tenant->getId(),
            'domain' => $tenant->getDomain()
        ]);
        try {
            $zipPath = $exporter->export($tenant);
            $response = new \Symfony\Component\HttpFoundation\BinaryFileResponse($zipPath);
            $response->setContentDisposition(
                \Symfony\Component\HttpFoundation\ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                sprintf('tenant_export_%s_%s.zip', preg_replace('/[^a-zA-Z0-9_\-]/', '_', $tenant->getDomain()), date('Ymd_His'))
            );
            $response->deleteFileAfterSend(true);
            return $response;
        } catch (\Exception $e) {
            $logger->error('[SuperAdminController] Falha ao exportar tenant.', [
                'tenant_id' => $tenant->getId(),
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->addFlash('error', 'Erro ao exportar tenant: ' . $e->getMessage());
            return $this->redirectToRoute('superadmin_dash');
        }
    }

    #[Route('/tenant/import', name: 'tenant_import', methods: ['GET', 'POST'])]
    public function tenantImport(Request $request, \App\Service\TenantImporter $importer, \Psr\Log\LoggerInterface $logger): Response
    {
        // 1. Check if this is the final resolution submit
        if ($request->isMethod('POST') && $request->request->has('zipPath')) {
            $zipPath = (string) $request->request->get('zipPath');
            $logger->info('[SuperAdminController] Submissão de resolução de conflito de importação.', [
                'zip_path' => $zipPath
            ]);

            if (!file_exists($zipPath)) {
                $logger->error('[SuperAdminController] Arquivo temporário de importação não encontrado para resolução.', [
                    'zip_path' => $zipPath
                ]);
                $this->addFlash('error', 'Arquivo temporário de importação não encontrado. Por favor, faça o upload novamente.');
                return $this->redirectToRoute('superadmin_tenant_import');
            }

            try {
                $analysis = $importer->analyze($zipPath);
                $metadata = $analysis['metadata'];

                // Read resolutions
                $resolutions = [
                    'domain' => $request->request->get('domain'),
                    'tenant_name' => $request->request->get('tenant_name'),
                    'users' => [],
                ];

                foreach ($metadata['users'] as $u) {
                    $originalUsername = $u['username'];
                    $resolutions['users'][$originalUsername] = [
                        'username' => $request->request->get('user_username_' . str_replace('.', '_', $originalUsername)),
                        'email' => $request->request->get('user_email_' . str_replace('.', '_', $originalUsername)),
                    ];
                }

                $logger->info('[SuperAdminController] Rodando importador com resoluções configuradas.', [
                    'domain_res' => $resolutions['domain'],
                    'name_res' => $resolutions['tenant_name'],
                    'users_res_count' => count($resolutions['users'])
                ]);

                $importer->import($metadata, $resolutions, $zipPath);
                
                // Clean up ZIP upload
                @unlink($zipPath);

                $this->addFlash('success', 'Tenant importado com sucesso!');
                return $this->redirectToRoute('superadmin_dash');
            } catch (\Exception $e) {
                $logger->error('[SuperAdminController] Falha na execução da importação final.', [
                    'zip_path' => $zipPath,
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $this->addFlash('error', 'Falha ao importar tenant: ' . $e->getMessage());
                return $this->render('superadmin/tenant/import.html.twig', [
                    'metadata' => $metadata ?? null,
                    'conflicts' => $analysis['conflicts'] ?? null,
                    'zipPath' => $zipPath,
                    'has_conflicts' => true,
                ]);
            }
        }

        // 2. Check if a new file is uploaded
        if ($request->isMethod('POST') && $request->files->has('zipFile')) {
            /** @var UploadedFile $file */
            $file = $request->files->get('zipFile');
            $logger->info('[SuperAdminController] Novo upload de arquivo ZIP para importação de tenant recebido.', [
                'client_original_name' => $file?->getClientOriginalName(),
                'client_size' => $file?->getSize(),
                'is_valid' => $file?->isValid()
            ]);

            if (!$file || !$file->isValid()) {
                $logger->error('[SuperAdminController] Erro no upload: Arquivo inválido ou não enviado.', [
                    'error_code' => $file?->getError()
                ]);
                $this->addFlash('error', 'Arquivo inválido ou não enviado.');
                return $this->redirectToRoute('superadmin_tenant_import');
            }

            try {
                // Save ZIP to a persistent temp folder inside the workspace
                $tempUploadDir = $this->getParameter('kernel.project_dir') . '/var/tmp';
                if (!is_dir($tempUploadDir)) {
                    mkdir($tempUploadDir, 0755, true);
                }
                $tempZipPath = $tempUploadDir . '/upload_' . uniqid() . '.zip';
                $file->move($tempUploadDir, basename($tempZipPath));

                $logger->info('[SuperAdminController] Arquivo ZIP movido para pasta temporária de upload.', [
                    'temp_zip_path' => $tempZipPath
                ]);

                $analysis = $importer->analyze($tempZipPath);

                // If no conflicts are present, import immediately!
                if (!$analysis['has_conflicts']) {
                    $logger->info('[SuperAdminController] Nenhum conflito relacional ou de domínio detectado. Executando importação imediata.');
                    $importer->import($analysis['metadata'], [], $tempZipPath);
                    @unlink($tempZipPath);
                    $this->addFlash('success', 'Tenant importado com sucesso!');
                    return $this->redirectToRoute('superadmin_dash');
                }

                // If there are conflicts, render the wizard
                $logger->info('[SuperAdminController] Conflitos detectados. Renderizando assistente de resolução para o SuperAdmin.', [
                    'conflicts' => $analysis['conflicts']
                ]);

                return $this->render('superadmin/tenant/import.html.twig', [
                    'metadata' => $analysis['metadata'],
                    'conflicts' => $analysis['conflicts'],
                    'zipPath' => $tempZipPath,
                    'has_conflicts' => true,
                ]);
            } catch (\Exception $e) {
                $logger->error('[SuperAdminController] Erro inesperado ao analisar pacote ZIP.', [
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $this->addFlash('error', 'Erro ao analisar pacote: ' . $e->getMessage());
                return $this->redirectToRoute('superadmin_tenant_import');
            }
        }

        return $this->render('superadmin/tenant/import.html.twig', [
            'has_conflicts' => false,
        ]);
    }

    // ── User (Admin) CRUD ────────────────────────────────────────────────────

    #[Route('/user/new', name: 'user_new', methods: ['GET', 'POST'])]
    public function userNew(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        TenantRepository $tenants,
    ): Response {
        $user = new User();
        if ($request->isMethod('POST')) {
            $this->populateUserFromRequest($user, $request, $em, $hasher, $tenants);
            $em->persist($user);
            $em->flush();
            $this->addFlash('success', 'Usuário criado.');
            return $this->redirectToRoute('superadmin_dash');
        }
        return $this->render('superadmin/user/new.html.twig', [
            'user'    => $user,
            'tenants' => $tenants->findAll(),
        ]);
    }

    #[Route('/user/{id}/edit', name: 'user_edit', methods: ['GET', 'POST'])]
    public function userEdit(
        User $user,
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        TenantRepository $tenants,
    ): Response {
        if ($request->isMethod('POST')) {
            $this->populateUserFromRequest($user, $request, $em, $hasher, $tenants);
            $em->flush();
            $this->addFlash('success', 'Usuário atualizado.');
            return $this->redirectToRoute('superadmin_dash');
        }
        return $this->render('superadmin/user/edit.html.twig', [
            'user'    => $user,
            'tenants' => $tenants->findAll(),
        ]);
    }

    #[Route('/user/{id}/delete', name: 'user_delete', methods: ['POST'])]
    public function userDelete(User $user, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('del_user_' . $user->getId(), (string) $request->request->get('_token'))) {
            $em->remove($user);
            $em->flush();
            $this->addFlash('success', 'Usuário removido.');
        }
        return $this->redirectToRoute('superadmin_dash');
    }

    // ── Impersonation helper ─────────────────────────────────────────────────

    /**
     * Redirects to /admin with ?_switch_user=<username> to impersonate the
     * first admin of a given tenant. Symfony Security handles the rest.
     */
    #[Route('/impersonate/{id}', name: 'impersonate', methods: ['GET'])]
    public function impersonate(User $user): Response
    {
        return $this->redirect('/admin?_switch_user=' . urlencode((string) $user->getUsername()));
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function populateTenantFromRequest(Tenant $tenant, Request $r, PageRepository $pageRepo): void
    {
        $tenant->setDomain((string) $r->request->get('domain'));
        $tenant->setName((string) $r->request->get('name'));
        $tenant->setPrimaryColor((string) $r->request->get('primaryColor', '#0044cc'));
        $tenant->setSecondaryColor((string) $r->request->get('secondaryColor', '#ffaa00'));
        $tenant->setPrimaryColorDark((string) $r->request->get('primaryColorDark', '#3b82f6'));
        $tenant->setSecondaryColorDark((string) $r->request->get('secondaryColorDark', '#fbbf24'));
        $tenant->setTheme((string) $r->request->get('theme', 'wab'));
        $tenant->setShowSectionTitles($r->request->get('showSectionTitles') === '1');
        $tenant->setLandingPageMode($r->request->get('landingPageMode') === '1');

        // HomePage
        $homePageId = $r->request->get('homePageId');
        $homePage = $homePageId ? $pageRepo->find((int) $homePageId) : null;
        $tenant->setHomePage($homePage);

        // SEO
        $tenant->setSeoTitle($r->request->get('seoTitle') ?: null);
        $tenant->setSeoDescription($r->request->get('seoDescription') ?: null);
        $tenant->setSeoKeywords($r->request->get('seoKeywords') ?: null);
        $tenant->setOgImage($r->request->get('ogImage') ?: null);

        // Contact
        $tenant->setContactEmail($r->request->get('contactEmail') ?: null);
        $tenant->setPhone($r->request->get('phone') ?: null);
        $tenant->setAddress($r->request->get('address') ?: null);
        $tenant->setMapsEmbedUrl($r->request->get('mapsEmbedUrl') ?: null);

        // Social Networks
        $tenant->setYoutubeLink($r->request->get('youtubeLink') ?: null);
        $tenant->setInstagramLink($r->request->get('instagramLink') ?: null);
        $tenant->setFacebookLink($r->request->get('facebookLink') ?: null);
        $tenant->setWhatsappLink($r->request->get('whatsappLink') ?: null);
        $tenant->setLinkedinLink($r->request->get('linkedinLink') ?: null);

        // Menu and TopBar settings
        $showMenuIcons = (bool) $r->request->get('showMenuIcons');
        $topBarEnabled = (bool) $r->request->get('topBarEnabled');
        $topBarLeft = $r->request->all('topBarLeft');
        $topBarRight = $r->request->all('topBarRight');

        $tenant->setNavigationSettings([
            'showMenuIcons' => $showMenuIcons,
            'topBarEnabled' => $topBarEnabled,
            'topBarLeft'    => $topBarLeft,
            'topBarRight'   => $topBarRight,
        ]);

        $fontSettings = [];
        for ($i = 1; $i <= 5; $i++) {
            $fontSettings['h' . $i] = [
                'font'   => (string) $r->request->get('h' . $i . '_font', 'Outfit'),
                'size'   => (string) $r->request->get('h' . $i . '_size', ''),
                'weight' => (string) $r->request->get('h' . $i . '_weight', '400'),
            ];
        }
        $tenant->setFontSettings($fontSettings);
    }

    private function populateUserFromRequest(
        User $user,
        Request $r,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        TenantRepository $tenants,
    ): void {
        $user->setUsername((string) $r->request->get('username'));
        $user->setName((string) $r->request->get('name'));
        $user->setEmail($r->request->get('email') ?: null);
        $user->setWorkGroup((int) $r->request->get('workGroup', 0));

        $tenantId = $r->request->get('tenant');
        $user->setTenant($tenantId ? $tenants->find((int) $tenantId) : null);

        $plain = (string) $r->request->get('password');
        if ($plain !== '') {
            $user->setPassword($hasher->hashPassword($user, $plain));
        }
    }
}
