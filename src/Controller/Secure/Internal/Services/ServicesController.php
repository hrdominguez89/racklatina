<?php

namespace App\Controller\Secure\Internal\Services;

use App\Entity\Servicios;
use App\Form\ServicesFormType;
use App\Repository\PaisRepository;
use App\Repository\ServiciosRepository;
use App\Repository\ProvinciasRepository;
use App\Repository\ServiciosMarcasRepository;
use App\Repository\ServiciosTipoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Email;
use Dompdf\Dompdf;

#[Route('/secure/servicios-internos')]
class ServicesController extends AbstractController
{
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer,
    private PaisRepository $pais_repository,
    private ServiciosMarcasRepository $marcas_repository,
    private ServiciosRepository $servicios_repository,
    private ServiciosTipoRepository $tipo_repository,
    private ProvinciasRepository $provincia_repository)
    {
        $this->mailer = $mailer;
    }

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

            // Enviar email al operador
            $this->enviarEmailAlOperador( $service);
            $this->enviarEmailAlCliente($service);

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
    private function enviarEmailAlOperador($servicio)
    {
        $marca_id = $servicio->getServicemarcaid();
        $marca = $this->marcas_repository->findOneBy(['serviceprodid'=>$marca_id]);
        $marca_nombre = $marca ? $marca->getServiceproddescrip() : null;
        
        $pais_id = $servicio->getServicepaisid();
        $pais = $this->pais_repository->findOneBy(["paisId"=>$pais_id]);
        $pais_nombre = $pais ? $pais->getPaisNombre() : null;
        
        $tipo_id = $servicio->getServicetypeid();
        $tipo = $this->tipo_repository->findOneBy([ "servicetypeid" => $tipo_id ]);
        $tipo_nombre = $tipo ? $tipo->getServicetypedescrip() : null;
        
        $provincia_id = $servicio->getServiceprovinciaid();
        $provincia = $this->provincia_repository->findOneBy([ "provinciaId" => $provincia_id ]);
        $provincia_nombre = $provincia ? $provincia->getProvinciaNombre() : null;
        

        $logoBase64 = base64_encode(file_get_contents($this->getParameter('kernel.project_dir') . '/assets/images/logo-racklatina-light.png'));

        $htmlPdf = $this->renderView('pdf/adjunto_mail_solicitud.html.twig', [
            'solicitud' => $servicio,
            'marca' => $marca_nombre,
            'pais' => $pais_nombre,
            'logo' => $logoBase64,
            'tipo' => $tipo_nombre
        ]);

        $dompdf = new Dompdf();
        $dompdf->loadHtml($htmlPdf);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $pdfContent = $dompdf->output();

        $template = "emails/seguimiento_servicio_operador.html.twig";
        $email = (new Email())
            ->from($_ENV['MAIL_FROM'])
            ->to('l.e.marguery@gmail.com')
            // ->to($_ENV['MAIL_CENTRO_RAC'])
            ->subject('Solicitud de servicio')
            ->html($this->renderView($template, [
                'numero_seguimiento' => $servicio->getServicenroseguimiento(),
                'solicitud' => $servicio,
                'pais' => $pais_nombre,
                'marca' => $marca_nombre,
                'provincia' => $provincia_nombre
            ]))
            ->attach($pdfContent, 'solicitud_servicio.pdf', 'application/pdf');
        $this->mailer->send($email);
    }
    
    public function enviarEmailAlCliente($servicio)
    {
        $marca_id = $servicio->getServicemarcaid();
        $marca = $this->marcas_repository->findOneBy(['serviceprodid'=>$marca_id]);
        $marca_nombre = $marca ? $marca->getServiceproddescrip() : null;
        
        $pais_id = $servicio->getServicepaisid();
        $pais = $this->pais_repository->findOneBy(["paisId"=>$pais_id]);
        $pais_nombre = $pais ? $pais->getPaisNombre() : null;

        $tipo_id = $servicio->getServicetypeid();
        $tipo = $this->tipo_repository->findOneBy([ "servicetypeid" => $tipo_id ]);
        $tipo_nombre = $tipo ? $tipo->getServicetypedescrip() : null;
        
        $logoBase64 = base64_encode(file_get_contents($this->getParameter('kernel.project_dir') . '/assets/images/logo-racklatina-light.png'));
        $dompdf = new Dompdf();
        $htmlPdf = $this->renderView('pdf/adjunto_mail_solicitud.html.twig', [
            'solicitud' => $servicio,
            'marca' => $marca_nombre,
            'pais' => $pais_nombre,
            'logo' => $logoBase64,
            'tipo' => $tipo_nombre
        ]);
        $dompdf->loadHtml($htmlPdf);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdfContent = $dompdf->output();

        $template = "emails/seguimiento_servicio_cliente.html.twig";
        $email = (new Email())
            ->from($_ENV['MAIL_FROM'])
            // ->to($servicio->getEmail())
            ->to("l.e.marguery@gmail.com")
            ->subject('Solicitud de servicio')
            ->html($this->renderView($template, [
                'numero_seguimiento' => $servicio->getServicenroseguimiento(),
            ]))
            ->attach($pdfContent, 'solicitud_servicio.pdf', 'application/pdf');

        $this->mailer->send($email);
    }
    #[Route('/download-i' , name:'app_interno_formulario_descarga')]
    public function descargaPdf(Request $request)
    {
        //reporsitory de servicio paso el id por aca 
        // dd($request);
        $servicio_id =  $request->query->get('id');
        if(!$servicio_id)
        {
            return $this->json(
                ['message' => "No se envio el id"]
                ,403);
        }
        $servicio  = $this->servicios_repository->findOneBy(["serviceid" => $servicio_id]);
        if(!$servicio)
        {
            return $this->json(
                ['message' => "No se encontro el servicio"]
                ,404);
        }
        $marca_id = $servicio->getServicemarcaid();
        $marca = $this->marcas_repository->findOneBy(['serviceprodid'=>$marca_id]);
        $marca_nombre = $marca ? $marca->getServiceproddescrip() : null;
        
        $pais_id = $servicio->getServicepaisid();
        $pais = $this->pais_repository->findOneBy(["paisId"=>$pais_id]);
        $pais_nombre = $pais ? $pais->getPaisNombre() : null;
        
        $tipo_id = $servicio->getServicetypeid();
        $tipo = $this->tipo_repository->findOneBy([ "servicetypeid" => $tipo_id ]);
        $tipo_nombre = $tipo ? $tipo->getServicetypedescrip() : null;
        
        $provincia_id = $servicio->getServiceprovinciaid();
        $provincia = $this->provincia_repository->findOneBy([ "provinciaId" => $provincia_id ]);
        $provincia_nombre = $provincia ? $provincia->getProvinciaNombre() : null;
        
        $logoBase64 = base64_encode(file_get_contents($this->getParameter('kernel.project_dir') . '/assets/images/logo-racklatina-light.png'));

        $dompdf = new Dompdf();
        $htmlPdf = $this->renderView('pdf/adjunto_mail_solicitud.html.twig', [
             'solicitud' => $servicio,
            'marca' => $marca_nombre,
            'pais' => $pais_nombre,
            'logo' => $logoBase64,
            'tipo' => $tipo_nombre
        ]);
        $dompdf->loadHtml($htmlPdf);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $pdfContent = $dompdf->output();
        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="solicitud_servicio.pdf"'
        ]);
    }
}
