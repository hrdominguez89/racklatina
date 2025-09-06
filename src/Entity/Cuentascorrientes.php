<?php

namespace App\Entity;

use App\Repository\CuentascorrientesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'cuentascorrientes')]
#[ORM\Entity(repositoryClass: CuentascorrientesRepository::class,readOnly: true)]
class Cuentascorrientes
{
    #[ORM\Column(name: "Dbschemaid", length: 20, nullable: true)]
    private ?string $dbschemaid = 'NULL';

    #[ORM\Column(name: "Zona", length: 2, nullable: true)]
    private ?string $zona = 'NULL';

    #[ORM\Column(name: "Codigo_Cliente", length: 22, nullable: true)]
    private ?string $codigoCliente = 'NULL';

    #[ORM\Column(name: "Nombre_Cliente", length: 100, nullable: true)]
    private ?string $nombreCliente = 'NULL';

    #[ORM\Column(name: "Fecha", type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $fecha = null;
    
    #[ORM\Column(name: "Documento", length: 1, nullable: true)]
    private ?string $documento = 'NULL';

    #[ORM\Column(name: "Comprobante", length: 308, nullable: true)]
    private ?string $comprobante = 'NULL';

    #[ORM\Column(name: "Nombre_Comprobante", length: 50, nullable: true)]
    private ?string $nombreComprobante = 'NULL';

    #[ORM\Column(name: "Clase_Comprobante", length: 1)]
    private ?string $claseComprobante = null;

    #[ORM\Column(name: "Numero_Comprobante", length: 255, nullable: true)]
    private ?string $numeroComprobante = 'NULL';
    #[ORM\Id]
    #[ORM\Column(name: "OrdenCompra", length: 100)]
    private ?string $ordencompra = null;

    #[ORM\Column(name: "Estado", length: 9)]
    private ?string $estado = null;

    public function getDbschemaid(): ?string
    {
        return $this->dbschemaid;
    }

    public function setDbschemaid(?string $dbschemaid): static
    {
        $this->dbschemaid = $dbschemaid;

        return $this;
    }

    public function getZona(): ?string
    {
        return $this->zona;
    }

    public function setZona(?string $zona): static
    {
        $this->zona = $zona;

        return $this;
    }

    public function getCodigoCliente(): ?string
    {
        return $this->codigoCliente;
    }

    public function setCodigoCliente(?string $codigoCliente): static
    {
        $this->codigoCliente = $codigoCliente;

        return $this;
    }

    public function getNombreCliente(): ?string
    {
        return $this->nombreCliente;
    }

    public function setNombreCliente(?string $nombreCliente): static
    {
        $this->nombreCliente = $nombreCliente;

        return $this;
    }

    public function getFecha(): ?\DateTimeInterface
    {
        return $this->fecha;
    }

    public function setFecha(?\DateTimeInterface $fecha): static
    {
        $this->fecha = $fecha;

        return $this;
    }

    public function getDocumento(): ?string
    {
        return $this->documento;
    }

    public function setDocumento(?string $documento): static
    {
        $this->documento = $documento;

        return $this;
    }

    public function getComprobante(): ?string
    {
        return $this->comprobante;
    }

    public function setComprobante(?string $comprobante): static
    {
        $this->comprobante = $comprobante;

        return $this;
    }

    public function getNombreComprobante(): ?string
    {
        return $this->nombreComprobante;
    }

    public function setNombreComprobante(?string $nombreComprobante): static
    {
        $this->nombreComprobante = $nombreComprobante;

        return $this;
    }

    public function getClaseComprobante(): ?string
    {
        return $this->claseComprobante;
    }

    public function setClaseComprobante(string $claseComprobante): static
    {
        $this->claseComprobante = $claseComprobante;

        return $this;
    }

    public function getNumeroComprobante(): ?string
    {
        return $this->numeroComprobante;
    }

    public function setNumeroComprobante(?string $numeroComprobante): static
    {
        $this->numeroComprobante = $numeroComprobante;

        return $this;
    }

    public function getOrdencompra(): ?string
    {
        return $this->ordencompra;
    }

    public function setOrdencompra(string $ordencompra): static
    {
        $this->ordencompra = $ordencompra;

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
}
