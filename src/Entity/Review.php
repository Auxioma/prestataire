<?php

namespace App\Entity;

use App\Repository\ReviewRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReviewRepository::class)]
#[ORM\Table(name: 'review')]
#[ORM\UniqueConstraint(name: 'uniq_review_quote_request', columns: ['quote_request_id'])]
#[ORM\Index(name: 'idx_review_client', columns: ['client_profile_id'])]
#[ORM\Index(name: 'idx_review_prestataire', columns: ['prestataire_profile_id'])]
#[ORM\Index(name: 'idx_review_created_at', columns: ['created_at'])]
#[UniqueEntity(fields: ['quoteRequest'], message: 'Un avis existe déjà pour cette demande.')]
class Review
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ClientProfile $clientProfile = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?PrestataireProfile $prestataireProfile = null;

    #[ORM\OneToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE', unique: true)]
    private ?QuoteRequest $quoteRequest = null;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\NotNull(message: 'Veuillez sélectionner une note.')]
    #[Assert\Range(
        min: 0,
        max: 5,
        notInRangeMessage: 'La note doit être comprise entre {{ min }} et {{ max }}.'
    )]
    private ?int $rating = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 2000,
        maxMessage: 'Votre commentaire ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $comment = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        $quoteRequestTitle = trim((string) ($this->quoteRequest?->getTitle() ?? ''));

        if ('' !== $quoteRequestTitle) {
            return sprintf('Avis - %s', $quoteRequestTitle);
        }

        return sprintf('Avis #%s', $this->id ?? 'n/a');
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getClientProfile(): ?ClientProfile
    {
        return $this->clientProfile;
    }

    public function setClientProfile(?ClientProfile $clientProfile): static
    {
        $this->clientProfile = $clientProfile;

        return $this;
    }

    public function getPrestataireProfile(): ?PrestataireProfile
    {
        return $this->prestataireProfile;
    }

    public function setPrestataireProfile(?PrestataireProfile $prestataireProfile): static
    {
        $this->prestataireProfile = $prestataireProfile;

        return $this;
    }

    public function getQuoteRequest(): ?QuoteRequest
    {
        return $this->quoteRequest;
    }

    public function setQuoteRequest(?QuoteRequest $quoteRequest): static
    {
        $this->quoteRequest = $quoteRequest;

        return $this;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(?int $rating): static
    {
        $this->rating = $rating;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
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
}
