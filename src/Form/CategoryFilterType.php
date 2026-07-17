<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\RangeType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('query', SearchType::class, [
                'label' => 'Catégorie',
                'required' => false,
                'trim' => true,
                'empty_data' => '',
                'attr' => [
                    'placeholder' => 'Ex. plomberie, jardinage...',
                    'autocomplete' => 'off',
                ],
            ])
            ->add('location', TextType::class, [
                'label' => 'Localisation',
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
            ->add('sort', ChoiceType::class, [
                'label' => 'Trier par',
                'required' => false,
                'placeholder' => false,
                'choices' => [
                    'Nombre de prestataires' => 'providers',
                    'Ordre alphabétique' => 'alphabetical',
                    'Plus récentes' => 'recent',
                ],
                'empty_data' => 'providers',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
