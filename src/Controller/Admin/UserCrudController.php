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
        yield IdField::new('id')->hideOnForm();
        
        yield EmailField::new('email', 'Adresse Email');
        
        yield ArrayField::new('roles', 'Rôles');

        yield ChoiceField::new('status', 'Statut du Compte')
            ->setChoices(UserStatusEnum::cases())
            ->renderAsBadges();
    }
}