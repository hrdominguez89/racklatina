<?php

namespace App\Controller\Secure\Internal\CustomerRequest;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('secure/customer-request')]
final class CustomerRequestController extends AbstractController
{
    #[Route('/', name: 'app_secure_internal_customer_request')]
    public function index(): Response
    {
        return $this->render('controller/secure/internal/customer_request/customer_request/index.html.twig', [
            'controller_name' => 'CustomerRequestController',
        ]);
    }
}
