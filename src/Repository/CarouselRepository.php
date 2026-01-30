<?php

namespace App\Repository;

use App\Entity\Carousel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Carousel>
 */
class CarouselRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Carousel::class);
    }

    /**
     * Encuentra todas las imágenes del carrusel ordenadas por el campo 'sort'.
     *
     * @return Carousel[]
     */
    public function findAllOrderedBySort(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.sort', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Encuentra los carruseles activos según programación de fechas.
     * Reglas:
     * - Si ambos campos son null, el carrusel está siempre activo.
     * - Si solo start_at tiene valor, se muestra desde esa fecha en adelante.
     * - Si solo end_at tiene valor, se muestra hasta esa fecha.
     * - Si ambos tienen valor, se muestra cuando now() esté entre start_at y end_at.
     *
     * @return Carousel[]
     */
    public function findActiveBySchedule(): array
    {
        $now = new \DateTimeImmutable();

        return $this->createQueryBuilder('c')
            ->where('c.deletedAt IS NULL')
            ->andWhere(
                '(c.startAt IS NULL AND c.endAt IS NULL) OR ' .
                '(c.startAt IS NOT NULL AND c.endAt IS NULL AND c.startAt <= :now) OR ' .
                '(c.startAt IS NULL AND c.endAt IS NOT NULL AND c.endAt >= :now) OR ' .
                '(c.startAt IS NOT NULL AND c.endAt IS NOT NULL AND c.startAt <= :now AND c.endAt >= :now)'
            )
            ->setParameter('now', $now)
            ->orderBy('c.sort', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Obtiene el siguiente valor disponible para el campo 'sort'.
     *
     * @return int
     */
    public function getNextSortValue(): int
    {
        $result = $this->createQueryBuilder('c')
            ->select('MAX(c.sort)')
            ->getQuery()
            ->getSingleScalarResult();

        return $result !== null ? (int)$result + 1 : 0;
    }

    /**
     * Encuentra una imagen del carrusel por su ID, excluyendo las eliminadas.
     *
     * @param int $id
     * @return Carousel|null
     */
    public function findActiveById(int $id): ?Carousel
    {
        return $this->createQueryBuilder('c')
            ->where('c.id = :id')
            ->andWhere('c.deletedAt IS NULL')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Actualiza el orden de múltiples imágenes del carrusel.
     *
     * @param array $sortData Array con estructura [['id' => 1, 'sort' => 0], ...]
     * @return void
     */
    public function updateSortOrder(array $sortData): void
    {
        $entityManager = $this->getEntityManager();

        foreach ($sortData as $data) {
            $carousel = $this->find($data['id']);
            if ($carousel) {
                $carousel->setSort($data['sort']);
                $entityManager->persist($carousel);
            }
        }

        $entityManager->flush();
    }
}
