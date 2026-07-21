<?php

namespace App\Form;

use App\Entity\ServiceCategory;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\RangeType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PrestataireBrowseFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var ServiceCategory|null $selectedCategory */
        $selectedCategory = $options['selected_category'];
        $subCategoryChoices = null !== $selectedCategory
            ? array_values(array_filter(
                $selectedCategory->getSubCategories()->toArray(),
                static fn (mixed $subCategory): bool => $subCategory instanceof ServiceCategory && true === $subCategory->isActive()
            ))
            : [];

        $builder
            ->add('query', SearchType::class, [
                'label' => 'Recherche',
                'required' => false,
                'trim' => true,
                'empty_data' => '',
                'attr' => [
                    'placeholder' => 'Entreprise, métier, prestation...',
                    'autocomplete' => 'off',
                ],
            ])
            ->add('location', TextType::class, [
                'label' => 'Où ?',
                'required' => false,
                'trim' => true,
                'empty_data' => '',
                'attr' => [
                    'placeholder' => 'Ville ou code postal...',
                    'autocomplete' => 'off',
                ],
            ])
            ->add('radiusKm', RangeType::class, [
                'label' => 'Rayon',
                'required' => false,
                'empty_data' => '25',
                'data' => 25,
                'attr' => [
                    'min' => 5,
                    'max' => 100,
                    'step' => 5,
                ],
            ])
            ->add('category', EntityType::class, [
                'class' => ServiceCategory::class,
                'label' => 'Catégorie',
                'required' => false,
                'placeholder' => 'Toutes les catégories',
                'choice_label' => 'name',
                'query_builder' => static function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->andWhere('c.parent IS NULL')
                        ->andWhere('c.isActive = :active')
                        ->setParameter('active', true)
                        ->orderBy('c.position', 'ASC')
                        ->addOrderBy('c.name', 'ASC');
                },
            ])
            ->add('subCategory', EntityType::class, [
                'class' => ServiceCategory::class,
                'label' => 'Sous-catégorie',
                'required' => false,
                'placeholder' => 'Toutes les sous-catégories',
                'choice_label' => 'name',
                'choices' => $subCategoryChoices,
                'disabled' => null === $selectedCategory,
            ])
            ->add('sort', ChoiceType::class, [
                'label' => 'Trier par',
                'required' => false,
                'placeholder' => false,
                'choices' => [
                    'Pertinence' => 'relevance',
                    'Note' => 'rating',
                    'Nombre d\'avis' => 'reviews',
                    'Ordre alphabétique' => 'alphabetical',
                ],
                'empty_data' => 'relevance',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'GET',
            'csrf_protection' => false,
            'selected_category' => null,
        ]);

        $resolver->setAllowedTypes('selected_category', ['null', ServiceCategory::class]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
