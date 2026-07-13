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
                'label' => 'Mode de document',
                'choices' => [
                    'Devis généré par la plateforme' => QuoteProposalDocumentModeEnum::PLATFORM,
                    'PDF externe fourni par le prestataire' => QuoteProposalDocumentModeEnum::EXTERNAL_PDF,
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
            ->add('externalPdfFile', VichFileType::class, [
                'label' => 'PDF externe du devis',
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
                'help' => 'Si vous ajoutez un PDF de devis externe, il remplacera le devis généré par la plateforme pour cette proposition.',
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
