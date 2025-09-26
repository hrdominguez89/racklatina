<?php

namespace App\Entity;

use App\Repository\ClientesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'Clientes')]
#[ORM\Entity(repositoryClass: ClientesRepository::class, readOnly: true)]
class Clientes
{
    #[ORM\Column(name: "dbschemaid", length: 20, nullable: true)]
    private ?string $dbschemaid = null;

    #[ORM\Id]
    #[ORM\Column(name: "Codigo_Calipso", length: 22)]
    private ?string $codigoCalipso = null;

    #[ORM\Column(name: "Razon_Social", length: 100, nullable: true)]
    private ?string $razonSocial = null;

    #[ORM\Column(name: "CUIT", length: 20, nullable: true)]
    private ?string $cuit = null;

    #[ORM\Column(name: "Codigo_Estado", length: 1, nullable: true)]
    private ?string $codigoEstado = null;

    #[ORM\Column(name: "Estado_Cliente", length: 100, nullable: true)]
    private ?string $estadoCliente = null;

    #[ORM\Column(name: "Domicilio", length: 100, nullable: true)]
    private ?string $domicilio = null;

    #[ORM\Column(name: "Codigo_Postal", length: 20, nullable: true)]
    private ?string $codigoPostal = null;

    #[ORM\Column(name: "Localidad", length: 100, nullable: true)]
    private ?string $localidad = null;

    #[ORM\Column(name: "Provincia", length: 20, nullable: true)]
    private ?string $provincia = null;

    #[ORM\Column(name: "Telefono", length: 100, nullable: true)]
    private ?string $telefono = null;

    #[ORM\Column(name: "Codigo_Vendedor", length: 255, nullable: true)]
    private ?string $codigoVendedor = null;

    #[ORM\Column(name: "Nombre_Vendedor", length: 128, nullable: true)]
    private ?string $nombreVendedor = null;

    #[ORM\Column(name: "Apellido_Vendedor", length: 128, nullable: true)]
    private ?string $apellidoVendedor = null;

    #[ORM\Column(name: "Email_Vendedor", length: 50, nullable: true)]
    private ?string $emailVendedor = null;
    
    #[ORM\Column(name: "Codigo_Cobrador", length: 20, nullable: true)]
    private ?string $codigocobrador = null;
    
    #[ORM\Column(name: "Nombre_Cobrador", length: 100, nullable: true)]
    private ?string $nombrecobrador = null;
    
    #[ORM\Column(name: "Email_Cobrador", length: 255, nullable: true)]
    private ?string $emailcobrador = null;
    public function getDbschemaid(): ?string
    {
        return $this->dbschemaid;
    }

    public function setDbschemaid(?string $dbschemaid): static
    {
        $this->dbschemaid = $dbschemaid;

        return $this;
    }

    public function getCodigoCalipso(): ?string
    {
        return $this->codigoCalipso;
    }

    public function setCodigoCalipso(?string $codigoCalipso): static
    {
        $this->codigoCalipso = $codigoCalipso;

        return $this;
    }

    public function getRazonSocial(): ?string
    {
        return $this->razonSocial;
    }

    public function setRazonSocial(?string $razonSocial): static
    {
        $this->razonSocial = $razonSocial;

        return $this;
    }

    public function getCuit(): ?string
    {
        return $this->cuit;
    }

    public function setCuit(?string $cuit): static
    {
        $this->cuit = $cuit;

        return $this;
    }

    public function getCodigoEstado(): ?string
    {
        return $this->codigoEstado;
    }

    public function setCodigoEstado(?string $codigoEstado): static
    {
        $this->codigoEstado = $codigoEstado;

        return $this;
    }

    public function getEstadoCliente(): ?string
    {
        return $this->estadoCliente;
    }

    public function setEstadoCliente(?string $estadoCliente): static
    {
        $this->estadoCliente = $estadoCliente;

        return $this;
    }

    public function getDomicilio(): ?string
    {
        return $this->domicilio;
    }

    public function setDomicilio(?string $domicilio): static
    {
        $this->domicilio = $domicilio;

        return $this;
    }

    public function getCodigoPostal(): ?string
    {
        return $this->codigoPostal;
    }

    public function setCodigoPostal(?string $codigoPostal): static
    {
        $this->codigoPostal = $codigoPostal;

        return $this;
    }

    public function getLocalidad(): ?string
    {
        return $this->localidad;
    }

    public function setLocalidad(?string $localidad): static
    {
        $this->localidad = $localidad;

        return $this;
    }

    public function getProvincia(): ?string
    {
        return $this->provincia;
    }

    public function setProvincia(?string $provincia): static
    {
        $this->provincia = $provincia;

        return $this;
    }

    public function getTelefono(): ?string
    {
        return $this->telefono;
    }

    public function setTelefono(?string $telefono): static
    {
        $this->telefono = $telefono;

        return $this;
    }

    public function getCodigoVendedor(): ?string
    {
        return $this->codigoVendedor;
    }

    public function setCodigoVendedor(?string $codigoVendedor): static
    {
        $this->codigoVendedor = $codigoVendedor;

        return $this;
    }

    public function getNombreVendedor(): ?string
    {
        return $this->nombreVendedor;
    }

    public function setNombreVendedor(?string $nombreVendedor): static
    {
        $this->nombreVendedor = $nombreVendedor;

        return $this;
    }

    public function getApellidoVendedor(): ?string
    {
        return $this->apellidoVendedor;
    }

    public function setApellidoVendedor(?string $apellidoVendedor): static
    {
        $this->apellidoVendedor = $apellidoVendedor;

        return $this;
    }

    public function getEmailVendedor(): ?string
    {
        return $this->emailVendedor;
    }

    public function setEmailVendedor(?string $emailVendedor): static
    {
        $this->emailVendedor = $emailVendedor;

        return $this;
    }
    public function getCodigoCobrador()
    {
        return $this->codigocobrador;
    }
    public function getNombreCobrador()
    {
        return $this->nombrecobrador;
    }
    public function getEmailCobrador()
    {
        return $this->emailcobrador;
    }
    public function setCodigoCobrador(string $codigoCobrador)
    {
        $this->codigocobrador = $codigoCobrador;
        return $this;
    }
    public function setNombreCobrador(string $nombrecobrador)
    {
        $this->nombrecobrador = $nombrecobrador;
        return $this;
    }
    public function setEmailCobrador(string $emailcobrador)
    {
        $this->emailcobrador = $emailcobrador;
        return $this;
    }
}
