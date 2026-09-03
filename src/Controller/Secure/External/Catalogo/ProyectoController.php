<?php

namespace App\Controller\Secure\External\Catalogo;

use App\Email\ContactoEmailWithAttachments;
use App\Entity\Proyecto;
use App\Entity\ProyectoItem;
use App\Enum\ProyectoStatus;
use App\Repository\StockAdvisorRepository;
use App\Repository\ClientesRepository;
use App\Repository\ProyectoItemRepository;
use App\Repository\ProyectoRepository;
use App\Services\CalypsoLeadtimeService;
use App\Services\CalypsoPreciosService;
use App\Services\ProyectoExcelExporter;
use App\Services\StockAdvisorService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/secure/proyectos')]
class ProyectoController extends AbstractController
{
    private function denyUnlessProyectosAccess(): void
    {
        if (!$this->isGranted('ROLE_COMPRADOR') && !$this->isGranted('ROLE_ADMIN')
            && !$this->isGranted('ROLE_ADMINISTRACION')
            && !$this->isGranted('ROLE_INGENIERO_N1') && !$this->isGranted('ROLE_INGENIERO_N2')) {
            throw $this->createAccessDeniedException();
        }
    }

    private function denyUnlessProyectosWrite(): void
    {
        if (!$this->isGranted('ROLE_COMPRADOR') && !$this->isGranted('ROLE_ADMIN')
            && !$this->isGranted('ROLE_INGENIERO_N1') && !$this->isGranted('ROLE_INGENIERO_N2')) {
            throw $this->createAccessDeniedException('No tenés permiso para crear o modificar proyectos.');
        }
    }

    /** Solo COMPRADOR y ADMIN pueden enviar una cotización. Los ingenieros usan proyectos como wishlist. */
    private function denyUnlessPuedeCotizar(): void
    {
        if (!$this->isGranted('ROLE_COMPRADOR') && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('No tenés permiso para solicitar cotizaciones.');
        }
    }

    /** Retorna true si el usuario puede ver precios (excluye INGENIERO_N1). */
    private function canVerPrecios(): bool
    {
        return $this->isGranted('ROLE_ADMIN')
            || $this->isGranted('ROLE_COMPRADOR')
            || $this->isGranted('ROLE_ADMINISTRACION')
            || $this->isGranted('ROLE_INGENIERO_N2');
    }

    public function __construct(
        private ProyectoRepository $proyectoRepo,
        private ProyectoItemRepository $itemRepo,
        private StockAdvisorRepository $articuloRepo,
        private EntityManagerInterface $em,
        private MailerInterface $mailer,
        private ClientesRepository $clientesRepo,
        private StockAdvisorService $stockService,
        private ProyectoExcelExporter $excelExporter,
        private CalypsoPreciosService $preciosService,
        private CalypsoLeadtimeService $leadtimeService,
    ) {}

    #[Route('', name: 'app_proyectos_index')]
    public function index(Request $request, ClientesRepository $clientesRepo): Response
    {
        $this->denyUnlessProyectosAccess();

        $user = $this->getUser();

        // Admin sin impersonar → vista combinada: sus proyectos propios + tabla de solicitudes recibidas
        if ($this->isGranted('ROLE_ADMIN')) {
            $filtroEmpresa = $request->query->get('empresa') ?: null;
            $filtroUsuario = ($v = $request->query->get('usuario')) && ctype_digit($v) ? (int) $v : null;

            // Solicitudes recibidas: solo proyectos FINISHED de otros usuarios
            $solicitudes = $this->proyectoRepo->findAllWithFilters(
                $filtroEmpresa,
                $filtroUsuario,
                ProyectoStatus::FINISHED,
                'fecha_desc',
            );

            // Opciones de filtro
            $codigos         = $this->proyectoRepo->findDistinctClientesCodigos();
            $empresasOptions = $clientesRepo->findBy(['codigoCalipso' => $codigos], ['razonSocial' => 'ASC']);
            $usuariosOptions = $this->proyectoRepo->findUsersWithProyectos($filtroEmpresa);

            // Proyectos propios del admin (todos los estados)
            $misProyectos = $this->proyectoRepo->findByUser($user, null);

            // Mapa clienteCodigo → razonSocial
            $allCodigos   = array_unique(array_filter(array_map(
                fn($p) => $p->getClienteCodigo(),
                array_merge($solicitudes, $misProyectos)
            )));
            $clientes     = $clientesRepo->findBy(['codigoCalipso' => $allCodigos]);
            $clienteNames = [];
            foreach ($clientes as $c) {
                $clienteNames[$c->getCodigoCalipso()] = $c->getRazonSocial();
            }

            return $this->render('secure/external/proyectos/index.html.twig', [
                'proyectos'       => $misProyectos,
                'solicitudes'     => $solicitudes,
                'isAdmin'         => true,
                'empresasOptions' => $empresasOptions,
                'usuariosOptions' => $usuariosOptions,
                'clienteNames'    => $clienteNames,
                'filtroEmpresa'   => $filtroEmpresa,
                'filtroUsuario'   => $filtroUsuario,
            ]);
        }

        // Impersonando o usuario externo normal → vista del usuario efectivo
        $proyectos = $this->proyectoRepo->findByUser($user, $user->getActiveClienteCodigo());

        return $this->render('secure/external/proyectos/index.html.twig', [
            'proyectos'   => $proyectos,
            'solicitudes' => [],
            'isAdmin'     => false,
        ]);
    }

