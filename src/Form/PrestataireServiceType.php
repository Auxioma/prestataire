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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PrestataireServiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('pricingType', ChoiceType::class, [
                'label' => 'Mode tarifaire',
                'required' => false,
                'choices' => [
                    'Prix affiché' => 'fixed',
                    'Sur devis' => 'quote',
                ],
                'placeholder' => 'Choisir un mode tarifaire',
            ])
            ->add('prixCatalogue', MoneyType::class, [
                'label' => 'Votre prix',
                'currency' => 'EUR',
                'required' => false,
                'empty_data' => '',
            ])
            ->add('tauxReduction', NumberType::class, [
                'label' => 'Réduction (%)',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PrestataireService::class,
        ]);
    }
}