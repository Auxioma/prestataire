<?php

namespace App\Entity\Subscription;

use App\Enum\SubscriptionInvoiceStatusEnum;
use App\Repository\Subscription\SubscriptionInvoiceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubscriptionInvoiceRepository::class)]
#[ORM\Table(name: 'subscription_invoice')]
#[ORM\UniqueConstraint(name: 'uniq_subscription_invoice_stripe_invoice', columns: ['stripe_invoice_id'])]
#[ORM\Index(name: 'idx_subscription_invoice_status', columns: ['status'])]
#[ORM\Index(name: 'idx_subscription_invoice_due_at', columns: ['due_at'])]
class SubscriptionInvoice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\ManyToOne(inversedBy: 'invoices')]
    #[ORM\JoinColumn(name: 'subscription_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?PrestataireSubscription $subscription = null;

    #[ORM\Column(length: 255)]
    private ?string $stripeInvoiceId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripePaymentIntentId = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $invoiceNumber = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $hostedInvoiceUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $invoicePdfUrl = null;

    #[ORM\Column(length: 3)]
    private string $currency = 'eur';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $subtotalAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $taxAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $totalAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $amountPaid = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $amountRemaining = null;

    #[ORM\Column(type: 'string', length: 20, enumType: SubscriptionInvoiceStatusEnum::class)]
    private SubscriptionInvoiceStatusEnum $status = SubscriptionInvoiceStatusEnum::DRAFT;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $billingReason = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $periodStart = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $periodEnd = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $dueAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $paidAt = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $stripePayload = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id;
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

    public function getStripeInvoiceId(): ?string
    {
        return $this->stripeInvoiceId;
    }

    public function setStripeInvoiceId(string $stripeInvoiceId): static
    {
        $this->stripeInvoiceId = $stripeInvoiceId;

        return $this;
    }

    public function getStripePaymentIntentId(): ?string
    {
        return $this->stripePaymentIntentId;
    }

    public function setStripePaymentIntentId(?string $stripePaymentIntentId): static
    {
        $this->stripePaymentIntentId = $stripePaymentIntentId;

        return $this;
    }

    public function getInvoiceNumber(): ?string
    {
        return $this->invoiceNumber;
    }

    public function setInvoiceNumber(?string $invoiceNumber): static
    {
        $this->invoiceNumber = $invoiceNumber;

        return $this;
    }

    public function getHostedInvoiceUrl(): ?string
    {
        return $this->hostedInvoiceUrl;
    }

    public function setHostedInvoiceUrl(?string $hostedInvoiceUrl): static
    {
        $this->hostedInvoiceUrl = $hostedInvoiceUrl;

        return $this;
    }

    public function getInvoicePdfUrl(): ?string
    {
        return $this->invoicePdfUrl;
    }

    public function setInvoicePdfUrl(?string $invoicePdfUrl): static
    {
        $this->invoicePdfUrl = $invoicePdfUrl;

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getCurrencyCode(): string
    {
        return mb_strtoupper($this->currency ?: 'eur');
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = mb_strtolower($currency);

        return $this;
    }

    public function getSubtotalAmount(): ?string
    {
        return $this->subtotalAmount;
    }

    public function setSubtotalAmount(?string $subtotalAmount): static
    {
        $this->subtotalAmount = $subtotalAmount;

        return $this;
    }

    public function getTaxAmount(): ?string
    {
        return $this->taxAmount;
    }

    public function setTaxAmount(?string $taxAmount): static
    {
        $this->taxAmount = $taxAmount;

        return $this;
    }

    public function getTotalAmount(): ?string
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(?string $totalAmount): static
    {
        $this->totalAmount = $totalAmount;

        return $this;
    }

    public function getAmountPaid(): ?string
    {
        return $this->amountPaid;
    }

    public function setAmountPaid(?string $amountPaid): static
    {
        $this->amountPaid = $amountPaid;

        return $this;
    }

    public function getAmountRemaining(): ?string
    {
        return $this->amountRemaining;
    }

    public function setAmountRemaining(?string $amountRemaining): static
    {
        $this->amountRemaining = $amountRemaining;

        return $this;
    }

    public function getStatus(): SubscriptionInvoiceStatusEnum
    {
        return $this->status;
    }

    public function setStatus(SubscriptionInvoiceStatusEnum $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getBillingReason(): ?string
    {
        return $this->billingReason;
    }

    public function setBillingReason(?string $billingReason): static
    {
        $this->billingReason = $billingReason;

        return $this;
    }

    public function getPeriodStart(): ?\DateTimeImmutable
    {
        return $this->periodStart;
    }

    public function setPeriodStart(?\DateTimeImmutable $periodStart): static
    {
        $this->periodStart = $periodStart;

        return $this;
    }

    public function getPeriodEnd(): ?\DateTimeImmutable
    {
        return $this->periodEnd;
    }

    public function setPeriodEnd(?\DateTimeImmutable $periodEnd): static
    {
        $this->periodEnd = $periodEnd;

        return $this;
    }

    public function getDueAt(): ?\DateTimeImmutable
    {
        return $this->dueAt;
    }

    public function setDueAt(?\DateTimeImmutable $dueAt): static
    {
        $this->dueAt = $dueAt;

        return $this;
    }

    public function getPaidAt(): ?\DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function setPaidAt(?\DateTimeImmutable $paidAt): static
    {
        $this->paidAt = $paidAt;

        return $this;
    }

    public function getStripePayload(): ?array
    {
        return $this->stripePayload;
    }

    public function setStripePayload(?array $stripePayload): static
    {
        $this->stripePayload = $stripePayload;

        return $this;
    }

    public function getStripeDescription(): ?string
    {
        $payload = $this->stripePayload;
        if (!\is_array($payload)) {
            return null;
        }

        $lines = $payload['lines'] ?? null;
        if (!\is_array($lines)) {
            return null;
        }

        $data = $lines['data'] ?? null;
        if (!\is_array($data) || [] === $data) {
            return null;
        }

        $firstLine = $data[0];
        if (!\is_array($firstLine)) {
            return null;
        }

        $description = trim((string) ($firstLine['description'] ?? ''));
        if ('' !== $description) {
            return $description;
        }

        $price = $firstLine['price'] ?? null;
        if (\is_array($price)) {
            $nickname = trim((string) ($price['nickname'] ?? ''));
            if ('' !== $nickname) {
                return $nickname;
            }

            $product = $price['product'] ?? null;
            if (\is_array($product)) {
                $productName = trim((string) ($product['name'] ?? ''));
                if ('' !== $productName) {
                    return $productName;
                }
            }
        }

        return null;
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
