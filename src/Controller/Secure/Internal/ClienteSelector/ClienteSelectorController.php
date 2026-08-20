<?php

namespace App\Controller\Secure\Internal\ClienteSelector;

use App\Repository\ClientesRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Permite a usuarios internos (ADMIN/SUPER_ADMIN) operar en nombre
 * de un usuario externo registrado en el portal.
 *
 * Usa el mecanismo nativo de Symfony switch_user:
 *   - seleccionar() devuelve una URL con ?_switch_user=email
 *   - Para volver: navegar a cualquier URL con ?_switch_user=_exit
 */
#[Route('/secure/cliente-selector')]
final class ClienteSelectorController extends AbstractController
{
    // Clave de sesión solo para mostrar el nombre de empresa en el header
    public const SESSION_CLIENTE_NOMBRE = 'ctx_cliente_nombre';

    // ── Endpoints ────────────────────────────────────────────────────────────

    /**
     * AJAX: busca usuarios externos por nombre, apellido, email o razón social
     * del cliente Calypso asociado.
     * Requiere al menos 2 caracteres.
     */
    #[Route('/buscar', name: 'app_cliente_selector_buscar', methods: ['GET'])]
    public function buscar(Request $request, UserRepository $userRepo): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $q = trim($request->query->getString('q'));

        if (mb_strlen($q) < 2) {
            return $this->json([]);
        }

        $usuarios = $userRepo->searchExternalUsersForSelector($q);

        return $this->json($usuarios);
    }

    /**
     * Valida el usuario externo y devuelve la URL de switch_user para que
     * el JS navegue a ella. Symfony cambia el usuario autenticado en sesión.
     *
     * Body JSON: { "userId": 42 }
     * Response:  { "ok": true, "switchUrl": "/secure/proyectos?_switch_user=email" }
     */
    #[Route('/seleccionar', name: 'app_cliente_selector_seleccionar', methods: ['POST'])]
    public function seleccionar(
        Request            $request,
        UserRepository     $userRepo,
        ClientesRepository $clientesRepo,
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $data   = json_decode($request->getContent(), true);
        $userId = (int) ($data['userId'] ?? 0);

        if ($userId <= 0) {
            return $this->json(['error' => 'ID de usuario requerido.'], 400);
        }

        $user = $userRepo->find($userId);

        if ($user === null || $user->isInternal()) {
            return $this->json(['error' => 'Usuario externo no encontrado.'], 404);
        }

        // Primer UserCustomer activo
        $userCustomers = $user->getUserCustomers()->filter(fn($uc) => $uc->getDeletedAt() === null);
        $firstUc       = $userCustomers->first();

        if ($firstUc === false) {
            return $this->json(['error' => 'El usuario no tiene un cliente Calypso asociado.'], 422);
        }

        $clienteCodigo = $firstUc->getClienteCodigo();
        $cliente       = $clientesRepo->find($clienteCodigo);
        $clienteNombre = $cliente?->getRazonSocial() ?? $clienteCodigo;

        // Guardamos solo el nombre de empresa en sesión para mostrarlo en el header.
        // El resto de la identidad lo maneja Symfony via switch_user.
        $request->getSession()->set(self::SESSION_CLIENTE_NOMBRE, $clienteNombre);

        return $this->json([
            'ok'        => true,
            'switchUrl' => $this->generateUrl('app_proyectos_index') . '?_switch_user=' . urlencode($user->getEmail()),
        ]);
    }
}
