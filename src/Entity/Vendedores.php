<?php

namespace App\Entity;

use App\Repository\VendedoresRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'vendedores')]
#[ORM\Entity(repositoryClass: VendedoresRepository::class,readOnly: true)]
class Vendedores
{
    #[ORM\Id]
    #[ORM\Column(name: "Codigo_Vendedor", length: 20)]
    #[ORM\GeneratedValue(strategy: "NONE")]
    private ?string $codigoVendedor = null;

    #[ORM\Column(name: "Codigo", length: 255, nullable: true)]
    private ?string $codigo = 'NULL';

    #[ORM\Column(name: "Nombre", length: 100, nullable: true)]
    private ?string $nombre = 'NULL';

    #[ORM\Column(name: "Mail", length: 50, nullable: true)]
    private ?string $mail = 'NULL';

    #[ORM\Column(name: "Grupo", length: 10, nullable: true)]
    private ?string $grupo = 'NULL';

    #[ORM\Column(name: "Sucursal", length: 255, nullable: true)]
    private ?string $sucursal = 'NULL';

    public function getCodigoVendedor(): ?string
    {
        return $this->codigoVendedor;
    }

    public function setCodigoVendedor(string $codigoVendedor): static
    {
        $this->codigoVendedor = $codigoVendedor;

        return $this;
    }

    public function getCodigo(): ?string
    {
        return $this->codigo;
    }

    public function setCodigo(?string $codigo): static
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

    public function getSucursal(): ?string
    {
        return $this->sucursal;
    }

    public function setSucursal(?string $sucursal): static
    {
        $this->sucursal = $sucursal;

        return $this;
    }
}
