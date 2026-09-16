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

namespace App\Tests\Service;

use App\Entity\PrestataireProfile;
use App\Exception\RegistrationAdmissionException;
use App\Repository\AllowedNafCodeRepository;
use App\Repository\PrestataireProfileRepository;
use App\Service\CompanyRegistryClient;
use App\Service\PrestataireRegistrationAdmission;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class PrestataireRegistrationAdmissionTest extends TestCase
{
    public function testAllowedNafIsTakenFromRegistryAndStoredOnProfile(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $service = $this->service('4322a', true);
        $company = $service->verify('12345678900011', $session);
        self::assertSame('43.22A', $company['nafCode']);
        self::assertNotNull($service->getApprovedCompany($session));
        $profile = new PrestataireProfile();
        $service->applyToProfile($profile, $company);
        self::assertSame('12345678900011', $profile->getSiret());
        self::assertSame('43.22A', $profile->getNafCode());
        self::assertSame('Entreprise', $profile->getCompanyName());
    }

    public function testRejectedNafDoesNotAllowContinuingAndClearsPreviousAdmission(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->set(PrestataireRegistrationAdmission::SESSION_KEY, ['checkedAt' => time()]);
        try {
            $this->service('01.11Z', false)->verify('12345678900011', $session);
            self::fail('A refused activity must not pass.');
        } catch (RegistrationAdmissionException $exception) {
            self::assertSame(PrestataireRegistrationAdmission::OTHER_PLATFORM_MESSAGE, $exception->getMessage());
            self::assertFalse($session->has(PrestataireRegistrationAdmission::SESSION_KEY));
        }
    }

    public function testMissingNafIsNotReportedAsWrongPlatform(): void
    {
        $this->expectException(RegistrationAdmissionException::class);
        $this->expectExceptionMessage('code NAF');
        $this->service(null, false)->verify('12345678900011', new Session(new MockArraySessionStorage()));
    }

    public function testExpiredOrDisabledAdmissionCannotBeReused(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $service = $this->service('43.22A', true);
        $company = $service->verify('12345678900011', $session);
        $company['checkedAt'] = time() - 1801;
        $session->set(PrestataireRegistrationAdmission::SESSION_KEY, $company);
        self::assertNull($service->getApprovedCompany($session));
        $company['checkedAt'] = time();
        $session->set(PrestataireRegistrationAdmission::SESSION_KEY, $company);
        self::assertNull($this->service('43.22A', false)->getApprovedCompany($session));
    }

    public function testClosedEstablishmentCannotProceed(): void
    {
        $this->expectException(RegistrationAdmissionException::class);
        $this->expectExceptionMessage('établissement actif');
        $this->service('43.22A', true, 'F')->verify('12345678900011', new Session(new MockArraySessionStorage()));
    }

    public function testApiOutageDoesNotExposeTransportDetailsOrRedirectToOtherPlatform(): void
    {
        $codes = $this->createStub(AllowedNafCodeRepository::class);
        $profiles = $this->createStub(PrestataireProfileRepository::class);
        $registry = new CompanyRegistryClient(new MockHttpClient(static function () {
            throw new \Symfony\Component\HttpClient\Exception\TransportException('Private transport details');
        }), 'https://registry.example');
        $session = new Session(new MockArraySessionStorage());
        try {
            (new PrestataireRegistrationAdmission($registry, $codes, $profiles))->verify('12345678900011', $session);
            self::fail('An API outage must block admission.');
        } catch (RegistrationAdmissionException $exception) {
            self::assertStringContainsString('temporairement indisponible', $exception->getMessage());
            self::assertStringNotContainsString('Private', $exception->getMessage());
            self::assertFalse($session->has(PrestataireRegistrationAdmission::SESSION_KEY));
        }
    }

    public function testFinalRecheckRejectsAnActivityThatHasChanged(): void
    {
        $codes = $this->createStub(AllowedNafCodeRepository::class);
        $codes->method('isAllowed')->willReturnCallback(static fn (string $code) => '43.22A' === $code);
        $profiles = $this->createStub(PrestataireProfileRepository::class);
        $profiles->method('findOneBy')->willReturn(null);
        $responses = [];
        foreach (['43.22A', '01.11Z'] as $naf) {
            $responses[] = new MockResponse(json_encode(['results' => [[
                'siren' => '123456789',
                'siege' => ['siret' => '12345678900011', 'etat_administratif' => 'A', 'activite_principale' => $naf],
            ]]], \JSON_THROW_ON_ERROR));
        }
        $service = new PrestataireRegistrationAdmission(new CompanyRegistryClient(new MockHttpClient($responses), 'https://registry.example'), $codes, $profiles);
        $session = new Session(new MockArraySessionStorage());
        $service->verify('12345678900011', $session);
        $this->expectException(RegistrationAdmissionException::class);
        $this->expectExceptionMessage(PrestataireRegistrationAdmission::OTHER_PLATFORM_MESSAGE);
        $service->getApprovedCompany($session, true);
    }

    private function service(?string $naf, bool $allowed, string $status = 'A'): PrestataireRegistrationAdmission
    {
        $codes = $this->createStub(AllowedNafCodeRepository::class);
        $codes->method('isAllowed')->willReturn($allowed);
        $profiles = $this->createStub(PrestataireProfileRepository::class);
        $profiles->method('findOneBy')->willReturn(null);
        $registry = new CompanyRegistryClient(new MockHttpClient(static fn () => new MockResponse(json_encode(['results' => [[
            'siren' => '123456789', 'nom_complet' => 'Entreprise',
            'siege' => ['siret' => '12345678900011', 'etat_administratif' => $status, 'activite_principale' => $naf],
        ]]], \JSON_THROW_ON_ERROR))), 'https://registry.example');

        return new PrestataireRegistrationAdmission($registry, $codes, $profiles);
    }
}
