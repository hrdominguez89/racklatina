<?php

namespace App\Controller\Secure\External\Cuentas;

use App\Repository\ClientesRepository;
use App\Repository\ComprobantesimpagosRepository;
use App\Repository\CuentascorrientesRepository;
use App\Repository\UserCustomerRepository;
use Exception;
use Symfony\Component\Mime\Email;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('secure/seccion-cuenta/clientes')]
final class CuentasController extends AbstractController
{
    public function __construct(private MailerInterface $mailer) 
    {
        $this->mailer = $mailer;
    }
    
    #[Route('/', name: 'app_seccion_cuenta_external')]
    public function index(): Response
    {
        return $this->render('secure/external/seccion_cuenta/index.html.twig', [
            'controller_name' => 'CuentasController',
        ]);
    }

    #[Route('/comprobantesSaldados/{tipo}', name: 'app_comprobantes_saldados_external', methods:['POST','GET'])]
    public function comprobantesSaldados(
        Request $request,
        CuentascorrientesRepository $cuentascorrientesRepository,
        UserCustomerRepository $userCustomerRepository, ClientesRepository $clientesRepository
    ): Response {
        $user = $this->getUser();
        $user_customer = $userCustomerRepository->findOneBy(["user"=>$user->getId()]);
        $codigoCalipso = $user_customer->getCliente($clientesRepository)->getCodigoCalipso(); // Adjust according to your User entity
        $tipo = $request->get('tipo') ?? 'TODAS';
        $comprobantes = $cuentascorrientesRepository->findComprobantesSaldados($codigoCalipso,$tipo);
        return $this->render('secure/external/seccion_cuenta/comprobantes_saldados.html.twig', [
            'controller_name' => 'CuentasController',
            'comprobantes' => $comprobantes
        ]);
    }
    #[Route('/comprobantesImpagos', name: 'app_comprobantes_impagos_external')]
    public function comprobantesImpagos(
        Request $request,
        ComprobantesimpagosRepository $comprobantesimpagosRepository,
        UserCustomerRepository $userCustomerRepository, 
        ClientesRepository $clientesRepository
        ): Response {
        $user = $this->getUser();
        $user_customer = $userCustomerRepository->findOneBy(["user"=>$user->getId()]);
        $codigoCalipso = $user_customer->getCliente($clientesRepository)->getCodigoCalipso();
        $comprobantes = $comprobantesimpagosRepository->findComprobantesImpagosByCliente($codigoCalipso);
        return $this->render('secure/external/seccion_cuenta/comprobantes_impagos_vencimientos.html.twig', [
            'controller_name' => 'CuentasController',
            'comprobantes' => $comprobantes
        ]);
    }
    #[Route('/obtenerFacturas', name: 'descarga_facturas_external')]
    public function obtenerFactura(Request $request, HttpClientInterface $httpClient,
     ComprobantesimpagosRepository $comprobantesimpagosRepository,
    UserCustomerRepository $userCustomerRepository,
    ClientesRepository $clientesRepository): Response
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
        
        // DEBUG TEMPORAL: Log de ruta calculada
        error_log("DEBUG DESCARGA FACTURA EXTERNA - Ruta calculada: " . $rutaArchivo);
        error_log("DEBUG DESCARGA FACTURA EXTERNA - Archivo existe: " . (file_exists($rutaArchivo) ? 'SI' : 'NO'));
        error_log("DEBUG DESCARGA FACTURA EXTERNA - kernel.project_dir: " . $this->getParameter('kernel.project_dir'));
        error_log("DEBUG DESCARGA FACTURA EXTERNA - dirname(kernel.project_dir): " . dirname($this->getParameter('kernel.project_dir')));
        
        // 1. Verificar si el archivo ya existe
        if (file_exists($rutaArchivo))
        {
            error_log("DEBUG DESCARGA FACTURA EXTERNA - Descargando archivo existente: " . $rutaArchivo);
            return $this->file($rutaArchivo, $fileName, ResponseHeaderBag::DISPOSITION_ATTACHMENT);
        }
        
