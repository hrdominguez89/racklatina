<?php

namespace App\Entity;

use App\Repository\ServiciosAdjuntosRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'ServiciosAdjuntos')]
#[ORM\Entity(repositoryClass: ServiciosAdjuntosRepository::class)]
class ServiciosAdjuntos
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "id", type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Servicios::class, inversedBy: "adjuntos")]
    #[ORM\JoinColumn(name: "servicio_id", referencedColumnName: "serviceID", nullable: false)]
    private ?Servicios $servicio = null;

    #[ORM\Column(name: "filename", type: "string", length: 255, nullable: false)]
    private ?string $filename = null;

    #[ORM\Column(name: "filepath", type: "string", length: 255, nullable: false)]
    private ?string $filepath = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getServicio(): ?Servicios
    {
        return $this->servicio;
    }

    public function setServicio(?Servicios $servicio): static
    {
        $this->servicio = $servicio;

        return $this;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): static
    {
        $this->filename = $filename;

        return $this;
    }

    public function getFilepath(): ?string
    {
        return $this->filepath;
    }

    public function setFilepath(string $filepath): static
    {
        $this->filepath = $filepath;

        return $this;
    }
}
