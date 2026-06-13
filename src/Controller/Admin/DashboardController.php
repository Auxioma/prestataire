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
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    public function index(): Response
    {
        $url = $this->adminUrlGenerator
            ->setController(UserCrudController::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        return $this->redirect($url);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('🔍 TrouveMoi - Administration')
            ->renderContentMaximized();
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Accueil', 'fa fa-home');

        // POINT 1 : MODÉRATION DES INSCRIPTIONS PRESTATAIRES
        yield MenuItem::section('Modération & Validation');
        yield MenuItem::linkToUrl(
            'Vérification Prestataires',
            'fas fa-id-card',
            $this->adminUrlGenerator->unsetAll()->setController(PrestataireProfileCrudController::class)->setAction(Action::INDEX)->generateUrl()
        );

        // POINT 2 : GESTION DE TOUS LES UTILISATEURS
        yield MenuItem::section('Gestion des Comptes');

        yield MenuItem::linkToUrl(
            'Tous les Utilisateurs',
            'fas fa-users',
            $this->adminUrlGenerator->unsetAll()->setController(UserCrudController::class)->setAction(Action::INDEX)->generateUrl()
        );

        yield MenuItem::linkToUrl(
            'Profils Prestataires',
            'fas fa-briefcase',
            $this->adminUrlGenerator->unsetAll()->setController(PrestataireProfileCrudController::class)->setAction(Action::INDEX)->generateUrl()
        );

        yield MenuItem::linkToUrl(
            'Profils Clients',
            'fas fa-user-circle',
            $this->adminUrlGenerator->unsetAll()->setController(ClientProfileCrudController::class)->setAction(Action::INDEX)->generateUrl()
        );

        // POINT 3 : CATALOGUE
        yield MenuItem::section('Catalogue');
        yield MenuItem::linkToUrl('Catégories de Service', 'fas fa-tags', '#');
        yield MenuItem::linkToUrl('Services / Métiers', 'fas fa-wrench', '#');
    }
}
