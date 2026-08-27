<?php

namespace App\Twig;

use App\Entity\Proyecto;
use App\Entity\User;
use App\Repository\ClientesRepository;
use App\Repository\ProyectoRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ClientesExtension extends AbstractExtension
{
    public function __construct(
        private ClientesRepository $clientesRepo,
        private ProyectoRepository $proyectoRepo,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('cliente_razon_social', [$this, 'getRazonSocial']),
            new TwigFunction('proyectos_del_usuario', [$this, 'getProyectos']),
            new TwigFunction('proyecto_activo', [$this, 'getProyectoActivo']),
        ];
    }

    public function getRazonSocial(string $codigo): string
    {
        $cliente = $this->clientesRepo->find($codigo);
        return $cliente?->getRazonSocial() ?? $codigo;
    }

    /**
     * Proyectos del usuario para la empresa activa.
     * @return Proyecto[]
     */
    public function getProyectos(User $user): array
    {
        return $this->proyectoRepo->findByUser($user, $user->getActiveClienteCodigo());
    }

    /**
     * Proyecto activo del usuario (o null si no hay ninguno configurado / ya no existe).
     */
    public function getProyectoActivo(User $user): ?Proyecto
    {
        $id = $user->getActiveProyectoId();
        if ($id === null) {
            return null;
        }
        $proyecto = $this->proyectoRepo->find($id);
        // Si fue eliminado o no pertenece al usuario, limpiar
        if (!$proyecto || $proyecto->getUser()->getId() !== $user->getId()) {
            return null;
        }
        return $proyecto;
    }
}
