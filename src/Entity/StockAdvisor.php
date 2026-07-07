<?php

namespace App\Entity;

use App\Repository\StockAdvisorRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StockAdvisorRepository::class, readOnly: true)]
#[ORM\Table(name: 'Stock_Advisor')]
class StockAdvisor
{
    #[ORM\Id]
    #[ORM\Column(name: 'Codigo_Calipso', length: 50)]
    private string $codigoCalipso;

    #[ORM\Column(name: 'Esquema', length: 30, nullable: true)]
    private ?string $esquema = null;

    #[ORM\Column(name: 'Articulo', length: 100, nullable: true)]
    private ?string $articulo = null;

    #[ORM\Column(name: 'Codigo_IdeaConnector', length: 100, nullable: true)]
    private ?string $codigoIdeaConnector = null;

    #[ORM\Column(name: 'Codigo_Rockwell', length: 100, nullable: true)]
    private ?string $codigoRockwell = null;

    #[ORM\Column(name: 'Descripcion', length: 500, nullable: true)]
    private ?string $descripcion = null;

    #[ORM\Column(name: 'Descripcion_Ideaconector', length: 500, nullable: true)]
    private ?string $descripcionIdeaconector = null;

    #[ORM\Column(name: 'Descripcion_Tecnica_Ideaconector', type: 'text', nullable: true)]
    private ?string $descripcionTecnica = null;

    #[ORM\Column(name: 'Imagen', length: 1000, nullable: true)]
    private ?string $imagen = null;

    #[ORM\Column(name: 'Soluciones', length: 500, nullable: true)]
    private ?string $soluciones = null;

    #[ORM\Column(name: 'Categoria_Advisor', length: 200, nullable: true)]
    private ?string $categoriaAdvisor = null;

    #[ORM\Column(name: 'SubCategoria_Advisor', length: 200, nullable: true)]
    private ?string $subcategoriaAdvisor = null;

    #[ORM\Column(name: 'Proveedor', length: 200, nullable: true)]
    private ?string $proveedor = null;

    #[ORM\Column(name: 'Marca', length: 100, nullable: true)]
    private ?string $marca = null;

    #[ORM\Column(name: 'Descripcion_Advisor', length: 500, nullable: true)]
    private ?string $descripcionAdvisor = null;

    #[ORM\Column(name: 'Visible_Advisor', length: 10, nullable: true)]
    private ?string $visibleAdvisor = null;

    #[ORM\Column(name: 'Tags', type: 'text', nullable: true)]
    private ?string $tags = null;

    #[ORM\Column(name: 'Stock', type: 'decimal', precision: 10, scale: 4, nullable: true)]
    private ?string $stock = null;

    public function getCodigoCalipso(): string { return $this->codigoCalipso; }

    public function getEsquema(): ?string { return $this->esquema; }

    public function getArticulo(): ?string { return $this->articulo; }

    public function getCodigoIdeaConnector(): ?string { return $this->codigoIdeaConnector; }

    public function getCodigoRockwell(): ?string { return $this->codigoRockwell; }

    public function getDescripcion(): ?string { return $this->descripcion; }

    public function getDescripcionIdeaconector(): ?string { return $this->descripcionIdeaconector; }

    public function getDescripcionTecnica(): ?string { return $this->descripcionTecnica; }

    public function getImagen(): ?string { return $this->imagen; }

    public function getSoluciones(): ?string { return $this->soluciones; }

    public function getCategoriaAdvisor(): ?string { return $this->categoriaAdvisor; }

    public function getSubcategoriaAdvisor(): ?string { return $this->subcategoriaAdvisor; }

    public function getProveedor(): ?string { return $this->proveedor; }

    public function getMarca(): ?string { return $this->marca; }

    public function getDescripcionAdvisor(): ?string { return $this->descripcionAdvisor; }

    public function getVisibleAdvisor(): ?string { return $this->visibleAdvisor; }

    public function getTags(): ?string { return $this->tags; }

    public function getStockNumerico(): float { return (float)($this->stock ?? 0); }

    public function tieneStock(): bool { return $this->getStockNumerico() > 0; }

    public function getStockDisponible(int $cantidadYaEnProyecto): int
    {
        return max(0, (int)floor($this->getStockNumerico()) - $cantidadYaEnProyecto);
    }
}
