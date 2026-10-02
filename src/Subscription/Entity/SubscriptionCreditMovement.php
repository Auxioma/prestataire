<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 AUXIOMA Web Agency.
 *
 * Projet : TrouveMoi
 *
 * Tous droits réservés.
 *
 * Ce fichier fait partie du projet TrouveMoi,
 * développé par AUXIOMA Web Agency.
 *
 * Toute reproduction, modification, distribution ou utilisation,
 * totale ou partielle, sans autorisation écrite préalable,
 * est strictement interdite.
 */

namespace App\Subscription\Entity;

use App\Prestataire\Entity\PrestataireProfile;
use App\Quote\Entity\QuoteRequest;
use App\Subscription\Enum\SubscriptionCreditMovementTypeEnum;
use App\Subscription\Repository\SubscriptionCreditMovementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubscriptionCreditMovementRepository::class)]
#[ORM\Table(name: 'subscription_credit_movement')]
// ORM 3 ignore les contraintes imbriquées dans #[ORM\Table] : elles doivent être déclarées au niveau de la classe.
#[ORM\UniqueConstraint(name: 'uniq_subscription_credit_movement_invoice', columns: ['invoice_id'])]
#[ORM\Index(name: 'idx_subscription_credit_movement_profile', columns: ['prestataire_profile_id'])]
#[ORM\Index(name: 'idx_subscription_credit_movement_profile_occurred', columns: ['prestataire_profile_id', 'occurred_at'])]
#[ORM\Index(name: 'idx_subscription_credit_movement_type', columns: ['type'])]
#[ORM\Index(name: 'idx_subscription_credit_movement_occurred_at', columns: ['occurred_at'])]
class SubscriptionCreditMovement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\ManyToOne(inversedBy: 'subscriptionCreditMovements')]
    #[ORM\JoinColumn(name: 'prestataire_profile_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?PrestataireProfile $prestataireProfile = null;

    #[ORM\ManyToOne(inversedBy: 'creditMovements')]
    #[ORM\JoinColumn(name: 'subscription_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?PrestataireSubscription $subscription = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'invoice_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?SubscriptionInvoice $invoice = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'quote_request_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?QuoteRequest $quoteRequest = null;

    #[ORM\Column(type: 'string', length: 40, enumType: SubscriptionCreditMovementTypeEnum::class)]
    private SubscriptionCreditMovementTypeEnum $type = SubscriptionCreditMovementTypeEnum::CORRECTION;

    #[ORM\Column]
    private int $creditsDelta = 0;

    #[ORM\Column(nullable: true)]
    private ?int $balanceAfter = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['jsonb' => true])]
    private ?array $metadata = null;

    #[ORM\Column]
    private \DateTimeImmutable $occurredAt;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->occurredAt = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id;
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

    public function getSubscription(): ?PrestataireSubscription
    {
        return $this->subscription;
    }

    public function setSubscription(?PrestataireSubscription $subscription): static
    {
        $this->subscription = $subscription;

        return $this;
    }

    public function getInvoice(): ?SubscriptionInvoice
    {
        return $this->invoice;
    }

    public function setInvoice(?SubscriptionInvoice $invoice): static
    {
        $this->invoice = $invoice;

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

    public function getType(): SubscriptionCreditMovementTypeEnum
    {
        return $this->type;
    }

    public function setType(SubscriptionCreditMovementTypeEnum $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getCreditsDelta(): int
    {
        return $this->creditsDelta;
    }

    public function setCreditsDelta(int $creditsDelta): static
    {
        $this->creditsDelta = $creditsDelta;

        return $this;
    }

    public function getBalanceAfter(): ?int
    {
        return $this->balanceAfter;
    }

    public function setBalanceAfter(?int $balanceAfter): static
    {
        $this->balanceAfter = $balanceAfter;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): static
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function setOccurredAt(\DateTimeImmutable $occurredAt): static
    {
        $this->occurredAt = $occurredAt;

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

    public function isDebit(): bool
    {
        return $this->creditsDelta < 0;
    }

    public function isCredit(): bool
    {
        return $this->creditsDelta > 0;
    }
}
