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

class TenantExporter
{
    public function __construct(
        private EntityManagerInterface $em,
        private string $projectDir,
        private \Psr\Log\LoggerInterface $logger
    ) {}

    public function export(Tenant $tenant): string
    {
        $this->logger->info('[TenantExporter] Iniciando processo de exportação do Tenant.', [
            'tenant_id' => $tenant->getId(),
            'domain'    => $tenant->getDomain(),
            'name'      => $tenant->getName(),
        ]);

        // 1. Create a safe temporary directory in the workspace
        $tempDir = $this->projectDir . '/var/tmp/export_' . uniqid('', true);
        $filesystem = new Filesystem();
        $filesystem->mkdir($tempDir);

        $zipPath = $tempDir . '/tenant_export_'
            . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $tenant->getDomain())
            . '_' . date('Ymd_His') . '.zip';

        $this->logger->info('[TenantExporter] Criando arquivo ZIP temporário.', ['zip_path' => $zipPath]);

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->logger->error('[TenantExporter] Falha catastrófica ao criar o arquivo ZIP temporário.', ['zip_path' => $zipPath]);
            throw new \RuntimeException('Não foi possível criar o arquivo ZIP temporário.');
        }

        // 2. Gather database metadata
        $this->logger->info('[TenantExporter] Serializando tabelas e metadados relacionais...');

        $data = [
            'system' => [
                'platform_version' => '2026.2',
                'export_timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            ],
            'tenant'               => $this->serializeTenant($tenant),
            'users'                => $this->serializeUsers($tenant),
            'categories'           => $this->serializeCategories($tenant),
            'pages'                => $this->serializePages($tenant),
            'sections'             => $this->serializeSections($tenant),
            'blocks'               => $this->serializeBlocks($tenant),
            'hero_banners'         => $this->serializeHeroBanners($tenant),
            'research_lines'       => $this->serializeResearchLines($tenant),
            'contact_form_fields'  => $this->serializeContactFormFields($tenant),
            'contact_messages'     => $this->serializeContactMessages($tenant),
            'newsletter_subscribers' => $this->serializeNewsletterSubscribers($tenant),
        ];

        $this->logger->info('[TenantExporter] Metadados serializados com sucesso.', [
            'users_count'                  => count($data['users']),
            'categories_count'             => count($data['categories']),
            'pages_count'                  => count($data['pages']),
            'sections_count'               => count($data['sections']),
            'blocks_count'                 => count($data['blocks']),
            'hero_banners_count'           => count($data['hero_banners']),
            'research_lines_count'         => count($data['research_lines']),
            'contact_form_fields_count'    => count($data['contact_form_fields']),
            'contact_messages_count'       => count($data['contact_messages']),
            'newsletter_subscribers_count' => count($data['newsletter_subscribers']),
        ]);

        // 3. Write metadata.json to zip
        $metadataJson = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $zip->addFromString('metadata.json', $metadataJson);
        $this->logger->info('[TenantExporter] metadata.json adicionado ao pacote ZIP.', [
            'json_bytes' => strlen($metadataJson),
        ]);

        // 4. Collect and add media files to zip
        $this->logger->info('[TenantExporter] Escaneando e empacotando arquivos físicos de mídia...');
        $this->collectMediaFiles($tenant, $data, $zip);

        $zip->close();

        $zipSize = file_exists($zipPath) ? filesize($zipPath) : 0;
        $this->logger->info('[TenantExporter] Exportação finalizada com sucesso.', [
            'zip_path'         => $zipPath,
            'zip_size_bytes'   => $zipSize,
            'zip_size_readable' => number_format($zipSize / 1024 / 1024, 2) . ' MB',
        ]);

        return $zipPath;
    }

    // ─────────────────────────────────────────────────────────────
    // Serializers
    // ─────────────────────────────────────────────────────────────

    private function serializeTenant(Tenant $t): array
    {
        return [
            'id'                  => $t->getId(),
            'domain'              => $t->getDomain(),
            'name'                => $t->getName(),
            'logo'                => $t->getLogo(),
            'darkLogo'            => $t->getDarkLogo(),
            'primaryColor'        => $t->getPrimaryColor(),
            'secondaryColor'      => $t->getSecondaryColor(),
            'primaryColorDark'    => $t->getPrimaryColorDark(),
            'secondaryColorDark'  => $t->getSecondaryColorDark(),
            'contactEmail'        => $t->getContactEmail(),
            'youtubeLink'         => $t->getYoutubeLink(),
            'instagramLink'       => $t->getInstagramLink(),
            'facebookLink'        => $t->getFacebookLink(),
            'whatsappLink'        => $t->getWhatsappLink(),
            'linkedinLink'        => $t->getLinkedinLink(),
            'aboutText'           => $t->getAboutText(),
            'aboutFullText'       => $t->getAboutFullText(),
            'aboutImage'          => $t->getAboutImage(),
            'address'             => $t->getAddress(),
            'phone'               => $t->getPhone(),
            'mapsEmbedUrl'        => $t->getMapsEmbedUrl(),
            'theme'               => $t->getTheme(),
            'homePageId'          => $t->getHomePage()?->getId(),
            'favicon'             => $t->getFavicon(),
            'seoTitle'            => $t->getSeoTitle(),
            'seoDescription'      => $t->getSeoDescription(),
            'seoKeywords'         => $t->getSeoKeywords(),
            'ogImage'             => $t->getOgImage(),
            'fontSettings'        => $t->getFontSettings(),
            'navigationSettings'  => $t->getNavigationSettings(),
            'showSectionTitles'   => $t->isShowSectionTitles(),
            'landingPageMode'     => $t->isLandingPageMode(),
        ];
    }

    private function serializeUsers(Tenant $t): array
    {
        $users = $this->em->getRepository(User::class)->findBy(['tenant' => $t]);
        $serialized = [];
        foreach ($users as $u) {
            $serialized[] = [
                'username'  => $u->getUsername(),
                'name'      => $u->getName(),
                'email'     => $u->getEmail(),
                'workGroup' => $u->getWorkGroup(),
                'roles'     => $u->getRoles(),
                'password'  => $u->getPassword(),
            ];
        }
        return $serialized;
    }

    private function serializeCategories(Tenant $t): array
    {
        $cats = $this->em->getRepository(Category::class)->findBy(['tenant' => $t]);
        $serialized = [];
        foreach ($cats as $c) {
            $serialized[] = [
                'id'           => $c->getId(),
                'parent_id'    => $c->getParent()?->getId(),
                'name'         => $c->getName(),
                'slug'         => $c->getSlug(),
                'preTitle'     => $c->getPreTitle(),
                'description'  => $c->getDescription(),
                'showInHeader' => $c->isShowInHeader(),
                'showInFooter' => $c->isShowInFooter(),
                'icon'         => $c->getIcon(),
            ];
        }
        return $serialized;
    }

    private function serializePages(Tenant $t): array
    {
        $pages = $this->em->getRepository(Page::class)->findBy(['tenant' => $t]);
        $serialized = [];
        foreach ($pages as $p) {
            $serialized[] = [
                'id'             => $p->getId(),
                'title'          => $p->getTitle(),
                'slug'           => $p->getSlug(),
                'showInHeader'   => $p->isShowInHeader(),
                'showInFooter'   => $p->isShowInFooter(),
                'seoTitle'       => $p->getSeoTitle(),
                'seoDescription' => $p->getSeoDescription(),
                'coverImage'     => $p->getCoverImage(),
                'position'       => $p->getPosition(),
                'category_id'    => $p->getCategory()?->getId(),
                'showTitle'      => $p->isShowTitle(),
            ];
        }
        return $serialized;
    }

    private function serializeSections(Tenant $t): array
    {
        $sections = $this->em->createQueryBuilder()
            ->select('s')
            ->from(PageSection::class, 's')
            ->leftJoin('s.page', 'p')
            ->leftJoin('s.category', 'c')
            ->where('p.tenant = :tenant OR c.tenant = :tenant')
            ->setParameter('tenant', $t)
            ->getQuery()
            ->getResult();

        $serialized = [];
        foreach ($sections as $s) {
            /** @var PageSection $s */
            $serialized[] = [
                'id'               => $s->getId(),
                'page_id'          => $s->getPage()?->getId(),
                'category_id'      => $s->getCategory()?->getId(),
                'titlePart1'       => $s->getTitlePart1(),
                'titlePart2'       => $s->getTitlePart2(),
                'position'         => $s->getPosition(),
                'active'           => $s->isActive(),
                'bgType'           => $s->getBgType(),
                'bgColor'          => $s->getBgColor(),
                'bgGradient'       => $s->getBgGradient(),
                'bgImage'          => $s->getBgImage(),
                'bgImageOpacity'   => $s->getBgImageOpacity(),
                'bgImagePosition'  => $s->getBgImagePosition(),
                'bgVideo'          => $s->getBgVideo(),
            ];
        }
        return $serialized;
    }

    private function serializeBlocks(Tenant $t): array
    {
        $blocks = $this->em->createQueryBuilder()
            ->select('b')
            ->from(PageBlock::class, 'b')
            ->join('b.section', 's')
            ->leftJoin('s.page', 'p')
            ->leftJoin('s.category', 'c')
            ->where('p.tenant = :tenant OR c.tenant = :tenant')
            ->setParameter('tenant', $t)
            ->getQuery()
            ->getResult();

        $serialized = [];
        foreach ($blocks as $b) {
            /** @var PageBlock $b */
            $serialized[] = [
                'id'                  => $b->getId(),
                'section_id'          => $b->getSection()?->getId(),
                'type'                => $b->getType(),
                'preTitle'            => $b->getPreTitle(),
                'title'               => $b->getTitle(),
                'text'                => $b->getText(),
                'image'               => $b->getImage(),
                'config'              => $b->getConfig(),
                'embedUrl'            => $b->getEmbedUrl(),
                'itemCount'           => $b->getItemCount(),
                'relatedCategory_id'  => $b->getRelatedCategory()?->getId(),
                'position'            => $b->getPosition(),
                'galleryImages'       => $this->serializeGalleryImages($b),
                'testimonials'        => $this->serializeTestimonials($b),
                'partnerLogos'        => $this->serializePartnerLogos($b),
                'teamMembers'         => $this->serializeTeamMembers($b),
            ];
        }
        return $serialized;
    }

    private function serializeGalleryImages(PageBlock $b): array
    {
        $res = [];
        foreach ($b->getGalleryImages() as $img) {
            /** @var PageBlockImage $img */
            $res[] = [
                'id'       => $img->getId(),
                'filename' => $img->getFilename(),
                'caption'  => $img->getCaption(),
                'position' => $img->getPosition(),
            ];
        }
        return $res;
    }

    private function serializeTestimonials(PageBlock $b): array
    {
        $res = [];
        foreach ($b->getTestimonials() as $t) {
            /** @var PageBlockTestimonial $t */
            $res[] = [
                'id'       => $t->getId(),
                'name'     => $t->getName(),
                'role'     => $t->getRole(),
                'text'     => $t->getText(),
                'rating'   => $t->getRating(),
                'avatar'   => $t->getAvatar(),
                'position' => $t->getPosition(),
            ];
        }
        return $res;
    }

    private function serializePartnerLogos(PageBlock $b): array
    {
        $res = [];
        foreach ($b->getPartnerLogos() as $p) {
            /** @var PageBlockPartnerLogo $p */
            $res[] = [
                'id'           => $p->getId(),
                'name'         => $p->getName(),
                'logoFilename' => $p->getLogoFilename(),
                'position'     => $p->getPosition(),
            ];
        }
        return $res;
    }

    private function serializeTeamMembers(PageBlock $b): array
    {
        $res = [];
        foreach ($b->getTeamMembers() as $m) {
            /** @var PageBlockTeamMember $m */
            $res[] = [
                'id'           => $m->getId(),
                'name'         => $m->getName(),
                'role'         => $m->getRole(),
                'bio'          => $m->getBio(),
                'image'        => $m->getImage(),
                'linkedinUrl'  => $m->getLinkedinUrl(),
                'facebookUrl'  => $m->getFacebookUrl(),
                'instagramUrl' => $m->getInstagramUrl(),
                'whatsappUrl'  => $m->getWhatsappUrl(),
                'phone'        => $m->getPhone(),
                'email'        => $m->getEmail(),
                'position'     => $m->getPosition(),
            ];
        }
        return $res;
    }

    private function serializeHeroBanners(Tenant $t): array
    {
        $banners = $this->em->getRepository(HeroBanner::class)->findBy(['tenant' => $t]);
        $serialized = [];
        foreach ($banners as $b) {
            $serialized[] = [
                'id'              => $b->getId(),
                'title'           => $b->getTitle(),
                'subtitle'        => $b->getSubtitle(),
                'ctaText'         => $b->getCtaText(),
                'ctaLink'         => $b->getCtaLink(),
                'backgroundImage' => $b->getBackgroundImage(),
                'active'          => $b->isActive(),
                'position'        => $b->getPosition(),
            ];
        }
        return $serialized;
    }

    private function serializeResearchLines(Tenant $t): array
    {
        $lines = $this->em->getRepository(ResearchLine::class)->findBy(['tenant' => $t]);
        $serialized = [];
        foreach ($lines as $l) {
            $serialized[] = [
                'id'          => $l->getId(),
                'title'       => $l->getTitle(),
                'description' => $l->getDescription(),
                'icon'        => $l->getIcon(),
                'position'    => $l->getPosition(),
            ];
        }
        return $serialized;
    }

    private function serializeContactFormFields(Tenant $t): array
    {
        $fields = $this->em->getRepository(ContactFormField::class)->findBy(['tenant' => $t]);
        $serialized = [];
        foreach ($fields as $f) {
            $serialized[] = [
                'id'       => $f->getId(),
                'label'    => $f->getLabel(),
                'type'     => $f->getType(),
                'options'  => $f->getOptions(),
                'required' => $f->isRequired(),
                'position' => $f->getPosition(),
            ];
        }
        return $serialized;
    }

    private function serializeContactMessages(Tenant $t): array
    {
        $messages = $this->em->getRepository(ContactMessage::class)->findBy(['tenant' => $t]);
        $serialized = [];
        foreach ($messages as $m) {
            $serialized[] = [
                'senderName'  => $m->getSenderName(),
                'senderEmail' => $m->getSenderEmail(),
                'message'     => $m->getMessage(),
                'phone'       => $m->getPhone(),
                'extraData'   => $m->getExtraData(),
                'isRead'      => $m->isRead(),
                'createdAt'   => $m->getCreatedAt()->format(\DateTimeInterface::ATOM),
            ];
        }
        $this->logger->info('[TenantExporter] Mensagens de contato serializadas.', ['count' => count($serialized)]);
        return $serialized;
    }

    private function serializeNewsletterSubscribers(Tenant $t): array
    {
        $subscribers = $this->em->getRepository(NewsletterSubscriber::class)->findBy(['tenant' => $t]);
        $serialized = [];
        foreach ($subscribers as $s) {
            $serialized[] = [
                'name'         => $s->getName(),
                'email'        => $s->getEmail(),
                'subscribedAt' => $s->getSubscribedAt()->format(\DateTimeInterface::ATOM),
            ];
        }
        $this->logger->info('[TenantExporter] Assinantes de newsletter serializados.', ['count' => count($serialized)]);
        return $serialized;
    }

    // ─────────────────────────────────────────────────────────────
    // Media file collection
    // ─────────────────────────────────────────────────────────────

    private function collectMediaFiles(Tenant $tenant, array $data, ZipArchive $zip): void
    {
        // 1. Tenant branding assets
        $this->addMediaFile($zip, 'tenant_logo',        $tenant->getLogo());
        $this->addMediaFile($zip, 'tenant_dark_logo',   $tenant->getDarkLogo());
        $this->addMediaFile($zip, 'tenant_about_image', $tenant->getAboutImage());
        $this->addMediaFile($zip, 'tenant_favicon',     $tenant->getFavicon());
        // ogImage may be a locally-uploaded file (not always an external URL)
        $this->addMediaFile($zip, 'tenant_og_image',    $tenant->getOgImage());

        // 2. Page cover images
        foreach ($data['pages'] as $p) {
            $this->addMediaFile($zip, 'page_cover_image', $p['coverImage']);
        }

        // 3. Section background images & videos
        foreach ($data['sections'] as $s) {
            $this->addMediaFile($zip, 'section_bg_image', $s['bgImage']);
            $this->addMediaFile($zip, 'section_bg_video', $s['bgVideo']);
        }

        // 4. PageBlock and all inner relation images
        foreach ($data['blocks'] as $b) {
            $this->addMediaFile($zip, 'page_block_image', $b['image']);

            // Banner carousel slides: images embedded in config JSON (config.banners[].image)
            $this->collectConfigImages($zip, $b['config'] ?? null);

            foreach ($b['galleryImages'] as $img) {
                $this->addMediaFile($zip, 'page_block_gallery', $img['filename']);
            }
            foreach ($b['testimonials'] as $t) {
                $this->addMediaFile($zip, 'testimonial_avatar', $t['avatar']);
            }
            foreach ($b['partnerLogos'] as $p) {
                $this->addMediaFile($zip, 'partner_logo', $p['logoFilename']);
            }
            foreach ($b['teamMembers'] as $m) {
                $this->addMediaFile($zip, 'team_member_image', $m['image']);
            }
        }

        // 5. HeroBanner background images
        foreach ($data['hero_banners'] as $hb) {
            $this->addMediaFile($zip, 'hero_banner', $hb['backgroundImage']);
        }
    }

    /**
     * Scans a block config array for embedded image filenames and adds them to the ZIP.
     * Handles banner carousel slides (config.banners[].image) and a generic config.image key.
     */
    private function collectConfigImages(ZipArchive $zip, mixed $config): void
    {
        if (empty($config) || !is_array($config)) {
            return;
        }

        // Banner carousel: config.banners[].image
        if (isset($config['banners']) && is_array($config['banners'])) {
            foreach ($config['banners'] as $slide) {
                if (!empty($slide['image'])) {
                    $this->logger->info('[TenantExporter] Imagem de slide de banner encontrada no config JSON.', [
                        'image' => $slide['image'],
                    ]);
                    $this->addMediaFile($zip, 'page_block_image', $slide['image']);
                }
            }
        }

        // Generic top-level config.image fallback
        if (!empty($config['image']) && is_string($config['image'])) {
            $this->addMediaFile($zip, 'page_block_image', $config['image']);
        }
    }

    private function addMediaFile(ZipArchive $zip, string $mapping, ?string $filename): void
    {
        if (empty($filename)) {
            return;
        }

        $sourcePath = sprintf('%s/public/uploads/%s/%s', $this->projectDir, $this->getMappingPath($mapping), $filename);
        if (file_exists($sourcePath) && is_file($sourcePath)) {
            $zip->addFile($sourcePath, sprintf('media/%s/%s', $mapping, $filename));
            $this->logger->debug('[TenantExporter] Arquivo de mídia adicionado ao ZIP.', [
                'mapping'     => $mapping,
                'filename'    => $filename,
                'source_path' => $sourcePath,
            ]);
        } else {
            $this->logger->warning('[TenantExporter] ALERTA: Arquivo referenciado no BD não existe no disco.', [
                'mapping'       => $mapping,
                'filename'      => $filename,
                'expected_path' => $sourcePath,
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
