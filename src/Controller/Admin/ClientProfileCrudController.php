<?php

namespace App\Controller\Admin;

use App\Entity\ClientProfile;
use App\Enum\ClientTypeEnum;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;

class ClientProfileCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ClientProfile::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        
        yield AssociationField::new('account', 'Utilisateur global');

        yield TextField::new('account.firstName', 'Prénom du Client')->hideOnForm();
        yield TextField::new('account.lastName', 'Nom du Client')->hideOnForm();

        yield TextField::new('companyName', "Nom de l'entreprise")->hideOnIndex();
        yield TextField::new('defaultCity', 'Ville principale');

        yield ChoiceField::new('type', 'Type de Client')
            ->setChoices(ClientTypeEnum::cases())
            ->setFormTypeOption('class', ClientTypeEnum::class)
            ->renderAsBadges([
                'PARTICULIER' => 'info',
                'PROFESSIONNEL' => 'primary',
            ]);
    }
}