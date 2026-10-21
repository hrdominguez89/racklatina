<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Entity\UserCustomer;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
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
        #[Autowire(env: 'CATALOG_ALLOWED_EMAILS')]
        private string $catalogAllowedEmails = '',
        #[Autowire(env: 'CATALOG_ALLOWED_CLIENTES')]
        private string $catalogAllowedClientes = '',
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

        $dominiosInternos = ['@racklatina.com', '@racklatina.com.ar', '@racklatina.com.uy'];
        $emailInterno = false;
        foreach ($dominiosInternos as $dominio) {
            if (str_ends_with($user->getEmail(), $dominio)) {
                $emailInterno = true;
                break;
            }
        }

        $rolesPermitidos = ['ROLE_SUPER_ADMIN', 'ROLE_ADMIN'];
        $emailWhitelisteado = in_array($user->getEmail(), $this->parseList($this->catalogAllowedEmails), true);
        $clientesPermitidos = $this->parseList($this->catalogAllowedClientes);
        $codigosUsuario = array_map(
            fn (UserCustomer $uc) => $uc->getClienteCodigo(),
            $user->getUserCustomers()->toArray()
        );
        $empresaWhitelisteada = (bool) array_intersect($clientesPermitidos, $codigosUsuario);

        if (
            !$emailInterno
            && !$emailWhitelisteado
            && !$empresaWhitelisteada
            && !array_intersect($rolesPermitidos, $user->getRoles())
        ) {
            // No redirigir a /login: un usuario ya autenticado sin acceso puede tener
            // /catalogo como "home" por rol (ver LoginSuccessHandler/LoginController),
            // lo que provocaría un loop de redirects login <-> catalogo.
            $event->setResponse(new Response(
                'El catálogo estará disponible próximamente. <a href="' . $this->router->generate('app_logout') . '">Cerrar sesión</a>',
                Response::HTTP_FORBIDDEN
            ));
        }
    }

    /**
     * Convierte un CSV de env var ("01001196, 01008556") en lista limpia sin vacíos.
     */
    private function parseList(string $csv): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $csv))));
    }
}
