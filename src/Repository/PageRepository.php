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
    /**
     * Busca do portal: páginas com todas as palavras no título, em ordem alfabética.
     *
     * @param list<string> $words
     * @return Page[]
     */
    public function searchByTitle(array $words, int $limit = 5): array
    {
        $qb = $this->createQueryBuilder('p')->orderBy('p.title', 'ASC')->setMaxResults($limit);
        foreach (array_values($words) as $i => $word) {
            $qb->andWhere("p.title LIKE :w$i")->setParameter("w$i", '%' . addcslashes($word, '%_\\') . '%');
        }
        return $qb->getQuery()->getResult();
    }

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
