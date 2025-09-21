<?php

namespace App\Controller\Secure\Internal\Account;

use App\Repository\ClientesRepository;
use App\Repository\ComprobantesimpagosRepository;
use App\Repository\CuentascorrientesRepository;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mime\Email;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
#[Route('/secure/seccion-cuenta')]
final class SeccionCuentaController extends AbstractController{
    public function __construct(private MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }
    #[Route('/', name: 'app_seccion_cuenta_internal')]
    public function index(): Response
    {
        return $this->render('secure/internal/seccion_cuenta/index.html.twig', [
            'controller_name' => 'SeccionCuentaController',
        ]);

    }
    #[Route('/cuentasCorrientes',"app_cuentas_corrientes")]
    public function cuentasCorrientes(Request $request,
    CuentascorrientesRepository $cuentasCorrientesRepository)
    {
        $cuentas = $cuentasCorrientesRepository->findAll();
        return $this->render('secure/internal/seccion_cuenta/cuentas_corrientes.html.twig', [
            'controller_name' => 'SeccionCuentaController',
            "cuentas" => $cuentas
        ]);
    }
    
    #[Route('/comprobantesSaldados',"app_comprobantes_saldados_internal")]
    public function comprobantesSaldados(Request $request,
    CuentascorrientesRepository $cuentascorrientesRepository
    )
    {
        return $this->render('secure/internal/seccion_cuenta/comprobantes_saldados.html.twig');
    }

    #[Route("/obtenerSaldados/{tipo}","app_c_saldados")]
    public function obtenerSaldados(Request $request,
    CuentascorrientesRepository $cuentascorrientesRepository,
    ClientesRepository $clientesRepository)
    {
        
        $search = $request->query->get('search');
        $tipoBusqueda = $request->get('tipo_busqueda') ?? null;
        $tipoComprobante = $request->get('tipo') ?? 'TODAS';
        $comprobantes = [];
        $clientes=[];
        if ($tipoBusqueda =='cliente') {
            $clientes = $clientesRepository->findClientesPorRazonSocial($search);
        }
        if($clientes)
        {
            foreach($clientes as $cliente)
            {
                $comprobantesCliente = $cuentascorrientesRepository->findComprobantesSaldados(
                $cliente["codigoCalipso"],
                $tipoComprobante
            );
            // Acumular resultados en lugar de sobrescribir
            $comprobantes = array_merge($comprobantes, $comprobantesCliente);
            }
        }
        else
        {
            $comprobantes = $cuentascorrientesRepository->findComprobantesSaldadosPorOrdenDeCompra($search,$tipoComprobante);
        }
        return $this->render(
            'secure/internal/seccion_cuenta/comprobantes_saldados.html.twig',
            [
                'controller_name' => 'SeccionCuentaController',
                'comprobantes' => $comprobantes
            ]
        );
    }



    #[Route('/comprobantesImpagos',"app_comprobantes_impagos_internal")]
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

