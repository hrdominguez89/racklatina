<?php

namespace App\Controller\Security;

use App\Entity\User;
use App\Services\PasswordPolicyService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Pantalla de cambio obligatorio de contraseña vencida (ticket 7). Se llega acá
 * redirigido por PasswordExpirationSubscriber — no hay otra forma de navegar el
 * portal con una contraseña de más de 90 días (o sin passwordChangedAt) hasta
 * completar el cambio.
 */
final class ForceChangePasswordController extends AbstractController
{
    #[Route('/secure/cambiar-contrasena-vencida', name: 'app_force_change_password')]
    public function index(Request $request, EntityManagerInterface $em, PasswordPolicyService $passwordPolicy): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$passwordPolicy->estaVencida($user)) {
            return $this->redirectToRoute('app_home');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('force_change_password', $request->request->get('_csrf_token'))) {
                $this->addFlash('danger', 'Token inválido.');
                return $this->redirectToRoute('app_force_change_password');
            }

            $password = $request->request->get('password');
            $confirm = $request->request->get('confirm_password');

            if (!$password || $password !== $confirm) {
                $this->addFlash('danger', 'Las contraseñas no coinciden.');
                return $this->redirectToRoute('app_force_change_password');
            }

            if (!$passwordPolicy->esCompleja($password)) {
                $this->addFlash('danger', PasswordPolicyService::MENSAJE_COMPLEJIDAD);
                return $this->redirectToRoute('app_force_change_password');
            }

            if ($passwordPolicy->fueUsadaRecientemente($user, $password)) {
                $this->addFlash('danger', PasswordPolicyService::MENSAJE_REUTILIZADA);
                return $this->redirectToRoute('app_force_change_password');
            }

            $passwordPolicy->aplicarCambio($user, $password);
            $em->flush();

            $this->addFlash('success', 'Contraseña actualizada correctamente.');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('security/force_change_password.html.twig');
    }
}
