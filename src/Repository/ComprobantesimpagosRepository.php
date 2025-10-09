<?php

namespace App\Repository;

use App\Entity\Comprobantesimpagos;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comprobantesimpagos>
 *
 * @method Comprobantesimpagos|null find($id, $lockMode = null, $lockVersion = null)
 * @method Comprobantesimpagos|null findOneBy(array $criteria, array $orderBy = null)
 * @method Comprobantesimpagos[]    findAll()
 * @method Comprobantesimpagos[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ComprobantesimpagosRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comprobantesimpagos::class);
    }
    public function findComprobantesImpagosByCliente(string $cliente_id): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.cliente = :cliente_id')
            ->setParameter('cliente_id', $cliente_id)
            ->getQuery()
            ->getArrayResult();
    }

//    /**
//     * @return Comprobantesimpagos[] Returns an array of Comprobantesimpagos objects
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

//    public function findOneBySomeField($value): ?Comprobantesimpagos
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
