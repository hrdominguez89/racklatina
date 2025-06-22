<?php

namespace App\Controller\Secure\External\SalesOrder;

use App\Entity\Pedidosrelacionados;
use App\Repository\FacturasRepository;
use App\Repository\PedidosrelacionadosRepository;
use App\Repository\RemitosRepository;
use App\Repository\UserCustomerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\BrowserKit\Request;
use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('secure/clientes/sales-order')]
final class SalesOrderController extends AbstractController
{
    #[Route('/estado/{status}', name: 'app_secure_external_sales_order_sales_order', requirements: ['status' => '.*'], defaults: ['status' => null])]

    public function index(
        PedidosrelacionadosRepository $pedidosrelacionadosRepository,
        UserCustomerRepository $userCustomerRepository,
        string $status
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
            ->getArrayResult();

        return $this->render('secure/external/sales_order/index.html.twig', [
            'pedidos' => $pedidos,
            'status' => $status,
        ]);
    }
    #[Route('/detalle/{cliente_id}/{orden_compra_cliente_id}', name: 'app_secure_external_sales_order_sales_order_ver_en_detalle', methods: ['GET'])]

    public function detalle(HttpFoundationRequest $request, string $cliente_id, string $orden_compra_cliente_id, PedidosrelacionadosRepository $pedidosrelacionadosRepository, EntityManagerInterface $em): Response
    {
        $ordenDeCompra =  $pedidosrelacionadosRepository->findOneBy(['cliente' => $cliente_id, 'ordencompracliente' => $orden_compra_cliente_id]);

        $ordenesDeCompra = $em->createQueryBuilder()
            ->select('p')
            ->from(Pedidosrelacionados::class, 'p')
            ->where('p.cliente = :cliente')
            ->andWhere('p.ordencompracliente = :orden')
            ->setParameter('cliente', $cliente_id)
            ->setParameter('orden', $orden_compra_cliente_id)
            ->getQuery()
            ->getArrayResult();

        return $this->render('secure/external/sales_order/detalle.html.twig', [
            "orden_de_compra" => $ordenDeCompra,
            "ordenes_de_compra" => $ordenesDeCompra,
        ]);
    }

    #[Route('/remito/{numero}', name: 'app_remito_show')]
    public function verRemito(string $numero, RemitosRepository $remitosRepository): Response
    {
        $remitos = $remitosRepository->findBy([
            'remito' => $numero
        ]);
        if (!$remitos) {
            $remitos = [];
        }

        return $this->render('secure/external/sales_order/_modalRemito.html.twig',
    ["remitos"=>$remitos]);
    }


    #[Route('/factura/{numero}', name: 'app_factura_show')]
    public function verFactura(string $numero, FacturasRepository $facturasRepository): Response
    {
        // Eliminamos prefijos como FA, CA, etc.
        $numeroLimpio = preg_replace('/^[A-Z]+/', '', $numero);

        $factura = $facturasRepository->findBy([
            'numero' => $numeroLimpio
        ]);
        // dd($facturas);
        return $this->render('secure/external/sales_order/_modalFactura.html.twig',
    ['facturas' => $factura]);
    }

}
