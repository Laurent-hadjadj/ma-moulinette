# 🚀 Pour bien démarrer avec Ma-Moulinette

Ma-Moulinette est une application open source sous licence CC BY-NC-SA 4.0. Le code source ne suffit pas à lui seul : il faut un environnement d'exécution (PHP, PostgreSQL) et, en développement, quelques outils complémentaires.

## 🧰 Prérequis

| Composant | Développement | Production |
| --- | --- | --- |
| PHP | ≥ 8.4 (8.5.5 utilisé) | ≥ 8.4 |
| PostgreSQL | 18 | 18 |
| Symfony CLI | recommandé (serveur local) | non requis (nginx + PHP-FPM) |
| SonarQube | 8.9.9 LTS, 9.9.4 LTS, 10 LTA, 2024, 2025 LTA ou 2026 | idem |
| Node.js | uniquement pour les tests E2E Playwright (`tests/e2e/`), pas pour les assets applicatifs | non requis |
| Python 3 + mkDocs Material | pour générer cette documentation | non requis |

!!! note "🧶 Plus de build JS/CSS séparé"
    Depuis la migration vers **Symfony asset-mapper** (v2.0.0), il n'y a plus de compilation Webpack/Encore ni de dépendance Node.js pour builder les assets applicatifs — ils sont servis directement.
    Node.js n'est nécessaire que pour la suite de tests End-to-End Playwright.

## 📁 Fichiers d'environnement

| Fichier | Rôle | Suivi Git |
| --- | --- | --- |
| `.env` | Valeurs par défaut, communes à tous les environnements | ✅ commité |
| `.env.local` | Surcharges locales (secrets, hosts, LDAP, tokens) | ❌ ignoré |
| `.env.test` | Valeurs par défaut pour `tests/Integration` (placeholders non sensibles) | ❌ ignoré |
| `.env.test.local` | Surcharges locales pour les tests (tokens de test, LDAP local) | ❌ ignoré |
| `.env-prod` | Base pour un déploiement production (avant `composer dump-env prod`) | ✅ commité |
| `docker/.env.template` | Gabarit des variables Docker/infra (mots de passe, tokens, Traefik) avec valeurs d'exemple `changeMe_*` | ✅ commité |

!!! warning "🔒 Jamais de secret dans un fichier commité"
    `.env`, `.env.test` et `docker/.env.template` ne doivent contenir que des valeurs par défaut non sensibles ou des placeholders explicites (`changeMe_*`). Les vraies valeurs vont dans `.env.local`/`.env.test.local` (ignorés par Git) ou dans le gestionnaire de secrets de l'environnement cible.

Activer le mode développement :

```bash
APP_ENV=dev
APP_DEBUG=1
```

## 🖥️ Démarrer en développement

Deux façons de démarrer l'environnement de développement, selon les habitudes :

### Option 1 — Docker (recommandé, aligné sur la prod)

