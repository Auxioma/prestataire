<?php

namespace App\Controller\Admin;

use App\Entity\QuoteRequest;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class QuoteRequestCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return QuoteRequest::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Demande de devis')
            ->setEntityLabelInPlural('Demandes de devis')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT, Action::DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('title', 'Titre');
        yield AssociationField::new('client', 'Client')->hideOnForm();
        yield AssociationField::new('prestataire', 'Prestataire')->hideOnForm();
        yield AssociationField::new('prestation', 'Prestation')->hideOnForm();
        yield TextareaField::new('description', 'Description')->hideOnIndex();
        yield DateField::new('desiredDate', 'Date souhaitée')->hideOnIndex();
        yield TextField::new('budgetAmount', 'Budget')->hideOnIndex();
        yield DateTimeField::new('createdAt', 'Créée le')->hideOnForm();
        yield DateTimeField::new('updatedAt', 'Mise à jour le')->hideOnForm()->hideOnIndex();
    }
}
