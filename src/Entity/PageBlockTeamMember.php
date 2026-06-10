<?php
namespace App\Entity;

use App\Repository\PageBlockTeamMemberRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: PageBlockTeamMemberRepository::class)]
#[Vich\Uploadable]
class PageBlockTeamMember
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: PageBlock::class, inversedBy: 'teamMembers')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?PageBlock $block = null;

    #[ORM\Column(length: 255)]
    private string $name = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $role = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $bio = null;

    #[Vich\UploadableField(mapping: 'team_member_image', fileNameProperty: 'image')]
    private ?File $imageFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $linkedinUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $facebookUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $instagramUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $whatsappUrl = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $summary = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $experience = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $address = null;

    /** [{title: string, content: string}] */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $customSections = null;

    /** [{name: string, url?: string}] */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $practiceAreas = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $detailEnabled = false;

    #[ORM\Column(length: 20, options: ['default' => 'classic'])]
    private string $detailLayout = 'classic';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $slug = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $position = 0;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getBlock(): ?PageBlock { return $this->block; }
    public function setBlock(?PageBlock $block): static { $this->block = $block; return $this; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getRole(): ?string { return $this->role; }
    public function setRole(?string $role): static { $this->role = $role; return $this; }

    public function getBio(): ?string { return $this->bio; }
    public function setBio(?string $bio): static { $this->bio = $bio; return $this; }

    public function getImageFile(): ?File { return $this->imageFile; }
    public function setImageFile(?File $file): static
    {
        $this->imageFile = $file;
        if ($file) { $this->updatedAt = new \DateTimeImmutable(); }
        return $this;
    }

    public function getImage(): ?string { return $this->image; }
    public function setImage(?string $image): static { $this->image = $image; return $this; }

    public function getLinkedinUrl(): ?string { return $this->linkedinUrl; }
    public function setLinkedinUrl(?string $linkedinUrl): static { $this->linkedinUrl = $linkedinUrl; return $this; }

    public function getFacebookUrl(): ?string { return $this->facebookUrl; }
    public function setFacebookUrl(?string $facebookUrl): static { $this->facebookUrl = $facebookUrl; return $this; }

    public function getInstagramUrl(): ?string { return $this->instagramUrl; }
    public function setInstagramUrl(?string $instagramUrl): static { $this->instagramUrl = $instagramUrl; return $this; }

    public function getWhatsappUrl(): ?string { return $this->whatsappUrl; }
    public function setWhatsappUrl(?string $whatsappUrl): static { $this->whatsappUrl = $whatsappUrl; return $this; }

    public function getPhone(): ?string { return $this->phone; }
    public function setPhone(?string $phone): static { $this->phone = $phone; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $email): static { $this->email = $email; return $this; }

    public function getSummary(): ?string { return $this->summary; }
    public function setSummary(?string $summary): static { $this->summary = $summary; return $this; }

    public function getExperience(): ?string { return $this->experience; }
    public function setExperience(?string $experience): static { $this->experience = $experience; return $this; }

    public function getAddress(): ?string { return $this->address; }
    public function setAddress(?string $address): static { $this->address = $address; return $this; }

    public function getCustomSections(): ?array { return $this->customSections; }
    public function setCustomSections(?array $customSections): static { $this->customSections = $customSections; return $this; }

    public function getPracticeAreas(): ?array { return $this->practiceAreas; }
    public function setPracticeAreas(?array $practiceAreas): static { $this->practiceAreas = $practiceAreas; return $this; }

    public function isDetailEnabled(): bool { return $this->detailEnabled; }
    public function setDetailEnabled(bool $detailEnabled): static { $this->detailEnabled = $detailEnabled; return $this; }

    public function getDetailLayout(): string { return $this->detailLayout; }
    public function setDetailLayout(string $detailLayout): static { $this->detailLayout = $detailLayout; return $this; }

    public function getSlug(): ?string { return $this->slug; }
    public function setSlug(?string $slug): static { $this->slug = $slug; return $this; }

    public function getPosition(): int { return $this->position; }
    public function setPosition(int $position): static { $this->position = $position; return $this; }

    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }
}
