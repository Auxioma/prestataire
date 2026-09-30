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

namespace App\Website\Dto;

use App\Website\Enum\ContactHelpCategoryEnum;
use Symfony\Component\Validator\Constraints as Assert;

final class ContactRequest
{
    #[Assert\NotBlank(message: 'Veuillez renseigner votre adresse e-mail.')]
    #[Assert\Email(message: 'Veuillez saisir une adresse e-mail valide.')]
    #[Assert\Length(max: 180, maxMessage: 'L’adresse e-mail ne peut pas dépasser {{ limit }} caractères.')]
    public ?string $email = null;

    #[Assert\NotNull(message: 'Veuillez sélectionner une catégorie.')]
    public ?ContactHelpCategoryEnum $category = null;

    #[Assert\NotBlank(message: 'Veuillez décrire votre demande.')]
    #[Assert\Length(
        min: 10,
        max: 300,
        minMessage: 'Votre message doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Votre message ne peut pas dépasser {{ limit }} caractères.'
    )]
    public ?string $message = null;
}
