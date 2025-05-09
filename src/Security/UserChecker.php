<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        // Nada por ahora
    }

    public function checkPostAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        // ⚠️ No permitir logueo si está soft-deleted
        if ($user->getDeletedAt() !== null) {
            throw new CustomUserMessageAccountStatusException('Tu cuenta fue deshabilitada.');
        }

        // ⚠️ Si es externo, debe estar verificado
        if (!$user->isInternal() && !$user->getExternalUserData()?->isVerified()) {
            throw new CustomUserMessageAccountStatusException(
            'Tu cuenta aún no fue verificada. <a href="' . '/registro/reenviar-confirmacion' . '" class="text-danger fw-semibold">Solicitar un nuevo correo de verificación</a>.'
        );
        }
    }
}
