<?php

namespace App\Controller\Secure\External\Catalogo;

use App\Entity\Proyecto;
use App\Entity\ProyectoItem;
use App\Repository\ArticuloEcommerceRepository;
use App\Repository\ProyectoItemRepository;
use App\Repository\ProyectoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/secure/proyectos')]
class ProyectoController extends AbstractController
{
    public function __construct(
        private ProyectoRepository $proyectoRepo,
        private ProyectoItemRepository $itemRepo,
        private ArticuloEcommerceRepository $articuloRepo,
        private EntityManagerInterface $em,
    ) {}

    #[Route('', name: 'app_proyectos_index')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_COMPRADOR');

        $proyectos = $this->proyectoRepo->findByUser($this->getUser());

        return $this->render('secure/external/proyectos/index.html.twig', [
            'proyectos' => $proyectos,
        ]);
    }

    #[Route('/nuevo', name: 'app_proyectos_nuevo', methods: ['POST'])]
    public function nuevo(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_COMPRADOR');

        if (!$this->isCsrfTokenValid('proyecto_nuevo', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token inválido.');
            return $this->redirectToRoute('app_proyectos_index');
        }

        $nombre = trim($request->request->get('nombre', ''));
        if (empty($nombre)) {
            $this->addFlash('error', 'El nombre del proyecto es obligatorio.');
            return $this->redirectToRoute('app_proyectos_index');
        }

        $proyecto = new Proyecto();
        $proyecto->setUser($this->getUser());
        $proyecto->setNombre($nombre);
        $proyecto->setDescripcion(trim($request->request->get('descripcion', '')) ?: null);

        $this->em->persist($proyecto);
        $this->em->flush();

        $this->addFlash('success', "Proyecto \"{$nombre}\" creado.");
        return $this->redirectToRoute('app_proyectos_show', ['id' => $proyecto->getId()]);
    }

    #[Route('/{id}', name: 'app_proyectos_show', requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_COMPRADOR');
        $proyecto = $this->getProyectoDelUsuario($id);

        return $this->render('secure/external/proyectos/show.html.twig', [
            'proyecto' => $proyecto,
        ]);
    }

    #[Route('/{id}/editar', name: 'app_proyectos_editar', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function editar(int $id, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_COMPRADOR');
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
        $this->denyAccessUnlessGranted('ROLE_COMPRADOR');
        $proyecto = $this->getProyectoDelUsuario($id);

        if (!$this->isCsrfTokenValid('proyecto_eliminar_' . $id, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token inválido.');
            return $this->redirectToRoute('app_proyectos_index');
        }

        $nombre = $proyecto->getNombre();
        $this->em->remove($proyecto);
        $this->em->flush();

        $this->addFlash('success', "Proyecto \"{$nombre}\" eliminado.");
        return $this->redirectToRoute('app_proyectos_index');
    }

    // --- Manejo de items (AJAX) ---

    #[Route('/{id}/agregar-articulo', name: 'app_proyectos_agregar_articulo', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function agregarArticulo(int $id, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_COMPRADOR');
        $proyecto = $this->getProyectoDelUsuario($id);

        $articuloCodigo = trim($request->request->get('articulo_codigo', ''));
        $cantidad = max(1, (int)$request->request->get('cantidad', 1));

        $articulo = $this->articuloRepo->find($articuloCodigo);
        if (!$articulo) {
            return $this->json(['error' => 'Artículo no encontrado'], 404);
        }

        // Si ya existe, actualizar cantidad
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

        return $this->json([
            'success' => true,
            'mensaje' => "Artículo agregado al proyecto \"{$proyecto->getNombre()}\"",
            'cantidadProductos' => $proyecto->getCantidadProductos(),
        ]);
    }

    #[Route('/item/{itemId}/cantidad', name: 'app_proyectos_update_cantidad', requirements: ['itemId' => '\d+'], methods: ['POST'])]
    public function updateCantidad(int $itemId, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_COMPRADOR');

        $item = $this->itemRepo->find($itemId);
        if (!$item || $item->getProyecto()->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'No autorizado'], 403);
        }

        $cantidad = max(1, (int)$request->request->get('cantidad', 1));
        $item->setCantidad($cantidad);
        $this->em->flush();

        return $this->json(['success' => true, 'cantidad' => $item->getCantidad()]);
    }

    #[Route('/item/{itemId}/quitar', name: 'app_proyectos_quitar_articulo', requirements: ['itemId' => '\d+'], methods: ['POST'])]
    public function quitarArticulo(int $itemId, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_COMPRADOR');

        $item = $this->itemRepo->find($itemId);
        if (!$item || $item->getProyecto()->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'No autorizado'], 403);
        }

        $this->em->remove($item);
        $this->em->flush();

        return $this->json(['success' => true]);
    }

    private function getProyectoDelUsuario(int $id): Proyecto
    {
        $proyecto = $this->proyectoRepo->find($id);
        if (!$proyecto || $proyecto->getUser() !== $this->getUser()) {
            throw $this->createNotFoundException('Proyecto no encontrado');
        }
        return $proyecto;
    }
}
