<?php

namespace App\Repository;

use App\Entity\ServiciosMarcas;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ServiciosMarcas>
 *
 * @method ServiciosMarcas|null find($id, $lockMode = null, $lockVersion = null)
 * @method ServiciosMarcas|null findOneBy(array $criteria, array $orderBy = null)
 * @method ServiciosMarcas[]    findAll()
 * @method ServiciosMarcas[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ServiciosMarcasRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ServiciosMarcas::class);
    }

//    /**
//     * @return ServiciosMarcas[] Returns an array of ServiciosMarcas objects
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

//    public function findOneBySomeField($value): ?ServiciosMarcas
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
