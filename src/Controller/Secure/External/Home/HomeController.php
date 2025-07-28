<?php

namespace App\Controller\Secure\External\Home;

use App\Entity\CustomerRequest;
use App\Repository\CustomerRequestRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('secure/clientes/home')]
final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_secure_external_home')]
    public function index(CustomerRequestRepository $customerRequestRepository): Response
    {
        $data['user'] = $this->getUser();
        $requests = $customerRequestRepository->findOneBy(["userRequest" => $data["user"]->getId()]);
        if(!empty($requests))
        {
            return $this->render('secure/external/home/index.html.twig',$data);
        }
        $this->addFlash('info', 'No tiene empresas asignadas para administrar desde su usuario, por favor agregue las empresas que desee administrar desde su perfil.');
        return $this->redirectToRoute('app_secure_external_customer_request');
    }
}