    #[Route('/nuevo', name: 'app_proyectos_nuevo', methods: ['POST'])]
    public function nuevo(Request $request): Response
    {
        $this->denyUnlessProyectosWrite();

        if (!$this->isCsrfTokenValid('proyecto_nuevo', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token inválido.');
            return $this->redirectToRoute('app_proyectos_index');
        }

        $nombre = trim($request->request->get('nombre', ''));
        if (empty($nombre)) {
            $this->addFlash('error', 'El nombre del proyecto es obligatorio.');
            return $this->redirectToRoute('app_proyectos_index');
        }

        $user = $this->getUser();
        $proyecto = new Proyecto();
        $proyecto->setUser($user);
        $proyecto->setNombre($nombre);
        $proyecto->setDescripcion(trim($request->request->get('descripcion', '')) ?: null);
        $proyecto->setClienteCodigo($user->getActiveClienteCodigo());

        $this->em->persist($proyecto);
        $this->em->flush();

        $user->setActiveProyectoId($proyecto->getId());
        $this->em->flush();

        $this->addFlash('success', "Proyecto \"{$nombre}\" creado y seleccionado como activo.");
        return $this->redirectToRoute('app_proyectos_show', ['id' => $proyecto->getId()]);
    }

    #[Route('/{id}', name: 'app_proyectos_show', requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $this->denyUnlessProyectosAccess();
        $proyecto = $this->getProyectoDelUsuario($id);

        $codigos  = $proyecto->getItems()->map(fn($i) => $i->getArticulo()->getCodigoCalipso())->toArray();
        $stockMap = array_map(fn($s) => (int)floor($s), $this->stockService->getStockMap($codigos));

        // Precios: para proyectos finalizados se usan snapshots guardados en BD.
        // Para proyectos en curso se cargan de forma asíncrona vía JS (endpoint precios-json).
        $preciosMap           = [];
        $precioTotalCalculado = null;

        // Leadtime: para proyectos finalizados se leen los snapshots guardados en BD.
        // Para proyectos en curso se calculan de forma asíncrona vía JS (endpoint leadtimes-json).
        $leadtimeMap = [];

        if ($proyecto->getStatus() === ProyectoStatus::FINISHED) {
            foreach ($proyecto->getItems() as $item) {
                $codigo = $item->getArticulo()->getCodigoCalipso();
                $lt     = $item->getLeadtimeResultado();
                if ($lt !== null) {
                    $leadtimeMap[$codigo] = $lt;
                }
            }
        }

        $isAdmin     = $this->isGranted('ROLE_ADMIN');
        $empresaNombre = null;
        if ($isAdmin && $proyecto->getClienteCodigo()) {
            $cliente = $this->clientesRepo->findOneBy(['codigoCalipso' => $proyecto->getClienteCodigo()]);
            $empresaNombre = $cliente?->getRazonSocial();
        }

        return $this->render('secure/external/proyectos/show.html.twig', [
            'proyecto'             => $proyecto,
            'stockMap'             => $stockMap,
            'preciosMap'           => $preciosMap,
            'precioTotalCalculado' => $precioTotalCalculado,
            'leadtimeMap'          => $leadtimeMap,
            'isAdmin'              => $isAdmin,
            'empresaNombre'        => $empresaNombre,
            'canCotizar'           => $this->isGranted('ROLE_COMPRADOR') || $this->isGranted('ROLE_ADMIN'),
            'canVerPrecio'         => $this->canVerPrecios(),
        ]);
    }

    #[Route('/{id}/editar', name: 'app_proyectos_editar', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function editar(int $id, Request $request): Response
    {
        $this->denyUnlessProyectosWrite();
        $proyecto = $this->getProyectoParaModificar($id);

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('proyecto_editar_' . $id, $request->request->get('_token'))) {
                $this->addFlash('error', 'Token inválido.');
                return $this->redirectToRoute('app_proyectos_index');
            }

            $nombre = trim($request->request->get('nombre', ''));
            if (!empty($nombre)) {
                $proyecto->setNombre($nombre);
            }
            $proyecto->setDescripcion(trim($request->request->get('descripcion', '')) ?: null);

