<?php

namespace App\Entity;

use App\Repository\ProyectoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;

#[ORM\Entity(repositoryClass: ProyectoRepository::class)]
#[ORM\Table(name: 'proyectos')]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false, hardDelete: true)]
class Proyecto
{
    use TimestampableEntity;
    use SoftDeleteableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(name: 'nombre', length: 200)]
    private string $nombre;

    #[ORM\Column(name: 'descripcion', type: 'text', nullable: true)]
    private ?string $descripcion = null;

    #[ORM\Column(name: 'cliente_codigo', length: 22, nullable: true)]
    private ?string $clienteCodigo = null;

    #[ORM\OneToMany(targetEntity: ProyectoItem::class, mappedBy: 'proyecto', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): User { return $this->user; }
    public function setUser(User $user): static { $this->user = $user; return $this; }

    public function getNombre(): string { return $this->nombre; }
    public function setNombre(string $nombre): static { $this->nombre = $nombre; return $this; }

    public function getDescripcion(): ?string { return $this->descripcion; }
    public function setDescripcion(?string $descripcion): static { $this->descripcion = $descripcion; return $this; }

    public function getClienteCodigo(): ?string { return $this->clienteCodigo; }
    public function setClienteCodigo(?string $clienteCodigo): static { $this->clienteCodigo = $clienteCodigo; return $this; }

    public function getItems(): Collection { return $this->items; }

    public function getCantidadProductos(): int { return $this->items->count(); }

    public function addItem(ProyectoItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setProyecto($this);
        }
        return $this;
    }

    public function removeItem(ProyectoItem $item): static
    {
        $this->items->removeElement($item);
        return $this;
    }

    public function tieneArticulo(int $articuloId): bool
    {
        foreach ($this->items as $item) {
            if ($item->getArticulo()->getId() === $articuloId) {
                return true;
            }
        }
        return false;
    }
}
