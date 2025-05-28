<?php

namespace App\Controller\Secure\Internal\CustomerRequest;

use App\Entity\UserCustomer;
use App\Enum\CustomerRequestStatus;
use App\Repository\CustomerRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('secure/customer-request')]
final class CustomerRequestController extends AbstractController
{
    #[Route('/', name: 'app_secure_internal_customer_request')]
    public function index(CustomerRequestRepository $repository): Response
    {
        $solicitudes = $repository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('secure/internal/customer_request/index.html.twig', [
            'solicitudes' => $solicitudes,
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
}
