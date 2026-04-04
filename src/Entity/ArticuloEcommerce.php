<?php

namespace App\Entity;

use App\Repository\ArticuloEcommerceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArticuloEcommerceRepository::class, readOnly: true)]
#[ORM\Table(name: 'articulos_ecommerce')]
class ArticuloEcommerce
{
    #[ORM\Id]
    #[ORM\Column(name: 'Codigo_Calipso', length: 50)]
    private string $codigoCalipso;

    #[ORM\Column(name: 'Esquema', length: 30, nullable: true)]
    private ?string $esquema = null;

    #[ORM\Column(name: 'Articulo_IdeaConnector', length: 100, nullable: true)]
    private ?string $articuloIdeaconnector = null;

    #[ORM\Column(name: 'Codigo_IdeaConnector', length: 100, nullable: true)]
    private ?string $codigoIdeaconnector = null;

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

    #[ORM\Column(name: 'ID_BU', length: 20, nullable: true)]
    private ?string $idBu = null;

    #[ORM\Column(name: 'BU', length: 100, nullable: true)]
    private ?string $bu = null;

    #[ORM\Column(name: 'Id_Proveedor', length: 20, nullable: true)]
    private ?string $idProveedor = null;

    #[ORM\Column(name: 'Proveedor', length: 200, nullable: true)]
    private ?string $proveedor = null;

    #[ORM\Column(name: 'Marca', length: 100, nullable: true)]
    private ?string $marca = null;

    public function getCodigoCalipso(): string { return $this->codigoCalipso; }
    public function setCodigoCalipso(string $v): static { $this->codigoCalipso = $v; return $this; }

    public function getEsquema(): ?string { return $this->esquema; }
    public function setEsquema(?string $v): static { $this->esquema = $v; return $this; }

    public function getArticuloIdeaconnector(): ?string { return $this->articuloIdeaconnector; }
    public function setArticuloIdeaconnector(?string $v): static { $this->articuloIdeaconnector = $v; return $this; }

    public function getCodigoIdeaconnector(): ?string { return $this->codigoIdeaconnector; }
    public function setCodigoIdeaconnector(?string $v): static { $this->codigoIdeaconnector = $v; return $this; }

    public function getCodigoRockwell(): ?string { return $this->codigoRockwell; }
    public function setCodigoRockwell(?string $v): static { $this->codigoRockwell = $v; return $this; }

    public function getDescripcion(): ?string { return $this->descripcion; }
    public function setDescripcion(?string $v): static { $this->descripcion = $v; return $this; }

    public function getDescripcionIdeaconector(): ?string { return $this->descripcionIdeaconector; }
    public function setDescripcionIdeaconector(?string $v): static { $this->descripcionIdeaconector = $v; return $this; }

    public function getDescripcionTecnica(): ?string { return $this->descripcionTecnica; }
    public function setDescripcionTecnica(?string $v): static { $this->descripcionTecnica = $v; return $this; }

    public function getImagen(): ?string { return $this->imagen; }
    public function setImagen(?string $v): static { $this->imagen = $v; return $this; }

    public function getSoluciones(): ?string { return $this->soluciones; }
    public function setSoluciones(?string $v): static { $this->soluciones = $v; return $this; }

    public function getCategoriaAdvisor(): ?string { return $this->categoriaAdvisor; }
    public function setCategoriaAdvisor(?string $v): static { $this->categoriaAdvisor = $v; return $this; }

    public function getSubcategoriaAdvisor(): ?string { return $this->subcategoriaAdvisor; }
    public function setSubcategoriaAdvisor(?string $v): static { $this->subcategoriaAdvisor = $v; return $this; }

    public function getIdBu(): ?string { return $this->idBu; }
    public function setIdBu(?string $v): static { $this->idBu = $v; return $this; }

    public function getBu(): ?string { return $this->bu; }
    public function setBu(?string $v): static { $this->bu = $v; return $this; }

    public function getIdProveedor(): ?string { return $this->idProveedor; }
    public function setIdProveedor(?string $v): static { $this->idProveedor = $v; return $this; }

    public function getProveedor(): ?string { return $this->proveedor; }
    public function setProveedor(?string $v): static { $this->proveedor = $v; return $this; }

    public function getMarca(): ?string { return $this->marca; }
    public function setMarca(?string $v): static { $this->marca = $v; return $this; }

    public function getNombreDisplay(): string
    {
        return $this->descripcionIdeaconector ?? $this->descripcion ?? $this->codigoCalipso;
    }
}
