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

        yield TextField::new('statusLabel', 'Statut du compte')
            ->formatValue(fn ($value, User $user): string => $this->renderStatusBadge($user->getStatus()))
            ->renderAsHtml()
            ->hideOnForm()
            ->setHelp('État général du compte. Un compte banni ou suspendu ne doit plus être utilisé normalement.');

        yield ChoiceField::new('status', 'Statut du compte')
            ->setChoices([
                UserStatusEnum::PENDING->getLabel() => UserStatusEnum::PENDING,
                UserStatusEnum::ACTIVE->getLabel() => UserStatusEnum::ACTIVE,
                UserStatusEnum::SUSPENDED->getLabel() => UserStatusEnum::SUSPENDED,
                UserStatusEnum::BANNED->getLabel() => UserStatusEnum::BANNED,
                UserStatusEnum::DELETED->getLabel() => UserStatusEnum::DELETED,
            ])
            ->onlyOnForms()
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

    private function renderStatusBadge(mixed $value): string
    {
        $status = $value instanceof UserStatusEnum ? $value : UserStatusEnum::tryFrom((string) $value);

        if (!$status instanceof UserStatusEnum) {
            return $this->renderBadge((string) $value, '#6c757d', 'rgba(108, 117, 125, 0.12)', '#495057');
        }

        [$borderColor, $backgroundColor, $textColor] = match ($status) {
            UserStatusEnum::PENDING => ['#f0ad00', 'rgba(240, 173, 0, 0.12)', '#7a5a00'],
            UserStatusEnum::ACTIVE => ['#198754', 'rgba(25, 135, 84, 0.12)', '#146c43'],
            UserStatusEnum::SUSPENDED => ['#6c757d', 'rgba(108, 117, 125, 0.12)', '#495057'],
            UserStatusEnum::BANNED => ['#dc3545', 'rgba(220, 53, 69, 0.10)', '#a61e2d'],
            UserStatusEnum::DELETED => ['#212529', 'rgba(33, 37, 41, 0.08)', '#212529'],
        };

        return $this->renderBadge($status->getLabel(), $borderColor, $backgroundColor, $textColor);
    }

    private function renderBadge(string $label, string $borderColor, string $backgroundColor, string $textColor): string
    {
        return sprintf(
            '<span class="badge rounded-pill" style="border:1px solid %s;background:%s;color:%s;font-weight:600;">%s</span>',
            htmlspecialchars($borderColor, ENT_QUOTES),
            htmlspecialchars($backgroundColor, ENT_QUOTES),
            htmlspecialchars($textColor, ENT_QUOTES),
            htmlspecialchars($label, ENT_QUOTES)
        );
    }
}
