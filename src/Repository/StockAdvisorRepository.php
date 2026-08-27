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

    public function buscarConFiltros(
        ?string $q,
        ?string $categoria,
        ?string $subcategoria,
        ?string $marca,
        int $pagina = 1,
        int $porPagina = 24,
        string $ordenar = 'az',
        array $tags = [],
    ): array {
        $qb = $this->createQueryBuilder('s')
            ->where('s.visibleAdvisor IS NOT NULL AND s.visibleAdvisor != :cero')
            ->setParameter('cero', '0');

        if ($q) {
            foreach (array_filter(array_map('trim', preg_split('/\s+/', $q))) as $i => $palabra) {
                $param = 'q' . $i;
                $qb->andWhere("s.descripcion LIKE :$param OR s.descripcionAdvisor LIKE :$param OR s.codigoCalipso LIKE :$param OR s.codigoRockwell LIKE :$param")
                   ->setParameter($param, '%' . $palabra . '%');
            }
        }
        if ($categoria) {
            $qb->andWhere('s.categoriaAdvisor = :cat')->setParameter('cat', $categoria);
        }
        if ($subcategoria) {
            $qb->andWhere('s.subcategoriaAdvisor = :sub')->setParameter('sub', $subcategoria);
        }
        if ($marca) {
            $qb->andWhere('s.marca = :marca')->setParameter('marca', $marca);
        }
        if (!empty($tags)) {
            $orParts = [];
            foreach ($tags as $i => $tag) {
                $orParts[] = "s.tags LIKE :tag$i";
                $qb->setParameter("tag$i", '%' . $tag . '%');
            }
            $qb->andWhere(implode(' OR ', $orParts));
        }

        $total = (clone $qb)->select('COUNT(s.codigoCalipso)')->getQuery()->getSingleScalarResult();

        $direction = $ordenar === 'za' ? 'DESC' : 'ASC';

        $items = $qb
            ->select('s')
            ->addSelect('CASE WHEN s.stock > 0 THEN 0 ELSE 1 END AS HIDDEN tiene_stock_orden')
            ->orderBy('tiene_stock_orden', 'ASC')
            ->addOrderBy('s.descripcionAdvisor', $direction)
            ->setFirstResult(($pagina - 1) * $porPagina)
            ->setMaxResults($porPagina)
            ->getQuery()
            ->getResult();

        return ['items' => $items, 'total' => (int)$total];
    }

    public function getCategorias(): array
    {
        return $this->createQueryBuilder('s')
            ->select('DISTINCT s.categoriaAdvisor')
            ->where('s.categoriaAdvisor IS NOT NULL')
            ->andWhere('s.visibleAdvisor IS NOT NULL AND s.visibleAdvisor != :cero')
            ->setParameter('cero', '0')
            ->orderBy('s.categoriaAdvisor', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();
    }

    public function getSubcategorias(?string $categoria = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->select('DISTINCT s.subcategoriaAdvisor')
            ->where('s.subcategoriaAdvisor IS NOT NULL')
            ->andWhere('s.visibleAdvisor IS NOT NULL AND s.visibleAdvisor != :cero')
            ->setParameter('cero', '0');

        if ($categoria) {
            $qb->andWhere('s.categoriaAdvisor = :cat')->setParameter('cat', $categoria);
        }

        return $qb->orderBy('s.subcategoriaAdvisor', 'ASC')->getQuery()->getSingleColumnResult();
    }

    public function getMarcas(): array
    {
        return $this->createQueryBuilder('s')
            ->select('DISTINCT s.marca')
            ->where('s.marca IS NOT NULL AND s.marca != :empty')
            ->andWhere('s.visibleAdvisor IS NOT NULL AND s.visibleAdvisor != :cero')
            ->setParameter('empty', '')
            ->setParameter('cero', '0')
            ->orderBy('s.marca', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();
    }

    public function getRecomendados(int $limit = 8): array
    {
        $codigos = $this->getEntityManager()->getConnection()->fetchFirstColumn(
            'SELECT Codigo_Calipso FROM Advisor_Stock
             WHERE Imagen IS NOT NULL AND Stock > 0
               AND Visible_Advisor IS NOT NULL AND Visible_Advisor != 0
             ORDER BY RAND() LIMIT :limit',
            ['limit' => $limit],
            ['limit' => \Doctrine\DBAL\ParameterType::INTEGER]
        );

        if (empty($codigos)) {
            return [];
        }

        return $this->createQueryBuilder('s')
            ->where('s.codigoCalipso IN (:codigos)')
            ->setParameter('codigos', $codigos)
            ->getQuery()
            ->getResult();
    }

    public function getStock(string $codigoCalipso): ?float
    {
        $row = $this->createQueryBuilder('s')
            ->select('s.stock')
            ->where('s.codigoCalipso = :codigo')
            ->andWhere('s.visibleAdvisor IS NOT NULL AND s.visibleAdvisor != :cero')
            ->setParameter('codigo', $codigoCalipso)
            ->setParameter('cero', '0')
            ->getQuery()
            ->getOneOrNullResult();

        return $row !== null ? (float)($row['stock'] ?? 0) : null;
    }

    public function getTagsDisponibles(): array
    {
        $rows = $this->createQueryBuilder('s')
            ->select('DISTINCT s.tags')
            ->where('s.tags IS NOT NULL AND s.tags != :vacio AND s.tags != :header')
            ->andWhere('s.visibleAdvisor IS NOT NULL AND s.visibleAdvisor != :cero')
            ->setParameter('vacio', '')
            ->setParameter('header', 'Tags')
            ->setParameter('cero', '0')
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

    public function getTagsMap(array $codigos): array
    {
        if (empty($codigos)) return [];

        $rows = $this->createQueryBuilder('s')
            ->select('s.codigoCalipso', 's.tags')
            ->where('s.codigoCalipso IN (:codigos)')
            ->andWhere('s.visibleAdvisor IS NOT NULL AND s.visibleAdvisor != :cero')
            ->setParameter('codigos', $codigos)
            ->setParameter('cero', '0')
            ->getQuery()
            ->getArrayResult();

        $map = [];
        foreach ($rows as $row) {
            $map[$row['codigoCalipso']] = $row['tags'] ?: null;
        }

        return $map;
    }

    public function getStockMap(array $codigos): array
    {
        if (empty($codigos)) return [];

        $rows = $this->createQueryBuilder('s')
            ->select('s.codigoCalipso', 's.stock')
            ->where('s.codigoCalipso IN (:codigos)')
            ->andWhere('s.visibleAdvisor IS NOT NULL AND s.visibleAdvisor != :cero')
            ->setParameter('codigos', $codigos)
            ->setParameter('cero', '0')
            ->getQuery()
            ->getArrayResult();

        $map = [];
        foreach ($rows as $row) {
            $map[$row['codigoCalipso']] = (float)($row['stock'] ?? 0);
        }

        return $map;
    }
}
