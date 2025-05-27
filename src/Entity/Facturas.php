<?php

namespace App\Entity;

use App\Repository\FacturasRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'facturas')]
#[ORM\Entity(repositoryClass: FacturasRepository::class, readOnly: true)]
class Facturas
{
    #[ORM\Column(name: "dbschemaid", length: 20, nullable: true)]
    private ?string $dbschemaid = null;

    #[ORM\Column(name: "Numero", length: 255, nullable: true)]
    private ?string $numero = null;

    #[ORM\Column(name: "Comprobante", length: 257, nullable: true)]
    private ?string $comprobante = null;

    #[ORM\Column(name: "Nombre_Comprobante", length: 50, nullable: true)]
    private ?string $nombreComprobante = null;

    #[ORM\Id]
    #[ORM\Column(name: "Codigo_Cliente", length: 50)]
    private ?string $codigoCliente = null;

    #[ORM\Column(name: "Razon_Social", length: 100, nullable: true)]
    private ?string $razonSocial = null;

    #[ORM\Column(name: "Cotizacion", length: 50, nullable: true)]
    private ?string $cotizacion = null;

    #[ORM\Column(name: "Fecha", type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $fecha = null;

    #[ORM\Column(name: "Estado", length: 50, nullable: true)]
    private ?string $estado = null;

    #[ORM\Column(name: "Articulo", length: 50, nullable: true)]
    private ?string $articulo = null;

    #[ORM\Column(name: "Nombre_Articulo", length: 255, nullable: true)]
    private ?string $nombreArticulo = null;

    #[ORM\Column(name: "Moneda", length: 100, nullable: true)]
    private ?string $moneda = null;

    #[ORM\Column(name: "Cantidad", nullable: true)]
    private ?float $cantidad = NULL;

    #[ORM\Column(name: "Precio", nullable: true)]
    private ?float $precio = NULL;

    #[ORM\Column(name: "Precio_Cotizado", nullable: true)]
    private ?float $precioCotizado = NULL;

    #[ORM\Column(name: "ImporteNeto", nullable: true)]
    private ?float $importeneto = NULL;

    #[ORM\Column(name: "ImporteNeto_Cotizado", nullable: true)]
    private ?float $importenetoCotizado = NULL;

    public function getDbschemaid(): ?string
    {
        return $this->dbschemaid;
    }

    public function setDbschemaid(?string $dbschemaid): static
    {
        $this->dbschemaid = $dbschemaid;

        return $this;
    }

    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function setNumero(?string $numero): static
    {
        $this->numero = $numero;

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

    public function getCodigoCliente(): ?string
    {
        return $this->codigoCliente;
    }

    public function setCodigoCliente(?string $codigoCliente): static
    {
        $this->codigoCliente = $codigoCliente;

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

    public function getCotizacion(): ?string
    {
        return $this->cotizacion;
    }

    public function setCotizacion(?string $cotizacion): static
    {
        $this->cotizacion = $cotizacion;

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

    public function getEstado(): ?string
    {
        return $this->estado;
    }

    public function setEstado(?string $estado): static
    {
        $this->estado = $estado;

        return $this;
    }

    public function getArticulo(): ?string
    {
        return $this->articulo;
    }

    public function setArticulo(?string $articulo): static
    {
        $this->articulo = $articulo;

        return $this;
    }

    public function getNombreArticulo(): ?string
    {
        return $this->nombreArticulo;
    }

    public function setNombreArticulo(?string $nombreArticulo): static
    {
        $this->nombreArticulo = $nombreArticulo;

        return $this;
    }

    public function getMoneda(): ?string
    {
        return $this->moneda;
    }

    public function setMoneda(?string $moneda): static
    {
        $this->moneda = $moneda;

        return $this;
    }

    public function getCantidad(): ?float
    {
        return $this->cantidad;
    }

    public function setCantidad(?float $cantidad): static
    {
        $this->cantidad = $cantidad;

        return $this;
    }

    public function getPrecio(): ?float
    {
        return $this->precio;
    }

    public function setPrecio(?float $precio): static
    {
        $this->precio = $precio;

        return $this;
    }

    public function getPrecioCotizado(): ?float
    {
        return $this->precioCotizado;
    }

    public function setPrecioCotizado(?float $precioCotizado): static
    {
        $this->precioCotizado = $precioCotizado;

        return $this;
    }

    public function getImporteneto(): ?float
    {
        return $this->importeneto;
    }

    public function setImporteneto(?float $importeneto): static
    {
        $this->importeneto = $importeneto;

        return $this;
    }

    public function getImportenetoCotizado(): ?float
    {
        return $this->importenetoCotizado;
    }

    public function setImportenetoCotizado(?float $importenetoCotizado): static
    {
        $this->importenetoCotizado = $importenetoCotizado;

        return $this;
    }
}
