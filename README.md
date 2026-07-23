<div align="center">

<img src="https://img.shields.io/badge/version-MVP-D4AF37?style=for-the-badge&labelColor=1B2A4A" alt="version"/>
<img src="https://img.shields.io/badge/framework-Symfony%208-000000?style=for-the-badge&labelColor=1B2A4A" alt="framework"/>
<img src="https://img.shields.io/badge/status-En%20développement-B8860B?style=for-the-badge&labelColor=1B2A4A" alt="status"/>

<br/><br/>



```text
██████╗ ██████╗ ███████╗███████╗████████╗ █████╗ ████████╗ █████╗ ██╗██████╗ ███████╗
██╔══██╗██╔══██╗██╔════╝██╔════╝╚══██╔══╝██╔══██╗╚══██╔══╝██╔══██╗██║██╔══██╗██╔════╝
██████╔╝██████╔╝█████╗  ███████╗   ██║   ███████║   ██║   ███████║██║██████╔╝█████╗
██╔═══╝ ██╔══██╗██╔══╝  ╚════██║   ██║   ██╔══██║   ██║   ██╔══██║██║██╔══██╗██╔══╝
██║     ██║  ██║███████╗███████║   ██║   ██║  ██║   ██║   ██║  ██║██║██║  ██║███████╗
╚═╝     ╚═╝  ╚═╝╚══════╝╚══════╝   ╚═╝   ╚═╝  ╚═╝   ╚═╝   ╚═╝  ╚═╝╚═╝╚═╝  ╚═╝╚══════╝
```

### _"Trouvez, comparez, échangez."_

<br/>

