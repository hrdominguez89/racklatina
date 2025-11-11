<?php

namespace App\Controller\Secure\Internal\CustomerRequest;

use App\Entity\CustomerRequest;
use App\Entity\UserCustomer;
use App\Enum\CustomerRequestStatus;
use App\Enum\CustomerRequestType;
use App\Repository\ClientesRepository;
use App\Repository\CustomerRequestRepository;
use App\Repository\UserCustomerRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

#[Route('secure/customer-request')]
final class CustomerRequestController extends AbstractController
{
    public function __construct(
    private MailerInterface $mailer,
    private UserCustomerRepository $user_customer_repository,
    private UserRepository $user_repository,
    private EntityManagerInterface $em) 
    {
        $this->mailer = $mailer;
    }
    #[Route('/', name: 'app_secure_internal_customer_request')]
    public function index(Request $request,
     CustomerRequestRepository $repository): Response
    {

        $statusParam = $request->query->get('status') ?? CustomerRequestStatus::PENDIENTE->value;
        if ($statusParam !== null) {
            // Validar que sea un valor válido del enum
            $statusParam = strtolower($statusParam);
            $statusValido = array_map(fn($e) => $e->value, CustomerRequestStatus::cases());

            if (in_array($statusParam, $statusValido, true)) {
                $criteria['status'] = CustomerRequestStatus::from($statusParam);
            }
        }

        $solicitudes = $repository->findByStatusWithActiveUsers($criteria['status'] ?? null);
        
        

        return $this->render('secure/internal/customer_request/index.html.twig', [
            'solicitudes' => $solicitudes,
            'statusFiltro' => $statusParam,
            'estadosDisponibles' => CustomerRequestStatus::cases(),
        ]);
    }


