<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private RouterInterface $router,
        private EntityManagerInterface $em,
    ) {}

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): RedirectResponse
    {
        /** @var User $user */
        $user = $token->getUser();

        // Limpiar empresa y proyecto activos: el usuario debe seleccionarlos en cada sesión
        $user->setActiveCliente(null);
        $user->setActiveProyectoId(null);
        $this->em->flush();

        if ($user->isInternal()) {
            return new RedirectResponse($this->router->generate('app_secure_internal_home'));
        }

        $roles = $user->getRoles();
        $rolesCatalogo = ['ROLE_INGENIERO_N1', 'ROLE_INGENIERO_N2'];
        $esCatalogoOnly = (bool) array_intersect($rolesCatalogo, $roles);

        if ($esCatalogoOnly) {
            return new RedirectResponse($this->router->generate('app_catalogo_index'));
        }

        // Para roles con acceso al portal, respetar redirect solicitado
        $targetPath = $request->request->get('_target_path');
        if ($targetPath && str_starts_with($targetPath, '/')) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->router->generate('app_secure_external_home'));
    }
}
