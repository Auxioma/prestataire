<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\InvoiceItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: InvoiceItemRepository::class)]
#[ORM\Table(name: 'invoice_item')]
#[ORM\Index(name: 'idx_invoice_item_position', columns: ['position'])]
class InvoiceItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'items', targetEntity: Invoice::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Invoice $invoice = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $label = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => '1.00'])]
    private ?string $quantity = '1.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => '0.00'])]
    private ?string $unitPriceHt = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, options: ['default' => '20.00'])]
    private ?string $vatRate = '20.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => '0.00'])]
    private ?string $totalHt = '0.00';

    #[ORM\Column(options: ['default' => 0])]
    private int $position = 0;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        $label = $this->getLabel();
        $description = $this->getDescription();
        $isLabelBlank = $label === null || trim($label) === '';
        $isDescriptionBlank = $description === null || trim($description) === '';
        $isQuantityEmpty = $this->getQuantity() === null;
        $isUnitPriceEmpty = $this->getUnitPriceHt() === null;
        $isVatRateEmpty = $this->getVatRate() === null;

        $isCompletelyEmpty = $isLabelBlank && $isDescriptionBlank && $isQuantityEmpty && $isUnitPriceEmpty && $isVatRateEmpty;
        if ($isCompletelyEmpty) {
            return;
        }

        if ($isLabelBlank) {
            $context->buildViolation('Veuillez renseigner un intitulé de ligne.')
                ->atPath('label')
                ->addViolation();
        }

        if ($isQuantityEmpty) {
            $context->buildViolation('Veuillez renseigner une quantité.')
                ->atPath('quantity')
                ->addViolation();
        }

        if ($isUnitPriceEmpty) {
            $context->buildViolation('Veuillez renseigner un prix unitaire HT.')
                ->atPath('unitPriceHt')
                ->addViolation();
        }

        if ($isVatRateEmpty) {
            $context->buildViolation('Veuillez renseigner une TVA.')
                ->atPath('vatRate')
                ->addViolation();
        }
    }

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInvoice(): ?Invoice
    {
        return $this->invoice;
    }

    public function setInvoice(?Invoice $invoice): self
    {
        $this->invoice = $invoice;
        $this->touch();

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): self
    {
        $this->label = $label;
        $this->touch();

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        $this->touch();

        return $this;
    }

    public function getQuantity(): ?string
    {
        return $this->quantity;
    }

    public function setQuantity(?string $quantity): self
    {
        $this->quantity = $quantity;
        $this->touch();

        return $this;
    }

    public function getUnitPriceHt(): ?string
    {
        return $this->unitPriceHt;
    }

    public function setUnitPriceHt(?string $unitPriceHt): self
    {
        $this->unitPriceHt = $unitPriceHt;
        $this->touch();

        return $this;
    }

    public function getVatRate(): ?string
    {
        return $this->vatRate;
    }

    public function setVatRate(?string $vatRate): self
    {
        $this->vatRate = $vatRate;
        $this->touch();

        return $this;
    }

    public function getTotalHt(): ?string
    {
        return $this->totalHt;
    }

    public function setTotalHt(?string $totalHt): self
    {
        $this->totalHt = $totalHt;
        $this->touch();

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): self
    {
        $this->position = $position ?? 0;
        $this->touch();

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function touch(): self
    {
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }
}
