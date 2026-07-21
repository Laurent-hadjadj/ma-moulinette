# 🦊 Automatisation des tâches (CI/CD GitLab)

Le pipeline GitLab CI (`.gitlab-ci.yml`) automatise la compilation et le déploiement de l'application. Ce document décrit le **mécanisme** ; les valeurs d'environnement réelles (hôtes de déploiement, secrets SSH) sont configurées comme variables CI/CD côté GitLab, jamais dans ce fichier ni dans cette documentation.

!!! note "🎓 Un pipeline d'exemple, pas un pipeline de production"
    Ce `.gitlab-ci.yml` est fourni comme **modèle de départ** pour qui clone Ma-Moulinette et souhaite l'héberger sur son propre GitLab privé : il montre un enchaînement build → deploy fonctionnel, à adapter à son infrastructure.
    Ce n'est pas le pipeline d'une instance de production existante, et rien n'y est spécifique à un environnement particulier.

!!! warning "🔒 Aucune valeur d'infrastructure ici"
    Noms d'hôtes de déploiement, chemins serveur, clés SSH : ces valeurs vivent exclusivement dans les variables CI/CD protégées de GitLab (`Settings → CI/CD → Variables`).
    Ne jamais les copier dans `.gitlab-ci.yml` en clair ni dans la documentation.

## 🪜 Stages

```mermaid
flowchart LR
    A[pre-quality] --> B[build] --> C[deploy]
```

| Stage | État | Rôle |
| --- | --- | --- |
| `pre-quality` | scaffoldé, aucun job actif | Prévu pour des contrôles qualité en amont du build (ex. analyse statique, lint) |
| `build` | `build_ma_moulinette_job` | Installation des dépendances, compilation des assets, packaging |
| `deploy` | `deploy_ma_moulinette_job` | Déploiement manuel (`when: manual`) vers le serveur cible |

## 🏗️ Job de build

Exécuté dans une image `composer:lts`, avec un service PostgreSQL éphémère lié (`postgres:18.1-alpine3.22`, alias `postgresql-ma-moulinette` — doit correspondre au host attendu par `DATABASE_URL`).

Étapes principales :

1. Installation des extensions PHP nécessaires (`pdo_pgsql`, `ldap`).
2. `composer install` (avec ou sans `--no-dev` selon `APP_ENV`).
3. `php bin/console importmap:update` / `importmap:require` (gestion des dépendances JS via asset-mapper, pas de build Node).
4. Si `APP_ENV=prod` : `composer dump-env prod` puis réinstallation optimisée (`--no-dev --optimize-autoloader`).
5. `php bin/console asset-map:compile`.

Le résultat est publié comme artefact GitLab (durée de vie 30 min), récupéré par le job de déploiement.

## 🚀 Job de déploiement

Manuel (`when: manual`), déclenché uniquement sur événement `web`. Récupère l'artefact du job de build via l'API GitLab, puis :

1. Archive l'ancienne version côté serveur cible (`tar.gz` horodaté).
2. Déploie la nouvelle version par SSH/SCP.
3. Pousse les scripts SQL d'évolution (`migrations/updates/`) et les exécute côté serveur via `docker exec ... execute_sql_files.sh` sur le conteneur PostgreSQL — voir [Installation d'un environnement PostgreSQL](guide-migration.md) pour le détail de ce script.

## 🎛️ Paramétrage par environnement

Le pipeline utilise les [inputs GitLab CI](https://docs.gitlab.com/ee/ci/inputs/) (`spec.inputs`) pour choisir l'environnement cible au déclenchement manuel : serveur de déploiement, `APP_ENV` (`dev`/`prod`), `APP_DEBUG`, et les hôtes de confiance (`TRUST_HOST1`/`TRUST_HOST2`, voir [Gestion de la sécurité](securite.md#-filtrage-par-hostproxy)).

## 📚 Pour aller plus loin

- [Pour bien démarrer](pour_bien_demarrer.md) : mise en place d'un environnement local (hors CI).
- [Installation d'un environnement PostgreSQL](guide-migration.md) : détail du mécanisme d'exécution des scripts SQL, réutilisé en déploiement.
- [Gestion de la sécurité](securite.md) : hôtes de confiance, variables sensibles.

-**-- FIN --**-

[Retour au menu principal](/index.html)
