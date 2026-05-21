<?php

namespace App\Service;

use App\Entity\Tenant;
use App\Entity\User;
use App\Entity\Category;
use App\Entity\Page;
use App\Entity\PageSection;
use App\Entity\PageBlock;
use App\Entity\PageBlockImage;
use App\Entity\PageBlockTestimonial;
use App\Entity\PageBlockPartnerLogo;
use App\Entity\PageBlockTeamMember;
use App\Entity\HeroBanner;
use App\Entity\ResearchLine;
use App\Entity\ContactFormField;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use ZipArchive;

class TenantImporter
{
    public function __construct(
        private EntityManagerInterface $em,
        private string $projectDir,
        private \Psr\Log\LoggerInterface $logger
    ) {}

    public function analyze(string $zipPath): array
    {
        $this->logger->info('[TenantImporter] Iniciando análise do pacote de importação ZIP.', [
            'zip_path' => $zipPath,
            'zip_size' => file_exists($zipPath) ? filesize($zipPath) : 0
        ]);

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            $this->logger->error('[TenantImporter] Erro crítico: Não foi possível abrir o arquivo ZIP.', ['zip_path' => $zipPath]);
            throw new \RuntimeException('Não foi possível abrir o arquivo ZIP.');
        }

        $jsonContent = $zip->getFromName('metadata.json');
        if (!$jsonContent) {
            $this->logger->error('[TenantImporter] Erro crítico: O arquivo metadata.json não foi encontrado dentro do pacote ZIP.');
            $zip->close();
            throw new \RuntimeException('O arquivo metadata.json não foi encontrado dentro do pacote ZIP.');
        }

