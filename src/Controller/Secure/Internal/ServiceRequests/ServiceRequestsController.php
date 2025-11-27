<?php

namespace App\Controller\Secure\Internal\ServiceRequests;

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

#[Route('/secure/service-requests-internos')]
class ServiceRequestsController extends AbstractController
{
    public function __construct(private MailerInterface $mailer)
    {}
    #[Route('/', name: 'app_secure_internal_service_requests')]
    public function index(Request $request, ServiceRequestsRepository $serviceRequestsRepository): Response
    {
        $searchTerm = $request->query->get('search', '');
        $statusFilter = $request->query->get('status', '');

        $qb = $serviceRequestsRepository->createQueryBuilder('s');

        if ($searchTerm) {
            $qb->andWhere('s.empresa LIKE :search
                          OR s.email LIKE :search
                          OR s.nroSerie LIKE :search
                          OR s.contacto LIKE :search')
               ->setParameter('search', '%' . $searchTerm . '%');
        }

        if ($statusFilter !== '') {
            $qb->andWhere('s.estado = :status')
               ->setParameter('status', $statusFilter);
        }

        $serviceRequests = $qb->orderBy('s.createdAt', 'DESC')
                        ->getQuery()
                        ->getResult();

        return $this->render('secure/internal/service_requests/index.html.twig', [
            'serviceRequests' => $serviceRequests,
            'searchTerm' => $searchTerm,
            'statusFilter' => $statusFilter,
            'title' => 'Gestión de Solicitudes de Servicio'
        ]);
    }

    #[Route('/nuevo', name: 'app_secure_internal_service_requests_new')]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, ProvinciasRepository $provinciasRepository): Response
    {
        $serviceRequest = new ServiceRequests();
        $serviceRequest->setCreatedAt(new \DateTime());
        $serviceRequest->setEstado('pendiente');
        $serviceRequest->setUser($this->getUser());

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
                $originalFilename = pathinfo($facturaFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$facturaFile->guessExtension();

                try {
                    // Guardar temporalmente en /tmp
                    $facturaFile->move('/tmp', $newFilename);
                    $serviceRequest->setFacturaCompraFilename($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Hubo un problema al subir el archivo de factura.');

                    return $this->render('secure/internal/service_requests/form.html.twig', [
                        'form' => $form->createView(),
                        'serviceRequest' => $serviceRequest,
                        'title' => 'Nueva Solicitud de Servicio'
                    ]);
                }
            }

            $entityManager->persist($serviceRequest);
            $entityManager->flush();
            $this->enviarEmailAlCliente($serviceRequest->getId(),$serviceRequest);
            $this->enviarEmailAlOperador($serviceRequest->getId(),$serviceRequest);
            $this->addFlash('success', 'Solicitud de servicio creada exitosamente.');

            return $this->redirectToRoute('app_secure_internal_service_requests');
        }

        return $this->render('secure/internal/service_requests/form.html.twig', [
            'form' => $form->createView(),
            'serviceRequest' => $serviceRequest,
            'title' => 'Nueva Solicitud de Servicio',
            'provinciasByPais' => json_encode($provinciasByPais)
        ]);
    }

    #[Route('/{id}/editar', name: 'app_secure_internal_service_requests_edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, ServiceRequests $serviceRequest, EntityManagerInterface $entityManager, SluggerInterface $slugger, ProvinciasRepository $provinciasRepository): Response
    {
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

                    return $this->render('secure/internal/service_requests/form.html.twig', [
                        'form' => $form->createView(),
                        'serviceRequest' => $serviceRequest,
                        'title' => 'Editar Solicitud de Servicio'
                    ]);
                }
            }

            $serviceRequest->setUpdatedAt(new \DateTime());
            $entityManager->flush();

            $this->addFlash('success', 'Solicitud de servicio actualizada exitosamente.');

            return $this->redirectToRoute('app_secure_internal_service_requests');
        }

        return $this->render('secure/internal/service_requests/form.html.twig', [
            'form' => $form->createView(),
            'serviceRequest' => $serviceRequest,
            'title' => 'Editar Solicitud de Servicio',
            'provinciasByPais' => json_encode($provinciasByPais)
        ]);
    }

    #[Route('/{id}', name: 'app_secure_internal_service_requests_show', requirements: ['id' => '\d+'])]
    public function show(ServiceRequests $serviceRequest): Response
    {
        return $this->render('secure/internal/service_requests/show.html.twig', [
            'serviceRequest' => $serviceRequest,
            'title' => 'Detalle de la Solicitud'
        ]);
    }

    #[Route('/{id}/eliminar', name: 'app_secure_internal_service_requests_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, ServiceRequests $serviceRequest, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $serviceRequest->getId(), $request->request->get('_token'))) {
            $entityManager->remove($serviceRequest);
            $entityManager->flush();

            $this->addFlash('success', 'Solicitud de servicio eliminada exitosamente.');
        }

        return $this->redirectToRoute('app_secure_internal_service_requests');
    }
     public function enviarEmailAlCliente($numero_seguimiento,$solicitud_servicio)
    {
        $logoBase64 = base64_encode(file_get_contents($this->getParameter('kernel.project_dir') . '/assets/images/logo-racklatina-light.png'));
        $dompdf = new Dompdf();
        $htmlPdf = $this->renderView('pdf/adjunto_mail_solicitud.html.twig', [
            'solicitud' => $solicitud_servicio,
            'numero_seguimiento' => $numero_seguimiento,
            'logo' => $logoBase64
        ]);
        $dompdf->loadHtml($htmlPdf);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdfContent = $dompdf->output();

        $template = "emails/seguimiento_servicio_cliente.html.twig";
        $email = (new Email())
            ->from($_ENV['MAIL_FROM'])
            ->to($solicitud_servicio->getEmail())
            ->subject('Solicitud de servicio')
            ->html($this->renderView($template, [
                'numero_seguimiento' => $numero_seguimiento,
            ]))
            ->attach($pdfContent, 'solicitud_servicio.pdf', 'application/pdf');

        $this->mailer->send($email);
    }
    public function enviarEmailAlOperador($numero_seguimiento,$solicitud_servicio)
    {
        $logoBase64 = base64_encode(file_get_contents($this->getParameter('kernel.project_dir') . '/assets/images/logo-racklatina-light.png'));
        $dompdf = new Dompdf();
        $htmlPdf = $this->renderView('pdf/adjunto_mail_solicitud.html.twig', [
            'solicitud' => $solicitud_servicio,
            'numero_seguimiento' => $numero_seguimiento,
            'logo' => $logoBase64
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
