<?php

namespace App\Entity;

use App\Repository\ServiceRequestsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ServiceRequestsRepository::class)]
class ServiceRequests
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // === DATOS PRINCIPALES ===

    #[ORM\ManyToOne(targetEntity: Pais::class)]
    #[ORM\JoinColumn(name: 'pais_id', referencedColumnName: 'pais_id', nullable: false)]
    private ?Pais $pais = null;

    #[ORM\ManyToOne(targetEntity: Provincias::class)]
    #[ORM\JoinColumn(name: 'provincia_id', referencedColumnName: 'provincia_id', nullable: false)]
    private ?Provincias $provincia = null;

    #[ORM\Column(length: 255)]
    private ?string $localidad = null;

    #[ORM\Column(length: 255)]
    private ?string $empresa = null;

    #[ORM\Column(length: 255)]
    private ?string $contacto = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 500)]
    private ?string $direccion = null;

    #[ORM\Column(length: 255)]
    private ?string $transporteNombre = null;

    // === INFORMACIÓN DEL MATERIAL ===

    #[ORM\ManyToOne(targetEntity: ServiciosMarcas::class)]
    #[ORM\JoinColumn(name: 'marca_id', referencedColumnName: 'serviceProdID', nullable: false)]
    private ?ServiciosMarcas $marca = null;

    #[ORM\Column(length: 255)]
    private ?string $codCatalogo = null;

    #[ORM\Column(length: 255)]
    private ?string $nroSerie = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $falla = null;

    #[ORM\Column]
    private ?bool $adquiridoUltimos12Meses = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $facturaCompraFilename = null;

    // === CAMPOS DE GESTIÓN ===

    #[ORM\Column(length: 50)]
    private ?string $estado = 'pendiente';

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private ?User $user = null;

    // === GETTERS Y SETTERS ===

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPais(): ?Pais
    {
        return $this->pais;
    }

    public function setPais(?Pais $pais): static
    {
        $this->pais = $pais;
        return $this;
    }

    public function getProvincia(): ?Provincias
    {
        return $this->provincia;
    }

    public function setProvincia(?Provincias $provincia): static
    {
        $this->provincia = $provincia;
        return $this;
    }

    public function getLocalidad(): ?string
    {
        return $this->localidad;
    }

    public function setLocalidad(string $localidad): static
    {
        $this->localidad = $localidad;
        return $this;
    }

    public function getEmpresa(): ?string
    {
        return $this->empresa;
    }

    public function setEmpresa(string $empresa): static
    {
        $this->empresa = $empresa;
        return $this;
    }

    public function getContacto(): ?string
    {
        return $this->contacto;
    }

    public function setContacto(string $contacto): static
    {
        $this->contacto = $contacto;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getDireccion(): ?string
    {
        return $this->direccion;
    }

    public function setDireccion(string $direccion): static
    {
        $this->direccion = $direccion;
        return $this;
    }

    public function getTransporteNombre(): ?string
    {
        return $this->transporteNombre;
    }

    public function setTransporteNombre(string $transporteNombre): static
    {
        $this->transporteNombre = $transporteNombre;
        return $this;
    }

    public function getMarca(): ?ServiciosMarcas
    {
        return $this->marca;
    }

    public function setMarca(?ServiciosMarcas $marca): static
    {
        $this->marca = $marca;
        return $this;
    }

    public function getCodCatalogo(): ?string
    {
        return $this->codCatalogo;
    }

    public function setCodCatalogo(string $codCatalogo): static
    {
        $this->codCatalogo = $codCatalogo;
        return $this;
    }

    public function getNroSerie(): ?string
    {
        return $this->nroSerie;
    }

    public function setNroSerie(string $nroSerie): static
    {
        $this->nroSerie = $nroSerie;
        return $this;
    }

    public function getFalla(): ?string
    {
        return $this->falla;
    }

    public function setFalla(string $falla): static
    {
        $this->falla = $falla;
        return $this;
    }

    public function isAdquiridoUltimos12Meses(): ?bool
    {
        return $this->adquiridoUltimos12Meses;
    }

    public function setAdquiridoUltimos12Meses(bool $adquiridoUltimos12Meses): static
    {
        $this->adquiridoUltimos12Meses = $adquiridoUltimos12Meses;
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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getFacturaCompraFilename(): ?string
    {
        return $this->facturaCompraFilename;
    }

    public function setFacturaCompraFilename(?string $facturaCompraFilename): static
    {
        $this->facturaCompraFilename = $facturaCompraFilename;
        return $this;
    }
}
