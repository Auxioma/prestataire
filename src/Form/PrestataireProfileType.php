<?php

namespace App\Form;

use App\Entity\PrestataireProfile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;

class PrestataireProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // --- ONGLET : MON PROFIL ---
            ->add('metier', TextType::class, [
                'label' => 'Votre métier / Spécialité',
                'required' => true,
                'attr' => ['placeholder' => 'ex: Électricien, Développeur Web, Plombier...']
            ])
            ->add('experience', TextType::class, [
                'label' => 'Expérience',
                'required' => false,
                'attr' => ['placeholder' => 'ex: 5 ans d\'expérience, Expert...']
            ])
            ->add('shortDescription', TextareaType::class, [
                'label' => 'Phrase d\'accroche (Slogan)',
                'required' => false,
                'attr' => [
                    'rows' => 2,
                    'placeholder' => 'Une phrase percutante qui apparaîtra sur les résultats de recherche (ex: Vos travaux de rénovation en toute sérénité).'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Présentation complète de votre entreprise',
                'required' => false,
                'attr' => [
                    'rows' => 6,
                    'placeholder' => 'Décrivez en détail votre savoir-faire, vos spécialités, vos valeurs et ce qui vous distingue...'
                ]
            ])

            // --- IMAGES DU PROFIL PROFESSIONNEL ---
            ->add('logo', FileType::class, [
                'label' => 'Logo de l\'entreprise',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Image(
                        maxSize: '2M',
                        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                        mimeTypesMessage: 'Veuillez téléverser un format d\'image valide (JPEG, PNG, WEBP).'
                    )
                ],
            ])
            ->add('coverImage', FileType::class, [
                'label' => 'Image de couverture (Bannière)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Image(
                        maxSize: '3M',
                        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                        mimeTypesMessage: 'Veuillez téléverser un format d\'image valide (JPEG, PNG, WEBP).'
                    )
                ],
            ])

            // --- ONGLET : MON ENTREPRISE (Administratif) ---
            ->add('companyName', TextType::class, [
                'label' => 'Nom de l\'entreprise / Enseigne',
                'required' => true,
                'attr' => ['placeholder' => 'ex: Martin & Co Rénovation']
            ])
            ->add('legalName', TextType::class, [
                'label' => 'Raison sociale',
                'required' => false,
                'attr' => ['placeholder' => 'ex: SARL Martin Électricité']
            ])
            ->add('structureType', TextType::class, [
                'label' => 'Forme juridique',
                'required' => false,
                'attr' => ['placeholder' => 'ex: SAS, SARL, Auto-entrepreneur...']
            ])
            ->add('siren', TextType::class, [
                'label' => 'Numéro SIREN',
                'required' => false,
                'attr' => ['placeholder' => 'ex: 123 456 789']
            ])
            ->add('siret', TextType::class, [
                'label' => 'Numéro SIRET',
                'required' => false,
                'attr' => ['placeholder' => 'ex: 123 456 789 00012']
            ])
            ->add('vatNumber', TextType::class, [
                'label' => 'Numéro de TVA Intracommunautaire',
                'required' => false,
                'attr' => ['placeholder' => 'ex: FR 12 123456789']
            ])

            // --- COORDONNÉES ET CONTACT ---
            ->add('address', TextType::class, [
                'label' => 'Adresse',
                'attr' => ['placeholder' => 'ex: 15 Rue de la Marne']
            ])
            ->add('addressComplement', TextType::class, [
                'label' => 'Complément d\'adresse',
                'required' => false,
                'attr' => ['placeholder' => 'ex: Batiment B, Escalier 2...']
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'Code postal',
                'attr' => ['placeholder' => 'ex: 33500']
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'attr' => ['placeholder' => 'ex: Libourne']
            ])
            ->add('country', TextType::class, [
                'label' => 'Pays', 
                'data' => 'France',
                'attr' => ['placeholder' => 'France']
            ])
            ->add('phonePublic', TextType::class, [
                'label' => 'Téléphone public (affiché sur le site)',
                'required' => false,
                'attr' => ['placeholder' => 'ex: 05 57 00 00 00']
            ])
            ->add('phonePrivate', TextType::class, [
                'label' => 'Téléphone privé (commandes applicatives)',
                'required' => false,
                'attr' => ['placeholder' => 'ex: 06 00 00 00 00']
            ])

            // --- LIENS & RÉSEAUX ---
            ->add('website', UrlType::class, [
                'label' => 'Site Internet',
                'required' => false,
                'attr' => ['placeholder' => 'https://www.mon-entreprise.fr']
            ])
            ->add('facebookUrl', UrlType::class, [
                'label' => 'Lien Facebook',
                'required' => false,
                'attr' => ['placeholder' => 'https://facebook.com/nomentreprise']
            ])
            ->add('instagramUrl', UrlType::class, [
                'label' => 'Lien Instagram',
                'required' => false,
                'attr' => ['placeholder' => 'https://instagram.com/nomentreprise']
            ])
            ->add('linkedinUrl', UrlType::class, [
                'label' => 'Lien LinkedIn',
                'required' => false,
                'attr' => ['placeholder' => 'https://linkedin.com/company/nomentreprise']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PrestataireProfile::class,
        ]);
    }
}