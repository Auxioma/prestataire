# Référence de refactorisation CSS

Cette référence permet de vérifier chaque lot de migration sans modifier le comportement ni le rendu attendu de l’application.

## Périmètre initial

- 55 fichiers CSS dans `assets/styles/`.
- 12 blocs `:root` répartis dans 12 feuilles de style.
- 4 blocs `<style>` dans des pages web classiques, hors emails et PDF.
- 135 attributs `style` dans 19 fichiers Twig, hors emails et PDF.
- Les emails et les PDF sont exclus de la refactorisation actuelle.

## Palette de référence

La source de vérité est la palette de la page d’accueil, désormais déclarée dans `assets/styles/core/tokens.css`.

La typographie existante reste inchangée pendant la migration des couleurs afin de ne pas cumuler deux changements visuels importants.

## Routes publiques de contrôle

État de référence relevé le 28 septembre 2026 sur le serveur local avant la création du socle CSS :

| Page | Route | État HTTP initial |
| --- | --- | ---: |
| Accueil | `/` | 200 |
| Choix du compte | `/register/choice` | 200 |
| Inscription | `/register` | 200 |
| Vérification SIRET/NAF | `/register/prestataire/siret` | 200 |
| Connexion | `/login` | 200 |
| Mot de passe oublié | `/reset-password` | 200 |
| Catégories | `/categories` | 200 |
| Prestataires | `/prestataires` | 200 |
| Bons plans | `/bons-plans` | 200 |
| À propos | `/page/about` | 200 |
| Avantages | `/page/avantages` | 200 |
| Recherche | `/recherche-prestataires` | 200 |

## Contrôles à effectuer après chaque lot

1. Vérifier que les routes publiques ci-dessus répondent sans erreur.
2. Vérifier les pages concernées en largeur bureau, tablette et mobile.
3. Contrôler la navigation, le pied de page, les formulaires, les états de focus et les messages d’erreur.
4. Lancer le lint Twig, les tests automatisés et le contrôle AssetMapper.
5. Vérifier que le diff n’introduit ni espace indésirable ni fichier temporaire.

Les pages authentifiées et les pages nécessitant des données doivent être vérifiées manuellement avec les comptes et jeux de données adaptés à chaque lot.
