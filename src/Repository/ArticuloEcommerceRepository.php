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
        ?string $bu,
        ?string $proveedor,
        ?string $marca,
        int $pagina = 1,
        int $porPagina = 24
    ): array {
        $qb = $this->createQueryBuilder('a');

        if ($q) {
            $qb->andWhere('a.descripcion LIKE :q OR a.descripcionIdeaconector LIKE :q OR a.codigoCalipso LIKE :q OR a.codigoRockwell LIKE :q')
               ->setParameter('q', '%' . $q . '%');
        }
        if ($categoria) {
            $qb->andWhere('a.categoriaAdvisor = :cat')->setParameter('cat', $categoria);
        }
        if ($subcategoria) {
            $qb->andWhere('a.subcategoriaAdvisor = :sub')->setParameter('sub', $subcategoria);
        }
        if ($bu) {
            $qb->andWhere('a.bu = :bu')->setParameter('bu', $bu);
        }
        if ($proveedor) {
            $qb->andWhere('a.proveedor = :prov')->setParameter('prov', $proveedor);
        }
        if ($marca) {
            $qb->andWhere('a.marca = :marca')->setParameter('marca', $marca);
        }

        $total = (clone $qb)->select('COUNT(a.codigoCalipso)')->getQuery()->getSingleScalarResult();

        $items = $qb->select('a')
            ->orderBy('a.descripcionIdeaconector', 'ASC')
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

    public function getBus(): array
    {
        return $this->createQueryBuilder('a')
            ->select('DISTINCT a.bu, a.idBu')
            ->where('a.bu IS NOT NULL')
            ->orderBy('a.bu', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getProveedores(): array
    {
        return $this->createQueryBuilder('a')
            ->select('DISTINCT a.proveedor')
            ->where('a.proveedor IS NOT NULL')
            ->orderBy('a.proveedor', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();
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
            'SELECT Codigo_Calipso FROM articulos_ecommerce WHERE Imagen IS NOT NULL ORDER BY RAND() LIMIT :limit',
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
