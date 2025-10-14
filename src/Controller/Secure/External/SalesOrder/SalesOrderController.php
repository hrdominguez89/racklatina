<?php

namespace App\Controller\Secure\External\SalesOrder;

use App\Entity\Pedidosrelacionados;
use App\Entity\Remitos;
use App\Enum\CustomerRequestStatus;
use App\Repository\CustomerRequestRepository;
use App\Repository\FacturasRepository;
use App\Repository\PedidosrelacionadosRepository;
use App\Repository\RemitosRepository;
use App\Repository\UserCustomerRepository;
use App\Services\EstadoCuentaService;
use Doctrine\ORM\EntityManagerInterface;
use Dom\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('secure/clientes/sales-order')]
final class SalesOrderController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em,private EstadoCuentaService $estadoCuentaService)
    {
        $this->em = $em;
    }
    #[Route('/', name: 'app_secure_external_sales_order_sales_order', methods: ['GET'])]
    public function index(
        Request $request,
        PedidosrelacionadosRepository $pedidosrelacionadosRepository,
        UserCustomerRepository $userCustomerRepository,
        EntityManagerInterface $em,
        CustomerRequestRepository $repository
    ): Response {
        $user = $this->getUser();
        $criteria = ['userRequest' => $user];
        $criteria['status'] = CustomerRequestStatus::PENDIENTE;
        $solicitudes = $repository->findBy($criteria, ['createdAt' => 'DESC']);
        
        if($solicitudes != null)
        {
            $mensaje = "Te enviaremos un email de confirmación una vez que sea aprobada.
            Tu solicitud está siendo evaluada para representar a la empresa:";
            foreach($solicitudes as $solicitud)
                {
                    if($solicitud->getStatus() == CustomerRequestStatus::PENDIENTE)
                        {
                            $mensaje = $mensaje . " " . $solicitud?->getData()[0]['razonSocial']  . "\n";
                        }
                }
            $this->addFlash('info', $mensaje);
        }
        $this->estadoCuentaService->verificarYNotificarEstadoCuenta($user->getId());
        // ARMAR UN ARRAY DE LOS CLIENTES
        // FLAG PARA MOSTRAR LA TABLA SI HAY CLIENTE SELECCIONADO 
        // $cliente_get = $request->query->get("Cliente") ?? null;
        // $cliente=[];
        // $data["mostrar_tabla"]=!empty($cliente);


        $data['status'] = $request->query->get('status') ?? 'Todas';
        if($data['status'] =="articulos_pendientes")
        {
            $usuario = $this->getUser();
            $clientes = $userCustomerRepository->createQueryBuilder('uc')
                ->select('uc.cliente')
                ->where('uc.user = :usuario')
                ->setParameter('usuario', $usuario)
                ->getQuery()
                ->getSingleColumnResult();
            $data['pedidos']=[];
            foreach($clientes as $cliente)
            {
                $aux = $em->createQueryBuilder()
                        ->select('p')
                        ->from(Pedidosrelacionados::class, 'p')
                        ->where('p.cliente = :cliente')
                        ->andWhere("p.estado = 'Pendiente'")
                        ->andWhere('p.cantidadoriginal != 0')
                        ->setParameter('cliente', $cliente)
                        ->getQuery()
                        ->getArrayResult();
                $data['pedidos']=$aux;
            }
            return $this->render('secure/external/sales_order/index.html.twig', $data);
        }
        $usuario = $this->getUser();
        // Obtener los códigos de cliente que el usuario tiene autorizados
        $clientes = $userCustomerRepository->createQueryBuilder('uc')
            ->select('uc.cliente')
            ->where('uc.user = :usuario')
            ->setParameter('usuario', $usuario)
            ->getQuery()
            ->getSingleColumnResult();
        // Buscar los pedidos relacionados de esos clientes
        $query = $pedidosrelacionadosRepository->createQueryBuilder('p')
            ->where('p.cliente IN (:clientes)')
            ->setParameter('clientes', $clientes);
        if ($data['status'] !== 'Todas') {
            $query->andWhere('p.estado = :estado')
                ->setParameter('estado', $data['status']);
        }
        $data['pedidos'] = $query->getQuery()
            ->getArrayResult();

        $agrupados = [];

        foreach ($data['pedidos'] as $pedido) {
            $key = $pedido['ordencompracliente'] . '|' . $pedido['numero'];

            if (!isset($agrupados[$key])) {
                $articulos = $this->obtenerArticulosDeOrden($pedido['cliente'],$pedido['ordencompracliente'], $pedido['numero']);
                $agrupados[$key] = [
                    'ordencompracliente' => $pedido['ordencompracliente'],
                    'numero' => $pedido['numero'],
                    'cliente' => $pedido['cliente'],
                    'razonsocial' => $pedido['razonsocial'],
                    'fechaoc' => $pedido['fechaoc'],
                    'pendientes' => 0,
                    'remitidos' => 0,
                    'articulos' => $articulos
                ];
            }
            // Contar estados
            if ($pedido['estado'] === 'Pendiente') {
                $agrupados[$key]['pendientes']++;
            } elseif ($pedido['estado'] === 'Remitido') {
                $agrupados[$key]['remitidos']++;
            }
        }

        $data['pedidos'] = array_values($agrupados);
        
        return $this->render('secure/external/sales_order/index.html.twig', $data);
    }
    #[Route('/detalle', name: 'app_secure_external_sales_order_sales_order_ver_en_detalle', methods: ['GET'])]
    public function detalle(Request $request, PedidosrelacionadosRepository $pedidosrelacionadosRepository, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $this->estadoCuentaService->verificarYNotificarEstadoCuenta($user->getId());
        $cliente_id = $request->query->get('cliente_id') ?? null;
        $numero_pedido = $request->query->get('numero_pedido') ?? null;
        $orden_compra_cliente_id = $request->query->get('orden_compra_cliente_id') ?? null;

        $ordenDeCompra =  $pedidosrelacionadosRepository->findOneBy(['cliente' => $cliente_id, 'ordencompracliente' => $orden_compra_cliente_id,'numero' => $numero_pedido]);

        $ordenesDeCompra = $em->createQueryBuilder()
            ->select('p')
            ->from(Pedidosrelacionados::class, 'p')
            ->where('p.cliente = :cliente')
            ->andWhere('p.ordencompracliente = :orden')
            ->andWhere('p.numero = :numero_pedido')
            ->setParameter('cliente', $cliente_id)
            ->setParameter('orden', $orden_compra_cliente_id)
            ->setParameter('numero_pedido', $numero_pedido)
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
        // dd("estoy aca motherfucker");
        $remitos = $remitosRepository->createQueryBuilder('r')
            ->where('r.remito = :numero')
            ->setParameter('numero:', $numero)
            ->getQuery()
            ->getArrayResult();
        // dd($remitos);
        if (!$remitos) {
            $remitos = [];
        }

        return $this->render(
            'secure/external/sales_order/_modalRemito.html.twig',
            ["remitos" => $remitos]
        );
    }


    #[Route('/factura/{numero}', name: 'app_factura_show')]
    public function verFactura(string $numero, FacturasRepository $facturasRepository): Response
    {
        // Eliminamos prefijos como FA, CA, etc.
        $numeroLimpio = preg_replace('/^[A-Z]+/', '', $numero);

        $factura = $facturasRepository->findBy([
            'numero' => $numeroLimpio
        ]);
        
        return $this->render(
            'secure/external/sales_order/_modalFactura.html.twig',
            ['facturas' => $factura]
        );
    }
    #[Route('/items', name: 'app_secure_external_sales_order_articulos')]
    public function verItems(Request $request, EntityManagerInterface $em,UserCustomerRepository $userCustomerRepository)
    {
        $usuario = $this->getUser();
        $clientes = $userCustomerRepository->createQueryBuilder('uc')
            ->select('uc.cliente')
            ->where('uc.user = :usuario')
            ->setParameter('usuario', $usuario)
            ->getQuery()
            ->getSingleColumnResult();
        $pedidos=[];
        foreach($clientes as $cliente)
        {
            $aux = $em->createQueryBuilder()
                    ->select('p')
                    ->from(Pedidosrelacionados::class, 'p')
                    ->where('p.cliente = :cliente')
                    ->andWhere("p.estado = 'Pendiente'")
                    ->andWhere('p.cantidadoriginal != 0')
                    ->setParameter('cliente', $cliente)
                    ->getQuery()
                    ->getArrayResult();
            array_push($pedidos,$aux);
        }
        return $this->render('secure/external/sales_order/articulos_cliente.html.twig', ['pedidos' => $pedidos]);
    }
    public function obtenerArticulosDeOrden($cliente_id,$orden_compra_cliente_id,$numero_pedido)
    {
        $ordenesDeCompra = $this->em->createQueryBuilder()
            ->select('p')
            ->from(Pedidosrelacionados::class, 'p')
            ->where('p.cliente = :cliente')
            ->andWhere('p.ordencompracliente = :orden')
            ->andWhere('p.numero = :numero_pedido')
            ->setParameter('cliente', $cliente_id)
            ->setParameter('orden', $orden_compra_cliente_id)
            ->setParameter('numero_pedido', $numero_pedido)
            ->getQuery()
            ->getArrayResult();
        $return=[];
        foreach($ordenesDeCompra as $ordC)
        {
            $return[]=$ordC["articulo"];
        }
        return $return;
    }
}
