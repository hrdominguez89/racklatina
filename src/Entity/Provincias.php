<?php

namespace App\Entity;

use App\Repository\ProvinciasRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'provincias')]
#[ORM\Entity(repositoryClass: ProvinciasRepository::class,readOnly: true)]
class Provincias
{
    
    #[ORM\Column(name: "provincia_id", type: Types::SMALLINT, nullable: true, options: ["default" => NULL])]
    private ?int $provinciaId = NULL;

    #[ORM\Column(name: "provincia_nombre", length: 75, nullable: true)]
    private ?string $provinciaNombre = 'NULL';
    #[ORM\Id]
    #[ORM\Column(name: "pais_id", type: Types::SMALLINT)]
    private ?int $paisId = null;

    public function getProvinciaId(): ?int
    {
        return $this->provinciaId;
    }

    public function setProvinciaId(?int $provinciaId): static
    {
        $this->provinciaId = $provinciaId;

        return $this;
    }

    public function getProvinciaNombre(): ?string
    {
        return $this->provinciaNombre;
    }

    public function setProvinciaNombre(?string $provinciaNombre): static
    {
        $this->provinciaNombre = $provinciaNombre;

        return $this;
    }

    public function getPaisId(): ?int
    {
        return $this->paisId;
    }

    public function setPaisId(int $paisId): static
    {
        $this->paisId = $paisId;

        return $this;
    }
}
