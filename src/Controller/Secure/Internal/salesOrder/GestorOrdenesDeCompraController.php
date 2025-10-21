<?php

namespace App\Controller\Secure\Internal\salesOrder;

use App\Entity\Clientes;
use App\Entity\Pedidosrelacionados;
use App\Repository\FacturasRepository;
use App\Repository\PedidosrelacionadosRepository;
use App\Repository\RemitosRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
#[Route('secure/sales-order')]
final class GestorOrdenesDeCompraController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    #[Route('/int-ord-compr', name: 'app_gestor_ordenes_de_compra', methods: ['GET'])]
    public function index(Request $request, PedidosrelacionadosRepository $pedidosrelacionadosRepository): Response
    {
        $data['status'] = $request->query->get('status') ?? 'Todas';
        $data['pedidos'] = [];
        $searchType = $request->query->get('searchType') ?? null;
        $search = $request->query->get('search') ?? null;
        $searchArticulo = $request->query->get('searchArticulo') ?? null;
        
        if ($searchType && (($searchType === 'cliente_y_articulo' && $searchArticulo) || ($searchType !== 'cliente_y_articulo' && $search))) {
            $query = $pedidosrelacionadosRepository->createQueryBuilder('p');
            switch ($searchType) {
                case 'orden':
                    $query->where('p.ordencompracliente like :ordencompracliente')
                        ->setParameter('ordencompracliente', '%' . $search . '%');
                    break;
                case 'pedido':
                    $query->where('p.numero like :numero')
                        ->setParameter('numero', '%' . $search . '%');
                    break;
                case 'cliente_y_articulo':
                    $query->where('p.razonsocial like :razonsocial')
                        ->andWhere('p.articulo like :articulo')
                        ->setParameter('razonsocial', '%' . $search . '%')
                        ->setParameter('articulo', '%' . $searchArticulo . '%');
                    break;
                case 'cliente':
                default:
                    
                    $query->where('p.razonsocial like :razonsocial')
                        ->setParameter('razonsocial', '%' . $search . '%');
                    break;
            }
            if ($data['status'] !== 'Todas') {
                
                if($data["status"] == "articulos_pendientes")
                {
                    $query->andWhere("p.estado = 'Pendiente'")
                        ->andWhere('p.cantidadoriginal != 0');
                }
                else
                {
                    $query->andWhere('p.estado = :estado')
                        ->setParameter('estado', $data['status']);
                }
            }
            $data['pedidos'] = $query->getQuery()
                ->getArrayResult();
            $agrupados = [];
            if($data["status"] != "articulos_pendientes")
            {
                foreach ($data['pedidos'] as $pedido) {
                    $key = $pedido['ordencompracliente'] . '|' . $pedido['numero'];
                    $articulos = $searchType != 'cliente' ? $this->obtenerArticulosDeOrden($pedido['cliente'],$pedido['ordencompracliente'], $pedido['numero']) : "";
                    
                    if (!isset($agrupados[$key])) {
                        $agrupados[$key] = [
                            'ordencompracliente' => $pedido['ordencompracliente'],
                        'numero' => $pedido['numero'],
                        'cliente' => $pedido['cliente'],
                        'razonsocial' => $pedido['razonsocial'],
                        'fechapedido' => $pedido['fechapedido'],
                        'fechaoc' => $pedido['fechaoc'],
                        'pendientes' => 0,
                        'remitidos' => 0,
                        'articulos' =>$articulos
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
            }
        }
        return $this->render('gestor_ordenes_de_compra/index.html.twig', $data);
    }
    #[Route('/buscar-ordenes-por-cuit/{cuit}', name: 'app_gestor_buscar_ordenes_por_cuit', methods: ['GET'])]
    public function buscarOrdenesPorCuit(Request $request, PedidosrelacionadosRepository $pedidosrelacionadosRepository, string $cuit): Response
    {
        if (!$cuit) {
            return $this->json(['error' => 'Debe enviar el CUIT del cliente'], 400);
        }

        // Buscar cliente por CUIT
        $cliente = $this->entityManager->getRepository(Clientes::class)
            ->findOneBy(['cuit' => $cuit]);

        if (!$cliente) {
            return $this->json(['error' => 'No se encontró el cliente con el CUIT ' . $cuit], 404);
        }
        // Buscar órdenes del cliente
        $ordenes = $pedidosrelacionadosRepository->createQueryBuilder('p')
            ->where('p.cliente = :cliente')
            ->setParameter('cliente', $cliente->getCodigoCalipso())
            ->getQuery()
            ->getArrayResult();
        if (empty($ordenes)) {
            return $this->json(['error' => 'No se encontraron órdenes de compra para el cliente con CUIT ' . $cuit], 404);
        }
        // Renderizar la tabla con las órdenes encontradas
        return $this->render('gestor_ordenes_de_compra/_tabla_ordenes.html.twig', [
            'ordenes' => $ordenes,
            'cliente' => $cliente
        ]);
    }
    #[Route('/orden', name: 'app_secure_internal_sales_order_sales_order_ver_en_detalle', methods: ['GET'])]
    public function verOrden(Request $request, PedidosrelacionadosRepository $pedidosrelacionadosRepository, EntityManagerInterface $em): Response
    {

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

        if (!$ordenesDeCompra) {
            throw $this->createNotFoundException('Orden no encontrada');
        }

        return $this->render('gestor_ordenes_de_compra/ver_orden.html.twig', [
            "orden_de_compra" => $ordenDeCompra,
            "ordenes_de_compra" => $ordenesDeCompra,
        ]);
    }
    #[Route('/remito-int/{numero}', name: 'app_remito_show_int')]
    public function verRemito(string $numero, RemitosRepository $remitosRepository): Response
    {
        $remitos = $remitosRepository->createQueryBuilder('r')
            ->where('r.remito = :numero')
            ->setParameter('numero:', $numero)
            ->getQuery()
            ->getArrayResult();
        if (!$remitos) {
            $remitos = [];
        }

        return $this->render(
            'gestor_ordenes_de_compra/_modalRemito.html.twig',
            ["remitos" => $remitos]
        );
    }
    #[Route('/factura-int/{numero}', name: 'app_factura_show_int')]
    public function verFactura(string $numero, FacturasRepository $facturasRepository): Response
    {
        // Eliminamos prefijos como FA, CA, etc.
        $numeroLimpio = preg_replace('/^[A-Z]+/', '', $numero);

        $factura = $facturasRepository->findBy([
            'numero' => $numeroLimpio
        ]);
        // dd($facturas);
        return $this->render(
            'gestor_ordenes_de_compra/_modalFactura.html.twig',
            ['facturas' => $factura]
        );
    }
     public function obtenerArticulosDeOrden($cliente_id,$orden_compra_cliente_id,$numero_pedido)
    {
        $ordenesDeCompra = $this->entityManager->createQueryBuilder()
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
    #[Route('/descargaRemitos', name: 'app_remitos_descarga_interno')]
    public function descargaDeRemito(Request $request): Response
    {
        $remito = $request->query->get('remito');
        
        if (!$remito || trim($remito) === '') {
            return $this->json([
                'success' => false,
                'message' => 'El número de remito es requerido'
            ], Response::HTTP_BAD_REQUEST);
        }
        $nombreArchivo = $remito . '.pdf';
        $rutaArchivo = $this->getParameter('kernel.project_dir') 
            . DIRECTORY_SEPARATOR . 'Remitos' 
            . DIRECTORY_SEPARATOR . $nombreArchivo;
        
        if (!file_exists($rutaArchivo)) {
            return $this->json([
                'success' => false,
                'message' => 'El remito solicitado no se encontro comuniquese con soporte.'
            ], Response::HTTP_NOT_FOUND);
        }
        
        return $this->file(
            $rutaArchivo, 
            $nombreArchivo, 
            ResponseHeaderBag::DISPOSITION_ATTACHMENT
        );
    }
}