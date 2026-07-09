<?php

namespace App\Controller\Admin;

use App\Entity\ServiceCategory;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ColorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * Gère les actions liées à service category  c r u d.
 */
class ServiceCategoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ServiceCategory::class;
    }

    /**
     * Traite l’action "configureCrud" du contrôleur Service Category  C R U D.
     *
     * @return Crud
     */
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Catégorie')
            ->setEntityLabelInPlural('Catégories / Sous-catégories')
            ->setDefaultSort(['position' => 'ASC']);
    }

    /**
     * Traite l’action "configureFields" du contrôleur Service Category  C R U D.
     *
     * @return iterable
     */
    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        yield TextField::new('name', 'Nom')
            ->setHelp('Le nom visible dans le back-office et sur le site.')
            ->setFormTypeOption('attr', [
                'placeholder' => 'Exemple : Plomberie',
            ]);

        yield SlugField::new('slug', 'Slug')
            ->setTargetFieldName('name')
            ->setHelp('Identifiant utilisé dans l’URL. À la création, il se génère automatiquement depuis le nom.')
            ->setFormTypeOption('attr', [
                'placeholder' => 'exemple-plomberie',
            ]);

        yield AssociationField::new('parent', 'Catégorie parente')
            ->setRequired(false)
            ->setHelp('Laisse vide pour une catégorie principale. Choisis une catégorie parente pour créer une sous-catégorie.');

        yield TextareaField::new('description', 'Description')
            ->hideOnIndex()
            ->setHelp('Petit texte de présentation pour expliquer la catégorie.')
            ->setFormTypeOption('attr', [
                'placeholder' => 'Décris brièvement cette catégorie...',
                'rows' => 4,
            ]);

        yield TextField::new('icon', 'Icône actuelle')
            ->onlyOnIndex()
            ->formatValue(function ($value, $entity) {
                if (!$value) {
                    return '<span class="text-muted">Aucune</span>';
                }

                return sprintf(
                    '<span style="display:inline-flex;align-items:center;gap:8px;">
                    <i class="fa-solid %s"></i>
                    <code>%s</code>
                </span>',
                    $value,
                    $value
                );
            })
            ->renderAsHtml();

        yield ChoiceField::new('icon', 'Icône')
            ->onlyOnForms()
            ->setChoices([
                // Bâtiment / travaux
                'Marteau' => 'fa-hammer',
                'Clé anglaise' => 'fa-wrench',
                'Outils croisés' => 'fa-screwdriver-wrench',
                'Peinture / rouleau' => 'fa-paint-roller',
                'Maison' => 'fa-house',
                'Immeuble' => 'fa-building',
                'Porte' => 'fa-door-open',
                'Fenêtre / structure' => 'fa-table-cells-large',
                'Boîte à outils' => 'fa-toolbox',
                'Règle / mesure' => 'fa-ruler-combined',
                'Casque chantier' => 'fa-helmet-safety',
                'Ampoule' => 'fa-lightbulb',
                'Électricité' => 'fa-bolt',
                'Prise' => 'fa-plug',
                'Plomberie' => 'fa-faucet-drip',
                'Douche' => 'fa-shower',
                'Toilettes / sanitaire' => 'fa-toilet',
                'Ventilation / climatisation' => 'fa-fan',
                'Chauffage / feu' => 'fa-fire',
                'Eau' => 'fa-droplet',

                // Jardin / extérieur
                'Arbre' => 'fa-tree',
                'Feuille' => 'fa-leaf',
                'Jeune pousse' => 'fa-seedling',
                'Spa / fleur' => 'fa-spa',
                'Trèfle / nature' => 'fa-clover',
                'Soleil' => 'fa-sun',
                'Arrosage' => 'fa-fill-drip',
                'Extérieur / manutention' => 'fa-cart-flatbed',

                // Nettoyage / entretien
                'Propreté / étincelles' => 'fa-sparkles',
                'Balai' => 'fa-broom',
                'Savon / hygiène' => 'fa-pump-soap',
                'Seau' => 'fa-bucket',
                'Spray / désinfection' => 'fa-spray-can-sparkles',
                'Recyclage' => 'fa-recycle',
                'Poubelle' => 'fa-trash',
                'Air / aspiration' => 'fa-wind',

                // Déménagement / logistique
                'Camion rampe' => 'fa-truck-ramp-box',
                'Camion' => 'fa-truck',
                'Cartons empilés' => 'fa-boxes-stacked',
                'Colis' => 'fa-box',
                'Diable / manutention' => 'fa-dolly',
                'Entrepôt' => 'fa-warehouse',

                // Informatique / high-tech
                'Ordinateur portable' => 'fa-laptop',
                'Ordinateur fixe' => 'fa-desktop',
                'Mobile' => 'fa-mobile-screen-button',
                'Tablette' => 'fa-tablet-screen-button',
                'Écran' => 'fa-display',
                'Code / développement' => 'fa-code',
                'Base de données' => 'fa-database',
                'Serveur' => 'fa-server',
                'Wi-Fi' => 'fa-wifi',
                'Microchip' => 'fa-microchip',
                'Clavier' => 'fa-keyboard',
                'Souris' => 'fa-computer-mouse',
                'Imprimante' => 'fa-print',
                'Caméra' => 'fa-camera',
                'Vidéo' => 'fa-video',
                'Musique' => 'fa-music',
                'Casque audio' => 'fa-headphones',
                'Jeu vidéo' => 'fa-gamepad',

                // Administratif / services
                'Utilisateur' => 'fa-user',
                'Groupe' => 'fa-users',
                'Carte de visite' => 'fa-address-card',
                'Dossier ouvert' => 'fa-folder-open',
                'Documents' => 'fa-file-lines',
                'Stylo' => 'fa-pen',
                'Signature' => 'fa-signature',
                'Calendrier' => 'fa-calendar-days',
                'Horloge' => 'fa-clock',
                'Téléphone' => 'fa-phone',
                'Email' => 'fa-envelope',
                'Commentaires' => 'fa-comments',
                'Business / mallette' => 'fa-briefcase',

                // Formation / éducation
                'Livre ouvert' => 'fa-book-open',
                'Livre' => 'fa-book',
                'Diplôme' => 'fa-graduation-cap',
                'École' => 'fa-school',
                'Crayon' => 'fa-pencil',
                'Tableau / présentation' => 'fa-chalkboard',
                'Puzzle / accompagnement' => 'fa-puzzle-piece',

                // Cuisine / restauration
                'Ustensiles' => 'fa-utensils',
                'Cloche de service' => 'fa-cloche',
                'Cuisine équipée' => 'fa-kitchen-set',
                'Burger' => 'fa-burger',
                'Pizza' => 'fa-pizza-slice',
                'Gâteau / pâtisserie' => 'fa-cake-candles',
                'Glace' => 'fa-ice-cream',
                'Café / boisson chaude' => 'fa-mug-hot',
                'Cocktail' => 'fa-martini-glass',
                'Vin' => 'fa-wine-glass',
                'Chef cuisinier' => 'fa-user-chef',

                // Événementiel / animation
                'Champagne / fête' => 'fa-champagne-glasses',
                'Calendrier événement' => 'fa-calendar-check',
                'Microphone' => 'fa-microphone',
                'Musique / animation' => 'fa-music',
                'Photo rétro' => 'fa-camera-retro',
                'Image / décoration' => 'fa-image',
                'Palette créative' => 'fa-palette',
                'Communication / mégaphone' => 'fa-bullhorn',
                'Ticket' => 'fa-ticket',
                'Étoile' => 'fa-star',

                // Beauté / bien-être / santé
                'Santé / cœur' => 'fa-heart-pulse',
                'Bien-être / spa' => 'fa-spa',
                'Soin / main' => 'fa-hand-sparkles',
                'Sourire / beauté' => 'fa-face-smile',
                'Coiffure / ciseaux' => 'fa-scissors',
                'Savon' => 'fa-soap',
                'Médical / notes' => 'fa-notes-medical',
                'Stéthoscope' => 'fa-stethoscope',
                'Pharmacie' => 'fa-prescription-bottle-medical',
                'Dentiste' => 'fa-tooth',

                // Automobile / mécanique
                'Voiture' => 'fa-car',
                'Voiture côté' => 'fa-car-side',
                'Volant' => 'fa-steering-wheel',
                'Route' => 'fa-road',
                'Station essence' => 'fa-gas-pump',
                'Batterie auto' => 'fa-car-battery',
                'Engrenages' => 'fa-gears',

                // Animaux
                'Chien' => 'fa-dog',
                'Chat' => 'fa-cat',
                'Patte' => 'fa-paw',
                'Poisson' => 'fa-fish',
                'Oiseau' => 'fa-dove',

                // Sécurité / surveillance
                'Bouclier' => 'fa-shield-halved',
                'Caméra sécurité' => 'fa-camera',
                'Cadenas' => 'fa-lock',
                'Clé' => 'fa-key',
                'Alerte' => 'fa-triangle-exclamation',
                'Validation' => 'fa-circle-check',

                // Commerce / vente
                'Panier' => 'fa-cart-shopping',
                'Sac boutique' => 'fa-bag-shopping',
                'Euro' => 'fa-euro-sign',
                'Carte bancaire' => 'fa-credit-card',
                'Caisse' => 'fa-cash-register',
                'Promotion / pourcentage' => 'fa-percent',
                'Étiquettes' => 'fa-tags',

                // Généralistes utiles
                'Recherche' => 'fa-magnifying-glass',
                'Localisation' => 'fa-location-dot',
                'Boussole' => 'fa-compass',
                'Globe' => 'fa-globe',
                'Aide / poignée de main' => 'fa-handshake-angle',
                'Pouce levé' => 'fa-thumbs-up',
                'Checklist' => 'fa-list-check',
                'Réglages' => 'fa-sliders',
                'Lien' => 'fa-link',
                'Cible' => 'fa-bullseye',
                'Lancement / rocket' => 'fa-rocket',
            ])
            ->setHelp('Choisis une icône Font Awesome gratuite pour l’affichage sur le site.')
            ->renderExpanded(false)
            ->allowMultipleChoices(false);

        yield ImageField::new('image', 'Image')
            ->hideOnForm()
            ->hideOnIndex()
            ->setHelp('Image actuellement enregistrée pour cette catégorie.');

        yield IntegerField::new('position', 'Ordre d’affichage')
            ->setHelp('Définit l’ordre d’apparition sur le site. Plus le nombre est petit, plus la catégorie remonte.')
            ->setFormTypeOption('attr', [
                'placeholder' => '10',
                'min' => 0,
            ]);

        yield ColorField::new('color', 'Couleur')
            ->hideOnIndex()
            ->setHelp('Couleur associée à la catégorie si tu veux un repère visuel dans le front.');

        yield TextField::new('seoTitle', 'Titre SEO')
            ->hideOnIndex()
            ->setHelp('Titre optimisé pour le référencement, utilisé si tu veux différencier le SEO du nom affiché.')
            ->setFormTypeOption('attr', [
                'placeholder' => 'Exemple : Trouver un plombier à Lacanau',
            ]);

        yield TextareaField::new('seoDescription', 'Description SEO')
            ->hideOnIndex()
            ->setHelp('Texte SEO court pour décrire la page catégorie dans les moteurs de recherche.')
            ->setFormTypeOption('attr', [
                'placeholder' => 'Exemple : Comparez les meilleurs professionnels de la catégorie...',
                'rows' => 3,
            ]);

        yield BooleanField::new('isActive', 'Catégorie active')
            ->setHelp('Active la catégorie pour l’afficher sur le site. Désactive-la pour la masquer sans la supprimer.');
    }
}
