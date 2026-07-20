<?php

namespace App\Entity\Subscription;

use App\Repository\Subscription\StripeWebhookEventRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StripeWebhookEventRepository::class)]
#[ORM\Table(name: 'stripe_webhook_event')]
#[ORM\UniqueConstraint(name: 'uniq_stripe_webhook_event_id', columns: ['stripe_event_id'])]
#[ORM\Index(name: 'idx_stripe_webhook_event_type', columns: ['event_type'])]
class StripeWebhookEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\Column(length: 255)]
    private ?string $stripeEventId = null;

    #[ORM\Column(length: 120)]
    private ?string $eventType = null;

    #[ORM\Column]
    private \DateTimeImmutable $processedAt;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $payload = null;

    public function __construct()
    {
        $this->processedAt = new \DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getStripeEventId(): ?string
    {
        return $this->stripeEventId;
    }

    public function setStripeEventId(string $stripeEventId): static
    {
        $this->stripeEventId = trim($stripeEventId);

        return $this;
    }

    public function getEventType(): ?string
    {
        return $this->eventType;
    }

    public function setEventType(string $eventType): static
    {
        $this->eventType = trim($eventType);

        return $this;
    }

    public function getProcessedAt(): \DateTimeImmutable
    {
        return $this->processedAt;
    }

    public function setProcessedAt(\DateTimeImmutable $processedAt): static
    {
        $this->processedAt = $processedAt;

        return $this;
    }

    public function getPayload(): ?array
    {
        return $this->payload;
    }

    public function setPayload(?array $payload): static
    {
        $this->payload = $payload;

        return $this;
    }
}
