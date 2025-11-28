<?php

namespace App\Repository;

use App\Entity\ServiciosAdjuntos;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ServiciosAdjuntosRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ServiciosAdjuntos::class);
    }
}
