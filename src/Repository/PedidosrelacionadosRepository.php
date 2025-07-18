<?php

namespace App\Repository;

use App\Entity\Pedidosrelacionados;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Pedidosrelacionados>
 *
 * @method Pedidosrelacionados|null find($id, $lockMode = null, $lockVersion = null)
 * @method Pedidosrelacionados|null findOneBy(array $criteria, array $orderBy = null)
 * @method Pedidosrelacionados[]    findAll()
 * @method Pedidosrelacionados[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PedidosrelacionadosRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Pedidosrelacionados::class);
    }

//    /**
//     * @return Pedidosrelacionados[] Returns an array of Pedidosrelacionados objects
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

//    public function findOneBySomeField($value): ?Pedidosrelacionados
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
