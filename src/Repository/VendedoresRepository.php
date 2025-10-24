<?php

namespace App\Repository;

use App\Entity\Vendedores;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Vendedores>
 *
 * @method Vendedores|null find($id, $lockMode = null, $lockVersion = null)
 * @method Vendedores|null findOneBy(array $criteria, array $orderBy = null)
 * @method Vendedores[]    findAll()
 * @method Vendedores[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VendedoresRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Vendedores::class);
    }

//    /**
//     * @return Vendedores[] Returns an array of Vendedores objects
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

//    public function findOneBySomeField($value): ?Vendedores
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
