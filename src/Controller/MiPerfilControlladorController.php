<?php

namespace App\Controller;

use App\Repository\ExternalUserDataRepository;
use App\Repository\SectorsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MiPerfilControlladorController extends AbstractController{


    public function __construct(
    private EntityManagerInterface $entityManager,
    private ExternalUserDataRepository $externalUserDataRepository,
    private SectorsRepository $sectorRepoitory)
    {
        $this->entityManager = $entityManager;
        $this->externalUserDataRepository = $externalUserDataRepository;
        $this->sectorRepoitory = $sectorRepoitory;
        
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
        $externalUserData = $this->externalUserDataRepository->findOneBy(['user' => $this->getUser()->getId()]);
        return $this->render('mi_perfil_controllador/editarMiperfil.html.twig', [
            'controller_name' => 'MiPerfilControlladorController',
            'user' => $this->getUser(),
            'external_user_data' => $externalUserData,
            'sectores' => $this->sectorRepoitory->findAll()
        ]);
    }

    #[Route('/mi/perfil/controllador/edicion/guardar', name: 'app_editar_perfil_guardar', methods: ['POST'])]
    public function guardarCambiosPerfil(Request $request): Response
    {
        $data = $request->request->all();

        $user = $this->getUser();
        $user->setFirstName($data['firstName'] ?? null);
        $user->setLastName($data['lastName'] ?? null);
        $user->setEmail($data['email'] ?? null);
        $user->setNationalIdNumber($data['dni'] ?? null);
        if ($data['password'] !== '') {
            $user->setPassword($passwordHasher->hashPassword($user, $data['password']));
        }
        $id = $user->getId();
        $userExternalData = $this->externalUserDataRepository->findOneBy(['user' => $id]);
        $userExternalData->setJobTitle($data['jobTitle'] ?? null);
        $userExternalData->setCompanyName($data['companyName'] ?? null);
        $userExternalData->setPais($data['country'] ?? null);
        $userExternalData->setProvincia($data['province'] ?? null);
        $userExternalData->setSegmento($data['segment'] ?? null);
        $sector = $this->sectorRepoitory->find($data['sector'] ?? null);
        $userExternalData->setSector($sector);
        $userExternalData->setPhoneNumber($data['phoneNumber'] ?? null);
        $userExternalData->setSectorExtraData($data['sectorExtraData'] ?? null);
        $this->entityManager->persist($userExternalData);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->render('secure/external/home/index.html.twig', [
            'controller_name' => 'MiPerfilControlladorController',
            'user' => $this->getUser()
        ]);
    }
}
