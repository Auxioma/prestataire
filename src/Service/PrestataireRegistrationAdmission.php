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
use App\Exception\RegistrationAdmissionException;
use App\Repository\AllowedNafCodeRepository;
use App\Repository\PrestataireProfileRepository;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class PrestataireRegistrationAdmission
{
    public const SESSION_KEY = 'prestataire_registration.company';
    public const OTHER_PLATFORM_MESSAGE = 'Votre activité ne correspond pas à TrouveMoi Prestataires. Veuillez vous inscrire sur l’autre plateforme « TrouveMoi ».';

    public function __construct(
        private readonly CompanyRegistryClient $registry,
        private readonly AllowedNafCodeRepository $codes,
        private readonly PrestataireProfileRepository $profiles,
    ) {
    }

    public function verify(string $siret, SessionInterface $session): array
    {
        $session->remove(self::SESSION_KEY);
        try {
            $company = $this->registry->buildCompanyPreviewFromSiret($siret);
        } catch (\Symfony\Contracts\HttpClient\Exception\ExceptionInterface $exception) {
            throw new RegistrationAdmissionException('La vérification SIRET est temporairement indisponible. Veuillez réessayer dans quelques instants.', 0, $exception);
        } catch (\RuntimeException|\InvalidArgumentException $exception) {
            throw new RegistrationAdmissionException($exception->getMessage(), 0, $exception);
        }
        if (!$company['isVerified'] || !$company['isActive']) {
            throw new RegistrationAdmissionException('Ce SIRET doit correspondre à un établissement actif pour poursuivre votre inscription.');
        }
        $naf = $company['nafCode'] ?? null;
        if (null === $naf) {
            throw new RegistrationAdmissionException('Le code NAF de cet établissement est indisponible. Veuillez réessayer plus tard.');
        }
        if (!$this->codes->isAllowed($naf)) {
            throw new RegistrationAdmissionException(self::OTHER_PLATFORM_MESSAGE);
        }
        if (null !== $this->profiles->findOneBy(['siret' => $company['siret']])) {
            throw new RegistrationAdmissionException('Ce SIRET est déjà utilisé. Connectez-vous ou contactez notre assistance.');
        }
        $company['checkedAt'] = time();
        $session->set(self::SESSION_KEY, $company);

        return $company;
    }

    public function getApprovedCompany(SessionInterface $session, bool $recheck = false): ?array
    {
        $company = $session->get(self::SESSION_KEY);
        if (!\is_array($company) || !isset($company['checkedAt'], $company['siret'], $company['nafCode'])
            || time() - $company['checkedAt'] > 1800 || !$this->codes->isAllowed($company['nafCode'])) {
            $session->remove(self::SESSION_KEY);

            return null;
        }

        return $recheck ? $this->verify($company['siret'], $session) : $company;
    }

    public function applyToProfile(PrestataireProfile $profile, array $company): void
    {
        $fields = $company['fields'];
        $profile->setSiret($company['siret'])->setNafCode($company['nafCode'])
            ->setSiren($fields['siren'])->setCompanyName($fields['companyName'] ?: 'Nouveau Prestataire')
            ->setLegalName($fields['legalName'])->setAddress($fields['address'])
            ->setPostalCode($fields['postalCode'])->setCity($fields['city'])->setCountry($fields['country']);
    }
}
