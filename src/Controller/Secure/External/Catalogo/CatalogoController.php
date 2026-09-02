<?php

namespace App\Controller\Secure\External\Catalogo;

use App\Repository\ClientesRepository;
use App\Repository\ProyectoRepository;
use App\Repository\StockAdvisorRepository;
use App\Services\CalypsoPreciosService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/catalogo')]
class CatalogoController extends AbstractController
{
    public function __construct(
        private StockAdvisorRepository $stockAdvisorRepo,
        private ClientesRepository $clientesRepo,
        private ProyectoRepository $proyectoRepo,
        private EntityManagerInterface $em,
        private CalypsoPreciosService $preciosService,
    ) {}

    #[Route('/switch-empresa', name: 'app_catalogo_switch_empresa', methods: ['POST'])]
    public function switchEmpresa(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $codigo = trim($request->request->get('cliente_codigo', ''));
        $user = $this->getUser();

        if ($this->isGranted('ROLE_ADMIN')) {
            if ($codigo === '') {
                $user->setActiveCliente(null);
                $user->setActiveProyectoId(null);
                $this->em->flush();
                $referer = $request->headers->get('referer');
                return $this->redirect($referer ?: $this->generateUrl('app_catalogo_index'));
            }
            if (!$this->clientesRepo->find($codigo)) {
                throw $this->createNotFoundException('Empresa no encontrada.');
            }
        } else {
            $valido = false;
            foreach ($user->getUserCustomers() as $uc) {
                if ($uc->getClienteCodigo() === $codigo) {
                    $valido = true;
                    break;
                }
            }
            if (!$valido) {
                throw $this->createAccessDeniedException('Empresa no asociada al usuario.');
            }
        }

        $user->setActiveCliente($codigo);

        if ($user->getActiveProyectoId() !== null) {
            $proyecto = $this->proyectoRepo->find($user->getActiveProyectoId());
            if ($proyecto === null || $proyecto->getClienteCodigo() !== $codigo) {
                $user->setActiveProyectoId(null);
            }
        }

        $this->em->flush();

        $referer = $request->headers->get('referer');
        return $this->redirect($referer ?: $this->generateUrl('app_catalogo_index'));
    }

    #[Route('', name: 'app_catalogo_index')]
    public function index(): Response
    {
        $categorias = $this->stockAdvisorRepo->getCategorias();
        $recomendados = $this->stockAdvisorRepo->getRecomendados(8);

        $codigos = array_map(fn($a) => $a->getCodigoCalipso(), $recomendados);
        $stockMap = $this->stockAdvisorRepo->getStockMap($codigos);
        $tagsMap = $this->stockAdvisorRepo->getTagsMap($codigos);

        return $this->render('secure/external/catalogo/index.html.twig', [
            'categorias' => $categorias,
            'recomendados' => $recomendados,
            'stockMap' => $stockMap,
            'tagsMap' => $tagsMap,
        ]);
    }

    #[Route('/buscar-empresa', name: 'app_catalogo_buscar_empresa', methods: ['GET'])]
    public function buscarEmpresa(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $q = trim($request->query->get('q', ''));
        if (strlen($q) < 2) {
            return $this->json([]);
        }

        $resultados = $this->clientesRepo->createQueryBuilder('c')
            ->where('c.razonSocial LIKE :q')
            ->andWhere('c.cuit LIKE :cuit30 OR c.cuit LIKE :cuit33 OR c.cuit LIKE :cuit34')
            ->setParameter('q', '%' . $q . '%')
            ->setParameter('cuit30', '30-%')
            ->setParameter('cuit33', '33-%')
            ->setParameter('cuit34', '34-%')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        return $this->json(array_map(fn($c) => [
            'codigo' => $c->getCodigoCalipso(),
            'nombre' => $c->getRazonSocial(),
        ], $resultados));
    }

