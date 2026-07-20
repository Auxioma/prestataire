<?php

namespace App\Controller\Admin;

use App\Entity\Subscription\SubscriptionPlan;
use App\Enum\SubscriptionPlanStatusEnum;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class SubscriptionPlanCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SubscriptionPlan::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Abonnement')
            ->setEntityLabelInPlural('Abonnements')
            ->setDefaultSort(['sortOrder' => 'ASC', 'name' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('code', 'Code interne');
        yield TextField::new('name', 'Nom');
        yield TextareaField::new('description', 'Description')->hideOnIndex();
        yield TextField::new('stripeProductId', 'Product ID Stripe')->hideOnIndex();
        yield IntegerField::new('monthlyCredits', 'Crédits mensuels');
        yield IntegerField::new('annualCredits', 'Crédits annuels');
        yield IntegerField::new('welcomeCredits', 'Crédits de bienvenue');
        yield BooleanField::new('quoteResponsesEnabled', 'Réponse aux devis active');
        yield BooleanField::new('instantMessagingEnabled', 'Messagerie instantanée active');
        yield IntegerField::new('sortOrder', 'Ordre');
        yield ChoiceField::new('status', 'Statut')
            ->setChoices([
                'Brouillon' => SubscriptionPlanStatusEnum::DRAFT,
                'Actif' => SubscriptionPlanStatusEnum::ACTIVE,
                'Archivé' => SubscriptionPlanStatusEnum::ARCHIVED,
            ])
            ->renderAsBadges();
    }
}
