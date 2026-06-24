<?php

namespace App\Entity;

use App\Repository\PrestataireAppointmentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Enum\PrestataireAppointmentStatusEnum;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PrestataireAppointmentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class PrestataireAppointment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'appointments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?PrestataireProfile $prestataire = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?PrestataireService $prestation = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?ClientProfile $client = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotNull(message: 'La date de début est obligatoire.')]
    private ?\DateTimeInterface $startsAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Assert\NotNull(message: 'La date de fin est obligatoire.')]
    #[Assert\GreaterThan(
        propertyPath: 'startsAt',
        message: 'La date de fin doit être postérieure à la date de début.'
    )]
    private ?\DateTimeInterface $endsAt = null;

    #[ORM\Column(length: 30, enumType: PrestataireAppointmentStatusEnum::class)]
    private PrestataireAppointmentStatusEnum $status = PrestataireAppointmentStatusEnum::PENDING;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $locationLabel = null;



    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));

        if (null === $this->createdAt) {
            $this->createdAt = $now;
        }

        $this->updatedAt = clone $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
    }

    #[ORM\Column(length: 255, unique: true, nullable: true)]
    private ?string $slug = null;

    public function __construct()
    {
        $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $this->createdAt = clone $now;
        $this->updatedAt = clone $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPrestataire(): ?PrestataireProfile
    {
        return $this->prestataire;
    }

    public function setPrestataire(?PrestataireProfile $prestataire): self
    {
        $this->prestataire = $prestataire;
        return $this;
    }

    public function getPrestation(): ?PrestataireService
    {
        return $this->prestation;
    }
    public function setPrestation(?PrestataireService $prestation): self
    {
        $this->prestation = $prestation;
        return $this;
    }

    public function getClient(): ?ClientProfile
    {
        return $this->client;
    }
    public function setClient(?ClientProfile $client): self
    {
        $this->client = $client;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }
    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getStartsAt(): ?\DateTimeInterface
    {
        return $this->startsAt;
    }

    public function setStartsAt(\DateTimeInterface $startsAt): self
    {
        $this->startsAt = new \DateTime(
            $startsAt->format('Y-m-d H:i:s'),
            new \DateTimeZone('Europe/Paris')
        );

        return $this;
    }

    public function getEndsAt(): ?\DateTimeInterface
    {
        return $this->endsAt;
    }

    public function setEndsAt(\DateTimeInterface $endsAt): self
    {
        $this->endsAt = new \DateTime(
            $endsAt->format('Y-m-d H:i:s'),
            new \DateTimeZone('Europe/Paris')
        );

        return $this;
    }

    public function getStatus(): PrestataireAppointmentStatusEnum
    {
        return $this->status;
    }

    public function setStatus(PrestataireAppointmentStatusEnum $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getLocationLabel(): ?string
    {
        return $this->locationLabel;
    }
    public function setLocationLabel(?string $locationLabel): self
    {
        $this->locationLabel = $locationLabel;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }
    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }
    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): self
    {
        $this->slug = $slug;
        return $this;
    }
}
