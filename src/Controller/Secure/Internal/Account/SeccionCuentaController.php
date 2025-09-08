<?php

namespace App\Controller\Secure\Internal\Account;

use App\Repository\ComprobantesimpagosRepository;
use App\Repository\CuentascorrientesRepository;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
#[Route('/seccion')]
final class SeccionCuentaController extends AbstractController{
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
    //
    #[Route('/cuenta/comprobantesSaldados',"app_comprobantes_saldados")]
    public function comprobantesSaldados(Request $request,
    CuentascorrientesRepository $cuentascorrientesRepository
    )
    {
        $comprobantesSaldados = $cuentascorrientesRepository->findComprobantesSaldados("01000602");
        return $this->render('secure/internal/seccion_cuenta/comprobantes_saldados.html.twig', [
            'controller_name' => 'SeccionCuentaController',
            "comprobantes" => $comprobantesSaldados
        ]);
    }
    #[Route('/cuenta/comprobantesImpagos',"app_comprobantes_impagos")]
    public function ComprobantesImpagos(Request $request,
    ComprobantesimpagosRepository $comprobantesimpagosRepository): Response
    {
        $comprobantes = $comprobantesimpagosRepository->findComprobantesImpagosByCliente("01000052");
        return $this->render(
      'secure/internal/seccion_cuenta/comprobantes_impagos_vencimientos.html.twig', 
['controller_name' => 'SeccionCuentaController',
            "comprobantes" => $comprobantes
        ]);
    }
    #[Route('/obtenerFacturas',"prueba_facturas")]
    public function obtenerFactura(Request $request,HttpClientInterface $httpClient)
    {
        // busco el pdf en la carpeta facturas
        // FA0019900000071
        if (file_exists("../Facturas/FA0019900000071.pdf"))
        {
            dd(63);
        }
        try
        {
            $response = $httpClient->request('GET', 'https://192.168.16.104/appserver/api/?action=generapdf&token=eyJhIjoiREVTQVJST0xMTzUiLCJ1IjoiUE9SVEFMIiwicCI6IlJAY2suMjAyNSEifQ',
            [
                'verify_peer' => false,
                'verify_host' => false,
                'json' => [
                    'modulo' => 'VENTAS',
                    'comprobante' => 'FA0019900000071'
                    ]
            ]);
            $statusCode = $response->getStatusCode();
            $content = $response->getContent(); // string
            $data = $response->toArray(); // array si es JSON
            return $this->json($data);
        }
        catch(Exception $e)
        {
            dd($e->getMessage());
        }
    }
}