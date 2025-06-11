<?php

namespace App\Entity;

use App\Repository\SectorsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SectorsRepository::class)]
class Sectors
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $name = null;

    #[ORM\Column]
    private ?bool $requiresData = null;

    /**
     * @var Collection<int, ExternalUserData>
     */
    #[ORM\OneToMany(targetEntity: ExternalUserData::class, mappedBy: 'sector')]
    private Collection $externalUserData;

    public function __construct()
    {
        $this->externalUserData = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function isRequiresData(): ?bool
    {
        return $this->requiresData;
    }

    public function setRequiresData(bool $requiresData): static
    {
        $this->requiresData = $requiresData;

        return $this;
    }

    /**
     * @return Collection<int, ExternalUserData>
     */
    public function getExternalUserData(): Collection
    {
        return $this->externalUserData;
    }

    public function addExternalUserData(ExternalUserData $externalUserData): static
    {
        if (!$this->externalUserData->contains($externalUserData)) {
            $this->externalUserData->add($externalUserData);
            $externalUserData->setSector($this);
        }

        return $this;
    }

    public function removeExternalUserData(ExternalUserData $externalUserData): static
    {
        if ($this->externalUserData->removeElement($externalUserData)) {
            // set the owning side to null (unless already changed)
            if ($externalUserData->getSector() === $this) {
                $externalUserData->setSector(null);
            }
        }

        return $this;
    }
}
