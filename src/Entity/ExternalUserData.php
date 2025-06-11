<?php

namespace App\Entity;

use App\Repository\ExternalUserDataRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExternalUserDataRepository::class)]
class ExternalUserData
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 25)]
    private ?string $phoneNumber = null;

    #[ORM\Column(length: 150)]
    private ?string $jobTitle = null;

    #[ORM\Column(length: 255)]
    private ?string $companyName = null;

    #[ORM\OneToOne(inversedBy: 'externalUserData', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private ?bool $verified = null;

    #[ORM\ManyToOne(inversedBy: 'externalUserData')]
    private ?Sectors $sector = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $sectorExtraData = null;

    #[ORM\Column(length: 60, nullable: true)]
    private ?string $segmento = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $pais = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $provincia = null;

    public function __construct()
    {
        $this->verified = false;
    }
    

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(string $phoneNumber): static
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function getJobTitle(): ?string
    {
        return $this->jobTitle;
    }

    public function setJobTitle(string $jobTitle): static
    {
        $this->jobTitle = $jobTitle;

        return $this;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(string $companyName): static
    {
        $this->companyName = $companyName;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function isVerified(): ?bool
    {
        return $this->verified;
    }

    public function setVerified(bool $verified): static
    {
        $this->verified = $verified;

        return $this;
    }

    public function getSector(): ?Sectors
    {
        return $this->sector;
    }

    public function setSector(?Sectors $sector): static
    {
        $this->sector = $sector;

        return $this;
    }

    public function getSectorExtraData(): ?string
    {
        return $this->sectorExtraData;
    }

    public function setSectorExtraData(?string $sectorExtraData): static
    {
        $this->sectorExtraData = $sectorExtraData;

        return $this;
    }

    public function getSegmento(): ?string
    {
        return $this->segmento;
    }

    public function setSegmento(?string $segmento): static
    {
        $this->segmento = $segmento;

        return $this;
    }

    public function getPais(): ?string
    {
        return $this->pais;
    }

    public function setPais(?string $pais): static
    {
        $this->pais = $pais;

        return $this;
    }

    public function getProvincia(): ?string
    {
        return $this->provincia;
    }

    public function setProvincia(?string $provincia): static
    {
        $this->provincia = $provincia;

        return $this;
    }
}
