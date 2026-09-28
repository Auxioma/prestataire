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

namespace App\Prestataire\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class PrestataireSiretRegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('siret', TextType::class, [
            'label' => 'Numéro SIRET',
            'constraints' => [new Assert\NotBlank(message: 'Veuillez renseigner votre numéro SIRET.'), new Assert\Regex(pattern: '/^\d{14}$/', message: 'Le SIRET doit contenir exactement 14 chiffres.')],
            'attr' => ['inputmode' => 'numeric', 'autocomplete' => 'off', 'placeholder' => 'Votre numéro SIRET à 14 chiffres'],
        ]);
        $builder->get('siret')->addModelTransformer(new CallbackTransformer(
            static fn ($value) => $value,
            static fn ($value) => preg_replace('/\s+/', '', (string) $value),
        ));
    }
}
