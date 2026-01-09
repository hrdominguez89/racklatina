<?php

namespace App\Controller\Secure\Internal\Carousel;

use App\Entity\Carousel;
use App\Form\CarouselFormType;
use App\Repository\CarouselRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/secure/carousel')]
class CarouselController extends AbstractController
{
    public function __construct(
        private CarouselRepository $carouselRepository,
        private EntityManagerInterface $entityManager,
        private SluggerInterface $slugger
    ) {
    }

    /**
     * Endpoint público para obtener las imágenes del carousel
     * (No requiere autenticación - para uso en el frontend público)
     */
    #[Route('/api/images', name: 'app_carousel_api_images', methods: ['GET'])]
    public function apiImages(): JsonResponse
    {
        $carousels = $this->carouselRepository->findAllOrderedBySort();

        $images = array_map(function (Carousel $carousel) {
            return [
                'id' => $carousel->getId(),
                'name' => $carousel->getName(),
                'path' => $carousel->getPath(),
                'sort' => $carousel->getSort(),
                'url' => $this->generateUrl('app_home', [], true) . $carousel->getPath()
            ];
        }, $carousels);

        return $this->json([
            'success' => true,
            'data' => $images,
            'count' => count($images)
        ]);
    }

    #[Route('/', name: 'app_carousel_index')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $carousels = $this->carouselRepository->findAllOrderedBySort();

        return $this->render('secure/internal/carousel/index.html.twig', [
            'carousels' => $carousels,
            'title' => 'Gestión de Carousel'
        ]);
    }

    #[Route('/nuevo', name: 'app_carousel_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $carousel = new Carousel();
        $form = $this->createForm(CarouselFormType::class, $carousel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('carousel_images_directory'),
                        $newFilename
                    );

                    $carousel->setPath('assets/images/carousel/' . $newFilename);

                    // Asignar automáticamente el siguiente valor de sort
                    $nextSort = $this->carouselRepository->getNextSortValue();
                    $carousel->setSort($nextSort);

                    $this->entityManager->persist($carousel);
                    $this->entityManager->flush();

                    $this->addFlash('success', 'Imagen agregada al carousel exitosamente.');
                    return $this->redirectToRoute('app_carousel_index');
                } catch (FileException $e) {
                    $this->addFlash('error', 'Error al subir la imagen: ' . $e->getMessage());
                }
            }
        }

        return $this->render('secure/internal/carousel/form.html.twig', [
            'form' => $form,
            'title' => 'Nueva Imagen de Carousel',
            'carousel' => $carousel
        ]);
    }

    #[Route('/{id}/editar', name: 'app_carousel_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $carousel = $this->carouselRepository->findActiveById($id);

        if (!$carousel) {
            $this->addFlash('error', 'Imagen no encontrada.');
            return $this->redirectToRoute('app_carousel_index');
        }

        $oldPath = $carousel->getPath();
        $form = $this->createForm(CarouselFormType::class, $carousel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                // Eliminar imagen anterior
                $this->deleteImageFile($oldPath);

                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('carousel_images_directory'),
                        $newFilename
                    );

                    $carousel->setPath('assets/images/carousel/' . $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Error al subir la imagen: ' . $e->getMessage());
                    return $this->redirectToRoute('app_carousel_edit', ['id' => $id]);
                }
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Imagen actualizada exitosamente.');
            return $this->redirectToRoute('app_carousel_index');
        }

        return $this->render('secure/internal/carousel/form.html.twig', [
            'form' => $form,
            'title' => 'Editar Imagen de Carousel',
            'carousel' => $carousel
        ]);
    }

    #[Route('/{id}/eliminar', name: 'app_carousel_delete', methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $carousel = $this->carouselRepository->findActiveById($id);

        if (!$carousel) {
            $this->addFlash('error', 'Imagen no encontrada.');
            return $this->redirectToRoute('app_carousel_index');
        }

        if ($this->isCsrfTokenValid('delete' . $carousel->getId(), $request->request->get('_token'))) {
            // Eliminar el archivo físico
            $this->deleteImageFile($carousel->getPath());

            // Eliminar el registro (soft delete por Gedmo)
            $this->entityManager->remove($carousel);
            $this->entityManager->flush();

            $this->addFlash('success', 'Imagen eliminada exitosamente.');
        }

        return $this->redirectToRoute('app_carousel_index');
    }

    #[Route('/actualizar-orden', name: 'app_carousel_update_order', methods: ['POST'])]
    public function updateOrder(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['order'])) {
            return $this->json(['success' => false, 'message' => 'Datos inválidos'], 400);
        }

        try {
            $sortData = [];
            foreach ($data['order'] as $index => $id) {
                $sortData[] = ['id' => (int)$id, 'sort' => $index];
            }

            $this->carouselRepository->updateSortOrder($sortData);

            return $this->json(['success' => true, 'message' => 'Orden actualizado exitosamente']);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => 'Error al actualizar el orden'], 500);
        }
    }

    /**
     * Elimina un archivo de imagen del sistema de archivos
     */
    private function deleteImageFile(string $path): void
    {
        $fullPath = $this->getParameter('kernel.project_dir') . '/public/' . $path;

        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }
}
