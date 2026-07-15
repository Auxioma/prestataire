<?php

namespace App\Entity\Subscription;

use App\Entity\PrestataireProfile;
use App\Enum\SubscriptionBillingPeriodEnum;
use App\Enum\SubscriptionStatusEnum;
use App\Repository\Subscription\PrestataireSubscriptionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PrestataireSubscriptionRepository::class)]
#[ORM\Table(name: 'prestataire_subscription')]
#[ORM\UniqueConstraint(name: 'uniq_prestataire_subscription_stripe_subscription', columns: ['stripe_subscription_id'])]
#[ORM\Index(name: 'idx_prestataire_subscription_profile', columns: ['prestataire_profile_id'])]
#[ORM\Index(name: 'idx_prestataire_subscription_status', columns: ['status'])]
#[ORM\Index(name: 'idx_prestataire_subscription_period_end', columns: ['current_period_end'])]
class PrestataireSubscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\ManyToOne(inversedBy: 'subscriptions')]
    #[ORM\JoinColumn(name: 'prestataire_profile_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?PrestataireProfile $prestataireProfile = null;

    #[ORM\ManyToOne(inversedBy: 'subscriptions')]
    #[ORM\JoinColumn(name: 'customer_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?SubscriptionCustomer $customer = null;

    #[ORM\ManyToOne(inversedBy: 'subscriptions')]
    #[ORM\JoinColumn(name: 'plan_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?SubscriptionPlan $plan = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'plan_price_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?SubscriptionPlanPrice $planPrice = null;

    #[ORM\Column(type: 'string', length: 20, enumType: SubscriptionBillingPeriodEnum::class)]
    private SubscriptionBillingPeriodEnum $billingPeriod = SubscriptionBillingPeriodEnum::MONTHLY;

    #[ORM\Column(type: 'string', length: 30, enumType: SubscriptionStatusEnum::class)]
    private SubscriptionStatusEnum $status = SubscriptionStatusEnum::INCOMPLETE;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripeSubscriptionId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripePriceId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripeSubscriptionItemId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripeScheduleId = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $currentPeriodStart = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $currentPeriodEnd = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $trialStartsAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $trialEndsAt = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $cancelAtPeriodEnd = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $cancellationRequestedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $canceledAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $endedAt = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $creditsGrantedCurrentPeriod = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $creditsConsumedCurrentPeriod = 0;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, SubscriptionInvoice>
     */
    #[ORM\OneToMany(mappedBy: 'subscription', targetEntity: SubscriptionInvoice::class, cascade: ['persist'])]
    private Collection $invoices;

    /**
     * @var Collection<int, SubscriptionCreditMovement>
     */
    #[ORM\OneToMany(mappedBy: 'subscription', targetEntity: SubscriptionCreditMovement::class, cascade: ['persist'])]
    private Collection $creditMovements;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->invoices = new ArrayCollection();
        $this->creditMovements = new ArrayCollection();
    }

    public function __toString(): string
    {
        $prestataire = $this->prestataireProfile?->__toString();
        $plan = $this->plan?->__toString();

        if (null !== $prestataire && null !== $plan) {
            return sprintf('%s - %s', $prestataire, $plan);
        }

        if (null !== $prestataire) {
            return $prestataire;
        }

        return sprintf('Souscription #%s', $this->id ?? 'n/a');
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

    public function getCustomer(): ?SubscriptionCustomer
    {
        return $this->customer;
    }

    public function setCustomer(?SubscriptionCustomer $customer): static
    {
        $this->customer = $customer;

        return $this;
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

    public function getPlanPrice(): ?SubscriptionPlanPrice
    {
        return $this->planPrice;
    }

    public function setPlanPrice(?SubscriptionPlanPrice $planPrice): static
    {
        $this->planPrice = $planPrice;

        if ($planPrice instanceof SubscriptionPlanPrice) {
            $this->plan = $planPrice->getPlan();
            $this->billingPeriod = $planPrice->getBillingPeriod();
            $this->stripePriceId = $planPrice->getStripePriceId();
        }

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

    public function getStatus(): SubscriptionStatusEnum
    {
        return $this->status;
    }

    public function setStatus(SubscriptionStatusEnum $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getStripeSubscriptionId(): ?string
    {
        return $this->stripeSubscriptionId;
    }

    public function setStripeSubscriptionId(?string $stripeSubscriptionId): static
    {
        $this->stripeSubscriptionId = $stripeSubscriptionId;

        return $this;
    }

    public function getStripePriceId(): ?string
    {
        return $this->stripePriceId;
    }

    public function setStripePriceId(?string $stripePriceId): static
    {
        $this->stripePriceId = $stripePriceId;

        return $this;
    }

    public function getStripeSubscriptionItemId(): ?string
    {
        return $this->stripeSubscriptionItemId;
    }

    public function setStripeSubscriptionItemId(?string $stripeSubscriptionItemId): static
    {
        $this->stripeSubscriptionItemId = $stripeSubscriptionItemId;

        return $this;
    }

    public function getStripeScheduleId(): ?string
    {
        return $this->stripeScheduleId;
    }

    public function setStripeScheduleId(?string $stripeScheduleId): static
    {
        $this->stripeScheduleId = $stripeScheduleId;

        return $this;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTimeImmutable $startedAt): static
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getCurrentPeriodStart(): ?\DateTimeImmutable
    {
        return $this->currentPeriodStart;
    }

    public function setCurrentPeriodStart(?\DateTimeImmutable $currentPeriodStart): static
    {
        $this->currentPeriodStart = $currentPeriodStart;

        return $this;
    }

    public function getCurrentPeriodEnd(): ?\DateTimeImmutable
    {
        return $this->currentPeriodEnd;
    }

    public function setCurrentPeriodEnd(?\DateTimeImmutable $currentPeriodEnd): static
    {
        $this->currentPeriodEnd = $currentPeriodEnd;

        return $this;
    }

    public function getTrialStartsAt(): ?\DateTimeImmutable
    {
        return $this->trialStartsAt;
    }

    public function setTrialStartsAt(?\DateTimeImmutable $trialStartsAt): static
    {
        $this->trialStartsAt = $trialStartsAt;

        return $this;
    }

    public function getTrialEndsAt(): ?\DateTimeImmutable
    {
        return $this->trialEndsAt;
    }

    public function setTrialEndsAt(?\DateTimeImmutable $trialEndsAt): static
    {
        $this->trialEndsAt = $trialEndsAt;

        return $this;
    }

    public function isCancelAtPeriodEnd(): bool
    {
        return $this->cancelAtPeriodEnd;
    }

    public function setCancelAtPeriodEnd(bool $cancelAtPeriodEnd): static
    {
        $this->cancelAtPeriodEnd = $cancelAtPeriodEnd;

        return $this;
    }

    public function getCancellationRequestedAt(): ?\DateTimeImmutable
    {
        return $this->cancellationRequestedAt;
    }

    public function setCancellationRequestedAt(?\DateTimeImmutable $cancellationRequestedAt): static
    {
        $this->cancellationRequestedAt = $cancellationRequestedAt;

        return $this;
    }

    public function getCanceledAt(): ?\DateTimeImmutable
    {
        return $this->canceledAt;
    }

    public function setCanceledAt(?\DateTimeImmutable $canceledAt): static
    {
        $this->canceledAt = $canceledAt;

        return $this;
    }

    public function getEndedAt(): ?\DateTimeImmutable
    {
        return $this->endedAt;
    }

    public function setEndedAt(?\DateTimeImmutable $endedAt): static
    {
        $this->endedAt = $endedAt;

        return $this;
    }

    public function getCreditsGrantedCurrentPeriod(): int
    {
        return $this->creditsGrantedCurrentPeriod;
    }

    public function setCreditsGrantedCurrentPeriod(int $creditsGrantedCurrentPeriod): static
    {
        $this->creditsGrantedCurrentPeriod = max(0, $creditsGrantedCurrentPeriod);

        return $this;
    }

    public function getCreditsConsumedCurrentPeriod(): int
    {
        return $this->creditsConsumedCurrentPeriod;
    }

    public function setCreditsConsumedCurrentPeriod(int $creditsConsumedCurrentPeriod): static
    {
        $this->creditsConsumedCurrentPeriod = max(0, $creditsConsumedCurrentPeriod);

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
        return SubscriptionStatusEnum::ACTIVE === $this->status;
    }

    public function isUsableAt(?\DateTimeImmutable $at = null): bool
    {
        $at ??= new \DateTimeImmutable();

        if (!$this->status->isUsable()) {
            return false;
        }

        if (null !== $this->endedAt && $this->endedAt <= $at) {
            return false;
        }

        return null === $this->currentPeriodEnd || $this->currentPeriodEnd >= $at;
    }

    public function getRemainingCredits(): int
    {
        return max(0, $this->creditsGrantedCurrentPeriod - $this->creditsConsumedCurrentPeriod);
    }

    public function hasCreditsAvailable(): bool
    {
        return $this->getRemainingCredits() > 0;
    }

    public function canRespondToQuoteRequests(?\DateTimeImmutable $at = null): bool
    {
        return $this->isUsableAt($at)
            && $this->hasCreditsAvailable()
            && $this->plan instanceof SubscriptionPlan
            && $this->plan->isQuoteResponsesEnabled();
    }

    public function canUseInstantMessaging(?\DateTimeImmutable $at = null): bool
    {
        return $this->isUsableAt($at)
            && $this->plan instanceof SubscriptionPlan
            && $this->plan->isInstantMessagingEnabled();
    }

    public function grantCredits(int $credits): static
    {
        $this->creditsGrantedCurrentPeriod = max(0, $this->creditsGrantedCurrentPeriod + $credits);

        return $this;
    }

    public function consumeCredits(int $credits = 1): static
    {
        $this->creditsConsumedCurrentPeriod = max(0, $this->creditsConsumedCurrentPeriod + $credits);

        return $this;
    }

    public function syncCreditsWithPlan(): static
    {
        if ($this->plan instanceof SubscriptionPlan) {
            $currentPrice = $this->planPrice;
            if (
                !$currentPrice instanceof SubscriptionPlanPrice
                || $currentPrice->getPlan() !== $this->plan
                || $currentPrice->getBillingPeriod() !== $this->billingPeriod
            ) {
                $currentPrice = $this->plan->getCurrentPriceForPeriod($this->billingPeriod);
                $this->planPrice = $currentPrice;
            }

            $this->creditsGrantedCurrentPeriod = $this->plan->getCreditsForPeriod($this->billingPeriod);
            $this->creditsConsumedCurrentPeriod = 0;
            $this->stripePriceId = $currentPrice?->getStripePriceId() ?? $this->plan->getStripePriceIdForPeriod($this->billingPeriod);
        }

        return $this;
    }

    public function getBilledAmount(): ?string
    {
        if ($this->planPrice instanceof SubscriptionPlanPrice) {
            return $this->planPrice->getAmount();
        }

        return $this->plan?->getAmountForPeriod($this->billingPeriod);
    }

    /**
     * @return Collection<int, SubscriptionInvoice>
     */
    public function getInvoices(): Collection
    {
        return $this->invoices;
    }

    public function addInvoice(SubscriptionInvoice $invoice): static
    {
        if (!$this->invoices->contains($invoice)) {
            $this->invoices->add($invoice);
            $invoice->setSubscription($this);
        }

        return $this;
    }

    public function removeInvoice(SubscriptionInvoice $invoice): static
    {
        if ($this->invoices->removeElement($invoice) && $invoice->getSubscription() === $this) {
            $invoice->setSubscription(null);
        }

        return $this;
    }

    /**
     * @return Collection<int, SubscriptionCreditMovement>
     */
    public function getCreditMovements(): Collection
    {
        return $this->creditMovements;
    }

    public function addCreditMovement(SubscriptionCreditMovement $creditMovement): static
    {
        if (!$this->creditMovements->contains($creditMovement)) {
            $this->creditMovements->add($creditMovement);
            $creditMovement->setSubscription($this);
        }

        return $this;
    }

    public function removeCreditMovement(SubscriptionCreditMovement $creditMovement): static
    {
        if ($this->creditMovements->removeElement($creditMovement) && $creditMovement->getSubscription() === $this) {
            $creditMovement->setSubscription(null);
        }

        return $this;
    }
}
