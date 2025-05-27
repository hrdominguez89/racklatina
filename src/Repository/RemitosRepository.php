<?php

namespace App\Repository;

use App\Entity\Remitos;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Remitos>
 *
 * @method Remitos|null find($id, $lockMode = null, $lockVersion = null)
 * @method Remitos|null findOneBy(array $criteria, array $orderBy = null)
 * @method Remitos[]    findAll()
 * @method Remitos[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RemitosRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Remitos::class);
    }

//    /**
//     * @return Remitos[] Returns an array of Remitos objects
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

//    public function findOneBySomeField($value): ?Remitos
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
