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

use App\Entity\PrestataireProfile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PrestatairePublicProfileTabType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('metier', TextType::class, [
                'label' => 'Votre métier / Spécialité',
                'required' => true,
                'attr' => [
                    'placeholder' => 'ex: Électricien, Développeur Web, Plombier...',
                ],
            ])
            ->add('experience', TextType::class, [
                'label' => 'Expérience',
                'required' => false,
                'attr' => [
                    'placeholder' => 'ex: 5 ans d\'expérience, Expert...',
                ],
            ])
            ->add('shortDescription', TextareaType::class, [
                'label' => 'Phrase d\'accroche (Slogan)',
                'required' => false,
                'attr' => [
                    'rows' => 2,
                    'placeholder' => 'Une phrase percutante qui apparaîtra sur les résultats de recherche.',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Présentation complète de votre entreprise',
                'required' => false,
                'attr' => [
                    'rows' => 6,
                    'placeholder' => 'Décrivez en détail votre savoir-faire, vos spécialités, vos valeurs et ce qui vous distingue...',
                ],
            ])
            ->add('website', UrlType::class, [
                'label' => 'Site Internet',
                'required' => false,
                'attr' => [
                    'placeholder' => 'https://www.mon-entreprise.fr',
                ],
            ])
            ->add('facebookUrl', UrlType::class, [
                'label' => 'Lien Facebook',
                'required' => false,
                'attr' => [
                    'placeholder' => 'https://facebook.com/nomentreprise',
                ],
            ])
            ->add('instagramUrl', UrlType::class, [
                'label' => 'Lien Instagram',
                'required' => false,
                'attr' => [
                    'placeholder' => 'https://instagram.com/nomentreprise',
                ],
            ])
            ->add('linkedinUrl', UrlType::class, [
                'label' => 'Lien LinkedIn',
                'required' => false,
                'attr' => [
                    'placeholder' => 'https://linkedin.com/company/nomentreprise',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PrestataireProfile::class,
            'csrf_protection' => false,
        ]);
    }
}
