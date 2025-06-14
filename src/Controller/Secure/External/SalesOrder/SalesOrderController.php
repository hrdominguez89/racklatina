<?php

namespace App\Controller\Secure\External\SalesOrder;

use App\Repository\PedidosrelacionadosRepository;
use App\Repository\UserCustomerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\BrowserKit\Request;
use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('secure/clientes/sales-order')]
final class SalesOrderController extends AbstractController
{
    #[Route('/', name: 'app_secure_external_sales_order_sales_order')]
    public function index(
        PedidosrelacionadosRepository $pedidosrelacionadosRepository,
        UserCustomerRepository $userCustomerRepository
    ): Response {
        $usuario = $this->getUser();

        // Obtener los códigos de cliente que el usuario tiene autorizados
        $clientes = $userCustomerRepository->createQueryBuilder('uc')
            ->select('uc.cliente')
            ->where('uc.user = :usuario')
            ->setParameter('usuario', $usuario)
            ->getQuery()
            ->getSingleColumnResult();

        // Buscar los pedidos relacionados de esos clientes
        $pedidos = $pedidosrelacionadosRepository->createQueryBuilder('p')
            ->where('p.cliente IN (:clientes)')
            ->setParameter('clientes', $clientes)
            ->getQuery()
            ->getResult();

        return $this->render('secure/external/sales_order/index.html.twig', [
            'pedidos' => $pedidos,
        ]);
    }
    #[Route('/verEnDetalle', name: 'app_secure_external_sales_order_sales_order_ver_en_detalle', methods: ['POST'])]

    public function verOrdenDetallada(HttpFoundationRequest $request)
    {
        $info = $request->request->get('info');
        $ordenDeCompra = json_decode($info, true);
        
        return $this->render('secure/external/sales_order/verDetalleOrdenCompra.html.twig', [
            "orden_de_compra" => $ordenDeCompra
        ]);
    }
    
    #[Route('/ajax/remitos', name: 'app_secure_external_sales_order_ajax_remitos')]
    public function ajaxRemitos(HttpFoundationRequest $request): Response
    {
        // $data = $request->request->get('remito');
        // $remito = json_decode($data, true);
        $remito = [
            [
                "numero" => "0001",
                "fecha" => "2022-01-01",
                "estado" => "Pendiente",
                "cantidad" => 100
            ],
            [
                "numero" => "0002",
                "fecha" => "2022-01-02",
                "estado" => "Pendiente",
                "cantidad" => 200
            ],
            [
                "numero" => "0003",
                "fecha" => "2022-01-03",
                "estado" => "Pendiente",
                "cantidad" => 300
            ]
        ];
        return $this->render('secure/external/sales_order/_tabla_remito.html.twig', [
            'remitos' => $remito
        ]);
    }

    #[Route('/ajax/facturas', name: 'app_secure_external_sales_order_ajax_facturas')]
    public function ajaxFacturas(HttpFoundationRequest $request): Response
    {
        // $data = $request->request->get('factura');
        // $factura = json_decode($data, true);
        $facturas = [
            ["numero" => "001-0000001",
            "fecha" => "2022-01-01",
            "importe" => 1000.00,
            "estado" => "Pendiente"],
            ["numero" => "001-0000002",
            "fecha" => "2022-01-02",
            "importe" => 2000.00,
            "estado" => "Pendiente"],
            ["numero" => "001-0000003",
            "fecha" => "2022-01-03",
            "importe" => 3000.00,
            "estado" => "Pendiente"]
        ];
       
        return $this->render('secure/external/sales_order/_tabla_factura.html.twig', [
            'facturas' => $facturas
        ]);
    }


    #[Route('/ajax/factura', name: 'app_secure_external_sales_order_ajax_factura')]
    public function ajaxFactura(HttpFoundationRequest $request): Response
    {
        $facturaId = $request->request->get('id');
        $factura = [
            "numero" => $facturaId,
            "fecha" => "2022-01-01",
            "importe" => 1000.00,
            "estado" => "Pendiente"
        ];
       
        return $this->render('secure/external/sales_order/_modalFactura.html.twig', [
            'factura' => $factura
        ]);
    }

    #[Route('/ajax/remito', name: 'app_secure_external_sales_order_ajax_remito')]
    public function ajaxRemito(HttpFoundationRequest $request): Response
    {
        $remito = [
            "numero" => 9999,
            "fecha" => "2022-01-01",
            "estado" => "Pendiente",
            "cantidad" => 100
        ];
       
        return $this->render('secure/external/sales_order/_modalRemito.html.twig', [
            'remito' => $remito
        ]);
    }


}
