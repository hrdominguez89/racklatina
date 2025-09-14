<?php

namespace App\Entity;

use App\Repository\PaisRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'Pais')]
#[ORM\Entity(repositoryClass: PaisRepository::class,readOnly: true)]
class Pais
{
    #[ORM\Id]
    #[ORM\Column(name: "pais_id", type: Types::SMALLINT)]
    private ?int $paisId;

    #[ORM\Column(name: "pais_nombre", length: 75, nullable: true)]
    private ?string $paisNombre = 'NULL';

    public function getPaisId(): ?int
    {
        return $this->paisId;
    }

    public function setPaisId(?int $paisId): static
    {
        $this->paisId = $paisId;

        return $this;
    }

    public function getPaisNombre(): ?string
    {
        return $this->paisNombre;
    }

    public function setPaisNombre(?string $paisNombre): static
    {
        $this->paisNombre = $paisNombre;

        return $this;
    }
}
