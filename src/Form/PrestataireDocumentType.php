<?php

namespace App\Form;

use App\Entity\PrestataireDocument;
use App\Enum\PrestataireDocumentTypeEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class PrestataireDocumentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // type de document
            ->add('type', EnumType::class, [
                'class' => PrestataireDocumentTypeEnum::class,
                'label' => 'Type de document',
                'choice_label' => static fn (PrestataireDocumentTypeEnum $choice): string => match ($choice) {
                    PrestataireDocumentTypeEnum::KBIS => 'Extrait Kbis / Justificatif d’entreprise',
                    PrestataireDocumentTypeEnum::RC_PRO => 'Assurance RC Pro',
                    PrestataireDocumentTypeEnum::DECENNALE => 'Attestation décennale',
                    PrestataireDocumentTypeEnum::VIGILANCE => 'Attestation de vigilance',
                    PrestataireDocumentTypeEnum::IDENTITE => 'Pièce d’identité',
                    PrestataireDocumentTypeEnum::AUTRE => 'Autre document',
                },
                'placeholder' => 'Choisir un type',
            ])

            // fichier réel uploadé
            ->add('documentFile', FileType::class, [
                'label' => 'Fichier',
                'required' => true,
                'mapped' => true,
                'constraints' => [
                    new File(
                        maxSize : '8M',
                        mimeTypes : [
                            'application/pdf',
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        mimeTypesMessage : 'Veuillez envoyer un PDF, JPG, PNG ou WEBP.',
                    ),
                ],
            ])

            // visible au client
            ->add('isVisibleToClient', CheckboxType::class, [
                'label' => 'Rendre ce document visible au client concerné',
                'required' => false,
            ])

            // date d’émission
            ->add('issuedAt', DateType::class, [
                'label' => 'Date d’émission',
                'required' => false,
                'widget' => 'single_text',
            ])

            // date d’expiration
            ->add('expiresAt', DateType::class, [
                'label' => 'Date d’expiration',
                'required' => false,
                'widget' => 'single_text',
            ])

            // notes internes éventuelles
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Informations complémentaires sur ce document',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PrestataireDocument::class,
        ]);
    }
}