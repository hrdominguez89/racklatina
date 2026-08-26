<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

class CatalogAccessSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Security $security,
        private RouterInterface $router,
        #[Autowire(env: 'bool:CATALOG_RESTRICTED')]
        private bool $catalogRestricted,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 7],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$this->catalogRestricted || !$event->isMainRequest()) {
            return;
        }

        $path = $event->getRequest()->getPathInfo();
        if (!str_starts_with($path, '/catalogo') && !str_starts_with($path, '/secure/proyectos')) {
            return;
        }

        $user = $this->security->getUser();
        $session = $event->getRequest()->getSession();

        if (!$user instanceof User) {
            if ($session instanceof FlashBagAwareSessionInterface) {
                $session->getFlashBag()->add(
                    'info',
                    'El catálogo estará disponible próximamente. Iniciá sesión con tu cuenta @racklatina.com para acceder.'
                );
            }
            $event->setResponse(new RedirectResponse($this->router->generate('app_login')));
            return;
        }

        $rolesPermitidos = ['ROLE_SUPER_ADMIN', 'ROLE_ADMIN', 'ROLE_INGENIERO_N1', 'ROLE_INGENIERO_N2'];
        if (!str_ends_with($user->getEmail(), '@racklatina.com') && !array_intersect($rolesPermitidos, $user->getRoles())) {
            if ($session instanceof FlashBagAwareSessionInterface) {
                $session->getFlashBag()->add('info', 'El catálogo estará disponible próximamente.');
            }
            $event->setResponse(new RedirectResponse($this->router->generate('app_login')));
        }
    }
}
