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
        
        // Liaison avec le compte global (grâce au __toString() sur User, l'email s'affichera tout seul)
        yield AssociationField::new('account', 'Utilisateur global');

        // Récupération du Nom et Prénom depuis l'entité User liée via la notation pointée !
        yield TextField::new('account.firstName', 'Prénom du Client')->hideOnForm();
        yield TextField::new('account.lastName', 'Nom du Client')->hideOnForm();

        // Informations propres au profil Client
        yield TextField::new('companyName', "Nom de l'entreprise")->hideOnIndex();
        yield TextField::new('defaultCity', 'Ville principale');

        // Correction : On cible la propriété 'type' et non 'clientType'
        yield ChoiceField::new('type', 'Type de Client')
            ->setChoices(ClientTypeEnum::cases())
            ->setFormTypeOption('class', ClientTypeEnum::class)
            ->renderAsBadges([
                'PARTICULIER' => 'info',
                'PROFESSIONNEL' => 'primary',
            ]);
    }
}