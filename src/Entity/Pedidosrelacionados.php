<?php

namespace App\Entity;

use App\Repository\PedidosrelacionadosRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'pedidosrelacionados')]
#[ORM\Entity(repositoryClass: PedidosrelacionadosRepository::class, readOnly: true)]
class Pedidosrelacionados
{
    #[ORM\Column(name: "DBSCHEMAID", length: 20, nullable: true)]
    private ?string $dbschemaid = null;

    #[ORM\Column(name: "ITEM", type: Types::BIGINT)]
    private ?string $item = null;

    #[ORM\Column(name: "LinkR", length: 255, nullable: true)]
    private ?string $linkr = null;

    #[ORM\Column(name: "LinkF", length: 255, nullable: true)]
    private ?string $linkf = null;

    #[ORM\Column(name: "ARTICULO", length: 22, nullable: true)]
    private ?string $articulo = null;

    #[ORM\Column(name: "DETALLE", length: 255, nullable: true)]
    private ?string $detalle = null;

    #[ORM\Column(name: "Estado", length: 9, nullable: true)]
    private ?string $estado = null;

    #[ORM\Column(name: "CantidadOriginal", nullable: true)]
    private ?float $cantidadoriginal = NULL;

    #[ORM\Column(name: "PRECIO_DOLAR", nullable: true)]
    private ?float $precioDolar = NULL;

    #[ORM\Column(name: "IMPORTE_DOLAR", nullable: true)]
    private ?float $importeDolar = NULL;

    #[ORM\Column(name: "OrdenCompra", length: 20)]
    private ?string $ordencompra = null;

    #[ORM\Column(name: "CantidadAsignada")]
    private ?float $cantidadasignada = null;

    #[ORM\Column(name: "FECHA_ESTIMADA", length: 255, nullable: true)]
    private ?string $fechaEstimada = null;

    #[ORM\Column(name: "FECHA_BACKLOG", type: Types::TEXT)]
    private ?string $fechaBacklog = null;

    #[ORM\Column(name: "FechaPedido", type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $fechapedido = null;

    #[ORM\Column(name: "FechaCarga", type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $fechacarga = null;

    #[ORM\Column(name: "Codigo", length: 1, nullable: true)]
    private ?string $codigo = null;

    #[ORM\Column(name: "Numero", length: 12, nullable: true)]
    private ?string $numero = null;

    #[ORM\Id]
    #[ORM\Column(name: "Cliente", length: 22)]
    private ?string $cliente = null;

    #[ORM\Column(name: "RazonSocial", length: 100, nullable: true)]
    private ?string $razonsocial = null;

    #[ORM\Column(name: "EstadoCliente", length: 100, nullable: true)]
    private ?string $estadocliente = null;

    #[ORM\Column(name: "Comercial", length: 100, nullable: true)]
    private ?string $comercial = null;

    #[ORM\Column(name: "Usuario", length: 50, nullable: true)]
    private ?string $usuario = null;

    #[ORM\Column(name: "OrdenCompraCliente", length: 100, nullable: true)]
    private ?string $ordencompracliente = null;

    #[ORM\Column(name: "TipoOrden", length: 255, nullable: true)]
    private ?string $tipoorden = null;

    #[ORM\Column(name: "Deposito", length: 100, nullable: true)]
    private ?string $deposito = null;

    #[ORM\Column(name: "TipoPedido", length: 255, nullable: true)]
    private ?string $tipopedido = null;

    #[ORM\Column(name: "KIT", length: 2)]
    private ?string $kit = null;

    #[ORM\Column(name: "transporte", length: 100, nullable: true)]
    private ?string $transporte = null;

    #[ORM\Column(name: "lugar", length: 255, nullable: true)]
    private ?string $lugar = null;

    #[ORM\Column(name: "Autorizado", length: 2)]
    private ?string $autorizado = null;

    #[ORM\Column(name: "cant_ent", nullable: true)]
    private ?float $cantEnt = NULL;

    #[ORM\Column(name: "cumplio", nullable: true)]
    private ?int $cumplio = NULL;

    #[ORM\Column(name: "Preparado")]
    private ?float $preparado = null;

    #[ORM\Column(name: "EstadoInterno", type: Types::TEXT)]
    private ?string $estadointerno = null;

    #[ORM\Column(name: "FechaEntrega", length: 4000, nullable: true)]
    private ?string $fechaentrega = null;

    #[ORM\Column(name: "Remitos", type: Types::TEXT)]
    private ?string $remitos = null;

    #[ORM\Column(name: "Facturas", type: Types::TEXT)]
    private ?string $facturas = null;

    #[ORM\Column(name: "Facturas2", type: Types::TEXT)]
    private ?string $facturas2 = null;

    #[ORM\Column(name: "PAGO", length: 100, nullable: true)]
    private ?string $pago = null;

    #[ORM\Column(name: "COBRADOR", length: 100, nullable: true)]
    private ?string $cobrador = null;

