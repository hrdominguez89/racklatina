<?php

namespace App\Controller\Secure\Internal\Account;

use App\Repository\ClientesRepository;
use App\Repository\ComprobantesimpagosRepository;
use App\Repository\CuentascorrientesRepository;
use Exception;
use Symfony\Component\Mime\Email;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
#[Route('/seccion')]
final class SeccionCuentaController extends AbstractController{
    public function __construct(private MailerInterface $mailer) 
    {
        $this->mailer = $mailer;
    }
    #[Route('/cuenta', name: 'app_seccion_cuenta')]
    public function index(): Response
    {
        return $this->render('secure/internal/seccion_cuenta/index.html.twig', [
            'controller_name' => 'SeccionCuentaController',
        ]);

    }
    #[Route('/cuenta/cuentasCorrientes',"app_cuentas_corrientes")]
    public function cuentasCorrientes(Request $request,
    CuentascorrientesRepository $cuentasCorrientesRepository)
    {
        $cuentas = $cuentasCorrientesRepository->findAll();
        return $this->render('secure/internal/seccion_cuenta/cuentas_corrientes.html.twig', [
            'controller_name' => 'SeccionCuentaController',
            "cuentas" => $cuentas
        ]);
    }
    
    #[Route('/cuenta/comprobantesSaldados',"app_comprobantes_saldados")]
    public function comprobantesSaldados(Request $request,
    CuentascorrientesRepository $cuentascorrientesRepository
    )
    {
        return $this->render('secure/internal/seccion_cuenta/comprobantes_saldados.html.twig');
    }

    #[Route("/cuenta/obtenerSaldados","app_c_saldados")]
    public function obtenerSaldados(Request $request,
    CuentascorrientesRepository $cuentascorrientesRepository,
    ClientesRepository $clientesRepository)
    {
        $search = $request->query->get('search');
        $comprobantes = [];
        if ($search) {
            $clientes = $clientesRepository->findClientesPorRazonSocial($search);
        }
        if($clientes)
        {
            foreach($clientes as $cliente)
            {
                $comprobantes = $cuentascorrientesRepository->findComprobantesSaldados($cliente["codigoCalipso"]);
            }
        }
        return $this->render(
            'secure/internal/seccion_cuenta/comprobantes_saldados.html.twig',
            [
                'controller_name' => 'SeccionCuentaController',
                'comprobantes' => $comprobantes
            ]
        );
    }



    #[Route('/cuenta/comprobantesImpagos',"app_comprobantes_impagos")]
    public function ComprobantesImpagos(Request $request,
    ComprobantesimpagosRepository $comprobantesimpagosRepository): Response
    {
        return $this->render('secure/internal/seccion_cuenta/comprobantes_impagos_vencimientos.html.twig');
    }
    #[Route('/obtenerComprobantesImpagos',"app_c_impagos")]
    public function obtenerImpagos(Request $request,
     ComprobantesimpagosRepository $comprobantesimpagosRepository,
     ClientesRepository $clientesRepository): Response
    {
        $search = $request->query->get('search');
        $comprobantes = [];
        $clientes=[];
        if ($search) {
            $clientes = $clientesRepository->findClientesPorRazonSocial($search);
        }
        if($clientes)
        {
            foreach($clientes as $cliente)
            {
                $comprobantes = $comprobantesimpagosRepository->findComprobantesImpagosByCliente($cliente["codigoCalipso"]);
            }
        }
        return $this->render(
            'secure/internal/seccion_cuenta/comprobantes_impagos_vencimientos.html.twig',
            [
                'controller_name' => 'SeccionCuentaController',
                'comprobantes' => $comprobantes
            ]
        );
    }

    #[Route('/obtenerFacturas',"descarga_facturas")]
    public function obtenerFactura(Request $request,HttpClientInterface $httpClient)
    {
        $fileName = $request->query->get("factura");
        // $nombreArchivo = 'FA0019900000071.pdf';
        $rutaArchivo = "../Facturas/{$fileName}";

        try {
            $response = $httpClient->request('POST', 'https://192.168.16.104/appserver/api/?action=generapdf&token='.$_ENV["TOKEN"],
            [
                'verify_peer' => false,
                'verify_host' => false,
                'json' => [
                    'modulo' => 'VENTAS',
                    'comprobante' => $fileName
                ]
            ]);
            
            $statusCode = $response->getStatusCode();
            
            if ($statusCode === 200) {
                if (file_exists($rutaArchivo)) {
                    // return $this->file($rutaArchivo);
                    
                    return $this->file($rutaArchivo, $fileName, ResponseHeaderBag::DISPOSITION_ATTACHMENT);
                } else {
                    return $this->json(['error' => 'El archivo no se generó correctamente'], 404);
                }
            } else {
                return $this->json(['error' => 'Error en la API'], $statusCode);
            }
            
        } catch(Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }
    #[Route('/enviarMail','app_notificar_pago')]
    public function enviarNotificacion(Request $request)
    {
        $data["mensaje"] = $request->query->get("mensaje");
        $archivos = $request->files->get('archivos');

        $email = (new Email())
            ->from($_ENV['MAIL_FROM'])
            ->to($_ENV["MAIL_FROM"])
            ->subject('Notificacion de pago.')
            ->html($this->renderView('emails/_notificacion_de_pago.html.twig',$data));

        // Adjuntar archivos si existen
        if ($archivos) {
            foreach ($archivos as $archivo) {
                if ($archivo) {
                    $email->attach(
                        $archivo->getContent(),
                        $archivo->getClientOriginalName(),
                        $archivo->getMimeType()
                    );
                }
            }
        }

        $this->mailer->send($email);
    }
}