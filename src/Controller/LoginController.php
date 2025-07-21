<?php

namespace App\Controller;

use App\Form\LoginFormType;
use App\Repository\PedidosrelacionadosRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    #[Route(path: '/', name: 'app_home')]
    public function home(): Response
    {
        if ($this->getUser()) {
            if ($this->getUser()->isInternal()) {
                return $this->redirectToRoute('app_secure_internal_home');
            }
            return $this->redirectToRoute('app_secure_external_home');
        }
        return $this->redirectToRoute('app_login');
    }

    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, PedidosrelacionadosRepository $pedidosrelacionadosRepository): Response
    {
        if ($this->getUser()) {
            if ($this->getUser()->isInternal()) {
                return $this->redirectToRoute('app_secure_internal_home');
            }
            // $requets =  $this->getUser()->getUserRequests();
            // if($requets)
            // {
            //     return $this->render('secure/external/home/index.html.twig');
            // }
            // $this->addFlash('info', 'No tiene empresas asignadas para administrar desde su usuario, por favor agregue las empresas que desee administrar desde su perfil.');
            // return $this->redirectToRoute('app_secure_external_customer_request');

            return $this->redirectToRoute('app_secure_external_home');
        }


        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('login/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'login' => true,
            'title' => 'Iniciar sesión'
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
