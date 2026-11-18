<?php

namespace App\Repository;

use App\Entity\Redirect;
use App\Entity\Tenant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Redirect> */
class RedirectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Redirect::class);
    }

    /** Redirecionamento do caminho já normalizado, só no site informado. */
    public function findOneByPath(Tenant $tenant, string $path): ?Redirect
    {
        return $this->findOneBy(['tenant' => $tenant, 'fromPath' => $path]);
    }

    public function incrementHits(int $id): void
    {
        $this->getEntityManager()->createQuery('UPDATE App\Entity\Redirect r SET r.hits = r.hits + 1 WHERE r.id = :id')
            ->setParameter('id', $id)
            ->execute();
    }

    /** @return Redirect[] */
    public function findForAdmin(Tenant $tenant, int $page = 1, int $perPage = 100): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.tenant = :t')->setParameter('t', $tenant)
            ->orderBy('r.fromPath', 'ASC')
            ->setFirstResult(max(0, ($page - 1) * $perPage))
            ->setMaxResults($perPage)
            ->getQuery()->getResult();
    }

    public function countForTenant(Tenant $tenant): int
    {
        return (int) $this->createQueryBuilder('r')->select('COUNT(r.id)')
            ->andWhere('r.tenant = :t')->setParameter('t', $tenant)
            ->getQuery()->getSingleScalarResult();
    }
}
