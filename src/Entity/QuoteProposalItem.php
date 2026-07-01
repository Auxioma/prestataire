<?php

namespace App\Entity;

use App\Repository\QuoteProposalItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: QuoteProposalItemRepository::class)]
#[ORM\Table(name: 'quote_proposal_item')]
#[ORM\Index(name: 'idx_quote_proposal_item_position', columns: ['position'])]
class QuoteProposalItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: QuoteProposal::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?QuoteProposal $quoteProposal = null;

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

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

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
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuoteProposal(): ?QuoteProposal
    {
        return $this->quoteProposal;
    }

    public function setQuoteProposal(?QuoteProposal $quoteProposal): self
    {
        $this->quoteProposal = $quoteProposal;
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
        $this->position = $position;
        $this->touch();

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function computeTotalHt(): self
    {
        $quantity = (float) ($this->quantity ?? '0');
        $unitPriceHt = (float) ($this->unitPriceHt ?? '0');

        $this->totalHt = number_format($quantity * $unitPriceHt, 2, '.', '');
        $this->updatedAt = new \DateTime();

        return $this;
    }

    public function getVatAmount(): string
    {
        $totalHt = (float) ($this->totalHt ?? '0');
        $vatRate = (float) ($this->vatRate ?? '0');

        return number_format($totalHt * ($vatRate / 100), 2, '.', '');
    }

    public function getTotalTtc(): string
    {
        $totalHt = (float) ($this->totalHt ?? '0');
        $vatAmount = (float) $this->getVatAmount();

        return number_format($totalHt + $vatAmount, 2, '.', '');
    }

    public function touch(): self
    {
        $this->updatedAt = new \DateTime();

        return $this;
    }
}
