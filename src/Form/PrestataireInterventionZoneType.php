<?php

namespace App\Form;

use App\Entity\PrestataireInterventionZone;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PrestataireInterventionZoneType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex. Lyon',
                    'class' => 'form-control',
                ],
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'Code postal',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Ex. 69000',
                    'class' => 'form-control',
                ],
            ])
            ->add('radiusKm', IntegerType::class, [
                'label' => 'Rayon d’intervention (km)',
                'required' => false,
                'empty_data' => '20',
                'attr' => [
                    'min' => 1,
                    'max' => 200,
                    'placeholder' => 'Ex. 20',
                    'class' => 'form-control',
                ],
            ])
            ->add('isMainZone', CheckboxType::class, [
                'label' => 'Définir comme zone principale',
                'required' => false,
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Zone active',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PrestataireInterventionZone::class,
        ]);
    }
}