            $this->em->flush();
            $this->addFlash('success', 'Proyecto actualizado.');
            return $this->redirectToRoute('app_proyectos_show', ['id' => $id]);
        }

        return $this->render('secure/external/proyectos/editar.html.twig', [
            'proyecto' => $proyecto,
        ]);
    }

    #[Route('/{id}/eliminar', name: 'app_proyectos_eliminar', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function eliminar(int $id, Request $request): Response
    {
        $this->denyUnlessProyectosWrite();
        $proyecto = $this->getProyectoParaModificar($id);

        if (!$this->isCsrfTokenValid('proyecto_eliminar_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token inválido.');
            return $this->redirectToRoute('app_proyectos_index');
        }

        $user = $this->getUser();
        $nombre = $proyecto->getNombre();

        // Si era el activo, limpiar
        if ($user->getActiveProyectoId() === $proyecto->getId()) {
            $user->setActiveProyectoId(null);
        }

        $this->em->remove($proyecto);
        $this->em->flush();

        $this->addFlash('success', "Proyecto \"{$nombre}\" eliminado.");
        return $this->redirectToRoute('app_proyectos_index');
    }

    // --- Lista de proyectos (JSON, para el modal del catálogo) ---

    /**
     * Endpoint exclusivo para ingenieros: busca su único proyecto (wishlist) o lo crea,
     * y agrega el artículo directamente. Un ingeniero no puede tener más de un proyecto.
     */
    #[Route('/agregar-automatico', name: 'app_proyectos_agregar_automatico', methods: ['POST'])]
    public function agregarAutomatico(Request $request): JsonResponse
    {
        if (!$this->isGranted('ROLE_INGENIERO_N1') && !$this->isGranted('ROLE_INGENIERO_N2')) {
            return $this->json(['error' => 'No autorizado'], 403);
        }

        $articuloCodigo = trim($request->request->get('articulo_codigo', ''));
        if (empty($articuloCodigo)) {
            return $this->json(['error' => 'Código de artículo requerido'], 400);
        }

        $articulo = $this->articuloRepo->find($articuloCodigo);
        if (!$articulo) {
            return $this->json(['error' => "Artículo '{$articuloCodigo}' no encontrado"], 404);
        }

        $user      = $this->getUser();
        $proyectos = $this->proyectoRepo->findByUser($user, null, ProyectoStatus::IN_PROGRESS);

        if (empty($proyectos)) {
            $proyecto = new Proyecto();
            $proyecto->setUser($user);
            $proyecto->setNombre('Mi Lista');
            $proyecto->setClienteCodigo($user->getActiveClienteCodigo());
            $this->em->persist($proyecto);
            $this->em->flush();
        } else {
            $proyecto = $proyectos[0];
        }

        // Sincronizar proyecto activo en sesión si cambió
        if ($user->getActiveProyectoId() !== $proyecto->getId()) {
            $user->setActiveProyectoId($proyecto->getId());
            $this->em->flush();
        }

        $item = $this->itemRepo->findOneBy(['proyecto' => $proyecto, 'articulo' => $articulo]);
        if ($item) {
            $item->setCantidad($item->getCantidad() + 1);
        } else {
            $item = new ProyectoItem();
            $item->setProyecto($proyecto);
            $item->setArticulo($articulo);
            $item->setCantidad(1);
            $this->em->persist($item);
        }
        $this->em->flush();
        $this->em->refresh($proyecto);

        return $this->json([
            'success'        => true,
            'mensaje'        => "Artículo agregado a tu lista",
            'cantidadItems'  => $proyecto->getCantidadProductos(),
            'proyectoNombre' => $proyecto->getNombre(),
            'proyecto'       => ['id' => $proyecto->getId(), 'nombre' => $proyecto->getNombre()],
        ]);
    }

    #[Route('/crear-ajax', name: 'app_proyectos_crear_ajax', methods: ['POST'])]
    public function crearAjax(Request $request): JsonResponse
    {
        $this->denyUnlessProyectosWrite();

        // Los ingenieros solo pueden tener un proyecto (wishlist); usar /agregar-automatico
        if ($this->isGranted('ROLE_INGENIERO_N1') || $this->isGranted('ROLE_INGENIERO_N2')) {
            return $this->json(['success' => false, 'error' => 'Los ingenieros solo pueden tener una lista activa.'], 403);
        }

        $nombre = trim($request->request->get('nombre', ''));
        if (empty($nombre)) {
            return $this->json(['success' => false, 'error' => 'El nombre del proyecto es obligatorio.']);
        }

        $user = $this->getUser();
        $proyecto = new Proyecto();
        $proyecto->setUser($user);
        $proyecto->setNombre($nombre);
        $proyecto->setRubro(trim($request->request->get('rubro', '')) ?: null);
        $proyecto->setDescripcion(trim($request->request->get('notas', '')) ?: null);
        $proyecto->setClienteCodigo($user->getActiveClienteCodigo());

        $this->em->persist($proyecto);
        $this->em->flush();

        $user->setActiveProyectoId($proyecto->getId());
        $this->em->flush();

        return $this->json([
            'success' => true,
            'proyecto' => ['id' => $proyecto->getId(), 'nombre' => $proyecto->getNombre()],
        ]);
    }

    #[Route('/mis-proyectos-json', name: 'app_proyectos_json', methods: ['GET'])]
    public function misProyectosJson(): JsonResponse
    {
        $this->denyUnlessProyectosWrite();
        $user = $this->getUser();
        $proyectos = $this->proyectoRepo->findByUser($user, $user->getActiveClienteCodigo(), ProyectoStatus::IN_PROGRESS);

        return $this->json(array_map(fn($p) => [
            'id'       => $p->getId(),
            'nombre'   => $p->getNombre(),
            'cantidad' => $p->getCantidadProductos(),
        ], $proyectos));
    }

    // --- Precios asíncronos (carga diferida en la vista show) ---

    #[Route('/{id}/precios-json', name: 'app_proyectos_precios_json', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function preciosJson(int $id): JsonResponse
    {
        $this->denyUnlessProyectosAccess();
        $proyecto = $this->getProyectoDelUsuario($id);

        if ($proyecto->getStatus() === ProyectoStatus::FINISHED) {
            return $this->json(['error' => 'Proyecto finalizado'], 400);
        }

        $clienteCodigo = $proyecto->getClienteCodigo();

        if (!$clienteCodigo || !$this->canVerPrecios()) {
            return $this->json([]);
        }

        $result = [];

        foreach ($proyecto->getItems() as $item) {
            $codigo = $item->getArticulo()->getCodigoCalipso();
            try {
                $resultado = $this->preciosService->consultarPrecio($clienteCodigo, $codigo, $item->getCantidad());
                $result[$item->getId()] = $resultado;
            } catch (\RuntimeException) {
                $result[$item->getId()] = null;
            }
        }

        return $this->json($result);
    }

    // --- Leadtimes asíncronos (carga diferida en la vista show) ---

    #[Route('/{id}/leadtimes-json', name: 'app_proyectos_leadtimes_json', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function leadtimesJson(int $id): JsonResponse
    {
        $this->denyUnlessProyectosAccess();
        $proyecto = $this->getProyectoDelUsuario($id);

        if ($proyecto->getStatus() === ProyectoStatus::FINISHED) {
            return $this->json(['error' => 'Proyecto finalizado'], 400);
        }

        $result = [];

        foreach ($proyecto->getItems() as $item) {
            $codigo             = $item->getArticulo()->getCodigoCalipso();
            $deposito           = $this->leadtimeService->resolverDeposito($codigo);
            $cantidadSolicitada = $item->getCantidad();

            if ($deposito === null) {
                $result[$item->getId()] = ['consultarPlazos' => true, 'cantidadSolicitada' => $cantidadSolicitada, 'items' => []];
                continue;
            }

            try {
                $resultado = $this->leadtimeService->consultarLeadtime($codigo, $cantidadSolicitada, $deposito);
                $result[$item->getId()] = [
                    'consultarPlazos'    => $resultado['consultarPlazos'],
                    'cantidadSolicitada' => $cantidadSolicitada,
                    'items' => array_map(static fn(array $ltItem): array => [
                        'cantidad'       => $ltItem['cantidad'],
                        'disponible'     => $ltItem['disponible'],
                        'fechaEntrega'   => $ltItem['fechaEntrega'] instanceof \DateTimeImmutable
                            ? $ltItem['fechaEntrega']->format('d/m/Y')
                            : $ltItem['fechaEntrega'],
                        'deposito'       => $ltItem['deposito'] ?? '',
                        'depositoNombre' => CalypsoLeadtimeService::getNombreDeposito($ltItem['deposito'] ?? ''),
                    ], $resultado['items']),
                ];
            } catch (\Throwable) {
                $result[$item->getId()] = ['consultarPlazos' => true, 'cantidadSolicitada' => $cantidadSolicitada, 'items' => []];
            }
        }

        return $this->json($result);
    }

    // --- Set proyecto activo ---

    #[Route('/{id}/set-activo', name: 'app_proyectos_set_activo', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function setActivo(int $id): JsonResponse
    {
        $this->denyUnlessProyectosWrite();
        $proyecto = $this->getProyectoParaModificar($id);

        $user = $this->getUser();
        $user->setActiveProyectoId($proyecto->getId());
        $this->em->flush();

        return $this->json([
            'success' => true,
            'proyectoId' => $proyecto->getId(),
            'proyectoNombre' => $proyecto->getNombre(),
        ]);
    }

    // --- Manejo de items (AJAX) ---

    #[Route('/{id}/agregar-articulo', name: 'app_proyectos_agregar_articulo', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function agregarArticulo(int $id, Request $request): JsonResponse
    {
        $this->denyUnlessProyectosWrite();

        try {
            $proyecto = $this->getProyectoParaModificar($id);

            $articuloCodigo = trim($request->request->get('articulo_codigo', ''));
            $cantidad = max(1, (int)$request->request->get('cantidad', 1));

            if (empty($articuloCodigo)) {
                return $this->json(['error' => 'Código de artículo requerido'], 400);
            }

            $articulo = $this->articuloRepo->find($articuloCodigo);
            if (!$articulo) {
                return $this->json(['error' => "Artículo '{$articuloCodigo}' no encontrado"], 404);
            }

            $item = $this->itemRepo->findOneBy(['proyecto' => $proyecto, 'articulo' => $articulo]);

            if ($item) {
                $item->setCantidad($item->getCantidad() + $cantidad);
            } else {
                $item = new ProyectoItem();
                $item->setProyecto($proyecto);
                $item->setArticulo($articulo);
                $item->setCantidad($cantidad);
                $this->em->persist($item);
            }

            $this->em->flush();

            // Refrescar count
            $this->em->refresh($proyecto);

            return $this->json([
                'success' => true,
                'mensaje' => "Artículo agregado al proyecto \"{$proyecto->getNombre()}\"",
                'cantidadItems' => $proyecto->getCantidadProductos(),
                'proyectoNombre' => $proyecto->getNombre(),
            ]);
        } catch (UniqueConstraintViolationException) {
            // Condición de carrera: doble submit. El ítem ya fue insertado por otra request.
            // Reiniciamos el EM y actualizamos la cantidad.
            $this->em->clear();
            $proyecto = $this->proyectoRepo->find($id);
            $articulo = $this->articuloRepo->find($articuloCodigo);
            $item = $this->itemRepo->findOneBy(['proyecto' => $proyecto, 'articulo' => $articulo]);
            if ($item) {
                $item->setCantidad($item->getCantidad() + $cantidad);
                $this->em->flush();
            }
            $this->em->refresh($proyecto);

            return $this->json([
                'success' => true,
                'mensaje' => "Artículo agregado al proyecto \"{$proyecto->getNombre()}\"",
                'cantidadItems' => $proyecto->getCantidadProductos(),
                'proyectoNombre' => $proyecto->getNombre(),
            ]);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => 'Error interno: ' . $e->getMessage(),
            ], 500);
        }
    }

    #[Route('/item/{itemId}/cantidad', name: 'app_proyectos_update_cantidad', requirements: ['itemId' => '\d+'], methods: ['POST'])]
    public function updateCantidad(int $itemId, Request $request): JsonResponse
    {
        if (!$this->isGranted('ROLE_COMPRADOR') && !$this->isGranted('ROLE_ADMIN')
            && !$this->isGranted('ROLE_INGENIERO_N1') && !$this->isGranted('ROLE_INGENIERO_N2')) {
            return $this->json(['error' => 'Sin permisos para modificar proyectos.'], 403);
        }

        $item = $this->itemRepo->find($itemId);
        if (!$item || ($item->getProyecto()->getUser()->getId() !== $this->getUser()->getId())) {
            return $this->json(['error' => 'No autorizado'], 403);
        }

        $cantidad = max(1, (int)$request->request->get('cantidad', 1));

        $item->setCantidad($cantidad);
        $this->em->flush();

        // Recalcular leadtime con la nueva cantidad para actualizarlo en la UI
        $codigo   = $item->getArticulo()->getCodigoCalipso();
        $deposito = $this->leadtimeService->resolverDeposito($codigo);

        if ($deposito === null) {
            $leadtime = ['consultarPlazos' => true, 'cantidadSolicitada' => $cantidad, 'items' => []];
        } else {
            $resultado = $this->leadtimeService->consultarLeadtime($codigo, $cantidad, $deposito);
            $leadtime  = [
                'consultarPlazos'    => $resultado['consultarPlazos'],
                'cantidadSolicitada' => $cantidad,
                'items' => array_map(static fn(array $ltItem): array => [
                    'cantidad'       => $ltItem['cantidad'],
                    'disponible'     => $ltItem['disponible'],
                    'fechaEntrega'   => $ltItem['fechaEntrega'] instanceof \DateTimeImmutable
                        ? $ltItem['fechaEntrega']->format('d/m/Y')
                        : $ltItem['fechaEntrega'],
                    'deposito'       => $ltItem['deposito'] ?? '',
                    'depositoNombre' => CalypsoLeadtimeService::getNombreDeposito($ltItem['deposito'] ?? ''),
                ], $resultado['items']),
            ];
        }

        // Recalcular precio con la nueva cantidad (solo si el usuario tiene permiso para ver precios)
        $precio        = null;
        $clienteCodigo = $item->getProyecto()->getClienteCodigo();
        if ($clienteCodigo && $this->canVerPrecios()) {
            try {
                $precio = $this->preciosService->consultarPrecio($clienteCodigo, $codigo, $cantidad);
            } catch (\RuntimeException) {}
        }

        return $this->json(['success' => true, 'cantidad' => $item->getCantidad(), 'leadtime' => $leadtime, 'precio' => $precio]);
    }

    #[Route('/item/{itemId}/comment', name: 'app_proyectos_update_comment', requirements: ['itemId' => '\d+'], methods: ['POST'])]
    public function updateComment(int $itemId, Request $request): JsonResponse
    {
        if (!$this->isGranted('ROLE_COMPRADOR') && !$this->isGranted('ROLE_ADMIN')
            && !$this->isGranted('ROLE_INGENIERO_N1') && !$this->isGranted('ROLE_INGENIERO_N2')) {
            return $this->json(['error' => 'Sin permisos para modificar proyectos.'], 403);
        }

        $item = $this->itemRepo->find($itemId);
        if (!$item || ($item->getProyecto()->getUser()->getId() !== $this->getUser()->getId())) {
            return $this->json(['error' => 'No autorizado'], 403);
        }

        $item->setComment($request->request->get('comment'));
        $this->em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/item/{itemId}/reemplazo', name: 'app_proyectos_update_reemplazo', requirements: ['itemId' => '\d+'], methods: ['POST'])]
    public function updateReemplazo(int $itemId, Request $request): JsonResponse
    {
        if (!$this->isGranted('ROLE_COMPRADOR') && !$this->isGranted('ROLE_ADMIN')
            && !$this->isGranted('ROLE_INGENIERO_N1') && !$this->isGranted('ROLE_INGENIERO_N2')) {
            return $this->json(['error' => 'Sin permisos para modificar proyectos.'], 403);
        }

        $item = $this->itemRepo->find($itemId);
        if (!$item || ($item->getProyecto()->getUser()->getId() !== $this->getUser()->getId())) {
            return $this->json(['error' => 'No autorizado'], 403);
        }

        $tipo = $request->request->get('tipo');
        $valor = filter_var($request->request->get('valor'), FILTER_VALIDATE_BOOLEAN);

        if ($tipo === 'precio') {
            $item->setReemplazoPrecio($valor);
        } elseif ($tipo === 'plazo') {
            $item->setReemplazoPlazo($valor);
        } else {
            return $this->json(['error' => 'Tipo inválido'], 400);
        }

        $this->em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/item/{itemId}/quitar', name: 'app_proyectos_quitar_articulo', requirements: ['itemId' => '\d+'], methods: ['POST'])]
    public function quitarArticulo(int $itemId, Request $request): JsonResponse
    {
        if (!$this->isGranted('ROLE_COMPRADOR') && !$this->isGranted('ROLE_ADMIN')
            && !$this->isGranted('ROLE_INGENIERO_N1') && !$this->isGranted('ROLE_INGENIERO_N2')) {
            return $this->json(['error' => 'Sin permisos para modificar proyectos.'], 403);
        }

        $item = $this->itemRepo->find($itemId);
        if (!$item) {
            return $this->json(['error' => 'Item no encontrado'], 404);
        }
        $esPropio = $item->getProyecto()->getUser()->getId() === $this->getUser()->getId();
        if (!$esPropio && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'No autorizado'], 403);
        }
        if ($item->getProyecto()->getStatus() !== ProyectoStatus::IN_PROGRESS) {
            return $this->json(['error' => 'El proyecto ya fue enviado y no puede modificarse.'], 403);
        }

        $this->em->remove($item);
        $this->em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/{id}/cotizacion-preview', name: 'app_proyectos_cotizacion_preview', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function cotizacionPreview(int $id, Request $request): Response
    {
        $this->denyUnlessProyectosAccess();
        $proyecto = $this->getProyectoDelUsuario($id);

        $user = $proyecto->getUser();
        $usuarioNombre = trim($user->getFirstName() . ' ' . $user->getLastName());

        $empresaNombre = null;
        $clienteCodigo = $proyecto->getClienteCodigo();
        if ($clienteCodigo) {
            $cliente = $this->clientesRepo->findOneBy(['codigoCalipso' => $clienteCodigo]);
            if ($cliente) {
                $empresaNombre = $cliente->getRazonSocial();
            }
        }

        $context = [
            'proyecto_id'      => $proyecto->getId(),
            'proyecto_nombre'  => $proyecto->getNombre(),
            'usuario_nombre'   => $usuarioNombre,
            'usuario_email'    => $user->getEmail(),
            'empresa_nombre'   => $empresaNombre,
            'items'            => $proyecto->getItems(),
            'fecha'            => new \DateTime(),
            'precio_total_usd' => $proyecto->getPrecioTotalUsd(),
            'plazo_maximo'     => $this->calcularPlazoMaximo($proyecto),
        ];

        $tipo = $request->query->get('tipo', 'interno');
        $template = $tipo === 'confirmacion'
            ? 'emails/solicitud_cotizacion_confirmacion.html.twig'
            : 'emails/solicitud_cotizacion_proyecto.html.twig';

        return $this->render($template, $context);
    }

    #[Route('/{id}/excel-preview', name: 'app_proyectos_excel_preview', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function excelPreview(int $id): Response
    {
        $this->denyUnlessProyectosAccess();
        $proyecto = $this->getProyectoDelUsuario($id);

        $content  = $this->excelExporter->export($proyecto);
        $filename = 'Solicitud_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $proyecto->getNombre()) . '.xlsx';

        // Guardar copia local en var/excel_test/
        $dir = $this->getParameter('kernel.project_dir') . '/var/excel_test';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        file_put_contents($dir . '/' . $filename, $content);

        // También devolver para descarga directa en el browser
        return new Response($content, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    #[Route('/{id}/solicitar-cotizacion', name: 'app_proyectos_solicitar_cotizacion', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function solicitarCotizacion(int $id, Request $request): JsonResponse
    {
        $this->denyUnlessPuedeCotizar();

        if (!$this->isCsrfTokenValid('solicitar_cotizacion_' . $id, $request->request->get('_token'))) {
            return $this->json(['success' => false, 'error' => 'Token inválido.'], 403);
        }

        $proyecto = $this->getProyectoDelUsuario($id);

        if ($proyecto->getItems()->isEmpty()) {
            return $this->json(['success' => false, 'error' => 'El proyecto no tiene productos.'], 400);
        }

        $user = $this->getUser();
        $usuarioNombre = trim($user->getFirstName() . ' ' . $user->getLastName());
        $usuarioEmail  = $user->getEmail();

        $empresaNombre = null;
        $clienteCodigo = $proyecto->getClienteCodigo();
        if ($clienteCodigo) {
            $cliente = $this->clientesRepo->findOneBy(['codigoCalipso' => $clienteCodigo]);
            if ($cliente) {
                $empresaNombre = $cliente->getRazonSocial();
            }
        }

        // 1. Snapshot de precios
        $this->snapshotPrecios($proyecto, $clienteCodigo);

        // 2. Snapshot de leadtime — debe completarse antes de armar los emails
        $this->snapshotLeadtime($proyecto);

        $fecha = new \DateTime();
        $items = $proyecto->getItems();
        $templateContext = [
            'proyecto_id'      => $proyecto->getId(),
            'proyecto_nombre'  => $proyecto->getNombre(),
            'usuario_nombre'   => $usuarioNombre,
            'usuario_email'    => $usuarioEmail,
            'empresa_nombre'   => $empresaNombre,
            'items'            => $items,
            'fecha'            => $fecha,
            'precio_total_usd' => $proyecto->getPrecioTotalUsd(),
            'plazo_maximo'     => $this->calcularPlazoMaximo($proyecto),
        ];

        // Generar Excel adjunto
        $excelContent  = $this->excelExporter->export($proyecto);
        $excelFilename = 'Solicitud_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $proyecto->getNombre()) . '.xlsx';

        // Email interno a Racklatina
        $emailInterno = (new ContactoEmailWithAttachments())
            ->from($_ENV['MAIL_FROM'])
            ->to($_ENV['MAIL_CENTRO_RAC'])
            ->replyTo($usuarioEmail)
            ->subject('Solicitud de Cotización – ' . $proyecto->getNombre() . ' (' . ($empresaNombre ?? $usuarioNombre) . ')')
            ->html($this->renderView('emails/solicitud_cotizacion_proyecto.html.twig', $templateContext));

        $emailInterno->addAttachmentData(
            $excelContent,
            $excelFilename,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );

        $this->mailer->send($emailInterno);

        // Email de confirmación al comprador
        $emailConfirmacion = (new ContactoEmailWithAttachments())
            ->from($_ENV['MAIL_FROM'])
            ->to($usuarioEmail)
            ->subject('Tu solicitud de presupuesto fue enviada con éxito')
            ->html($this->renderView('emails/solicitud_cotizacion_confirmacion.html.twig', $templateContext));

        $this->mailer->send($emailConfirmacion);

        $proyecto->setStatus(ProyectoStatus::FINISHED);
        $this->em->flush();

        return $this->json(['success' => true]);
    }

    /**
     * Consulta precios en Calypso para cada ítem y los guarda en la entidad.
     * Si un ítem no tiene precio disponible, se deja en null.
     * El total del proyecto se actualiza solo si al menos un ítem tiene precio.
     */
    private function snapshotPrecios(Proyecto $proyecto, ?string $clienteCodigo): void
    {
        if (!$clienteCodigo) {
            return;
        }

        $totalUsd = 0.0;
        $hayPrecios = false;

        foreach ($proyecto->getItems() as $item) {
            try {
                $resultado = $this->preciosService->consultarPrecio(
                    $clienteCodigo,
                    $item->getArticulo()->getCodigoCalipso(),
                    $item->getCantidad()
                );
                if ($resultado !== null) {
                    $item->setPrecioUnitarioUsd($resultado['precio']);
                    $totalUsd += $resultado['precioTotal'];
                    $hayPrecios = true;
                }
            } catch (\RuntimeException) {
                // Sin precio para este ítem: se deja en null
            }
        }

        if ($hayPrecios) {
            $proyecto->setPrecioTotalUsd(round($totalUsd, 2));
        }
    }

    /**
     * Consulta el leadtime de cada ítem en Calypso y lo persiste como snapshot.
     * Las fechas se almacenan como string "d/m/Y" para compatibilidad con JSON.
     */
    /**
     * Calcula la fecha máxima de entrega entre todos los ítems del proyecto.
     * Retorna la fecha como string "d/m/Y", o null si algún ítem requiere consulta manual.
     */
    private function calcularPlazoMaximo(Proyecto $proyecto): ?string
    {
        $max = null;

        foreach ($proyecto->getItems() as $item) {
            $lt = $item->getLeadtimeResultado();

            if ($lt === null || $lt['consultarPlazos'] === true) {
                return null; // Al menos un ítem sin fecha conocida → no se puede mostrar fecha
            }

            foreach ($lt['items'] as $ltItem) {
                $fecha = \DateTimeImmutable::createFromFormat('d/m/Y', $ltItem['fechaEntrega']);
                if ($fecha === false) {
                    return null;
                }
                if ($max === null || $fecha > $max) {
                    $max = $fecha;
                }
            }
        }

        return $max?->format('d/m/Y');
    }

    private function snapshotLeadtime(Proyecto $proyecto): void
    {
        foreach ($proyecto->getItems() as $item) {
            $codigo             = $item->getArticulo()->getCodigoCalipso();
            $deposito           = $this->leadtimeService->resolverDeposito($codigo);
            $cantidadSolicitada = $item->getCantidad();

            if ($deposito === null) {
                // País/depósito aún no soportado → CONSULTAR PLAZOS
                $item->setLeadtimeResultado([
                    'consultarPlazos'    => true,
                    'cantidadSolicitada' => $cantidadSolicitada,
                    'items'              => [],
                ]);
                continue;
            }

            $resultado = $this->leadtimeService->consultarLeadtime($codigo, $cantidadSolicitada, $deposito);

            // Convertir DateTimeImmutable → string para almacenamiento JSON
            $itemsSerializables = array_map(static function (array $ltItem): array {
                return [
                    'cantidad'       => $ltItem['cantidad'],
                    'disponible'     => $ltItem['disponible'],
                    'fechaEntrega'   => $ltItem['fechaEntrega']->format('d/m/Y'),
                    'deposito'       => (string) $ltItem['deposito'],
                    'depositoNombre' => CalypsoLeadtimeService::getNombreDeposito((string) $ltItem['deposito']),
                ];
            }, $resultado['items']);

            $item->setLeadtimeResultado([
                'consultarPlazos'    => $resultado['consultarPlazos'],
                'cantidadSolicitada' => $cantidadSolicitada,
                'items'              => $itemsSerializables,
            ]);
        }
    }

    private function getProyectoDelUsuario(int $id): Proyecto
    {
        $proyecto = $this->proyectoRepo->find($id);
        if (!$proyecto) {
            throw $this->createNotFoundException('Proyecto no encontrado');
        }

        // Admin sin impersonar puede ver cualquier proyecto
        if ($this->isGranted('ROLE_ADMIN')) {
            return $proyecto;
        }

        // Impersonando o usuario externo: solo sus propios proyectos
        if ($proyecto->getUser()->getId() !== $this->getUser()->getId()) {
            throw $this->createNotFoundException('Proyecto no encontrado');
        }

        return $proyecto;
    }

    private function getProyectoParaModificar(int $id): Proyecto
    {
        $proyecto = $this->proyectoRepo->find($id);
        if (!$proyecto) {
            throw $this->createNotFoundException('Proyecto no encontrado');
        }

        if ($proyecto->getUser()->getId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException('No tenés permiso para modificar este proyecto.');
        }

        return $proyecto;
    }
}
