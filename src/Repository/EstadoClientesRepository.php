<?php

namespace App\Repository;

use App\Entity\EstadoClientes;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EstadoClientes>
 *
 * @method EstadoClientes|null find($id, $lockMode = null, $lockVersion = null)
 * @method EstadoClientes|null findOneBy(array $criteria, array $orderBy = null)
 * @method EstadoClientes[]    findAll()
 * @method EstadoClientes[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EstadoClientesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EstadoClientes::class);
    }

//    /**
//     * @return EstadoClientes[] Returns an array of EstadoClientes objects
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

//    public function findOneBySomeField($value): ?EstadoClientes
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
