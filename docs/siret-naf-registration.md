# Admission des prestataires par SIRET / NAF

Une vérification préalable est accessible sur `/register/prestataire/siret`. Les routes et les trois étapes d’inscription existantes restent disponibles, mais requièrent une admission valide en session. L’inscription Google des nouveaux prestataires est également contrôlée ; la connexion des comptes existants et l’inscription client sont conservées.

Le SIRET exact doit correspondre à un établissement actif. Le code NAF est lu dans `activite_principale` de cet établissement, jamais dans une valeur saisie par l’utilisateur ni dans le code du siège d’un établissement secondaire. Il est normalisé au format `43.22A`, puis comparé aux codes actifs de `allowed_naf_code`. La migration contient les 95 codes uniques du document du lead ; les deux doublons 74.20Z et 82.30Z sont fusionnés en conservant le premier libellé.

Un code non autorisé affiche : « Votre activité ne correspond pas à TrouveMoi Prestataires. Veuillez vous inscrire sur l’autre plateforme “TrouveMoi”. » Aucun lien n’est inventé : l’URL de cette autre plateforme n’a pas été fournie. Une panne de l’API, un SIRET inconnu, un établissement fermé ou un code absent affiche un message distinct.

L’admission expire après 30 minutes. La liste est contrôlée à chaque étape et le SIRET est revérifié avant création du compte, y compris via Google. Le SIRET, le NAF et les informations d’entreprise sont enregistrés sur le profil. La vérification d’admission ne prouve pas que l’inscrit est propriétaire de l’entreprise ; le parcours existant de validation du compte et d’activation du profil reste applicable. Les comptes existants ne sont pas rétroactivement exclus.

## Déploiement

Cette fonctionnalité dépend des changements de la branche Elasticsearch sur laquelle elle est développée. Créer la table et le champ avant activation du nouveau code :

```bash
php bin/console doctrine:migrations:execute 'DoctrineMigrations\Version20260916130000' --up --no-interaction --env=prod --no-debug
php bin/console cache:clear --env=prod --no-debug
```

Migration appliquée localement. Ne pas réexécuter une migration déjà enregistrée. La liste peut être désactivée ou enrichie en base ; il n’est pas nécessaire de modifier le code applicatif pour changer les activités acceptées.

```sql
UPDATE allowed_naf_code SET is_active = FALSE WHERE code = '43.22A';
```

La liste fournie correspond à la NAF rév. 2 utilisée en 2026. Préparer sa mise à jour avant le 1er janvier 2027, date d’entrée en vigueur des nouveaux codes APE : [source Insee](https://www.insee.fr/fr/information/8181066). L’application utilise le code courant retourné par l’API, pas le champ anticipé `activite_principale_naf25`.

## Validation locale

Les tests couvrent l’admission, le refus, le code absent, l’établissement fermé, l’expiration, la désactivation d’un code, la panne API et un changement de NAF lors de la revérification finale. Contrôles HTTP locaux effectués : étape finale sans admission redirigée, page SIRET HTTP 200, SIRET incomplet bloqué, inscription Google sans admission bloquée, inscription client HTTP 200. Le compteur en base confirme 95 codes actifs. Twig et l’injection de services en production ont été vérifiés sur le poste local ; aucun déploiement distant n’a été effectué.
