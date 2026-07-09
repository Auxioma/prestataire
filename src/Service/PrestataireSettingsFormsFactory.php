<?php

namespace App\Service;

use App\Dto\PrestataireSettingsForms;
use App\Entity\PrestataireDocument;
use App\Entity\PrestataireInterventionZone;
use App\Entity\PrestataireProfile;
use App\Entity\User;
use App\Form\PrestataireAvailabilityCollectionType;
use App\Form\PrestataireCompanyTabType;
use App\Form\PrestataireInterventionZoneType;
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
            availabilityForm: $this->formFactory->create(
                PrestataireAvailabilityCollectionType::class,
                $prestataireProfile,
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
            documentEntity: $document,
        );
    }
}
