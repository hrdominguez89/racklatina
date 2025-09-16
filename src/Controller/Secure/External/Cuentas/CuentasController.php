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

#[Route('/seccion/clientes')]
final class CuentasController extends AbstractController
{
    public function __construct(private MailerInterface $mailer) 
    {
        $this->mailer = $mailer;
    }
    
    #[Route('/cuenta', name: 'app_seccion_cuenta_external')]
    public function index(): Response
    {
        return $this->render('secure/external/seccion_cuenta/index.html.twig', [
            'controller_name' => 'CuentasController',
        ]);
    }

    #[Route('/cuenta/comprobantesSaldados/{tipo}', name: 'app_comprobantes_saldados_external', methods:['POST','GET'])]
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

    #[Route('/cuenta/comprobantesImpagos', name: 'app_comprobantes_impagos_external')]
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
        $rutaArchivo = "../Facturas/{$fileName}";
        if($request->getMethod() === 'POST')
        {
            unlink($rutaArchivo);
        }
        if (file_exists($rutaArchivo))
        {
            return $this->file($rutaArchivo, $fileName, ResponseHeaderBag::DISPOSITION_ATTACHMENT);
        }
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

            if ($statusCode === 200)
            {
                sleep(15);
                if (file_exists($rutaArchivo)) {
                    return $this->file($rutaArchivo, $fileName, ResponseHeaderBag::DISPOSITION_ATTACHMENT);
                } else {
                    $message = "Error al descargar la factura : " . "no existio el archivo al momento de la descarga";
                }
            } else {
                $message = "Error al descargar la factura : " . "respuesta ". $statusCode;
            }
        } catch(Exception $e) {
                $message ="Error al descargar la factura :" . $e->getMessage();
        }
        return new JsonResponse([
                'success' => false,
                'error' => true,
                'message' => $message
            ], 400);
    }
     #[Route('/obtenerRemito', name: 'descarga_remito_external')]
    public function obtenerComprobante(Request $request,
     HttpClientInterface $httpClient,
    ComprobantesimpagosRepository $comprobantesimpagosRepository,
    UserCustomerRepository $userCustomerRepository, 
    ClientesRepository $clientesRepository): Response
    {
        $queryParams = $request->query->all();
        $fileName = $queryParams["remito"].".pdf" ?? null;
        $rutaArchivo = "../Recibos/{$fileName}";
        
        if (file_exists($rutaArchivo))
        {
            return $this->file($rutaArchivo, $fileName, ResponseHeaderBag::DISPOSITION_ATTACHMENT);
        }
        try {
            $response = $httpClient->request('POST', 'https://192.168.16.104/appserver/api/?action=generapdf&token='.$_ENV["TOKEN"],
            [
                'verify_peer' => false,
                'verify_host' => false,
                'json' => [
                    'modulo' => 'COBRANZAS',
                    'comprobante' => $fileName
                ]
            ]);
            $statusCode = $response->getStatusCode();
            
            if ($statusCode === 200) {
                if (file_exists($rutaArchivo)) {
                    return $this->file($rutaArchivo, $fileName, ResponseHeaderBag::DISPOSITION_ATTACHMENT);
                } else {
                    $comprobantes = $this->auxiliar(
                          $comprobantesimpagosRepository,
                         $userCustomerRepository, 
                         $clientesRepository);
                    $this->addFlash("danger",'El archivo no se generó correctamente');
                }
            } else {
                $comprobantes = $this->auxiliar(
                          $comprobantesimpagosRepository,
                         $userCustomerRepository, 
                         $clientesRepository);
                $this->addFlash("danger",'Error en la API');
            }
        } catch(Exception $e) {
            $comprobantes = $this->auxiliar(
                          $comprobantesimpagosRepository,
                         $userCustomerRepository, 
                         $clientesRepository);
                $this->addFlash("danger",$e->getMessage());
            }
            return $this->redirectToRoute("app_comprobantes_saldados_external", [
            'controller_name' => 'CuentasController',
            'comprobantes' => $comprobantes
            ]);
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