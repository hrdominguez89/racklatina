<?php

namespace App\Controller\Secure\External\Formularios;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


#[Route('secure/clientes/formularios-cliente')]
final class FormularioController extends AbstractController
{
    #[Route('/', name: 'app_secure_external_formularios_servicios')]
    public function index( Request $request): Response
    {
         return $this->render('secure/external/formularios_servicios/index.html.twig', [
            'controller_name' => 'CuentasController',
        ]);
    }

    #[Route('/carga', name: 'app_secure_external_formularios_servicios_carga')]
    public function carga( Request $request): Response
    {
        $data = $request->request->all();

        return $this->render('secure/external/formularios_servicios/index.html.twig', [
            'controller_name' => 'FormularioController',
        ]);
    }
}