<?php

namespace App\Controller\Public\Services;

use App\Entity\Servicios;
use App\Entity\ServiciosAdjuntos;
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
use Symfony\Component\Mime\Email;
use Dompdf\Dompdf;

#[Route('/servicios')]
class PublicServicesController extends AbstractController
{
    private MailerInterface $mailer;

    public function __construct(
        MailerInterface $mailer,
        private PaisRepository $pais_repository,
        private ServiciosMarcasRepository $marcas_repository,
        private ServiciosRepository $servicios_repository,
        private ServiciosTipoRepository $tipo_repository,
        private ProvinciasRepository $provincia_repository
    ) {
        $this->mailer = $mailer;
    }

    #[Route('/nuevo', name: 'app_public_services_new')]
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

        // Crear el formulario SIN deshabilitar campos de contacto (es público)
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

            // Generar el siguiente serviceNroSeguimiento (MAX + 1)
            $nroSeguimiento = $this->generarNroSeguimiento($serviciosRepository);
            $service->setServicenroseguimiento($nroSeguimiento);

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

                    // Crear un registro en ServiciosAdjuntos
                    $adjunto = new ServiciosAdjuntos();
                    $adjunto->setFilename($filename);
                    $adjunto->setFilepath('/tmp/' . $filename);
                    $adjunto->setServicio($service);

                    $entityManager->persist($adjunto);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Error al guardar la factura: ' . $e->getMessage());
                }
            }

            $entityManager->persist($service);
            $entityManager->flush();

            // Enviar emails
            $this->enviarEmailAlOperador($service);
            $this->enviarEmailAlCliente($service);

            // Redirigir a página de confirmación
            return $this->redirectToRoute('app_public_services_success', [
                'nroSeguimiento' => $nroSeguimiento
            ]);
        }

        return $this->render('public/services/form.html.twig', [
            'form' => $form->createView(),
            'service' => $service,
            'title' => 'Solicitar Servicio Técnico',
            'provinciasByPais' => json_encode($provinciasByPais)
        ]);
    }

    #[Route('/exito', name: 'app_public_services_success')]
    public function success(Request $request): Response
    {
        $nroSeguimiento = $request->query->get('nroSeguimiento');

        return $this->render('public/services/success.html.twig', [
            'nroSeguimiento' => $nroSeguimiento,
            'title' => 'Solicitud Enviada'
        ]);
    }

    private function generarNroSeguimiento(ServiciosRepository $serviciosRepository): string
    {
        $maxAttempts = 10;
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            // Obtener todos los números de seguimiento y encontrar el máximo manualmente
            $nrosSeguimiento = $serviciosRepository->createQueryBuilder('s')
                ->select('s.servicenroseguimiento')
                ->where('s.servicenroseguimiento IS NOT NULL')
                ->andWhere('s.servicenroseguimiento != :empty')
                ->setParameter('empty', '')
                ->getQuery()
                ->getScalarResult();

            $maxNroSeguimiento = 0;
            foreach ($nrosSeguimiento as $nro) {
                $valor = (int)$nro['servicenroseguimiento'];
                if ($valor > $maxNroSeguimiento) {
                    $maxNroSeguimiento = $valor;
                }
            }

            $nextNroSeguimiento = $maxNroSeguimiento + 1;
            $nextNroSeguimientoStr = (string)$nextNroSeguimiento;

            // Verificar que no exista este número en la base de datos
            $existe = $serviciosRepository->createQueryBuilder('s')
                ->select('COUNT(s.serviceid)')
                ->where('s.servicenroseguimiento = :nro')
                ->setParameter('nro', $nextNroSeguimientoStr)
                ->getQuery()
                ->getSingleScalarResult();

            if ($existe == 0) {
                return $nextNroSeguimientoStr;
            }

            $attempt++;
        }

        // Si después de los intentos sigue habiendo colisión, usar timestamp
        return (string)time();
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
            ->to($_ENV['MAIL_CENTRO_RAC'])
            ->subject('Solicitud de servicio (Formulario Público)')
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
            ->to($servicio->getServiceemail())
            ->subject('Solicitud de servicio')
            ->html($this->renderView($template, [
                'numero_seguimiento' => $servicio->getServicenroseguimiento(),
            ]))
            ->attach($pdfContent, 'solicitud_servicio.pdf', 'application/pdf');

        $this->mailer->send($email);
    }
}
