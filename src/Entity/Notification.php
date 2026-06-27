<?php

namespace App\Entity;

use App\Enum\NotificationTypeEnum;
use App\Repository\NotificationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[ORM\Table(name: 'notification')]
#[ORM\Index(name: 'idx_notification_recipient', columns: ['recipient_id'])]
#[ORM\Index(name: 'idx_notification_type', columns: ['type'])]
#[ORM\Index(name: 'idx_notification_recipient_is_read', columns: ['recipient_id', 'is_read'])]
#[ORM\Index(name: 'idx_notification_recipient_created_at', columns: ['recipient_id', 'created_at'])]
class Notification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'notifications')]
    #[ORM\JoinColumn(
        name: 'recipient_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'CASCADE',
        columnDefinition: 'BIGINT NOT NULL'
    )]
    private ?User $recipient = null;

    #[ORM\Column(type: Types::STRING, length: 50, enumType: NotificationTypeEnum::class)]
    private ?NotificationTypeEnum $type = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $body = null;

    #[ORM\Column(name: 'target_url', type: Types::STRING, length: 255, nullable: true)]
    private ?string $targetUrl = null;

    #[ORM\Column(name: 'is_read', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isRead = false;

    #[ORM\Column(name: 'read_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $readAt = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $metadata = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->isRead = false;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getRecipient(): ?User
    {
        return $this->recipient;
    }

    public function setRecipient(?User $recipient): static
    {
        $this->recipient = $recipient;

        return $this;
    }

    public function getType(): ?NotificationTypeEnum
    {
        return $this->type;
    }

    public function setType(NotificationTypeEnum $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = trim($title);

        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(string $body): static
    {
        $this->body = trim($body);

        return $this;
    }

    public function getTargetUrl(): ?string
    {
        return $this->targetUrl;
    }

    public function setTargetUrl(?string $targetUrl): static
    {
        $this->targetUrl = null !== $targetUrl ? trim($targetUrl) : null;

        return $this;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function getIsRead(): bool
    {
        return $this->isRead;
    }

    public function setIsRead(bool $isRead): static
    {
        $this->isRead = $isRead;

        if ($isRead && null === $this->readAt) {
            $this->readAt = new \DateTimeImmutable();
        }

        if (!$isRead) {
            $this->readAt = null;
        }

        return $this;
    }

    public function getReadAt(): ?\DateTimeImmutable
    {
        return $this->readAt;
    }

    public function setReadAt(?\DateTimeImmutable $readAt): static
    {
        $this->readAt = $readAt;
        $this->isRead = null !== $readAt;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

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

    public function markAsRead(): static
    {
        $this->isRead = true;
        $this->readAt = new \DateTimeImmutable();

        return $this;
    }

    public function markAsUnread(): static
    {
        $this->isRead = false;
        $this->readAt = null;

        return $this;
    }
}
