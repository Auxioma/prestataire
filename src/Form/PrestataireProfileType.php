<?php

namespace App\Form;

use App\Entity\PrestataireProfile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PrestataireProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // --- ONGLET : MON PROFIL (Visuel maquette) ---
            ->add('metier', TextType::class, [
                'label' => 'Votre métier / Spécialité',
                'required' => true,
            ])
            ->add('experience', TextType::class, [
                'label' => 'Expérience (ex: 5 ans, Expert...)',
                'required' => false,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'À propos de vous / Présentation',
                'required' => false,
                'attr' => ['rows' => 5]
            ])

            // --- ONGLET : MON ENTREPRISE (Administratif) ---
            ->add('companyName', TextType::class, [
                'label' => 'Nom de l\'entreprise / Enseigne',
                'required' => true,
            ])
            ->add('legalName', TextType::class, [
                'label' => 'Raison sociale',
                'required' => false,
            ])
            ->add('structureType', TextType::class, [
                'label' => 'Forme juridique (SAS, SARL, Auto-entrepreneur...)',
                'required' => false,
            ])
            ->add('siren', TextType::class, [
                'label' => 'Numéro SIREN',
                'required' => false,
            ])
            ->add('siret', TextType::class, [
                'label' => 'Numéro SIRET',
                'required' => false,
            ])
            ->add('vatNumber', TextType::class, [
                'label' => 'Numéro de TVA Intracommunautaire',
                'required' => false,
            ])

            // --- COORDONNÉES ET CONTACT ---
            ->add('address', TextType::class, ['label' => 'Adresse'])
            ->add('addressComplement', TextType::class, ['label' => 'Complément d\'adresse', 'required' => false])
            ->add('postalCode', TextType::class, ['label' => 'Code postal'])
            ->add('city', TextType::class, ['label' => 'Ville'])
            ->add('country', TextType::class, ['label' => 'Pays', 'data' => 'France'])
            ->add('phonePublic', TextType::class, ['label' => 'Téléphone public (affiché sur le site)', 'required' => false])
            ->add('phonePrivate', TextType::class, ['label' => 'Téléphone privé (commandes applicatives)', 'required' => false])

            // --- LIENS & RÉSEAUX ---
            ->add('website', UrlType::class, ['label' => 'Site Internet', 'required' => false])
            ->add('facebookUrl', UrlType::class, ['label' => 'Lien Facebook', 'required' => false])
            ->add('instagramUrl', UrlType::class, ['label' => 'Lien Instagram', 'required' => false])
            ->add('linkedinUrl', UrlType::class, ['label' => 'Lien LinkedIn', 'required' => false])

            // --- IMAGES ---
            // ->add('logo', UrlType::class, ['label' => 'Logo', 'required' => false])
            // ->add('coverImage', UrlType::class, ['label' => 'Image de couverture', 'required' => false])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PrestataireProfile::class,
        ]);
    }
}
