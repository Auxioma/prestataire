<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Enum\UserStatusEnum;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
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

/**
 * Gère les actions liées à user  c r u d.
 */
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

    /**
     * Traite l’action "configureCrud" du contrôleur User  C R U D.
     *
     * @return Crud
     */
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Utilisateur')
            ->setEntityLabelInPlural('Utilisateurs')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    /**
     * Traite l’action "configureActions" du contrôleur User  C R U D.
     *
     * @return Actions
     */
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
    /**
     * Traite l’action "banUser" du contrôleur User  C R U D.
     *
     * @return RedirectResponse
     */
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
    /**
     * Traite l’action "reactivateUser" du contrôleur User  C R U D.
     *
     * @return RedirectResponse
     */
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

    /**
     * Traite l’action "configureFields" du contrôleur User  C R U D.
     *
     * @return iterable
     */
    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        yield EmailField::new('email', 'Adresse email')
            ->setHelp('Adresse utilisée pour se connecter à la plateforme.')
            ->setFormTypeOption('attr', [
                'placeholder' => 'client@exemple.fr',
            ]);

        yield TextField::new('firstName', 'Prénom')
            ->setHelp('Prénom de l’utilisateur.')
            ->setFormTypeOption('attr', [
                'placeholder' => 'Exemple : Jean',
            ]);

        yield TextField::new('lastName', 'Nom')
            ->setHelp('Nom de famille de l’utilisateur.')
            ->setFormTypeOption('attr', [
                'placeholder' => 'Exemple : Dupont',
            ]);

        yield ArrayField::new('roles', 'Rôles')
            ->setHelp('Définit les droits d’accès de l’utilisateur dans l’application.');

        yield ChoiceField::new('status', 'Statut du compte')
            ->setChoices([
                'En attente' => UserStatusEnum::PENDING,
                'Actif' => UserStatusEnum::ACTIVE,
                'Suspendu' => UserStatusEnum::SUSPENDED,
                'Banni' => UserStatusEnum::BANNED,
            ])
            ->renderAsBadges()
            ->setHelp('État général du compte. Un compte banni ou suspendu ne doit plus être utilisé normalement.');

        yield BooleanField::new('isVerified', 'Compte vérifié')
            ->setHelp('Indique si le compte a déjà été validé ou confirmé.');

        yield IntegerField::new('loginCount', 'Nombre de connexions')
            ->setHelp('Nombre total de connexions enregistrées pour ce compte.');

        yield DateTimeField::new('lastLoginAt', 'Dernière connexion')
            ->hideOnForm()
            ->setHelp('Dernière date de connexion connue.');

        yield DateTimeField::new('createdAt', 'Créé le')
            ->hideOnForm()
            ->setHelp('Date de création du compte.');
    }
}