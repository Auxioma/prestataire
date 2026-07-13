<?php

namespace App\Entity\Subscription;

use App\Enum\SubscriptionBillingPeriodEnum;
use App\Enum\SubscriptionPlanStatusEnum;
use App\Repository\Subscription\SubscriptionPlanRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubscriptionPlanRepository::class)]
#[ORM\Table(name: 'subscription_plan')]
#[ORM\UniqueConstraint(name: 'uniq_subscription_plan_code', columns: ['code'])]
#[ORM\UniqueConstraint(name: 'uniq_subscription_plan_monthly_price', columns: ['monthly_stripe_price_id'])]
#[ORM\UniqueConstraint(name: 'uniq_subscription_plan_annual_price', columns: ['annual_stripe_price_id'])]
#[ORM\Index(name: 'idx_subscription_plan_status', columns: ['status'])]
#[ORM\Index(name: 'idx_subscription_plan_sort_order', columns: ['sort_order'])]
class SubscriptionPlan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\Column(length: 80)]
    private ?string $code = null;

    #[ORM\Column(length: 180)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $monthlyAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $annualAmount = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $monthlyStripePriceId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $annualStripePriceId = null;

    #[ORM\Column]
    private int $monthlyCredits = 0;

    #[ORM\Column]
    private int $annualCredits = 0;

    #[ORM\Column(options: ['default' => true])]
    private bool $quoteResponsesEnabled = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $instantMessagingEnabled = true;

    #[ORM\Column(options: ['default' => 0])]
    private int $sortOrder = 0;

    #[ORM\Column(type: 'string', length: 20, enumType: SubscriptionPlanStatusEnum::class)]
    private SubscriptionPlanStatusEnum $status = SubscriptionPlanStatusEnum::DRAFT;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, PrestataireSubscription>
     */
    #[ORM\OneToMany(mappedBy: 'plan', targetEntity: PrestataireSubscription::class)]
    private Collection $subscriptions;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->subscriptions = new ArrayCollection();
    }

    public function __toString(): string
    {
        $name = trim((string) ($this->name ?? ''));
        $code = trim((string) ($this->code ?? ''));

        if ('' !== $name && '' !== $code) {
            return sprintf('%s (%s)', $name, $code);
        }

        if ('' !== $name) {
            return $name;
        }

        return sprintf('Plan #%s', $this->id ?? 'n/a');
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

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

    public function getMonthlyAmount(): ?string
    {
        return $this->monthlyAmount;
    }

    public function setMonthlyAmount(?string $monthlyAmount): static
    {
        $this->monthlyAmount = $monthlyAmount;

        return $this;
    }

    public function getAnnualAmount(): ?string
    {
        return $this->annualAmount;
    }

    public function setAnnualAmount(?string $annualAmount): static
    {
        $this->annualAmount = $annualAmount;

        return $this;
    }

    public function getMonthlyStripePriceId(): ?string
    {
        return $this->monthlyStripePriceId;
    }

    public function setMonthlyStripePriceId(?string $monthlyStripePriceId): static
    {
        $this->monthlyStripePriceId = $monthlyStripePriceId;

        return $this;
    }

    public function getAnnualStripePriceId(): ?string
    {
        return $this->annualStripePriceId;
    }

    public function setAnnualStripePriceId(?string $annualStripePriceId): static
    {
        $this->annualStripePriceId = $annualStripePriceId;

        return $this;
    }

    public function getMonthlyCredits(): int
    {
        return $this->monthlyCredits;
    }

    public function setMonthlyCredits(int $monthlyCredits): static
    {
        $this->monthlyCredits = max(0, $monthlyCredits);

        return $this;
    }

    public function getAnnualCredits(): int
    {
        return $this->annualCredits;
    }

    public function setAnnualCredits(int $annualCredits): static
    {
        $this->annualCredits = max(0, $annualCredits);

        return $this;
    }

    public function isQuoteResponsesEnabled(): bool
    {
        return $this->quoteResponsesEnabled;
    }

    public function setQuoteResponsesEnabled(bool $quoteResponsesEnabled): static
    {
        $this->quoteResponsesEnabled = $quoteResponsesEnabled;

        return $this;
    }

    public function isInstantMessagingEnabled(): bool
    {
        return $this->instantMessagingEnabled;
    }

    public function setInstantMessagingEnabled(bool $instantMessagingEnabled): static
    {
        $this->instantMessagingEnabled = $instantMessagingEnabled;

        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    public function getStatus(): SubscriptionPlanStatusEnum
    {
        return $this->status;
    }

    public function setStatus(SubscriptionPlanStatusEnum $status): static
    {
        $this->status = $status;

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

    public function isActive(): bool
    {
        return SubscriptionPlanStatusEnum::ACTIVE === $this->status;
    }

    public function supportsBillingPeriod(SubscriptionBillingPeriodEnum $billingPeriod): bool
    {
        return null !== $this->getStripePriceIdForPeriod($billingPeriod);
    }

    public function getCreditsForPeriod(SubscriptionBillingPeriodEnum $billingPeriod): int
    {
        return match ($billingPeriod) {
            SubscriptionBillingPeriodEnum::MONTHLY => $this->monthlyCredits,
            SubscriptionBillingPeriodEnum::ANNUAL => $this->annualCredits,
        };
    }

    public function getStripePriceIdForPeriod(SubscriptionBillingPeriodEnum $billingPeriod): ?string
    {
        return match ($billingPeriod) {
            SubscriptionBillingPeriodEnum::MONTHLY => $this->monthlyStripePriceId,
            SubscriptionBillingPeriodEnum::ANNUAL => $this->annualStripePriceId,
        };
    }

    public function getAmountForPeriod(SubscriptionBillingPeriodEnum $billingPeriod): ?string
    {
        return match ($billingPeriod) {
            SubscriptionBillingPeriodEnum::MONTHLY => $this->monthlyAmount,
            SubscriptionBillingPeriodEnum::ANNUAL => $this->annualAmount,
        };
    }

    /**
     * @return Collection<int, PrestataireSubscription>
     */
    public function getSubscriptions(): Collection
    {
        return $this->subscriptions;
    }

    public function addSubscription(PrestataireSubscription $subscription): static
    {
        if (!$this->subscriptions->contains($subscription)) {
            $this->subscriptions->add($subscription);
            $subscription->setPlan($this);
        }

        return $this;
    }

    public function removeSubscription(PrestataireSubscription $subscription): static
    {
        if ($this->subscriptions->removeElement($subscription) && $subscription->getPlan() === $this) {
            $subscription->setPlan(null);
        }

        return $this;
    }
}
