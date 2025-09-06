<?php

namespace App\Repository;

use App\Entity\UsuariosExport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UsuariosExport>
 *
 * @method UsuariosExport|null find($id, $lockMode = null, $lockVersion = null)
 * @method UsuariosExport|null findOneBy(array $criteria, array $orderBy = null)
 * @method UsuariosExport[]    findAll()
 * @method UsuariosExport[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UsuariosExportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UsuariosExport::class);
    }

//    /**
//     * @return UsuariosExport[] Returns an array of UsuariosExport objects
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

//    public function findOneBySomeField($value): ?UsuariosExport
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