        // 2. Si no existe, generar a través de la API
        try {
            // Configuración SSL según entorno
            $sslOptions = [];
            if ($_ENV['APP_ENV'] === 'dev' || $_ENV['APP_ENV'] === 'test') {
                // Solo en desarrollo/testing - desactivar verificación SSL
                $sslOptions = [
                    'verify_peer' => false,
                    'verify_host' => false,
                ];
            }
            // En producción, mantener verificación SSL activa para seguridad
            
            $response = $httpClient->request('POST', 'https://192.168.16.104/appserver/api/?action=generapdf&token='.$_ENV["TOKEN"],
            array_merge([
                'json' => [
                    'modulo' => 'VENTAS',
                    'comprobante' => $fileName
                ]
            ], $sslOptions));
            
            $statusCode = $response->getStatusCode();
            if ($statusCode === 200)
            {
                $responseData = $response->toArray();
                
                // Verificar el formato de respuesta de la API
                if (isset($responseData['resultado'])) {
                    if ($responseData['resultado'] === 'ERROR') {
                        $message = "Error al descargar la factura: " . ($responseData['detalle'] ?? 'Error desconocido en la API');
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
                            
                            // Eliminar archivo después de la descarga
                            register_shutdown_function(function() use ($rutaArchivo) {
                                if (file_exists($rutaArchivo)) {
                                    unlink($rutaArchivo);
                                }
                            });
                            
                            return $response;
                        } else {
                            $message = "Error al descargar la factura: el archivo no se generó en el tiempo esperado";
                        }
                    }
                } else {
                    $message = "Error al descargar la factura: respuesta de API en formato inesperado";
                }
            } else {
                $message = "Error al descargar la factura: respuesta " . $statusCode;
            }
        } catch(Exception $e) {
                $message ="Error al descargar la factura: " . $e->getMessage();
        }
        return new JsonResponse([
                'success' => false,
                'error' => true,
                'message' => $message
            ], 400);
    }
    #[Route('/obtenerRemito', name: 'descarga_remito_external')]
    public function obtenerComprobante(Request $request,HttpClientInterface $httpClient,ComprobantesimpagosRepository $comprobantesimpagosRepository,UserCustomerRepository $userCustomerRepository,ClientesRepository $clientesRepository): Response
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
            // Configuración SSL según entorno
            $sslOptions = [];
            if ($_ENV['APP_ENV'] === 'dev' || $_ENV['APP_ENV'] === 'test') {
                // Solo en desarrollo/testing - desactivar verificación SSL
                $sslOptions = [
                    'verify_peer' => false,
                    'verify_host' => false,
                ];
            }
            // En producción, mantener verificación SSL activa para seguridad
            
            $response = $httpClient->request('POST', 'https://192.168.16.104/appserver/api/?action=generapdf&token='.$_ENV["TOKEN"],
            array_merge([
                'json' => [
                    'modulo' => 'COBRANZAS',
                    'comprobante' => $fileName
                ]
            ], $sslOptions));
            
            $statusCode = $response->getStatusCode();
            if ($statusCode === 200) {
                $responseData = $response->toArray();
                
                // Verificar el formato de respuesta de la API
                if (isset($responseData['resultado'])) {
                    if ($responseData['resultado'] === 'ERROR') {
                        $this->addFlash("danger", $responseData['detalle'] ?? 'Error desconocido en la API');
                        return $this->redirectToRoute("app_comprobantes_impagos_external");
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
                            $this->addFlash("danger",'El archivo no se generó en el tiempo esperado');
                        }
                    }
                } else {
                    $this->addFlash("danger",'Respuesta de API en formato inesperado');
                }
            } else {
                $this->addFlash("danger",'Error en la API - Código: ' . $statusCode);
            }
        } catch(Exception $e) {
            $this->addFlash("danger", $e->getMessage());
        }
        
        return $this->redirectToRoute("app_comprobantes_impagos_external");
    }
    #[Route('/enviarMail', name: 'app_notificar_pago_external')]
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
        return $this->redirectToRoute('app_comprobantes_saldados_external');
    }
    public function auxiliar(ComprobantesimpagosRepository $comprobantesimpagosRepository,
    UserCustomerRepository $userCustomerRepository, 
    ClientesRepository $clientesRepository)
    {
        $user = $this->getUser();
        $user_customer = $userCustomerRepository->findOneBy(["user"=>$user->getId()]);
        $codigoCalipso = $user_customer->getCliente($clientesRepository)->getCodigoCalipso();
        $comprobantes = $comprobantesimpagosRepository->findComprobantesImpagosByCliente($codigoCalipso);
        return $comprobantes;
    }
}