    #[Route('/{id}/revisar', name: 'app_secure_internal_customer_request_review')]
    public function review(
        int $id,
        Request $request,
        CustomerRequestRepository $customerRequestRepository,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $solicitud = $customerRequestRepository->find($id);

        if (!$solicitud) {
            throw $this->createNotFoundException('Solicitud no encontrada');
        }

        $clientes = $solicitud->getData(); // array de clientes [{id, razonSocial, cuit}]
        $aprobados = $request->request->all('aprobados'); // array de strings

        if ($request->isMethod('POST')) {
            $aprobados = is_array($aprobados) ? $aprobados : [];

            foreach ($clientes as $cliente) {
                if (in_array($cliente['id'], $aprobados, true)) {
                    $relacion = new UserCustomer();
                    $relacion->setUser($solicitud->getUserRequest());
                    $relacion->setCliente($cliente['id']);
                    $relacion->setCustomerRequest($solicitud);
                    $em->persist($relacion);
                    $data["clientes"] = $cliente["razonSocial"];
                }
            }

            $total = count($clientes);
            $cantidadAprobada = count($aprobados);

            if ($cantidadAprobada === 0) {
                $solicitud->setStatus(CustomerRequestStatus::RECHAZADO);
            } elseif ($cantidadAprobada === $total) {
                $solicitud->setStatus(CustomerRequestStatus::APROBADO);
            } else {
                $solicitud->setStatus(CustomerRequestStatus::PARCIALMENTE_APROBADO);
            }
            if ($cantidadAprobada !== 0) {
                $data["nombre"] = $solicitud->getUserRequest()->getFirstName() ." ". $solicitud->getUserRequest()->getLastName();
                $this->notificarCliente($solicitud->getUserRequest()->getEmail(),$data);
            }
            $solicitud->setUserUpdate($this->getUser());
            $em->flush();

            $this->addFlash('success', 'Solicitud procesada correctamente.');
            return $this->redirectToRoute('app_secure_internal_customer_request');
        }

        return $this->render('secure/internal/customer_request/review.html.twig', [
            'solicitud' => $solicitud,
            'clientes' => $clientes,
        ]);
    }

    
    #[Route('/{id}/ver', name: 'customer_secure_internal_request_show')]
    public function show(CustomerRequest $solicitud, EntityManagerInterface $em): Response
    {
        if ($solicitud->getStatus() === CustomerRequestStatus::PENDIENTE) {
            $this->addFlash('warning', 'Esta solicitud aún no ha sido procesada.');
            return $this->redirectToRoute('app_secure_external_customer_request');
        }

        $clienteIdsAprobados = $em->getRepository(UserCustomer::class)
            ->createQueryBuilder('uc')
            ->select('uc.cliente')
            ->where('uc.customerRequest = :req')
            ->setParameter('req', $solicitud)
            ->getQuery()
            ->getSingleColumnResult();

        return $this->render('secure/internal/customer_request/show.html.twig', [
            'solicitud' => $solicitud,
            'aprobados' => $clienteIdsAprobados,
        ]);
    }
    public function notificarCliente($adress,$data)
    {
        $email = (new Email())
            ->from($_ENV['MAIL_FROM'])
            ->to($adress)
            ->subject('Solicitud de Representación aprobada')
            ->html($this->renderView('emails/solicitud_aprobada.html.twig',$data));

        $this->mailer->send($email);
    }
    #[Route('/asignar', name:'app_asignar', methods: ['GET', 'POST'])]
    public function asignarCliente(Request $request,
    ClientesRepository $clientes_repository,
    EntityManagerInterface $em)
    {
        $usuarioBuscado = $request->query->get('usuarioBuscado') ?? null;
        $clienteBuscado = $request->query->get('clienteBuscado') ?? null;
        $asignar  = $request->query->get('asignar') ?? null;
        $data["usuario"] = null;
        $data["cliente"] = null;
        $data["clientesRepresentados"] = [];
        
        if($usuarioBuscado)
        {
            $usuario = $this->user_repository->findOneBy(["email" => $usuarioBuscado]);
            if($usuario)
            {
                $data["usuario"] = $usuario;
                $aux = $this->user_customer_repository->findBy(["user" => $usuario->getId() ]);
                foreach($aux as $a)
                {
                    $cliente = $a->getCliente($clientes_repository);
                    $data["clientesRepresentados"][] = $cliente;
                }
            }
        }
        if($clienteBuscado)
        {
            $cliente = $clientes_repository->findOneBy(["razonSocial" => $clienteBuscado]);
            $data["cliente"] = $cliente;
        }
       if($asignar && $data["cliente"] && $data["usuario"])
        {

            $flag = $this->validarClienteRepresentado($cliente->getCodigoCalipso(),$usuario->getId());
            if(!$flag)
            {
                $solicitud = $this->crearSolicitud( $cliente,$usuario);
                $relacion = new UserCustomer();
                $relacion->setUser($usuario);
                $relacion->setCliente($cliente->getCodigoCalipso());
                $relacion->setCustomerRequest($solicitud);
                $this->em->persist($relacion);
                $this->em->flush(); // ⚠️ IMPORTANTE: Falta el flush() para guardar en la BD
                return $this->json([
                    'success' => true,
                    'message' => 'Cliente asignado correctamente',
                    'usuario' => [
                        'id' => $usuario->getId(),
                        'email' => $usuario->getEmail(),
                        'nombre' => $usuario->getFirstName() . ' ' . $usuario->getLastName()
                    ],
                    'cliente' => [
                        'codigo' => $cliente->getCodigoCalipso(),
                        'razonSocial' => $cliente->getRazonSocial(),
                        'cuit' => $cliente->getCuit()
                        ]
                ]);
            }
            else
            {
                $this->addFlash('warning','Ya tiene asignado este cliente.');
                return $this->json([
                                    'success' => false,
                                    'message' => 'ya tiene asignado este cliente',
                                    'usuario' => [
                                        'id' => $usuario->getId(),
                                        'email' => $usuario->getEmail(),
                                        'nombre' => $usuario->getFirstName() . ' ' . $usuario->getLastName()
                                    ],
                                    'cliente' => [
                                        'codigo' => $cliente->getCodigoCalipso(),
                                        'razonSocial' => $cliente->getRazonSocial(),
                                        'cuit' => $cliente->getCuit()
                                        ]
                ],response::HTTP_BAD_REQUEST);
            }
        }
        return $this->render('secure/internal/customer_request/assigns.html.twig', $data);
    }
    public function crearSolicitud($cliente,$usuario)
    {
        $clienteArray[] = [
            'id' => $cliente->getCodigoCalipso(),
            'cuit' => $cliente->getCuit(),
            'razonSocial' => $cliente->getRazonSocial(),
        ];

        $solicitud = new CustomerRequest();
        $solicitud->setRequestType(CustomerRequestType::REPRESENTACION);
        $solicitud->setStatus(CustomerRequestStatus::APROBADO);
        $solicitud->setUserRequest($usuario);
        $solicitud->setData($clienteArray);

        $this->em->persist($solicitud);
        $this->em->flush();
        return $solicitud;
    }
    public function validarClienteRepresentado($cliente_id,$usuario_id)
    {
        $userCustomer = $this->user_customer_repository->findBy(["cliente" => $cliente_id,'user'=>$usuario_id]);
        return $userCustomer ? true : false;
    }
}
