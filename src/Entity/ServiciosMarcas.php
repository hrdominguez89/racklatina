<?php

namespace App\Entity;

use App\Repository\ServiciosMarcasRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'servicios_marcas')]
#[ORM\Entity(repositoryClass: ServiciosMarcasRepository::class,readOnly: true)]
class ServiciosMarcas
{
    #[ORM\Id]
    #[ORM\Column(name: "serviceProdID", nullable: true)]
    private ?int $serviceprodid = NULL;

    #[ORM\Column(name: "serviceProdDescrip", length: 100, nullable: true)]
    private ?string $serviceproddescrip = 'NULL';

    public function getServiceprodid(): ?int
    {
        return $this->serviceprodid;
    }

    public function setServiceprodid(?int $serviceprodid): static
    {
        $this->serviceprodid = $serviceprodid;

        return $this;
    }

    public function getServiceproddescrip(): ?string
    {
        return $this->serviceproddescrip;
    }

    public function setServiceproddescrip(?string $serviceproddescrip): static
    {
        $this->serviceproddescrip = $serviceproddescrip;

        return $this;
    }
}
