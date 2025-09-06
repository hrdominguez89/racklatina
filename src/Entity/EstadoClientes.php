<?php

namespace App\Entity;

use App\Repository\EstadoClientesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'estado_clientes')]
#[ORM\Entity(repositoryClass: EstadoClientesRepository::class,readOnly: true)]
class EstadoClientes
{
    #[ORM\Id]
    #[ORM\Column(name: "Codigo_Estado", length: 1, options: ["default" => '0'])]
    private ?string $codigoEstado = '\'0\'';

    #[ORM\Column(name: "Nombre_Estado", length: 50, options: ["default" => ''])]
    private ?string $nombreEstado = '\'\'';

    #[ORM\Column(name: "Estado", length: 50, options: ["default" => ''])]
    private ?string $estado = '\'\'';

    #[ORM\Column(name: "Detalle_Estado", length: 250, options: ["default" => ''])]
    private ?string $detalleEstado = '\'\'';

    public function getCodigoEstado(): ?string
    {
        return $this->codigoEstado;
    }

    public function setCodigoEstado(string $codigoEstado): static
    {
        $this->codigoEstado = $codigoEstado;

        return $this;
    }

    public function getNombreEstado(): ?string
    {
        return $this->nombreEstado;
    }

    public function setNombreEstado(string $nombreEstado): static
    {
        $this->nombreEstado = $nombreEstado;

        return $this;
    }

    public function getEstado(): ?string
    {
        return $this->estado;
    }

    public function setEstado(string $estado): static
    {
        $this->estado = $estado;

        return $this;
    }

    public function getDetalleEstado(): ?string
    {
        return $this->detalleEstado;
    }

    public function setDetalleEstado(string $detalleEstado): static
    {
        $this->detalleEstado = $detalleEstado;

        return $this;
    }
}
