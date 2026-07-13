<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\InvoiceSourceTypeEnum;
use App\Enum\InvoiceStatusEnum;
use App\Repository\InvoiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Attribute as Vich;

#[ORM\Entity(repositoryClass: InvoiceRepository::class)]
#[ORM\Table(name: 'invoice')]
#[ORM\UniqueConstraint(name: 'uniq_invoice_quote_proposal', columns: ['quote_proposal_id'])]
#[ORM\UniqueConstraint(name: 'uniq_invoice_prestataire_sequence', columns: ['prestataire_id', 'invoice_sequence_number'])]
#[Vich\Uploadable]
class Invoice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\OneToOne(inversedBy: 'invoice', targetEntity: QuoteProposal::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?QuoteProposal $quoteProposal = null;

    #[ORM\ManyToOne(targetEntity: QuoteRequest::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?QuoteRequest $quoteRequest = null;

    #[ORM\ManyToOne(targetEntity: PrestataireProfile::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?PrestataireProfile $prestataire = null;

    #[ORM\ManyToOne(targetEntity: ClientProfile::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ClientProfile $client = null;

    #[ORM\Column(type: Types::STRING, length: 30, enumType: InvoiceStatusEnum::class)]
    private InvoiceStatusEnum $status = InvoiceStatusEnum::DRAFT;

    #[ORM\Column(type: Types::STRING, length: 40, enumType: InvoiceSourceTypeEnum::class)]
    private InvoiceSourceTypeEnum $sourceType = InvoiceSourceTypeEnum::GENERATED_FROM_QUOTE;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    private ?string $invoiceNumber = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $invoiceSequenceNumber = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $issuedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $dueAt = null;

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

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $terms = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $facturXPdfName = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $facturXXmlName = null;

    #[Vich\UploadableField(
        mapping: 'invoice_external_pdfs',
        fileNameProperty: 'externalPdfName',
        size: 'externalPdfSize',
        mimeType: 'externalPdfMimeType',
        originalName: 'externalPdfOriginalName'
    )]
    private ?File $externalPdfFile = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $externalPdfName = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $externalPdfOriginalName = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $externalPdfMimeType = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $externalPdfSize = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $externalPdfUploadedAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, InvoiceItem>
     */
    #[ORM\OneToMany(mappedBy: 'invoice', targetEntity: InvoiceItem::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    #[Assert\Valid]
    private Collection $items;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->items = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getQuoteProposal(): ?QuoteProposal
    {
        return $this->quoteProposal;
    }

    public function setQuoteProposal(?QuoteProposal $quoteProposal): self
    {
        $this->quoteProposal = $quoteProposal;

        if ($quoteProposal instanceof QuoteProposal && $quoteProposal->getInvoice() !== $this) {
            $quoteProposal->setInvoice($this);
        }

        return $this;
    }

    public function getQuoteRequest(): ?QuoteRequest
    {
        return $this->quoteRequest;
    }

    public function setQuoteRequest(?QuoteRequest $quoteRequest): self
    {
        $this->quoteRequest = $quoteRequest;

        return $this;
    }

    public function getPrestataire(): ?PrestataireProfile
    {
        return $this->prestataire;
    }

    public function setPrestataire(?PrestataireProfile $prestataire): self
    {
        $this->prestataire = $prestataire;

        return $this;
    }

    public function getClient(): ?ClientProfile
    {
        return $this->client;
    }

    public function setClient(?ClientProfile $client): self
    {
        $this->client = $client;

        return $this;
    }

    public function getStatus(): InvoiceStatusEnum
    {
        return $this->status;
    }

    public function setStatus(InvoiceStatusEnum $status): self
    {
        $this->status = $status;
        $this->touch();

        return $this;
    }

    public function getSourceType(): InvoiceSourceTypeEnum
    {
        return $this->sourceType;
    }

    public function setSourceType(InvoiceSourceTypeEnum $sourceType): self
    {
        $this->sourceType = $sourceType;
        $this->touch();

        return $this;
    }

    public function getInvoiceNumber(): ?string
    {
        return $this->invoiceNumber;
    }

    public function setInvoiceNumber(?string $invoiceNumber): self
    {
        $this->invoiceNumber = $invoiceNumber;
        $this->touch();

        return $this;
    }

    public function getInvoiceSequenceNumber(): ?int
    {
        return $this->invoiceSequenceNumber;
    }

    public function setInvoiceSequenceNumber(?int $invoiceSequenceNumber): self
    {
        $this->invoiceSequenceNumber = $invoiceSequenceNumber;
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

    public function getDueAt(): ?\DateTimeImmutable
    {
        return $this->dueAt;
    }

    public function setDueAt(?\DateTimeImmutable $dueAt): self
    {
        $this->dueAt = $dueAt;
        $this->touch();

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;
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

    public function getTerms(): ?string
    {
        return $this->terms;
    }

    public function setTerms(?string $terms): self
    {
        $this->terms = $terms;
        $this->touch();

        return $this;
    }

    public function getFacturXPdfName(): ?string
    {
        return $this->facturXPdfName;
    }

    public function setFacturXPdfName(?string $facturXPdfName): self
    {
        $this->facturXPdfName = $facturXPdfName;

        return $this;
    }

    public function getFacturXXmlName(): ?string
    {
        return $this->facturXXmlName;
    }

    public function setFacturXXmlName(?string $facturXXmlName): self
    {
        $this->facturXXmlName = $facturXXmlName;

        return $this;
    }

    public function getExternalPdfFile(): ?File
    {
        return $this->externalPdfFile;
    }

    public function setExternalPdfFile(?File $externalPdfFile = null): self
    {
        $this->externalPdfFile = $externalPdfFile;

        if ($externalPdfFile instanceof UploadedFile) {
            $this->externalPdfUploadedAt = new \DateTimeImmutable();
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getExternalPdfName(): ?string
    {
        return $this->externalPdfName;
    }

    public function setExternalPdfName(?string $externalPdfName): self
    {
        $this->externalPdfName = $externalPdfName;

        return $this;
    }

    public function getExternalPdfOriginalName(): ?string
    {
        return $this->externalPdfOriginalName;
    }

    public function setExternalPdfOriginalName(?string $externalPdfOriginalName): self
    {
        $this->externalPdfOriginalName = $externalPdfOriginalName;

        return $this;
    }

    public function getExternalPdfMimeType(): ?string
    {
        return $this->externalPdfMimeType;
    }

    public function setExternalPdfMimeType(?string $externalPdfMimeType): self
    {
        $this->externalPdfMimeType = $externalPdfMimeType;

        return $this;
    }

    public function getExternalPdfSize(): ?int
    {
        return $this->externalPdfSize;
    }

    public function setExternalPdfSize(?int $externalPdfSize): self
    {
        $this->externalPdfSize = $externalPdfSize;

        return $this;
    }

    public function getExternalPdfUploadedAt(): ?\DateTimeImmutable
    {
        return $this->externalPdfUploadedAt;
    }

    public function setExternalPdfUploadedAt(?\DateTimeImmutable $externalPdfUploadedAt): self
    {
        $this->externalPdfUploadedAt = $externalPdfUploadedAt;

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

    /**
     * @return Collection<int, InvoiceItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(InvoiceItem $item): self
    {
        if (!$this->items->contains($item)) {
            $maxPosition = 0;

            foreach ($this->items as $existingItem) {
                $maxPosition = max($maxPosition, $existingItem->getPosition() ?? 0);
            }

            if ($item->getPosition() === null) {
                $item->setPosition($maxPosition + 1);
            }

            $this->items->add($item);
            $item->setInvoice($this);
            $this->touch();
        }

        return $this;
    }

    public function removeItem(InvoiceItem $item): self
    {
        if ($this->items->removeElement($item)) {
            if ($item->getInvoice() === $this) {
                $item->setInvoice(null);
            }

            $this->touch();
        }

        return $this;
    }

    public function isDraft(): bool
    {
        return $this->status->isDraft();
    }

    public function isIssued(): bool
    {
        return $this->status->isIssued();
    }

    public function isExternalImport(): bool
    {
        return $this->sourceType->isExternalImport();
    }

    public function hasExternalPdf(): bool
    {
        return $this->externalPdfName !== null && $this->externalPdfName !== '';
    }

    public function hasGeneratedPdf(): bool
    {
        return $this->facturXPdfName !== null && $this->facturXPdfName !== '';
    }

    public function touch(): self
    {
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }
}
