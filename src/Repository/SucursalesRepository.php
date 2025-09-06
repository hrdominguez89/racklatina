<?php

namespace App\Repository;

use App\Entity\Sucursales;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Sucursales>
 *
 * @method Sucursales|null find($id, $lockMode = null, $lockVersion = null)
 * @method Sucursales|null findOneBy(array $criteria, array $orderBy = null)
 * @method Sucursales[]    findAll()
 * @method Sucursales[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SucursalesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sucursales::class);
    }

//    /**
//     * @return Sucursales[] Returns an array of Sucursales objects
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

//    public function findOneBySomeField($value): ?Sucursales
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
