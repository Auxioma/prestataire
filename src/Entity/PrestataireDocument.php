<?php

namespace App\Entity;

use App\Enum\PrestataireDocumentStatusEnum;
use App\Enum\PrestataireDocumentTypeEnum;
use App\Repository\PrestataireDocumentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: PrestataireDocumentRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[Vich\Uploadable]
class PrestataireDocument
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // profil prestataire propriétaire du document
    #[ORM\ManyToOne(inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?PrestataireProfile $prestataireProfile = null;

    // type métier du document
    #[ORM\Column(enumType: PrestataireDocumentTypeEnum::class)]
    private ?PrestataireDocumentTypeEnum $type = null;

    // statut métier du document
    #[ORM\Column(enumType: PrestataireDocumentStatusEnum::class)]
    private PrestataireDocumentStatusEnum $status = PrestataireDocumentStatusEnum::UPLOADED;

    // fichier uploadé (non mappé en base)
    #[Vich\UploadableField(
        mapping: 'prestataire_documents',
        fileNameProperty: 'fileName',
        size: 'fileSize',
        mimeType: 'mimeType',
        originalName: 'originalName'
    )]
    private ?File $documentFile = null;

    // nom original du fichier envoyé
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $originalName = null;

    // nom physique stocké sur le serveur
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fileName = null;

    // type mime
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $mimeType = null;

    // taille du fichier en octets
    #[ORM\Column(nullable: true)]
    private ?int $fileSize = null;

    // document visible par le client ou non
    #[ORM\Column(options: ['default' => false])]
    private bool $isVisibleToClient = false;

    // date d’émission du document si connue
    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $issuedAt = null;

    // date d’expiration du document si connue
    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $expiresAt = null;

    // notes libres éventuelles
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    // dates techniques
    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $updatedAt = null;

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

    public function getType(): ?PrestataireDocumentTypeEnum
    {
        return $this->type;
    }

    public function setType(?PrestataireDocumentTypeEnum $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getStatus(): PrestataireDocumentStatusEnum
    {
        return $this->status;
    }

    public function setStatus(PrestataireDocumentStatusEnum $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getDocumentFile(): ?File
    {
        return $this->documentFile;
    }

    public function setDocumentFile(?File $documentFile = null): void
    {
        $this->documentFile = $documentFile;

        if ($documentFile instanceof UploadedFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    public function setOriginalName(?string $originalName): static
    {
        $this->originalName = $originalName;

        return $this;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(?string $fileName): static
    {
        $this->fileName = $fileName;

        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(?string $mimeType): static
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function setFileSize(?int $fileSize): static
    {
        $this->fileSize = $fileSize;

        return $this;
    }

    public function isVisibleToClient(): bool
    {
        return $this->isVisibleToClient;
    }

    public function setIsVisibleToClient(bool $isVisibleToClient): static
    {
        $this->isVisibleToClient = $isVisibleToClient;

        return $this;
    }

    public function getIssuedAt(): ?\DateTimeInterface
    {
        return $this->issuedAt;
    }

    public function setIssuedAt(?\DateTimeInterface $issuedAt): static
    {
        $this->issuedAt = $issuedAt;

        return $this;
    }

    public function getExpiresAt(): ?\DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeInterface $expiresAt): static
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

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

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTimeImmutable();

        $this->createdAt ??= $now;
        $this->updatedAt ??= $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
