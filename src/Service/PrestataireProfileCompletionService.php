<?php

namespace App\Service;

use App\Entity\PrestataireAvailability;
use App\Entity\PrestataireProfile;
use App\Entity\User;

final class PrestataireProfileCompletionService
{
    /**
     * @return array{
     *     score:int,
     *     completedChecks:int,
     *     totalChecks:int,
     *     completedSections:int,
     *     totalSections:int,
     *     sections:list<array{
     *         key:string,
     *         label:string,
     *         tab:string,
     *         icon:string,
     *         completed:int,
     *         total:int,
     *         isComplete:bool,
     *         missingItems:list<string>
     *     }>,
     *     nextSections:list<array{
     *         key:string,
     *         label:string,
     *         tab:string,
     *         icon:string,
     *         completed:int,
     *         total:int,
     *         isComplete:bool,
     *         missingItems:list<string>
     *     }>
     * }
     */
    public function buildReport(User $user, PrestataireProfile $prestataireProfile): array
    {
        $sections = [
            $this->createSection(
                key: 'profile',
                label: 'Profil personnel',
                tab: 'profile',
                icon: 'fa-user',
                checks: [
                    'Photo de profil' => null !== $user->getAvatar(),
                    'Numéro de téléphone' => null !== $user->getPhoneNumber() && '' !== trim((string) $user->getPhoneNumber()),
                ],
            ),
            $this->createSection(
                key: 'public_profile',
                label: 'Profil public',
                tab: 'profile',
                icon: 'fa-id-card',
                checks: [
                    'Métier / spécialité' => null !== $prestataireProfile->getMetier() && '' !== trim((string) $prestataireProfile->getMetier()),
                    'Expérience' => null !== $prestataireProfile->getExperience() && '' !== trim((string) $prestataireProfile->getExperience()),
                    'Phrase d’accroche' => null !== $prestataireProfile->getShortDescription() && '' !== trim((string) $prestataireProfile->getShortDescription()),
                    'Présentation complète' => null !== $prestataireProfile->getDescription() && '' !== trim((string) $prestataireProfile->getDescription()),
                    'Site web ou réseau social' => $this->hasPublicWebPresence($prestataireProfile),
                ],
            ),
            $this->createSection(
                key: 'company',
                label: 'Entreprise',
                tab: 'company',
                icon: 'fa-building',
                checks: [
                    'Nom de l’entreprise' => null !== $prestataireProfile->getCompanyName() && '' !== trim((string) $prestataireProfile->getCompanyName()),
                    'SIRET ou SIREN' => $this->hasCompanyIdentifier($prestataireProfile),
                    'Adresse complète' => $this->hasCompanyAddress($prestataireProfile),
                    'Logo' => null !== $prestataireProfile->getLogo(),
                    'Image de couverture' => null !== $prestataireProfile->getCoverImage(),
                ],
            ),
            $this->createSection(
                key: 'services',
                label: 'Prestations',
                tab: 'services',
                icon: 'fa-screwdriver-wrench',
                checks: [
                    'Au moins une prestation active' => $this->hasActiveService($prestataireProfile),
                ],
            ),
            $this->createSection(
                key: 'zones',
                label: 'Zones d’intervention',
                tab: 'zones',
                icon: 'fa-location-dot',
                checks: [
                    'Au moins une zone active' => $this->hasActiveZone($prestataireProfile),
                ],
            ),
            $this->createSection(
                key: 'availability',
                label: 'Disponibilités',
                tab: 'dispo',
                icon: 'fa-calendar-days',
                checks: [
                    'Horaires hebdomadaires ou statut vacances' => $prestataireProfile->isOnVacation() || $this->hasConfiguredAvailability($prestataireProfile),
                ],
            ),
            $this->createSection(
                key: 'documents',
                label: 'Documents',
                tab: 'company',
                icon: 'fa-file-shield',
                checks: [
                    'Au moins un document transmis' => !$prestataireProfile->getDocuments()->isEmpty(),
                ],
            ),
        ];

        $completedChecks = array_sum(array_column($sections, 'completed'));
        $totalChecks = array_sum(array_column($sections, 'total'));
        $completedSections = count(array_filter($sections, static fn (array $section): bool => $section['isComplete']));
        $totalSections = count($sections);
        $score = 0 === $totalChecks ? 0 : (int) round(($completedChecks / $totalChecks) * 100);

        $prestataireProfile->setCompletionScore($score);

        return [
            'score' => $score,
            'completedChecks' => $completedChecks,
            'totalChecks' => $totalChecks,
            'completedSections' => $completedSections,
            'totalSections' => $totalSections,
            'sections' => $sections,
            'nextSections' => array_values(array_filter($sections, static fn (array $section): bool => !$section['isComplete'])),
        ];
    }

