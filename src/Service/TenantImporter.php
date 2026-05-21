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
        private string $projectDir
    ) {}

    public function analyze(string $zipPath): array
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('Não foi possível abrir o arquivo ZIP.');
        }

        $jsonContent = $zip->getFromName('metadata.json');
        if (!$jsonContent) {
            $zip->close();
            throw new \RuntimeException('O arquivo metadata.json não foi encontrado dentro do pacote ZIP.');
        }

        $data = json_decode($jsonContent, true);
        $zip->close();

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('O metadata.json possui formato JSON inválido.');
        }

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
        }

        // 2. Check for Tenant name conflict
        $name = $data['tenant']['name'] ?? '';
        $existingTenantByName = $this->em->getRepository(Tenant::class)->findOneBy(['name' => $name]);
        if ($existingTenantByName) {
            $conflicts['tenant_name'] = $name;
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
            }
        }

        $hasConflicts = !empty($conflicts['domain']) || !empty($conflicts['users']);

        return [
            'metadata' => $data,
            'conflicts' => $conflicts,
            'has_conflicts' => $hasConflicts,
        ];
    }

    public function import(array $data, array $resolutions, string $zipPath): Tenant
    {
        $filesystem = new Filesystem();
        $tempWorkDir = $this->projectDir . '/var/tmp/import_' . uniqid('', true);
        $filesystem->mkdir($tempWorkDir);

        // Extract ZIP contents
        $zip = new ZipArchive();
        if ($zip->open($zipPath) === true) {
            $zip->extractTo($tempWorkDir);
            $zip->close();
        } else {
            throw new \RuntimeException('Não foi possível extrair o arquivo ZIP.');
        }

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

            // 7. Connect HomePage
            if (!empty($tData['homePageId']) && isset($pageMap[$tData['homePageId']])) {
                $tenant->setHomePage($pageMap[$tData['homePageId']]);
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

            // Clear temporary workspace
            $filesystem->remove($tempWorkDir);

            return $tenant;
        } catch (\Exception $e) {
            $this->em->rollback();
            $filesystem->remove($tempWorkDir);
            throw $e;
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
            }
            copy($source, $destination);
        }
    }

    private function getMappingPath(string $mapping): string
    {
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

        return $map[$mapping] ?? $mapping;
    }
}
