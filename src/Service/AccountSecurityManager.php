<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ClientProfile;
use App\Entity\PrestataireProfile;
use App\Entity\User;
use App\Enum\DocumentVerificationStatusEnum;
use App\Enum\PrestataireProfileStatusEnum;
use App\Enum\SearchVisibilityEnum;
use App\Enum\UserStatusEnum;
use App\Enum\VerificationStatusEnum;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AccountSecurityManager
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function changePassword(User $user, string $plainPassword): void
    {
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        $user->setUpdatedAt(new \DateTimeImmutable());
    }

    public function softDelete(User $user): void
    {
        $now = new \DateTimeImmutable();

        $user
            ->setPassword($this->passwordHasher->hashPassword($user, bin2hex(random_bytes(32))))
            ->setFirstName(null)
            ->setLastName(null)
            ->setPhoneNumber(null)
            ->setPhoneVerifiedAt(null)
            ->setEmailVerifiedAt(null)
            ->setIsVerified(false)
            ->setAvatar(null)
            ->setLocale(null)
            ->setTimezone(null)
            ->setDeletedAt($now)
            ->setUpdatedAt($now)
            ->setStatus(UserStatusEnum::DELETED)
            ->setResetToken(null)
            ->setResetTokenExpiresAt(null);

        $user->setAvatarFile(null);

        if (null !== $user->getClientProfile()) {
            $this->anonymizeClientProfile($user->getClientProfile(), $now);
        }

        if (null !== $user->getPrestataireProfile()) {
            $this->anonymizePrestataireProfile($user->getPrestataireProfile(), $now);
        }
    }

    private function anonymizeClientProfile(ClientProfile $profile, \DateTimeImmutable $now): void
    {
        $profile
            ->setCompanyName(null)
            ->setSiret(null)
            ->setBillingAddress(null)
            ->setBillingPostalCode(null)
            ->setBillingCity(null)
            ->setBillingCountry(null)
            ->setDefaultAddress(null)
            ->setDefaultPostalCode(null)
            ->setDefaultCity(null)
            ->setLatitude(null)
            ->setLongitude(null)
            ->setUpdatedAt($now);
    }

    private function anonymizePrestataireProfile(PrestataireProfile $profile, \DateTimeImmutable $now): void
    {
        $profileId = $profile->getId() ?? bin2hex(random_bytes(6));

        $profile
            ->setDeletedAt($now)
            ->setUpdatedAt($now)
            ->setCompanyName(sprintf('Compte desinscrit %s', $profileId))
            ->setSlug(sprintf('compte-desinscrit-%s', $profileId))
            ->setLegalName(null)
            ->setStructureType(null)
            ->setSiren(null)
            ->setSiret(null)
            ->setVatNumber(null)
            ->setAddress(null)
            ->setAddressComplement(null)
            ->setPostalCode(null)
            ->setCity(null)
            ->setCountry(null)
            ->setLatitude(null)
            ->setLongitude(null)
            ->setGeohash(null)
            ->setPhonePublic(null)
            ->setPhonePrivate(null)
            ->setWebsite(null)
            ->setFacebookUrl(null)
            ->setInstagramUrl(null)
            ->setLinkedinUrl(null)
            ->setShortDescription(null)
            ->setLongDescription(null)
            ->setLogo(null)
            ->setLogoFile(null)
            ->setCoverImage(null)
            ->setCoverImageFile(null)
            ->setSignatureImage(null)
            ->setSignatureImageFile(null)
            ->setProfileStatus(PrestataireProfileStatusEnum::DELETED)
            ->setVerificationStatus(VerificationStatusEnum::NOT_VERIFIED)
            ->setCompletionScore(0)
            ->setResponseTimeMinutes(null)
            ->setIsFeatured(false)
            ->setFeaturedUntil(null)
            ->setSearchVisibility(SearchVisibilityEnum::HIDDEN)
            ->setVerifiedAt(null)
            ->setDescription(null)
            ->setMetier(null)
            ->setExperience(null)
            ->setDocumentVerificationStatus(DocumentVerificationStatusEnum::NOT_SUBMITTED)
            ->setCompanyLastVerificationAt(null)
            ->setCompanyVerificationSource(null)
            ->setCompanyVerificationNote(null);
    }
}
