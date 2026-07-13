<?php

namespace App\Entity\Subscription;

use App\Entity\PrestataireProfile;
use App\Repository\Subscription\SubscriptionCustomerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubscriptionCustomerRepository::class)]
#[ORM\Table(name: 'subscription_customer')]
#[ORM\UniqueConstraint(name: 'uniq_subscription_customer_prestataire', columns: ['prestataire_profile_id'])]
#[ORM\UniqueConstraint(name: 'uniq_subscription_customer_stripe_customer', columns: ['stripe_customer_id'])]
class SubscriptionCustomer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\OneToOne(inversedBy: 'subscriptionCustomer')]
    #[ORM\JoinColumn(name: 'prestataire_profile_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?PrestataireProfile $prestataireProfile = null;

    #[ORM\Column(length: 255)]
    private ?string $stripeCustomerId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripeDefaultPaymentMethodId = null;

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $defaultPaymentMethodType = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $billingEmail = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, PrestataireSubscription>
     */
    #[ORM\OneToMany(mappedBy: 'customer', targetEntity: PrestataireSubscription::class)]
    private Collection $subscriptions;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->subscriptions = new ArrayCollection();
    }

    public function __toString(): string
    {
        $prestataire = $this->prestataireProfile?->__toString();
        $stripeCustomerId = trim((string) ($this->stripeCustomerId ?? ''));

        if (null !== $prestataire && '' !== $stripeCustomerId) {
            return sprintf('%s [%s]', $prestataire, $stripeCustomerId);
        }

        if (null !== $prestataire) {
            return $prestataire;
        }

        return sprintf('Client Stripe #%s', $this->id ?? 'n/a');
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

        if ($prestataireProfile && $prestataireProfile->getSubscriptionCustomer() !== $this) {
            $prestataireProfile->setSubscriptionCustomer($this);
        }

        return $this;
    }

    public function getStripeCustomerId(): ?string
    {
        return $this->stripeCustomerId;
    }

    public function setStripeCustomerId(string $stripeCustomerId): static
    {
        $this->stripeCustomerId = $stripeCustomerId;

        return $this;
    }

    public function getStripeDefaultPaymentMethodId(): ?string
    {
        return $this->stripeDefaultPaymentMethodId;
    }

    public function setStripeDefaultPaymentMethodId(?string $stripeDefaultPaymentMethodId): static
    {
        $this->stripeDefaultPaymentMethodId = $stripeDefaultPaymentMethodId;

        return $this;
    }

    public function getDefaultPaymentMethodType(): ?string
    {
        return $this->defaultPaymentMethodType;
    }

    public function setDefaultPaymentMethodType(?string $defaultPaymentMethodType): static
    {
        $this->defaultPaymentMethodType = $defaultPaymentMethodType;

        return $this;
    }

    public function getBillingEmail(): ?string
    {
        return $this->billingEmail;
    }

    public function setBillingEmail(?string $billingEmail): static
    {
        $this->billingEmail = $billingEmail;

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
            $subscription->setCustomer($this);
        }

        return $this;
    }

    public function removeSubscription(PrestataireSubscription $subscription): static
    {
        if ($this->subscriptions->removeElement($subscription) && $subscription->getCustomer() === $this) {
            $subscription->setCustomer(null);
        }

        return $this;
    }
}
