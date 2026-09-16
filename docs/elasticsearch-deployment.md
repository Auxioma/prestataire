# Synchronisation Elasticsearch

## Comportement

Après acceptation d’un SIRET exact et actif, le profil est sauvegardé puis synchronisé avec Elasticsearch. La réponse d’indexation attend sa visibilité dans la recherche (`refresh=wait_for`). Les services, zones, informations publiques, statuts administratifs et suppressions sont aussi synchronisés par les événements Doctrine. Les profils masqués, suspendus ou non vérifiés sont exclus. La validation manuelle et documentaire reste admise.

Une tâche par profil est enregistrée dans `prestataire_search_job`, dans la transaction métier. En cas de panne, elle reste en base et le worker la reprend avec un délai croissant, jusqu’à une heure entre tentatives. La tâche n’est pas abandonnée après un nombre limité d’échecs. Le traitement relit l’état actuel du profil et ne publie pas une transaction annulée. Les changements SQL directs qui contournent Doctrine nécessitent une reconstruction.

## Installation en production

Les changements sont appliqués et testés localement. La session ne dispose pas d’un accès identifié au serveur de production.

1. Fournir au processus PHP les valeurs réelles de `ELASTICSEARCH_HOST`, `ELASTICSEARCH_USER`, `ELASTICSEARCH_PASSWORD`, `ELASTICSEARCH_VERIFY_TLS=1` et, pour une CA privée, `ELASTICSEARCH_CA_CERT`. Le certificat public doit être lisible par l’utilisateur PHP. Aucun contournement TLS implicite n’est conservé. Les secrets restent dans l’environnement serveur ou un fichier protégé hors Git. L’utilisateur applicatif doit pouvoir rechercher, indexer et supprimer des documents ; la reconstruction nécessite en plus les droits de création d’index et de gestion des alias.
2. Avant d’activer le nouveau code pour les requêtes web, créer la table de file. Une autre migration était déjà en attente localement : la commande ci-dessous cible uniquement cette modification.

```bash
php bin/console doctrine:migrations:execute 'DoctrineMigrations\Version20260916120000' --up --no-interaction --env=prod --no-debug
php bin/console cache:clear --env=prod --no-debug
php bin/console app:elasticsearch:ping --env=prod --no-debug
php bin/console app:elasticsearch:reindex-prestataires --env=prod --no-debug
php bin/console app:elasticsearch:process-queue --env=prod --no-debug
```

La reconstruction crée un index `prestataires_search_v2_*`, le remplit depuis la base, vérifie son compteur puis bascule l’alias `prestataires_search` en une opération. Les anciens index sont conservés. Le nombre de répliques vaut 1 par défaut ; `--replicas=0` convient au développement avec un seul nœud. Les traitements individuels sont suspendus pendant la reconstruction, et leurs tâches restent en attente. Prévoir une courte fenêtre de maintenance lors de la première activation, car l’ancien code utilise un index physique et le nouveau code utilise l’alias.

3. Installer la reprise une fois par minute dans la crontab de l’utilisateur applicatif, en adaptant les chemins et le binaire PHP. `flock` évite le chevauchement de deux exécutions.

```cron
* * * * * /usr/bin/flock -n /chemin/prestataire/var/elasticsearch-worker.lock /usr/bin/php /chemin/prestataire/bin/console app:elasticsearch:process-queue --env=prod --no-debug --limit=100 >> /chemin/prestataire/var/log/elasticsearch-worker.log 2>&1
```

Vérifier que cron est actif et que l’utilisateur peut écrire les fichiers de verrou et de log. Prévoir la rotation de `elasticsearch-worker.log`. Le poste local utilise la même tâche en environnement `dev` ; cela ne configure pas le serveur de production.

## Contrôles et maintenance

Vérifier le nom d’une entreprise depuis la barre et la page de résultats, puis ajouter une prestation et confirmer qu’elle est retrouvée. Tester une suspension et vérifier sa disparition. La reprise après panne peut être vérifiée sur un environnement de recette.

```sql
SELECT COUNT(*) AS pending, MIN(available_at) AS next_attempt
FROM prestataire_search_job;

SELECT profile_id, attempts, available_at, last_error
FROM prestataire_search_job
WHERE attempts > 0
ORDER BY attempts DESC;
```

Les logs conservent l’identifiant du profil et la classe d’erreur, sans messages susceptibles de contenir des identifiants de connexion. Surveiller l’accumulation de tâches et le statut du cluster. Une panne ne permet pas de garantir une recherche immédiate : le profil est conservé et publié après reprise.

`app:elasticsearch:create-index` crée désormais uniquement un nouvel index physique ; il ne supprime ni ne remplace l’index actif. Pour une réconciliation complète, utiliser `reindex-prestataires`. Supprimer les anciens index uniquement après validation et selon une politique de conservation explicite.

## Tests

Validation locale du 16 septembre 2026 : nouvel index activé via l’alias, 21 profils éligibles indexés, état `green`, vérification TLS stricte HTTP 200. Une entreprise existante renvoie désormais 1 résultat dans la recherche d’accueil et 1 dans l’autocomplétion. La table de file a été créée, la crontab locale installée et sa première exécution constatée dans le log du worker.

Suite complète : 21 tests, 115 assertions, aucune erreur ni échec. Quatre notices PHPUnit préexistantes concernent les mocks des tests de voter et de PDF. Les 10 tests ciblés Elasticsearch/SIRET passent sans notice (76 assertions). Les contrôles de conteneur Symfony passent en `dev` et `prod` ; les 33 fichiers YAML sont valides. Le contrôle `prod` est un contrôle de configuration sur le poste local, pas un test du serveur déployé.

```bash
php bin/phpunit tests/Search tests/Service/CompanyRegistryClientTest.php
ELASTICSEARCH_INTEGRATION=1 php bin/phpunit tests/Integration/PrestataireSearchSynchronizationTest.php
```

Les tests d’intégration utilisent PostgreSQL local dans un schéma temporaire supprimé après chaque test, avec un émulateur HTTP Elasticsearch. Ils lisent la connexion locale depuis `.env.local`, ou `SEARCH_TEST_DATABASE_URL` si fourni. Les connexions distantes sont refusées. Ils couvrent l’acceptation SIRET, les services/zones, la suspension, la validation manuelle, le masquage, la panne/reprise, l’annulation d’une transaction, le changement de SIRET et la suppression physique. Les tests en lecture seule sur le véritable Elasticsearch local vérifient également le mapping, la recherche, l’autocomplétion et TLS.
