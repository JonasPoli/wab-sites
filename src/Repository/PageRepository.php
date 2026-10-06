<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Page;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Page> */
class PageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, Page::class); }

    /** @return Page[] */
    public function findForHeader(): array { return $this->findBy(['showInHeader' => true]); }

    /** @return Page[] */
    public function findForFooter(): array { return $this->findBy(['showInFooter' => true]); }

    /**
     * Latest pages of a category (and its subcategories), newest first.
     *
     * @return Page[]
     */
    public function findLatestInCategory(Category $category, int $limit): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->andWhere('c = :cat OR c.parent = :cat')
            ->setParameter('cat', $category)
            ->orderBy('p.publishedAt', 'DESC')
            ->addOrderBy('p.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
