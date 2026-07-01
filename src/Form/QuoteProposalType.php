<?php

namespace App\Form;

use App\Entity\QuoteProposal;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuoteProposalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
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