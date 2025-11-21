<?php

namespace App\Repository;

use App\Entity\ServiceRequests;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ServiceRequests>
 *
 * @method ServiceRequests|null find($id, $lockMode = null, $lockVersion = null)
 * @method ServiceRequests|null findOneBy(array $criteria, array $orderBy = null)
 * @method ServiceRequests[]    findAll()
 * @method ServiceRequests[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ServiceRequestsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ServiceRequests::class);
    }

    public function save(ServiceRequests $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ServiceRequests $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
