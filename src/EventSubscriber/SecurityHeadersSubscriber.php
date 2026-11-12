<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Headers de seguridad HTTP (ticket 12).
 *
 * Los 5 headers de bajo riesgo se aplican directo (ya se auditaron, no rompen nada).
 * La CSP se aplica SOLO en modo Report-Only: todavía no hay un relevamiento con
 * navegador real de qué scripts/estilos inline y de qué dominios externos carga cada
 * página del portal, así que forzarla en modo enforcement ahora podría romper cosas en
 * producción. El header Content-Security-Policy-Report-Only no bloquea nada — el
 * navegador solo reporta en su consola qué hubiera bloqueado. Antes de pasar a
 * enforcement real, hay que navegar las páginas principales con la consola abierta,
 * revisar qué violaciones aparecen, y resolverlas (nonces para inline scripts o
 * migrarlos a archivos externos) — ver docs/tickets-soporte-roadmap.md, ticket 12.
 */
class SecurityHeadersSubscriber implements EventSubscriberInterface
{
    /**
     * Política inicial armada relevando (grep) los dominios externos y scripts/estilos
     * inline que usan templates/base.html.twig, base_public.html.twig, base_catalogo.html.twig
     * y las vistas que más los extienden (registro, recuperar contraseña, service requests,
     * gestor de órdenes de compra). No reemplaza la revisión manual en navegador.
     */
    private const CSP_REPORT_ONLY = "default-src 'self'; "
        . "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdn.datatables.net https://www.clarity.ms https://www.google.com https://www.gstatic.com; "
        . "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdn.datatables.net https://fonts.googleapis.com; "
        . "font-src 'self' https://fonts.gstatic.com data:; "
        . "img-src 'self' data: https://link2-files.s3.us-east-1.amazonaws.com https://cdn.lordicon.com; "
        . "connect-src 'self' https://www.clarity.ms https://cdn.lordicon.com; "
        . "frame-src https://www.google.com; "
        . "object-src 'none'; "
        . "base-uri 'self'; "
        . "form-action 'self'; "
        . "frame-ancestors 'self';";

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $headers = $event->getResponse()->headers;

        $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        $headers->set('Content-Security-Policy-Report-Only', self::CSP_REPORT_ONLY);
    }
}
