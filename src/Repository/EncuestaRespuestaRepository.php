<?php

namespace App\Repository;

use App\Entity\EncuestaRespuesta;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EncuestaRespuesta>
 */
class EncuestaRespuestaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EncuestaRespuesta::class);
    }

    public function findByUser(User $user): ?EncuestaRespuesta
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function hasUserResponded(User $user): bool
    {
        return $this->findByUser($user) !== null;
    }
}
