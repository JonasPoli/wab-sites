<?php

namespace App\Entity;

use App\Repository\PageSectionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: PageSectionRepository::class)]
#[Vich\Uploadable]
class PageSection
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Pertence a uma Page — nullable porque pode pertencer a uma Category */
    #[ORM\ManyToOne(targetEntity: Page::class, inversedBy: 'sections')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Page $page = null;

    /** Pertence a uma Category — nullable porque pode pertencer a uma Page */
    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'sections')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Category $category = null;

    /** Displayed on secondary-color background */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $titlePart1 = null;

    /** Displayed on primary-color background */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $titlePart2 = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $position = 0;

    #[ORM\Column(options: ['default' => true])]
    private bool $active = true;

    // ── Background ────────────────────────────────────────────────────────────

    /** none | color | gradient | image | video */
    #[ORM\Column(length: 20, options: ['default' => 'none'])]
    private string $bgType = 'none';

    /** CSS color value (for 'color' type and as overlay fallback for 'video') */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $bgColor = null;

    /** CSS gradient args — e.g. "to right, #f00, #00f" */
    #[ORM\Column(length: 500, nullable: true)]
    private ?string $bgGradient = null;

    /** Background image filename */
    #[Vich\UploadableField(mapping: 'section_bg_image', fileNameProperty: 'bgImage')]
    private ?File $bgImageFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $bgImage = null;

    /** 0-100 opacity for background image */
    #[ORM\Column(options: ['default' => 100])]
    private int $bgImageOpacity = 100;

    /** CSS background-position value */
    #[ORM\Column(length: 20, options: ['default' => 'center'])]
    private string $bgImagePosition = 'center';

    /** Background video filename */
    #[Vich\UploadableField(mapping: 'section_bg_video', fileNameProperty: 'bgVideo')]
    private ?File $bgVideoFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $bgVideo = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    // ─────────────────────────────────────────────────────────────────────────

    /** @var Collection<int, PageBlock> */
    #[ORM\OneToMany(mappedBy: 'section', targetEntity: PageBlock::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $blocks;

    public function __construct()
    {
        $this->blocks = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getPage(): ?Page { return $this->page; }
    public function setPage(?Page $page): static { $this->page = $page; return $this; }

    public function getCategory(): ?Category { return $this->category; }
    public function setCategory(?Category $category): static { $this->category = $category; return $this; }

    public function getTitlePart1(): ?string { return $this->titlePart1; }
    public function setTitlePart1(?string $titlePart1): static { $this->titlePart1 = $titlePart1; return $this; }

    public function getTitlePart2(): ?string { return $this->titlePart2; }
    public function setTitlePart2(?string $titlePart2): static { $this->titlePart2 = $titlePart2; return $this; }

    public function getPosition(): int { return $this->position; }
    public function setPosition(int $position): static { $this->position = $position; return $this; }

    public function isActive(): bool { return $this->active; }
    public function setActive(bool $active): static { $this->active = $active; return $this; }

    // ── Background accessors ──────────────────────────────────────────────────

    public function getBgType(): string { return $this->bgType; }
    public function setBgType(string $bgType): static { $this->bgType = $bgType; return $this; }

    public function getBgColor(): ?string { return $this->bgColor; }
    public function setBgColor(?string $bgColor): static { $this->bgColor = $bgColor; return $this; }

    public function getBgGradient(): ?string { return $this->bgGradient; }
    public function setBgGradient(?string $bgGradient): static { $this->bgGradient = $bgGradient; return $this; }

    public function getBgImageFile(): ?File { return $this->bgImageFile; }
    public function setBgImageFile(?File $file): static
    {
        $this->bgImageFile = $file;
        if ($file) { $this->updatedAt = new \DateTimeImmutable(); }
        return $this;
    }

    public function getBgImage(): ?string { return $this->bgImage; }
    public function setBgImage(?string $bgImage): static { $this->bgImage = $bgImage; return $this; }

    public function getBgImageOpacity(): int { return $this->bgImageOpacity; }
    public function setBgImageOpacity(int $bgImageOpacity): static { $this->bgImageOpacity = $bgImageOpacity; return $this; }

    public function getBgImagePosition(): string { return $this->bgImagePosition; }
    public function setBgImagePosition(string $bgImagePosition): static { $this->bgImagePosition = $bgImagePosition; return $this; }

    public function getBgVideoFile(): ?File { return $this->bgVideoFile; }
    public function setBgVideoFile(?File $file): static
    {
        $this->bgVideoFile = $file;
        if ($file) { $this->updatedAt = new \DateTimeImmutable(); }
        return $this;
    }

    public function getBgVideo(): ?string { return $this->bgVideo; }
    public function setBgVideo(?string $bgVideo): static { $this->bgVideo = $bgVideo; return $this; }

    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }

    // ─────────────────────────────────────────────────────────────────────────

    /** @return Collection<int, PageBlock> */
    public function getBlocks(): Collection { return $this->blocks; }

    public function __clone()
    {
        $this->id = null;
        $this->blocks = new ArrayCollection();
    }

    public function __toString(): string
    {
        return trim(($this->titlePart1 ?? '') . ' ' . ($this->titlePart2 ?? '')) ?: 'Seção #' . $this->id;
    }
}
