<?php

namespace App\Controller\Admin;

use App\Entity\Review;
use App\Service\ReviewManager;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;

final class ReviewCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly ReviewManager $reviewManager,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Review::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Avis')
            ->setEntityLabelInPlural('Avis')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('clientProfile', 'Client')->hideOnForm();
        yield AssociationField::new('prestataireProfile', 'Prestataire')->hideOnForm();
        yield AssociationField::new('quoteRequest', 'Demande liée')->hideOnForm();
        yield IntegerField::new('rating', 'Note')->hideOnForm();
        yield TextareaField::new('comment', 'Commentaire')->hideOnIndex();
        yield DateTimeField::new('createdAt', 'Créé le')->hideOnForm();
        yield DateTimeField::new('updatedAt', 'Mis à jour le')->hideOnForm();
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Review) {
            parent::deleteEntity($entityManager, $entityInstance);

            return;
        }

        $this->reviewManager->deleteReview($entityInstance);
    }
}
