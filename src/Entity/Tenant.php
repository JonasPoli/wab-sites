<?php

namespace App\Entity;

use App\Repository\TenantRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: TenantRepository::class)]
#[Vich\Uploadable]
class Tenant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private string $domain = '';

    #[ORM\Column(length: 255)]
    private string $name = '';

    #[Vich\UploadableField(mapping: 'tenant_logo', fileNameProperty: 'logo')]
    private ?File $logoFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

    #[Vich\UploadableField(mapping: 'tenant_dark_logo', fileNameProperty: 'darkLogo')]
    private ?File $darkLogoFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $darkLogo = null;

    #[ORM\Column(length: 7, nullable: true)]
    private ?string $primaryColor = '#0044cc';

    #[ORM\Column(length: 7, nullable: true)]
    private ?string $secondaryColor = '#ffaa00';

    #[ORM\Column(length: 7, nullable: true)]
    private ?string $primaryColorDark = '#3b82f6';

    #[ORM\Column(length: 7, nullable: true)]
    private ?string $secondaryColorDark = '#fbbf24';

    #[ORM\Column(length: 7, nullable: true, options: ['default' => '#ffffff'])]
    private ?string $bgColorLight1 = '#ffffff';

    #[ORM\Column(length: 7, nullable: true, options: ['default' => '#f8fafc'])]
    private ?string $bgColorLight2 = '#f8fafc';

    #[ORM\Column(length: 7, nullable: true, options: ['default' => '#0d0f1a'])]
    private ?string $bgColorDark1 = '#0d0f1a';

    #[ORM\Column(length: 7, nullable: true, options: ['default' => '#131625'])]
    private ?string $bgColorDark2 = '#131625';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contactEmail = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $youtubeLink = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $instagramLink = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $facebookLink = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $whatsappLink = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $linkedinLink = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $aboutText = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $aboutFullText = null;

    #[Vich\UploadableField(mapping: 'tenant_about_image', fileNameProperty: 'aboutImage')]
    private ?File $aboutImageFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $aboutImage = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $mapsEmbedUrl = null;


    /** Which visual theme this tenant uses */
    #[ORM\Column(length: 50, options: ['default' => 'wab'])]
    private string $theme = 'wab';

    /** Page used as the site home */
    #[ORM\ManyToOne(targetEntity: Page::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Page $homePage = null;

    /** Favicon file upload */
    #[\Vich\UploaderBundle\Mapping\Annotation\UploadableField(mapping: 'tenant_favicon', fileNameProperty: 'favicon')]
    private ?\Symfony\Component\HttpFoundation\File\File $faviconFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $favicon = null;

    /** SEO: default page title */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $seoTitle = null;

    /** SEO: default meta description */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $seoDescription = null;

    /** SEO: meta keywords */
    #[ORM\Column(length: 500, nullable: true)]
    private ?string $seoKeywords = null;

    /** SEO: Open Graph image URL */
    #[ORM\Column(length: 500, nullable: true)]
    private ?string $ogImage = null;

    /** Google Analytics Measurement ID (e.g. G-XXXXXXXXXX) */
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $googleAnalyticsId = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $fontSettings = [];

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $navigationSettings = [];

    #[ORM\Column(options: ['default' => true])]
    private bool $showSectionTitles = true;

    #[ORM\Column(options: ['default' => false])]
    private bool $landingPageMode = false;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getDomain(): string { return $this->domain; }
    public function setDomain(string $domain): static { $this->domain = $domain; return $this; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getLogoFile(): ?File { return $this->logoFile; }
    public function setLogoFile(?File $logoFile): static
    {
        $this->logoFile = $logoFile;
        if ($logoFile !== null) {
            $this->updatedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function getLogo(): ?string { return $this->logo; }
    public function setLogo(?string $logo): static { $this->logo = $logo; return $this; }

    public function getPrimaryColor(): ?string { return $this->primaryColor; }
    public function setPrimaryColor(?string $primaryColor): static { $this->primaryColor = $primaryColor; return $this; }

    public function getSecondaryColor(): ?string { return $this->secondaryColor; }
    public function setSecondaryColor(?string $secondaryColor): static { $this->secondaryColor = $secondaryColor; return $this; }

    public function getPrimaryColorDark(): ?string { return $this->primaryColorDark; }
    public function setPrimaryColorDark(?string $primaryColorDark): static { $this->primaryColorDark = $primaryColorDark; return $this; }

    public function getSecondaryColorDark(): ?string { return $this->secondaryColorDark; }
    public function setSecondaryColorDark(?string $secondaryColorDark): static { $this->secondaryColorDark = $secondaryColorDark; return $this; }

    public function getBgColorLight1(): ?string { return $this->bgColorLight1; }
    public function setBgColorLight1(?string $color): static { $this->bgColorLight1 = $color; return $this; }

    public function getBgColorLight2(): ?string { return $this->bgColorLight2; }
    public function setBgColorLight2(?string $color): static { $this->bgColorLight2 = $color; return $this; }

    public function getBgColorDark1(): ?string { return $this->bgColorDark1; }
    public function setBgColorDark1(?string $color): static { $this->bgColorDark1 = $color; return $this; }

    public function getBgColorDark2(): ?string { return $this->bgColorDark2; }
    public function setBgColorDark2(?string $color): static { $this->bgColorDark2 = $color; return $this; }

    public function getContactEmail(): ?string { return $this->contactEmail; }
    public function setContactEmail(?string $contactEmail): static { $this->contactEmail = $contactEmail; return $this; }

    public function getYoutubeLink(): ?string { return $this->youtubeLink; }
    public function setYoutubeLink(?string $youtubeLink): static { $this->youtubeLink = $youtubeLink; return $this; }

    public function getInstagramLink(): ?string { return $this->instagramLink; }
    public function setInstagramLink(?string $instagramLink): static { $this->instagramLink = $instagramLink; return $this; }

    public function getFacebookLink(): ?string { return $this->facebookLink; }
    public function setFacebookLink(?string $facebookLink): static { $this->facebookLink = $facebookLink; return $this; }

    public function getWhatsappLink(): ?string { return $this->whatsappLink; }
    public function setWhatsappLink(?string $whatsappLink): static { $this->whatsappLink = $whatsappLink; return $this; }

    public function getLinkedinLink(): ?string { return $this->linkedinLink; }
    public function setLinkedinLink(?string $linkedinLink): static { $this->linkedinLink = $linkedinLink; return $this; }

    public function getAboutText(): ?string { return $this->aboutText; }
    public function setAboutText(?string $aboutText): static { $this->aboutText = $aboutText; return $this; }

    public function getAboutFullText(): ?string { return $this->aboutFullText; }
    public function setAboutFullText(?string $t): static { $this->aboutFullText = $t; return $this; }

    public function getAboutImageFile(): ?File { return $this->aboutImageFile; }
    public function setAboutImageFile(?File $f): static
    {
        $this->aboutImageFile = $f;
        if ($f) { $this->updatedAt = new \DateTimeImmutable(); }
        return $this;
    }

    public function getAboutImage(): ?string { return $this->aboutImage; }
    public function setAboutImage(?string $aboutImage): static { $this->aboutImage = $aboutImage; return $this; }

    public function getAddress(): ?string { return $this->address; }
    public function setAddress(?string $address): static { $this->address = $address; return $this; }

    public function getPhone(): ?string { return $this->phone; }
    public function setPhone(?string $phone): static { $this->phone = $phone; return $this; }

    public function getMapsEmbedUrl(): ?string { return $this->mapsEmbedUrl; }
    public function setMapsEmbedUrl(?string $url): static { $this->mapsEmbedUrl = $url; return $this; }


    public function getTheme(): string { return $this->theme; }
    public function setTheme(string $theme): static { $this->theme = $theme; return $this; }

    public function getHomePage(): ?Page { return $this->homePage; }
    public function setHomePage(?Page $homePage): static { $this->homePage = $homePage; return $this; }

    public function getFaviconFile(): ?\Symfony\Component\HttpFoundation\File\File { return $this->faviconFile; }
    public function setFaviconFile(?\Symfony\Component\HttpFoundation\File\File $file): static
    {
        $this->faviconFile = $file;
        if ($file) { $this->updatedAt = new \DateTimeImmutable(); }
        return $this;
    }

    public function getFavicon(): ?string { return $this->favicon; }
    public function setFavicon(?string $favicon): static { $this->favicon = $favicon; return $this; }

    public function getSeoTitle(): ?string { return $this->seoTitle; }
    public function setSeoTitle(?string $seoTitle): static { $this->seoTitle = $seoTitle; return $this; }

    public function getSeoDescription(): ?string { return $this->seoDescription; }
    public function setSeoDescription(?string $seoDescription): static { $this->seoDescription = $seoDescription; return $this; }

    public function getSeoKeywords(): ?string { return $this->seoKeywords; }
    public function setSeoKeywords(?string $seoKeywords): static { $this->seoKeywords = $seoKeywords; return $this; }

    public function getOgImage(): ?string { return $this->ogImage; }
    public function setOgImage(?string $ogImage): static { $this->ogImage = $ogImage; return $this; }

    public function getGoogleAnalyticsId(): ?string { return $this->googleAnalyticsId; }
    public function setGoogleAnalyticsId(?string $googleAnalyticsId): static { $this->googleAnalyticsId = $googleAnalyticsId; return $this; }

    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }

    public function getFontSettings(): ?array
    {
        return $this->fontSettings ?? [];
    }

    public function setFontSettings(?array $fontSettings): static
    {
        $this->fontSettings = $fontSettings;
        return $this;
    }

    public function getNavigationSettings(): ?array
    {
        return $this->navigationSettings ?? [
            'showMenuIcons' => true,
            'topBarEnabled' => false,
            'topBarLeft'    => [],
            'topBarRight'   => [],
        ];
    }

    public function setNavigationSettings(?array $navigationSettings): static
    {
        $this->navigationSettings = $navigationSettings;
        return $this;
    }

    public function isShowSectionTitles(): bool
    {
        return $this->showSectionTitles;
    }

    public function setShowSectionTitles(bool $showSectionTitles): static
    {
        $this->showSectionTitles = $showSectionTitles;
        return $this;
    }

    public function getDarkLogoFile(): ?File { return $this->darkLogoFile; }
    public function setDarkLogoFile(?File $darkLogoFile): static
    {
        $this->darkLogoFile = $darkLogoFile;
        if ($darkLogoFile !== null) {
            $this->updatedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function getDarkLogo(): ?string { return $this->darkLogo; }
    public function setDarkLogo(?string $darkLogo): static { $this->darkLogo = $darkLogo; return $this; }

    public function isLandingPageMode(): bool
    {
        return $this->landingPageMode;
    }

    public function setLandingPageMode(bool $landingPageMode): static
    {
        $this->landingPageMode = $landingPageMode;
        return $this;
    }


    /**
     * Exclude $logoFile (Symfony\Component\HttpFoundation\File\File) from serialization.
     * VichUploader injects a File object when inject_on_load=true, but File objects
     * cannot be serialized — which causes session failures during _switch_user / impersonation.
     */
    public function __serialize(): array
    {
        return [
            'id'               => $this->id,
            'domain'           => $this->domain,
            'name'             => $this->name,
            'logo'             => $this->logo,
            'primaryColor'     => $this->primaryColor,
            'secondaryColor'   => $this->secondaryColor,
            'primaryColorDark'   => $this->primaryColorDark,
            'secondaryColorDark' => $this->secondaryColorDark,
            'bgColorLight1'    => $this->bgColorLight1,
            'bgColorLight2'    => $this->bgColorLight2,
            'bgColorDark1'     => $this->bgColorDark1,
            'bgColorDark2'     => $this->bgColorDark2,
            'contactEmail'     => $this->contactEmail,
            'youtubeLink'      => $this->youtubeLink,
            'instagramLink'    => $this->instagramLink,
            'facebookLink'     => $this->facebookLink,
            'whatsappLink'     => $this->whatsappLink,
            'linkedinLink'     => $this->linkedinLink,
            'aboutText'        => $this->aboutText,
            'aboutFullText'    => $this->aboutFullText,
            'aboutImage'       => $this->aboutImage,
            'address'          => $this->address,
            'phone'            => $this->phone,
            'mapsEmbedUrl'     => $this->mapsEmbedUrl,

            'theme'            => $this->theme,
            'favicon'          => $this->favicon,
            'seoTitle'         => $this->seoTitle,
            'seoDescription'   => $this->seoDescription,
            'seoKeywords'      => $this->seoKeywords,
            'ogImage'          => $this->ogImage,
            'fontSettings'       => $this->fontSettings,
            'navigationSettings' => $this->navigationSettings,
            'showSectionTitles'  => $this->showSectionTitles,
            'landingPageMode'    => $this->landingPageMode,
            'darkLogo'           => $this->darkLogo,
            'homePageId'         => $this->homePage?->getId(),
            'googleAnalyticsId'  => $this->googleAnalyticsId,
            'updatedAt'          => $this->updatedAt,
            // File objects intentionally excluded — not serializable
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->id                = $data['id'];
        $this->domain            = $data['domain'];
        $this->name              = $data['name'];
        $this->logo              = $data['logo'];
        $this->primaryColor      = $data['primaryColor'];
        $this->secondaryColor    = $data['secondaryColor'];
        $this->primaryColorDark   = $data['primaryColorDark'] ?? '#3b82f6';
        $this->secondaryColorDark = $data['secondaryColorDark'] ?? '#fbbf24';
        $this->bgColorLight1     = $data['bgColorLight1'] ?? '#ffffff';
        $this->bgColorLight2     = $data['bgColorLight2'] ?? '#f8fafc';
        $this->bgColorDark1      = $data['bgColorDark1'] ?? '#0d0f1a';
        $this->bgColorDark2      = $data['bgColorDark2'] ?? '#131625';
        $this->contactEmail      = $data['contactEmail'];
        $this->youtubeLink       = $data['youtubeLink'];
        $this->instagramLink     = $data['instagramLink'];
        $this->facebookLink      = $data['facebookLink'] ?? null;
        $this->whatsappLink      = $data['whatsappLink'] ?? null;
        $this->linkedinLink      = $data['linkedinLink'] ?? null;
        $this->aboutText         = $data['aboutText'] ?? null;
        $this->aboutFullText     = $data['aboutFullText'] ?? null;
        $this->aboutImage        = $data['aboutImage'] ?? null;
        $this->address           = $data['address'] ?? null;
        $this->phone             = $data['phone'] ?? null;
        $this->mapsEmbedUrl      = $data['mapsEmbedUrl'] ?? null;

        $this->theme             = $data['theme'];
        $this->favicon           = $data['favicon'] ?? null;
        $this->seoTitle          = $data['seoTitle'] ?? null;
        $this->seoDescription    = $data['seoDescription'] ?? null;
        $this->seoKeywords       = $data['seoKeywords'] ?? null;
        $this->ogImage           = $data['ogImage'] ?? null;
        $this->fontSettings      = $data['fontSettings'] ?? [];
        $this->navigationSettings = $data['navigationSettings'] ?? [];
        $this->showSectionTitles = $data['showSectionTitles'] ?? true;
        $this->landingPageMode   = $data['landingPageMode'] ?? false;
        $this->darkLogo          = $data['darkLogo'] ?? null;
        $this->googleAnalyticsId = $data['googleAnalyticsId'] ?? null;
        $this->updatedAt         = $data['updatedAt'];
        $this->logoFile          = null;
        $this->darkLogoFile      = null;
        $this->aboutImageFile    = null;
        $this->faviconFile       = null;
        $this->homePage          = null; // lazy-loaded separately if needed
    }

    public function __toString(): string { return $this->name; }
}
