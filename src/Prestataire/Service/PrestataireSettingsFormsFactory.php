<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 AUXIOMA Web Agency.
 *
 * Projet : TrouveMoi
 *
 * Tous droits réservés.
 *
 * Ce fichier fait partie du projet TrouveMoi,
 * développé par AUXIOMA Web Agency.
 *
 * Toute reproduction, modification, distribution ou utilisation,
 * totale ou partielle, sans autorisation écrite préalable,
 * est strictement interdite.
 */

namespace App\Prestataire\Service;

use App\Account\Entity\User;
use App\Account\Form\AccountDeletionType;
use App\Account\Form\AccountPasswordChangeType;
use App\Messaging\Form\PrestataireNotificationPreferencesType;
use App\Prestataire\Dto\PrestataireSettingsForms;
use App\Prestataire\Entity\PrestataireDocument;
use App\Prestataire\Entity\PrestataireInterventionZone;
use App\Prestataire\Entity\PrestataireProfile;
use App\Prestataire\Form\PrestataireAvailabilityCollectionType;
use App\Prestataire\Form\PrestataireCompanyTabType;
use App\Prestataire\Form\PrestataireInterventionZoneType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class PrestataireSettingsFormsFactory
{
    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function create(User $user, PrestataireProfile $prestataireProfile): PrestataireSettingsForms
    {
        $zone = new PrestataireInterventionZone();
        $zone->setPrestataireProfile($prestataireProfile);

        $document = new PrestataireDocument();
        $document->setPrestataireProfile($prestataireProfile);

        $certification = new PrestataireDocument();
        $certification->setPrestataireProfile($prestataireProfile);

        return new PrestataireSettingsForms(
            userForm: $this->formFactory->createNamed(
                'user_profile_form',
                \App\Account\Form\UserProfileTabType::class,
                $user
            ),
            companyForm: $this->formFactory->createNamed(
                'company_form',
                PrestataireCompanyTabType::class,
                $prestataireProfile
            ),
            publicProfileForm: $this->formFactory->createNamed(
                'public_profile_form',
                \App\Prestataire\Form\PrestatairePublicProfileTabType::class,
                $prestataireProfile
            ),
            certificationForm: $this->formFactory->createNamed(
                'certification_form',
                \App\Prestataire\Form\PrestataireCertificationType::class,
                $certification,
                [
                    'action' => $this->urlGenerator->generate('app_prestataire_settings', ['tab' => 'profile']),
                    'method' => 'POST',
                ]
            ),
            availabilityForm: $this->formFactory->create(
                PrestataireAvailabilityCollectionType::class,
                $prestataireProfile,
                [
                    'action' => $this->urlGenerator->generate('app_prestataire_settings'),
                    'method' => 'POST',
                ]
            ),
            notificationForm: $this->formFactory->createNamed(
                'prestataire_notification_form',
                PrestataireNotificationPreferencesType::class,
                $user,
                [
                    'action' => $this->urlGenerator->generate('app_prestataire_settings'),
                    'method' => 'POST',
                ]
            ),
            passwordForm: $this->formFactory->createNamed(
                'prestataire_password_form',
                AccountPasswordChangeType::class,
                null,
                [
                    'action' => $this->urlGenerator->generate('app_prestataire_settings'),
                    'method' => 'POST',
                ]
            ),
            deletionForm: $this->formFactory->createNamed(
                'prestataire_deletion_form',
                AccountDeletionType::class,
                null,
                [
                    'action' => $this->urlGenerator->generate('app_prestataire_settings'),
                    'method' => 'POST',
                ]
            ),
            zoneForm: $this->formFactory->createNamed(
                'zone_form',
                PrestataireInterventionZoneType::class,
                $zone,
                [
                    'action' => $this->urlGenerator->generate('app_prestataire_zone_add'),
                    'method' => 'POST',
                ]
            ),
            documentForm: $this->formFactory->createNamed(
                'document_form',
                \App\Prestataire\Form\PrestataireDocumentType::class,
                $document,
                [
                    'action' => $this->urlGenerator->generate('app_prestataire_settings'),
                    'method' => 'POST',
                ]
            ),
            certificationEntity: $certification,
            documentEntity: $document,
        );
    }
}
