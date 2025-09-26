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
      public function findComprobantesSaldados(string $cliente_id,string $tipo): array
    {
        $qb = $this->createQueryBuilder('c');
        $qb->where('c.codigoCliente = :cliente_id')
        ->setParameter('cliente_id', $cliente_id);
        if ($tipo != "TODAS")
        {
            if(strlen($tipo) > 1) {
                $caracteres = str_split($tipo);
                $qb->andWhere('c.documento IN (:tipos)')
                ->setParameter('tipos', $caracteres);
            } else {
                $qb->andWhere('c.documento = :tipo')
                ->setParameter('tipo', $tipo);
            }
        }
        $retorno = $qb->getQuery()
        ->getArrayResult();
        return $retorno;
    }
    public function findComprobantesSaldadosPorOrdenDeCompra(string $ordenDeCompra,string $tipo)
    {
        $qb = $this->createQueryBuilder('c');
        $qb->where('c.ordencompra = :ordenDeCompra')
        ->setParameter('ordenDeCompra', $ordenDeCompra);
        if ($tipo != "TODAS")
        {
            $qb->andWhere('c.documento = :tipo')
            ->setParameter('tipo', $tipo);
        }
        $retorno = $qb->getQuery()
        ->getArrayResult();
        return $retorno;
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
