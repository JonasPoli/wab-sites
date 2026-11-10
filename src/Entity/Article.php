<?php

namespace App\Entity;

use App\Contract\TenantAwareInterface;
use App\Entity\Enum\ArticleStatus;
use App\Repository\ArticleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * Notícia de um portal (tema noticias). Visível ao público só quando está
 * publicada e com publishedAt no passado (ArticleRepository::visibleQueryBuilder()).
 */
#[ORM\Entity(repositoryClass: ArticleRepository::class)]
#[ORM\Table(name: 'article')]
#[ORM\UniqueConstraint(name: 'uniq_article_tenant_slug', columns: ['tenant_id', 'slug'])]
#[ORM\Index(name: 'idx_article_listing', columns: ['tenant_id', 'status', 'published_at'])]
#[Vich\Uploadable]
class Article implements TenantAwareInterface
{
    public const MAX_RELATED_LINKS = 3;

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

    /** Chapéu */
    #[ORM\Column(length: 120, nullable: true)]
    private ?string $kicker = null;

    /** Linha fina (também é a meta description) */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $summary = null;

    /** HTML já sanitizado (sanitizador article_body) */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $body = null;

    /** Editoria principal */
    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Category $category = null;

    /**
     * Outras categorias (sem a principal).
     *
     * @var Collection<int, Category>
     */
    #[ORM\ManyToMany(targetEntity: Category::class)]
    #[ORM\JoinTable(name: 'article_category')]
    #[ORM\JoinColumn(name: 'article_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'category_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Collection $categories;

    #[ORM\ManyToOne(targetEntity: Author::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Author $author = null;

    #[Vich\UploadableField(mapping: 'article_image', fileNameProperty: 'coverImage')]
    private ?File $coverImageFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $coverImage = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $coverCaption = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $coverCredit = null;

    #[ORM\Column(length: 20, enumType: ArticleStatus::class)]
    private ArticleStatus $status = ArticleStatus::Draft;

    #[ORM\Column]
    private \DateTimeImmutable $publishedAt;

    /** @var list<array{label: string, url: string}> */
    #[ORM\Column(type: Types::JSON)]
    private array $relatedLinks = [];

    /** Marcada para o quadro "Leia também" (só a equipe do painel altera) */
    #[ORM\Column(options: ['default' => false])]
    private bool $featured = false;

    #[ORM\Column(options: ['default' => 0])]
    private int $viewCount = 0;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->categories = new ArrayCollection();
        $this->publishedAt = $now;
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int { return $this->id; }

    public function getTenant(): ?Tenant { return $this->tenant; }
    public function setTenant(?Tenant $tenant): static { $this->tenant = $tenant; return $this; }

    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }

    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): static { $this->slug = $slug; return $this; }

    public function getKicker(): ?string { return $this->kicker; }
    public function setKicker(?string $kicker): static { $this->kicker = $kicker; return $this; }

    public function getSummary(): ?string { return $this->summary; }
    public function setSummary(?string $summary): static { $this->summary = $summary; return $this; }

    public function getBody(): ?string { return $this->body; }
    public function setBody(?string $body): static { $this->body = $body; return $this; }

    public function getCategory(): ?Category { return $this->category; }
    public function setCategory(?Category $category): static { $this->category = $category; return $this; }

    /** @return Collection<int, Category> */
    public function getCategories(): Collection { return $this->categories; }

    /** @param iterable<Category> $categories */
    public function setCategories(iterable $categories): static
    {
        $this->categories->clear();
        foreach ($categories as $category) {
            if (!$this->categories->contains($category)) {
                $this->categories->add($category);
            }
        }
        return $this;
    }

    public function hasCategory(Category $category): bool
    {
        return $this->category === $category || $this->categories->contains($category);
    }

    public function getAuthor(): ?Author { return $this->author; }
    public function setAuthor(?Author $author): static { $this->author = $author; return $this; }

    public function getCoverImageFile(): ?File { return $this->coverImageFile; }
    public function setCoverImageFile(?File $file): static
    {
        $this->coverImageFile = $file;
        // Só um upload novo muda updatedAt (o Vich injeta o File ao carregar: inject_on_load)
        if ($file instanceof UploadedFile) { $this->updatedAt = new \DateTimeImmutable(); }
        return $this;
    }

    public function getCoverImage(): ?string { return $this->coverImage; }
    public function setCoverImage(?string $coverImage): static { $this->coverImage = $coverImage; return $this; }

    public function getCoverCaption(): ?string { return $this->coverCaption; }
    public function setCoverCaption(?string $coverCaption): static { $this->coverCaption = $coverCaption; return $this; }

    public function getCoverCredit(): ?string { return $this->coverCredit; }
    public function setCoverCredit(?string $coverCredit): static { $this->coverCredit = $coverCredit; return $this; }

    public function getStatus(): ArticleStatus { return $this->status; }
    public function setStatus(ArticleStatus $status): static { $this->status = $status; return $this; }
    public function isPublished(): bool { return $this->status === ArticleStatus::Published; }

    /** Publicada e com data de publicação no passado. */
    public function isVisible(?\DateTimeImmutable $now = null): bool
    {
        return $this->isPublished() && $this->publishedAt <= ($now ?? new \DateTimeImmutable());
    }

    public function getPublishedAt(): \DateTimeImmutable { return $this->publishedAt; }
    public function setPublishedAt(\DateTimeImmutable $publishedAt): static { $this->publishedAt = $publishedAt; return $this; }

    /** @return list<array{label: string, url: string}> */
    public function getRelatedLinks(): array { return $this->relatedLinks; }
    /** @param list<array{label: string, url: string}> $relatedLinks */
    public function setRelatedLinks(array $relatedLinks): static
    {
        $this->relatedLinks = array_slice(array_values($relatedLinks), 0, self::MAX_RELATED_LINKS);
        return $this;
    }

    public function isFeatured(): bool { return $this->featured; }
    public function setFeatured(bool $featured): static { $this->featured = $featured; return $this; }

    public function getViewCount(): int { return $this->viewCount; }
    public function setViewCount(int $viewCount): static { $this->viewCount = $viewCount; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }

    public function __toString(): string { return $this->title; }
}
