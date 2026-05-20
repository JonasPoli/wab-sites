<?php

namespace App\Entity;

use App\Contract\TenantAwareInterface;
use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category implements TenantAwareInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Tenant::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Tenant $tenant = null;

    /** Categoria pai — null significa que esta é uma categoria raiz */
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?self $parent = null;

    /** @var Collection<int, self> */
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['name' => 'ASC'])]
    private Collection $children;

    #[ORM\Column(length: 255)]
    private string $name = '';

    #[ORM\Column(length: 255, unique: false)]
    private string $slug = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $preTitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $showInHeader = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $showInFooter = false;

    /** Font Awesome class (e.g. 'fa-solid fa-home') or empty for no icon */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $icon = null;


    /** @var Collection<int, PageSection> */
    #[ORM\OneToMany(mappedBy: 'category', targetEntity: PageSection::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $sections;

    /** @var Collection<int, Page> */
    #[ORM\OneToMany(mappedBy: 'category', targetEntity: Page::class)]
    #[ORM\OrderBy(['position' => 'ASC', 'title' => 'ASC'])]
    private Collection $pages;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->sections = new ArrayCollection();
        $this->pages = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getTenant(): ?Tenant { return $this->tenant; }
    public function setTenant(?Tenant $tenant): static { $this->tenant = $tenant; return $this; }

    public function getParent(): ?self { return $this->parent; }
    public function setParent(?self $parent): static { $this->parent = $parent; return $this; }

    /** @return Collection<int, self> */
    public function getChildren(): Collection { return $this->children; }

    public function isSubCategory(): bool { return $this->parent !== null; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): static { $this->slug = $slug; return $this; }

    public function getPreTitle(): ?string { return $this->preTitle; }
    public function setPreTitle(?string $preTitle): static { $this->preTitle = $preTitle; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function isShowInHeader(): bool { return $this->showInHeader; }
    public function setShowInHeader(bool $showInHeader): static { $this->showInHeader = $showInHeader; return $this; }

    public function isShowInFooter(): bool { return $this->showInFooter; }
    public function setShowInFooter(bool $showInFooter): static { $this->showInFooter = $showInFooter; return $this; }

    public function getIcon(): ?string { return $this->icon; }
    public function setIcon(?string $icon): static { $this->icon = $icon; return $this; }

    /** @return Collection<int, PageSection> */
    public function getSections(): Collection { return $this->sections; }

    /** @return Collection<int, Page> */
    public function getPages(): Collection { return $this->pages; }

    public function __toString(): string { return $this->name; }
}
