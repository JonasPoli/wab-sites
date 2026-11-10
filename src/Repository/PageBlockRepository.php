<?php

namespace App\Repository;

use App\Entity\PageBlock;
use App\Entity\Tenant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * PageBlock não tem tenant_id: o isolamento vem da seção, e dela da Page ou da Category.
 * Os métodos *ForTenant recebem o tenant explicitamente e não dependem do tenant_filter.
 *
 * @extends ServiceEntityRepository<PageBlock>
 */
class PageBlockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, PageBlock::class); }

    /** Bloco de uma seção de página ou categoria do tenant; null para outro tenant. */
    public function findOneForTenant(int $id, Tenant $tenant): ?PageBlock
    {
        return $this->forTenant($tenant)
            ->andWhere('b.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Só os blocos do tenant entre os ids pedidos (reordenação).
     *
     * @param int[] $ids
     * @return PageBlock[]
     */
    public function findForTenantByIds(array $ids, Tenant $tenant): array
    {
        if ($ids === []) {
            return [];
        }

        return $this->forTenant($tenant)
            ->andWhere('b.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }

    private function forTenant(Tenant $tenant): QueryBuilder
    {
        return $this->createQueryBuilder('b')
            ->innerJoin('b.section', 's')
            ->leftJoin('s.page', 'p')
            ->leftJoin('s.category', 'c')
            ->where('p.tenant = :tenant OR c.tenant = :tenant')
            ->setParameter('tenant', $tenant);
    }
}
