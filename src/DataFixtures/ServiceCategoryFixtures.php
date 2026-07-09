<?php

namespace App\DataFixtures;

use App\Entity\ServiceCategory;
use Doctrine\Persistence\ObjectManager;

class ServiceCategoryFixtures extends BaseFixture
{
    /**
     * @var array<int, array{name: string, icon: string, color: string, children: list<array{name: string, icon: string}>}>
     */
    private const CATEGORIES = [
        ['name' => 'Bâtiment et travaux', 'icon' => 'fa-house', 'color' => '0d6efd', 'children' => [['name' => 'Plomberie', 'icon' => 'fa-faucet-drip'], ['name' => 'Électricité', 'icon' => 'fa-bolt']]],
        ['name' => 'Jardin et extérieur', 'icon' => 'fa-tree', 'color' => '198754', 'children' => [['name' => 'Entretien des espaces verts', 'icon' => 'fa-leaf'], ['name' => 'Aménagement extérieur', 'icon' => 'fa-seedling']]],
        ['name' => 'Informatique et web', 'icon' => 'fa-laptop', 'color' => '0dcaf0', 'children' => [['name' => 'Assistance informatique', 'icon' => 'fa-computer'], ['name' => 'Développement web', 'icon' => 'fa-code']]],
        ['name' => 'Services à la personne', 'icon' => 'fa-heart', 'color' => 'd63384', 'children' => [['name' => 'Ménage et entretien', 'icon' => 'fa-broom'], ['name' => 'Cours particuliers', 'icon' => 'fa-graduation-cap']]],
        ['name' => 'Beauté et bien-être', 'icon' => 'fa-spa', 'color' => 'fd7e14', 'children' => [['name' => 'Coiffure et esthétique', 'icon' => 'fa-scissors'], ['name' => 'Coaching bien-être', 'icon' => 'fa-hand-holding-heart']]],
    ];

    public function load(ObjectManager $manager): void
    {
        $position = 1;
        foreach (self::CATEGORIES as $groupIndex => $categoryData) {
            $parent = (new ServiceCategory())
                ->setName($categoryData['name'])
                ->setSlug($this->slugify($categoryData['name']))
                ->setDescription(sprintf('Prestations professionnelles autour de %s.', mb_strtolower($categoryData['name'])))
                ->setIcon($categoryData['icon'])
                ->setColor($categoryData['color'])
                ->setImage(sprintf('categories/%s.jpg', $this->slugify($categoryData['name'])))
                ->setSeoTitle($categoryData['name'] . ' | TrouveMoi')
                ->setSeoDescription('Trouvez rapidement un professionnel qualifié près de chez vous.')
                ->setPosition($position++)
                ->setCreatedAt($this->randomDateTimeImmutable('-2 years', '-10 months'))
                ->setIsActive(true);
            $manager->persist($parent);
            $this->addReference(sprintf('service_category_parent_%d', $groupIndex + 1), $parent);

            foreach ($categoryData['children'] as $childIndex => $childData) {
                $child = (new ServiceCategory())
                    ->setName($childData['name'])
                    ->setSlug($this->slugify($childData['name']))
                    ->setDescription(sprintf('Sous-catégorie dédiée à %s.', mb_strtolower($childData['name'])))
                    ->setIcon($childData['icon'])
                    ->setColor($categoryData['color'])
                    ->setImage(sprintf('categories/%s.jpg', $this->slugify($childData['name'])))
                    ->setSeoTitle($childData['name'] . ' | TrouveMoi')
                    ->setSeoDescription('Comparez des profils vérifiés et des prestations adaptées à votre besoin.')
                    ->setPosition($childIndex + 1)
                    ->setParent($parent)
                    ->setCreatedAt($this->randomDateTimeImmutable('-2 years', '-10 months'))
                    ->setIsActive(true);
                $manager->persist($child);
                $this->addReference(sprintf('service_category_child_%d', (($groupIndex) * 2) + $childIndex + 1), $child);
            }
        }

        $manager->flush();
    }
}
