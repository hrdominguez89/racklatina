<?php

namespace App\Repository;

use App\Entity\Servicios;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Servicios>
 *
 * @method Servicios|null find($id, $lockMode = null, $lockVersion = null)
 * @method Servicios|null findOneBy(array $criteria, array $orderBy = null)
 * @method Servicios[]    findAll()
 * @method Servicios[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ServiciosRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Servicios::class);
    }

//    /**
//     * @return Servicios[] Returns an array of Servicios objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('a.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Servicios
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
