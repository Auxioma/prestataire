<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 AUXIOMA Web Agency.
 *
 * Projet : TrouveMoi
 *
 * Tous droits réservés.
 *
 * Ce fichier fait partie du projet TrouveMoi,
 * développé par AUXIOMA Web Agency.
 *
 * Toute reproduction, modification, distribution ou utilisation,
 * totale ou partielle, sans autorisation écrite préalable,
 * est strictement interdite.
 */

namespace App\Website\Form;

use App\Website\Dto\ContactRequest;
use App\Website\Enum\ContactHelpCategoryEnum;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ContactRequestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Votre adresse e-mail',
                'attr' => [
                    'autocomplete' => 'email',
                    'maxlength' => 180,
                    'placeholder' => 'vous@exemple.fr',
                ],
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Catégorie d’aide',
                'choices' => array_combine(
                    array_map(
                        static fn (ContactHelpCategoryEnum $category): string => $category->getLabel(),
                        ContactHelpCategoryEnum::cases()
                    ),
                    ContactHelpCategoryEnum::cases()
                ),
                'choice_value' => static fn (?ContactHelpCategoryEnum $category): ?string => $category?->value,
                'placeholder' => 'Sélectionnez une catégorie',
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Votre question',
                'help' => 'Entre 10 et 300 caractères.',
                'attr' => [
                    'rows' => 5,
                    'minlength' => 10,
                    'maxlength' => 300,
                    'placeholder' => 'Décrivez votre question ou le problème rencontré.',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContactRequest::class,
            'csrf_token_id' => 'contact_request',
        ]);
    }
}
