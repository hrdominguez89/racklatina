<?php

namespace App\Controller\Secure\Internal\User;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('secure/user')]
final class UserController extends AbstractController
{
    #[Route('/', name: 'app_secure_internal_user_user')]
    public function index(UserRepository $userRepository): Response
    {
        $usuarios = $userRepository->findBy([], ['lastName' => 'ASC']);

        return $this->render('secure/internal/user/index.html.twig', [
            'usuarios' => $usuarios,
        ]);
    }
}
