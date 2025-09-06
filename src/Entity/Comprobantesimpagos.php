<?php

namespace App\Entity;

use App\Repository\ComprobantesimpagosRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'comprobantesimpagos')]
#[ORM\Entity(repositoryClass: ComprobantesimpagosRepository::class , readOnly: true)]
class Comprobantesimpagos
{
    #[ORM\Column(name: "dbschemaid", length: 20, nullable: true)]
    private ?string $dbschemaid = 'NULL';

    #[ORM\Column(name: "Zona", length: 2, nullable: true)]
    private ?string $zona = 'NULL';
    
    #[ORM\Column(name: "Cliente", length: 22, nullable: true)]
    private ?string $cliente = 'NULL';

    #[ORM\Column(name: "Fecha", type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $fecha = null;

    #[ORM\Column(name: "FechaVencimiento", type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $fechavencimiento = null;

    #[ORM\Column(name: "[D/Imp]", length: 30, nullable: true)]
    private ?string $d_imp = 'NULL';

    #[ORM\Column(name: "[D/Vto]", length: 30, nullable: true)]
    private ?string $d_vto = 'NULL';

    #[ORM\Column(name: "Codigo", length: 2, nullable: true)]
    private ?string $codigo = 'NULL';

    #[ORM\Column(name: "Clase", length: 1, nullable: true)]
    private ?string $clase = 'NULL';

    #[ORM\Column(name: "Comprobante", length: 260, nullable: true)]
    private ?string $comprobante = 'NULL';

    #[ORM\Column(name: "OC", length: 260, nullable: true)]
    private ?string $oc = 'NULL';

    #[ORM\Column(name: "TotalPesos", nullable: true, options: ["default" => NULL])]
    private ?float $totalpesos = NULL;

    #[ORM\Column(name: "PendientePesos", nullable: true, options: ["default" => NULL])]
    private ?float $pendientepesos = NULL;

    #[ORM\Column(name: "TotalDolares", nullable: true, options: ["default" => NULL])]
    private ?float $totaldolares = NULL;

    #[ORM\Column(name: "PendienteDolares", nullable: true, options: ["default" => NULL])]
    private ?float $pendientedolares = NULL;

    #[ORM\Column(name: "Cotizacion")]
    private ?float $cotizacion = null;

    #[ORM\Column(name: "EmitidaEn", length: 20, nullable: true)]
    private ?string $emitidaen = 'NULL';
    #[ORM\Id]
    #[ORM\Column(name: "Observaciones", length: 1)]
    private ?string $observaciones = null;

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

    public function getCliente(): ?string
    {
        return $this->cliente;
    }

    public function setCliente(?string $cliente): static
    {
        $this->cliente = $cliente;

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

    public function getFechavencimiento(): ?\DateTimeInterface
    {
        return $this->fechavencimiento;
    }

    public function setFechavencimiento(?\DateTimeInterface $fechavencimiento): static
    {
        $this->fechavencimiento = $fechavencimiento;

        return $this;
    }

    public function getD_imp(): ?string
    {
        return $this->d_imp;
    }

    public function setD_imp(?string $d_imp): static
    {
        $this->d_imp = $d_imp;

        return $this;
    }

    public function getD_vto(): ?string
    {
        return $this->d_vto;
    }

    public function setD_vto(?string $d_vto): static
    {
        $this->d_vto = $d_vto;

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

    public function getClase(): ?string
    {
        return $this->clase;
    }

    public function setClase(?string $clase): static
    {
        $this->clase = $clase;

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

    public function getOc(): ?string
    {
        return $this->oc;
    }

    public function setOc(?string $oc): static
    {
        $this->oc = $oc;

        return $this;
    }

    public function getTotalpesos(): ?float
    {
        return $this->totalpesos;
    }

    public function setTotalpesos(?float $totalpesos): static
    {
        $this->totalpesos = $totalpesos;

        return $this;
    }

    public function getPendientepesos(): ?float
    {
        return $this->pendientepesos;
    }

    public function setPendientepesos(?float $pendientepesos): static
    {
        $this->pendientepesos = $pendientepesos;

        return $this;
    }

    public function getTotaldolares(): ?float
    {
        return $this->totaldolares;
    }

    public function setTotaldolares(?float $totaldolares): static
    {
        $this->totaldolares = $totaldolares;

        return $this;
    }

    public function getPendientedolares(): ?float
    {
        return $this->pendientedolares;
    }

    public function setPendientedolares(?float $pendientedolares): static
    {
        $this->pendientedolares = $pendientedolares;

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

    public function getEmitidaen(): ?string
    {
        return $this->emitidaen;
    }

    public function setEmitidaen(?string $emitidaen): static
    {
        $this->emitidaen = $emitidaen;

        return $this;
    }

    public function getObservaciones(): ?string
    {
        return $this->observaciones;
    }

    public function setObservaciones(string $observaciones): static
    {
        $this->observaciones = $observaciones;

        return $this;
    }
}
