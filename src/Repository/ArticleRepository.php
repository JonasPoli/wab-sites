<?php

namespace App\Repository;

use App\Entity\Article;
use App\Entity\Author;
use App\Entity\Category;
use App\Entity\Enum\ArticleStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Notícias. Article é TenantAware: todas as consultas ORM abaixo passam pelo
 * filtro de tenant do domínio aberto. "Visível" = publicada e com publishedAt
 * igual ou anterior ao momento atual (visibleQueryBuilder()).
 *
 * @extends ServiceEntityRepository<Article>
 */
class ArticleRepository extends ServiceEntityRepository
{
    public const PER_PAGE = 20;

    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, Article::class); }

    /** Única definição de "notícia visível" usada pelo site, sitemap e quadros. */
    public function visibleQueryBuilder(string $alias = 'a'): QueryBuilder
    {
        return $this->createQueryBuilder($alias)
            ->andWhere("$alias.status = :visibleStatus")
            ->andWhere("$alias.publishedAt <= :visibleNow")
            ->setParameter('visibleStatus', ArticleStatus::Published->value)
            ->setParameter('visibleNow', new \DateTimeImmutable(), Types::DATETIME_IMMUTABLE);
    }

    /**
     * Busca do portal: notícias visíveis com todas as palavras no título, chapéu, linha fina
     * ou texto (o LIKE da collation ignora maiúsculas e acentos); título que casa vem primeiro.
     *
     * @param list<string> $words
     * @return Article[]
     */
    public function searchVisible(array $words, int $page = 1, int $perPage = self::PER_PAGE): array
    {
        $qb = $this->searchQueryBuilder($words);
        $titleHit = [];
        foreach (array_keys($words) as $i) {
            $titleHit[] = "a.title LIKE :w$i";
        }
        return $qb
            ->addSelect('CASE WHEN ' . implode(' OR ', $titleHit) . ' THEN 0 ELSE 1 END AS HIDDEN titleFirst')
            ->orderBy('titleFirst', 'ASC')
            ->addOrderBy('a.publishedAt', 'DESC')
            ->addOrderBy('a.id', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }

    /** @param list<string> $words */
    public function countSearchVisible(array $words): int
    {
        return (int) $this->searchQueryBuilder($words)->select('COUNT(a.id)')->getQuery()->getSingleScalarResult();
    }

    /** @param list<string> $words */
    private function searchQueryBuilder(array $words): QueryBuilder
    {
        $qb = $this->visibleQueryBuilder();
        foreach (array_values($words) as $i => $word) {
            $qb->andWhere("(a.title LIKE :w$i OR a.kicker LIKE :w$i OR a.summary LIKE :w$i OR a.body LIKE :w$i)")
                ->setParameter("w$i", '%' . addcslashes($word, '%_\\') . '%');
        }
        return $qb;
    }

    private function orderNewestFirst(QueryBuilder $qb, string $alias = 'a'): QueryBuilder
    {
        return $qb->orderBy("$alias.publishedAt", 'DESC')->addOrderBy("$alias.id", 'DESC');
    }

    /**
     * Restringe às notícias que têm a categoria (ou uma subcategoria dela)
     * como editoria principal ou entre as outras categorias, sem repetir.
     */
    private function inCategory(QueryBuilder $qb, Category $category, string $alias = 'a'): QueryBuilder
    {
        $ids = [$category->getId()];
        foreach ($category->getChildren() as $child) {
            $ids[] = $child->getId();
        }

        $sub = $this->getEntityManager()->createQueryBuilder()
            ->select('ac.id')
            ->from(Article::class, 'ac')
            ->innerJoin('ac.categories', 'acc')
            ->where('acc.id IN (:catIds)')
            ->getDQL();

        return $qb
            ->andWhere($qb->expr()->orX(
                "IDENTITY($alias.category) IN (:catIds)",
                $qb->expr()->in("$alias.id", $sub),
            ))
            ->setParameter('catIds', $ids);
    }

    /** @return Article[] */
    public function findVisiblePage(?Category $category, int $page = 1, int $perPage = self::PER_PAGE): array
    {
        $qb = $this->visibleQueryBuilder();
        if ($category) {
            $this->inCategory($qb, $category);
        }
        return $this->orderNewestFirst($qb)
            ->setFirstResult(max(0, ($page - 1) * $perPage))
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }

    public function countVisible(?Category $category = null): int
    {
        $qb = $this->visibleQueryBuilder()->select('COUNT(a.id)');
        if ($category) {
            $this->inCategory($qb, $category);
        }
        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /** @return Article[] */
    public function findVisibleByAuthor(Author $author, int $page = 1, int $perPage = self::PER_PAGE): array
    {
        return $this->orderNewestFirst(
            $this->visibleQueryBuilder()->andWhere('a.author = :author')->setParameter('author', $author)
        )
            ->setFirstResult(max(0, ($page - 1) * $perPage))
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }

    public function countVisibleByAuthor(Author $author): int
    {
        return (int) $this->visibleQueryBuilder()
            ->select('COUNT(a.id)')
            ->andWhere('a.author = :author')
            ->setParameter('author', $author)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findOneVisibleBySlug(string $slug): ?Article
    {
        return $this->visibleQueryBuilder()
            ->andWhere('a.slug = :slug')
            ->setParameter('slug', $slug)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /** Notícia visível imediatamente mais antiga ("Próximo"). */
    public function findNextOlder(Article $article): ?Article
    {
        $qb = $this->visibleQueryBuilder();
        return $this->orderNewestFirst(
            $qb->andWhere($qb->expr()->orX(
                'a.publishedAt < :refDate',
                $qb->expr()->andX('a.publishedAt = :refDate', 'a.id < :refId'),
            ))
                ->setParameter('refDate', $article->getPublishedAt(), Types::DATETIME_IMMUTABLE)
                ->setParameter('refId', $article->getId())
        )
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * "Mais lidas": visíveis publicadas nos últimos $days dias, com pelo menos
     * uma visualização, da mais vista para a menos vista (mais recente no empate).
     *
     * @return Article[]
     */
    public function findMostRead(int $days = 7, int $limit = 5): array
    {
        return $this->visibleQueryBuilder()
            ->andWhere('a.publishedAt >= :since')
            ->andWhere('a.viewCount > 0')
            ->setParameter('since', new \DateTimeImmutable(sprintf('-%d days', $days)), Types::DATETIME_IMMUTABLE)
            ->orderBy('a.viewCount', 'DESC')
            ->addOrderBy('a.publishedAt', 'DESC')
            ->addOrderBy('a.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /** @return Article[] "Leia também": as marcadas mais recentes. */
    public function findFeatured(int $limit = 3): array
    {
        return $this->orderNewestFirst(
            $this->visibleQueryBuilder()->andWhere('a.featured = true')
        )
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Article[] Todas as visíveis. O sitemap passa $withSamples = false:
     *                   notícias de exemplo (app:conteudo-exemplo) não vão para buscadores.
     */
    public function findAllVisible(bool $withSamples = true): array
    {
        $qb = $this->visibleQueryBuilder();
        if (!$withSamples) {
            $qb->andWhere('a.sample = false');
        }
        return $this->orderNewestFirst($qb)->getQuery()->getResult();
    }

    /**
     * Ids das categorias que têm ao menos uma notícia visível, como principal
     * ou entre as outras (menu do tema noticias).
     *
     * @return int[]
     */
    public function findCategoryIdsWithVisibleArticles(): array
    {
        $principal = $this->visibleQueryBuilder()
            ->select('DISTINCT IDENTITY(a.category) AS cid')
            ->andWhere('a.category IS NOT NULL')
            ->getQuery()
            ->getSingleColumnResult();

        $others = $this->visibleQueryBuilder()
            ->select('DISTINCT c.id AS cid')
            ->innerJoin('a.categories', 'c')
            ->getQuery()
            ->getSingleColumnResult();

        return array_values(array_unique(array_map('intval', array_merge($principal, $others))));
    }

    /**
     * Lista do painel (todas as situações), da mais recente para a mais antiga.
     *
     * @return Article[]
     */
    public function findForAdmin(int $page = 1, int $perPage = 50, ?Author $author = null): array
    {
        $qb = $this->createQueryBuilder('a');
        if ($author) {
            $qb->andWhere('a.author = :author')->setParameter('author', $author);
        }
        return $this->orderNewestFirst($qb)
            ->setFirstResult(max(0, ($page - 1) * $perPage))
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }

    public function countForAdmin(?Author $author = null): int
    {
        $qb = $this->createQueryBuilder('a')->select('COUNT(a.id)');
        if ($author) {
            $qb->andWhere('a.author = :author')->setParameter('author', $author);
        }
        return (int) $qb->getQuery()->getSingleScalarResult();
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

    /**
     * Soma 1 visualização direto no banco (UPDATE atômico, sem passar pelo
     * filtro de tenant). Só é chamado com uma notícia já carregada pela
     * consulta filtrada; o id vem da entidade, nunca da requisição.
     */
    public function incrementViews(Article $article): void
    {
        if (!$article->getId()) {
            return;
        }
        $this->getEntityManager()->getConnection()->executeStatement(
            'UPDATE article SET view_count = view_count + 1 WHERE id = :id',
            ['id' => $article->getId()],
        );
        $article->setViewCount($article->getViewCount() + 1);
    }
}
