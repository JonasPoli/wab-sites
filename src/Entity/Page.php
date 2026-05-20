<?php

namespace App\Entity;

use App\Contract\TenantAwareInterface;
use App\Repository\PageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: PageRepository::class)]
#[Vich\Uploadable]
class Page implements TenantAwareInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Tenant::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Tenant $tenant = null;

    #[ORM\Column(length: 255)]
    private string $title = '';

    #[ORM\Column(length: 255)]
    private string $slug = '';

    #[ORM\Column(options: ['default' => false])]
    private bool $showInHeader = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $showInFooter = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $seoTitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $seoDescription = null;

    /** Cover image shown in page listing cards */
    #[Vich\UploadableField(mapping: 'page_cover_image', fileNameProperty: 'coverImage')]
    private ?File $coverImageFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $coverImage = null;

    /** Display order in the pages listing */
    #[ORM\Column(options: ['default' => 0])]
    private int $position = 0;

    /** Optional category / subcategory this page belongs to */
    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'pages')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Category $category = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $showTitle = true;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    /** @var Collection<int, PageSection> */
    #[ORM\OneToMany(mappedBy: 'page', targetEntity: PageSection::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $sections;

    public function __construct()
    {
        $this->sections = new ArrayCollection();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getTenant(): ?Tenant { return $this->tenant; }
    public function setTenant(?Tenant $tenant): static { $this->tenant = $tenant; return $this; }

    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }

    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): static { $this->slug = $slug; return $this; }

    public function isShowInHeader(): bool { return $this->showInHeader; }
    public function setShowInHeader(bool $showInHeader): static { $this->showInHeader = $showInHeader; return $this; }

    public function isShowInFooter(): bool { return $this->showInFooter; }
    public function setShowInFooter(bool $showInFooter): static { $this->showInFooter = $showInFooter; return $this; }

    public function getSeoTitle(): ?string { return $this->seoTitle; }
    public function setSeoTitle(?string $seoTitle): static { $this->seoTitle = $seoTitle; return $this; }

    public function getSeoDescription(): ?string { return $this->seoDescription; }
    public function setSeoDescription(?string $seoDescription): static { $this->seoDescription = $seoDescription; return $this; }

    public function getCoverImageFile(): ?File { return $this->coverImageFile; }
    public function setCoverImageFile(?File $file): static
    {
        $this->coverImageFile = $file;
        if ($file) { $this->updatedAt = new \DateTimeImmutable(); }
        return $this;
    }

    public function getCoverImage(): ?string { return $this->coverImage; }
    public function setCoverImage(?string $coverImage): static { $this->coverImage = $coverImage; return $this; }

    public function getPosition(): int { return $this->position; }
    public function setPosition(int $position): static { $this->position = $position; return $this; }

    public function getCategory(): ?Category { return $this->category; }
    public function setCategory(?Category $category): static { $this->category = $category; return $this; }

    public function isShowTitle(): bool { return $this->showTitle; }
    public function setShowTitle(bool $showTitle): static { $this->showTitle = $showTitle; return $this; }

    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }

    /** @return Collection<int, PageSection> */
    public function getSections(): Collection { return $this->sections; }

    public function __clone()
    {
        $this->id = null;
        $this->sections = new ArrayCollection();
    }

    public function __toString(): string { return $this->title; }
}
