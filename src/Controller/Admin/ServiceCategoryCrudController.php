<?php

namespace App\Controller\Admin;

use App\Entity\ServiceCategory;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ColorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ServiceCategoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ServiceCategory::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Catégorie')
            ->setEntityLabelInPlural('Catégories / Sous-catégories')
            ->setDefaultSort(['position' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        yield TextField::new('name', 'Nom')
            ->setHelp('Le nom visible dans le back-office et sur le site.')
            ->setFormTypeOption('attr', [
                'placeholder' => 'Exemple : Plomberie',
            ]);

        yield SlugField::new('slug', 'Slug')
            ->setTargetFieldName('name')
            ->setHelp('Identifiant utilisé dans l’URL. À la création, il se génère automatiquement depuis le nom.')
            ->setFormTypeOption('attr', [
                'placeholder' => 'exemple-plomberie',
            ]);

        yield AssociationField::new('parent', 'Catégorie parente')
            ->setRequired(false)
            ->setHelp('Laisse vide pour une catégorie principale. Choisis une catégorie parente pour créer une sous-catégorie.');

        yield TextareaField::new('description', 'Description')
            ->hideOnIndex()
            ->setHelp('Petit texte de présentation pour expliquer la catégorie.')
            ->setFormTypeOption('attr', [
                'placeholder' => 'Décris brièvement cette catégorie...',
                'rows' => 4,
            ]);

        yield TextField::new('icon', 'Icône')
            ->hideOnIndex()
            ->setHelp('Nom technique de l’icône si tu en utilises une dans le front.')
            ->setFormTypeOption('attr', [
                'placeholder' => 'Exemple : fa-wrench',
            ]);

        yield ImageField::new('image', 'Image')
            ->hideOnForm()
            ->hideOnIndex()
            ->setHelp('Image actuellement enregistrée pour cette catégorie.');

        yield IntegerField::new('position', 'Ordre d’affichage')
            ->setHelp('Définit l’ordre d’apparition sur le site. Plus le nombre est petit, plus la catégorie remonte.')
            ->setFormTypeOption('attr', [
                'placeholder' => '10',
                'min' => 0,
            ]);

        yield ColorField::new('color', 'Couleur')
            ->hideOnIndex()
            ->setHelp('Couleur associée à la catégorie si tu veux un repère visuel dans le front.');

        yield TextField::new('seoTitle', 'Titre SEO')
            ->hideOnIndex()
            ->setHelp('Titre optimisé pour le référencement, utilisé si tu veux différencier le SEO du nom affiché.')
            ->setFormTypeOption('attr', [
                'placeholder' => 'Exemple : Trouver un plombier à Lacanau',
            ]);

        yield TextareaField::new('seoDescription', 'Description SEO')
            ->hideOnIndex()
            ->setHelp('Texte SEO court pour décrire la page catégorie dans les moteurs de recherche.')
            ->setFormTypeOption('attr', [
                'placeholder' => 'Exemple : Comparez les meilleurs professionnels de la catégorie...',
                'rows' => 3,
            ]);

        yield BooleanField::new('isActive', 'Catégorie active')
            ->setHelp('Active la catégorie pour l’afficher sur le site. Désactive-la pour la masquer sans la supprimer.');
    }
}