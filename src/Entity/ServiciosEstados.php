<?php

namespace App\Entity;

use App\Repository\ServiciosEstadosRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'servicios_estados')]
#[ORM\Entity(repositoryClass: ServiciosEstadosRepository::class,readOnly: true)]
class ServiciosEstados
{
    #[ORM\Id]
    #[ORM\Column(name: "estado_ID", nullable: true)]
    private ?int $estadoId = NULL;

    #[ORM\Column(name: "estado_Descrip", length: 100, nullable: true)]
    private ?string $estadoDescrip = 'NULL';

    public function getEstadoId(): ?int
    {
        return $this->estadoId;
    }

    public function setEstadoId(?int $estadoId): static
    {
        $this->estadoId = $estadoId;

        return $this;
    }

    public function getEstadoDescrip(): ?string
    {
        return $this->estadoDescrip;
    }

    public function setEstadoDescrip(?string $estadoDescrip): static
    {
        $this->estadoDescrip = $estadoDescrip;

        return $this;
    }
}
