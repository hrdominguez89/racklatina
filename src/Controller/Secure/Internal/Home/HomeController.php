<?php

namespace App\Controller\Secure\Internal\Home;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('secure/home')]
final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_secure_internal_home')]
    public function index(UserRepository $userRepository): Response
    {
        $externalUsers = $userRepository->findExternalUsers();

        return $this->render('secure/internal/home/index.html.twig', [
            'external_users' => $externalUsers,
        ]);
    }
}
