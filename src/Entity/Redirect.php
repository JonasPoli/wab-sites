<?php

namespace App\Entity;

use App\Contract\TenantAwareInterface;
use App\Repository\RedirectRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Endereço antigo de um site respondendo 301 para o novo (site-marcenaria-pichoneri).
 * Só é aplicado quando o endereço pedido daria 404: nunca esconde uma página que existe.
 */
#[ORM\Entity(repositoryClass: RedirectRepository::class)]
#[ORM\Table(name: 'redirect')]
#[ORM\UniqueConstraint(name: 'uniq_redirect_tenant_from', columns: ['tenant_id', 'from_path'])]
class Redirect implements TenantAwareInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Tenant::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Tenant $tenant = null;

    /** Caminho antigo, normalizado (sem consulta e sem barra final) */
    #[ORM\Column(length: 255)]
    private string $fromPath = '';

    /** Caminho novo no próprio site (começa com /) */
    #[ORM\Column(length: 500)]
    private string $toPath = '';

    #[ORM\Column(options: ['default' => 0])]
    private int $hits = 0;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getTenant(): ?Tenant { return $this->tenant; }
    public function setTenant(?Tenant $tenant): static { $this->tenant = $tenant; return $this; }

    public function getFromPath(): string { return $this->fromPath; }
    public function setFromPath(string $fromPath): static { $this->fromPath = $fromPath; return $this; }

    public function getToPath(): string { return $this->toPath; }
    public function setToPath(string $toPath): static { $this->toPath = $toPath; return $this; }

    public function getHits(): int { return $this->hits; }
    public function setHits(int $hits): static { $this->hits = $hits; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
