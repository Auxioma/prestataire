<?php

namespace App\Entity\Subscription;

use App\Enum\SubscriptionBillingPeriodEnum;
use App\Repository\Subscription\SubscriptionPlanPriceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubscriptionPlanPriceRepository::class)]
#[ORM\Table(name: 'subscription_plan_price')]
#[ORM\UniqueConstraint(name: 'uniq_subscription_plan_price_stripe_price', columns: ['stripe_price_id'])]
#[ORM\Index(name: 'idx_subscription_plan_price_plan_period', columns: ['plan_id', 'billing_period'])]
#[ORM\Index(name: 'idx_subscription_plan_price_active', columns: ['is_active'])]
class SubscriptionPlanPrice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\ManyToOne(inversedBy: 'prices')]
    #[ORM\JoinColumn(name: 'plan_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?SubscriptionPlan $plan = null;

    #[ORM\Column(type: 'string', length: 20, enumType: SubscriptionBillingPeriodEnum::class)]
    private SubscriptionBillingPeriodEnum $billingPeriod = SubscriptionBillingPeriodEnum::MONTHLY;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $label = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $amount = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripePriceId = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(options: ['default' => false])]
    private bool $isPromotional = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $validFrom = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $validUntil = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        $planName = $this->plan?->getName() ?? 'Plan';
        $label = trim((string) ($this->label ?? ''));
        $amount = $this->amount ?? '0.00';

        return sprintf(
            '%s - %s %sEUR%s',
            $planName,
            $this->billingPeriod->getLabel(),
            $amount,
            '' !== $label ? sprintf(' (%s)', $label) : ''
        );
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getPlan(): ?SubscriptionPlan
    {
        return $this->plan;
    }

    public function setPlan(?SubscriptionPlan $plan): static
    {
        $this->plan = $plan;

        return $this;
    }

    public function getBillingPeriod(): SubscriptionBillingPeriodEnum
    {
        return $this->billingPeriod;
    }

    public function setBillingPeriod(SubscriptionBillingPeriodEnum $billingPeriod): static
    {
        $this->billingPeriod = $billingPeriod;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): static
    {
        $this->label = null !== $label ? trim($label) : null;

        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getStripePriceId(): ?string
    {
        return $this->stripePriceId;
    }

    public function setStripePriceId(?string $stripePriceId): static
    {
        $this->stripePriceId = null !== $stripePriceId ? trim($stripePriceId) : null;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function isPromotional(): bool
    {
        return $this->isPromotional;
    }

    public function setIsPromotional(bool $isPromotional): static
    {
        $this->isPromotional = $isPromotional;

        return $this;
    }

    public function getValidFrom(): ?\DateTimeImmutable
    {
        return $this->validFrom;
    }

    public function setValidFrom(?\DateTimeImmutable $validFrom): static
    {
        $this->validFrom = $validFrom;

        return $this;
    }

    public function getValidUntil(): ?\DateTimeImmutable
    {
        return $this->validUntil;
    }

    public function setValidUntil(?\DateTimeImmutable $validUntil): static
    {
        $this->validUntil = $validUntil;

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

    public function isAvailableAt(?\DateTimeImmutable $at = null): bool
    {
        if (!$this->isActive) {
            return false;
        }

        $at ??= new \DateTimeImmutable();

        if (null !== $this->validFrom && $this->validFrom > $at) {
            return false;
        }

        if (null !== $this->validUntil && $this->validUntil < $at) {
            return false;
        }

        return true;
    }

    public function isPaid(): bool
    {
        return (float) ($this->amount ?? 0) > 0;
    }
}
