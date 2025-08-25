<?php

namespace App\Controller\Secure\Internal\CustomerRequest;

use App\Entity\CustomerRequest;
use App\Entity\UserCustomer;
use App\Enum\CustomerRequestStatus;
use App\Repository\CustomerRequestRepository;
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
    private MailerInterface $mailer) 
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
        if($solicitudes != null)
        {
            $mensaje = "Te enviaremos un email de confirmación una vez que sea aprobada.
            Tu solicitud está siendo evaluada para representar a la empresa:";
            foreach($solicitudes as $solicitud)
            {
                if($solicitud->getStatus() == CustomerRequestStatus::PENDIENTE)
                {
                    $mensaje = $mensaje." ".$solicitud->getData()[0]['razonSocial']."\n";
                }
            }
            $this->addFlash('info', $mensaje);
        }

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
            if ($cantidadAprobada === 0) {
            $this->notificarCliente($solicitud->getUserRequest()->getEmail());
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
    public function notificarCliente($adress)
    {
        $email = (new Email())
            ->from($_ENV['MAIL_FROM'])
            ->to($adress)
            ->subject('Solicitud de Representación aprobada')
            ->html($this->renderView('emails/solicitud_aprobada.html.twig'));

        $this->mailer->send($email);
    }
}