    #[Route('/obtenerFacturas', name: 'descarga_facturas_internal')]
    public function obtenerFactura(Request $request, HttpClientInterface $httpClient)
    {
        $factura = $request->query->get("factura");
        $fileName = str_replace(" ","",$factura).".pdf";
        // Ruta portable que funciona en Windows y Linux
        $rutaArchivo = dirname($this->getParameter('kernel.project_dir')) . DIRECTORY_SEPARATOR . 'Facturas' . DIRECTORY_SEPARATOR . $fileName;
        
        // Si es POST, eliminar el archivo
        if($request->getMethod() === 'POST')
        {
            if (file_exists($rutaArchivo)) {
                unlink($rutaArchivo);
            }
            return new JsonResponse(['success' => true, 'message' => 'Archivo eliminado']);
        }
        
        // 1. Verificar si el archivo ya existe
        if (file_exists($rutaArchivo))
        {
            return $this->file($rutaArchivo, $fileName, ResponseHeaderBag::DISPOSITION_ATTACHMENT);
        }
        
        // 2. Si no existe, generar a través de la API
        try {
            $response = $httpClient->request('POST', 'https://192.168.16.104/appserver/api/?action=generapdf&token='.$_ENV["TOKEN"],
            [
                'json' => [
                    'modulo' => 'VENTAS',
                    'comprobante' => $fileName
                ]
            ]);
            
            $statusCode = $response->getStatusCode();
            if ($statusCode === 200)
            {
                $responseData = $response->toArray();
                
                // Verificar el formato de respuesta de la API
                if (isset($responseData['resultado'])) {
                    if ($responseData['resultado'] === 'ERROR') {
                        $message = $responseData['detalle'] ?? 'Error desconocido en la API';
                        return new JsonResponse([
                            'success' => false,
                            'error' => true,
                            'message' => $message
                        ], 400);
                    } else if ($responseData['resultado'] === 'OK') {
                        // Esperar tiempo prudencial para la generación del archivo
                        sleep(15);
                        
                        if (file_exists($rutaArchivo)) {
                            // 3. Descargar y programar eliminación
                            $response = $this->file($rutaArchivo, $fileName, ResponseHeaderBag::DISPOSITION_ATTACHMENT);
                            
                            // Eliminar archivo después de la descarga (usar register_shutdown_function para asegurar la eliminación)
                            register_shutdown_function(function() use ($rutaArchivo) {
                                if (file_exists($rutaArchivo)) {
                                    unlink($rutaArchivo);
                                }
                            });
                            
                            return $response;
                        } else {
                            $message = "El archivo no se generó en el tiempo esperado";
                        }
                    }
                } else {
                    $message = "Respuesta de API en formato inesperado";
                }
            } else {
                $message = "La API respondió con código: " . $statusCode;
            }
        } catch(Exception $e) {
            $message = "Error al comunicarse con la API: " . $e->getMessage();
        }
        
        return new JsonResponse([
                'success' => false,
                'error' => true,
                'message' => $message
            ], 400);
    }
     #[Route('/obtenerRemito', name: 'descarga_remito_internal')]
    public function obtenerComprobante(Request $request, HttpClientInterface $httpClient): Response
    {
        $queryParams = $request->query->all();
        $fileName = $queryParams["remito"] ?? '';
        
        if (empty($fileName)) {
            return new JsonResponse([
                'success' => false,
                'error' => true,
                'message' => 'Nombre de archivo no proporcionado'
            ], 400);
        }
        
        // Determinar la ruta según el tipo de comprobante - portable para Windows y Linux
        $baseDir = dirname($this->getParameter('kernel.project_dir'));
        if($fileName[0]=="F")
        {
            $rutaArchivo = $baseDir . DIRECTORY_SEPARATOR . 'Facturas' . DIRECTORY_SEPARATOR . $fileName . '.pdf';
        }
        else
        {
            $rutaArchivo = $baseDir . DIRECTORY_SEPARATOR . 'Recibos' . DIRECTORY_SEPARATOR . $fileName . '.pdf';
        }
        
        // Si es POST, eliminar el archivo
        if($request->getMethod() === 'POST')
        {
            if (file_exists($rutaArchivo)) {
                unlink($rutaArchivo);
            }
            return new JsonResponse(['success' => true, 'message' => 'Archivo eliminado']);
        }
        
        // 1. Verificar si el archivo ya existe
        if (file_exists($rutaArchivo))
        {
            return $this->file($rutaArchivo, $fileName.'.pdf', ResponseHeaderBag::DISPOSITION_ATTACHMENT);
        }
        
        // 2. Si no existe, generar a través de la API
        try {
            $response = $httpClient->request('POST', 'https://192.168.16.104/appserver/api/?action=generapdf&token='.$_ENV["TOKEN"],
            [
                'json' => [
                    'modulo' => 'COBRANZAS',
                    'comprobante' => $fileName
                ]
            ]);
            
            $statusCode = $response->getStatusCode();
            if ($statusCode === 200) {
                $responseData = $response->toArray();
                
                // Verificar el formato de respuesta de la API
                if (isset($responseData['resultado'])) {
                    if ($responseData['resultado'] === 'ERROR') {
                        $message = $responseData['detalle'] ?? 'Error desconocido en la API';
                        return new JsonResponse([
                            'success' => false,
                            'error' => true,
                            'message' => $message
                        ], 400);
                    } else if ($responseData['resultado'] === 'OK') {
                        // Esperar tiempo prudencial para la generación del archivo
                        sleep(15);
                        
                        if (file_exists($rutaArchivo)) {
                            // 3. Descargar y programar eliminación
                            $response = $this->file($rutaArchivo, $fileName.'.pdf', ResponseHeaderBag::DISPOSITION_ATTACHMENT);
                            
                            // Eliminar archivo después de la descarga
                            register_shutdown_function(function() use ($rutaArchivo) {
                                if (file_exists($rutaArchivo)) {
                                    unlink($rutaArchivo);
                                }
                            });
                            
                            return $response;
                        } else {
                            $message = "El archivo no se generó en el tiempo esperado";
                        }
                    }
                } else {
                    $message = "Respuesta de API en formato inesperado";
                }
            } else {
                $message = "La API respondió con código: " . $statusCode;
            }
        } catch(Exception $e) {
            $message = "Error al comunicarse con la API: " . $e->getMessage();
        }
        
        return new JsonResponse([
            'success' => false,
            'error' => true,
            'message' => $message
        ], 400);
    }
    #[Route('/enviarMail', name: 'app_notificar_pago_internal')]
    public function enviarNotificacion(Request $request): Response
    {
        $data["mensaje"] = $request->request->get("mensaje");
        $archivos = $request->files->get('archivos');
        $email = (new Email())
            ->from($_ENV['MAIL_FROM'])
            ->to($_ENV["MAIL_FROM"])
            ->subject('Notificacion de pago.')
            ->html($this->renderView('emails/_notificacion_de_pago.html.twig',$data));
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
        return $this->redirectToRoute('app_comprobantes_saldados_internal');
    }
}