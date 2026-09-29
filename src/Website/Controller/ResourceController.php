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

namespace App\Website\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ResourceController extends AbstractController
{
    private const RESOURCES = [
        'creer-profil-prestataire' => [
            'title' => 'Bien créer son profil prestataire',
            'category' => 'Guide pratique',
            'icon' => 'fa-regular fa-file-lines',
            'visual' => 'profile',
            'lead' => 'Construisez une vitrine claire et rassurante qui aide les clients à comprendre votre savoir-faire en quelques secondes.',
            'metaDescription' => 'Conseils pratiques pour créer un profil prestataire complet, visible et rassurant sur TrouveMoi.',
            'steps' => [
                ['icon' => 'fa-solid fa-building', 'title' => 'Présentez votre activité', 'text' => 'Utilisez un nom professionnel, une description précise et des coordonnées toujours à jour.'],
                ['icon' => 'fa-solid fa-screwdriver-wrench', 'title' => 'Détaillez vos prestations', 'text' => 'Créez une fiche par service avec un intitulé compréhensible, votre méthode et vos conditions.'],
                ['icon' => 'fa-regular fa-images', 'title' => 'Apportez des preuves', 'text' => 'Ajoutez des réalisations nettes, vos zones d’intervention et les informations qui renforcent la confiance.'],
            ],
            'checklist' => ['Photo ou logo net', 'Description orientée client', 'Prestations et zones complètes', 'Coordonnées et disponibilités vérifiées'],
            'tip' => 'Relisez votre profil comme si vous découvriez l’entreprise : la spécialité, la zone et la prochaine action doivent être évidentes.',
        ],
        'recevoir-plus-de-demandes' => [
            'title' => '5 astuces pour recevoir plus de demandes',
            'category' => 'Développer son activité',
            'icon' => 'fa-solid fa-bullseye',
            'visual' => 'requests',
            'lead' => 'Améliorez votre visibilité et transformez davantage de visites en demandes réellement adaptées à votre activité.',
            'metaDescription' => 'Cinq actions concrètes pour gagner en visibilité et recevoir davantage de demandes qualifiées.',
            'steps' => [
                ['icon' => 'fa-solid fa-location-dot', 'title' => 'Ciblez précisément', 'text' => 'Gardez uniquement les services et zones que vous pouvez réellement prendre en charge.'],
                ['icon' => 'fa-solid fa-camera', 'title' => 'Montrez vos résultats', 'text' => 'Publiez régulièrement des réalisations représentatives avec un contexte court et concret.'],
                ['icon' => 'fa-solid fa-bolt', 'title' => 'Répondez rapidement', 'text' => 'Une première réponse claire et personnalisée augmente vos chances de poursuivre l’échange.'],
            ],
            'checklist' => ['Profil complété à 100 %', 'Rayon d’intervention réaliste', 'Notifications activées', 'Réponse personnalisée sous 24 h', 'Avis demandés après intervention'],
            'tip' => 'La qualité des demandes compte plus que leur volume : mieux cibler évite les échanges inutiles et améliore votre taux de conversion.',
        ],
        'gerer-ses-devis' => [
            'title' => 'Gérer vos devis comme un pro',
            'category' => 'Gestion et outils',
            'icon' => 'fa-regular fa-star',
            'visual' => 'quotes',
            'lead' => 'Présentez une proposition lisible, complète et facile à comparer pour accélérer la décision du client.',
            'metaDescription' => 'Méthode et bonnes pratiques pour préparer, envoyer et suivre des devis professionnels.',
            'steps' => [
                ['icon' => 'fa-solid fa-list-check', 'title' => 'Découpez les prestations', 'text' => 'Une ligne par action permet au client de comprendre précisément ce qui est compris.'],
                ['icon' => 'fa-solid fa-calculator', 'title' => 'Vérifiez les montants', 'text' => 'Contrôlez quantités, prix HT, TVA, total TTC et durée de validité avant publication.'],
                ['icon' => 'fa-regular fa-paper-plane', 'title' => 'Expliquez et suivez', 'text' => 'Ajoutez un message d’introduction puis répondez aux questions depuis la demande associée.'],
            ],
            'checklist' => ['Objet et périmètre explicites', 'Lignes détaillées', 'Délais et conditions indiqués', 'Totaux vérifiés', 'PDF relu avant envoi'],
            'tip' => 'Un devis convaincant ne se contente pas d’un prix : il réduit les zones d’incertitude du client.',
        ],
        'securite-des-paiements' => [
            'title' => 'Sécurité des paiements : nos engagements',
            'category' => 'Guide pratique',
            'icon' => 'fa-solid fa-shield-halved',
            'visual' => 'security',
            'lead' => 'Comprenez les contrôles essentiels qui protègent les données de paiement et les échanges liés à une transaction.',
            'metaDescription' => 'Présentation visuelle des principes de sécurité appliqués aux paiements sur TrouveMoi.',
            'steps' => [
                ['icon' => 'fa-solid fa-lock', 'title' => 'Saisie sécurisée', 'text' => 'Les informations sensibles de carte sont saisies dans le composant sécurisé du prestataire de paiement.'],
                ['icon' => 'fa-solid fa-user-shield', 'title' => 'Authentification', 'text' => 'Une validation bancaire complémentaire peut être demandée lorsque la transaction l’exige.'],
                ['icon' => 'fa-solid fa-receipt', 'title' => 'Traçabilité', 'text' => 'Les statuts et justificatifs utiles restent rattachés au bon abonnement ou document.'],
            ],
            'checklist' => ['Ne jamais transmettre une carte par message', 'Vérifier le domaine affiché', 'Utiliser un mot de passe unique', 'Signaler immédiatement toute anomalie'],
            'tip' => 'TrouveMoi ne vous demandera jamais votre numéro complet de carte ou votre cryptogramme par téléphone ou messagerie.',
        ],
        'nouveautes-trouvemoi' => [
            'title' => 'Nouveautés TrouveMoi.com',
            'category' => 'Actualités',
            'icon' => 'fa-solid fa-bullhorn',
            'visual' => 'updates',
            'lead' => 'Découvrez comment les évolutions récentes simplifient le suivi des demandes, des devis et de votre activité.',
            'metaDescription' => 'Tour d’horizon des améliorations récentes de la plateforme TrouveMoi.',
            'steps' => [
                ['icon' => 'fa-regular fa-comments', 'title' => 'Échanges centralisés', 'text' => 'Retrouvez plus facilement les conversations liées aux demandes et propositions.'],
                ['icon' => 'fa-regular fa-file-lines', 'title' => 'Documents structurés', 'text' => 'Les devis et factures suivent un parcours plus clair, du brouillon à la consultation.'],
                ['icon' => 'fa-solid fa-chart-line', 'title' => 'Pilotage amélioré', 'text' => 'Le tableau de bord rassemble les informations importantes et vos prochaines actions.'],
            ],
            'checklist' => ['Consulter régulièrement le tableau de bord', 'Vérifier ses notifications', 'Relire ses informations publiques', 'Tester les nouveaux parcours avant un besoin urgent'],
            'tip' => 'Les fonctionnalités peuvent évoluer progressivement : fiez-vous aux actions disponibles dans votre espace au moment de l’utilisation.',
        ],
        'developper-activite-en-ligne' => [
            'title' => 'Développer son activité en ligne',
            'category' => 'Webinaire',
            'icon' => 'fa-regular fa-circle-play',
            'visual' => 'webinar',
            'lead' => 'Un parcours en trois chapitres pour clarifier votre positionnement, gagner en visibilité et mieux convertir vos contacts.',
            'metaDescription' => 'Programme visuel pour structurer le développement en ligne de son activité professionnelle.',
            'steps' => [
                ['icon' => 'fa-solid fa-compass', 'title' => 'Clarifier son offre', 'text' => 'Définissez votre client cible, son problème principal et la réponse concrète que vous apportez.'],
                ['icon' => 'fa-solid fa-eye', 'title' => 'Être visible au bon endroit', 'text' => 'Concentrez vos efforts sur les canaux réellement consultés par vos futurs clients.'],
                ['icon' => 'fa-solid fa-handshake', 'title' => 'Transformer la confiance', 'text' => 'Appuyez-vous sur vos preuves, vos réponses et un parcours de contact sans friction.'],
            ],
            'checklist' => ['Promesse compréhensible en une phrase', 'Profil cohérent sur chaque canal', 'Réalisations récentes', 'Réponse rapide et humaine'],
            'tip' => 'Mieux vaut une présence régulière sur deux canaux pertinents qu’une présence incomplète partout.',
        ],
        'calculer-tarifs-et-marges' => [
            'title' => 'Calculer vos tarifs et vos marges',
            'category' => 'Outil',
            'icon' => 'fa-solid fa-calculator',
            'visual' => 'calculator',
            'lead' => 'Construisez un prix cohérent à partir de vos coûts, du temps nécessaire et de la marge qui finance votre activité.',
            'metaDescription' => 'Méthode simple pour estimer un tarif professionnel et contrôler sa marge.',
            'steps' => [
                ['icon' => 'fa-solid fa-box-open', 'title' => 'Lister les coûts', 'text' => 'Additionnez fournitures, déplacements, sous-traitance et part des frais fixes.'],
                ['icon' => 'fa-regular fa-clock', 'title' => 'Valoriser le temps', 'text' => 'Intégrez préparation, intervention, échanges, administration et suivi.'],
                ['icon' => 'fa-solid fa-percent', 'title' => 'Ajouter la marge', 'text' => 'Appliquez une marge adaptée au risque, à l’expertise et aux objectifs de l’entreprise.'],
            ],
            'checklist' => ['Coûts directs recensés', 'Frais fixes répartis', 'Temps non facturable intégré', 'TVA appliquée correctement', 'Marge vérifiée avant remise'],
            'tip' => 'Une remise doit réduire une marge prévue, pas masquer l’oubli d’un coût ou d’une heure de travail.',
        ],
        'communiquer-avec-ses-clients' => [
            'title' => 'Bien communiquer avec vos clients',
            'category' => 'Conseils',
            'icon' => 'fa-regular fa-lightbulb',
            'visual' => 'communication',
            'lead' => 'Des échanges simples, précis et réguliers renforcent la confiance avant, pendant et après l’intervention.',
            'metaDescription' => 'Conseils concrets pour instaurer une communication client claire et professionnelle.',
            'steps' => [
                ['icon' => 'fa-regular fa-comment-dots', 'title' => 'Reformuler le besoin', 'text' => 'Confirmez votre compréhension avec des mots simples avant de proposer une solution.'],
                ['icon' => 'fa-regular fa-calendar-check', 'title' => 'Donner des repères', 'text' => 'Annoncez clairement les prochaines étapes, horaires, délais et éléments attendus.'],
                ['icon' => 'fa-solid fa-circle-check', 'title' => 'Clore proprement', 'text' => 'Récapitulez le travail réalisé et restez disponible pour une question utile.'],
            ],
            'checklist' => ['Message personnalisé', 'Délai de réponse annoncé', 'Vocabulaire compréhensible', 'Décisions importantes confirmées par écrit', 'Compte rendu après intervention'],
            'tip' => 'En cas de retard ou d’imprévu, prévenir tôt protège davantage la relation que chercher une explication parfaite trop tard.',
        ],
    ];

    #[Route('/ressources/{slug}', name: 'app_resource_show', methods: ['GET'], requirements: ['slug' => '[a-z0-9-]+'])]
    public function __invoke(string $slug): Response
    {
        $resource = self::RESOURCES[$slug] ?? null;
        if (null === $resource) {
            throw $this->createNotFoundException('La ressource demandée n’existe pas.');
        }

        return $this->render('Website/static/resource_detail.html.twig', [
            'resource' => $resource,
            'resourceSlug' => $slug,
        ]);
    }
}
