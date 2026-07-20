<?php

namespace App\Security\Voter;

use App\Entity\PrestataireProfile;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class PrestataireCompanySettingsVoter extends Voter
{
    public const PRESTATAIRE_HAS_COMPLETE_COMPANY_SETTINGS = 'PRESTATAIRE_HAS_COMPLETE_COMPANY_SETTINGS';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::PRESTATAIRE_HAS_COMPLETE_COMPANY_SETTINGS === $attribute && $subject instanceof PrestataireProfile;
    }

    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token,
        ?Vote $vote = null
    ): bool {
        \assert($subject instanceof PrestataireProfile);

        return !$this->isBlank($subject->getCompanyName())
            && !$this->isBlank($subject->getSiret())
            && !$this->isBlank($subject->getSiren())
            && !$this->isBlank($subject->getStructureType())
            && !$this->isBlank($subject->getVatNumber())
            && !$this->isBlank($subject->getAddress())
            && !$this->isBlank($subject->getPostalCode())
            && !$this->isBlank($subject->getCity())
            && !$this->isBlank($subject->getCountry());
    }

    private function isBlank(mixed $value): bool
    {
        if (null === $value) {
            return true;
        }

        if (\is_string($value)) {
            return '' === trim($value);
        }

        return false;
    }
}
