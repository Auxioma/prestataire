<?php

namespace App\Controller\Admin;

use App\Entity\Subscription\PrestataireSubscription;
use App\Enum\SubscriptionBillingPeriodEnum;
use App\Enum\SubscriptionStatusEnum;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
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

final class PrestataireSubscriptionCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return PrestataireSubscription::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Souscription prestataire')
            ->setEntityLabelInPlural('Souscriptions prestataires')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $activate = Action::new('activateNow', 'Activer')
            ->linkToCrudAction('activateNow')
            ->setCssClass('btn btn-success');

        $cancel = Action::new('cancelNow', 'Résilier')
            ->linkToCrudAction('cancelNow')
            ->setCssClass('btn btn-outline-danger');

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $activate)
            ->add(Crud::PAGE_INDEX, $cancel)
            ->add(Crud::PAGE_DETAIL, $activate)
            ->add(Crud::PAGE_DETAIL, $cancel);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('prestataireProfile', 'Prestataire')->autocomplete();
        yield AssociationField::new('customer', 'Client Stripe')->hideOnIndex();
        yield AssociationField::new('plan', 'Plan')->autocomplete();
        yield ChoiceField::new('billingPeriod', 'Période')
            ->setChoices([
                'Mensuel' => SubscriptionBillingPeriodEnum::MONTHLY,
                'Annuel' => SubscriptionBillingPeriodEnum::ANNUAL,
            ])
            ->renderAsBadges();
        yield ChoiceField::new('status', 'Statut')
            ->setChoices([
                'Création incomplète' => SubscriptionStatusEnum::INCOMPLETE,
                'Création expirée' => SubscriptionStatusEnum::INCOMPLETE_EXPIRED,
                'Essai' => SubscriptionStatusEnum::TRIALING,
                'Actif' => SubscriptionStatusEnum::ACTIVE,
                'Paiement en retard' => SubscriptionStatusEnum::PAST_DUE,
                'Impayé' => SubscriptionStatusEnum::UNPAID,
                'Résilié' => SubscriptionStatusEnum::CANCELED,
                'Suspendu' => SubscriptionStatusEnum::PAUSED,
            ])
            ->renderAsBadges();
        yield TextField::new('stripeSubscriptionId', 'ID abonnement Stripe')->hideOnIndex();
        yield TextField::new('stripePriceId', 'ID prix Stripe')->hideOnIndex();
        yield TextField::new('stripeSubscriptionItemId', 'ID item Stripe')->hideOnIndex();
        yield IntegerField::new('creditsGrantedCurrentPeriod', 'Crédits accordés');
        yield IntegerField::new('creditsConsumedCurrentPeriod', 'Crédits consommés');
        yield BooleanField::new('cancelAtPeriodEnd', 'Résiliation fin de période');
        yield DateTimeField::new('startedAt', 'Début')->hideOnIndex();
        yield DateTimeField::new('currentPeriodStart', 'Début de période')->hideOnIndex();
        yield DateTimeField::new('currentPeriodEnd', 'Fin de période');
        yield DateTimeField::new('endedAt', 'Fin effective')->hideOnIndex();
        yield DateTimeField::new('updatedAt', 'Mis à jour le')->hideOnForm()->hideOnIndex();
        yield DateTimeField::new('createdAt', 'Créé le')->hideOnForm();
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof PrestataireSubscription) {
            $this->normalizeSubscription($entityInstance);
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof PrestataireSubscription) {
            $this->normalizeSubscription($entityInstance);
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    #[AdminRoute(path: '/activate-now', name: 'activate_now')]
    public function activateNow(): RedirectResponse
    {
        $subscription = $this->getCurrentSubscription();
        $now = new \DateTimeImmutable();
        $periodEnd = $subscription->getCurrentPeriodEnd() ?? $now->modify(
            SubscriptionBillingPeriodEnum::ANNUAL === $subscription->getBillingPeriod() ? '+12 months' : '+1 month'
        );

        $subscription
            ->setStatus(SubscriptionStatusEnum::ACTIVE)
            ->setStartedAt($subscription->getStartedAt() ?? $now)
            ->setCurrentPeriodStart($subscription->getCurrentPeriodStart() ?? $now)
            ->setCurrentPeriodEnd($periodEnd)
            ->setCancelAtPeriodEnd(false)
            ->setCancellationRequestedAt(null)
            ->setCanceledAt(null)
            ->setEndedAt(null)
            ->syncCreditsWithPlan()
            ->setUpdatedAt($now);

        $this->managerRegistry->getManager()->flush();
        $this->addFlash('success', 'La souscription a été activée manuellement.');

        return $this->redirect($this->generateUrl('admin'));
    }

    #[AdminRoute(path: '/cancel-now', name: 'cancel_now')]
    public function cancelNow(): RedirectResponse
    {
        $subscription = $this->getCurrentSubscription();
        $now = new \DateTimeImmutable();

        $subscription
            ->setStatus(SubscriptionStatusEnum::CANCELED)
            ->setCancelAtPeriodEnd(false)
            ->setCancellationRequestedAt($now)
            ->setCanceledAt($now)
            ->setEndedAt($now)
            ->setUpdatedAt($now);

        $this->managerRegistry->getManager()->flush();
        $this->addFlash('success', 'La souscription a été résiliée manuellement.');

        return $this->redirect($this->generateUrl('admin'));
    }

    private function getCurrentSubscription(): PrestataireSubscription
    {
        $id = $this->getContext()->getRequest()->query->get('entityId');
        $subscription = $this->managerRegistry->getRepository(PrestataireSubscription::class)->find($id);

        if (!$subscription instanceof PrestataireSubscription) {
            throw $this->createNotFoundException('Souscription introuvable.');
        }

        return $subscription;
    }

    private function normalizeSubscription(PrestataireSubscription $subscription): void
    {
        $now = new \DateTimeImmutable();

        $subscription->setUpdatedAt($now);

        if ($subscription->getPlan() && 0 === $subscription->getCreditsGrantedCurrentPeriod()) {
            $subscription->syncCreditsWithPlan();
        }

        if (SubscriptionStatusEnum::ACTIVE === $subscription->getStatus()) {
            $subscription
                ->setStartedAt($subscription->getStartedAt() ?? $now)
                ->setCurrentPeriodStart($subscription->getCurrentPeriodStart() ?? $now)
                ->setEndedAt(null)
                ->setCanceledAt(null)
                ->setCancellationRequestedAt(null);
        }

        if (SubscriptionStatusEnum::CANCELED === $subscription->getStatus() && null === $subscription->getEndedAt()) {
            $subscription->setEndedAt($now);
        }
    }
}
