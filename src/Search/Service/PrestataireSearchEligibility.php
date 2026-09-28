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

namespace App\Search\Service;

use App\Account\Enum\UserStatusEnum;
use App\Prestataire\Entity\PrestataireProfile;
use App\Prestataire\Enum\PrestataireProfileStatusEnum;
use App\Search\Enum\SearchVisibilityEnum;

final class PrestataireSearchEligibility
{
    public const VERIFIED_STATUSES = ['COMPANY_VERIFIED', 'DOCUMENTS_VERIFIED', 'MANUALLY_VERIFIED'];

    public static function isEligible(PrestataireProfile $profile): bool
    {
        return PrestataireProfileStatusEnum::ACTIVE === $profile->getProfileStatus()
            && \in_array($profile->getVerificationStatus()?->value, self::VERIFIED_STATUSES, true)
            && SearchVisibilityEnum::HIDDEN !== $profile->getSearchVisibility()
            && !\in_array($profile->getAccount()?->getStatus(), [UserStatusEnum::SUSPENDED, UserStatusEnum::BANNED, UserStatusEnum::DELETED], true)
            && null === $profile->getAccount()?->getDeletedAt()
            && '' !== mb_trim((string) $profile->getCompanyName())
            && '' !== mb_trim((string) $profile->getSlug());
    }

    public static function filter(): array
    {
        return ['bool' => [
            'filter' => [
                ['term' => ['profileStatus' => 'ACTIVE']],
                ['terms' => ['verificationStatus' => self::VERIFIED_STATUSES]],
            ],
            'must_not' => [['term' => ['searchVisibility' => 'HIDDEN']]],
        ]];
    }
}
