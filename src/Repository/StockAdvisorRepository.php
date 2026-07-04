<?php

namespace App\Repository;

use App\Entity\StockAdvisor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StockAdvisor>
 */
class StockAdvisorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StockAdvisor::class);
    }

    public function findByCodigo(string $codigoCalipso): ?StockAdvisor
    {
        return $this->find($codigoCalipso);
    }

    /**
     * Retorna el stock disponible para un artículo dado su Codigo_Calipso.
     * Devuelve null si el artículo no existe en stock_advisor.
     */
    public function getStock(string $codigoCalipso): ?float
    {
        $row = $this->createQueryBuilder('s')
            ->select('s.stock')
            ->where('s.codigoCalipso = :codigo')
            ->setParameter('codigo', $codigoCalipso)
            ->getQuery()
            ->getOneOrNullResult();

        if ($row === null) {
            return null;
        }

        return (float)($row['stock'] ?? 0);
    }

    /**
     * Retorna los tags individuales distintos que existen en stock_advisor.
     * Las filas guardan tags como CSV (ej: "Promoción,Liquidación").
     *
     * @return string[]
     */
    public function getTagsDisponibles(): array
    {
        $rows = $this->createQueryBuilder('s')
            ->select('DISTINCT s.tags')
            ->where('s.tags IS NOT NULL AND s.tags != :vacio AND s.tags != :header')
            ->setParameter('vacio', '')
            ->setParameter('header', 'Tags')
            ->getQuery()
            ->getSingleColumnResult();

        $tags = [];
        foreach ($rows as $row) {
            foreach (array_map('trim', explode(',', $row)) as $tag) {
                if ($tag !== '') {
                    $tags[$tag] = true;
                }
            }
        }

        $tags = array_keys($tags);
        sort($tags);

        return $tags;
    }

    /**
     * Retorna un mapa [codigoCalipso => stock] para múltiples artículos en una sola query.
     *
     * @param string[] $codigos
     * @return array<string, float>
     */
    public function getStockMap(array $codigos): array
    {
        if (empty($codigos)) {
            return [];
        }

        $rows = $this->createQueryBuilder('s')
            ->select('s.codigoCalipso', 's.stock')
            ->where('s.codigoCalipso IN (:codigos)')
            ->setParameter('codigos', $codigos)
            ->getQuery()
            ->getArrayResult();

        $map = [];
        foreach ($rows as $row) {
            $map[$row['codigoCalipso']] = (float)($row['stock'] ?? 0);
        }

        return $map;
    }
}
