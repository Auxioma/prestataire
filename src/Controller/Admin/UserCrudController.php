<?php

/**
 * Copyright(c) 2026 Trouve moi
 *
 * Ce fichier fait partie d’un projet développé par Auxioma Web Agency.
 * Tous droits réservés.
 *
 * Ce code source est la propriété exclusive de Auxioma Web Agency.
 * Toute reproduction, modification, distribution ou utilisation sans autorisation préalable est interdite.
 */

namespace App\Controller\Admin;

use App\Entity\User;
use App\Enum\UserStatusEnum;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\RedirectResponse;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;

class UserCrudController extends AbstractCrudController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Utilisateur')
            ->setEntityLabelInPlural('Utilisateurs')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $ban = Action::new('banUser', 'Bannir')
            ->linkToCrudAction('banUser')
            ->setCssClass('btn btn-danger');

        $reactivate = Action::new('reactivateUser', 'Réactiver')
            ->linkToCrudAction('reactivateUser')
            ->setCssClass('btn btn-success');

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $ban)
            ->add(Crud::PAGE_INDEX, $reactivate)
            ->add(Crud::PAGE_DETAIL, $ban)
            ->add(Crud::PAGE_DETAIL, $reactivate);
    }

    #[AdminRoute(path: '/ban-user', name: 'ban_user')]
    public function banUser(): RedirectResponse
    {
        $id = $this->getContext()->getRequest()->query->get('entityId');
        $user = $this->entityManager->getRepository(User::class)->find($id);

        if ($user) {
            $user->setStatus(UserStatusEnum::BANNED);
            $this->entityManager->flush();
            $this->addFlash('success', 'Utilisateur banni.');
        }

        return $this->redirect($this->generateUrl('admin'));
    }

    #[AdminRoute(path: '/reactivate-user', name: 'reactivate_user')]
    public function reactivateUser(): RedirectResponse
    {
        $id = $this->getContext()->getRequest()->query->get('entityId');
        $user = $this->entityManager->getRepository(User::class)->find($id);

        if ($user) {
            $user->setStatus(UserStatusEnum::ACTIVE);
            $this->entityManager->flush();
            $this->addFlash('success', 'Utilisateur réactivé.');
        }

        return $this->redirect($this->generateUrl('admin'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        yield EmailField::new('email', 'Adresse email');

        yield TextField::new('firstName', 'Prénom');
        yield TextField::new('lastName', 'Nom');

        yield ArrayField::new('roles', 'Rôles');

        yield ChoiceField::new('status', 'Statut du compte')
            ->setChoices([
                'En attente' => UserStatusEnum::PENDING,
                'Actif' => UserStatusEnum::ACTIVE,
                'Suspendu' => UserStatusEnum::SUSPENDED,
                'Banni' => UserStatusEnum::BANNED,
            ])
            ->renderAsBadges();

        yield BooleanField::new('isVerified', 'Compte vérifié');
        yield IntegerField::new('loginCount', 'Nb connexions');
        yield DateTimeField::new('lastLoginAt', 'Dernière connexion')->hideOnForm();
        yield DateTimeField::new('createdAt', 'Créé le')->hideOnForm();
    }
}
