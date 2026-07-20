<?php

namespace App\Security\Voter;

use App\Entity\ClientProfile;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class ClientSettingsVoter extends Voter
{
    public const CLIENT_HAS_COMPLETE_SETTINGS = 'CLIENT_HAS_COMPLETE_SETTINGS';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::CLIENT_HAS_COMPLETE_SETTINGS === $attribute && $subject instanceof User;
    }

    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token,
        ?Vote $vote = null
    ): bool {
        \assert($subject instanceof User);

        $clientProfile = $subject->getClientProfile();
        if (!$clientProfile instanceof ClientProfile) {
            return false;
        }

        return !$this->isBlank($subject->getFirstName())
            && !$this->isBlank($subject->getLastName())
            && !$this->isBlank($subject->getPhoneNumber())
            && !$this->isBlank($clientProfile->getDefaultAddress())
            && !$this->isBlank($clientProfile->getDefaultPostalCode())
            && !$this->isBlank($clientProfile->getDefaultCity())
            && !$this->isBlank($clientProfile->getBillingAddress())
            && !$this->isBlank($clientProfile->getBillingPostalCode())
            && !$this->isBlank($clientProfile->getBillingCity())
            && !$this->isBlank($clientProfile->getBillingCountry());
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
