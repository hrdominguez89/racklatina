<?php

namespace App\Controller\Secure\External\Services;

use App\Entity\Servicios;
use App\Form\ServicesFormType;
use App\Repository\ServiciosRepository;
use App\Repository\ProvinciasRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/secure/servicios-externos')]
class ServicesController extends AbstractController
{
    #[Route('/', name: 'app_secure_external_services')]
    public function index(ServiciosRepository $serviciosRepository): Response
    {
        $user = $this->getUser();

        // Get all services for this user based on their email
        $services = $serviciosRepository->createQueryBuilder('s')
            ->where('s.serviceemail = :email')
            ->setParameter('email', $user->getEmail())
            ->orderBy('s.servicedate', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('secure/external/services/index.html.twig', [
            'services' => $services,
            'title' => 'Mis Servicios'
        ]);
    }

    #[Route('/nuevo', name: 'app_secure_external_services_new')]
    public function new(Request $request, EntityManagerInterface $entityManager, ProvinciasRepository $provinciasRepository, ServiciosRepository $serviciosRepository): Response
    {
        $user = $this->getUser();

        $service = new Servicios();
        $service->setServicedate(new \DateTime());
        $service->setServiceemail($user->getEmail());
        $service->setServicecontacto($user->getFirstName() . ' ' . $user->getLastName());

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

        $form = $this->createForm(ServicesFormType::class, $service, [
            'disable_contact_fields' => true
        ]);
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
            }

            $entityManager->persist($service);
            $entityManager->flush();

            $this->addFlash('success', 'Servicio creado exitosamente.');

            return $this->redirectToRoute('app_secure_external_services');
        }

        return $this->render('secure/external/services/form.html.twig', [
            'form' => $form->createView(),
            'service' => $service,
            'title' => 'Nuevo Servicio',
            'provinciasByPais' => json_encode($provinciasByPais)
        ]);
    }

    #[Route('/{id}/editar', name: 'app_secure_external_services_edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, int $id, ServiciosRepository $serviciosRepository, EntityManagerInterface $entityManager, ProvinciasRepository $provinciasRepository): Response
    {
        $service = $serviciosRepository->find($id);

        if (!$service) {
            throw $this->createNotFoundException('Servicio no encontrado');
        }

        $user = $this->getUser();

        // Verify that this service belongs to the user
        if ($service->getServiceemail() !== $user->getEmail()) {
            throw $this->createAccessDeniedException('No tiene permiso para editar este servicio');
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

        $form = $this->createForm(ServicesFormType::class, $service, [
            'disable_contact_fields' => true
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Manejar el archivo de factura PDF si existe
            $facturaFile = $request->files->get('factura_compra');

            if ($facturaFile && $facturaFile->isValid()) {
                try {
                    // Generar un nombre único para el archivo
                    $filename = 'factura_' . $service->getServiceid() . '_' . uniqid() . '.pdf';

                    // Mover el archivo a /tmp
                    $facturaFile->move('/tmp', $filename);

                    // Guardar la ruta completa en la entidad
                    $filepath = '/tmp/' . $filename;
                    $service->setFacturaFilepath($filepath);

                    $this->addFlash('info', 'Factura actualizada en: ' . $filepath);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Error al actualizar la factura: ' . $e->getMessage());
                }
            }

            $entityManager->flush();

            $this->addFlash('success', 'Servicio actualizado exitosamente.');

            return $this->redirectToRoute('app_secure_external_services');
        }

        return $this->render('secure/external/services/form.html.twig', [
            'form' => $form->createView(),
            'service' => $service,
            'title' => 'Editar Servicio',
            'provinciasByPais' => json_encode($provinciasByPais)
        ]);
    }

    #[Route('/{id}', name: 'app_secure_external_services_show', requirements: ['id' => '\d+'])]
    public function show(int $id, ServiciosRepository $serviciosRepository): Response
    {
        $service = $serviciosRepository->find($id);

        if (!$service) {
            throw $this->createNotFoundException('Servicio no encontrado');
        }

        $user = $this->getUser();

        // Verify that this service belongs to the user
        if ($service->getServiceemail() !== $user->getEmail()) {
            throw $this->createAccessDeniedException('No tiene permiso para ver este servicio');
        }

        return $this->render('secure/external/services/show.html.twig', [
            'service' => $service,
            'title' => 'Detalle del Servicio'
        ]);
    }
}
