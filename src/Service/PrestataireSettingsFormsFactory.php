<?php

namespace App\Service;

use App\Dto\PrestataireSettingsForms;
use App\Entity\PrestataireDocument;
use App\Entity\PrestataireInterventionZone;
use App\Entity\PrestataireProfile;
use App\Entity\User;
use App\Form\AccountDeletionType;
use App\Form\AccountPasswordChangeType;
use App\Form\PrestataireAvailabilityCollectionType;
use App\Form\PrestataireCompanyTabType;
use App\Form\PrestataireInterventionZoneType;
use App\Form\PrestataireNotificationPreferencesType;
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
                \App\Form\UserProfileTabType::class,
                $user
            ),
            companyForm: $this->formFactory->createNamed(
                'company_form',
                PrestataireCompanyTabType::class,
                $prestataireProfile
            ),
            publicProfileForm: $this->formFactory->createNamed(
                'public_profile_form',
                \App\Form\PrestatairePublicProfileTabType::class,
                $prestataireProfile
            ),
            certificationForm: $this->formFactory->createNamed(
                'certification_form',
                \App\Form\PrestataireCertificationType::class,
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
                \App\Form\PrestataireDocumentType::class,
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
