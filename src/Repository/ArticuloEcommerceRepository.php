<?php

namespace App\Repository;

use App\Entity\ArticuloEcommerce;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ArticuloEcommerce>
 */
class ArticuloEcommerceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ArticuloEcommerce::class);
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
        // JOIN con stock_advisor siempre: necesario para ordenar por stock y filtrar por tag
        $qb = $this->createQueryBuilder('a')
            ->leftJoin(\App\Entity\StockAdvisor::class, 's', 'WITH', 's.codigoCalipso = a.codigoCalipso');

        if ($q) {
            $palabras = array_filter(array_map('trim', preg_split('/\s+/', $q)));
            foreach ($palabras as $i => $palabra) {
                $param = 'q' . $i;
                $qb->andWhere("a.descripcion LIKE :$param OR a.descripcionIdeaconector LIKE :$param OR a.codigoCalipso LIKE :$param OR a.codigoRockwell LIKE :$param")
                   ->setParameter($param, '%' . $palabra . '%');
            }
        }
        if ($categoria) {
            $qb->andWhere('a.categoriaAdvisor = :cat')->setParameter('cat', $categoria);
        }
        if ($subcategoria) {
            $qb->andWhere('a.subcategoriaAdvisor = :sub')->setParameter('sub', $subcategoria);
        }
        if ($marca) {
            $qb->andWhere('a.marca = :marca')->setParameter('marca', $marca);
        }
        if (!empty($tags)) {
            $orX = $qb->expr()->orX();
            foreach ($tags as $i => $tag) {
                $param = 'tag' . $i;
                $orX->add("s.tags LIKE :$param");
                $qb->setParameter($param, '%' . $tag . '%');
            }
            $qb->andWhere($orX);
        }

        $total = (clone $qb)->select('COUNT(a.codigoCalipso)')->getQuery()->getSingleScalarResult();

        $direction = $ordenar === 'za' ? 'DESC' : 'ASC';

        $items = $qb->select('a')
            ->addSelect('CASE WHEN s.codigoCalipso IS NOT NULL AND s.stock > 0 THEN 0 ELSE 1 END AS HIDDEN tiene_stock_orden')
            ->orderBy('tiene_stock_orden', 'ASC')
            ->addOrderBy('a.descripcionIdeaconector', $direction)
            ->setFirstResult(($pagina - 1) * $porPagina)
            ->setMaxResults($porPagina)
            ->getQuery()
            ->getResult();

        return ['items' => $items, 'total' => (int)$total];
    }

    public function getCategorias(): array
    {
        return $this->createQueryBuilder('a')
            ->select('DISTINCT a.categoriaAdvisor')
            ->where('a.categoriaAdvisor IS NOT NULL')
            ->orderBy('a.categoriaAdvisor', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();
    }

    public function getSubcategorias(?string $categoria = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->select('DISTINCT a.subcategoriaAdvisor')
            ->where('a.subcategoriaAdvisor IS NOT NULL');

        if ($categoria) {
            $qb->andWhere('a.categoriaAdvisor = :cat')->setParameter('cat', $categoria);
        }

        return $qb->orderBy('a.subcategoriaAdvisor', 'ASC')->getQuery()->getSingleColumnResult();
    }

    public function getMarcas(): array
    {
        return $this->createQueryBuilder('a')
            ->select('DISTINCT a.marca')
            ->where('a.marca IS NOT NULL AND a.marca != :empty')
            ->setParameter('empty', '')
            ->orderBy('a.marca', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();
    }

    public function getRecomendados(int $limit = 8): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $codigos = $conn->fetchFirstColumn(
            'SELECT ae.Codigo_Calipso FROM articulos_ecommerce ae
             INNER JOIN stock_advisor sa ON sa.Codigo_Calipso = ae.Codigo_Calipso
             WHERE ae.Imagen IS NOT NULL AND sa.Stock > 0
             ORDER BY RAND() LIMIT :limit',
            ['limit' => $limit],
            ['limit' => \Doctrine\DBAL\ParameterType::INTEGER]
        );

        if (empty($codigos)) {
            return [];
        }

        return $this->createQueryBuilder('a')
            ->where('a.codigoCalipso IN (:codigos)')
            ->setParameter('codigos', $codigos)
            ->getQuery()
            ->getResult();
    }
}
