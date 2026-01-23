<?php

namespace App\EventSubscriber;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class LoginLogSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $loginLogger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
            LoginFailureEvent::class => 'onLoginFailure',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $request = $event->getRequest();
        $user = $event->getUser();

        $this->loginLogger->info('Login exitoso', [
            'email' => $user instanceof User ? $user->getEmail() : $user->getUserIdentifier(),
            'user_id' => $user instanceof User ? $user->getId() : null,
            'ip' => $request->getClientIp(),
            'user_agent' => $request->headers->get('User-Agent'),
        ]);
    }

    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $request = $event->getRequest();
        $passport = $event->getPassport();
        $exception = $event->getException();

        $email = $passport?->getBadge(UserBadge::class)?->getUserIdentifier() ?? 'unknown';

        $this->loginLogger->warning('Login fallido', [
            'email' => $email,
            'ip' => $request->getClientIp(),
            'user_agent' => $request->headers->get('User-Agent'),
            'reason' => $this->getFailureReason($exception),
        ]);
    }

    private function getFailureReason(\Throwable $exception): string
    {
        return match (true) {
            $exception instanceof \Symfony\Component\Security\Core\Exception\BadCredentialsException => 'Credenciales inválidas',
            $exception instanceof \Symfony\Component\Security\Core\Exception\UserNotFoundException => 'Usuario no encontrado',
            $exception instanceof \Symfony\Component\Security\Core\Exception\DisabledException => 'Cuenta deshabilitada',
            $exception instanceof \Symfony\Component\Security\Core\Exception\LockedException => 'Cuenta bloqueada',
            $exception instanceof \Symfony\Component\Security\Core\Exception\AccountExpiredException => 'Cuenta expirada',
            $exception instanceof \Symfony\Component\Security\Core\Exception\CredentialsExpiredException => 'Credenciales expiradas',
            default => $exception->getMessage(),
        };
    }
}