    public function syncCompletionScore(User $user, PrestataireProfile $prestataireProfile): int
    {
        $report = $this->buildReport($user, $prestataireProfile);

        return $report['score'];
    }

    /**
     * @param array<string, bool> $checks
     * @return array{
     *     key:string,
     *     label:string,
     *     tab:string,
     *     icon:string,
     *     completed:int,
     *     total:int,
     *     isComplete:bool,
     *     missingItems:list<string>
     * }
     */
    private function createSection(string $key, string $label, string $tab, string $icon, array $checks): array
    {
        $missingItems = [];

        foreach ($checks as $checkLabel => $isCompleted) {
            if (!$isCompleted) {
                $missingItems[] = $checkLabel;
            }
        }

        $completed = count($checks) - count($missingItems);
        $total = count($checks);

        return [
            'key' => $key,
            'label' => $label,
            'tab' => $tab,
            'icon' => $icon,
            'completed' => $completed,
            'total' => $total,
            'isComplete' => 0 === count($missingItems),
            'missingItems' => $missingItems,
        ];
    }

    private function hasPublicWebPresence(PrestataireProfile $prestataireProfile): bool
    {
        return $this->hasText($prestataireProfile->getWebsite())
            || $this->hasText($prestataireProfile->getFacebookUrl())
            || $this->hasText($prestataireProfile->getInstagramUrl())
            || $this->hasText($prestataireProfile->getLinkedinUrl());
    }

    private function hasCompanyIdentifier(PrestataireProfile $prestataireProfile): bool
    {
        return $this->hasText($prestataireProfile->getSiret()) || $this->hasText($prestataireProfile->getSiren());
    }

    private function hasCompanyAddress(PrestataireProfile $prestataireProfile): bool
    {
        return $this->hasText($prestataireProfile->getAddress())
            && $this->hasText($prestataireProfile->getPostalCode())
            && $this->hasText($prestataireProfile->getCity());
    }

    private function hasActiveService(PrestataireProfile $prestataireProfile): bool
    {
        foreach ($prestataireProfile->getPrestataireServices() as $service) {
            if ($service->isActive()) {
                return true;
            }
        }

        return false;
    }

    private function hasActiveZone(PrestataireProfile $prestataireProfile): bool
    {
        foreach ($prestataireProfile->getPrestataireInterventionZones() as $zone) {
            if ($zone->isActive()) {
                return true;
            }
        }

        return false;
    }

    private function hasConfiguredAvailability(PrestataireProfile $prestataireProfile): bool
    {
        foreach ($prestataireProfile->getAvailabilities() as $availability) {
            if (!$availability instanceof PrestataireAvailability) {
                continue;
            }

            if (
                ($availability->isMorningEnabled() && null !== $availability->getMorningStart() && null !== $availability->getMorningEnd())
                || ($availability->isAfternoonEnabled() && null !== $availability->getAfternoonStart() && null !== $availability->getAfternoonEnd())
            ) {
                return true;
            }
        }

        return false;
    }

    private function hasText(?string $value): bool
    {
        return null !== $value && '' !== trim($value);
    }
}
