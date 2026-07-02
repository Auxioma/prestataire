<?php

namespace App\Entity;

use App\Enum\QuoteProposalStatusEnum;
use App\Repository\QuoteProposalRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: QuoteProposalRepository::class)]
#[ORM\Table(name: 'quote_proposal')]
#[ORM\Index(name: 'idx_quote_proposal_status', columns: ['status'])]
#[ORM\Index(name: 'idx_quote_proposal_created_at', columns: ['created_at'])]
#[ORM\Index(name: 'idx_quote_proposal_deleted_at', columns: ['deleted_at'])]
class QuoteProposal
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: QuoteRequest::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?QuoteRequest $quoteRequest = null;

    #[ORM\ManyToOne(targetEntity: PrestataireProfile::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?PrestataireProfile $prestataire = null;

    #[ORM\ManyToOne(targetEntity: ClientProfile::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ClientProfile $client = null;

    #[ORM\ManyToOne(targetEntity: Conversation::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Conversation $conversation = null;

    #[ORM\Column(type: Types::STRING, length: 30, enumType: QuoteProposalStatusEnum::class)]
    private QuoteProposalStatusEnum $status = QuoteProposalStatusEnum::DRAFT;

    #[ORM\Column(type: Types::STRING, length: 50, unique: true, nullable: true)]
    private ?string $proposalNumber = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $introMessage = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $terms = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $validUntil = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $finalizedAt = null;

    #[ORM\Column(type: Types::STRING, length: 3, options: ['default' => 'EUR'])]
    private ?string $currency = 'EUR';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => '0.00'])]
    private ?string $subtotalHt = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => '0.00'])]
    private ?string $taxAmount = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => '0.00'])]
    private ?string $totalTtc = '0.00';

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $prestataireCompanyName = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $prestataireLegalName = null;

    #[ORM\Column(type: Types::STRING, length: 80, nullable: true)]
    private ?string $prestataireStructureType = null;

    #[ORM\Column(type: Types::STRING, length: 14, nullable: true)]
    private ?string $prestataireSiret = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    private ?string $prestataireVatNumber = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $prestataireAddress = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $prestataireAddressComplement = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    private ?string $prestatairePostalCode = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $prestataireCity = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $prestataireCountry = null;

    #[ORM\Column(type: Types::STRING, length: 30, nullable: true)]
    private ?string $prestatairePhone = null;

    #[ORM\Column(type: Types::STRING, length: 180, nullable: true)]
    private ?string $prestataireEmail = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    private ?string $clientTypeLabel = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $clientFullName = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $clientCompanyName = null;

    #[ORM\Column(type: Types::STRING, length: 14, nullable: true)]
    private ?string $clientSiret = null;

    #[ORM\Column(type: Types::STRING, length: 30, nullable: true)]
    private ?string $clientPhone = null;

    #[ORM\Column(type: Types::STRING, length: 180, nullable: true)]
    private ?string $clientEmail = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $clientBillingAddress = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    private ?string $clientBillingPostalCode = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $clientBillingCity = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $clientBillingCountry = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $clientInterventionAddress = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $clientInterventionAddressComplement = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    private ?string $clientInterventionPostalCode = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $clientInterventionCity = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $clientInterventionCountry = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $deletedAt = null;

    #[ORM\Column(length: 64, unique: true)]
    private ?string $publicReference = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $archivedByPrestataireAt = null;

    /**
     * @var Collection<int, QuoteProposalItem>
     */
    #[ORM\OneToMany(mappedBy: 'quoteProposal', targetEntity: QuoteProposalItem::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    #[Assert\Valid]
    private Collection $items;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->items = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
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

    public function getConversation(): ?Conversation
    {
        return $this->conversation;
    }

    public function setConversation(?Conversation $conversation): self
    {
        $this->conversation = $conversation;

        return $this;
    }

    public function getStatus(): QuoteProposalStatusEnum
    {
        return $this->status;
    }

    public function setStatus(QuoteProposalStatusEnum $status): self
    {
        $this->status = $status;
        $this->touch();

        return $this;
    }

    public function isDraft(): bool
    {
        return $this->status === QuoteProposalStatusEnum::DRAFT;
    }

    public function isFinalized(): bool
    {
        return $this->status === QuoteProposalStatusEnum::FINALIZED;
    }

    public function isDeleted(): bool
    {
        return $this->status === QuoteProposalStatusEnum::DELETED || null !== $this->deletedAt;
    }

    public function getProposalNumber(): ?string
    {
        return $this->proposalNumber;
    }

    public function setProposalNumber(?string $proposalNumber): self
    {
        $this->proposalNumber = $proposalNumber;
        $this->touch();

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;
        $this->touch();

        return $this;
    }

    public function getIntroMessage(): ?string
    {
        return $this->introMessage;
    }

    public function setIntroMessage(?string $introMessage): self
    {
        $this->introMessage = $introMessage;
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

    public function getValidUntil(): ?\DateTimeInterface
    {
        return $this->validUntil;
    }

    public function setValidUntil(?\DateTimeInterface $validUntil): self
    {
        $this->validUntil = $validUntil;
        $this->touch();

        return $this;
    }

    public function getFinalizedAt(): ?\DateTimeInterface
    {
        return $this->finalizedAt;
    }

    public function setFinalizedAt(?\DateTimeInterface $finalizedAt): self
    {
        $this->finalizedAt = $finalizedAt;
        $this->touch();

        return $this;
    }

    public function markAsFinalized(): self
    {
        $this->status = QuoteProposalStatusEnum::FINALIZED;
        $this->finalizedAt = new \DateTime();
        $this->updatedAt = new \DateTime();

        return $this;
    }

    public function markAsDeleted(): self
    {
        $this->status = QuoteProposalStatusEnum::DELETED;
        $this->deletedAt = new \DateTime();
        $this->updatedAt = new \DateTime();

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): self
    {
        $this->currency = $currency;
        $this->touch();

        return $this;
    }

    public function getSubtotalHt(): ?string
    {
        return $this->subtotalHt;
    }

    public function setSubtotalHt(?string $subtotalHt): self
    {
        $this->subtotalHt = $subtotalHt;
        $this->touch();

        return $this;
    }

    public function getTaxAmount(): ?string
    {
        return $this->taxAmount;
    }

    public function setTaxAmount(?string $taxAmount): self
    {
        $this->taxAmount = $taxAmount;
        $this->touch();

        return $this;
    }

    public function getTotalTtc(): ?string
    {
        return $this->totalTtc;
    }

    public function setTotalTtc(?string $totalTtc): self
    {
        $this->totalTtc = $totalTtc;
        $this->touch();

        return $this;
    }

    public function getPrestataireCompanyName(): ?string
    {
        return $this->prestataireCompanyName;
    }

    public function setPrestataireCompanyName(?string $prestataireCompanyName): self
    {
        $this->prestataireCompanyName = $prestataireCompanyName;
        $this->touch();

        return $this;
    }

    public function getPrestataireLegalName(): ?string
    {
        return $this->prestataireLegalName;
    }

    public function setPrestataireLegalName(?string $prestataireLegalName): self
    {
        $this->prestataireLegalName = $prestataireLegalName;
        $this->touch();

        return $this;
    }

    public function getPrestataireStructureType(): ?string
    {
        return $this->prestataireStructureType;
    }

    public function setPrestataireStructureType(?string $prestataireStructureType): self
    {
        $this->prestataireStructureType = $prestataireStructureType;
        $this->touch();

        return $this;
    }

    public function getPrestataireSiret(): ?string
    {
        return $this->prestataireSiret;
    }

    public function setPrestataireSiret(?string $prestataireSiret): self
    {
        $this->prestataireSiret = $prestataireSiret;
        $this->touch();

        return $this;
    }

    public function getPrestataireVatNumber(): ?string
    {
        return $this->prestataireVatNumber;
    }

    public function setPrestataireVatNumber(?string $prestataireVatNumber): self
    {
        $this->prestataireVatNumber = $prestataireVatNumber;
        $this->touch();

        return $this;
    }

    public function getPrestataireAddress(): ?string
    {
        return $this->prestataireAddress;
    }

    public function setPrestataireAddress(?string $prestataireAddress): self
    {
        $this->prestataireAddress = $prestataireAddress;
        $this->touch();

        return $this;
    }

    public function getPrestataireAddressComplement(): ?string
    {
        return $this->prestataireAddressComplement;
    }

    public function setPrestataireAddressComplement(?string $prestataireAddressComplement): self
    {
        $this->prestataireAddressComplement = $prestataireAddressComplement;
        $this->touch();

        return $this;
    }

    public function getPrestatairePostalCode(): ?string
    {
        return $this->prestatairePostalCode;
    }

    public function setPrestatairePostalCode(?string $prestatairePostalCode): self
    {
        $this->prestatairePostalCode = $prestatairePostalCode;
        $this->touch();

        return $this;
    }

    public function getPrestataireCity(): ?string
    {
        return $this->prestataireCity;
    }

    public function setPrestataireCity(?string $prestataireCity): self
    {
        $this->prestataireCity = $prestataireCity;
        $this->touch();

        return $this;
    }

    public function getPrestataireCountry(): ?string
    {
        return $this->prestataireCountry;
    }

    public function setPrestataireCountry(?string $prestataireCountry): self
    {
        $this->prestataireCountry = $prestataireCountry;
        $this->touch();

        return $this;
    }

    public function getPrestatairePhone(): ?string
    {
        return $this->prestatairePhone;
    }

    public function setPrestatairePhone(?string $prestatairePhone): self
    {
        $this->prestatairePhone = $prestatairePhone;
        $this->touch();

        return $this;
    }

    public function getPrestataireEmail(): ?string
    {
        return $this->prestataireEmail;
    }

    public function setPrestataireEmail(?string $prestataireEmail): self
    {
        $this->prestataireEmail = $prestataireEmail;
        $this->touch();

        return $this;
    }

    public function getClientTypeLabel(): ?string
    {
        return $this->clientTypeLabel;
    }

    public function setClientTypeLabel(?string $clientTypeLabel): self
    {
        $this->clientTypeLabel = $clientTypeLabel;
        $this->touch();

        return $this;
    }

    public function getClientFullName(): ?string
    {
        return $this->clientFullName;
    }

    public function setClientFullName(?string $clientFullName): self
    {
        $this->clientFullName = $clientFullName;
        $this->touch();

        return $this;
    }

    public function getClientCompanyName(): ?string
    {
        return $this->clientCompanyName;
    }

    public function setClientCompanyName(?string $clientCompanyName): self
    {
        $this->clientCompanyName = $clientCompanyName;
        $this->touch();

        return $this;
    }

    public function getClientSiret(): ?string
    {
        return $this->clientSiret;
    }

    public function setClientSiret(?string $clientSiret): self
    {
        $this->clientSiret = $clientSiret;
        $this->touch();

        return $this;
    }

    public function getClientPhone(): ?string
    {
        return $this->clientPhone;
    }

    public function setClientPhone(?string $clientPhone): self
    {
        $this->clientPhone = $clientPhone;
        $this->touch();

        return $this;
    }

    public function getClientEmail(): ?string
    {
        return $this->clientEmail;
    }

    public function setClientEmail(?string $clientEmail): self
    {
        $this->clientEmail = $clientEmail;
        $this->touch();

        return $this;
    }

    public function getClientBillingAddress(): ?string
    {
        return $this->clientBillingAddress;
    }

    public function setClientBillingAddress(?string $clientBillingAddress): self
    {
        $this->clientBillingAddress = $clientBillingAddress;
        $this->touch();

        return $this;
    }

    public function getClientBillingPostalCode(): ?string
    {
        return $this->clientBillingPostalCode;
    }

    public function setClientBillingPostalCode(?string $clientBillingPostalCode): self
    {
        $this->clientBillingPostalCode = $clientBillingPostalCode;
        $this->touch();

        return $this;
    }

    public function getClientBillingCity(): ?string
    {
        return $this->clientBillingCity;
    }

    public function setClientBillingCity(?string $clientBillingCity): self
    {
        $this->clientBillingCity = $clientBillingCity;
        $this->touch();

        return $this;
    }

    public function getClientBillingCountry(): ?string
    {
        return $this->clientBillingCountry;
    }

    public function setClientBillingCountry(?string $clientBillingCountry): self
    {
        $this->clientBillingCountry = $clientBillingCountry;
        $this->touch();

        return $this;
    }

    public function getClientInterventionAddress(): ?string
    {
        return $this->clientInterventionAddress;
    }

    public function setClientInterventionAddress(?string $clientInterventionAddress): self
    {
        $this->clientInterventionAddress = $clientInterventionAddress;
        $this->touch();

        return $this;
    }

    public function getClientInterventionAddressComplement(): ?string
    {
        return $this->clientInterventionAddressComplement;
    }

    public function setClientInterventionAddressComplement(?string $clientInterventionAddressComplement): self
    {
        $this->clientInterventionAddressComplement = $clientInterventionAddressComplement;
        $this->touch();

        return $this;
    }

    public function getClientInterventionPostalCode(): ?string
    {
        return $this->clientInterventionPostalCode;
    }

    public function setClientInterventionPostalCode(?string $clientInterventionPostalCode): self
    {
        $this->clientInterventionPostalCode = $clientInterventionPostalCode;
        $this->touch();

        return $this;
    }

    public function getClientInterventionCity(): ?string
    {
        return $this->clientInterventionCity;
    }

    public function setClientInterventionCity(?string $clientInterventionCity): self
    {
        $this->clientInterventionCity = $clientInterventionCity;
        $this->touch();

        return $this;
    }

    public function getClientInterventionCountry(): ?string
    {
        return $this->clientInterventionCountry;
    }

    public function setClientInterventionCountry(?string $clientInterventionCountry): self
    {
        $this->clientInterventionCountry = $clientInterventionCountry;
        $this->touch();

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeInterface $deletedAt): self
    {
        $this->deletedAt = $deletedAt;
        $this->touch();

        return $this;
    }

    public function getPublicReference(): ?string
    {
        return $this->publicReference;
    }

    public function setPublicReference(string $publicReference): self
    {
        $this->publicReference = $publicReference;

        return $this;
    }

    /**
     * @return Collection<int, QuoteProposalItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(QuoteProposalItem $item): self
    {
        if (!$this->items->contains($item)) {
            $maxPosition = 0;

            foreach ($this->items as $existingItem) {
                $maxPosition = max($maxPosition, $existingItem->getPosition() ?? 0);
            }

            if (null === $item->getPosition()) {
                $item->setPosition($maxPosition + 1);
            }

            $this->items->add($item);
            $item->setQuoteProposal($this);
            $this->touch();
        }

        return $this;
    }

    public function removeItem(QuoteProposalItem $item): self
    {
        if ($this->items->removeElement($item)) {
            if ($item->getQuoteProposal() === $this) {
                $item->setQuoteProposal(null);
            }

            $this->touch();
        }

        return $this;
    }

    public function getArchivedByPrestataireAt(): ?\DateTimeImmutable
    {
        return $this->archivedByPrestataireAt;
    }

    public function setArchivedByPrestataireAt(?\DateTimeImmutable $archivedByPrestataireAt): self
    {
        $this->archivedByPrestataireAt = $archivedByPrestataireAt;

        return $this;
    }

    public function isArchivedByPrestataire(): bool
    {
        return null !== $this->archivedByPrestataireAt;
    }

    public function touch(): self
    {
        $this->updatedAt = new \DateTime();

        return $this;
    }
}
