<?php

namespace App\Controller\Secure\External\ServiceRequests;

use App\Entity\ServiceRequests;
use App\Form\ServiceRequestsFormType;
use App\Repository\ServiceRequestsRepository;
use App\Repository\ProvinciasRepository;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/secure/service-requests-externos')]
class ServiceRequestsController extends AbstractController
{
    public function __construct(private MailerInterface $mailer)
    {}
    #[Route('/', name: 'app_secure_external_service_requests')]
    public function index(ServiceRequestsRepository $serviceRequestsRepository): Response
    {
        $user = $this->getUser();

        // Get all service requests for this user
        $serviceRequests = $serviceRequestsRepository->createQueryBuilder('s')
            ->where('s.user = :user')
            ->setParameter('user', $user)
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('secure/external/service_requests/index.html.twig', [
            'serviceRequests' => $serviceRequests,
            'title' => 'Mis Solicitudes de Servicio'
        ]);
    }

    #[Route('/nuevo', name: 'app_secure_external_service_requests_new')]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, ProvinciasRepository $provinciasRepository): Response
    {
        $user = $this->getUser();

        $serviceRequest = new ServiceRequests();
        $serviceRequest->setCreatedAt(new \DateTime());
        $serviceRequest->setEstado('pendiente');
        $serviceRequest->setUser($user);

        // Pre-llenar con datos del usuario autenticado
        $serviceRequest->setContacto($user->getFirstName() . ' ' . $user->getLastName());
        $serviceRequest->setEmail($user->getEmail());

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

        $form = $this->createForm(ServiceRequestsFormType::class, $serviceRequest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Manejar el archivo de factura (SIEMPRE REQUERIDO en External)
            $facturaFile = $form->get('facturaCompra')->getData();

            if (!$facturaFile) {
                $this->addFlash('error', 'El archivo de factura es obligatorio.');

                return $this->render('secure/external/service_requests/form.html.twig', [
                    'form' => $form->createView(),
                    'serviceRequest' => $serviceRequest,
                    'title' => 'Nueva Solicitud de Servicio'
                ]);
            }

            $originalFilename = pathinfo($facturaFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$facturaFile->guessExtension();

            try {
                // Guardar temporalmente en /tmp
                $facturaFile->move('/tmp', $newFilename);
                $serviceRequest->setFacturaCompraFilename($newFilename);
            } catch (FileException $e) {
                $this->addFlash('error', 'Hubo un problema al subir el archivo de factura.');

                return $this->render('secure/external/service_requests/form.html.twig', [
                    'form' => $form->createView(),
                    'serviceRequest' => $serviceRequest,
                    'title' => 'Nueva Solicitud de Servicio'
                ]);
            }

            $entityManager->persist($serviceRequest);
            $entityManager->flush();

            $this->addFlash('success', 'Solicitud de servicio creada exitosamente.');

            $this->enviarEmailAlCliente($serviceRequest->getId(),$user->getEmail(),$serviceRequest);
            $this->enviarEmailAlOperador($serviceRequest->getId(),$serviceRequest);

            return $this->redirectToRoute('app_secure_external_service_requests');
        }

        return $this->render('secure/external/service_requests/form.html.twig', [
            'form' => $form->createView(),
            'serviceRequest' => $serviceRequest,
            'title' => 'Nueva Solicitud de Servicio',
            'provinciasByPais' => json_encode($provinciasByPais)
        ]);
    }

    #[Route('/{id}/editar', name: 'app_secure_external_service_requests_edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, int $id, ServiceRequestsRepository $serviceRequestsRepository, EntityManagerInterface $entityManager, SluggerInterface $slugger, ProvinciasRepository $provinciasRepository): Response
    {
        $serviceRequest = $serviceRequestsRepository->find($id);

        if (!$serviceRequest) {
            throw $this->createNotFoundException('Solicitud no encontrada');
        }

        $user = $this->getUser();

        // Verify that this service request belongs to the user
        if ($serviceRequest->getUser() !== $user) {
            throw $this->createAccessDeniedException('No tiene permiso para editar esta solicitud');
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

        $form = $this->createForm(ServiceRequestsFormType::class, $serviceRequest);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Manejar el archivo de factura si fue subido
            $facturaFile = $form->get('facturaCompra')->getData();

            if ($facturaFile) {
                // Eliminar archivo anterior si existe
                if ($serviceRequest->getFacturaCompraFilename()) {
                    $oldFile = '/tmp/' . $serviceRequest->getFacturaCompraFilename();
                    if (file_exists($oldFile)) {
                        unlink($oldFile);
                    }
                }

                $originalFilename = pathinfo($facturaFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$facturaFile->guessExtension();

                try {
                    // Guardar temporalmente en /tmp
                    $facturaFile->move('/tmp', $newFilename);
                    $serviceRequest->setFacturaCompraFilename($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Hubo un problema al subir el archivo de factura.');

                    return $this->render('secure/external/service_requests/form.html.twig', [
                        'form' => $form->createView(),
                        'serviceRequest' => $serviceRequest,
                        'title' => 'Editar Solicitud de Servicio',
                        'provinciasByPais' => json_encode($provinciasByPais)
                    ]);
                }
            }

            $serviceRequest->setUpdatedAt(new \DateTime());
            $entityManager->flush();

            $this->addFlash('success', 'Solicitud de servicio actualizada exitosamente.');

            return $this->redirectToRoute('app_secure_external_service_requests');
        }

        return $this->render('secure/external/service_requests/form.html.twig', [
            'form' => $form->createView(),
            'serviceRequest' => $serviceRequest,
            'title' => 'Editar Solicitud de Servicio',
            'provinciasByPais' => json_encode($provinciasByPais)
        ]);
    }

    #[Route('/{id}', name: 'app_secure_external_service_requests_show', requirements: ['id' => '\d+'])]
    public function show(int $id, ServiceRequestsRepository $serviceRequestsRepository): Response
    {
        $serviceRequest = $serviceRequestsRepository->find($id);

        if (!$serviceRequest) {
            throw $this->createNotFoundException('Solicitud no encontrada');
        }

        $user = $this->getUser();

        // Verify that this service request belongs to the user
        if ($serviceRequest->getUser() !== $user) {
            throw $this->createAccessDeniedException('No tiene permiso para ver esta solicitud');
        }

        return $this->render('secure/external/service_requests/show.html.twig', [
            'serviceRequest' => $serviceRequest,
            'title' => 'Detalle de la Solicitud'
        ]);
    }
    public function enviarEmailAlCliente($numero_seguimiento,$mail_to,$solicitud_servicio)
    {
        $dompdf = new Dompdf();
        $htmlPdf = $this->renderView('pdf/adjunto_mail_solicitud.html.twig', [
            'solicitud' => $solicitud_servicio,
            'numero_seguimiento' => $numero_seguimiento
        ]);
        $dompdf->loadHtml($htmlPdf);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdfContent = $dompdf->output();

        $template = "emails/seguimiento_servicio_cliente.html.twig";
        $email = (new Email())
            ->from($_ENV['MAIL_FROM'])
            ->to($mail_to)
            ->subject('Solicitud de servicio')
            ->html($this->renderView($template, [
                'numero_seguimiento' => $numero_seguimiento
            ]))
            ->attach($pdfContent, 'solicitud_servicio.pdf', 'application/pdf');
        $this->mailer->send($email);
    }
    public function enviarEmailAlOperador($numero_seguimiento,$solicitud_servicio)
    {
        $dompdf = new Dompdf();
        $htmlPdf = $this->renderView('pdf/adjunto_mail_solicitud.html.twig', [
            'solicitud' => $solicitud_servicio,
            'numero_seguimiento' => $numero_seguimiento
        ]);
        $dompdf->loadHtml($htmlPdf);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdfContent = $dompdf->output();

        $template = "emails/seguimiento_servicio_operador.html.twig";
        $email = (new Email())
            ->from($_ENV['MAIL_FROM'])
            ->to($_ENV['MAIL_CENTRO_RAC']) 
            ->subject('Solicitud de servicio')
            ->html($this->renderView($template, [
                'numero_seguimiento' => $numero_seguimiento,
                'solicitud' => $solicitud_servicio
            ]))
            ->attach($pdfContent, 'solicitud_servicio.pdf', 'application/pdf');
        $this->mailer->send($email);
    }
}