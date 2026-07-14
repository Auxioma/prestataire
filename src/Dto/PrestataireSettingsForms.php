<?php

namespace App\Dto;

use App\Entity\PrestataireDocument;
use Symfony\Component\Form\FormInterface;

final class PrestataireSettingsForms
{
    public function __construct(
        public readonly FormInterface $userForm,
        public readonly FormInterface $companyForm,
        public readonly FormInterface $publicProfileForm,
        public readonly FormInterface $availabilityForm,
        public readonly FormInterface $passwordForm,
        public readonly FormInterface $deletionForm,
        public readonly FormInterface $zoneForm,
        public readonly FormInterface $documentForm,
        public readonly PrestataireDocument $documentEntity,
    ) {
    }
}
