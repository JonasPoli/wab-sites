<?php

namespace App\Repository;

use App\Entity\PageSection;
use App\Entity\Tenant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * PageSection não tem tenant_id: o isolamento vem da Page ou da Category a que a seção pertence.
 * Os métodos *ForTenant recebem o tenant explicitamente e não dependem do tenant_filter.
 *
 * @extends ServiceEntityRepository<PageSection>
 */
class PageSectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, PageSection::class); }

    /** Seção de página ou categoria do tenant; null para outro tenant ou seção solta. */
    public function findOneForTenant(int $id, Tenant $tenant): ?PageSection
    {
        return $this->forTenant($tenant)
            ->andWhere('s.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Só as seções do tenant entre os ids pedidos (reordenação).
     *
     * @param int[] $ids
     * @return PageSection[]
     */
    public function findForTenantByIds(array $ids, Tenant $tenant): array
    {
        if ($ids === []) {
            return [];
        }

        return $this->forTenant($tenant)
            ->andWhere('s.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }

    private function forTenant(Tenant $tenant): QueryBuilder
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.page', 'p')
            ->leftJoin('s.category', 'c')
            ->where('p.tenant = :tenant OR c.tenant = :tenant')
            ->setParameter('tenant', $tenant);
    }
}
