<?php

namespace App\Entity;

use App\Repository\PrestataireAvailabilityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PrestataireAvailabilityRepository::class)]
#[ORM\Table(name: 'prestataire_availability')]
#[ORM\UniqueConstraint(name: 'uniq_prestataire_day', columns: ['prestataire_profile_id', 'day_of_week'])]
class PrestataireAvailability
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'availabilities')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?PrestataireProfile $prestataireProfile = null;

    #[ORM\Column]
    private ?int $dayOfWeek = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $morningEnabled = false;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $morningStart = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $morningEnd = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $afternoonEnabled = false;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $afternoonStart = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $afternoonEnd = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
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

    public function getDayOfWeek(): ?int
    {
        return $this->dayOfWeek;
    }

    public function setDayOfWeek(int $dayOfWeek): static
    {
        $this->dayOfWeek = $dayOfWeek;

        return $this;
    }

    public function isMorningEnabled(): bool
    {
        return $this->morningEnabled;
    }

    public function setMorningEnabled(bool $morningEnabled): static
    {
        $this->morningEnabled = $morningEnabled;

        return $this;
    }

    public function getMorningStart(): ?\DateTimeInterface
    {
        return $this->morningStart;
    }

    public function setMorningStart(?\DateTimeInterface $morningStart): static
    {
        $this->morningStart = $morningStart;

        return $this;
    }

    public function getMorningEnd(): ?\DateTimeInterface
    {
        return $this->morningEnd;
    }

    public function setMorningEnd(?\DateTimeInterface $morningEnd): static
    {
        $this->morningEnd = $morningEnd;

        return $this;
    }

    public function isAfternoonEnabled(): bool
    {
        return $this->afternoonEnabled;
    }

    public function setAfternoonEnabled(bool $afternoonEnabled): static
    {
        $this->afternoonEnabled = $afternoonEnabled;

        return $this;
    }

    public function getAfternoonStart(): ?\DateTimeInterface
    {
        return $this->afternoonStart;
    }

    public function setAfternoonStart(?\DateTimeInterface $afternoonStart): static
    {
        $this->afternoonStart = $afternoonStart;

        return $this;
    }

    public function getAfternoonEnd(): ?\DateTimeInterface
    {
        return $this->afternoonEnd;
    }

    public function setAfternoonEnd(?\DateTimeInterface $afternoonEnd): static
    {
        $this->afternoonEnd = $afternoonEnd;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getDayLabel(): string
    {
        return match ($this->dayOfWeek) {
            1 => 'Lundi',
            2 => 'Mardi',
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi',
            7 => 'Dimanche',
            default => 'Jour inconnu',
        };
    }
}