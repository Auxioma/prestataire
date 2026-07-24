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
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PrestataireServiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('tauxReduction', NumberType::class, [
                'label' => 'Réduction (%)',
                'required' => false,
                'disabled' => !$options['can_edit_reduction'],
                'help' => $options['can_edit_reduction']
                    ? 'La réduction s’applique au tarif défini dans la proposition de prestation.'
                    : 'La réduction n’est disponible que si une proposition de prestation contient un tarif affichable.',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PrestataireService::class,
            'can_edit_reduction' => true,
        ]);

        $resolver->setAllowedTypes('can_edit_reduction', 'bool');
    }
}
