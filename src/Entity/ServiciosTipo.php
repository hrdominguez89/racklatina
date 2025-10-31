<?php

namespace App\Entity;

use App\Repository\ServiciosTipoRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'Servicios_Tipo')]
#[ORM\Entity(repositoryClass: ServiciosTipoRepository::class,readOnly: true)]
class ServiciosTipo
{
    #[ORM\Id]
    #[ORM\Column(name: "serviceTypeID", nullable: true)]
    private ?int $servicetypeid = NULL;

    #[ORM\Column(name: "serviceTypeDescrip", length: 100, nullable: true)]
    private ?string $servicetypedescrip = 'NULL';

    public function getServicetypeid(): ?int
    {
        return $this->servicetypeid;
    }

    public function setServicetypeid(?int $servicetypeid): static
    {
        $this->servicetypeid = $servicetypeid;

        return $this;
    }

    public function getServicetypedescrip(): ?string
    {
        return $this->servicetypedescrip;
    }

    public function setServicetypedescrip(?string $servicetypedescrip): static
    {
        $this->servicetypedescrip = $servicetypedescrip;

        return $this;
    }
}
