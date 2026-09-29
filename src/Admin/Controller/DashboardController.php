<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 AUXIOMA Web Agency.
 *
 * Projet : TrouveMoi
 *
 * Tous droits réservés.
 *
 * Ce fichier fait partie du projet TrouveMoi,
 * développé par AUXIOMA Web Agency.
 *
 * Toute reproduction, modification, distribution ou utilisation,
 * totale ou partielle, sans autorisation écrite préalable,
 * est strictement interdite.
 */

namespace App\Admin\Controller;

use App\Account\Controller\Admin\ClientProfileCrudController;
use App\Account\Controller\Admin\UserCrudController;
use App\Catalog\Controller\Admin\AllowedNafCodeCrudController;
use App\Catalog\Controller\Admin\ServiceCategoryCrudController;
use App\Catalog\Controller\Admin\ServiceCrudController;
use App\Prestataire\Controller\Admin\PrestataireProfileCrudController;
use App\Report\Controller\Admin\ReportCrudController;
use App\Review\Controller\Admin\ReviewCrudController;
use App\Subscription\Controller\Admin\PrestataireSubscriptionCrudController;
use App\Subscription\Controller\Admin\SubscriptionInvoiceCrudController;
use App\Subscription\Controller\Admin\SubscriptionPlanCrudController;
use App\Subscription\Controller\Admin\SubscriptionPlanPriceCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
#[IsGranted('ROLE_ADMIN')]
/**
 * Gère les actions liées à dashboard.
 */
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    /**
     * Affiche la page principale de ce contrôleur.
     */
    public function index(): Response
    {
        $url = $this->adminUrlGenerator
            ->setController(UserCrudController::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }

    /**
     * Traite l’action "configureDashboard" du contrôleur Dashboard.
     */
    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('🔍 TrouveMoi - Administration')
            ->renderContentMaximized();
    }

    /**
     * Traite l’action "configureMenuItems" du contrôleur Dashboard.
     */
    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Accueil', 'fa fa-home');

        yield MenuItem::section('Modération');
        yield MenuItem::linkToUrl(
            'Utilisateurs',
            'fas fa-users',
            $this->adminUrlGenerator->unsetAll()
                ->setController(UserCrudController::class)
                ->setAction(Action::INDEX)
                ->generateUrl()
        );

        yield MenuItem::linkToUrl(
            'Prestataires',
            'fas fa-briefcase',
            $this->adminUrlGenerator->unsetAll()
                ->setController(PrestataireProfileCrudController::class)
                ->setAction(Action::INDEX)
                ->generateUrl()
        );

        yield MenuItem::linkToUrl(
            'Clients',
            'fas fa-user-circle',
            $this->adminUrlGenerator->unsetAll()
                ->setController(ClientProfileCrudController::class)
                ->setAction(Action::INDEX)
                ->generateUrl()
        );

        yield MenuItem::linkToUrl(
            'Avis clients',
            'fas fa-star',
            $this->adminUrlGenerator->unsetAll()
                ->setController(ReviewCrudController::class)
                ->setAction(Action::INDEX)
                ->generateUrl()
        );

        yield MenuItem::linkToUrl(
            'Signalements',
            'fas fa-flag',
            $this->adminUrlGenerator->unsetAll()
                ->setController(ReportCrudController::class)
                ->setAction(Action::INDEX)
                ->generateUrl()
        );

        yield MenuItem::section('Catalogue');
        yield MenuItem::linkToUrl(
            'Catégories / Sous-catégories',
            'fas fa-tags',
            $this->adminUrlGenerator->unsetAll()
                ->setController(ServiceCategoryCrudController::class)
                ->setAction(Action::INDEX)
                ->generateUrl()
        );

        yield MenuItem::linkToUrl(
            'Services / Métiers',
            'fas fa-wrench',
            $this->adminUrlGenerator->unsetAll()
                ->setController(ServiceCrudController::class)
                ->setAction(Action::INDEX)
                ->generateUrl()
        );

        yield MenuItem::linkToUrl(
            'Codes NAF autorisés',
            'fas fa-list-check',
            $this->adminUrlGenerator->unsetAll()
                ->setController(AllowedNafCodeCrudController::class)
                ->setAction(Action::INDEX)
                ->generateUrl()
        );

        yield MenuItem::section('Abonnements');
        yield MenuItem::linkToUrl(
            'Plans d’abonnement',
            'fas fa-layer-group',
            $this->adminUrlGenerator->unsetAll()
                ->setController(SubscriptionPlanCrudController::class)
                ->setAction(Action::INDEX)
                ->generateUrl()
        );

        yield MenuItem::linkToUrl(
            'Tarifs d’abonnement',
            'fas fa-tags',
            $this->adminUrlGenerator->unsetAll()
                ->setController(SubscriptionPlanPriceCrudController::class)
                ->setAction(Action::INDEX)
                ->generateUrl()
        );

        yield MenuItem::linkToUrl(
            'Souscriptions',
            'fas fa-repeat',
            $this->adminUrlGenerator->unsetAll()
                ->setController(PrestataireSubscriptionCrudController::class)
                ->setAction(Action::INDEX)
                ->generateUrl()
        );

        yield MenuItem::linkToUrl(
            'Factures Stripe',
            'fas fa-file-invoice-dollar',
            $this->adminUrlGenerator->unsetAll()
                ->setController(SubscriptionInvoiceCrudController::class)
                ->setAction(Action::INDEX)
                ->generateUrl()
        );
    }
}
