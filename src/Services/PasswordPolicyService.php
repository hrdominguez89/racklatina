<?php

namespace App\Services;

use App\Entity\PasswordHistory;
use App\Entity\User;
use App\Repository\PasswordHistoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Política de contraseñas centralizada (ticket 7): complejidad, caducidad a 90 días,
 * y no reutilizar ninguna de las últimas 5 contraseñas. Pensada para invocarse desde
 * cualquier punto del código que setea una contraseña (registro, "olvidé mi contraseña",
 * mi perfil, alta/edición de usuarios por un admin interno).
 */
class PasswordPolicyService
{
    public const HISTORIAL_LIMITE = 5;
    public const DIAS_VENCIMIENTO = 90;

    public const MENSAJE_COMPLEJIDAD = 'La contraseña debe tener al menos 8 caracteres, incluyendo una letra, un número y un símbolo.';
    public const MENSAJE_REUTILIZADA = 'No podés reutilizar ninguna de tus últimas 5 contraseñas.';

    // Al menos 8 caracteres, 1 letra, 1 número y 1 símbolo (cualquier carácter que no sea letra/número/espacio).
    public const PATTERN_COMPLEJIDAD = '/^(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z\d\s]).{8,}$/u';

    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private PasswordHistoryRepository $historyRepo,
        private EntityManagerInterface $em,
    ) {}

    public function esCompleja(string $plainPassword): bool
    {
        return (bool) preg_match(self::PATTERN_COMPLEJIDAD, $plainPassword);
    }

    /**
     * True si $plainPassword coincide con la contraseña actual del usuario o con
     * alguna de sus últimas HISTORIAL_LIMITE contraseñas.
     */
    public function fueUsadaRecientemente(User $user, string $plainPassword): bool
    {
        if ($user->getPassword() !== null && password_verify($plainPassword, $user->getPassword())) {
            return true;
        }

        foreach ($this->historyRepo->findRecientes($user, self::HISTORIAL_LIMITE) as $entrada) {
            if (password_verify($plainPassword, $entrada->getPasswordHash())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Aplica el cambio de contraseña: guarda el hash ANTERIOR en el historial (podando al
     * límite) antes de reemplazarlo, hashea y setea la nueva, y actualiza passwordChangedAt.
     * No hace flush — responsabilidad del caller (para poder combinarlo con otros cambios
     * en la misma transacción/request).
     */
    public function aplicarCambio(User $user, string $plainPassword): void
    {
        $hashAnterior = $user->getPassword();
        if ($hashAnterior !== null) {
            $this->em->persist(new PasswordHistory($user, $hashAnterior));
            $this->podarHistorial($user);
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        $user->setPasswordChangedAt(new \DateTimeImmutable());
    }

    /** True si nunca cambió la contraseña o pasaron más de DIAS_VENCIMIENTO días desde el último cambio. */
    public function estaVencida(User $user): bool
    {
        $cambio = $user->getPasswordChangedAt();
        if ($cambio === null) {
            return true;
        }

        return $cambio < new \DateTimeImmutable('-' . self::DIAS_VENCIMIENTO . ' days');
    }

    private function podarHistorial(User $user): void
    {
        $entradas = $this->historyRepo->findRecientes($user, self::HISTORIAL_LIMITE + 1);
        if (count($entradas) >= self::HISTORIAL_LIMITE) {
            foreach (array_slice($entradas, self::HISTORIAL_LIMITE - 1) as $vieja) {
                $this->em->remove($vieja);
            }
        }
    }
}
