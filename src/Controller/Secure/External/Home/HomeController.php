<?php

namespace App\Controller\Secure\External\Home;

use App\Entity\CustomerRequest;
use App\Enum\CustomerRequestStatus;
use App\Repository\CustomerRequestRepository;
use App\Repository\EstadoClientesRepository;
use App\Repository\UserCustomerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('secure/clientes/home')]
final class HomeController extends AbstractController
{
        
    public function __construct(private CustomerRequestRepository $repository)
    {
        $this->repository = $repository;
    }
    #[Route('/', name: 'app_secure_external_home')]
    public function index(CustomerRequestRepository $customerRequestRepository,
    UserCustomerRepository $userCustomerRepository,
    EstadoClientesRepository $estadoCuentaRepository): Response
    {
        $data['user'] = $this->getUser();
        $user_id = $data["user"]->getId();
        $requests = $customerRequestRepository->findOneBy(["userRequest" => $user_id]);
        // $usuario_cliente = $userCustomerRepository->findOneBy(["user"=>$user_id ]);
        // $cliente = $usuario_cliente->getCliente();
        // $estado_cliente = $cliente->getEstado();
        // $estadoCuenta = $estadoCuentaRepository->findOneBy(["nombreEstado"=>$estado_cliente]);
        // $this->addFlash("info",$estadoCuenta->getDetalleEstado());
        if(!empty($requests))
        {
            $this->auxiliar();
            return $this->render('secure/external/home/index.html.twig',$data);
        }
        $this->addFlash('info', 'No tiene empresas asignadas para administrar desde su usuario, por favor agregue las empresas que desee administrar desde su perfil.');
        return $this->redirectToRoute('app_secure_external_customer_request');
    }
    public function auxiliar()
    {
        $user = $this->getUser();
        $criteria = ['userRequest' => $user];
        $criteria['status'] = CustomerRequestStatus::PENDIENTE;
        $solicitudes = $this->repository->findBy($criteria, ['createdAt' => 'DESC']);
        
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
    }

}