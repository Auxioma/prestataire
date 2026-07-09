<?php

namespace App\Controller\Admin;

use App\Entity\Subscription\PrestataireSubscription;
use App\Enum\SubscriptionBillingPeriodEnum;
use App\Enum\SubscriptionStatusEnum;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class PrestataireSubscriptionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return PrestataireSubscription::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Souscription prestataire')
            ->setEntityLabelInPlural('Souscriptions prestataires')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('prestataireProfile', 'Prestataire');
        yield AssociationField::new('plan', 'Plan');
        yield ChoiceField::new('billingPeriod', 'Période')
            ->setChoices([
                'Mensuel' => SubscriptionBillingPeriodEnum::MONTHLY,
                'Annuel' => SubscriptionBillingPeriodEnum::ANNUAL,
            ])
            ->renderAsBadges();
        yield ChoiceField::new('status', 'Statut')
            ->setChoices([
                'Création incomplète' => SubscriptionStatusEnum::INCOMPLETE,
                'Création expirée' => SubscriptionStatusEnum::INCOMPLETE_EXPIRED,
                'Essai' => SubscriptionStatusEnum::TRIALING,
                'Actif' => SubscriptionStatusEnum::ACTIVE,
                'Paiement en retard' => SubscriptionStatusEnum::PAST_DUE,
                'Impayé' => SubscriptionStatusEnum::UNPAID,
                'Résilié' => SubscriptionStatusEnum::CANCELED,
                'Suspendu' => SubscriptionStatusEnum::PAUSED,
            ])
            ->renderAsBadges();
        yield TextField::new('stripeSubscriptionId', 'ID abonnement Stripe')->hideOnIndex();
        yield TextField::new('stripePriceId', 'ID prix Stripe')->hideOnIndex();
        yield IntegerField::new('creditsGrantedCurrentPeriod', 'Crédits accordés');
        yield IntegerField::new('creditsConsumedCurrentPeriod', 'Crédits consommés');
        yield BooleanField::new('cancelAtPeriodEnd', 'Résiliation fin de période');
        yield DateTimeField::new('currentPeriodEnd', 'Fin de période');
        yield DateTimeField::new('createdAt', 'Créé le')->hideOnForm();
    }
}
