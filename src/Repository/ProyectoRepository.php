<?php

namespace App\Repository;

use App\Entity\Proyecto;
use App\Entity\User;
use App\Enum\ProyectoStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Proyecto>
 */
class ProyectoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Proyecto::class);
    }

    public function findByUser(User $user, ?string $clienteCodigo = null, ?ProyectoStatus $status = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.items', 'i')
            ->addSelect('i')
            ->where('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('p.createdAt', 'DESC');

        if ($clienteCodigo !== null) {
            $qb->andWhere('p.clienteCodigo = :cliente OR p.clienteCodigo IS NULL')
               ->setParameter('cliente', $clienteCodigo);
        }

        if ($status !== null) {
            $qb->andWhere('p.status = :status')
               ->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }

    /** Para ADMIN/SUPER_ADMIN: todos los proyectos del sistema con filtros opcionales. */
    public function findAllWithFilters(
        ?string $clienteCodigo = null,
        ?int $userId = null,
        ?ProyectoStatus $status = null,
        string $orden = 'fecha_desc',
    ): array {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.items', 'i')
            ->join('p.user', 'u')
            ->addSelect('i', 'u');

        if ($clienteCodigo !== null) {
            $qb->andWhere('p.clienteCodigo = :clienteCodigo')
               ->setParameter('clienteCodigo', $clienteCodigo);
        }

        if ($userId !== null) {
            $qb->andWhere('u.id = :userId')
               ->setParameter('userId', $userId);
        }

        if ($status !== null) {
            $qb->andWhere('p.status = :status')
               ->setParameter('status', $status);
        }

        match ($orden) {
            'fecha_asc'   => $qb->orderBy('p.createdAt', 'ASC'),
            'nombre_asc'  => $qb->orderBy('p.nombre', 'ASC'),
            'nombre_desc' => $qb->orderBy('p.nombre', 'DESC'),
            default       => $qb->orderBy('p.createdAt', 'DESC'),
        };

        return $qb->getQuery()->getResult();
    }

    /** Retorna los clienteCodigo distintos que tienen al menos un proyecto. */
    public function findDistinctClientesCodigos(): array
    {
        $rows = $this->createQueryBuilder('p')
            ->select('p.clienteCodigo')
            ->where('p.clienteCodigo IS NOT NULL')
            ->distinct()
            ->getQuery()
            ->getScalarResult();

        return array_column($rows, 'clienteCodigo');
    }

    /** Retorna los User distintos que tienen al menos un proyecto, filtrando opcionalmente por empresa. */
    public function findUsersWithProyectos(?string $clienteCodigo = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('DISTINCT u.id as userId')
            ->join('p.user', 'u');

        if ($clienteCodigo !== null) {
            $qb->where('p.clienteCodigo = :clienteCodigo')
               ->setParameter('clienteCodigo', $clienteCodigo);
        }

        $rows = $qb->getQuery()->getScalarResult();
        $userIds = array_column($rows, 'userId');

        if (empty($userIds)) {
            return [];
        }

        return $this->getEntityManager()
            ->getRepository(User::class)
            ->findBy(['id' => $userIds], ['lastName' => 'ASC']);
    }
}
