<?php

namespace App\Repository;

use App\Entity\Analistas;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Analistas>
 *
 * @method Analistas|null find($id, $lockMode = null, $lockVersion = null)
 * @method Analistas|null findOneBy(array $criteria, array $orderBy = null)
 * @method Analistas[]    findAll()
 * @method Analistas[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AnalistasRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Analistas::class);
    }

//    /**
//     * @return Analistas[] Returns an array of Analistas objects
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

//    public function findOneBySomeField($value): ?Analistas
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