        $data = json_decode($jsonContent, true);
        $zip->close();

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error('[TenantImporter] Erro crítico: O metadata.json possui formato JSON inválido.', [
                'json_error' => json_last_error_msg()
            ]);
            throw new \RuntimeException('O metadata.json possui formato JSON inválido.');
        }

        $this->logger->info('[TenantImporter] metadata.json lido com sucesso. Iniciando varredura de conflitos relacionais...');

        $conflicts = [
            'domain' => false,
            'tenant_name' => false,
            'users' => [],
        ];

        // 1. Check for Tenant domain conflict
        $domain = $data['tenant']['domain'] ?? '';
        $existingTenant = $this->em->getRepository(Tenant::class)->findOneBy(['domain' => $domain]);
        if ($existingTenant) {
            $conflicts['domain'] = $domain;
            $this->logger->warning('[TenantImporter] Conflito detectado: O domínio de acesso já está em uso.', ['domain' => $domain]);
        }

        // 2. Check for Tenant name conflict
        $name = $data['tenant']['name'] ?? '';
        $existingTenantByName = $this->em->getRepository(Tenant::class)->findOneBy(['name' => $name]);
        if ($existingTenantByName) {
            $conflicts['tenant_name'] = $name;
            $this->logger->warning('[TenantImporter] Conflito detectado: O nome do Tenant já está em uso.', ['name' => $name]);
        }

        // 3. Check for User username/email conflicts
        $users = $data['users'] ?? [];
        foreach ($users as $u) {
            $username = $u['username'] ?? '';
            $email = $u['email'] ?? '';

            $existingUserByUsername = $this->em->getRepository(User::class)->findOneBy(['username' => $username]);
            $existingUserByEmail = $email ? $this->em->getRepository(User::class)->findOneBy(['email' => $email]) : null;

            if ($existingUserByUsername || $existingUserByEmail) {
                $conflicts['users'][] = [
                    'original_username' => $username,
                    'original_email' => $email,
                    'username_collision' => (bool)$existingUserByUsername,
                    'email_collision' => (bool)$existingUserByEmail,
                ];
                $this->logger->warning('[TenantImporter] Conflito detectado: Colisão de credenciais de usuário.', [
                    'username' => $username,
                    'email' => $email,
                    'username_collision' => (bool)$existingUserByUsername,
                    'email_collision' => (bool)$existingUserByEmail,
                ]);
            }
        }

        $hasConflicts = !empty($conflicts['domain']) || !empty($conflicts['users']);

        $this->logger->info('[TenantImporter] Análise de conflitos concluída.', [
            'has_conflicts' => $hasConflicts,
            'conflicts_count_users' => count($conflicts['users']),
        ]);

        return [
            'metadata' => $data,
            'conflicts' => $conflicts,
            'has_conflicts' => $hasConflicts,
        ];
    }

    public function import(array $data, array $resolutions, string $zipPath): Tenant
    {
        $this->logger->info('[TenantImporter] Iniciando processo de importação relacional e física.', [
            'zip_path' => $zipPath,
            'resolutions' => $resolutions
        ]);

        $filesystem = new Filesystem();
        $tempWorkDir = $this->projectDir . '/var/tmp/import_' . uniqid('', true);
        $filesystem->mkdir($tempWorkDir);

        $this->logger->info('[TenantImporter] Extraindo pacote ZIP para espaço temporário de trabalho.', ['temp_dir' => $tempWorkDir]);

        // Extract ZIP contents
        $zip = new ZipArchive();
        if ($zip->open($zipPath) === true) {
            $zip->extractTo($tempWorkDir);
            $zip->close();
            $this->logger->info('[TenantImporter] Pacote ZIP extraído com sucesso.');
        } else {
            $this->logger->error('[TenantImporter] Falha crítica ao extrair o arquivo ZIP temporário.');
            throw new \RuntimeException('Não foi possível extrair o arquivo ZIP.');
        }

        $this->logger->info('[TenantImporter] Iniciando transação no Banco de Dados...');

        // --- Pre-flight: validate resolved credentials before any DB writes ---
        $usersData = $data['users'] ?? [];
        $credentialErrors = [];
        foreach ($usersData as $uData) {
            $originalUsername = $uData['username'];
            $resolvedUsername = $resolutions['users'][$originalUsername]['username'] ?? $originalUsername;
            $resolvedEmail    = $resolutions['users'][$originalUsername]['email']    ?? $uData['email'];

            // Check username uniqueness
            if ($this->em->getRepository(\App\Entity\User::class)->findOneBy(['username' => $resolvedUsername])) {
                $credentialErrors[] = sprintf(
                    'Username "%s" já existe no banco de dados. Escolha um nome diferente.',
                    $resolvedUsername
                );
                $this->logger->warning('[TenantImporter] Conflito de username detectado na pré-verificação.', [
                    'original_username' => $originalUsername,
                    'resolved_username' => $resolvedUsername,
                ]);
            }

            // Check email uniqueness (only when non-empty)
            if (!empty($resolvedEmail)) {
                if ($this->em->getRepository(\App\Entity\User::class)->findOneBy(['email' => $resolvedEmail])) {
                    $credentialErrors[] = sprintf(
                        'E-mail "%s" já existe no banco de dados. Informe um e-mail diferente.',
                        $resolvedEmail
                    );
                    $this->logger->warning('[TenantImporter] Conflito de e-mail detectado na pré-verificação.', [
                        'original_username' => $originalUsername,
                        'resolved_email'    => $resolvedEmail,
                    ]);
                }
            }
        }

        if (!empty($credentialErrors)) {
            $filesystem->remove($tempWorkDir);
            throw new \RuntimeException(
                'Conflito de credenciais de usuários: ' . implode(' | ', $credentialErrors)
            );
        }
        // --- End pre-flight ---

        $this->em->beginTransaction();

        try {
            // 1. Create and persist Tenant
            $tData = $data['tenant'];
            $tenant = new Tenant();
            $tenant->setDomain($resolutions['domain'] ?? $tData['domain']);
            $tenant->setName($resolutions['tenant_name'] ?? $tData['name']);
            $tenant->setPrimaryColor($tData['primaryColor'] ?? '#0044cc');
            $tenant->setSecondaryColor($tData['secondaryColor'] ?? '#ffaa00');
            $tenant->setPrimaryColorDark($tData['primaryColorDark'] ?? '#3b82f6');
            $tenant->setSecondaryColorDark($tData['secondaryColorDark'] ?? '#fbbf24');
            $tenant->setTheme($tData['theme'] ?? 'wab');
            $tenant->setContactEmail($tData['contactEmail'] ?? null);
            $tenant->setYoutubeLink($tData['youtubeLink'] ?? null);
            $tenant->setInstagramLink($tData['instagramLink'] ?? null);
            $tenant->setFacebookLink($tData['facebookLink'] ?? null);
            $tenant->setWhatsappLink($tData['whatsappLink'] ?? null);
            $tenant->setLinkedinLink($tData['linkedinLink'] ?? null);
            $tenant->setAboutText($tData['aboutText'] ?? null);
            $tenant->setAboutFullText($tData['aboutFullText'] ?? null);
            $tenant->setAddress($tData['address'] ?? null);
            $tenant->setPhone($tData['phone'] ?? null);
            $tenant->setMapsEmbedUrl($tData['mapsEmbedUrl'] ?? null);
            $tenant->setSeoTitle($tData['seoTitle'] ?? null);
            $tenant->setSeoDescription($tData['seoDescription'] ?? null);
            $tenant->setSeoKeywords($tData['seoKeywords'] ?? null);
            $tenant->setOgImage($tData['ogImage'] ?? null);
            $tenant->setFontSettings($tData['fontSettings'] ?? []);
            $tenant->setNavigationSettings($tData['navigationSettings'] ?? []);
            $tenant->setShowSectionTitles($tData['showSectionTitles'] ?? true);
            $tenant->setLandingPageMode($tData['landingPageMode'] ?? false);

            // Copy tenant branding assets
            if (!empty($tData['logo'])) {
                $tenant->setLogo($tData['logo']);
                $this->relocateMediaFile($tempWorkDir, 'tenant_logo', $tData['logo']);
            }
            if (!empty($tData['darkLogo'])) {
                $tenant->setDarkLogo($tData['darkLogo']);
                $this->relocateMediaFile($tempWorkDir, 'tenant_dark_logo', $tData['darkLogo']);
            }
            if (!empty($tData['favicon'])) {
                $tenant->setFavicon($tData['favicon']);
                $this->relocateMediaFile($tempWorkDir, 'tenant_favicon', $tData['favicon']);
            }
            if (!empty($tData['aboutImage'])) {
                $tenant->setAboutImage($tData['aboutImage']);
                $this->relocateMediaFile($tempWorkDir, 'tenant_about_image', $tData['aboutImage']);
            }
            if (!empty($tData['ogImage'])) {
                $tenant->setOgImage($tData['ogImage']);
                // Try to relocate as local file; if not in the zip, it stays as a URL string
                $this->relocateMediaFile($tempWorkDir, 'tenant_og_image', $tData['ogImage']);
            }

            $this->em->persist($tenant);

            // 2. Create Users
            $usersData = $data['users'] ?? [];
            foreach ($usersData as $uData) {
                $user = new User();
                $originalUsername = $uData['username'];
                
                // Read resolved credentials from the Wizard inputs
                $resolvedUsername = $resolutions['users'][$originalUsername]['username'] ?? $originalUsername;
                $resolvedEmail = $resolutions['users'][$originalUsername]['email'] ?? $uData['email'];

                $user->setUsername($resolvedUsername);
                $user->setName($uData['name']);
                $user->setEmail($resolvedEmail ?: null);
                $user->setWorkGroup((int)$uData['workGroup']);
                $user->setRoles($uData['roles']);
                $user->setPassword($uData['password']); // Preserve hashed bcrypt/argon password
                $user->setTenant($tenant);

                $this->em->persist($user);
            }

            // 3. Create Categories (2-pass hierarchy mapping)
            $categoryMap = [];
            $categoriesData = $data['categories'] ?? [];
            
            // First pass: save categories without parents
            foreach ($categoriesData as $cData) {
                $cat = new Category();
                $cat->setTenant($tenant);
                $cat->setName($cData['name']);
                $cat->setSlug($cData['slug']);
                $cat->setPreTitle($cData['preTitle'] ?? null);
                $cat->setDescription($cData['description'] ?? null);
                $cat->setShowInHeader((bool)$cData['showInHeader']);
                $cat->setShowInFooter((bool)$cData['showInFooter']);
                $cat->setIcon($cData['icon'] ?? null);

                $this->em->persist($cat);
                $this->em->flush(); // Assign DB auto-increment ID immediately

                $categoryMap[$cData['id']] = $cat;
            }

            // Second pass: connect parent relationships
            foreach ($categoriesData as $cData) {
                if (!empty($cData['parent_id']) && isset($categoryMap[$cData['parent_id']])) {
                    $categoryMap[$cData['id']]->setParent($categoryMap[$cData['parent_id']]);
                }
            }

            // 4. Create Pages
            $pageMap = [];
            $pagesData = $data['pages'] ?? [];
            foreach ($pagesData as $pData) {
                $page = new Page();
                $page->setTenant($tenant);
                $page->setTitle($pData['title']);
                $page->setSlug($pData['slug']);
                $page->setShowInHeader((bool)$pData['showInHeader']);
                $page->setShowInFooter((bool)$pData['showInFooter']);
                $page->setSeoTitle($pData['seoTitle'] ?? null);
                $page->setSeoDescription($pData['seoDescription'] ?? null);
                $page->setPosition((int)$pData['position']);
                $page->setShowTitle((bool)($pData['showTitle'] ?? true));

                if (!empty($pData['coverImage'])) {
                    $page->setCoverImage($pData['coverImage']);
                    $this->relocateMediaFile($tempWorkDir, 'page_cover_image', $pData['coverImage']);
                }

                if (!empty($pData['category_id']) && isset($categoryMap[$pData['category_id']])) {
                    $page->setCategory($categoryMap[$pData['category_id']]);
                }

                $this->em->persist($page);
                $this->em->flush(); // Assign page ID for downstream sections

                $pageMap[$pData['id']] = $page;
            }

            // 5. Create Sections
            $sectionMap = [];
            $sectionsData = $data['sections'] ?? [];
            foreach ($sectionsData as $sData) {
                $sec = new PageSection();
                
                if (!empty($sData['page_id']) && isset($pageMap[$sData['page_id']])) {
                    $sec->setPage($pageMap[$sData['page_id']]);
                }
                if (!empty($sData['category_id']) && isset($categoryMap[$sData['category_id']])) {
                    $sec->setCategory($categoryMap[$sData['category_id']]);
                }

                $sec->setTitlePart1($sData['titlePart1'] ?? null);
                $sec->setTitlePart2($sData['titlePart2'] ?? null);
                $sec->setPosition((int)$sData['position']);
                $sec->setActive((bool)$sData['active']);
                $sec->setBgType($sData['bgType'] ?? 'none');
                $sec->setBgColor($sData['bgColor'] ?? null);
                $sec->setBgGradient($sData['bgGradient'] ?? null);
                $sec->setBgImageOpacity((int)($sData['bgImageOpacity'] ?? 100));
                $sec->setBgImagePosition($sData['bgImagePosition'] ?? 'center');

                if (!empty($sData['bgImage'])) {
                    $sec->setBgImage($sData['bgImage']);
                    $this->relocateMediaFile($tempWorkDir, 'section_bg_image', $sData['bgImage']);
                }
                if (!empty($sData['bgVideo'])) {
                    $sec->setBgVideo($sData['bgVideo']);
                    $this->relocateMediaFile($tempWorkDir, 'section_bg_video', $sData['bgVideo']);
                }

                $this->em->persist($sec);
                $this->em->flush();

                $sectionMap[$sData['id']] = $sec;
            }

            // 6. Create Blocks and inner relations
            $blocksData = $data['blocks'] ?? [];
            foreach ($blocksData as $bData) {
                if (empty($bData['section_id']) || !isset($sectionMap[$bData['section_id']])) {
                    continue;
                }

                $block = new PageBlock();
                $block->setSection($sectionMap[$bData['section_id']]);
                $block->setType($bData['type'] ?? 'text_image');
                $block->setPreTitle($bData['preTitle'] ?? null);
                $block->setTitle($bData['title'] ?? null);
                $block->setText($bData['text'] ?? null);
                $block->setConfig($bData['config'] ?? null);
                $block->setEmbedUrl($bData['embedUrl'] ?? null);
                $block->setItemCount($bData['itemCount'] ? (int)$bData['itemCount'] : null);
                $block->setPosition((int)$bData['position']);

                if (!empty($bData['relatedCategory_id']) && isset($categoryMap[$bData['relatedCategory_id']])) {
                    $block->setRelatedCategory($categoryMap[$bData['relatedCategory_id']]);
                }

                if (!empty($bData['image'])) {
                    $block->setImage($bData['image']);
                    $this->relocateMediaFile($tempWorkDir, 'page_block_image', $bData['image']);
                }

                // Relocate images embedded in config JSON
                // (e.g. banner block stores slide images in config.banners[].image)
                $this->relocateConfigImages($tempWorkDir, $bData['config'] ?? null);

                $this->em->persist($block);

                // Gallery Images
                foreach ($bData['galleryImages'] ?? [] as $imgData) {
                    if (empty($imgData['filename'])) continue;
                    $gImg = new PageBlockImage();
                    $gImg->setBlock($block);
                    $gImg->setFilename($imgData['filename']);
                    $gImg->setCaption($imgData['caption'] ?? null);
                    $gImg->setPosition((int)$imgData['position']);
                    
                    $this->em->persist($gImg);
                    $this->relocateMediaFile($tempWorkDir, 'page_block_gallery', $imgData['filename']);
                }

                // Testimonials
                foreach ($bData['testimonials'] ?? [] as $tData) {
                    $testi = new PageBlockTestimonial();
                    $testi->setBlock($block);
                    $testi->setName($tData['name'] ?? $tData['author'] ?? '');
                    $testi->setRole($tData['role'] ?? null);
                    $testi->setText($tData['text'] ?? '');
                    $testi->setRating((int)($tData['rating'] ?? 5));
                    $testi->setPosition((int)$tData['position']);

                    if (!empty($tData['avatar'])) {
                        $testi->setAvatar($tData['avatar']);
                        $this->relocateMediaFile($tempWorkDir, 'testimonial_avatar', $tData['avatar']);
                    }
                    $this->em->persist($testi);
                }

                // Partner Logos
                foreach ($bData['partnerLogos'] ?? [] as $pData) {
                    if (empty($pData['logoFilename'])) continue;
                    $partner = new PageBlockPartnerLogo();
                    $partner->setBlock($block);
                    $partner->setName($pData['name'] ?? '');
                    $partner->setLogoFilename($pData['logoFilename']);
                    $partner->setPosition((int)$pData['position']);

                    $this->em->persist($partner);
                    $this->relocateMediaFile($tempWorkDir, 'partner_logo', $pData['logoFilename']);
                }

                // Team Members
                foreach ($bData['teamMembers'] ?? [] as $mData) {
                    $member = new PageBlockTeamMember();
                    $member->setBlock($block);
                    $member->setName($mData['name'] ?? '');
                    $member->setRole($mData['role'] ?? null);
                    $member->setBio($mData['bio'] ?? null);
                    $member->setLinkedinUrl($mData['linkedinUrl'] ?? null);
                    $member->setFacebookUrl($mData['facebookUrl'] ?? null);
                    $member->setInstagramUrl($mData['instagramUrl'] ?? null);
                    $member->setWhatsappUrl($mData['whatsappUrl'] ?? null);
                    $member->setPhone($mData['phone'] ?? null);
                    $member->setEmail($mData['email'] ?? null);
                    $member->setPosition((int)$mData['position']);

                    if (!empty($mData['image'])) {
                        $member->setImage($mData['image']);
                        $this->relocateMediaFile($tempWorkDir, 'team_member_image', $mData['image']);
                    }
                    $this->em->persist($member);
                }
            }

            // 7. Connect HomePage — must be done after all pages are persisted and flushed
            if (!empty($tData['homePageId']) && isset($pageMap[$tData['homePageId']])) {
                $tenant->setHomePage($pageMap[$tData['homePageId']]);
                $this->logger->info('[TenantImporter] Página inicial (homePage) conectada ao tenant.', [
                    'original_page_id' => $tData['homePageId'],
                    'new_page_id'      => $pageMap[$tData['homePageId']]->getId(),
                ]);
            } else {
                $this->logger->warning('[TenantImporter] homePageId não encontrado ou não mapeado. Home page não definida.', [
                    'homePageId_from_export' => $tData['homePageId'] ?? null,
                    'page_map_keys'          => array_keys($pageMap),
                ]);
            }

            // 8. Create Hero Banners
            $heroBannersData = $data['hero_banners'] ?? [];
            foreach ($heroBannersData as $hbData) {
                $hb = new HeroBanner();
                $hb->setTenant($tenant);
                $hb->setTitle($hbData['title'] ?? '');
                $hb->setSubtitle($hbData['subtitle'] ?? null);
                $hb->setCtaText($hbData['ctaText'] ?? null);
                $hb->setCtaLink($hbData['ctaLink'] ?? null);
                $hb->setActive((bool)$hbData['active']);
                $hb->setPosition((int)$hbData['position']);

                if (!empty($hbData['backgroundImage'])) {
                    $hb->setBackgroundImage($hbData['backgroundImage']);
                    $this->relocateMediaFile($tempWorkDir, 'hero_banner', $hbData['backgroundImage']);
                }
                $this->em->persist($hb);
            }

            // 9. Create Research Lines
            $researchLinesData = $data['research_lines'] ?? [];
            foreach ($researchLinesData as $rlData) {
                $rl = new ResearchLine();
                $rl->setTenant($tenant);
                $rl->setTitle($rlData['title'] ?? '');
                $rl->setDescription($rlData['description'] ?? null);
                $rl->setIcon($rlData['icon'] ?? null);
                $rl->setPosition((int)$rlData['position']);

                $this->em->persist($rl);
            }

            // 10. Create Contact Form Fields
            $formFieldsData = $data['contact_form_fields'] ?? [];
            foreach ($formFieldsData as $ffData) {
                $ff = new ContactFormField();
                $ff->setTenant($tenant);
                $ff->setLabel($ffData['label'] ?? '');
                $ff->setType($ffData['type'] ?? 'text');
                $ff->setOptions($ffData['options'] ?? null);
                $ff->setRequired((bool)$ffData['required']);
                $ff->setPosition((int)$ffData['position']);

                $this->em->persist($ff);
            }

            $this->em->flush();
            $this->em->commit();
            $this->logger->info('[TenantImporter] Transação do Banco de Dados comitada com sucesso!');

            // Clear temporary workspace
            $filesystem->remove($tempWorkDir);
            $this->logger->info('[TenantImporter] Workspace temporário limpo.', ['temp_dir' => $tempWorkDir]);

            return $tenant;
        } catch (\Exception $e) {
            $this->logger->error('[TenantImporter] Erro catastrófico durante a importação. Revertendo transação (Rollback) e limpando workspace.', [
                'exception_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->em->rollback();
            $filesystem->remove($tempWorkDir);
            throw $e;
        }
    }

    /**
     * Scans the block config JSON for embedded image filenames and relocates
     * them from the ZIP temp dir to the correct public/uploads/ folder.
     * Handles banner carousel slides (config.banners[].image) and other
     * known config image patterns.
     */
    private function relocateConfigImages(string $tempWorkDir, mixed $config): void
    {
        if (empty($config) || !is_array($config)) {
            return;
        }

        // Banner carousel: config.banners[].image
        if (isset($config['banners']) && is_array($config['banners'])) {
            foreach ($config['banners'] as $slide) {
                if (!empty($slide['image'])) {
                    $this->logger->info('[TenantImporter] Relocalizando imagem de slide de banner do config JSON.', [
                        'image' => $slide['image']
                    ]);
                    $this->relocateMediaFile($tempWorkDir, 'page_block_image', $slide['image']);
                }
            }
        }

        // Generic top-level config.image fallback
        if (!empty($config['image']) && is_string($config['image'])) {
            $this->relocateMediaFile($tempWorkDir, 'page_block_image', $config['image']);
        }
    }

    private function relocateMediaFile(string $tempWorkDir, string $mapping, string $filename): void
    {
        $source = sprintf('%s/media/%s/%s', $tempWorkDir, $mapping, $filename);
        $destinationDir = sprintf('%s/public/uploads/%s', $this->projectDir, $this->getMappingPath($mapping));
        $destination = $destinationDir . '/' . $filename;

        if (file_exists($source) && is_file($source)) {
            if (!is_dir($destinationDir)) {
                mkdir($destinationDir, 0755, true);
                $this->logger->info('[TenantImporter] Criando diretório de uploads de mídia de destino.', ['dir' => $destinationDir]);
            }
            if (copy($source, $destination)) {
                $this->logger->debug('[TenantImporter] Arquivo de mídia copiado com sucesso.', [
                    'source' => $source,
                    'destination' => $destination
                ]);
            } else {
                $this->logger->error('[TenantImporter] Falha ao copiar arquivo de mídia.', [
                    'source' => $source,
                    'destination' => $destination
                ]);
            }
        } else {
            $this->logger->warning('[TenantImporter] ALERTA: Arquivo de mídia referenciado em metadata.json não foi encontrado dentro do ZIP.', [
                'expected_source_path' => $source,
                'mapping' => $mapping,
                'filename' => $filename
            ]);
        }
    }

    private function getMappingPath(string $mapping): string
    {
        $map = [
            'tenant_logo'        => 'tenant/logo',
            'tenant_dark_logo'   => 'tenant/dark_logo',
            'tenant_about_image' => 'tenant/about',
            'tenant_favicon'     => 'tenant/favicon',
            'tenant_og_image'    => 'tenant/og',
            'page_cover_image'   => 'page_cover',
            'section_bg_image'   => 'section/bg',
            'section_bg_video'   => 'section/video',
            'page_block_image'   => 'page_block',
            'page_block_gallery' => 'page_block_gallery',
            'testimonial_avatar' => 'testimonial_avatar',
            'partner_logo'       => 'partner_logo',
            'team_member_image'  => 'team_member_image',
            'hero_banner'        => 'hero',
        ];

        return $map[$mapping] ?? $mapping;
    }
}
