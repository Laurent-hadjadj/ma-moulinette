# 📖 Référence des scripts

Tous les fichiers sont dans `docker/demo/`. Cette page répond, pour chacun : **où** le lancer, **quand**, **pourquoi**, avec quelles **options**, et **ce qu'il modifie**.

## 1️⃣ 🗂️ Vue d'ensemble

* [`preparer-donnees.sh`](#2️⃣-preparer-donneessh)
* [`deployer-demo.sh`](#3️⃣-deployer-demosh)
* [`restaurer-demo.sh`](#4️⃣-restaurer-demosh)
* [`verifier-sonar.sh`](#5️⃣-verifier-sonarsh)
* [les fichiers internes](#6️⃣-fichiers-internes)

## 2️⃣ `preparer-donnees.sh`

**Où :** poste de développement (PHP, `vendor/` et Docker présents). **Jamais sur le serveur.**

**Pourquoi :** fabriquer le jeu de données de la démo à partir de SonarQube, sans jamais toucher à une base existante.

**Ce qu'il modifie :** la base **jetable** de préparation (PostgreSQL sur `127.0.0.1:5435`) et le fichier `docker/demo/donnees/demo.sql.gz`. Il ne touche ni à la base de développement, ni à la production, ni à la démo.

**Prérequis :** `docker/demo/.env` avec `DEMO_SONAR_URL`, `DEMO_SONAR_TOKEN`, `DEMO_PREPA_POSTGRES_PASSWORD` et `DEMO_PREPA_DB_USER_PASSWORD`.

```bash
bash docker/demo/preparer-donnees.sh [options]
```

| Option | Effet |
| --- | --- |
| `--reinitialiser` | détruit la base jetable (volume compris) avant de recommencer |
| `--projet <clé>` | ne collecte que ce projet (répétable) |
| `--tag <préfixe>` | préfixe de tag SonarQube (défaut `ma-moulinette`) |
| `--sans-historique` | ne reconstruit pas l'historique par version |
| `--liste-seulement` | affiche les projets retenus, sans collecter ni exporter |
| `--sans-export` | collecte sans écrire `demo.sql.gz` |
| `--export-seulement` | réexporte la base telle quelle, sans recollecter |

Détail et cas d'usage de chaque option : [Préparer les données](donnees.md#options-de-preparer-donneessh).

La commande Symfony sous-jacente est `php bin/console app:demo:preparer` (options `--tag`, `--projet`, `--sans-historique`, `--liste-seulement`). Ne la lancez pas directement : elle refuse de s'exécuter hors `APP_ENV=dev` et pour toute connexion autre que la base jetable, et le script lui fournit les bonnes variables d'environnement.

## 3️⃣ `deployer-demo.sh`

**Où :** serveur, depuis le clone du dépôt.

**Pourquoi :** installer ou mettre à jour la démo **sans action manuelle** : même enchaînement que le pipeline de production (récupérer, construire, déployer, rejouer le SQL).

```bash
bash docker/demo/deployer-demo.sh <tag>                  # déploiement complet
bash docker/demo/deployer-demo.sh <tag> --env-seulement  # génère uniquement le .env (contrôle)
```

| Paramètre ou variable | Rôle |
| --- | --- |
| `<tag>` (obligatoire) | version à déployer : un tag git existant, par exemple `v2.9.0` |
| `--env-seulement` | ne régénère que `.env` et s'arrête. Les mots de passe de la **base existante** ne sont **pas** modifiés : ne recréez pas les conteneurs avant un déploiement complet |
| `SRV` | dossier de travail (défaut `/opt/DOCKER/ma-moulinette-demo`) : `depot/`, `app/`, `sauvegardes/` |
| `DEPOT_URL` | dépôt à cloner si `depot/` n'existe pas (défaut : le dépôt public) |
| `DEMO_SONAR_URL`, `DEMO_SONAR_TOKEN` | adresse et jeton SonarQube, pris en compte **au premier déploiement seulement** ; ensuite repris du `.env` |

**Ce qu'il modifie :**

| Élément | Effet |
| --- | --- |
| `depot/` | `git fetch` puis `checkout` du tag |
| `app/` | remplacé par la nouvelle construction ; l'ancienne version est archivée dans `sauvegardes/app-AAAAMMJJ-HHMM.tar.gz` |
| `docker/demo/.env` | régénéré : mots de passe de la base et clé applicative **changent** ; le mot de passe du compte de démonstration et les paramètres SonarQube sont **conservés**. L'ancien est copié dans `sauvegardes/env-AAAAMMJJ-HHMM` |
| base PostgreSQL | si elle existe déjà, ses mots de passe sont mis à jour par `ALTER ROLE` ; puis les données sont rechargées (`restaurer-demo.sh`) |
| conteneurs | recréés |

**Garde-fous :** le script s'arrête au premier échec. Si la construction est incomplète (assets manquants), l'ancienne version reste en place. Si le tag apporte une nouvelle version du script, il se relance lui-même. Étapes détaillées : [Étapes détaillées](etapes-detaillees.md).

## 4️⃣ `restaurer-demo.sh`

**Où :** serveur (hôte), jamais dans un conteneur.

**Pourquoi :** remettre la base dans son **état de référence** : nouvelles données, remise à zéro après des modifications de visiteurs, restauration nocturne.

```bash
bash docker/demo/restaurer-demo.sh [fichier.sql.gz]
```

| Paramètre | Rôle |
| --- | --- |
| `fichier.sql.gz` (facultatif) | données à charger (défaut `donnees/demo.sql.gz` ; s'il n'existe pas, la base garde le schéma seul) |

**Ce qu'il fait, dans l'ordre :** arrête l'application (la base reste lancée) → recrée la base `ma_moulinette` et rejoue le schéma → charge les données → applique `reference-demo.sql` (supprime le compte d'initialisation, crée le compte de démonstration avec le mot de passe de `.env`) → redémarre l'application.

**Ce qu'il modifie :** **toute** la base de la démo est remplacée. Il peut se lancer à tout moment ; la démo est indisponible pendant l'opération. Il se planifie sur l'hôte (la démo n'embarque aucun cron) : voir [Installation initiale](installation.md#planifier-la-restauration-nocturne).

## 5️⃣ `verifier-sonar.sh`

**Où :** poste de développement.

**Pourquoi :** s'assurer, **en lecture seule**, que le compte SonarQube de la démo n'a aucun droit global et ne peut ni écrire ni lire le code source.

```bash
bash docker/demo/verifier-sonar.sh [login_attendu]       # défaut : ma-moulinette-demo
```

**Ce qu'il modifie :** rien. Il lit `DEMO_SONAR_URL` et `DEMO_SONAR_TOKEN` dans `docker/demo/.env` et n'affiche jamais le jeton.

## 6️⃣ Fichiers internes

| Fichier | Rôle |
| --- | --- |
| `docker-compose.demo.yml` | la pile de la démo : PostgreSQL, PHP-FPM (code monté depuis `app`), nginx sur `127.0.0.1:8081` |
| `docker-compose.prepa.yml` | la base jetable de préparation (`127.0.0.1:5435`), utilisée seulement par `preparer-donnees.sh` |
| `init-schema.sh` | au **premier démarrage** d'un volume PostgreSQL vide : crée le rôle `db_user`, la base `ma_moulinette` et rejoue le schéma |
| `assembler-schema.sh` | assemble le SQL du schéma depuis `migrations/POSTGRESQL/99_master_install.sql`, **en excluant** les scripts `00_init` (locale Windows) et `90_fixtures/fixtures-e2e.sql` (comptes de test aux mots de passe connus) |
| `reference-demo.sql` | état de référence des comptes : supprime les comptes d'initialisation, crée le compte de démonstration (rôles `ROLE_UTILISATEUR` et `ROLE_SUIVI`) et son groupe |
| `nginx.conf` | virtual host HTTP interne (le TLS est porté par le reverse proxy de l'hôte) |
| `.env.exemple` | modèle du `.env` : variables, type attendu de chaque secret, et celles qui ne servent qu'au poste de développement |
| `.env` (ignoré par git) | valeurs réelles : sur le poste (SonarQube, mots de passe de préparation) et sur le serveur (secrets générés) |
| `donnees/demo.sql.gz` (ignoré par git) | jeu de données produit par `preparer-donnees.sh` |

Les secrets et leurs types sont décrits dans [Sécurité et mode démo](securite.md#secrets-de-dockerdemoenv).
