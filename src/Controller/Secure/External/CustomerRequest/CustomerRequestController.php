<?php

namespace App\Controller\Secure\External\CustomerRequest;

use App\Entity\CustomerRequest;
use App\Entity\UserCustomer;
use App\Enum\CustomerRequestStatus;
use App\Enum\CustomerRequestType;
use App\Repository\ClientesRepository;
use App\Repository\CustomerRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;

#[Route('secure/clientes/mis-solicitudes')]
final class CustomerRequestController extends AbstractController
{
    public function __construct(private MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }
    #[Route('/', name: 'app_secure_external_customer_request')]
        
    public function index(Request $request, CustomerRequestRepository $repository): Response
    {
        $statusParam = $request->query->get('status');
        $user = $this->getUser();

        $criteria = ['userRequest' => $user];

        if ($statusParam !== null) {
            // Validar que sea un valor válido del enum
            $statusParam = strtolower($statusParam);
            $statusValido = array_map(fn($e) => $e->value, CustomerRequestStatus::cases());

            if (in_array($statusParam, $statusValido, true)) {
                $criteria['status'] = CustomerRequestStatus::from($statusParam);
            }
        }

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
        return $this->render('secure/external/customer_request/index.html.twig', [
            'solicitudes' => $solicitudes,
            'statusFiltro' => $statusParam,
            'estadosDisponibles' => CustomerRequestStatus::cases(),
        ]);
    }

    #[Route('/nueva', name: 'app_secure_external_customer_request_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createFormBuilder(null)->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $clientesJson = $request->request->get('clientes');
            $clientes = json_decode($clientesJson, true);

            if (!is_array($clientes) || empty($clientes)) {
                $this->addFlash('danger', 'Debés agregar al menos un cliente.');
                return $this->redirectToRoute('app_secure_external_customer_request_new');
            }

            $solicitud = new CustomerRequest();
            $solicitud->setRequestType(CustomerRequestType::REPRESENTACION);
            $solicitud->setStatus(CustomerRequestStatus::PENDIENTE);
            $solicitud->setUserRequest($this->getUser());
            $solicitud->setData($clientes);

            $em->persist($solicitud);
            $em->flush();

            $representante = $this->getUser();
            $representados = $clientes;
            $this->enviarMailDeSolicitudDeRepresentacion($representante,$representados,$solicitud->getId());
            $this->addFlash('success', 'Solicitud enviada correctamente.');
            return $this->redirectToRoute('app_secure_external_customer_request');
        }
        $this->addFlash('warning', "Para representar una empresa debe agregar al menos un cliente y enviar la solicitud.\n1)Ingrese un cuit.\n2)Haga click en 'Buscar clientes'.\n3)Puede buscar y seleccionar más de uno.\n4)Al finalizar la selección hacer click en 'Enviar solicitud'.\nSu solicitud será aprobada por un empleado de RackLatina a la brevedad.");



        return $this->render('secure/external/customer_request/form.html.twig', [
            'form' => $form->createView(),
            'clientesJson' => '[]',
            'modo' => 'crear',
            'cuitInicial' => '',
        ]);
    }


    #[Route('/{id}/editar', name: 'customer_request_edit')]
    public function edit(CustomerRequest $solicitud, Request $request, EntityManagerInterface $em): Response
    {
        if ($solicitud->getUserRequest() !== $this->getUser()) {
            throw $this->createAccessDeniedException('No tenés permiso para editar esta solicitud.');
        }

        $form = $this->createFormBuilder(null)->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $clientesJson = $request->request->get('clientes');
            $clientes = json_decode($clientesJson, true);

            if (!is_array($clientes) || empty($clientes)) {
                $this->addFlash('danger', 'Debés mantener al menos un cliente.');
                return $this->redirectToRoute('customer_request_edit', ['id' => $solicitud->getId()]);
            }

            $solicitud->setData($clientes);
            $solicitud->setUserUpdate($this->getUser());
            $em->flush();

            $this->addFlash('success', 'Solicitud actualizada correctamente.');
            return $this->redirectToRoute('app_secure_external_customer_request');
        }

        $primerCuit = $solicitud->getData()[0]['cuit'] ?? '';

        return $this->render('secure/external/customer_request/form.html.twig', [
            'form' => $form->createView(),
            'clientesJson' => json_encode($solicitud->getData()),
            'modo' => 'editar',
            'cuitInicial' => $primerCuit,
        ]);
    }

    #[Route('/{id}/ver', name: 'customer_request_show')]
    public function show(CustomerRequest $solicitud, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if ($solicitud->getUserRequest() !== $user) {
            throw $this->createAccessDeniedException('No tenés permiso para ver esta solicitud.');
        }

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

        return $this->render('secure/external/customer_request/show.html.twig', [
            'solicitud' => $solicitud,
            'aprobados' => $clienteIdsAprobados,
        ]);
    }

    #[Route('/buscar-clientes-por-cuit', name: 'app_secure_external_customer_request_buscar_clientes', methods: ['GET'])]
    public function buscarClientesPorCuit(Request $request, ClientesRepository $clientesRepository): JsonResponse
    {
        $cuit = $request->query->get('cuit');

        if (!preg_match('/^\d{2}-\d{8}-\d$/', $cuit)) {
            return new JsonResponse(['error' => 'CUIT inválido.'], Response::HTTP_BAD_REQUEST);
        }

        // Buscar todos los clientes con ese CUIT, sin importar estado
        $clientes = $clientesRepository->findBy(['cuit' => $cuit]);

        $resultado = array_map(fn($cliente) => [
            'id' => $cliente->getCodigoCalipso(),
            'razonSocial' => $cliente->getRazonSocial() ?? '(Sin nombre)',
            'cuit' => $cliente->getCuit() ?? '(Sin CUIT)',
        ], $clientes);

        return new JsonResponse($resultado);
    }
    public function enviarMailDeSolicitudDeRepresentacion($representante,$representados,$solicitud_id)
    {
        $template = "emails/solicitud_de_representacion.html.twig";
        $email = (new Email())
            ->from($_ENV['MAIL_FROM'])
            ->to($_ENV['MAIL_CENTRO_RAC'])
            ->subject('Solicitud de Representación')
            ->html($this->renderView($template, [
                'solicitud_id' => $solicitud_id,
                'representante' => $representante,
                'representados' => $representados
            ]));
        $this->mailer->send($email);
    }
}
