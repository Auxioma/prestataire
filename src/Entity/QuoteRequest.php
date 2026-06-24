<?php

namespace App\Entity;

use App\Enum\QuoteRequestStatusEnum;
use App\Repository\QuoteRequestRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuoteRequestRepository::class)]
#[ORM\Table(name: 'quote_request')]
class QuoteRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\ManyToOne(inversedBy: 'quoteRequests')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ClientProfile $client = null;

    #[ORM\ManyToOne(inversedBy: 'quoteRequests')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?PrestataireProfile $prestataire = null;

    #[ORM\ManyToOne(inversedBy: 'quoteRequests')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?PrestataireService $prestation = null;

    #[ORM\OneToOne(mappedBy: 'quoteRequest', targetEntity: Conversation::class)]
    private ?Conversation $conversation = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 255, unique: true, nullable: true)]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $budgetAmount = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $desiredDate = null;

    #[ORM\Column(length: 50, enumType: QuoteRequestStatusEnum::class)]
    private QuoteRequestStatusEnum $status = QuoteRequestStatusEnum::SUBMITTED;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->status = QuoteRequestStatusEnum::SUBMITTED;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getClient(): ?ClientProfile
    {
        return $this->client;
    }

    public function setClient(?ClientProfile $client): static
    {
        $this->client = $client;

        return $this;
    }

    public function getPrestataire(): ?PrestataireProfile
    {
        return $this->prestataire;
    }

    public function setPrestataire(?PrestataireProfile $prestataire): static
    {
        $this->prestataire = $prestataire;

        return $this;
    }

    public function getPrestation(): ?PrestataireService
    {
        return $this->prestation;
    }

    public function setPrestation(?PrestataireService $prestation): static
    {
        $this->prestation = $prestation;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getBudgetAmount(): ?string
    {
        return $this->budgetAmount;
    }

    public function setBudgetAmount(?string $budgetAmount): static
    {
        $this->budgetAmount = $budgetAmount;

        return $this;
    }

    public function getDesiredDate(): ?\DateTimeInterface
    {
        return $this->desiredDate;
    }

    public function setDesiredDate(?\DateTimeInterface $desiredDate): static
    {
        $this->desiredDate = $desiredDate;

        return $this;
    }

    public function getStatus(): QuoteRequestStatusEnum
    {
        return $this->status;
    }

    public function setStatus(QuoteRequestStatusEnum $status): static
    {
        $this->status = $status;

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

    public function getConversation(): ?Conversation
    {
        return $this->conversation;
    }

    public function setConversation(?Conversation $conversation): static
    {
        if ($conversation === null && $this->conversation !== null) {
            $this->conversation->setQuoteRequest(null);
        }

        if ($conversation !== null && $conversation->getQuoteRequest() !== $this) {
            $conversation->setQuoteRequest($this);
        }

        $this->conversation = $conversation;

        return $this;
    }
}