    #[Route('/productos', name: 'app_catalogo_lista')]
    public function lista(Request $request): Response
    {
        $q = $request->query->get('q');
        $categoria = $request->query->get('categoria');
        $subcategoria = $request->query->get('subcategoria');
        $marca = $request->query->get('marca');
        $rawTags = $request->query->all('tag');
        $tags = array_values(array_filter(is_array($rawTags) ? $rawTags : []));
        $ordenar = in_array($request->query->get('ordenar'), ['az', 'za']) ? $request->query->get('ordenar') : 'az';
        $pagina = max(1, (int)$request->query->get('pagina', 1));
        $porPagina = in_array((int)$request->query->get('por_pagina', 24), [24, 48, 72])
            ? (int)$request->query->get('por_pagina', 24)
            : 24;
        $vista = $request->query->get('vista', 'grid');

        $resultado = $this->stockAdvisorRepo->buscarConFiltros(
            $q, $categoria, $subcategoria, $marca, $pagina, $porPagina, $ordenar, $tags
        );

        $totalPaginas = (int)ceil($resultado['total'] / $porPagina);

        $user = $this->getUser();
        $proyectos = $user
            ? $this->proyectoRepo->findByUser($user, $user->getActiveClienteCodigo())
            : [];

        $codigos = array_map(fn($a) => $a->getCodigoCalipso(), $resultado['items']);
        $stockMap = $this->stockAdvisorRepo->getStockMap($codigos);
        $tagsMap = $this->stockAdvisorRepo->getTagsMap($codigos);

        return $this->render('secure/external/catalogo/lista.html.twig', [
            'articulos' => $resultado['items'],
            'total' => $resultado['total'],
            'pagina' => $pagina,
            'totalPaginas' => $totalPaginas,
            'porPagina' => $porPagina,
            'vista' => $vista,
            'stockMap' => $stockMap,
            'tagsMap' => $tagsMap,
            'filtros' => [
                'q' => $q,
                'categoria' => $categoria,
                'subcategoria' => $subcategoria,
                'marca' => $marca,
                'tags' => $tags,
                'ordenar' => $ordenar,
            ],
            'opcionesFiltros' => [
                'categorias' => $this->stockAdvisorRepo->getCategorias(),
                'subcategorias' => $this->stockAdvisorRepo->getSubcategorias($categoria),
                'marcas' => $this->stockAdvisorRepo->getMarcas(),
                'tags' => $this->stockAdvisorRepo->getTagsDisponibles(),
            ],
            'proyectos' => $proyectos,
        ]);
    }

    #[Route('/productos/{codigo}/similares', name: 'app_catalogo_similares', methods: ['GET'])]
    public function similares(string $codigo): JsonResponse
    {
        $articulo = $this->stockAdvisorRepo->find($codigo);
        if (!$articulo || !$articulo->getCategoriaAdvisor()) {
            return $this->json([]);
        }

        $items = array_values(array_filter(
            $this->stockAdvisorRepo->buscarConFiltros(null, $articulo->getCategoriaAdvisor(), null, null, 1, 6)['items'],
            fn($a) => $a->getCodigoCalipso() !== $codigo
        ));

        return $this->json(array_map(fn($a) => [
            'codigo'      => $a->getCodigoCalipso(),
            'nombre'      => $a->getNombreDisplay(),
            'imagen'      => $a->getImagen(),
            'descripcion' => $a->getDescripcion(),
        ], array_slice($items, 0, 4)));
    }

    #[Route('/productos/{codigo}', name: 'app_catalogo_detalle')]
    public function detalle(string $codigo): Response
    {
        $articulo = $this->stockAdvisorRepo->find($codigo);
        if (!$articulo) {
            throw $this->createNotFoundException('Producto no encontrado');
        }

        $user = $this->getUser();
        $proyectos = $user
            ? $this->proyectoRepo->findByUser($user, $user->getActiveClienteCodigo())
            : [];

        $relacionados = array_values(array_filter(
            $this->stockAdvisorRepo->buscarConFiltros(null, $articulo->getCategoriaAdvisor(), null, null, 1, 5)['items'],
            fn($a) => $a->getCodigoCalipso() !== $articulo->getCodigoCalipso()
        ));

        $precio = null;
        $puedeVerPrecio = $user && (
            $this->isGranted('ROLE_COMPRADOR') ||
            $this->isGranted('ROLE_ADMIN') ||
            $this->isGranted('ROLE_ADMINISTRACION') ||
            $this->isGranted('ROLE_INGENIERO_N2')
        );
        if ($puedeVerPrecio) {
            $codigoCliente = $user->getActiveClienteCodigo() ?? 'DEMO';
            try {
                $precio = $this->preciosService->consultarPrecio(
                    $codigoCliente,
                    $articulo->getCodigoCalipso()
                );
            } catch (\RuntimeException) {
                // Sin precio disponible: se muestra sin precio
            }
        }

        return $this->render('secure/external/catalogo/detalle.html.twig', [
            'articulo' => $articulo,
            'proyectos' => $proyectos,
            'relacionados' => array_slice($relacionados, 0, 4),
            'precio' => $precio,
            'stockAdvisor' => $articulo,
        ]);
    }
}
