<?php

namespace App\Repository;

use App\Entity\ContactMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<ContactMessage> */
class ContactMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, ContactMessage::class); }

    /** Só os contatos aceitos contam como novos: os bloqueados pelo antispam nascem lidos e fora da conta. */
    public function countUnread(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.isRead = false')
            ->andWhere('c.spamReason IS NULL')
            ->getQuery()->getSingleScalarResult();
    }

    /**
     * Contatos do tenant do domínio, do mais novo para o mais antigo.
     *
     * @return ContactMessage[]
     */
    public function findBySituacao(bool $bloqueados): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere($bloqueados ? 'c.spamReason IS NOT NULL' : 'c.spamReason IS NULL')
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()->getResult();
    }

    public function countBlocked(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.spamReason IS NOT NULL')
            ->getQuery()->getSingleScalarResult();
    }
}
