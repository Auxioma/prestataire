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

use App\Entity\PrestataireProfile;
use App\Enum\PrestataireProfileStatusEnum;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class PrestataireProfileCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return PrestataireProfile::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        yield AssociationField::new('account', 'Utilisateur');

        yield TextField::new('companyName', 'Nom de l\'entreprise');
        yield TextField::new('siret', 'Numéro SIRET');

        yield ChoiceField::new('ProfileStatus', 'Statut du Profil')
            ->setChoices(PrestataireProfileStatusEnum::cases())
            ->setFormTypeOption('class', PrestataireProfileStatusEnum::class)
            ->renderAsBadges([
                'DRAFT' => 'warning',
                'PENDING_VALIDATION' => 'primary',
                'ACTIVE' => 'success',
                'REFUSED' => 'danger',
                'SUSPENDED' => 'dark',
            ]);
    }
}
