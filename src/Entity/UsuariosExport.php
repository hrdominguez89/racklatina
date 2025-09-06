<?php

namespace App\Entity;

use App\Repository\UsuariosExportRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'usuarios_export')]
#[ORM\Entity(repositoryClass: UsuariosExportRepository::class,readOnly: true)]
class UsuariosExport
{
     #[ORM\Id]
    #[ORM\Column(name: "userID", nullable: true)]
    private ?int $userid = NULL;

    #[ORM\Column(name: "userCalipso", length: 50, nullable: true)]
    private ?string $usercalipso = 'NULL';

    #[ORM\Column(name: "userFirstName", length: 50, nullable: true)]
    private ?string $userfirstname = 'NULL';

    #[ORM\Column(name: "userLastName", length: 50, nullable: true)]
    private ?string $userlastname = 'NULL';

    #[ORM\Column(name: "userEmail", length: 50, nullable: true)]
    private ?string $useremail = 'NULL';

    #[ORM\Column(name: "userBranchName", length: 50, nullable: true)]
    private ?string $userbranchname = 'NULL';

    public function getUserid(): ?int
    {
        return $this->userid;
    }

    public function setUserid(?int $userid): static
    {
        $this->userid = $userid;

        return $this;
    }

    public function getUsercalipso(): ?string
    {
        return $this->usercalipso;
    }

    public function setUsercalipso(?string $usercalipso): static
    {
        $this->usercalipso = $usercalipso;

        return $this;
    }

    public function getUserfirstname(): ?string
    {
        return $this->userfirstname;
    }

    public function setUserfirstname(?string $userfirstname): static
    {
        $this->userfirstname = $userfirstname;

        return $this;
    }

    public function getUserlastname(): ?string
    {
        return $this->userlastname;
    }

    public function setUserlastname(?string $userlastname): static
    {
        $this->userlastname = $userlastname;

        return $this;
    }

    public function getUseremail(): ?string
    {
        return $this->useremail;
    }

    public function setUseremail(?string $useremail): static
    {
        $this->useremail = $useremail;

        return $this;
    }

    public function getUserbranchname(): ?string
    {
        return $this->userbranchname;
    }

    public function setUserbranchname(?string $userbranchname): static
    {
        $this->userbranchname = $userbranchname;

        return $this;
    }
}
