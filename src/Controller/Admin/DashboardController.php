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
     *
     * @return Response
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
     *
     * @return Dashboard
     */
    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('🔍 TrouveMoi - Administration')
            ->renderContentMaximized();
    }

    /**
     * Traite l’action "configureMenuItems" du contrôleur Dashboard.
     *
     * @return iterable
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
}
}
