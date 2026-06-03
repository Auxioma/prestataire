<?php

namespace App\Controller\Admin;

use App\Entity\PrestataireProfile;
use App\Enum\PrestataireProfileStatusEnum;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;

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
