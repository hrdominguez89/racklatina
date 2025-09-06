<?php

namespace App\Entity;

use App\Repository\RemitosRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'remitos')]
#[ORM\Entity(repositoryClass: RemitosRepository::class, readOnly: true)]
class Remitos
{
    #[ORM\Column(name: "dbschemaid", length: 20, nullable: true)]
    private ?string $dbschemaid = null;

    #[ORM\Column(name: "Fecha", type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $fecha = null;

    #[ORM\Column(name: "Remito", length: 14, nullable: true)]
    private ?string $remito = null;

    #[ORM\Column(name: "Numero", length: 13, nullable: true)]
    private ?string $numero = null;

    #[ORM\Column(name: "Tipo_Comprobante", length: 1, nullable: true)]
    private ?string $tipoComprobante = null;

    #[ORM\Column(name: "Nombre_Comprobante", length: 50, nullable: true)]
    private ?string $nombreComprobante = null;

    #[ORM\Column(name: "Codigo_Articulo", length: 22, nullable: true)]
    private ?string $codigoArticulo = null;

    #[ORM\Column(name: "Nombre_Articulo", length: 255, nullable: true)]
    private ?string $nombreArticulo = null;

    #[ORM\Column(name: "Servicio", length: 1, nullable: true)]
    private ?string $servicio = null;

    #[ORM\Id]
    #[ORM\Column(name: "Codigo_Cliente", length: 22)]
    private ?string $codigoCliente = null;

    #[ORM\Column(name: "Razon_Social", length: 40, nullable: true)]
    private ?string $razonSocial = null;

    #[ORM\Column(name: "Cotizacion")]
    private ?float $cotizacion = null;

    #[ORM\Column(name: "Moneda", length: 100, nullable: true)]
    private ?string $moneda = null;

    #[ORM\Column(name: "Entregado", nullable: true)]
    private ?float $entregado = NULL;

    #[ORM\Column(name: "Facturado", nullable: true)]
    private ?float $facturado = NULL;

    #[ORM\Column(name: "Pendiente", nullable: true)]
    private ?float $pendiente = NULL;

    public function getDbschemaid(): ?string
    {
        return $this->dbschemaid;
    }

    public function setDbschemaid(?string $dbschemaid): static
    {
        $this->dbschemaid = $dbschemaid;

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

    public function getRemito(): ?string
    {
        return $this->remito;
    }

    public function setRemito(?string $remito): static
    {
        $this->remito = $remito;

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

    public function getTipoComprobante(): ?string
    {
        return $this->tipoComprobante;
    }

    public function setTipoComprobante(?string $tipoComprobante): static
    {
        $this->tipoComprobante = $tipoComprobante;

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

    public function getCodigoArticulo(): ?string
    {
        return $this->codigoArticulo;
    }

    public function setCodigoArticulo(?string $codigoArticulo): static
    {
        $this->codigoArticulo = $codigoArticulo;

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

    public function getServicio(): ?string
    {
        return $this->servicio;
    }

    public function setServicio(?string $servicio): static
    {
        $this->servicio = $servicio;

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

    public function getCotizacion(): ?float
    {
        return $this->cotizacion;
    }

    public function setCotizacion(float $cotizacion): static
    {
        $this->cotizacion = $cotizacion;

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

    public function getEntregado(): ?float
    {
        return $this->entregado;
    }

    public function setEntregado(?float $entregado): static
    {
        $this->entregado = $entregado;

        return $this;
    }

    public function getFacturado(): ?float
    {
        return $this->facturado;
    }

    public function setFacturado(?float $facturado): static
    {
        $this->facturado = $facturado;

        return $this;
    }

    public function getPendiente(): ?float
    {
        return $this->pendiente;
    }

    public function setPendiente(?float $pendiente): static
    {
        $this->pendiente = $pendiente;

        return $this;
    }
}
