<?php

namespace App\Controller\Secure\External\Catalogo;

use App\Entity\Proyecto;
use App\Entity\ProyectoItem;
use App\Enum\ProyectoStatus;
use App\Repository\ArticuloEcommerceRepository;
use App\Repository\ProyectoItemRepository;
use App\Repository\ProyectoRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/secure/proyectos')]
class ProyectoController extends AbstractController
{
    private function denyUnlessProyectosAccess(): void
    {
        if (!$this->isGranted('ROLE_COMPRADOR') && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }
    }

    public function __construct(
        private ProyectoRepository $proyectoRepo,
        private ProyectoItemRepository $itemRepo,
        private ArticuloEcommerceRepository $articuloRepo,
        private EntityManagerInterface $em,
    ) {}

    #[Route('', name: 'app_proyectos_index')]
    public function index(): Response
    {
        $this->denyUnlessProyectosAccess();

        $user = $this->getUser();
        $proyectos = $this->proyectoRepo->findByUser($user, $user->getActiveClienteCodigo());

        return $this->render('secure/external/proyectos/index.html.twig', [
            'proyectos' => $proyectos,
        ]);
    }

    #[Route('/nuevo', name: 'app_proyectos_nuevo', methods: ['POST'])]
    public function nuevo(Request $request): Response
    {
        $this->denyUnlessProyectosAccess();

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

        // Setear como proyecto activo automáticamente
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

        return $this->render('secure/external/proyectos/show.html.twig', [
            'proyecto' => $proyecto,
        ]);
    }

    #[Route('/{id}/editar', name: 'app_proyectos_editar', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function editar(int $id, Request $request): Response
    {
        $this->denyUnlessProyectosAccess();
        $proyecto = $this->getProyectoDelUsuario($id);

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
        $this->denyUnlessProyectosAccess();
        $proyecto = $this->getProyectoDelUsuario($id);

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

    #[Route('/crear-ajax', name: 'app_proyectos_crear_ajax', methods: ['POST'])]
    public function crearAjax(Request $request): JsonResponse
    {
        $this->denyUnlessProyectosAccess();

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
        $this->denyUnlessProyectosAccess();
        $user = $this->getUser();
        $proyectos = $this->proyectoRepo->findByUser($user, $user->getActiveClienteCodigo(), ProyectoStatus::IN_PROGRESS);

        return $this->json(array_map(fn($p) => [
            'id'       => $p->getId(),
            'nombre'   => $p->getNombre(),
            'cantidad' => $p->getCantidadProductos(),
        ], $proyectos));
    }

    // --- Set proyecto activo ---

    #[Route('/{id}/set-activo', name: 'app_proyectos_set_activo', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function setActivo(int $id): JsonResponse
    {
        $this->denyUnlessProyectosAccess();
        $proyecto = $this->getProyectoDelUsuario($id);

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
        $this->denyUnlessProyectosAccess();

        try {
            $proyecto = $this->getProyectoDelUsuario($id);

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
        $this->denyUnlessProyectosAccess();

        $item = $this->itemRepo->find($itemId);
        if (!$item || $item->getProyecto()->getUser()->getId() !== $this->getUser()->getId()) {
            return $this->json(['error' => 'No autorizado'], 403);
        }

        $cantidad = max(1, (int)$request->request->get('cantidad', 1));
        $item->setCantidad($cantidad);
        $this->em->flush();

        return $this->json(['success' => true, 'cantidad' => $item->getCantidad()]);
    }

    #[Route('/item/{itemId}/comment', name: 'app_proyectos_update_comment', requirements: ['itemId' => '\d+'], methods: ['POST'])]
    public function updateComment(int $itemId, Request $request): JsonResponse
    {
        $this->denyUnlessProyectosAccess();

        $item = $this->itemRepo->find($itemId);
        if (!$item || $item->getProyecto()->getUser()->getId() !== $this->getUser()->getId()) {
            return $this->json(['error' => 'No autorizado'], 403);
        }

        $item->setComment($request->request->get('comment'));
        $this->em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/item/{itemId}/quitar', name: 'app_proyectos_quitar_articulo', requirements: ['itemId' => '\d+'], methods: ['POST'])]
    public function quitarArticulo(int $itemId, Request $request): JsonResponse
    {
        $this->denyUnlessProyectosAccess();

        $item = $this->itemRepo->find($itemId);
        if (!$item || $item->getProyecto()->getUser()->getId() !== $this->getUser()->getId()) {
            return $this->json(['error' => 'No autorizado'], 403);
        }

        $this->em->remove($item);
        $this->em->flush();

        return $this->json(['success' => true]);
    }

    private function getProyectoDelUsuario(int $id): Proyecto
    {
        $proyecto = $this->proyectoRepo->find($id);
        if (!$proyecto) {
            throw $this->createNotFoundException('Proyecto no encontrado');
        }
        // Admin puede ver cualquier proyecto; comprador solo el propio
        if (!$this->isGranted('ROLE_ADMIN') && $proyecto->getUser()->getId() !== $this->getUser()->getId()) {
            throw $this->createNotFoundException('Proyecto no encontrado');
        }
        return $proyecto;
    }
}
