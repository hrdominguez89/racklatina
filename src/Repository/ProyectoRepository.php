<?php

namespace App\Repository;

use App\Entity\Proyecto;
use App\Entity\User;
use App\Enum\ProyectoStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Proyecto>
 */
class ProyectoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Proyecto::class);
    }

    public function findByUser(User $user, ?string $clienteCodigo = null, ?ProyectoStatus $status = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.items', 'i')
            ->addSelect('i')
            ->where('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('p.createdAt', 'DESC');

        if ($clienteCodigo !== null) {
            $qb->andWhere('p.clienteCodigo = :cliente OR p.clienteCodigo IS NULL')
               ->setParameter('cliente', $clienteCodigo);
        }

        if ($status !== null) {
            $qb->andWhere('p.status = :status')
               ->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }
}
