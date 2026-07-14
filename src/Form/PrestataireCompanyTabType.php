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
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;
use Vich\UploaderBundle\Form\Type\VichImageType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class PrestataireCompanyTabType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('companyName', TextType::class, [
                'label' => 'Nom de l\'entreprise / Enseigne',
                'required' => true,
                'attr' => ['placeholder' => 'ex: Martin & Co Rénovation'],
            ])
            ->add('legalName', TextType::class, [
                'label' => 'Raison sociale',
                'required' => false,
                'attr' => ['placeholder' => 'ex: SARL Martin Électricité'],
            ])
            ->add('structureType', TextType::class, [
                'label' => 'Forme juridique',
                'required' => false,
                'attr' => ['placeholder' => 'ex: SAS, SARL, Auto-entrepreneur...'],
            ])
            ->add('siret', TextType::class, [
                'label' => 'Numéro SIRET',
                'required' => false,
                'attr' => [
                    'maxlength' => 14,
                    'minlength' => 14,
                    'inputmode' => 'numeric',
                    'pattern' => '[0-9]{14}',
                    'placeholder' => 'Ex : 12345678901234',
                    'autocomplete' => 'off',
                ],
            ])
            ->add('siren', TextType::class, [
                'label' => 'Numéro SIREN',
                'required' => false,
                'attr' => [
                    'maxlength' => 9,
                    'minlength' => 9,
                    'inputmode' => 'numeric',
                    'pattern' => '[0-9]{9}',
                    'placeholder' => 'Ex : 123456789',
                    'autocomplete' => 'off',
                ],
            ])
            ->add('vatNumber', TextType::class, [
                'label' => 'Numéro de TVA Intracommunautaire',
                'required' => false,
                'attr' => ['placeholder' => 'ex: FR 12 123456789'],
            ])
            ->add('logoFile', VichImageType::class, [
                'label' => 'Logo de l\'entreprise',
                'required' => false,
                'allow_delete' => true,
                'download_uri' => false,
                'image_uri' => false,
                'constraints' => [
                    new Image(
                        maxSize: '2M',
                        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                        mimeTypesMessage: 'Veuillez téléverser un format d\'image valide (JPEG, PNG, WEBP).'
                    ),
                ],
            ])
            ->add('coverImageFile', VichImageType::class, [
                'label' => 'Image de couverture (Bannière)',
                'required' => false,
                'allow_delete' => true,
                'download_uri' => false,
                'image_uri' => false,
                'constraints' => [
                    new Image(
                        maxSize: '3M',
                        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                        mimeTypesMessage: 'Veuillez téléverser un format d\'image valide (JPEG, PNG, WEBP).'
                    ),
                ],
            ])
            ->add('signatureImageFile', VichImageType::class, [
                'label' => 'Image de signature',
                'required' => false,
                'allow_delete' => true,
                'download_uri' => false,
                'image_uri' => false,
                'constraints' => [
                    new Image(
                        maxSize: '2M',
                        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                        mimeTypesMessage: 'Veuillez téléverser un format d\'image valide (JPEG, PNG, WEBP).'
                    ),
                ],
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresse',
                'required' => false,
                'attr' => ['placeholder' => 'ex: 15 Rue de la Marne'],
            ])
            ->add('addressComplement', TextType::class, [
                'label' => 'Complément d\'adresse',
                'required' => false,
                'attr' => ['placeholder' => 'ex: Batiment B, Escalier 2...'],
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'Code postal',
                'required' => false,
                'attr' => ['placeholder' => 'ex: 33500'],
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'required' => false,
                'attr' => ['placeholder' => 'ex: Libourne'],
            ])
            ->add('country', TextType::class, [
                'label' => 'Pays',
                'required' => false,
                'attr' => ['placeholder' => 'France'],
            ])
            ->add('phonePublic', TextType::class, [
                'label' => 'Téléphone public (affiché sur le site)',
                'required' => false,
                'attr' => ['placeholder' => 'ex: 05 57 00 00 00'],
            ])
            ->add('phonePrivate', TextType::class, [
                'label' => 'Téléphone privé (commandes applicatives)',
                'required' => false,
                'attr' => ['placeholder' => 'ex: 06 00 00 00 00'],
            ])
            ->add('verifyCompany', SubmitType::class, [
                'label' => 'Vérifier mon entreprise',
                'attr' => [
                    'class' => 'btn btn-outline-primary',
                ],
            ]);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $data = $event->getData();

            if (!is_array($data)) {
                return;
            }

            if (isset($data['siret']) && is_string($data['siret'])) {
                $data['siret'] = preg_replace('/\D+/', '', $data['siret']);
                $data['siret'] = mb_substr($data['siret'], 0, 14);
            }

            if (isset($data['siren']) && is_string($data['siren'])) {
                $data['siren'] = preg_replace('/\D+/', '', $data['siren']);
                $data['siren'] = mb_substr($data['siren'], 0, 9);
            }

            $event->setData($data);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PrestataireProfile::class,
        ]);
    }
}