[![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![Symfony](https://img.shields.io/badge/Symfony-8-000000?style=flat-square&logo=symfony&logoColor=white)](https://symfony.com)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?style=flat-square&logo=postgresql&logoColor=white)](https://postgresql.org)
[![Twig](https://img.shields.io/badge/Twig-Templates-0C7A43?style=flat-square&logo=twig&logoColor=white)](https://twig.symfony.com)
[![EasyAdmin](https://img.shields.io/badge/EasyAdmin-5-1A1A1A?style=flat-square)](https://easyadmin.com)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5-7952B3?style=flat-square&logo=bootstrap&logoColor=white)](https://getbootstrap.com)
[![Stimulus](https://img.shields.io/badge/Stimulus-Hotwired-77E8B9?style=flat-square)](https://stimulus.hotwired.dev)
[![Turbo](https://img.shields.io/badge/Turbo-Hotwired-5A67D8?style=flat-square)](https://turbo.hotwired.dev)
[![Doctrine](https://img.shields.io/badge/Doctrine-ORM-2C5D86?style=flat-square)](https://www.doctrine-project.org)
[![Messenger](https://img.shields.io/badge/Symfony-Messenger-8A2BE2?style=flat-square)](https://symfony.com/doc/current/messenger.html)
[![Mailer](https://img.shields.io/badge/Symfony-Mailer-FF6B6B?style=flat-square)](https://symfony.com/doc/current/mailer.html)
[![Notifier](https://img.shields.io/badge/Symfony-Notifier-4C8BF5?style=flat-square)](https://symfony.com/doc/current/notifier.html)
[![Elasticsearch](https://img.shields.io/badge/Elasticsearch-Search-005571?style=flat-square&logo=elasticsearch&logoColor=white)](https://www.elastic.co/elasticsearch)
[![Stripe](https://img.shields.io/badge/Stripe-Billing-635BFF?style=flat-square&logo=stripe&logoColor=white)](https://stripe.com)
[![VichUploader](https://img.shields.io/badge/VichUploader-Upload-2E7D32?style=flat-square)](https://github.com/dustin10/VichUploaderBundle)
[![Guzzle](https://img.shields.io/badge/Guzzle-HTTP-4A90E2?style=flat-square)](https://docs.guzzlephp.org)
[![OAuth2](https://img.shields.io/badge/OAuth2-Google-4285F4?style=flat-square)](https://developers.google.com/identity)
[![Dompdf](https://img.shields.io/badge/Dompdf-PDF-9C27B0?style=flat-square)](https://github.com/dompdf/dompdf)

<br/><br/>

**Application Symfony de gestion de prestations**  
_Projet centré sur la recherche, la mise en relation, les échanges, les devis, la facturation et l’administration._

</div>

---

## Table des matières

- [À propos du projet](#-à-propos-du-projet)
- [Fonctionnalités](#-fonctionnalités)
- [Architecture](#-architecture)
- [Stack technique](#-stack-technique)
- [Démarrage local](#-démarrage-local)
- [Temps réel, mailer et services](#-temps-réel-mailer-et-services)
- [Structure du projet](#-structure-du-projet)
- [Base de données](#-base-de-données)
- [Commandes utiles](#-commandes-utiles)
- [Tâche cron abonnements](#-tâche-cron-abonnements)
- [Qualité et outils de développement](#-qualité-et-outils-de-développement)
- [Auteur](#-auteur)

---

## 🚀 À propos du projet

**Prestataire** est une application web Symfony 8 reposant sur PHP 8.4 et PostgreSQL 16. Le dépôt contient une architecture complète avec Doctrine ORM, Doctrine Migrations, Twig, Bootstrap, Stimulus, Turbo, Asset Mapper, EasyAdmin, Mailer, Messenger, Notifier, VichUploader, Elasticsearch, Stripe et plusieurs composants Symfony UX.

Le code source montre un périmètre métier étendu autour de la recherche de prestataires, des profils, des rendez-vous, des conversations, des notifications, des devis, des propositions, des factures, des avis, des signalements et des abonnements. Cette description repose uniquement sur les classes, services, contrôleurs, entités, commandes et fichiers réellement présents dans le dépôt converti.

---

## ✨ Fonctionnalités

### Fonctionnalités métier

Les contrôleurs, entités, formulaires et services présents dans `src/` montrent que l'application couvre plusieurs domaines fonctionnels.

- Recherche et navigation de prestataires avec `PrestataireBrowseController`, `SearchController` et `SearchApiController`.
- Gestion de profils client et prestataire avec `ClientProfile`, `PrestataireProfile` et leurs contrôleurs associés.
- Rendez-vous et disponibilités avec `PrestataireAppointment`, `PrestataireAvailability` et leurs gestionnaires.
- Demandes de devis et propositions avec `QuoteRequest`, `QuoteProposal`, `QuoteProposalItem` et les contrôleurs correspondants.
- Facturation avec `Invoice`, `InvoiceItem`, générateurs PDF et services de calcul.
- Avis, favoris, signalements et notifications avec les entités `Review`, `Favorite`, `Report` et `Notification`.
- Gestion d'abonnements et d'offres avec les entités et services `Subscription*` ainsi que des commandes Stripe dédiées.

### Interface utilisateur

Le front repose sur Twig, Bootstrap, Stimulus, Turbo et Asset Mapper. Le fichier `assets/app.js` importe explicitement `bootstrap`, `bootstrap/dist/css/bootstrap.min.css` et `./styles/app.css`, ce qui confirme l'utilisation de Bootstrap et de CSS personnalisé dans l'interface.

Les contrôleurs Stimulus présents dans `assets/controllers/` montrent une interface riche, avec recherche homepage, wizard de prestation, dashboard prestataire, carrousel de catégories, calendrier, lightbox média, galerie de conversation, notation par étoiles, carte de zones et plusieurs interactions métier spécialisées.

### Administration

Le dépôt contient un important back-office EasyAdmin avec des contrôleurs CRUD pour les utilisateurs, conversations, devis, avis, signalements, plans d'abonnement, factures, services, catégories et profils. Cette partie d'administration repose sur des contrôleurs comme `DashboardController`, `UserCrudController`, `ConversationCrudController`, `QuoteRequestCrudController`, `ReviewCrudController` ou `SubscriptionInvoiceCrudController`.

---

## 🏗️ Architecture

L'application suit une architecture Symfony monolithique moderne avec rendu serveur et enrichissement progressif de l'interface via Stimulus et Turbo. Elle s'appuie sur un noyau Symfony principal, une base PostgreSQL et un serveur temps réel séparé dans le dossier `realtime-server`.

```text
┌──────────────────────────────────────┐
│              Front web               │
│ Twig + Bootstrap + app.css           │
│ Stimulus + Turbo + Asset Mapper      │
└──────────────────┬───────────────────┘
                   │
                   ▼
┌──────────────────────────────────────┐
│              Symfony 8               │
│ Controllers / Forms / Services       │
│ Security / Mailer / Messenger        │
│ Notifier / EasyAdmin / Commands      │
└──────────────────┬───────────────────┘
                   │
                   ▼
┌──────────────────────────────────────┐
│           PostgreSQL 16              │
│ Doctrine ORM + Migrations            │
└──────────────────────────────────────┘

                   +

┌──────────────────────────────────────┐
│          realtime-server             │
│ Node service via server.js           │
└──────────────────────────────────────┘
```

Le dépôt contient également des services dédiés à la recherche Elasticsearch, aux notifications temps réel, à la sécurité de compte, aux factures PDF, au géocodage, aux favoris, aux devis, aux abonnements Stripe et à la synchronisation de catalogues tarifaires.

---

## 🛠️ Stack technique

### Backend

- PHP 8.4.
- Symfony 8.
- Doctrine ORM.
- Doctrine Migrations.
- Symfony Security Bundle.
- Symfony Form.
- Symfony Validator.
- Symfony Serializer.
- Symfony Mailer.
- Symfony Messenger.
- Symfony Notifier.
- Symfony Console.
- Symfony HTTP Client.
- Symfony Process.
- Symfony Translation.
- Symfony Asset.
- Symfony Asset Mapper.

### Frontend

- Twig.
- Bootstrap 5, importé dans `assets/app.js`.
- CSS applicatif via `assets/styles/app.css`.
- Stimulus.
- Turbo.
- Importmap / Asset Mapper.
- Symfony UX Autocomplete.
- Symfony UX Map.
- Symfony UX Leaflet Map.
- FullCalendar côté public/vendor et contrôleur calendrier.

### Base de données

- PostgreSQL 16.
- Doctrine DBAL.

### Administration et métier

- EasyAdmin 5.
- VichUploader.
- KnpPaginator.
- ResetPassword Bundle.
- VerifyEmail Bundle.

### Services et intégrations

- Elasticsearch.
- Guzzle.
- OAuth Google via KnpU OAuth2 Client et `league/oauth2-google`.
- Stripe avec plusieurs services et commandes métier dédiés.
- Dompdf.
- FPDF.
- FPDI.
- ZUGFeRD / Factur-X via `horstoeko/zugferd`.

### Temps réel

- `realtime-server`.
- `server.js`.
- Contrôleurs `realtimeconversationcontroller.js` et `realtimenotificationscontroller.js`.
- Utilisation de Socket.IO côté client, avec URL par défaut sur `http://localhost:3001` dans les contrôleurs front.

### Qualité et développement

- PHPUnit 13.
- Doctrine Fixtures Bundle.
- Faker.
- PHP-CS-Fixer.
- Twig CS Fixer.
- Debug Bundle.
- Maker Bundle.
- Web Profiler.
- Workflows GitHub Actions pour PHP CS Fixer et Twig CS Fixer.

---

## 🚀 Démarrage local

Le dépôt contient un projet Symfony classique avec `composer.json`, `public/index.php`, `importmap.php`, `assets/app.js`, des fichiers de configuration Symfony, ainsi qu'un serveur temps réel distinct. Le démarrage local doit donc prendre en compte à la fois l'application Symfony et les services annexes réellement présents dans le dépôt.

### 1. Installation du projet PHP

```bash
git clone https://github.com/Auxioma/prestataire.git
cd prestataire
composer install
```

### 2. Configuration de l'environnement

Le dépôt contient des fichiers de configuration comme `doctrine.yaml`, `mailer.yaml`, `messenger.yaml`, `security.yaml`, `twig.yaml`, `uxmap.yaml`, `vichuploader.yaml` et d'autres packages Symfony. Les variables d'environnement doivent être adaptées à ton environnement local avant exécution.

### 3. Base de données

```bash
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate
php bin/console doctrine:schema:validate
```

### 4. Démarrage du serveur Symfony

```bash
symfony server:start
```

### 5. Assets front

Le projet utilise Importmap, Asset Mapper, Stimulus, Bootstrap et un fichier CSS principal `assets/styles/app.css`. Les commandes suivantes sont cohérentes avec les composants présents dans le dépôt.

```bash
php bin/console importmap:install
php bin/console asset-map:compile
```

---

## ⚡ Temps réel, mailer et services

### Realtime server

Le dépôt contient explicitement un dossier `realtime-server` avec `package.json`, `package-lock.json` et `server.js`. Ce service doit être lancé séparément du serveur Symfony pour les fonctionnalités temps réel.

```bash
cd realtime-server
npm install
npm start
```

Les contrôleurs de conversation et notifications temps réel pointent côté front vers `http://localhost:3001`, ce qui confirme l'attente d'un serveur distinct pour ces flux.

### Mailer

Le dépôt contient `config/packages/mailer.yaml` ainsi qu'un service métier `ReportAdminMailer.php`. Cela confirme l'usage du composant Symfony Mailer dans l'application.

Exemple de variable à configurer :

```env
MAILER_DSN=...
```

### Messenger

Le dépôt contient `config/packages/messenger.yaml`, ce qui confirme l'utilisation de Messenger. Si ton environnement envoie certains messages dans un transport asynchrone, il faut lancer un worker dédié.

```bash
php bin/console messenger:consume async -vv
```

### Notifier

La présence de `config/packages/notifier.yaml` et d'un service `RealtimeNotifier.php` montre que les notifications font partie de l'architecture applicative.

---

## 📁 Structure du projet

Le dépôt converti permet d'identifier les répertoires et fichiers suivants.

```text
prestataire/
├── assets/
│   ├── app.js
│   ├── controllers/
│   ├── styles/
│   │   └── app.css
│   └── stimulusbootstrap.js
├── config/
│   ├── packages/
│   ├── routes/
│   ├── bundles.php
│   └── services.yaml
├── public/
│   ├── index.php
│   └── vendor/
├── realtime-server/
│   ├── package.json
│   ├── package-lock.json
│   └── server.js
├── src/
│   ├── Command/
│   ├── Controller/
│   ├── Dto/
│   ├── Entity/
│   ├── Enum/
│   ├── EventSubscriber/
│   ├── Form/
│   ├── Repository/
│   ├── Search/
│   ├── Security/
│   ├── Service/
│   └── Twig/
├── tests/
├── composer.json
├── composer.lock
├── importmap.php
├── symfony.lock
└── twig-cs-fixer.php
```

---

## 🗄️ Base de données

Le projet repose sur Doctrine ORM avec PostgreSQL et un ensemble d'entités couvrant les principaux besoins métier. Le dépôt converti montre notamment la présence des entités suivantes.

- `User`.
- `ClientProfile`.
- `PrestataireProfile`.
- `PrestataireService`.
- `PrestataireAppointment`.
- `PrestataireAvailability`.
- `PrestataireInterventionZone`.
- `Conversation`.
- `Message` et `MessageAttachment`.
- `Notification`.
- `QuoteRequest`.
- `QuoteProposal` et `QuoteProposalItem`.
- `Invoice` et `InvoiceItem`.
- `Favorite`.
- `Review`.
- `Report`.
- plusieurs entités liées aux abonnements, plans, clients Stripe et mouvements de crédits.

Commandes utiles :

```bash
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate
php bin/console doctrine:schema:validate
```

---

## 🧰 Commandes utiles

Le dépôt contient plusieurs commandes Symfony personnalisées, notamment autour d'Elasticsearch et des abonnements.

```bash
php bin/console app:elasticsearch:ping
php bin/console app:elasticsearch:reindex-prestataires
php bin/console app:subscription:install-default-plans
php bin/console app:subscription:daily-maintenance
php bin/console app:stripe:sync-subscription-prices
```

Ces libellés exacts peuvent dépendre de la définition finale des noms de commandes dans les classes concernées, mais les classes `ElasticsearchPingCommand`, `ElasticsearchReindexPrestatairesCommand`, `SubscriptionInstallDefaultPlansCommand`, `SubscriptionDailyMaintenanceCommand` et `StripeSyncSubscriptionPricesCommand` sont bien présentes dans le dépôt.

---

## ⏰ Tâche cron abonnements

La maintenance quotidienne des abonnements repose sur la commande Symfony suivante :

```bash
php bin/console app:subscription:daily-maintenance
```

En développement, elle peut être testée manuellement avec l'environnement d'outillage du projet :

```bash
bin/devtools php bin/console app:subscription:daily-maintenance -vv
```

En production, la planification doit être réalisée sur le serveur cible :

1. Ouvrir la crontab de l'utilisateur qui exécute l'application :

```bash
crontab -e
```

2. Ajouter une ligne de planification, par exemple pour une exécution tous les jours à `03:00` :

```cron
0 3 * * * cd /chemin/vers/prestataire && /usr/bin/php bin/console app:subscription:daily-maintenance --env=prod --no-debug >> var/log/cron_subscription.log 2>&1
```

3. Vérifier que la tâche est bien enregistrée :

```bash
crontab -l
```

4. Contrôler ensuite le log généré après exécution :

```bash
tail -f /chemin/vers/prestataire/var/log/cron_subscription.log
```

Le dépôt contient la commande métier, mais pas sa planification système. Cette configuration doit donc être faite sur l'environnement de production par la personne en charge de l'infrastructure ou du déploiement.

---

## 🧪 Qualité et outils de développement

Le projet est outillé pour le développement et la qualité de code avec PHPUnit, Faker, Doctrine Fixtures, PHP-CS-Fixer, Twig CS Fixer, Debug Bundle, Maker Bundle et Web Profiler. Des workflows GitHub Actions sont aussi présents pour vérifier PHP CS Fixer et Twig CS Fixer à chaque push ou pull request.

Commandes utiles :

```bash
php bin/phpunit
vendor/bin/php-cs-fixer fix
vendor/bin/twig-cs-fixer fix templates
php bin/console debug:router
php bin/console debug:container
```

---

## 👤 Auteur

<div align="center">

**COUILLET Maxime**  
_Concepteur Développeur d'Applications_

---

_Projet Symfony métier avec Twig, Bootstrap, CSS personnalisé, temps réel, administration EasyAdmin, recherche Elasticsearch et services applicatifs avancés._

</div>
