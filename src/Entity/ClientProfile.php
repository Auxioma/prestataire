<?php

namespace App\Entity;

use App\Enum\ClientTypeEnum;
use App\Repository\ClientProfileRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClientProfileRepository::class)]
#[ORM\Table(name: 'client_profile')]
#[ORM\Index(name: 'idx_client_user', columns: ['user_id'])]
class ClientProfile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\Column(type: 'string', length: 50, enumType: \App\Enum\ClientTypeEnum::class)]
    private ?\App\Enum\ClientTypeEnum $type = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $companyName = null;

    #[ORM\Column(length: 14, nullable: true)]
    private ?string $siret = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $billingAddress = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $billingPostalCode = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $billingCity = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $billingCountry = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $defaultAddress = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $defaultPostalCode = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $defaultCity = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7, nullable: true)]
    private ?string $latitude = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7, nullable: true)]
    private ?string $longitude = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToOne(inversedBy: 'clientProfile', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, columnDefinition: 'BIGINT NOT NULL')]
    private ?User $account = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getType(): ?ClientTypeEnum
    {
        return $this->type;
    }

    public function setType(ClientTypeEnum $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(?string $companyName): static
    {
        $this->companyName = $companyName;

        return $this;
    }

    public function getSiret(): ?string
    {
        return $this->siret;
    }

    public function setSiret(?string $siret): static
    {
        $this->siret = $siret;

        return $this;
    }

    public function getBillingAddress(): ?string
    {
        return $this->billingAddress;
    }

    public function setBillingAddress(?string $billingAddress): static
    {
        $this->billingAddress = $billingAddress;

        return $this;
    }

    public function getBillingPostalCode(): ?string
    {
        return $this->billingPostalCode;
    }

    public function setBillingPostalCode(?string $billingPostalCode): static
    {
        $this->billingPostalCode = $billingPostalCode;

        return $this;
    }

    public function getBillingCity(): ?string
    {
        return $this->billingCity;
    }

    public function setBillingCity(?string $billingCity): static
    {
        $this->billingCity = $billingCity;

        return $this;
    }

    public function getBillingCountry(): ?string
    {
        return $this->billingCountry;
    }

    public function setBillingCountry(?string $billingCountry): static
    {
        $this->billingCountry = $billingCountry;

        return $this;
    }

    public function getDefaultAddress(): ?string
    {
        return $this->defaultAddress;
    }

    public function setDefaultAddress(?string $defaultAddress): static
    {
        $this->defaultAddress = $defaultAddress;

        return $this;
    }

    public function getDefaultPostalCode(): ?string
    {
        return $this->defaultPostalCode;
    }

    public function setDefaultPostalCode(?string $defaultPostalCode): static
    {
        $this->defaultPostalCode = $defaultPostalCode;

        return $this;
    }

    public function getDefaultCity(): ?string
    {
        return $this->defaultCity;
    }

    public function setDefaultCity(?string $defaultCity): static
    {
        $this->defaultCity = $defaultCity;

        return $this;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(?string $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(?string $longitude): static
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getAccount(): ?User
    {
        return $this->account;
    }

    public function setAccount(User $account): static
    {
        $this->account = $account;

        return $this;
    }

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }
}
