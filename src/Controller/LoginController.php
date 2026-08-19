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
    private function redirectByRole(): Response
    {
        $user = $this->getUser();
        if ($user->isInternal()) {
            return $this->redirectToRoute('app_secure_internal_home');
        }
        $rolesCatalogo = ['ROLE_INGENIERO_N1', 'ROLE_INGENIERO_N2', 'ROLE_USER'];
        foreach ($user->getRoles() as $rol) {
            if (in_array($rol, $rolesCatalogo, true)) {
                return $this->redirectToRoute('app_catalogo_index');
            }
        }
        return $this->redirectToRoute('app_secure_external_home');
    }

    #[Route(path: '/', name: 'app_home')]
    public function home(): Response
    {
        if ($this->getUser()) {
            return $this->redirectByRole();
        }
        return $this->redirectToRoute('app_login');
    }

    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, PedidosrelacionadosRepository $pedidosrelacionadosRepository): Response
    {
        if ($this->getUser()) {
            return $this->redirectByRole();
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
