<?php

namespace App\Controller\Secure\Internal\Services;

use App\Entity\Servicios;
use App\Form\ServicesFormType;
use App\Repository\ServiciosRepository;
use App\Repository\ProvinciasRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/secure/servicios-internos')]
class ServicesController extends AbstractController
{
    #[Route('/', name: 'app_secure_internal_services')]
    public function index(Request $request, ServiciosRepository $serviciosRepository): Response
    {
        $searchTerm = $request->query->get('search', '');
        $statusFilter = $request->query->get('status', '');

        $qb = $serviciosRepository->createQueryBuilder('s');

        if ($searchTerm) {
            $qb->andWhere('s.serviceempresa LIKE :search
                          OR s.serviceemail LIKE :search
                          OR s.servicenroserie LIKE :search
                          OR s.servicecontacto LIKE :search')
               ->setParameter('search', '%' . $searchTerm . '%');
        }

        if ($statusFilter !== '') {
            $qb->andWhere('s.servicestatus = :status')
               ->setParameter('status', $statusFilter);
        }

        $services = $qb->orderBy('s.servicedate', 'DESC')
                        ->getQuery()
                        ->getResult();

        return $this->render('secure/internal/services/index.html.twig', [
            'services' => $services,
            'searchTerm' => $searchTerm,
            'statusFilter' => $statusFilter,
            'title' => 'Gestión de Servicios'
        ]);
    }

    #[Route('/nuevo', name: 'app_secure_internal_services_new')]
    public function new(Request $request, EntityManagerInterface $entityManager, ProvinciasRepository $provinciasRepository, ServiciosRepository $serviciosRepository): Response
    {
        $service = new Servicios();
        $service->setServicedate(new \DateTime());

        // Obtener todas las provincias agrupadas por país
        $allProvincias = $provinciasRepository->findAll();
        $provinciasByPais = [];
        foreach ($allProvincias as $provincia) {
            $paisId = $provincia->getPaisId();
            if (!isset($provinciasByPais[$paisId])) {
                $provinciasByPais[$paisId] = [];
            }
            $provinciasByPais[$paisId][] = [
                'id' => $provincia->getProvinciaId(),
                'nombre' => $provincia->getProvinciaNombre()
            ];
        }

        $form = $this->createForm(ServicesFormType::class, $service);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Generar el siguiente serviceID (MAX + 1)
            $maxId = $serviciosRepository->createQueryBuilder('s')
                ->select('MAX(s.serviceid)')
                ->getQuery()
                ->getSingleScalarResult();

            $nextId = ($maxId !== null) ? $maxId + 1 : 1;
            $service->setServiceid($nextId);

            // Establecer el estado como "En proceso" (1)
            $service->setServicestatus(1);

            // Manejar el archivo de factura PDF si existe
            $facturaFile = $request->files->get('factura_compra');

            if ($facturaFile && $facturaFile->isValid()) {
                try {
                    // Generar un nombre único para el archivo
                    $filename = 'factura_' . $nextId . '_' . uniqid() . '.pdf';

                    // Mover el archivo a /tmp
                    $facturaFile->move('/tmp', $filename);

                    // Guardar la ruta completa en la entidad
                    $filepath = '/tmp/' . $filename;
                    $service->setFacturaFilepath($filepath);

                    $this->addFlash('info', 'Factura guardada en: ' . $filepath);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Error al guardar la factura: ' . $e->getMessage());
                }
            } else {
                // Debug: verificar si el archivo está llegando
                $allFiles = $request->files->all();
                if (!empty($allFiles)) {
                    $this->addFlash('warning', 'Archivos recibidos: ' . implode(', ', array_keys($allFiles)));
                }
            }

            $entityManager->persist($service);
            $entityManager->flush();

            $this->addFlash('success', 'Servicio creado exitosamente.');

            return $this->redirectToRoute('app_secure_internal_services');
        }

        return $this->render('secure/internal/services/form.html.twig', [
            'form' => $form->createView(),
            'service' => $service,
            'title' => 'Nuevo Servicio',
            'provinciasByPais' => json_encode($provinciasByPais)
        ]);
    }

    #[Route('/{id}/editar', name: 'app_secure_internal_services_edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, int $id, ServiciosRepository $serviciosRepository, EntityManagerInterface $entityManager, ProvinciasRepository $provinciasRepository): Response
    {
        $service = $serviciosRepository->find($id);

        if (!$service) {
            throw $this->createNotFoundException('Servicio no encontrado');
        }

        // Obtener todas las provincias agrupadas por país
        $allProvincias = $provinciasRepository->findAll();
        $provinciasByPais = [];
        foreach ($allProvincias as $provincia) {
            $paisId = $provincia->getPaisId();
            if (!isset($provinciasByPais[$paisId])) {
                $provinciasByPais[$paisId] = [];
            }
            $provinciasByPais[$paisId][] = [
                'id' => $provincia->getProvinciaId(),
                'nombre' => $provincia->getProvinciaNombre()
            ];
        }

        $form = $this->createForm(ServicesFormType::class, $service);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Servicio actualizado exitosamente.');

            return $this->redirectToRoute('app_secure_internal_services');
        }

        return $this->render('secure/internal/services/form.html.twig', [
            'form' => $form->createView(),
            'service' => $service,
            'title' => 'Editar Servicio',
            'provinciasByPais' => json_encode($provinciasByPais)
        ]);
    }

    #[Route('/{id}', name: 'app_secure_internal_services_show', requirements: ['id' => '\d+'])]
    public function show(int $id, ServiciosRepository $serviciosRepository): Response
    {
        $service = $serviciosRepository->find($id);

        if (!$service) {
            throw $this->createNotFoundException('Servicio no encontrado');
        }

        return $this->render('secure/internal/services/show.html.twig', [
            'service' => $service,
            'title' => 'Detalle del Servicio'
        ]);
    }

    #[Route('/{id}/eliminar', name: 'app_secure_internal_services_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, int $id, ServiciosRepository $serviciosRepository, EntityManagerInterface $entityManager): Response
    {
        $service = $serviciosRepository->find($id);

        if (!$service) {
            throw $this->createNotFoundException('Servicio no encontrado');
        }

        if ($this->isCsrfTokenValid('delete' . $service->getServiceid(), $request->request->get('_token'))) {
            $entityManager->remove($service);
            $entityManager->flush();

            $this->addFlash('success', 'Servicio eliminado exitosamente.');
        }

        return $this->redirectToRoute('app_secure_internal_services');
    }
}
