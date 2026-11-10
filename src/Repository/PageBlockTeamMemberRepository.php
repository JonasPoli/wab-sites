<?php
namespace App\Repository;

use App\Entity\PageBlockTeamMember;
use App\Entity\Tenant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PageBlockTeamMemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, PageBlockTeamMember::class); }

    /**
     * Membro com perfil ativo num bloco de página ou categoria do tenant
     * (o membro não tem tenant_id; o isolamento vem da Page ou da Category).
     */
    public function findBySlug(string $slug, Tenant $tenant): ?PageBlockTeamMember
    {
        return $this->createQueryBuilder('m')
            ->innerJoin('m.block', 'b')
            ->innerJoin('b.section', 's')
            ->leftJoin('s.page', 'p')
            ->leftJoin('s.category', 'c')
            ->where('m.slug = :slug')
            ->andWhere('m.detailEnabled = true')
            ->andWhere('p.tenant = :tenant OR c.tenant = :tenant')
            ->setParameter('slug', $slug)
            ->setParameter('tenant', $tenant)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
