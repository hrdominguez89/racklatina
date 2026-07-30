<?php

namespace App\Controller\Secure\External\Encuesta;

use App\Entity\EncuestaNps;
use App\Entity\User;
use App\Repository\EncuestaNpsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('secure/clientes/encuesta-nps')]
final class EncuestaNpsController extends AbstractController
{
    #[Route('/verificar', name: 'app_encuesta_nps_verificar', methods: ['GET'])]
    public function verificar(EncuestaNpsRepository $repository): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
        }

        return new JsonResponse([
            'success' => true,
            'yaRespondio' => $repository->hasUserResponded($user),
        ]);
    }

    #[Route('/responder', name: 'app_encuesta_nps_responder', methods: ['POST'])]
    public function responder(
        Request $request,
        EntityManagerInterface $entityManager,
        EncuestaNpsRepository $repository
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return new JsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
        }

        if ($repository->hasUserResponded($user)) {
            return new JsonResponse(['success' => false, 'message' => 'Ya respondiste esta encuesta'], 400);
        }

        $data = json_decode($request->getContent(), true);
        $score = $data['score'] ?? null;
        $comentario = isset($data['comentario']) ? trim($data['comentario']) : null;

        if ($score === null || !is_numeric($score) || (int) $score < 0 || (int) $score > 10) {
            return new JsonResponse(['success' => false, 'message' => 'El puntaje debe ser un número entre 0 y 10'], 400);
        }

        $respuesta = new EncuestaNps();
        $respuesta->setUser($user);
        $respuesta->setScore((int) $score);
        $respuesta->setComentario($comentario ?: null);

        $entityManager->persist($respuesta);
        $entityManager->flush();

        return new JsonResponse(['success' => true, 'message' => '¡Gracias por tu opinión!']);
    }
}
