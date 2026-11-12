<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Services\PasswordPolicyService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

/**
 * Fuerza el cambio de contraseña vencida (ticket 7): si el usuario logueado tiene
 * la contraseña vencida (o nunca la cambió), lo redirige a app_force_change_password
 * en cualquier request, hasta que la cambie. No aplica mientras un admin está
 * impersonando a otro usuario (IS_IMPERSONATOR) — no tiene sentido forzarle el
 * cambio de contraseña del usuario impersonado al admin.
 */
class PasswordExpirationSubscriber implements EventSubscriberInterface
{
    private const RUTA_CAMBIO = 'app_force_change_password';
    private const RUTAS_EXENTAS = [self::RUTA_CAMBIO, 'app_logout'];

    public function __construct(
        private Security $security,
        private RouterInterface $router,
        private PasswordPolicyService $passwordPolicy,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 5],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }

        if ($this->security->isGranted('IS_IMPERSONATOR')) {
            return;
        }

        $route = $event->getRequest()->attributes->get('_route');
        if (in_array($route, self::RUTAS_EXENTAS, true)) {
            return;
        }

        if (!$this->passwordPolicy->estaVencida($user)) {
            return;
        }

        $event->setResponse(new RedirectResponse($this->router->generate(self::RUTA_CAMBIO)));
    }
}
