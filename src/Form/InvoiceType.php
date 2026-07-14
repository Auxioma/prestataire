<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Invoice;
use App\Enum\InvoiceSourceTypeEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Vich\UploaderBundle\Form\Type\VichFileType;

final class InvoiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var InvoiceSourceTypeEnum $internalSourceType */
        $internalSourceType = $options['internal_source_type'];

        $builder
            ->add('sourceType', ChoiceType::class, [
                'label' => 'Mode de facture',
                'choices' => [
                    $internalSourceType->getLabel() => $internalSourceType,
                    'Facture PDF importée depuis votre logiciel' => InvoiceSourceTypeEnum::EXTERNAL_IMPORT,
                ],
                'choice_attr' => static function (?InvoiceSourceTypeEnum $choice): array {
                    return [
                        'data-invoice-source-type-value' => $choice?->value ?? '',
                        'onchange' => 'toggleInvoiceSourceMode()',
                    ];
                },
                'expanded' => true,
                'multiple' => false,
            ])
            ->add('dueAt', DateType::class, [
                'label' => 'Date d’échéance',
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                ],
            ])
            ->add('terms', TextareaType::class, [
                'label' => 'Conditions de règlement',
                'required' => false,
                'attr' => [
                    'rows' => 5,
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
                'label' => 'PDF externe de la facture',
                'required' => false,
                'allow_delete' => true,
                'delete_label' => 'Supprimer le PDF importé',
                'download_uri' => false,
                'asset_helper' => false,
                'constraints' => [
                    new File(
                        maxSize: '10M',
                        mimeTypes: ['application/pdf'],
                        mimeTypesMessage: 'Seuls les fichiers PDF sont autorisés.',
                        maxSizeMessage: 'Le fichier ne doit pas dépasser 10 Mo.',
                    ),
                ],
                'help' => 'Si vous choisissez une facture PDF externe, ce document remplacera la facture générée dans TrouveMoi.',
            ])
            ->add('items', CollectionType::class, [
                'label' => 'Lignes de facture',
                'entry_type' => InvoiceItemType::class,
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
            'data_class' => Invoice::class,
            'internal_source_type' => InvoiceSourceTypeEnum::GENERATED_FROM_QUOTE,
        ]);

        $resolver->setAllowedTypes('internal_source_type', InvoiceSourceTypeEnum::class);
    }
}