    #[ORM\Column(name: "FECHA_ANTBACKLOG", type: Types::TEXT)]
    private ?string $fechaAntbacklog = null;

    #[ORM\Column(name: "SalesOrder", type: Types::TEXT)]
    private ?string $salesorder = null;

    #[ORM\Column(name: "Recomend", length: 22)]
    private ?string $recomend = null;

    #[ORM\Column(name: "NomRecomend", length: 100)]
    private ?string $nomrecomend = null;

    #[ORM\Column(name: "FechaRemitos", type: Types::TEXT)]
    private ?string $fecharemitos = null;

    #[ORM\Column(name: "FechaEntregaRem", type: Types::TEXT, nullable: true)]
    private ?string $fechaentregarem = null;

    #[ORM\Column(name: "HojaRuta", type: Types::TEXT, nullable: true)]
    private ?string $hojaruta = null;

    public function getDbschemaid(): ?string
    {
        return $this->dbschemaid;
    }

    public function setDbschemaid(?string $dbschemaid): static
    {
        $this->dbschemaid = $dbschemaid;

        return $this;
    }

    public function getItem(): ?string
    {
        return $this->item;
    }

    public function setItem(string $item): static
    {
        $this->item = $item;

        return $this;
    }

    public function getLinkr(): ?string
    {
        return $this->linkr;
    }

    public function setLinkr(?string $linkr): static
    {
        $this->linkr = $linkr;

        return $this;
    }

    public function getLinkf(): ?string
    {
        return $this->linkf;
    }

