<?php

namespace App\Entity;

use App\Enum\CustomerRequestStatus;
use App\Enum\CustomerRequestType;
use App\Repository\CustomerRequestRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;


#[Gedmo\SoftDeleteable(fieldName: 'deletedAt')]
#[ORM\Entity(repositoryClass: CustomerRequestRepository::class)]
#[ORM\Index(columns: ['status'])]
#[ORM\Index(columns: ['request_type'])]
class CustomerRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(enumType: CustomerRequestType::class)]
    private ?CustomerRequestType $requestType = null;

    #[ORM\Column(enumType: CustomerRequestStatus::class)]
    private ?CustomerRequestStatus $status = CustomerRequestStatus::PENDIENTE;

    #[ORM\Column]
    private array $data = [];

    #[ORM\ManyToOne(inversedBy: 'userRequests')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $userRequest = null;

    #[ORM\ManyToOne(inversedBy: 'userUpdateRequests')]
    private ?User $userUpdate = null;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $deletedAt = null;

    /**
     * @var Collection<int, UserCustomer>
     */
    #[ORM\OneToMany(targetEntity: UserCustomer::class, mappedBy: 'customerRequest')]
    private Collection $userCustomers;

    public function __construct()
    {
        $this->status = CustomerRequestStatus::PENDIENTE;
        $this->createdAt = new \DateTime();
        $this->userCustomers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRequestType(): ?CustomerRequestType
    {
        return $this->requestType;
    }

    public function setRequestType(CustomerRequestType $requestType): static
    {
        $this->requestType = $requestType;

        return $this;
    }

    public function getStatus(): ?CustomerRequestStatus
    {
        return $this->status;
    }

    public function setStatus(CustomerRequestStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function getUserRequest(): ?User
    {
        return $this->userRequest;
    }

    public function setUserRequest(?User $userRequest): static
    {
        $this->userRequest = $userRequest;

        return $this;
    }

    public function getUserUpdate(): ?User
    {
        return $this->userUpdate;
    }

    public function setUserUpdate(?User $userUpdate): static
    {
        $this->userUpdate = $userUpdate;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeInterface $deletedAt): static
    {
        $this->deletedAt = $deletedAt;

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
            $userCustomer->setCustomerRequest($this);
        }

        return $this;
    }

    public function removeUserCustomer(UserCustomer $userCustomer): static
    {
        if ($this->userCustomers->removeElement($userCustomer)) {
            // set the owning side to null (unless already changed)
            if ($userCustomer->getCustomerRequest() === $this) {
                $userCustomer->setCustomerRequest(null);
            }
        }

        return $this;
    }
}
