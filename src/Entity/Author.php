<?php

namespace App\Entity;

use App\Contract\TenantAwareInterface;
use App\Entity\Enum\AuthorStatus;
use App\Repository\AuthorRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * Jornalista (autor) de um portal de notícias. Nasce do cadastro público
 * em /jornalista/cadastro como pendente e só publica depois de aprovado
 * pelo admin do site. O login fica no User ligado (workGroup 3).
 */
#[ORM\Entity(repositoryClass: AuthorRepository::class)]
#[ORM\Table(name: 'author')]
#[ORM\UniqueConstraint(name: 'uniq_author_tenant_slug', columns: ['tenant_id', 'slug'])]
#[Vich\Uploadable]
class Author implements TenantAwareInterface
{
    public const BIO_MAX_LENGTH = 600;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Tenant::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Tenant $tenant = null;

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, unique: true, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 150)]
    private string $name = '';

    #[ORM\Column(length: 255)]
    private string $slug = '';

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $phone = null;

    /** Registro profissional (ex.: MTb) */
    #[ORM\Column(length: 60, nullable: true)]
    private ?string $professionalRegistration = null;

    /** Veículo onde atua */
    #[ORM\Column(length: 150, nullable: true)]
    private ?string $outlet = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $bio = null;

    #[Vich\UploadableField(mapping: 'author_photo', fileNameProperty: 'photo')]
    private ?File $photoFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;

    #[ORM\Column(length: 20, enumType: AuthorStatus::class)]
    private AuthorStatus $status = AuthorStatus::Pending;

    /** Aceite do uso dos dados no cadastro */
    #[ORM\Column]
    private \DateTimeImmutable $consentAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $reviewedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $reviewedBy = null;

    /** Criado pelo comando app:conteudo-exemplo; sai com --remover (nenhum formulário altera) */
    #[ORM\Column(options: ['default' => false])]
    private bool $sample = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->consentAt = $now;
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int { return $this->id; }

    public function getTenant(): ?Tenant { return $this->tenant; }
    public function setTenant(?Tenant $tenant): static { $this->tenant = $tenant; return $this; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): static { $this->slug = $slug; return $this; }

    public function getPhone(): ?string { return $this->phone; }
    public function setPhone(?string $phone): static { $this->phone = $phone; return $this; }

    public function getProfessionalRegistration(): ?string { return $this->professionalRegistration; }
    public function setProfessionalRegistration(?string $value): static { $this->professionalRegistration = $value; return $this; }

    public function getOutlet(): ?string { return $this->outlet; }
    public function setOutlet(?string $outlet): static { $this->outlet = $outlet; return $this; }

    public function getCity(): ?string { return $this->city; }
    public function setCity(?string $city): static { $this->city = $city; return $this; }

    public function getBio(): ?string { return $this->bio; }
    public function setBio(?string $bio): static { $this->bio = $bio; return $this; }

    public function getPhotoFile(): ?File { return $this->photoFile; }
    public function setPhotoFile(?File $file): static
    {
        $this->photoFile = $file;
        // Só um upload novo muda updatedAt (o Vich injeta o File ao carregar: inject_on_load)
        if ($file instanceof UploadedFile) { $this->updatedAt = new \DateTimeImmutable(); }
        return $this;
    }

    public function getPhoto(): ?string { return $this->photo; }
    public function setPhoto(?string $photo): static { $this->photo = $photo; return $this; }

    public function getStatus(): AuthorStatus { return $this->status; }
    public function setStatus(AuthorStatus $status): static { $this->status = $status; return $this; }
    public function isApproved(): bool { return $this->status === AuthorStatus::Approved; }

    public function getConsentAt(): \DateTimeImmutable { return $this->consentAt; }
    public function setConsentAt(\DateTimeImmutable $consentAt): static { $this->consentAt = $consentAt; return $this; }

    public function getReviewedAt(): ?\DateTimeImmutable { return $this->reviewedAt; }
    public function setReviewedAt(?\DateTimeImmutable $reviewedAt): static { $this->reviewedAt = $reviewedAt; return $this; }

    public function getReviewedBy(): ?User { return $this->reviewedBy; }
    public function setReviewedBy(?User $reviewedBy): static { $this->reviewedBy = $reviewedBy; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }

    public function isSample(): bool { return $this->sample; }
    public function setSample(bool $sample): static { $this->sample = $sample; return $this; }

    public function __toString(): string { return $this->name; }
}
