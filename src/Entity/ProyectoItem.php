<?php

namespace App\Entity;

use App\Repository\ProyectoItemRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: ProyectoItemRepository::class)]
#[ORM\Table(name: 'proyecto_items')]
#[ORM\UniqueConstraint(name: 'uq_proyecto_articulo', columns: ['proyecto_id', 'articulo_codigo'])]
class ProyectoItem
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Proyecto::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Proyecto $proyecto;

    #[ORM\ManyToOne(targetEntity: ArticuloEcommerce::class)]
    #[ORM\JoinColumn(name: 'articulo_codigo', referencedColumnName: 'Codigo_Calipso', nullable: false)]
    private ArticuloEcommerce $articulo;

    #[ORM\Column(name: 'cantidad', type: 'integer', options: ['default' => 1])]
    private int $cantidad = 1;

    #[ORM\Column(name: 'comment', type: 'text', nullable: true)]
    private ?string $comment = null;

    /** Precio unitario en USD capturado al momento de finalizar el proyecto. Null si no había precio disponible. */
    #[ORM\Column(name: 'precio_unitario_usd', type: 'decimal', precision: 12, scale: 2, nullable: true)]
    private ?string $precioUnitarioUsd = null;

    #[ORM\Column(name: 'reemplazo_precio', type: 'boolean', options: ['default' => false])]
    private bool $reemplazoPrecio = false;

    #[ORM\Column(name: 'reemplazo_plazo', type: 'boolean', options: ['default' => false])]
    private bool $reemplazoPlazo = false;

    public function getId(): ?int { return $this->id; }

    public function getProyecto(): Proyecto { return $this->proyecto; }
    public function setProyecto(Proyecto $proyecto): static { $this->proyecto = $proyecto; return $this; }

    public function getArticulo(): ArticuloEcommerce { return $this->articulo; }
    public function setArticulo(ArticuloEcommerce $articulo): static { $this->articulo = $articulo; return $this; }

    public function getCantidad(): int { return $this->cantidad; }
    public function setCantidad(int $cantidad): static { $this->cantidad = max(1, $cantidad); return $this; }

    public function getComment(): ?string { return $this->comment; }
    public function setComment(?string $comment): static { $this->comment = $comment ?: null; return $this; }

    public function getPrecioUnitarioUsd(): ?float { return $this->precioUnitarioUsd !== null ? (float) $this->precioUnitarioUsd : null; }
    public function setPrecioUnitarioUsd(?float $precio): static { $this->precioUnitarioUsd = $precio !== null ? (string) $precio : null; return $this; }

    public function isReemplazoPrecio(): bool { return $this->reemplazoPrecio; }
    public function setReemplazoPrecio(bool $v): static { $this->reemplazoPrecio = $v; return $this; }

    public function isReemplazoPlazo(): bool { return $this->reemplazoPlazo; }
    public function setReemplazoPlazo(bool $v): static { $this->reemplazoPlazo = $v; return $this; }

    public function getPrecioTotalUsd(): ?float
    {
        return $this->precioUnitarioUsd !== null ? round((float) $this->precioUnitarioUsd * $this->cantidad, 2) : null;
    }
}
