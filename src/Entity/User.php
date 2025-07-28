<?php

namespace App\Entity;

use App\Enum\UserRoleType;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\DBAL\Types\Types;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_NATIONAL_ID_NUMBER', fields: ['nationalIdNumber'])]
#[UniqueEntity(fields: ['email'], message: 'Este email ya está en uso')]
#[UniqueEntity(fields: ['nationalIdNumber'], message: 'Este DNI ya está en uso')]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt', timeAware: false)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    /**
     * @var Collection<int, UserRole>
     */
    #[ORM\OneToMany(targetEntity: UserRole::class, mappedBy: 'user')]
    private Collection $userRoles;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Gedmo\Timestampable]
    public ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Gedmo\Timestampable]
    public ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    public ?\DateTimeImmutable $deletedAt = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 60)]
    private ?string $firstName = null;

    #[ORM\Column(length: 60)]
    private ?string $lastName = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'El DNI es obligatorio')]
    #[Assert\Range(
        min: 1000000,   // mínimo 7 dígitos (1.000.000)
        max: 99999999,  // máximo 8 dígitos (99.999.999)
        notInRangeMessage: 'El DNI debe tener entre 7 y 8 dígitos'
    )]
    private ?int $nationalIdNumber = null;

    #[ORM\Column(type: Types::GUID, nullable: true)]
    private ?string $accountToken = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $accountTokenExpiresAt = null;

    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?ExternalUserData $externalUserData = null;

    #[ORM\Column(type: Types::GUID, nullable: true)]
    private ?string $resetPasswordToken = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $resetPasswordTokenExiresAt = null;

    /**
     * @var Collection<int, CustomerRequest>
     */
    #[ORM\OneToMany(targetEntity: CustomerRequest::class, mappedBy: 'userRequest')]
    private Collection $userRequests;

    /**
     * @var Collection<int, CustomerRequest>
     */
    #[ORM\OneToMany(targetEntity: CustomerRequest::class, mappedBy: 'userUpdate')]
    private Collection $userUpdateRequests;

    /**
     * @var Collection<int, UserCustomer>
     */
    #[ORM\OneToMany(targetEntity: UserCustomer::class, mappedBy: 'user')]
    private Collection $userCustomers;

    public function __construct()
    {
        $this->userRoles = new ArrayCollection();
        $this->userRequests = new ArrayCollection();
        $this->userUpdateRequests = new ArrayCollection();
        $this->userCustomers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->nationalIdNumber;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->userRoles->map(fn(UserRole $userRoles) => $userRoles->getRole()->getName())->toArray();
        // guarantee every user at least has ROLE_USER
        // $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return Collection<int, UserRole>
     */
    public function getUserRoles(): Collection
    {
        return $this->userRoles;
    }

    public function addUserRole(UserRole $userRole): static
    {
        if (!$this->userRoles->contains($userRole)) {
            $this->userRoles->add($userRole);
            $userRole->setUser($this);
        }

        return $this;
    }

    public function removeUserRole(UserRole $userRole): static
    {
        if ($this->userRoles->removeElement($userRole)) {
            // set the owning side to null (unless already changed)
            if ($userRole->getUser() === $this) {
                $userRole->setUser(null);
            }
        }

        return $this;
    }

    public function isInternal(): bool
    {
        foreach ($this->userRoles as $userRole) {
            if ($userRole->getRole()->getType() === UserRoleType::INTERNAL) {
                return true;
            }
        }
        return false;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeImmutable $deletedAt): void
    {
        $this->deletedAt = $deletedAt;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getNationalIdNumber(): ?int
    {
        return $this->nationalIdNumber;
    }

    public function setNationalIdNumber(int $nationalIdNumber): static
    {
        $this->nationalIdNumber = $nationalIdNumber;

        return $this;
    }

    public function getAccountToken(): ?string
    {
        return $this->accountToken;
    }

    public function setAccountToken(?string $accountToken): static
    {
        $this->accountToken = $accountToken;

        return $this;
    }

    public function getAccountTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->accountTokenExpiresAt;
    }

    public function setAccountTokenExpiresAt(?\DateTimeImmutable $accountTokenExpiresAt): static
    {
        $this->accountTokenExpiresAt = $accountTokenExpiresAt;

        return $this;
    }

    public function getExternalUserData(): ?ExternalUserData
    {
        return $this->externalUserData;
    }

    public function setExternalUserData(ExternalUserData $externalUserData): static
    {
        // set the owning side of the relation if necessary
        if ($externalUserData->getUser() !== $this) {
            $externalUserData->setUser($this);
        }

        $this->externalUserData = $externalUserData;

        return $this;
    }

    public function getResetPasswordToken(): ?string
    {
        return $this->resetPasswordToken;
    }

    public function setResetPasswordToken(?string $resetPasswordToken): static
    {
        $this->resetPasswordToken = $resetPasswordToken;

        return $this;
    }

    public function getResetPasswordTokenExiresAt(): ?\DateTimeImmutable
    {
        return $this->resetPasswordTokenExiresAt;
    }

    public function setResetPasswordTokenExiresAt(?\DateTimeImmutable $resetPasswordTokenExiresAt): static
    {
        $this->resetPasswordTokenExiresAt = $resetPasswordTokenExiresAt;

        return $this;
    }

    /**
     * @return Collection<int, CustomerRequest>
     */
    public function getUserRequests(): Collection
    {
        return $this->userRequests;
    }

    public function addUserRequest(CustomerRequest $userRequest): static
    {
        if (!$this->userRequests->contains($userRequest)) {
            $this->userRequests->add($userRequest);
            $userRequest->setUserRequest($this);
        }

        return $this;
    }

    public function removeUserRequest(CustomerRequest $userRequest): static
    {
        if ($this->userRequests->removeElement($userRequest)) {
            // set the owning side to null (unless already changed)
            if ($userRequest->getUserRequest() === $this) {
                $userRequest->setUserRequest(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CustomerRequest>
     */
    public function getUserUpdateRequests(): Collection
    {
        return $this->userUpdateRequests;
    }

    public function addUserUpdateRequest(CustomerRequest $userUpdateRequest): static
    {
        if (!$this->userUpdateRequests->contains($userUpdateRequest)) {
            $this->userUpdateRequests->add($userUpdateRequest);
            $userUpdateRequest->setUserUpdate($this);
        }

        return $this;
    }

    public function removeUserUpdateRequest(CustomerRequest $userUpdateRequest): static
    {
        if ($this->userUpdateRequests->removeElement($userUpdateRequest)) {
            // set the owning side to null (unless already changed)
            if ($userUpdateRequest->getUserUpdate() === $this) {
                $userUpdateRequest->setUserUpdate(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, UserCustomer>
     */
    public function getUserCustomers(): Collection
    {
        return $this->userCustomers;
    }

    public function addUserCustomer(UserCustomer $userCustomer): static
    {
        if (!$this->userCustomers->contains($userCustomer)) {
            $this->userCustomers->add($userCustomer);
            $userCustomer->setUser($this);
        }

        return $this;
    }

    public function removeUserCustomer(UserCustomer $userCustomer): static
    {
        if ($this->userCustomers->removeElement($userCustomer)) {
            // set the owning side to null (unless already changed)
            if ($userCustomer->getUser() === $this) {
                $userCustomer->setUser(null);
            }
        }

        return $this;
    }
}
