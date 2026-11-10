<?php

namespace App\Repository;

use App\Entity\Author;
use App\Entity\Enum\AuthorStatus;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Autores (jornalistas). Author é TenantAware: todas as consultas abaixo
 * passam pelo filtro de tenant do domínio aberto.
 *
 * @extends ServiceEntityRepository<Author>
 */
class AuthorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, Author::class); }

    /** @return Author[] Aprovados, por nome (seletor de autor do painel). */
    public function findApproved(): array
    {
        return $this->findBy(['status' => AuthorStatus::Approved], ['name' => 'ASC']);
    }

    public function findOneApproved(int $id): ?Author
    {
        return $this->findOneBy(['id' => $id, 'status' => AuthorStatus::Approved]);
    }

    public function findOneApprovedBySlug(string $slug): ?Author
    {
        return $this->findOneBy(['slug' => $slug, 'status' => AuthorStatus::Approved]);
    }

    /** Autor do usuário logado, no site do domínio aberto. */
    public function findOneByUser(User $user): ?Author
    {
        return $this->findOneBy(['user' => $user]);
    }

    public function countPending(): int
    {
        return $this->count(['status' => AuthorStatus::Pending]);
    }

    /**
     * Lista do painel: pendentes primeiro, depois por data de cadastro (mais novos primeiro).
     *
     * @return Author[]
     */
    public function findForAdmin(?AuthorStatus $status = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->addSelect("CASE WHEN a.status = :pending THEN 0 ELSE 1 END AS HIDDEN pendingFirst")
            ->setParameter('pending', AuthorStatus::Pending->value)
            ->orderBy('pendingFirst', 'ASC')
            ->addOrderBy('a.createdAt', 'DESC');
        if ($status) {
            $qb->andWhere('a.status = :status')->setParameter('status', $status->value);
        }
        return $qb->getQuery()->getResult();
    }

    public function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        $qb = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.slug = :slug')
            ->setParameter('slug', $slug);
        if ($ignoreId) {
            $qb->andWhere('a.id != :id')->setParameter('id', $ignoreId);
        }
        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }
}
