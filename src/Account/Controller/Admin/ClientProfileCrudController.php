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

namespace App\Account\Controller\Admin;

use App\Account\Entity\ClientProfile;
use App\Account\Enum\ClientTypeEnum;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * Gère les actions liées à client profile  c r u d.
 */
class ClientProfileCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ClientProfile::class;
    }

    /**
     * Traite l’action "configureCrud" du contrôleur Client Profile  C R U D.
     */
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Client')
            ->setEntityLabelInPlural('Clients')
            ->setDefaultSort(['id' => 'DESC']);
    }

    /**
     * Traite l’action "configureFields" du contrôleur Client Profile  C R U D.
     */
    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        yield AssociationField::new('account', 'Compte utilisateur')
            ->setHelp('Compte principal lié à ce profil client.');

        yield TextField::new('account.firstName', 'Prénom')
            ->hideOnForm()
            ->setHelp('Prénom du client lié au compte.');

        yield TextField::new('account.lastName', 'Nom')
            ->hideOnForm()
            ->setHelp('Nom du client lié au compte.');

        yield TextField::new('account.email', 'Email')
            ->hideOnForm()
            ->setHelp('Adresse email du compte client.');

        yield ChoiceField::new('type', 'Type de client')
            ->setChoices([
                'Particulier' => ClientTypeEnum::PARTICULIER,
                'Professionnel' => ClientTypeEnum::PROFESSIONNEL,
            ])
            ->renderAsBadges()
            ->setHelp('Permet de distinguer un particulier d’un client professionnel.');

        yield TextField::new('companyName', 'Entreprise')
            ->hideOnIndex()
            ->setHelp('Nom de l’entreprise si le client est un professionnel.')
            ->setFormTypeOption('attr', [
                'placeholder' => 'Exemple : Agence Océane',
            ]);

        yield TextField::new('defaultCity', 'Ville principale')
            ->setHelp('Ville principale utilisée pour les recherches ou les demandes du client.')
            ->setFormTypeOption('attr', [
                'placeholder' => 'Exemple : Bordeaux',
            ]);
    }
}
