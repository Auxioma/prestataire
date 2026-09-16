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

namespace App\Service;

use App\Entity\PrestataireProfile;
use App\Enum\PrestataireProfileStatusEnum;
use App\Enum\VerificationStatusEnum;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class CompanyVerificationManager
{
    public function __construct(
        private readonly PrestataireProfileManager $prestataireProfileManager,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function buildPreview(PrestataireProfile $prestataireProfile, CompanyRegistryClient $companyRegistryClient): array
    {
        $siret = $prestataireProfile->getSiret();

        if (!$siret) {
            throw new \RuntimeException('Veuillez renseigner un numéro SIRET avant de lancer la vérification.');
        }

        $previewPayload = $companyRegistryClient->buildCompanyPreviewFromSiret($siret);
        $previewPayload['profileId'] = $prestataireProfile->getId();
        $this->getSession()?->set('company_verification_preview', $previewPayload);

        return [
            'siret' => $previewPayload['siret'],
            'siren' => $previewPayload['siren'] ?? null,
            'fields' => [
                [
                    'label' => 'Numéro SIREN',
                    'current' => $prestataireProfile->getSiren(),
                    'incoming' => $previewPayload['fields']['siren'] ?? null,
                ],
                [
                    'label' => 'Nom de l’entreprise / Enseigne',
                    'current' => $prestataireProfile->getCompanyName(),
                    'incoming' => $previewPayload['fields']['companyName'] ?? null,
                ],
                [
                    'label' => 'Raison sociale',
                    'current' => $prestataireProfile->getLegalName(),
                    'incoming' => $previewPayload['fields']['legalName'] ?? null,
                ],
                [
                    'label' => 'Forme juridique',
                    'current' => $prestataireProfile->getStructureType(),
                    'incoming' => $previewPayload['fields']['structureType'] ?? null,
                ],
                [
                    'label' => 'Adresse',
                    'current' => $prestataireProfile->getAddress(),
                    'incoming' => $previewPayload['fields']['address'] ?? null,
                ],
                [
                    'label' => 'Code postal',
                    'current' => $prestataireProfile->getPostalCode(),
                    'incoming' => $previewPayload['fields']['postalCode'] ?? null,
                ],
                [
                    'label' => 'Ville',
                    'current' => $prestataireProfile->getCity(),
                    'incoming' => $previewPayload['fields']['city'] ?? null,
                ],
                [
                    'label' => 'Pays',
                    'current' => $prestataireProfile->getCountry(),
                    'incoming' => $previewPayload['fields']['country'] ?? null,
                ],
            ],
        ];
    }

    /**
     * @return array{isVerified: bool, isActive: bool}
     */
    public function applyAcceptedPreview(PrestataireProfile $prestataireProfile): array
    {
        $previewPayload = $this->getSession()?->get('company_verification_preview');

        if (!\is_array($previewPayload) || empty($previewPayload['fields'])) {
            throw new \RuntimeException('La prévisualisation a expiré. Veuillez relancer la vérification.');
        }

        if (($previewPayload['profileId'] ?? null) !== $prestataireProfile->getId()
            || ($previewPayload['siret'] ?? null) !== $prestataireProfile->getSiret()) {
            $this->clearPreview();

            throw new \RuntimeException('Le SIRET a changé. Veuillez relancer la vérification.');
        }

        $prestataireProfile->setNafCode($previewPayload['nafCode'] ?? null);
        $fields = $previewPayload['fields'];

        if (!empty($fields['siren'])) {
            $prestataireProfile->setSiren($fields['siren']);
        }

        if (!empty($fields['companyName'])) {
            $prestataireProfile->setCompanyName($fields['companyName']);
        }

        if (!empty($fields['legalName'])) {
            $prestataireProfile->setLegalName($fields['legalName']);
        }

        if (!empty($fields['structureType'])) {
            $prestataireProfile->setStructureType($fields['structureType']);
        }

        if (!empty($fields['address'])) {
            $prestataireProfile->setAddress($fields['address']);
        }

        if (!empty($fields['postalCode'])) {
            $prestataireProfile->setPostalCode($fields['postalCode']);
        }

        if (!empty($fields['city'])) {
            $prestataireProfile->setCity($fields['city']);
        }

        if (!empty($fields['country'])) {
            $prestataireProfile->setCountry($fields['country']);
        }

        $this->prestataireProfileManager->syncSlug($prestataireProfile);

        $isVerified = !empty($previewPayload['isVerified']);
        $isActive = !empty($previewPayload['isActive']);

        if ($isVerified) {
            $prestataireProfile->setVerificationStatus(VerificationStatusEnum::COMPANY_VERIFIED);
            $prestataireProfile->setVerifiedAt(new \DateTimeImmutable());
            $prestataireProfile->setCompanyVerificationSource('recherche-entreprises.api.gouv.fr');
        }

        if ($isVerified && $isActive) {
            $prestataireProfile->setProfileStatus(PrestataireProfileStatusEnum::ACTIVE);
        } elseif ($isVerified) {
            $prestataireProfile->setProfileStatus(PrestataireProfileStatusEnum::PENDING_VALIDATION);
        }

        $this->clearPreview();

        return [
            'isVerified' => $isVerified,
            'isActive' => $isActive,
        ];
    }

    public function clearPreview(): void
    {
        $this->getSession()?->remove('company_verification_preview');
    }

    private function getSession(): ?SessionInterface
    {
        return $this->requestStack->getSession();
    }
}
