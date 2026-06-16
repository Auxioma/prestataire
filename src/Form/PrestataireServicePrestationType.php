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

namespace App\Form;

use App\Entity\PrestataireService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PrestataireServicePrestationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('isActive', CheckboxType::class, [
                'label' => 'Prestation active',
                'required' => false,
            ])
            ->add('title', TextType::class, [
                'label' => 'Titre de la prestation',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Exemple : Dépannage fuite d’eau en urgence',
                    'maxlength' => 255,
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description détaillée',
                'required' => false,
                'attr' => [
                    'rows' => 6,
                    'placeholder' => 'Décrivez précisément ce qui est inclus dans cette prestation.',
                ],
            ])
            ->add('priceFrom', MoneyType::class, [
                'label' => 'Prix à partir de',
                'required' => false,
                'currency' => 'EUR',
                'html5' => true,
                'attr' => [
                    'step' => '0.01',
                    'min' => 0,
                ],
            ])
            ->add('priceTo', MoneyType::class, [
                'label' => 'Prix jusqu’à',
                'required' => false,
                'currency' => 'EUR',
                'html5' => true,
                'attr' => [
                    'step' => '0.01',
                    'min' => 0,
                ],
            ])
            ->add('priceUnit', ChoiceType::class, [
                'label' => 'Unité de prix',
                'required' => false,
                'placeholder' => 'Choisir une unité',
                'choices' => [
                    'Forfait' => 'forfait',
                    'Heure' => 'heure',
                    'Jour' => 'jour',
                    'm²' => 'm2',
                    'ml' => 'ml',
                    'Intervention' => 'intervention',
                ],
            ])
            ->add('position', IntegerType::class, [
                'label' => 'Ordre d’affichage',
                'required' => false,
                'attr' => [
                    'min' => 0,
                    'placeholder' => '0',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PrestataireService::class,
        ]);
    }
}