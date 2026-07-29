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
use App\Entity\ContactMessage;
use App\Entity\NewsletterSubscriber;
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

    // ─────────────────────────────────────────────────────────────
    // analyze(): open ZIP, read metadata.json, detect DB conflicts
    // ─────────────────────────────────────────────────────────────

    public function analyze(string $zipPath): array
    {
        $this->logger->info('[TenantImporter] Iniciando análise do pacote de importação ZIP.', [
            'zip_path' => $zipPath,
            'zip_size' => file_exists($zipPath) ? filesize($zipPath) : 0,
        ]);

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            $this->logger->error('[TenantImporter] Erro crítico: Não foi possível abrir o arquivo ZIP.', ['zip_path' => $zipPath]);
            throw new \RuntimeException('Não foi possível abrir o arquivo ZIP.');
        }

        $jsonContent = $zip->getFromName('metadata.json');
        if (!$jsonContent) {
            $this->logger->error('[TenantImporter] Erro crítico: metadata.json não encontrado dentro do pacote ZIP.');
            $zip->close();
            throw new \RuntimeException('O arquivo metadata.json não foi encontrado dentro do pacote ZIP.');
        }

        $data = json_decode($jsonContent, true);
        $zip->close();

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error('[TenantImporter] Erro crítico: metadata.json possui formato JSON inválido.', [
                'json_error' => json_last_error_msg(),
            ]);
            throw new \RuntimeException('O metadata.json possui formato JSON inválido.');
        }

        $this->logger->info('[TenantImporter] metadata.json lido com sucesso. Iniciando varredura de conflitos...');

        $conflicts = [
            'domain'      => false,
            'tenant_name' => false,
            'users'       => [],
        ];

        // 1. Check Tenant domain conflict
        $domain = $data['tenant']['domain'] ?? '';
        if ($this->em->getRepository(Tenant::class)->findOneBy(['domain' => $domain])) {
            $conflicts['domain'] = $domain;
            $this->logger->warning('[TenantImporter] Conflito de domínio.', ['domain' => $domain]);
        }

        // 2. Check Tenant name conflict
        $name = $data['tenant']['name'] ?? '';
        if ($this->em->getRepository(Tenant::class)->findOneBy(['name' => $name])) {
            $conflicts['tenant_name'] = $name;
            $this->logger->warning('[TenantImporter] Conflito de nome do Tenant.', ['name' => $name]);
        }

        // 3. Check User username/email conflicts
        foreach ($data['users'] ?? [] as $u) {
            $username = $u['username'] ?? '';
            $email    = $u['email'] ?? '';

            $usernameExists = $this->em->getRepository(User::class)->findOneBy(['username' => $username]);
            $emailExists    = $email ? $this->em->getRepository(User::class)->findOneBy(['email' => $email]) : null;

            if ($usernameExists || $emailExists) {
                $conflicts['users'][] = [
                    'original_username'  => $username,
                    'original_email'     => $email,
                    'username_collision' => (bool) $usernameExists,
                    'email_collision'    => (bool) $emailExists,
                ];
                $this->logger->warning('[TenantImporter] Conflito de credenciais de usuário.', [
                    'username'           => $username,
                    'email'              => $email,
                    'username_collision' => (bool) $usernameExists,
                    'email_collision'    => (bool) $emailExists,
                ]);
            }
        }

        $hasConflicts = !empty($conflicts['domain']) || !empty($conflicts['users']);

        $this->logger->info('[TenantImporter] Análise de conflitos concluída.', [
            'has_conflicts'        => $hasConflicts,
            'conflicts_count_users' => count($conflicts['users']),
        ]);

        return [
            'metadata'     => $data,
            'conflicts'    => $conflicts,
            'has_conflicts' => $hasConflicts,
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // import(): full transactional restore of all tenant data
    // ─────────────────────────────────────────────────────────────

    public function import(array $data, array $resolutions, string $zipPath): Tenant
    {
        $this->logger->info('[TenantImporter] Iniciando processo de importação relacional e física.', [
            'zip_path'    => $zipPath,
            'resolutions' => $resolutions,
        ]);

        $filesystem  = new Filesystem();
        $tempWorkDir = $this->projectDir . '/var/tmp/import_' . uniqid('', true);
        $filesystem->mkdir($tempWorkDir);

        $this->logger->info('[TenantImporter] Extraindo pacote ZIP para workspace temporário.', ['temp_dir' => $tempWorkDir]);

        // Extract ZIP
        $zip = new ZipArchive();
        if ($zip->open($zipPath) === true) {
            $zip->extractTo($tempWorkDir);
            $zip->close();
            $this->logger->info('[TenantImporter] Pacote ZIP extraído com sucesso.');
        } else {
            $this->logger->error('[TenantImporter] Falha crítica ao extrair o arquivo ZIP.');
            throw new \RuntimeException('Não foi possível extrair o arquivo ZIP.');
        }

        // ── Pre-flight: validate resolved credentials before any DB writes ──
        $credentialErrors = [];
        foreach ($data['users'] ?? [] as $uData) {
            $originalUsername = $uData['username'];
            $resolvedUsername = $resolutions['users'][$originalUsername]['username'] ?? $originalUsername;
            $resolvedEmail    = $resolutions['users'][$originalUsername]['email']    ?? $uData['email'];

            if ($this->em->getRepository(User::class)->findOneBy(['username' => $resolvedUsername])) {
                $credentialErrors[] = sprintf('Username "%s" já existe. Escolha outro.', $resolvedUsername);
                $this->logger->warning('[TenantImporter] Conflito de username detectado na pré-verificação.', [
                    'original_username' => $originalUsername,
                    'resolved_username' => $resolvedUsername,
                ]);
            }
            if (!empty($resolvedEmail) && $this->em->getRepository(User::class)->findOneBy(['email' => $resolvedEmail])) {
                $credentialErrors[] = sprintf('E-mail "%s" já existe. Informe outro.', $resolvedEmail);
                $this->logger->warning('[TenantImporter] Conflito de e-mail detectado na pré-verificação.', [
                    'original_username' => $originalUsername,
                    'resolved_email'    => $resolvedEmail,
                ]);
            }
        }

        if (!empty($credentialErrors)) {
            $filesystem->remove($tempWorkDir);
            throw new \RuntimeException('Conflito de credenciais: ' . implode(' | ', $credentialErrors));
        }
        // ── End pre-flight ──

        $this->logger->info('[TenantImporter] Iniciando transação no Banco de Dados...');
        $this->em->beginTransaction();

        try {
            $tData = $data['tenant'];

            // ── 1. Tenant ─────────────────────────────────────────────────
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
            $tenant->setFontSettings($tData['fontSettings'] ?? []);
            if (array_key_exists('openingHours', $tData)) {
                $tenant->setOpeningHours($tData['openingHours']);
            }
            $tenant->setNavigationSettings($tData['navigationSettings'] ?? []);
            $tenant->setShowSectionTitles($tData['showSectionTitles'] ?? true);
            $tenant->setLandingPageMode($tData['landingPageMode'] ?? false);
            $tenant->setNewsletterEnabled($tData['newsletterEnabled'] ?? true);

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
                $this->relocateMediaFile($tempWorkDir, 'tenant_og_image', $tData['ogImage']);
            }

            $this->em->persist($tenant);

            // ── 2. Users ──────────────────────────────────────────────────
            foreach ($data['users'] ?? [] as $uData) {
                $originalUsername = $uData['username'];
                $resolvedUsername = $resolutions['users'][$originalUsername]['username'] ?? $originalUsername;
                $resolvedEmail    = $resolutions['users'][$originalUsername]['email']    ?? $uData['email'];

                $user = new User();
                $user->setUsername($resolvedUsername);
                $user->setName($uData['name']);
                $user->setEmail($resolvedEmail ?: null);
                $user->setWorkGroup((int) $uData['workGroup']);
                $user->setRoles($uData['roles']);
                $user->setPassword($uData['password']); // Preserve hashed password
                $user->setTenant($tenant);
                $this->em->persist($user);
            }

            // ── 3. Categories (2-pass for parent hierarchy) ───────────────
            $categoryMap = [];

            // Pass 1: persist without parents
            foreach ($data['categories'] ?? [] as $cData) {
                $cat = new Category();
                $cat->setTenant($tenant);
                $cat->setName($cData['name']);
                $cat->setSlug($cData['slug']);
                $cat->setPreTitle($cData['preTitle'] ?? null);
                $cat->setDescription($cData['description'] ?? null);
                $cat->setShowInHeader((bool) $cData['showInHeader']);
                $cat->setShowInFooter((bool) $cData['showInFooter']);
                $cat->setIcon($cData['icon'] ?? null);
                $this->em->persist($cat);
                $this->em->flush();
                $categoryMap[$cData['id']] = $cat;
            }

            // Pass 2: wire parent relationships
            foreach ($data['categories'] ?? [] as $cData) {
                if (!empty($cData['parent_id']) && isset($categoryMap[$cData['parent_id']])) {
                    $categoryMap[$cData['id']]->setParent($categoryMap[$cData['parent_id']]);
                }
            }

            // ── 4. Pages ──────────────────────────────────────────────────
            $pageMap = [];
            foreach ($data['pages'] ?? [] as $pData) {
                $page = new Page();
                $page->setTenant($tenant);
                $page->setTitle($pData['title']);
                $page->setSlug($pData['slug']);
                $page->setShowInHeader((bool) $pData['showInHeader']);
                $page->setShowInFooter((bool) $pData['showInFooter']);
                $page->setSeoTitle($pData['seoTitle'] ?? null);
                $page->setSeoDescription($pData['seoDescription'] ?? null);
                $page->setPosition((int) $pData['position']);
                $page->setShowTitle((bool) ($pData['showTitle'] ?? true));

                if (!empty($pData['coverImage'])) {
                    $page->setCoverImage($pData['coverImage']);
                    $this->relocateMediaFile($tempWorkDir, 'page_cover_image', $pData['coverImage']);
                }
                if (!empty($pData['category_id']) && isset($categoryMap[$pData['category_id']])) {
                    $page->setCategory($categoryMap[$pData['category_id']]);
                }

                $this->em->persist($page);
                $this->em->flush(); // Flush to get the new ID for downstream entities
                $pageMap[$pData['id']] = $page;
            }

            // ── 5. Sections ───────────────────────────────────────────────
            $sectionMap = [];
            foreach ($data['sections'] ?? [] as $sData) {
                $sec = new PageSection();
                if (!empty($sData['page_id']) && isset($pageMap[$sData['page_id']])) {
                    $sec->setPage($pageMap[$sData['page_id']]);
                }
                if (!empty($sData['category_id']) && isset($categoryMap[$sData['category_id']])) {
                    $sec->setCategory($categoryMap[$sData['category_id']]);
                }
                $sec->setTitlePart1($sData['titlePart1'] ?? null);
                $sec->setTitlePart2($sData['titlePart2'] ?? null);
                $sec->setPosition((int) $sData['position']);
                $sec->setActive((bool) $sData['active']);
                $sec->setBgType($sData['bgType'] ?? 'none');
                $sec->setBgColor($sData['bgColor'] ?? null);
                $sec->setBgGradient($sData['bgGradient'] ?? null);
                $sec->setBgImageOpacity((int) ($sData['bgImageOpacity'] ?? 100));
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

            // ── 6. Blocks (with all inner entities) ───────────────────────
            foreach ($data['blocks'] ?? [] as $bData) {
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
                $block->setItemCount($bData['itemCount'] ? (int) $bData['itemCount'] : null);
                $block->setPosition((int) $bData['position']);

                if (!empty($bData['relatedCategory_id']) && isset($categoryMap[$bData['relatedCategory_id']])) {
                    $block->setRelatedCategory($categoryMap[$bData['relatedCategory_id']]);
                }
                if (!empty($bData['image'])) {
                    $block->setImage($bData['image']);
                    $this->relocateMediaFile($tempWorkDir, 'page_block_image', $bData['image']);
                }

                // Relocate banner carousel slide images embedded in config JSON
                $this->relocateConfigImages($tempWorkDir, $bData['config'] ?? null);

                $this->em->persist($block);

                // Gallery images
                foreach ($bData['galleryImages'] ?? [] as $imgData) {
                    if (empty($imgData['filename'])) continue;
                    $gImg = new PageBlockImage();
                    $gImg->setBlock($block);
                    $gImg->setFilename($imgData['filename']);
                    $gImg->setCaption($imgData['caption'] ?? null);
                    $gImg->setPosition((int) $imgData['position']);
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
                    $testi->setRating((int) ($tData['rating'] ?? 5));
                    $testi->setPosition((int) $tData['position']);
                    if (!empty($tData['avatar'])) {
                        $testi->setAvatar($tData['avatar']);
                        $this->relocateMediaFile($tempWorkDir, 'testimonial_avatar', $tData['avatar']);
                    }
                    $this->em->persist($testi);
                }

                // Partner logos
                foreach ($bData['partnerLogos'] ?? [] as $pData) {
                    if (empty($pData['logoFilename'])) continue;
                    $partner = new PageBlockPartnerLogo();
                    $partner->setBlock($block);
                    $partner->setName($pData['name'] ?? '');
                    $partner->setLogoFilename($pData['logoFilename']);
                    $partner->setPosition((int) $pData['position']);
                    $this->em->persist($partner);
                    $this->relocateMediaFile($tempWorkDir, 'partner_logo', $pData['logoFilename']);
                }

                // Team members
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
                    $member->setPosition((int) $mData['position']);
                    if (!empty($mData['image'])) {
                        $member->setImage($mData['image']);
                        $this->relocateMediaFile($tempWorkDir, 'team_member_image', $mData['image']);
                    }
                    $this->em->persist($member);
                }
            }

            // ── 7. Connect home page ──────────────────────────────────────
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

            // ── 8. Hero Banners ───────────────────────────────────────────
            foreach ($data['hero_banners'] ?? [] as $hbData) {
                $hb = new HeroBanner();
                $hb->setTenant($tenant);
                $hb->setTitle($hbData['title'] ?? '');
                $hb->setSubtitle($hbData['subtitle'] ?? null);
                $hb->setCtaText($hbData['ctaText'] ?? null);
                $hb->setCtaLink($hbData['ctaLink'] ?? null);
                $hb->setActive((bool) $hbData['active']);
                $hb->setPosition((int) $hbData['position']);
                if (!empty($hbData['backgroundImage'])) {
                    $hb->setBackgroundImage($hbData['backgroundImage']);
                    $this->relocateMediaFile($tempWorkDir, 'hero_banner', $hbData['backgroundImage']);
                }
                $this->em->persist($hb);
            }

            // ── 9. Research Lines ─────────────────────────────────────────
            foreach ($data['research_lines'] ?? [] as $rlData) {
                $rl = new ResearchLine();
                $rl->setTenant($tenant);
                $rl->setTitle($rlData['title'] ?? '');
                $rl->setDescription($rlData['description'] ?? null);
                $rl->setIcon($rlData['icon'] ?? null);
                $rl->setPosition((int) $rlData['position']);
                $this->em->persist($rl);
            }

            // ── 10. Contact Form Fields ───────────────────────────────────
            foreach ($data['contact_form_fields'] ?? [] as $ffData) {
                $ff = new ContactFormField();
                $ff->setTenant($tenant);
                $ff->setLabel($ffData['label'] ?? '');
                $ff->setType($ffData['type'] ?? 'text');
                $ff->setOptions($ffData['options'] ?? null);
                $ff->setRequired((bool) $ffData['required']);
                $ff->setPosition((int) $ffData['position']);
                $this->em->persist($ff);
            }

            // ── 11. Contact Messages ──────────────────────────────────────
            $messagesImported = 0;
            foreach ($data['contact_messages'] ?? [] as $mData) {
                $msg = new ContactMessage();
                $msg->setTenant($tenant);
                $msg->setSenderName($mData['senderName'] ?? '');
                $msg->setSenderEmail($mData['senderEmail'] ?? '');
                $msg->setMessage($mData['message'] ?? '');
                $msg->setPhone($mData['phone'] ?? null);
                $msg->setExtraData($mData['extraData'] ?? null);
                $msg->setIsRead((bool) ($mData['isRead'] ?? false));
                // Preserve original createdAt timestamp
                if (!empty($mData['createdAt'])) {
                    $reflection = new \ReflectionProperty(ContactMessage::class, 'createdAt');
                    $reflection->setAccessible(true);
                    $reflection->setValue($msg, new \DateTimeImmutable($mData['createdAt']));
                }
                $this->em->persist($msg);
                $messagesImported++;
            }
            $this->logger->info('[TenantImporter] Mensagens de contato importadas.', ['count' => $messagesImported]);

            // ── 12. Newsletter Subscribers ────────────────────────────────
            $subscribersImported = 0;
            foreach ($data['newsletter_subscribers'] ?? [] as $sData) {
                $sub = new NewsletterSubscriber();
                $sub->setTenant($tenant);
                $sub->setName($sData['name'] ?? '');
                $sub->setEmail($sData['email'] ?? '');
                // Preserve original subscribedAt timestamp
                if (!empty($sData['subscribedAt'])) {
                    $reflection = new \ReflectionProperty(NewsletterSubscriber::class, 'subscribedAt');
                    $reflection->setAccessible(true);
                    $reflection->setValue($sub, new \DateTimeImmutable($sData['subscribedAt']));
                }
                $this->em->persist($sub);
                $subscribersImported++;
            }
            $this->logger->info('[TenantImporter] Assinantes de newsletter importados.', ['count' => $subscribersImported]);

            // ── Final flush + commit ──────────────────────────────────────
            $this->em->flush();
            $this->em->commit();
            $this->logger->info('[TenantImporter] Transação comitada com sucesso!');

            $filesystem->remove($tempWorkDir);
            $this->logger->info('[TenantImporter] Workspace temporário limpo.', ['temp_dir' => $tempWorkDir]);

            return $tenant;

        } catch (\Throwable $e) {
            $this->em->rollback();
            $filesystem->remove($tempWorkDir);
            $this->logger->error('[TenantImporter] Erro durante importação — transação revertida.', [
                'exception' => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────

    /**
     * Scans block config JSON for embedded image filenames and relocates them.
     * Handles banner carousel slides (config.banners[].image) and generic config.image.
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
                        'image' => $slide['image'],
                    ]);
                    $this->relocateMediaFile($tempWorkDir, 'page_block_image', $slide['image']);
                }
            }
        }

        // Generic top-level config.image
        if (!empty($config['image']) && is_string($config['image'])) {
            $this->relocateMediaFile($tempWorkDir, 'page_block_image', $config['image']);
        }
    }

    private function relocateMediaFile(string $tempWorkDir, string $mapping, string $filename): void
    {
        $source         = sprintf('%s/media/%s/%s', $tempWorkDir, $mapping, $filename);
        $destinationDir = sprintf('%s/public/uploads/%s', $this->projectDir, $this->getMappingPath($mapping));
        $destination    = $destinationDir . '/' . $filename;

        if (file_exists($source) && is_file($source)) {
            if (!is_dir($destinationDir)) {
                mkdir($destinationDir, 0755, true);
                $this->logger->info('[TenantImporter] Diretório de uploads criado.', ['dir' => $destinationDir]);
            }
            if (copy($source, $destination)) {
                $this->logger->debug('[TenantImporter] Arquivo de mídia copiado com sucesso.', [
                    'source'      => $source,
                    'destination' => $destination,
                ]);
            } else {
                $this->logger->error('[TenantImporter] Falha ao copiar arquivo de mídia.', [
                    'source'      => $source,
                    'destination' => $destination,
                ]);
            }
        } else {
            $this->logger->warning('[TenantImporter] Arquivo de mídia não encontrado no pacote ZIP.', [
                'mapping'       => $mapping,
                'filename'      => $filename,
                'expected_path' => $source,
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
