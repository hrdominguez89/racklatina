<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MiPerfilControlladorController extends AbstractController{


    public function __construct(
    private EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    #[Route('/mi/perfil/controllador', name: 'app_mi_perfil_controllador')]
    public function index(): Response
    {
        return $this->render('mi_perfil_controllador/miPerfil.html.twig', [
            'controller_name' => 'MiPerfilControlladorController',
            'user' => $this->getUser()
        ]);
    }
    #[Route('/mi/perfil/controllador/edicion', name: 'app_mi_perfil_controllador_edicion')]
    public function editarPerfil(Request $request): Response
    {

        return $this->render('mi_perfil_controllador/editarMiPerfil.html.twig', [
            'controller_name' => 'MiPerfilControlladorController',
            'user' => $this->getUser()
        ]);
    }

    #[Route('/mi/perfil/controllador/edicion/guardar', name: 'app_editar_perfil_guardar', methods: ['POST'])]
    public function guardarCambiosPerfil(Request $request): Response
    {
        $user = $this->getUser();
        $user->setFirstName($request->request->get('firstName'));
        $user->setLastName($request->request->get('lastName'));
        $user->setEmail($request->request->get('email'));
        $user->setNationalIdNumber($request->request->get('dni'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();
        return $this->render('mi_perfil_controllador/miPerfil.html.twig', [
            'controller_name' => 'MiPerfilControlladorController',
            'user' => $this->getUser()
        ]);
    }
}
