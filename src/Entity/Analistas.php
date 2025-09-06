<?php

namespace App\Entity;

use App\Repository\AnalistasRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'analistas')]
#[ORM\Entity(repositoryClass: AnalistasRepository::class,readOnly: true)]
class Analistas
{
    #[ORM\Id]
    #[ORM\Column(name: "Codigo", length: 50)]
    #[ORM\GeneratedValue(strategy: "NONE")]
    private ?string $codigo = null;

    #[ORM\Column(name: "Nombre", length: 50, nullable: true)]
    private ?string $nombre = 'NULL';

    #[ORM\Column(name: "Mail", length: 255, nullable: true)]
    private ?string $mail = 'NULL';

    #[ORM\Column(name: "Grupo", length: 10, nullable: true)]
    private ?string $grupo = 'NULL';

    public function getCodigo(): ?string
    {
        return $this->codigo;
    }

    public function setCodigo(string $codigo): static
    {
        $this->codigo = $codigo;

        return $this;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(?string $nombre): static
    {
        $this->nombre = $nombre;

        return $this;
    }

    public function getMail(): ?string
    {
        return $this->mail;
    }

    public function setMail(?string $mail): static
    {
        $this->mail = $mail;

        return $this;
    }

    public function getGrupo(): ?string
    {
        return $this->grupo;
    }

    public function setGrupo(?string $grupo): static
    {
        $this->grupo = $grupo;

        return $this;
    }
}
