<?php

namespace App\Controller\Secure\External\Encuesta;

use App\Entity\EncuestaRespuesta;
use App\Entity\User;
use App\Repository\EncuestaRespuestaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('secure/clientes/encuesta')]
final class EncuestaController extends AbstractController
{
    #[Route('/verificar', name: 'app_encuesta_verificar', methods: ['GET'])]
    public function verificar(EncuestaRespuestaRepository $repository): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Usuario no autenticado'
            ], 401);
        }

        $yaRespondio = $repository->hasUserResponded($user);

        return new JsonResponse([
            'success' => true,
            'yaRespondio' => $yaRespondio
        ]);
    }

    #[Route('/responder', name: 'app_encuesta_responder', methods: ['POST'])]
    public function responder(
        Request $request,
        EntityManagerInterface $entityManager,
        EncuestaRespuestaRepository $repository
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Usuario no autenticado'
            ], 401);
        }

        // Verificar si ya respondió
        if ($repository->hasUserResponded($user)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Ya has respondido esta encuesta'
            ], 400);
        }

        $data = json_decode($request->getContent(), true);
        $calificacion = $data['calificacion'] ?? null;

        if ($calificacion === null || !is_numeric($calificacion) || $calificacion < 1 || $calificacion > 5) {
            return new JsonResponse([
                'success' => false,
                'message' => 'La calificación debe ser un número entre 1 y 5'
            ], 400);
        }

        $respuesta = new EncuestaRespuesta();
        $respuesta->setUser($user);
        $respuesta->setCalificacion((int) $calificacion);

        $entityManager->persist($respuesta);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => '¡Gracias por tu opinión!'
        ]);
    }
}
