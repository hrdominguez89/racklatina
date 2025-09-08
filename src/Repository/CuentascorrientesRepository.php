<?php

namespace App\Repository;

use App\Entity\Cuentascorrientes;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Cuentascorrientes>
 *
 * @method Cuentascorrientes|null find($id, $lockMode = null, $lockVersion = null)
 * @method Cuentascorrientes|null findOneBy(array $criteria, array $orderBy = null)
 * @method Cuentascorrientes[]    findAll()
 * @method Cuentascorrientes[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CuentascorrientesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cuentascorrientes::class);
    }
      public function findComprobantesSaldados(string $cliente_id): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.codigoCliente = :cliente_id')
            ->setParameter('cliente_id', $cliente_id)
            ->getQuery()
            ->getArrayResult();
    }
//    /**
//     * @return Cuentascorrientes[] Returns an array of Cuentascorrientes objects
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

//    public function findOneBySomeField($value): ?Cuentascorrientes
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
