<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;

#[ORM\Entity]
#[ORM\Table(name: 'prestataire_profile_service')]
class PrestataireService
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: PrestataireProfile::class, inversedBy: 'prestataireServices')]
    #[ORM\JoinColumn(name: 'prestataire_profile_id', referencedColumnName: 'id', nullable: false)]
    private ?PrestataireProfile $prestataire = null;

    #[ORM\ManyToOne(targetEntity: Service::class)]
    #[ORM\JoinColumn(name: 'service_id', referencedColumnName: 'id', nullable: false)]
    private ?Service $service = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => 0])]
    private ?string $prixCatalogue = '0.00';

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $tauxReduction = null;

    // Getters et Setters basiques
    public function getId(): ?int { return $this->id; }

    public function getPrestataire(): ?PrestataireProfile { return $this->prestataire; }
    public function setPrestataire(?PrestataireProfile $prestataire): self { $this->prestataire = $prestataire; return $this; }

    public function getService(): ?Service { return $this->service; }
    public function setService(?Service $service): self { $this->service = $service; return $this; }

    public function getPrixCatalogue(): ?string { return $this->prixCatalogue; }
    public function setPrixCatalogue(string $prixCatalogue): self { $this->prixCatalogue = $prixCatalogue; return $this; }

    public function getTauxReduction(): ?string { return $this->tauxReduction; }
    public function setTauxReduction(?string $tauxReduction): self { $this->tauxReduction = $tauxReduction; return $this; }
}