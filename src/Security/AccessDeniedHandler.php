<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function __construct(
        private RouterInterface $router,
        private TokenStorageInterface $tokenStorage,
    ) {}

    public function handle(Request $request, AccessDeniedException $accessDeniedException): RedirectResponse
    {
        $token = $this->tokenStorage->getToken();

        if ($token === null) {
            return new RedirectResponse($this->router->generate('app_login'));
        }

        /** @var \App\Entity\User|null $user */
        $user = $token->getUser();

        if ($user instanceof \App\Entity\User) {
            if ($user->isInternal()) {
                return new RedirectResponse($this->router->generate('app_secure_internal_home'));
            }
            $rolesCatalogo = ['ROLE_INGENIERO_N1', 'ROLE_INGENIERO_N2', 'ROLE_USER'];
            foreach ($user->getRoles() as $rol) {
                if (in_array($rol, $rolesCatalogo, true)) {
                    return new RedirectResponse($this->router->generate('app_catalogo_index'));
                }
            }
        }

        return new RedirectResponse($this->router->generate('app_secure_external_home'));
    }
}
