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

namespace App\Controller\Admin;

use App\Entity\ClientProfile;
use App\Enum\ClientTypeEnum;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ClientProfileCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ClientProfile::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Client')
            ->setEntityLabelInPlural('Clients')
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        yield AssociationField::new('account', 'Compte utilisateur');

        yield TextField::new('account.firstName', 'Prénom')->hideOnForm();
        yield TextField::new('account.lastName', 'Nom')->hideOnForm();
        yield TextField::new('account.email', 'Email')->hideOnForm();

        yield ChoiceField::new('type', 'Type de client')
            ->setChoices([
                'Particulier' => ClientTypeEnum::PARTICULIER,
                'Professionnel' => ClientTypeEnum::PROFESSIONNEL,
            ])
            ->renderAsBadges();

        yield TextField::new('companyName', 'Entreprise')->hideOnIndex();
        yield TextField::new('defaultCity', 'Ville principale');
    }
}