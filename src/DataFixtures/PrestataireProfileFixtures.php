<?php

namespace App\DataFixtures;

use App\Entity\PrestataireProfile;
use App\Entity\User;
use App\Enum\DocumentVerificationStatusEnum;
use App\Enum\PrestataireProfileStatusEnum;
use App\Enum\SearchVisibilityEnum;
use App\Enum\VerificationStatusEnum;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class PrestataireProfileFixtures extends BaseFixture implements DependentFixtureInterface
{
    /**
     * @var list<array{company: string, metier: string, city: string, postal: string, lat: string, lng: string}>
     */
    private const PROFILES = [
        ['company' => 'Atelier Bâtisseur Gironde', 'metier' => 'Plombier chauffagiste', 'city' => 'Bordeaux', 'postal' => '33000', 'lat' => '44.8377890', 'lng' => '-0.5791800'],
        ['company' => 'Élec Habitat Conseil', 'metier' => 'Électricien', 'city' => 'Mérignac', 'postal' => '33700', 'lat' => '44.8422000', 'lng' => '-0.6458000'],
        ['company' => 'Les Jardins de Louise', 'metier' => 'Jardinière paysagiste', 'city' => 'Pessac', 'postal' => '33600', 'lat' => '44.8069000', 'lng' => '-0.6318000'],
        ['company' => 'Terrasse & Nature', 'metier' => 'Paysagiste', 'city' => 'Talence', 'postal' => '33400', 'lat' => '44.8089000', 'lng' => '-0.5895000'],
        ['company' => 'Docteur Ordi Bordeaux', 'metier' => 'Technicien informatique', 'city' => 'Bègles', 'postal' => '33130', 'lat' => '44.8081000', 'lng' => '-0.5486000'],
        ['company' => 'Studio Web Atlantique', 'metier' => 'Développeur web', 'city' => 'Arcachon', 'postal' => '33120', 'lat' => '44.6589000', 'lng' => '-1.1681000'],
        ['company' => 'Maison Nette Services', 'metier' => 'Agent d’entretien', 'city' => 'Libourne', 'postal' => '33500', 'lat' => '44.9145000', 'lng' => '-0.2419000'],
        ['company' => 'Savoir Plus Académie', 'metier' => 'Professeur particulier', 'city' => 'Gradignan', 'postal' => '33170', 'lat' => '44.7727000', 'lng' => '-0.6135000'],
        ['company' => 'Éclat & Style', 'metier' => 'Coiffeuse à domicile', 'city' => 'Le Bouscat', 'postal' => '33110', 'lat' => '44.8640000', 'lng' => '-0.6009000'],
        ['company' => 'Équilibre Coaching', 'metier' => 'Coach bien-être', 'city' => 'Talence', 'postal' => '33400', 'lat' => '44.8062000', 'lng' => '-0.5952000'],
        ['company' => 'Rivage Habitat', 'metier' => 'Électricien', 'city' => 'Lège-Cap-Ferret', 'postal' => '33950', 'lat' => '44.7985000', 'lng' => '-1.1466000'],
        ['company' => 'Plombéo Services', 'metier' => 'Plombier', 'city' => 'Saint-Médard-en-Jalles', 'postal' => '33160', 'lat' => '44.8965000', 'lng' => '-0.7148000'],
        ['company' => 'Pixel & Performance', 'metier' => 'Consultant web', 'city' => 'Bruges', 'postal' => '33520', 'lat' => '44.8850000', 'lng' => '-0.6123000'],
        ['company' => 'Formule Propreté', 'metier' => 'Technicienne de surface', 'city' => 'Cenon', 'postal' => '33150', 'lat' => '44.8526000', 'lng' => '-0.5225000'],
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::PROFILES as $index => $data) {
            /** @var User $user */
            $user = $this->getReference(sprintf('user_prestataire_%d', $index + 1), User::class);
            $createdAt = $this->randomDateTimeImmutable('-18 months', '-4 months');

            $profile = (new PrestataireProfile())
                ->setAccount($user)
                ->setCompanyName($data['company'])
                ->setSlug($this->slugify($data['company'] . '-' . ($index + 1)))
                ->setLegalName($data['company'] . ' SASU')
                ->setStructureType($this->faker->randomElement(['EI', 'EURL', 'SASU', 'SARL']))
                ->setSiren($this->faker->numerify('#########'))
                ->setSiret($this->faker->numerify('#########000##'))
                ->setVatNumber('FR' . $this->faker->numerify('##') . $this->faker->numerify('#########'))
                ->setAddress($this->faker->streetAddress())
                ->setAddressComplement($this->faker->optional()->secondaryAddress())
                ->setPostalCode($data['postal'])
                ->setCity($data['city'])
                ->setCountry('France')
                ->setLatitude($data['lat'])
                ->setLongitude($data['lng'])
                ->setGeohash($this->faker->regexify('[a-z0-9]{6}'))
                ->setPhonePublic($this->faker->numerify('05########'))
                ->setPhonePrivate($this->faker->numerify('06########'))
                ->setWebsite('https://www.' . $this->slugify($data['company']) . '.fr')
                ->setFacebookUrl('https://facebook.com/' . $this->slugify($data['company']))
                ->setInstagramUrl('https://instagram.com/' . $this->slugify($data['company']))
                ->setLinkedinUrl('https://linkedin.com/company/' . $this->slugify($data['company']))
                ->setShortDescription(sprintf('%s basé à %s, disponible pour des missions locales avec devis clair et intervention rapide.', $data['metier'], $data['city']))
                ->setLongDescription(sprintf('%s accompagne particuliers et professionnels de %s pour des prestations fiables, pédagogiques et soignées. Chaque mission est préparée avec un cadre de prix transparent et un suivi sérieux.', $data['company'], $data['city']))
                ->setDescription(sprintf('Profil professionnel complet pour %s.', $data['company']))
                ->setMetier($data['metier'])
                ->setExperience($this->faker->numberBetween(4, 18) . ' ans d’expérience')
                ->setProfileStatus(PrestataireProfileStatusEnum::ACTIVE)
                ->setVerificationStatus($this->faker->randomElement([
                    VerificationStatusEnum::COMPANY_VERIFIED,
                    VerificationStatusEnum::DOCUMENTS_VERIFIED,
                    VerificationStatusEnum::MANUALLY_VERIFIED,
                ]))
                ->setDocumentVerificationStatus($this->faker->randomElement([
                    DocumentVerificationStatusEnum::VERIFIED,
                    DocumentVerificationStatusEnum::PENDING_REVIEW,
                    DocumentVerificationStatusEnum::VERIFIED,
                ]))
                ->setCompletionScore($this->faker->numberBetween(82, 100))
                ->setAverageRating($this->decimal(4.10, 5.00))
                ->setReviewsCount($this->faker->numberBetween(7, 96))
                ->setResponseTimeMinutes($this->faker->numberBetween(20, 180))
                ->setIsFeatured($index < 5)
                ->setFeaturedUntil($index < 5 ? $this->randomDateTimeImmutable('+5 days', '+30 days') : null)
                ->setSearchVisibility($this->faker->randomElement([
                    SearchVisibilityEnum::NORMAL,
                    SearchVisibilityEnum::BOOSTED,
                    SearchVisibilityEnum::PREMIUM,
                ]))
                ->setVerifiedAt($this->randomDateTimeImmutable('-12 months', '-2 months'))
                ->setCreatedAt($createdAt)
                ->setUpdatedAt($this->randomDateTimeImmutable('-60 days'))
                ->setCompanyLastVerificationAt($this->randomDateTimeImmutable('-8 months', '-1 month'))
                ->setCompanyVerificationSource('fixtures_france')
                ->setCompanyVerificationNote('Entreprise vérifiée automatiquement dans le jeu de données de démonstration.');

            $this->attachRemoteImage(
                $profile,
                'setLogoFile',
                sprintf('https://picsum.photos/300/300?random=logo-%d', $index + 1)
            );

            $this->attachRemoteImage(
                $profile,
                'setCoverImageFile',
                sprintf('https://picsum.photos/1200/400?random=cover-%d', $index + 1)
            );

            $manager->persist($profile);
            $this->addReference(sprintf('prestataire_profile_%d', $index + 1), $profile);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }
}
