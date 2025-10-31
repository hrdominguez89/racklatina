<?php

namespace App\Entity;

use App\Repository\SucursalesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'Sucursales')]
#[ORM\Entity(repositoryClass: SucursalesRepository::class,readOnly: true)]
class Sucursales
{
    #[ORM\Id]
    #[ORM\Column(name: "branchID", nullable: true)]
    private ?int $branchid = NULL;

    #[ORM\Column(name: "branchName", length: 50, nullable: true)]
    private ?string $branchname = 'NULL';

    #[ORM\Column(name: "branchAddress", length: 100, nullable: true)]
    private ?string $branchaddress = 'NULL';

    #[ORM\Column(name: "branchCity", length: 50, nullable: true)]
    private ?string $branchcity = 'NULL';

    #[ORM\Column(name: "branchState", length: 50, nullable: true)]
    private ?string $branchstate = 'NULL';

    #[ORM\Column(name: "branchZipCode", length: 10, nullable: true)]
    private ?string $branchzipcode = 'NULL';

    #[ORM\Column(name: "branchCountry", length: 50, nullable: true)]
    private ?string $branchcountry = 'NULL';

    #[ORM\Column(name: "branchPhone", length: 50, nullable: true)]
    private ?string $branchphone = 'NULL';

    #[ORM\Column(name: "branchMail", length: 50, nullable: true)]
    private ?string $branchmail = 'NULL';

    public function getBranchid(): ?int
    {
        return $this->branchid;
    }

    public function setBranchid(?int $branchid): static
    {
        $this->branchid = $branchid;

        return $this;
    }

    public function getBranchname(): ?string
    {
        return $this->branchname;
    }

    public function setBranchname(?string $branchname): static
    {
        $this->branchname = $branchname;

        return $this;
    }

    public function getBranchaddress(): ?string
    {
        return $this->branchaddress;
    }

    public function setBranchaddress(?string $branchaddress): static
    {
        $this->branchaddress = $branchaddress;

        return $this;
    }

    public function getBranchcity(): ?string
    {
        return $this->branchcity;
    }

    public function setBranchcity(?string $branchcity): static
    {
        $this->branchcity = $branchcity;

        return $this;
    }

    public function getBranchstate(): ?string
    {
        return $this->branchstate;
    }

    public function setBranchstate(?string $branchstate): static
    {
        $this->branchstate = $branchstate;

        return $this;
    }

    public function getBranchzipcode(): ?string
    {
        return $this->branchzipcode;
    }

    public function setBranchzipcode(?string $branchzipcode): static
    {
        $this->branchzipcode = $branchzipcode;

        return $this;
    }

    public function getBranchcountry(): ?string
    {
        return $this->branchcountry;
    }

    public function setBranchcountry(?string $branchcountry): static
    {
        $this->branchcountry = $branchcountry;

        return $this;
    }

    public function getBranchphone(): ?string
    {
        return $this->branchphone;
    }

    public function setBranchphone(?string $branchphone): static
    {
        $this->branchphone = $branchphone;

        return $this;
    }

    public function getBranchmail(): ?string
    {
        return $this->branchmail;
    }

    public function setBranchmail(?string $branchmail): static
    {
        $this->branchmail = $branchmail;

        return $this;
    }
}
