<?php

namespace App\Controller\Admin;

use App\Entity\Service;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ServiceCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Service::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Service')
            ->setEntityLabelInPlural('Services / Métiers')
            ->setDefaultSort(['position' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        yield TextField::new('name', 'Nom')
            ->setHelp('Nom du service ou du métier affiché dans le catalogue.')
            ->setFormTypeOption('attr', [
                'placeholder' => 'Exemple : Dépannage de fuite',
            ]);

        yield SlugField::new('slug', 'Slug')
            ->setTargetFieldName('name')
            ->setHelp('Identifiant utilisé dans l’URL. Il se génère automatiquement à la création.')
            ->setFormTypeOption('attr', [
                'placeholder' => 'depannage-de-fuite',
            ]);

        yield AssociationField::new('category', 'Catégorie / Sous-catégorie')
            ->setHelp('Choisis la catégorie ou la sous-catégorie à laquelle ce service appartient.');

        yield TextareaField::new('description', 'Description')
            ->hideOnIndex()
            ->setHelp('Texte court pour décrire clairement le service proposé.')
            ->setFormTypeOption('attr', [
                'placeholder' => 'Décris brièvement ce service...',
                'rows' => 4,
            ]);

        yield TextField::new('icon', 'Icône')
            ->hideOnIndex()
            ->setHelp('Nom technique d’une icône si le front en utilise une.')
            ->setFormTypeOption('attr', [
                'placeholder' => 'Exemple : fa-droplet',
            ]);

        yield MoneyField::new('averagePriceMin', 'Prix minimum indicatif')
            ->setCurrency('EUR')
            ->setStoredAsCents(false)
            ->setHelp('Fourchette basse indicative du prix moyen pour ce service.');

        yield MoneyField::new('averagePriceMax', 'Prix maximum indicatif')
            ->setCurrency('EUR')
            ->setStoredAsCents(false)
            ->setHelp('Fourchette haute indicative du prix moyen pour ce service.');

        yield IntegerField::new('position', 'Ordre d’affichage')
            ->setHelp('Définit l’ordre d’apparition dans la liste des services. Plus le nombre est petit, plus le service remonte.')
            ->setFormTypeOption('attr', [
                'placeholder' => '10',
                'min' => 0,
            ]);

        yield BooleanField::new('isActive', 'Service actif')
            ->setHelp('Active le service pour l’afficher sur le site. Désactive-le pour le masquer sans le supprimer.');
    }
}