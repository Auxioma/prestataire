<?php

namespace App\Controller\Admin;

use App\Entity\PrestataireProfile;
use App\Enum\PrestataireProfileStatusEnum;
use App\Enum\VerificationStatusEnum;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\RedirectResponse;

class PrestataireProfileCrudController extends AbstractCrudController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return PrestataireProfile::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Prestataire')
            ->setEntityLabelInPlural('Prestataires')
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $verify = Action::new('verifyManually', 'Vérifier manuellement')
            ->linkToCrudAction('verifyManually')
            ->setCssClass('btn btn-success');

        $suspend = Action::new('suspendProfile', 'Suspendre')
            ->linkToCrudAction('suspendProfile')
            ->setCssClass('btn btn-danger');

        $reactivate = Action::new('reactivateProfile', 'Réactiver')
            ->linkToCrudAction('reactivateProfile')
            ->setCssClass('btn btn-primary');

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $verify)
            ->add(Crud::PAGE_INDEX, $suspend)
            ->add(Crud::PAGE_INDEX, $reactivate)
            ->add(Crud::PAGE_DETAIL, $verify)
            ->add(Crud::PAGE_DETAIL, $suspend)
            ->add(Crud::PAGE_DETAIL, $reactivate);
    }

    #[AdminRoute(path: '/verify-manually', name: 'verify_manually')]
    public function verifyManually(): RedirectResponse
    {
        $id = $this->getContext()->getRequest()->query->get('entityId');
        $profile = $this->entityManager->getRepository(PrestataireProfile::class)->find($id);

        if ($profile) {
            $profile->setVerificationStatus(VerificationStatusEnum::MANUALLY_VERIFIED);
            $profile->setProfileStatus(PrestataireProfileStatusEnum::ACTIVE);
            $profile->setVerifiedAt(new \DateTimeImmutable());

            $this->entityManager->flush();
            $this->addFlash('success', 'Prestataire vérifié manuellement.');
        }

        return $this->redirect($this->generateUrl('admin'));
    }

    #[AdminRoute(path: '/suspend-profile', name: 'suspend_profile')]
    public function suspendProfile(): RedirectResponse
    {
        $id = $this->getContext()->getRequest()->query->get('entityId');
        $profile = $this->entityManager->getRepository(PrestataireProfile::class)->find($id);

        if ($profile) {
            $profile->setProfileStatus(PrestataireProfileStatusEnum::SUSPENDED);
            $this->entityManager->flush();
            $this->addFlash('success', 'Prestataire suspendu.');
        }

        return $this->redirect($this->generateUrl('admin'));
    }

    #[AdminRoute(path: '/reactivate-profile', name: 'reactivate_profile')]
    public function reactivateProfile(): RedirectResponse
    {
        $id = $this->getContext()->getRequest()->query->get('entityId');
        $profile = $this->entityManager->getRepository(PrestataireProfile::class)->find($id);

        if ($profile) {
            $profile->setProfileStatus(PrestataireProfileStatusEnum::ACTIVE);
            $this->entityManager->flush();
            $this->addFlash('success', 'Prestataire réactivé.');
        }

        return $this->redirect($this->generateUrl('admin'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        yield AssociationField::new('account', 'Compte utilisateur')
            ->setHelp('Compte principal lié à ce profil prestataire.');

        yield TextField::new('companyName', 'Entreprise')
            ->setHelp('Nom commercial ou nom visible du prestataire.')
            ->setFormTypeOption('attr', [
                'placeholder' => 'Exemple : Dupont Services',
            ]);

        yield TextField::new('slug', 'Slug')
            ->hideOnIndex()
            ->setHelp('Identifiant utilisé dans l’URL publique du profil.')
            ->setFormTypeOption('attr', [
                'placeholder' => 'dupont-services',
            ]);

        yield TextField::new('siret', 'SIRET')
            ->setHelp('Numéro officiel d’identification de l’entreprise.')
            ->setFormTypeOption('attr', [
                'placeholder' => '12345678901234',
            ]);

        yield TextField::new('city', 'Ville')
            ->setHelp('Ville principale d’activité du prestataire.')
            ->setFormTypeOption('attr', [
                'placeholder' => 'Exemple : Lacanau',
            ]);

        yield ChoiceField::new('profileStatus', 'Statut du profil')
            ->setChoices([
                'Brouillon' => PrestataireProfileStatusEnum::DRAFT,
                'En attente de validation' => PrestataireProfileStatusEnum::PENDING_VALIDATION,
                'Actif' => PrestataireProfileStatusEnum::ACTIVE,
                'Suspendu' => PrestataireProfileStatusEnum::SUSPENDED,
                'Refusé' => PrestataireProfileStatusEnum::REFUSED,
            ])
            ->renderAsBadges()
            ->setHelp('État général du profil prestataire dans la plateforme.');

        yield ChoiceField::new('verificationStatus', 'Statut de vérification')
            ->setChoices([
                'Non vérifié' => VerificationStatusEnum::NOT_VERIFIED,
                'Email vérifié' => VerificationStatusEnum::EMAIL_VERIFIED,
                'Téléphone vérifié' => VerificationStatusEnum::PHONE_VERIFIED,
                'Entreprise vérifiée' => VerificationStatusEnum::COMPANY_VERIFIED,
                'Documents vérifiés' => VerificationStatusEnum::DOCUMENTS_VERIFIED,
                'Vérifié manuellement' => VerificationStatusEnum::MANUALLY_VERIFIED,
            ])
            ->renderAsBadges()
            ->setHelp('Niveau de vérification atteint pour ce prestataire.');

        yield IntegerField::new('completionScore', 'Complétion')
            ->setHelp('Pourcentage ou score d’avancement du profil prestataire.');

        yield BooleanField::new('isFeatured', 'Mis en avant')
            ->setHelp('Permet de mettre ce prestataire en avant sur le site.');

        yield DateTimeField::new('verifiedAt', 'Vérifié le')
            ->hideOnForm()
            ->setHelp('Date de validation manuelle ou finale du profil.');
    }
}