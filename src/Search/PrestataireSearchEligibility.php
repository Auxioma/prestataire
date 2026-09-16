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

namespace App\Search;

use App\Entity\PrestataireProfile;
use App\Enum\PrestataireProfileStatusEnum;
use App\Enum\SearchVisibilityEnum;
use App\Enum\UserStatusEnum;

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
