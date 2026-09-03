<?php

namespace App\EventListener;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Twig\Environment;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 100)]
class MaintenanceListener
{
    private const ALLOWED_PATHS = [
        '/login',
        '/logout',
        '/registro',
        '/forgot-password',
        '/change-password',
        '/resend-token',
    ];

    public function __construct(
        private readonly Environment $twig,
        #[Autowire(env: 'default::IN_MAINTENANCE')]
        private readonly string $inMaintenance = '',
    ) {}

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (strtolower($this->inMaintenance) !== 'true') {
            return;
        }

        $path = $event->getRequest()->getPathInfo();

        if (preg_match('~^/(css|js|images|libs|styles|fonts|assets|favicon)~', $path)) {
            return;
        }

        foreach (self::ALLOWED_PATHS as $allowed) {
            if (str_starts_with($path, $allowed)) {
                return;
            }
        }

        $html = $this->twig->render('maintenance/index.html.twig');

        $event->setResponse(new Response($html, Response::HTTP_SERVICE_UNAVAILABLE));
        $event->stopPropagation();
    }
}