    public function setLinkf(?string $linkf): static
    {
        $this->linkf = $linkf;

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

    public function getDetalle(): ?string
    {
        return $this->detalle;
    }

    public function setDetalle(?string $detalle): static
    {
        $this->detalle = $detalle;

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

    public function getCantidadoriginal(): ?float
    {
        return $this->cantidadoriginal;
    }

    public function setCantidadoriginal(?float $cantidadoriginal): static
    {
        $this->cantidadoriginal = $cantidadoriginal;

        return $this;
    }

    public function getPrecioDolar(): ?float
    {
        return $this->precioDolar;
    }

    public function setPrecioDolar(?float $precioDolar): static
    {
        $this->precioDolar = $precioDolar;

        return $this;
    }

    public function getImporteDolar(): ?float
    {
        return $this->importeDolar;
    }

    public function setImporteDolar(?float $importeDolar): static
    {
        $this->importeDolar = $importeDolar;

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

    public function getCantidadasignada(): ?float
    {
        return $this->cantidadasignada;
    }

    public function setCantidadasignada(float $cantidadasignada): static
    {
        $this->cantidadasignada = $cantidadasignada;

        return $this;
    }

    public function getFechaEstimada(): ?string
    {
        return $this->fechaEstimada;
    }

    public function setFechaEstimada(?string $fechaEstimada): static
    {
        $this->fechaEstimada = $fechaEstimada;

        return $this;
    }

    public function getFechaBacklog(): ?string
    {
        return $this->fechaBacklog;
    }

    public function setFechaBacklog(string $fechaBacklog): static
    {
        $this->fechaBacklog = $fechaBacklog;

        return $this;
    }

    public function getFechapedido(): ?\DateTimeInterface
    {
        return $this->fechapedido;
    }

    public function setFechapedido(?\DateTimeInterface $fechapedido): static
    {
        $this->fechapedido = $fechapedido;

        return $this;
    }

    public function getFechacarga(): ?\DateTimeInterface
    {
        return $this->fechacarga;
    }

    public function setFechacarga(?\DateTimeInterface $fechacarga): static
    {
        $this->fechacarga = $fechacarga;

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

    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function setNumero(?string $numero): static
    {
        $this->numero = $numero;

        return $this;
    }

    public function getCliente(): ?string
    {
        return $this->cliente;
    }

    public function setCliente(?string $cliente): static
    {
        $this->cliente = $cliente;

        return $this;
    }

    public function getRazonsocial(): ?string
    {
        return $this->razonsocial;
    }

    public function setRazonsocial(?string $razonsocial): static
    {
        $this->razonsocial = $razonsocial;

        return $this;
    }

    public function getEstadocliente(): ?string
    {
        return $this->estadocliente;
    }

    public function setEstadocliente(?string $estadocliente): static
    {
        $this->estadocliente = $estadocliente;

        return $this;
    }

    public function getComercial(): ?string
    {
        return $this->comercial;
    }

    public function setComercial(?string $comercial): static
    {
        $this->comercial = $comercial;

        return $this;
    }

    public function getUsuario(): ?string
    {
        return $this->usuario;
    }

    public function setUsuario(?string $usuario): static
    {
        $this->usuario = $usuario;

        return $this;
    }

    public function getOrdencompracliente(): ?string
    {
        return $this->ordencompracliente;
    }

    public function setOrdencompracliente(?string $ordencompracliente): static
    {
        $this->ordencompracliente = $ordencompracliente;

        return $this;
    }

    public function getTipoorden(): ?string
    {
        return $this->tipoorden;
    }

    public function setTipoorden(?string $tipoorden): static
    {
        $this->tipoorden = $tipoorden;

        return $this;
    }

    public function getDeposito(): ?string
    {
        return $this->deposito;
    }

    public function setDeposito(?string $deposito): static
    {
        $this->deposito = $deposito;

        return $this;
    }

    public function getTipopedido(): ?string
    {
        return $this->tipopedido;
    }

    public function setTipopedido(?string $tipopedido): static
    {
        $this->tipopedido = $tipopedido;

        return $this;
    }

    public function getKit(): ?string
    {
        return $this->kit;
    }

    public function setKit(string $kit): static
    {
        $this->kit = $kit;

        return $this;
    }

    public function getTransporte(): ?string
    {
        return $this->transporte;
    }

    public function setTransporte(?string $transporte): static
    {
        $this->transporte = $transporte;

        return $this;
    }

    public function getLugar(): ?string
    {
        return $this->lugar;
    }

    public function setLugar(?string $lugar): static
    {
        $this->lugar = $lugar;

        return $this;
    }

    public function getAutorizado(): ?string
    {
        return $this->autorizado;
    }

    public function setAutorizado(string $autorizado): static
    {
        $this->autorizado = $autorizado;

        return $this;
    }

    public function getCantEnt(): ?float
    {
        return $this->cantEnt;
    }

    public function setCantEnt(?float $cantEnt): static
    {
        $this->cantEnt = $cantEnt;

        return $this;
    }

    public function getCumplio(): ?int
    {
        return $this->cumplio;
    }

    public function setCumplio(?int $cumplio): static
    {
        $this->cumplio = $cumplio;

        return $this;
    }

    public function getPreparado(): ?float
    {
        return $this->preparado;
    }

    public function setPreparado(float $preparado): static
    {
        $this->preparado = $preparado;

        return $this;
    }

    public function getEstadointerno(): ?string
    {
        return $this->estadointerno;
    }

    public function setEstadointerno(string $estadointerno): static
    {
        $this->estadointerno = $estadointerno;

        return $this;
    }

    public function getFechaentrega(): ?string
    {
        return $this->fechaentrega;
    }

    public function setFechaentrega(?string $fechaentrega): static
    {
        $this->fechaentrega = $fechaentrega;

        return $this;
    }

    public function getRemitos(): ?string
    {
        return $this->remitos;
    }

    public function setRemitos(string $remitos): static
    {
        $this->remitos = $remitos;

        return $this;
    }

    public function getFacturas(): ?string
    {
        return $this->facturas;
    }

    public function setFacturas(string $facturas): static
    {
        $this->facturas = $facturas;

        return $this;
    }

    public function getFacturas2(): ?string
    {
        return $this->facturas2;
    }

    public function setFacturas2(string $facturas2): static
    {
        $this->facturas2 = $facturas2;

        return $this;
    }

    public function getPago(): ?string
    {
        return $this->pago;
    }

    public function setPago(?string $pago): static
    {
        $this->pago = $pago;

        return $this;
    }

    public function getCobrador(): ?string
    {
        return $this->cobrador;
    }

    public function setCobrador(?string $cobrador): static
    {
        $this->cobrador = $cobrador;

        return $this;
    }

    public function getFechaAntbacklog(): ?string
    {
        return $this->fechaAntbacklog;
    }

    public function setFechaAntbacklog(string $fechaAntbacklog): static
    {
        $this->fechaAntbacklog = $fechaAntbacklog;

        return $this;
    }

    public function getSalesorder(): ?string
    {
        return $this->salesorder;
    }

    public function setSalesorder(string $salesorder): static
    {
        $this->salesorder = $salesorder;

        return $this;
    }

    public function getRecomend(): ?string
    {
        return $this->recomend;
    }

    public function setRecomend(string $recomend): static
    {
        $this->recomend = $recomend;

        return $this;
    }

    public function getNomrecomend(): ?string
    {
        return $this->nomrecomend;
    }

    public function setNomrecomend(string $nomrecomend): static
    {
        $this->nomrecomend = $nomrecomend;

        return $this;
    }

    public function getFecharemitos(): ?string
    {
        return $this->fecharemitos;
    }

    public function setFecharemitos(string $fecharemitos): static
    {
        $this->fecharemitos = $fecharemitos;

        return $this;
    }

    public function getFechaentregarem(): ?string
    {
        return $this->fechaentregarem;
    }

    public function setFechaentregarem(?string $fechaentregarem): static
    {
        $this->fechaentregarem = $fechaentregarem;

        return $this;
    }

    public function getHojaruta(): ?string
    {
        return $this->hojaruta;
    }

    public function setHojaruta(?string $hojaruta): static
    {
        $this->hojaruta = $hojaruta;

        return $this;
    }
}
