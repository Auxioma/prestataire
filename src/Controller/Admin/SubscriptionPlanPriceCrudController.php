<?php

namespace App\Controller\Admin;

use App\Entity\Subscription\SubscriptionPlanPrice;
use App\Enum\SubscriptionBillingPeriodEnum;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class SubscriptionPlanPriceCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SubscriptionPlanPrice::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Tarif d’abonnement')
            ->setEntityLabelInPlural('Tarifs d’abonnement')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('plan', 'Plan')->autocomplete();
        yield ChoiceField::new('billingPeriod', 'Période')
            ->setChoices([
                'Mensuel' => SubscriptionBillingPeriodEnum::MONTHLY,
                'Annuel' => SubscriptionBillingPeriodEnum::ANNUAL,
            ])
            ->renderAsBadges();
        yield TextField::new('label', 'Libellé')->hideOnIndex();
        yield MoneyField::new('amount', 'Montant')->setCurrency('EUR')->setStoredAsCents(false);
        yield BooleanField::new('isPromotional', 'Promo');
        yield BooleanField::new('isActive', 'Actif');
        yield DateTimeField::new('validFrom', 'Valide à partir du')->hideOnIndex();
        yield DateTimeField::new('validUntil', 'Valide jusqu’au')->hideOnIndex();
        yield TextField::new('stripePriceId', 'Price ID Stripe')->hideOnIndex();
        yield DateTimeField::new('updatedAt', 'Mis à jour le')->hideOnForm()->hideOnIndex();
        yield DateTimeField::new('createdAt', 'Créé le')->hideOnForm();
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof SubscriptionPlanPrice) {
            $this->normalizePrice($entityInstance);
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof SubscriptionPlanPrice) {
            $this->normalizePrice($entityInstance);
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    private function normalizePrice(SubscriptionPlanPrice $price): void
    {
        $now = new \DateTimeImmutable();

        if (null !== $price->getValidFrom() && null !== $price->getValidUntil() && $price->getValidFrom() > $price->getValidUntil()) {
            $validFrom = $price->getValidFrom();
            $price
                ->setValidFrom($price->getValidUntil())
                ->setValidUntil($validFrom);
        }

        $price->setUpdatedAt($now);
    }
}
