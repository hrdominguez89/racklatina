<?php

namespace App\Controller\Secure\External\SalesOrder;

use App\Repository\PedidosrelacionadosRepository;
use App\Repository\UserCustomerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
}
