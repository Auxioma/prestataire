<?php

namespace App\Form;

use App\Entity\QuoteProposal;
use App\Enum\QuoteProposalDocumentModeEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Vich\UploaderBundle\Form\Type\VichFileType;

class QuoteProposalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('documentMode', ChoiceType::class, [
                'label' => 'Choisissez la manière dont vous voulez ajouter le devis:',
                'choices' => [
                    'Devis généré grâce à la plateforme' => QuoteProposalDocumentModeEnum::PLATFORM,
                    'Devis PDF externe fourni par le prestataire' => QuoteProposalDocumentModeEnum::EXTERNAL_PDF,
                ],
                'choice_attr' => static function (?QuoteProposalDocumentModeEnum $choice): array {
                    return [
                        'data-document-mode-value' => $choice?->value ?? '',
                        'onchange' => 'toggleQuoteProposalDocumentMode()',
                    ];
                },
                'expanded' => true,
                'multiple' => false,
            ])
            ->add('title', TextType::class, [
                'label' => 'Titre du devis',
                'required' => true,
            ])
            ->add('introMessage', TextareaType::class, [
                'label' => 'Message d’introduction',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                ],
            ])
            ->add('validUntil', DateType::class, [
                'label' => 'Valable jusqu’au',
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime',
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                ],
            ])
            ->add('terms', TextareaType::class, [
                'label' => 'Conditions',
                'required' => false,
                'attr' => [
                    'rows' => 6,
                ],
            ])
            ->add('latePaymentPenaltyTerms', TextareaType::class, [
                'label' => 'Mention pénalités de retard',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Ex. pénalités exigibles en cas de retard de paiement au taux légal en vigueur majoré de X points.',
                ],
            ])
            ->add('fixedRecoveryCompensationTerms', TextareaType::class, [
                'label' => 'Mention indemnité forfaitaire de recouvrement',
                'required' => false,
                'attr' => [
                    'rows' => 2,
                    'placeholder' => 'Ex. indemnité forfaitaire de 40 EUR pour frais de recouvrement en cas de retard de paiement.',
                ],
            ])
            ->add('earlyPaymentDiscountTerms', TextareaType::class, [
                'label' => 'Mention escompte',
                'required' => false,
                'attr' => [
                    'rows' => 2,
                    'placeholder' => 'Ex. pas d’escompte pour paiement anticipé.',
                ],
            ])
            ->add('externalPdfFile', VichFileType::class, [
                'label' => 'PDF externe du devis',
                'required' => false,
                'allow_delete' => true,
                'delete_label' => 'Supprimer le PDF importé',
                'download_uri' => false,
                'asset_helper' => false,
                'attr' => [
                    'accept' => 'application/pdf,.pdf',
                ],
                'constraints' => [
                    new File(
                        maxSize: '10M',
                        mimeTypes: ['application/pdf'],
                        mimeTypesMessage: 'Seuls les fichiers PDF sont autorisés.',
                        maxSizeMessage: 'Le fichier ne doit pas dépasser 10 Mo.',
                    ),
                ],
            ])
            ->add('items', CollectionType::class, [
                'label' => 'Lignes du devis',
                'entry_type' => QuoteProposalItemType::class,
                'entry_options' => [
                    'label' => false,
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => QuoteProposal::class,
        ]);
    }
}
