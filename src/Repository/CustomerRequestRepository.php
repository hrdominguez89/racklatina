<?php

namespace App\Repository;

use App\Entity\CustomerRequest;
use App\Enum\CustomerRequestStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CustomerRequest>
 */
class CustomerRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CustomerRequest::class);
    }

    public function findByStatusWithActiveUsers(?CustomerRequestStatus $status = null): array
    {
        $qb = $this->createQueryBuilder('cr')
            ->join('cr.userRequest', 'u')
            ->andWhere('u.deletedAt IS NULL')
            ->orderBy('cr.createdAt', 'DESC');

        if ($status !== null) {
            $qb->andWhere('cr.status = :status')
                ->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }
}
