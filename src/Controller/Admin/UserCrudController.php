<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Enum\UserStatusEnum;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        // On cache l'ID sur les formulaires de création/édition, mais on le garde sur la liste
        yield IdField::new('id')->hideOnForm();
        
        yield EmailField::new('email', 'Adresse Email');
        
        // Affichage des rôles (EasyAdmin gère ça très bien sous forme de liste de tags)
        yield ArrayField::new('roles', 'Rôles');

        // La correction pour ton Enum : on force l'utilisation d'un ChoiceField alimenté par l'Enum
        yield ChoiceField::new('status', 'Statut du Compte')
            ->setChoices(UserStatusEnum::cases())
            ->renderAsBadges();
    }
}