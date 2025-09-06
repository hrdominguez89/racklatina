<?php

namespace App\Repository;

use App\Entity\ServiciosEstados;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ServiciosEstados>
 *
 * @method ServiciosEstados|null find($id, $lockMode = null, $lockVersion = null)
 * @method ServiciosEstados|null findOneBy(array $criteria, array $orderBy = null)
 * @method ServiciosEstados[]    findAll()
 * @method ServiciosEstados[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ServiciosEstadosRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ServiciosEstados::class);
    }

//    /**
//     * @return ServiciosEstados[] Returns an array of ServiciosEstados objects
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

//    public function findOneBySomeField($value): ?ServiciosEstados
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
