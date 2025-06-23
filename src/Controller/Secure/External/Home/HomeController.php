<?php

namespace App\Controller\Secure\External\Home;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('secure/clientes/home')]
final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_secure_external_home')]
    public function index(): Response
    {
        $data['user'] = $this->getUser();
        return $this->render('secure/external/home/index.html.twig',$data);
    }
}
