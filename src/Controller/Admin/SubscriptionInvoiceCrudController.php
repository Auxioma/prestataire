<?php

namespace App\Controller\Admin;

use App\Entity\Subscription\SubscriptionInvoice;
use App\Enum\SubscriptionInvoiceStatusEnum;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class SubscriptionInvoiceCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SubscriptionInvoice::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Facture Stripe')
            ->setEntityLabelInPlural('Factures Stripe')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('subscription', 'Souscription');
        yield TextField::new('stripeInvoiceId', 'ID facture Stripe');
        yield TextField::new('invoiceNumber', 'Numéro facture');
        yield ChoiceField::new('status', 'Statut')
            ->setChoices([
                'Brouillon' => SubscriptionInvoiceStatusEnum::DRAFT,
                'Ouverte' => SubscriptionInvoiceStatusEnum::OPEN,
                'Payée' => SubscriptionInvoiceStatusEnum::PAID,
                'Irrécouvrable' => SubscriptionInvoiceStatusEnum::UNCOLLECTIBLE,
                'Annulée' => SubscriptionInvoiceStatusEnum::VOID,
            ])
            ->renderAsBadges();
        yield MoneyField::new('totalAmount', 'Montant total')->setCurrencyPropertyPath('currency')->setStoredAsCents(false);
        yield MoneyField::new('amountPaid', 'Montant payé')->setCurrencyPropertyPath('currency')->setStoredAsCents(false);
        yield DateTimeField::new('paidAt', 'Payée le');
        yield DateTimeField::new('createdAt', 'Créée le')->hideOnForm();
    }
}
