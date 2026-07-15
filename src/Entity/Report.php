<?php

namespace App\Entity;

use App\Enum\ReportReasonEnum;
use App\Enum\ReportStatusEnum;
use App\Repository\ReportRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReportRepository::class)]
#[ORM\Table(name: 'report')]
#[ORM\Index(name: 'idx_report_status', columns: ['status'])]
#[ORM\Index(name: 'idx_report_reason', columns: ['reason'])]
#[ORM\Index(name: 'idx_report_created_at', columns: ['created_at'])]
class Report
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'reporter_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE', columnDefinition: 'BIGINT NOT NULL')]
    private ?User $reporter = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'reported_user_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL', columnDefinition: 'BIGINT DEFAULT NULL')]
    private ?User $reportedUser = null;

    #[ORM\ManyToOne(targetEntity: QuoteRequest::class)]
    #[ORM\JoinColumn(name: 'quote_request_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?QuoteRequest $quoteRequest = null;

    #[ORM\ManyToOne(targetEntity: Conversation::class)]
    #[ORM\JoinColumn(name: 'conversation_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Conversation $conversation = null;

    #[ORM\ManyToOne(targetEntity: Review::class)]
    #[ORM\JoinColumn(name: 'review_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Review $review = null;

    #[ORM\Column(type: 'string', length: 60, enumType: ReportReasonEnum::class)]
    private ReportReasonEnum $reason = ReportReasonEnum::OTHER;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $message = null;

    #[ORM\Column(type: 'string', length: 30, enumType: ReportStatusEnum::class)]
    private ReportStatusEnum $status = ReportStatusEnum::NEW;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adminNote = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $resolvedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return sprintf('Signalement #%s', $this->id ?? 'n/a');
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getReporter(): ?User
    {
        return $this->reporter;
    }

    public function setReporter(?User $reporter): static
    {
        $this->reporter = $reporter;

        return $this;
    }

    public function getReportedUser(): ?User
    {
        return $this->reportedUser;
    }

    public function setReportedUser(?User $reportedUser): static
    {
        $this->reportedUser = $reportedUser;

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

    public function getConversation(): ?Conversation
    {
        return $this->conversation;
    }

    public function setConversation(?Conversation $conversation): static
    {
        $this->conversation = $conversation;

        return $this;
    }

    public function getReview(): ?Review
    {
        return $this->review;
    }

    public function setReview(?Review $review): static
    {
        $this->review = $review;

        return $this;
    }

    public function getReason(): ReportReasonEnum
    {
        return $this->reason;
    }

    public function getReasonLabel(): string
    {
        return $this->reason->getLabel();
    }

    public function setReason(ReportReasonEnum $reason): static
    {
        $this->reason = $reason;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): static
    {
        $this->message = null !== $message ? trim($message) : null;

        return $this;
    }

    public function getStatus(): ReportStatusEnum
    {
        return $this->status;
    }

    public function getStatusLabel(): string
    {
        return $this->status->getLabel();
    }

    public function setStatus(ReportStatusEnum $status): static
    {
        $this->status = $status;

        if (\in_array($status, [ReportStatusEnum::RESOLVED, ReportStatusEnum::DISMISSED], true) && null === $this->resolvedAt) {
            $this->resolvedAt = new \DateTimeImmutable();
        }

        if (\in_array($status, [ReportStatusEnum::NEW, ReportStatusEnum::IN_REVIEW], true)) {
            $this->resolvedAt = null;
        }

        return $this;
    }

    public function getAdminNote(): ?string
    {
        return $this->adminNote;
    }

    public function setAdminNote(?string $adminNote): static
    {
        $this->adminNote = null !== $adminNote ? trim($adminNote) : null;

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

    public function getResolvedAt(): ?\DateTimeImmutable
    {
        return $this->resolvedAt;
    }

    public function setResolvedAt(?\DateTimeImmutable $resolvedAt): static
    {
        $this->resolvedAt = $resolvedAt;

        return $this;
    }

    public function getContextLabel(): string
    {
        if ($this->review instanceof Review) {
            return 'Avis client';
        }

        if ($this->conversation instanceof Conversation) {
            return 'Conversation';
        }

        if ($this->quoteRequest instanceof QuoteRequest) {
            return 'Demande de devis';
        }

        return 'Signalement';
    }

    public function getContextSummary(): string
    {
        if ($this->review instanceof Review) {
            return sprintf(
                'Avis lié à la demande "%s"%s.',
                $this->review->getQuoteRequest()?->getTitle() ?? 'sans titre',
                $this->review->getRating() !== null ? sprintf(' Note : %d/5', $this->review->getRating()) : ''
            );
        }

        if ($this->conversation instanceof Conversation) {
            return sprintf(
                'Conversation liée à la demande "%s".',
                $this->conversation->getQuoteRequest()?->getTitle() ?? 'sans titre'
            );
        }

        if ($this->quoteRequest instanceof QuoteRequest) {
            return sprintf(
                'Demande de devis "%s".',
                $this->quoteRequest->getTitle() ?? 'sans titre'
            );
        }

        return 'Aucun contexte détaillé disponible.';
    }

    public function getContextLinks(): string
    {
        return '';
    }
}
