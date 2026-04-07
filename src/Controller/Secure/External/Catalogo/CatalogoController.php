<?php

namespace App\Controller\Secure\External\Catalogo;

use App\Repository\ArticuloEcommerceRepository;
use App\Repository\ProyectoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/catalogo')]
class CatalogoController extends AbstractController
{
    public function __construct(
        private ArticuloEcommerceRepository $articuloRepo,
        private ProyectoRepository $proyectoRepo,
        private EntityManagerInterface $em,
    ) {}

    #[Route('/switch-empresa', name: 'app_catalogo_switch_empresa', methods: ['POST'])]
    public function switchEmpresa(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_COMPRADOR');

        $codigo = $request->request->get('cliente_codigo', '');
        $user = $this->getUser();

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

        $user->setActiveCliente($codigo);
        $this->em->flush();

        $referer = $request->headers->get('referer');
        return $this->redirect($referer ?: $this->generateUrl('app_catalogo_index'));
    }

    #[Route('', name: 'app_catalogo_index')]
    public function index(): Response
    {
        $categorias = $this->articuloRepo->getCategorias();
        $recomendados = $this->articuloRepo->getRecomendados(8);

        return $this->render('secure/external/catalogo/index.html.twig', [
            'categorias' => $categorias,
            'recomendados' => $recomendados,
        ]);
    }

    #[Route('/productos', name: 'app_catalogo_lista')]
    public function lista(Request $request): Response
    {
        $q = $request->query->get('q');
        $categoria = $request->query->get('categoria');
        $subcategoria = $request->query->get('subcategoria');
        $bu = $request->query->get('bu');
        $proveedor = $request->query->get('proveedor');
        $marca = $request->query->get('marca');
        $pagina = max(1, (int)$request->query->get('pagina', 1));
        $porPagina = in_array((int)$request->query->get('por_pagina', 24), [24, 48, 72])
            ? (int)$request->query->get('por_pagina', 24)
            : 24;
        $vista = $request->query->get('vista', 'grid');

        $resultado = $this->articuloRepo->buscarConFiltros(
            $q, $categoria, $subcategoria, $bu, $proveedor, $marca, $pagina, $porPagina
        );

        $totalPaginas = (int)ceil($resultado['total'] / $porPagina);

        // Proyectos solo si el usuario está autenticado
        $user = $this->getUser();
        $proyectos = $user
            ? $this->proyectoRepo->findByUser($user, $user->getActiveClienteCodigo())
            : [];

        return $this->render('secure/external/catalogo/lista.html.twig', [
            'articulos' => $resultado['items'],
            'total' => $resultado['total'],
            'pagina' => $pagina,
            'totalPaginas' => $totalPaginas,
            'porPagina' => $porPagina,
            'vista' => $vista,
            'filtros' => [
                'q' => $q,
                'categoria' => $categoria,
                'subcategoria' => $subcategoria,
                'bu' => $bu,
                'proveedor' => $proveedor,
                'marca' => $marca,
            ],
            'opcionesFiltros' => [
                'categorias' => $this->articuloRepo->getCategorias(),
                'subcategorias' => $this->articuloRepo->getSubcategorias($categoria),
                'bus' => $this->articuloRepo->getBus(),
                'proveedores' => $this->articuloRepo->getProveedores(),
                'marcas' => $this->articuloRepo->getMarcas(),
            ],
            'proyectos' => $proyectos,
        ]);
    }

    #[Route('/productos/{codigo}', name: 'app_catalogo_detalle')]
    public function detalle(string $codigo): Response
    {
        $articulo = $this->articuloRepo->find($codigo);
        if (!$articulo) {
            throw $this->createNotFoundException('Producto no encontrado');
        }

        $user = $this->getUser();
        $proyectos = $user
            ? $this->proyectoRepo->findByUser($user, $user->getActiveClienteCodigo())
            : [];

        $relacionados = array_values(array_filter(
            $this->articuloRepo->buscarConFiltros(null, $articulo->getCategoriaAdvisor(), null, null, null, null, 1, 5)['items'],
            fn($a) => $a->getCodigoCalipso() !== $articulo->getCodigoCalipso()
        ));

        return $this->render('secure/external/catalogo/detalle.html.twig', [
            'articulo' => $articulo,
            'proyectos' => $proyectos,
            'relacionados' => array_slice($relacionados, 0, 4),
        ]);
    }
}