Voir [Environnement d'exécution](../architecture/architecture-technique.md#-environnement-dexécution) pour le détail des 4 services (`database-ma-moulinette` — conteneur nommé `postgresql-ma-moulinette` —, `php-fpm-ma-moulinette`, `cron-ma-moulinette`, `nginx-ma-moulinette`).
Copier `docker/.env.template` en `docker/.env`, adapter les valeurs, puis :

```bash
docker compose -f docker/docker-compose.yml up -d
```

### Option 2 — Outillage natif Windows (`bin/`)

Le dossier `bin/` regroupe les scripts par outil :

| Sous-dossier | Contenu |
| --- | --- |
| `bin/symfony/` | Démarrage/arrêt du serveur `symfony-cli`, console CLI |
| `bin/postgresql/` | Scripts d'installation, sauvegarde/restauration, maintenance PostgreSQL 18 |
| `bin/phpunit` | Script wrapper de lancement de PHPUnit (fichier, pas un sous-dossier) |
| `bin/e2e/` | Scripts de préparation/exécution des tests E2E (reconstruction de base, seed, capture de fixtures) |
| `bin/mkdocs/` | Serveur local de cette documentation |
| `bin/cli/` | Scripts CLI de collecte/mise à jour appelés en dehors du serveur web (ex. `run_ma-moulinette_collecte_cli.php`) |
| `bin/git/` | Utilitaires Git (réécriture d'historique) |
| `bin/php-conf/` | `php.ini` de référence pour la version PHP utilisée |

## 🎨 Assets publics des bundles tiers (`public/bundles/`)

L'application sert ses propres assets (JS/CSS applicatifs) via **Symfony asset-mapper**, sans étape de build (voir la note ci-dessus). Mais certains bundles tiers — **EasyAdmin en premier lieu** — embarquent leurs propres CSS/JS déjà compilés (ex. `app.db37a8e3.css`, `app.c2478f67.js`) dans leur dossier `Resources/public/` (ou `public/` côté bundle).
Ces fichiers ne passent **pas** par asset-mapper : ils doivent être publiés dans `public/bundles/<nom-du-bundle>/` via la commande classique Symfony :

```bash
php bin/console assets:install --symlink --relative
```

`public/bundles/` est **gitignored** et n'est jamais créé automatiquement — ni au premier clone, ni après un `composer update` qui change la version d'un bundle avec des assets publics (typiquement EasyAdmin).

!!! caution "⚠️ Symptôme si oublié : pages EasyAdmin cassées (CSS absent, erreurs JS)"
    Sans `public/bundles/easyadmin/`, le navigateur réclame des fichiers introuvables comme `http://localhost:8000/bundles/easyadmin/app.db37a8e3.css` (404) — la page `/admin` (et toutes les pages EasyAdmin : CRUD utilisateurs, groupes, portefeuilles…) s'affiche alors sans mise en forme et avec des erreurs JS bloquantes, ce qui peut ressembler à tort à un problème de cache navigateur ou d'asset-mapper.
    Sur Windows sans privilèges pour les liens symboliques, `assets:install` bascule automatiquement sur une copie des fichiers (`--symlink` échoue silencieusement en `copy`, sans erreur bloquante) — c'est normal, il suffit de relancer la commande après chaque mise à jour du bundle.

## 🔑 Fichier JS local `secrets.local.js`

Trois entrées JS (`accueil/index-accueil.js`, `profil/index-profil.js`, `projet/peinture.js`) importent dynamiquement `assets/js/common/secrets.local.js`, un fichier personnel non commité (`**/secrets.local.js` dans `.gitignore`). Après un premier clone, copier le gabarit fourni :

```bash
cp assets/js/common/secrets.local.js.dist assets/js/common/secrets.local.js
```

!!! caution "⚠️ Symptôme si oublié : erreur 500 sur toutes les pages"
    Le code JS gère bien l'absence de ce module au chargement dans le navigateur (`.catch()`), mais Symfony **AssetMapper** analyse statiquement l'`importmap` au rendu de la page côté serveur et lève une exception fatale si le fichier n'existe pas physiquement sur le disque — indépendamment de ce `.catch()`. Sans ce fichier, toute page utilisant le layout de base (donc la quasi-totalité de l'application, y compris `tests/Integration`) répond en 500.

## 🧪 Base de données de développement

Voir [Architecture — base de données](../architecture/architecture-base-de-donnees.md) pour le détail du schéma et [Migration PostgreSQL](guide-migration.md) pour la procédure d'installation complète d'un nouvel environnement.

## 📦 Déploiement en production

```bash
# Passage en mode production
APP_ENV=prod
APP_DEBUG=0
```

- Changer `APP_SECRET` et `SECRET` (jamais réutiliser les valeurs de développement).
- Nettoyer `var/cache/{dev,prod}` et `var/log/dev.log`.
- Générer le fichier d'environnement compilé :

```bash
composer dump-env prod
symfony composer dump-autoload --no-dev --classmap-authoritative
php bin/console asset-map:compile
```

## ✉️ Convention de rédaction des messages (logs, flash, JS)

Trois canaux de message coexistent dans l'application, chacun avec ses propres règles de forme — à respecter pour tout nouveau code :

| Canal | Préfixe `[TAG]` | Emoji dans le texte | Pourquoi |
| --- | --- | --- | --- |
| Logger (`$this->logger->info/warning/error(...)`) | ✅ Oui (ex. `[OWASP]`, `[Batch OWASP]`) | ✅ Oui | Permet de filtrer/grep les logs par origine ; rien d'autre n'affiche ce tag. |
| Message flash serveur (`addFlash('notice', ['type' => ..., 'message' => ...])`) | ❌ Non | ✅ Oui | Le gabarit d'affichage ne peut pas injecter d'icône à partir de `type` : l'emoji doit être écrit dans le texte du message lui-même. |
| Message JS (`showMessage(type, message)`) | ❌ Non | ❌ Non | Le type (`info`/`error`/`warning`/`critical`...) est déjà utilisé par `showMessage()` pour injecter l'icône/la couleur appropriée — un emoji écrit en dur ferait doublon. |

Un même texte de message ne doit donc **jamais** être réutilisé tel quel entre un appel logger et un message utilisateur (flash ou JS) : construire deux chaînes distinctes si besoin, comme le fait `ProjetPerimetreGuard::verifierPerimetreProjet()`.

## 📚 Pour aller plus loin

- [Architecture technique](../architecture/architecture-technique.md) : stack, conteneurs Docker, variables d'environnement.
- [Gestion de la sécurité](securite.md) : rôles, hôtes de confiance.
- [Tests unitaires](test-unitaire.md) : lancer la suite de tests.
- [Travailler à plusieurs](tuto-git.md) : conventions Git du projet.

-**-- FIN --**-

[Retour au menu principal](/index.html)
