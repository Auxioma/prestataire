<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PrestataireRevenueEntryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PrestataireRevenueEntryRepository::class)]
#[ORM\Table(name: 'prestataire_revenue_entry')]
#[ORM\Index(name: 'idx_prestataire_revenue_entry_prestataire', columns: ['prestataire_id'])]
#[ORM\Index(name: 'idx_prestataire_revenue_entry_issued_at', columns: ['issued_at'])]
#[ORM\Index(name: 'idx_prestataire_revenue_entry_paid_at', columns: ['paid_at'])]
class PrestataireRevenueEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: PrestataireProfile::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?PrestataireProfile $prestataire = null;

    #[ORM\ManyToOne(targetEntity: PrestataireService::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?PrestataireService $prestataireService = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $label = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $serviceLabel = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $clientName = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $invoiceNumber = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $issuedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $paidAt = null;

    #[ORM\Column(type: Types::STRING, length: 3, options: ['default' => 'EUR'])]
    private string $currency = 'EUR';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => '0.00'])]
    private string $subtotalHt = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => '0.00'])]
    private string $taxAmount = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => '0.00'])]
    private string $totalTtc = '0.00';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->issuedAt = new \DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getPrestataire(): ?PrestataireProfile
    {
        return $this->prestataire;
    }

    public function setPrestataire(?PrestataireProfile $prestataire): self
    {
        $this->prestataire = $prestataire;
        $this->touch();

        return $this;
    }

    public function getPrestataireService(): ?PrestataireService
    {
        return $this->prestataireService;
    }

    public function setPrestataireService(?PrestataireService $prestataireService): self
    {
        $this->prestataireService = $prestataireService;
        $this->touch();

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): self
    {
        $this->label = null !== $label ? trim($label) : null;
        $this->touch();

        return $this;
    }

    public function getServiceLabel(): ?string
    {
        return $this->serviceLabel;
    }

    public function setServiceLabel(?string $serviceLabel): self
    {
        $this->serviceLabel = null !== $serviceLabel ? trim($serviceLabel) : null;
        $this->touch();

        return $this;
    }

    public function getClientName(): ?string
    {
        return $this->clientName;
    }

    public function setClientName(?string $clientName): self
    {
        $this->clientName = null !== $clientName ? trim($clientName) : null;
        $this->touch();

        return $this;
    }

    public function getInvoiceNumber(): ?string
    {
        return $this->invoiceNumber;
    }

    public function setInvoiceNumber(?string $invoiceNumber): self
    {
        $this->invoiceNumber = null !== $invoiceNumber ? trim($invoiceNumber) : null;
        $this->touch();

        return $this;
    }

    public function getIssuedAt(): ?\DateTimeImmutable
    {
        return $this->issuedAt;
    }

    public function setIssuedAt(?\DateTimeImmutable $issuedAt): self
    {
        $this->issuedAt = $issuedAt;
        $this->touch();

        return $this;
    }

    public function getPaidAt(): ?\DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function setPaidAt(?\DateTimeImmutable $paidAt): self
    {
        $this->paidAt = $paidAt;
        $this->touch();

        return $this;
    }

    public function isPaid(): bool
    {
        return null !== $this->paidAt;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = trim($currency);
        $this->touch();

        return $this;
    }

    public function getSubtotalHt(): string
    {
        return $this->subtotalHt;
    }

    public function setSubtotalHt(string $subtotalHt): self
    {
        $this->subtotalHt = $subtotalHt;
        $this->touch();

        return $this;
    }

    public function getTaxAmount(): string
    {
        return $this->taxAmount;
    }

    public function setTaxAmount(string $taxAmount): self
    {
        $this->taxAmount = $taxAmount;
        $this->touch();

        return $this;
    }

    public function getTotalTtc(): string
    {
        return $this->totalTtc;
    }

    public function setTotalTtc(string $totalTtc): self
    {
        $this->totalTtc = $totalTtc;
        $this->touch();

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        $this->touch();

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getResolvedServiceLabel(): string
    {
        if ($this->prestataireService instanceof PrestataireService) {
            return $this->prestataireService->getDisplayTitle();
        }

        if (null !== $this->serviceLabel && '' !== trim($this->serviceLabel)) {
            return $this->serviceLabel;
        }

        return 'Autre prestation';
    }

    public function touch(): self
    {
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }
}
