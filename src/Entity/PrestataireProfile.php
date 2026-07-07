<?php

/**
 * Copyright(c) 2026 Trouve moi
 *
 * Ce fichier fait partie d’un projet développé par Auxioma Web Agency.
 * Tous droits réservés.
 *
 * Ce code source est la propriété exclusive de Auxioma Web Agency.
 * Toute reproduction, modification, distribution ou utilisation sans autorisation préalable est interdite.
 */

namespace App\Entity;

use App\Enum\PrestataireProfileStatusEnum;
use App\Enum\SearchVisibilityEnum;
use App\Enum\VerificationStatusEnum;
use App\Repository\PrestataireProfileRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Attribute as Vich;
use App\Entity\PrestataireAppointment;
use App\Enum\DocumentVerificationStatusEnum;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: PrestataireProfileRepository::class)]
#[ORM\Table(name: 'prestataire_profile')]
#[ORM\Index(name: 'idx_presta_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_presta_status', columns: ['profile_status'])]
#[ORM\Index(name: 'idx_presta_city', columns: ['city'])]
#[ORM\Index(name: 'idx_presta_zip', columns: ['postal_code'])]
#[Vich\Uploadable]
class PrestataireProfile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\OneToOne(inversedBy: 'prestataireProfile', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, columnDefinition: 'BIGINT NOT NULL')]
    private ?User $account = null;

    #[ORM\Column(length: 255)]
    private ?string $companyName = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $slug = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $legalName = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $structureType = null;

    #[ORM\Column(length: 9, nullable: true)]
    private ?string $siren = null;

    #[ORM\Column(length: 14, nullable: true, unique: true)]
    #[Assert\Length(
        exactly: 14,
        exactMessage: 'Le numéro SIRET doit contenir exactement 14 chiffres.'
    )]
    #[Assert\Regex(
        pattern: '/^\d{14}$/',
        message: 'Le numéro SIRET doit contenir uniquement 14 chiffres.'
    )]
    private ?string $siret = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $vatNumber = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $addressComplement = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $postalCode = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $country = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7, nullable: true)]
    private ?string $latitude = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7, nullable: true)]
    private ?string $longitude = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $geohash = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $phonePublic = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $phonePrivate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $website = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $facebookUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $instagramUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $linkedinUrl = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $shortDescription = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $longDescription = null;

    // --- LOGO ---
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

    #[Vich\UploadableField(mapping: 'company_logo', fileNameProperty: 'logo')]
    private ?File $logoFile = null;

    // --- COVER IMAGE ---
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $coverImage = null;

    #[Vich\UploadableField(mapping: 'company_cover', fileNameProperty: 'coverImage')]
    private ?File $coverImageFile = null;

    #[ORM\Column(type: 'string', length: 50, enumType: PrestataireProfileStatusEnum::class)]
    private ?PrestataireProfileStatusEnum $profileStatus = PrestataireProfileStatusEnum::DRAFT;

    #[ORM\Column(type: 'string', length: 50, enumType: VerificationStatusEnum::class)]
    private ?VerificationStatusEnum $verificationStatus = VerificationStatusEnum::NOT_VERIFIED;

    #[ORM\Column]
    private ?int $completionScore = 0;

    #[ORM\Column(type: Types::DECIMAL, precision: 3, scale: 2)]
    private ?string $averageRating = '0.00';

    #[ORM\Column]
    private ?int $reviewsCount = 0;

    #[ORM\Column(nullable: true)]
    private ?int $responseTimeMinutes = null;

    #[ORM\Column]
    private ?bool $isFeatured = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $featuredUntil = null;

    #[ORM\Column(type: 'string', length: 50, enumType: SearchVisibilityEnum::class)]
    private ?SearchVisibilityEnum $searchVisibility = SearchVisibilityEnum::NORMAL;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $verifiedAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $metier = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $experience = null;

    #[ORM\Column(type: 'string', length: 50, enumType: DocumentVerificationStatusEnum::class)]
    private ?DocumentVerificationStatusEnum $documentVerificationStatus = DocumentVerificationStatusEnum::NOT_SUBMITTED;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $companyLastVerificationAt = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $companyVerificationSource = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $companyVerificationNote = null;

    /**
     * @var Collection<int, PrestataireService>
     */
    #[ORM\OneToMany(mappedBy: 'prestataire', targetEntity: PrestataireService::class, cascade: ['persist', 'remove'])]
    private Collection $prestataireServices;

    /**
     * @var Collection<int, PrestataireInterventionZone>
     */
    #[ORM\OneToMany(targetEntity: PrestataireInterventionZone::class, mappedBy: 'prestataireProfile')]
    private Collection $prestataireInterventionZones;

    /**
     * @var Collection<int, PrestataireAppointment>
     */
    #[ORM\OneToMany(mappedBy: 'prestataire', targetEntity: PrestataireAppointment::class, orphanRemoval: true)]
    private Collection $appointments;

    /**
     * @var Collection<int, QuoteRequest>
     */
    #[ORM\OneToMany(mappedBy: 'prestataire', targetEntity: QuoteRequest::class)]
    private Collection $quoteRequests;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->profileStatus = PrestataireProfileStatusEnum::DRAFT;
        $this->verificationStatus = VerificationStatusEnum::NOT_VERIFIED;
        $this->searchVisibility = SearchVisibilityEnum::NORMAL;
        $this->completionScore = 0;
        $this->reviewsCount = 0;
        $this->averageRating = '0.00';
        $this->isFeatured = false;
        $this->prestataireServices = new ArrayCollection();
        $this->prestataireInterventionZones = new ArrayCollection();
        $this->availabilities = new ArrayCollection();
        $this->appointments = new ArrayCollection();
        $this->quoteRequests = new ArrayCollection();
        $this->documentVerificationStatus = DocumentVerificationStatusEnum::NOT_SUBMITTED;
    }

    // --- AVAILABILITY ---
    #[ORM\OneToMany(mappedBy: 'prestataireProfile', targetEntity: PrestataireAvailability::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['dayOfWeek' => 'ASC'])]
    private Collection $availabilities;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getAccount(): ?User
    {
        return $this->account;
    }

    public function setAccount(?User $account): static
    {
        $this->account = $account;

        return $this;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(string $companyName): static
    {
        $this->companyName = $companyName;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getLegalName(): ?string
    {
        return $this->legalName;
    }

    public function setLegalName(?string $legalName): static
    {
        $this->legalName = $legalName;

        return $this;
    }

    public function getStructureType(): ?string
    {
        return $this->structureType;
    }

    public function setStructureType(?string $structureType): static
    {
        $this->structureType = $structureType;

        return $this;
    }

    public function getSiren(): ?string
    {
        return $this->siren;
    }

    public function setSiren(?string $siren): static
    {
        $this->siren = $siren;

        return $this;
    }

    public function getSiret(): ?string
    {
        return $this->siret;
    }

    public function setSiret(?string $siret): static
    {
        $this->siret = $siret;

        return $this;
    }

    public function getVatNumber(): ?string
    {
        return $this->vatNumber;
    }

    public function setVatNumber(?string $vatNumber): static
    {
        $this->vatNumber = $vatNumber;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getAddressComplement(): ?string
    {
        return $this->addressComplement;
    }

    public function setAddressComplement(?string $addressComplement): static
    {
        $this->addressComplement = $addressComplement;

        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): static
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(?string $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(?string $longitude): static
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getGeohash(): ?string
    {
        return $this->geohash;
    }

    public function setGeohash(?string $geohash): static
    {
        $this->geohash = $geohash;

        return $this;
    }

    public function getPhonePublic(): ?string
    {
        return $this->phonePublic;
    }

    public function setPhonePublic(?string $phonePublic): static
    {
        $this->phonePublic = $phonePublic;

        return $this;
    }

    public function getPhonePrivate(): ?string
    {
        return $this->phonePrivate;
    }

    public function setPhonePrivate(?string $phonePrivate): static
    {
        $this->phonePrivate = $phonePrivate;

        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): static
    {
        $this->website = $website;

        return $this;
    }

    public function getFacebookUrl(): ?string
    {
        return $this->facebookUrl;
    }

    public function setFacebookUrl(?string $facebookUrl): static
    {
        $this->facebookUrl = $facebookUrl;

        return $this;
    }

    public function getInstagramUrl(): ?string
    {
        return $this->instagramUrl;
    }

    public function setInstagramUrl(?string $instagramUrl): static
    {
        $this->instagramUrl = $instagramUrl;

        return $this;
    }

    public function getLinkedinUrl(): ?string
    {
        return $this->linkedinUrl;
    }

    public function setLinkedinUrl(?string $linkedinUrl): static
    {
        $this->linkedinUrl = $linkedinUrl;

        return $this;
    }

    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    public function setShortDescription(?string $shortDescription): static
    {
        $this->shortDescription = $shortDescription;

        return $this;
    }

    public function getLongDescription(): ?string
    {
        return $this->longDescription;
    }

    public function setLongDescription(?string $longDescription): static
    {
        $this->longDescription = $longDescription;

        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): static
    {
        $this->logo = $logo;

        return $this;
    }

    public function getLogoFile(): ?File
    {
        return $this->logoFile;
    }

    public function setLogoFile(?File $logoFile = null): static
    {
        $this->logoFile = $logoFile;

        if (null !== $logoFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getCoverImage(): ?string
    {
        return $this->coverImage;
    }

    public function setCoverImage(?string $coverImage): static
    {
        $this->coverImage = $coverImage;

        return $this;
    }

    public function getCoverImageFile(): ?File
    {
        return $this->coverImageFile;
    }

    public function setCoverImageFile(?File $coverImageFile = null): static
    {
        $this->coverImageFile = $coverImageFile;

        if (null !== $coverImageFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getProfileStatus(): ?PrestataireProfileStatusEnum
    {
        return $this->profileStatus;
    }

    public function setProfileStatus(PrestataireProfileStatusEnum $profileStatus): static
    {
        $this->profileStatus = $profileStatus;

        return $this;
    }

    public function getVerificationStatus(): ?VerificationStatusEnum
    {
        return $this->verificationStatus;
    }

    public function setVerificationStatus(VerificationStatusEnum $verificationStatus): static
    {
        $this->verificationStatus = $verificationStatus;

        return $this;
    }

    public function getCompletionScore(): ?int
    {
        return $this->completionScore;
    }

    public function setCompletionScore(int $completionScore): static
    {
        $this->completionScore = $completionScore;

        return $this;
    }

    public function getAverageRating(): ?string
    {
        return $this->averageRating;
    }

    public function setAverageRating(string $averageRating): static
    {
        $this->averageRating = $averageRating;

        return $this;
    }

    public function getReviewsCount(): ?int
    {
        return $this->reviewsCount;
    }

    public function setReviewsCount(int $reviewsCount): static
    {
        $this->reviewsCount = $reviewsCount;

        return $this;
    }

    public function getResponseTimeMinutes(): ?int
    {
        return $this->responseTimeMinutes;
    }

    public function setResponseTimeMinutes(?int $responseTimeMinutes): static
    {
        $this->responseTimeMinutes = $responseTimeMinutes;

        return $this;
    }

    public function isFeatured(): ?bool
    {
        return $this->isFeatured;
    }

    public function setIsFeatured(bool $isFeatured): static
    {
        $this->isFeatured = $isFeatured;

        return $this;
    }

    public function getFeaturedUntil(): ?\DateTimeImmutable
    {
        return $this->featuredUntil;
    }

    public function setFeaturedUntil(?\DateTimeImmutable $featuredUntil): static
    {
        $this->featuredUntil = $featuredUntil;

        return $this;
    }

    public function getSearchVisibility(): ?SearchVisibilityEnum
    {
        return $this->searchVisibility;
    }

    public function setSearchVisibility(SearchVisibilityEnum $searchVisibility): static
    {
        $this->searchVisibility = $searchVisibility;

        return $this;
    }

    public function getVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->verifiedAt;
    }

    public function setVerifiedAt(?\DateTimeImmutable $verifiedAt): static
    {
        $this->verifiedAt = $verifiedAt;

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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeImmutable $deletedAt): static
    {
        $this->deletedAt = $deletedAt;

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

    public function getMetier(): ?string
    {
        return $this->metier;
    }

    public function setMetier(?string $metier): static
    {
        $this->metier = $metier;

        return $this;
    }

    public function getExperience(): ?string
    {
        return $this->experience;
    }

    public function setExperience(?string $experience): static
    {
        $this->experience = $experience;

        return $this;
    }

    public function getDocumentVerificationStatus(): ?DocumentVerificationStatusEnum
    {
        return $this->documentVerificationStatus;
    }

    public function setDocumentVerificationStatus(DocumentVerificationStatusEnum $documentVerificationStatus): static
    {
        $this->documentVerificationStatus = $documentVerificationStatus;

        return $this;
    }

    public function getCompanyLastVerificationAt(): ?\DateTimeImmutable
    {
        return $this->companyLastVerificationAt;
    }

    public function setCompanyLastVerificationAt(?\DateTimeImmutable $companyLastVerificationAt): static
    {
        $this->companyLastVerificationAt = $companyLastVerificationAt;

        return $this;
    }

    public function getCompanyVerificationSource(): ?string
    {
        return $this->companyVerificationSource;
    }

    public function setCompanyVerificationSource(?string $companyVerificationSource): static
    {
        $this->companyVerificationSource = $companyVerificationSource;

        return $this;
    }

    public function getCompanyVerificationNote(): ?string
    {
        return $this->companyVerificationNote;
    }

    public function setCompanyVerificationNote(?string $companyVerificationNote): static
    {
        $this->companyVerificationNote = $companyVerificationNote;

        return $this;
    }

    /**
     * @return Collection<int, PrestataireAvailability>
     */
    public function getAvailabilities(): Collection
    {
        return $this->availabilities;
    }

    public function addAvailability(PrestataireAvailability $availability): static
    {
        if (!$this->availabilities->contains($availability)) {
            $this->availabilities->add($availability);
            $availability->setPrestataireProfile($this);
        }

        return $this;
    }

    public function removeAvailability(PrestataireAvailability $availability): static
    {
        if ($this->availabilities->removeElement($availability)) {
            if ($availability->getPrestataireProfile() === $this) {
                $availability->setPrestataireProfile(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PrestataireService>
     */
    public function getPrestataireServices(): Collection
    {
        return $this->prestataireServices;
    }

    public function addPrestataireService(PrestataireService $prestataireService): static
    {
        if (!$this->prestataireServices->contains($prestataireService)) {
            $this->prestataireServices->add($prestataireService);
            $prestataireService->setPrestataire($this);
        }

        return $this;
    }

    public function removePrestataireService(PrestataireService $prestataireService): static
    {
        if ($this->prestataireServices->removeElement($prestataireService)) {
            // set the owning side to null (unless already changed)
            if ($prestataireService->getPrestataire() === $this) {
                $prestataireService->setPrestataire(null);
            }
        }

        return $this;
    }

    /**
     * Raccourci pour garder la compatibilité avec le reste de ton site.
     *
     * @return Collection<int, Service>
     */
    public function getServices(): Collection
    {
        // Extrait les objets Service de tes PrestataireService
        $services = new ArrayCollection();
        foreach ($this->prestataireServices as $ps) {
            $services->add($ps->getService());
        }

        return $services;
    }

    /**
     * @return Collection<int, PrestataireInterventionZone>
     */
    public function getPrestataireInterventionZones(): Collection
    {
        return $this->prestataireInterventionZones;
    }

    public function addPrestataireInterventionZone(PrestataireInterventionZone $prestataireInterventionZone): static
    {
        if (!$this->prestataireInterventionZones->contains($prestataireInterventionZone)) {
            $this->prestataireInterventionZones->add($prestataireInterventionZone);
            $prestataireInterventionZone->setPrestataireProfile($this);
        }

        return $this;
    }

    public function removePrestataireInterventionZone(PrestataireInterventionZone $prestataireInterventionZone): static
    {
        $this->prestataireInterventionZones->removeElement($prestataireInterventionZone);

        return $this;
    }

    /**
     * @return Collection<int, PrestataireAppointment>
     */
    public function getAppointments(): Collection
    {
        return $this->appointments;
    }

    public function addAppointment(PrestataireAppointment $appointment): self
    {
        if (!$this->appointments->contains($appointment)) {
            $this->appointments->add($appointment);
            $appointment->setPrestataire($this);
        }

        return $this;
    }

    public function removeAppointment(PrestataireAppointment $appointment): self
    {
        if ($this->appointments->removeElement($appointment)) {
            if ($appointment->getPrestataire() === $this) {
                $appointment->setPrestataire(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, QuoteRequest>
     */
    public function getQuoteRequests(): Collection
    {
        return $this->quoteRequests;
    }

    public function addQuoteRequest(QuoteRequest $quoteRequest): static
    {
        if (!$this->quoteRequests->contains($quoteRequest)) {
            $this->quoteRequests->add($quoteRequest);
            $quoteRequest->setPrestataire($this);
        }

        return $this;
    }

    public function removeQuoteRequest(QuoteRequest $quoteRequest): static
    {
        if ($this->quoteRequests->removeElement($quoteRequest)) {
            if ($quoteRequest->getPrestataire() === $this) {
                $quoteRequest->setPrestataire(null);
            }
        }

        return $this;
    }
}
