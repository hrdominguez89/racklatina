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

    public function getId(): ?int { return $this->id; }

    public function getProyecto(): Proyecto { return $this->proyecto; }
    public function setProyecto(Proyecto $proyecto): static { $this->proyecto = $proyecto; return $this; }

    public function getArticulo(): ArticuloEcommerce { return $this->articulo; }
    public function setArticulo(ArticuloEcommerce $articulo): static { $this->articulo = $articulo; return $this; }

    public function getCantidad(): int { return $this->cantidad; }
    public function setCantidad(int $cantidad): static { $this->cantidad = max(1, $cantidad); return $this; }
}
