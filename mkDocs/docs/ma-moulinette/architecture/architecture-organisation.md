# 🗂️ Organisation du code et des environnements

## 📦 Environnements

L'application distingue plusieurs environnements Symfony (`APP_ENV`) : `dev`, `test` et `prod`. Chacun a ses fichiers `.env` dédiés — voir [Pour bien démarrer](../developpement/pour_bien_demarrer.md#-fichiers-denvironnement) pour la liste complète et la règle « jamais de secret commité ».

```bash
# Développement
APP_ENV=dev
APP_DEBUG=1

# Production
APP_ENV=prod
APP_DEBUG=0
```

`APP_SECRET` (chiffrement CSRF, session) doit être une valeur unique et aléatoire par environnement, générée à l'installation — jamais réutilisée entre dev/test/prod, jamais copiée depuis un exemple de documentation.

## 🌳 Organisation des dossiers (`src/`)

Le code applicatif suit l'arborescence Symfony standard, avec un regroupement des controllers par domaine fonctionnel plutôt qu'à plat :

```text
src/
├── Controller/
│   ├── Accueil/            # Page d'accueil, favoris
│   ├── Admin/              # Back-office EasyAdmin (CRUD Utilisateur/Groupe/Portefeuille/Batch)
│   ├── Actuator/           # Collecte Actuator (Spring Boot)
│   ├── Batch/              # Traitement automatique/manuel
│   ├── CleanCode/          # Indicateurs Clean Code
│   ├── DependencyCheck/    # Module OWASP DependencyCheck
│   ├── Profiling/          # Mesures de performance batch
│   ├── Projet/             # Page projet, collecte SonarQube
│   ├── Repartition/        # Répartition par module
│   ├── Statistique/        # Statistiques d'usage transverses
│   └── Suivi/              # Suivi de versions favorites
├── Entity/                 # Entités Doctrine (mapping 1:1 avec les tables PostgreSQL)
├── EventSubscriber/        # Filtrage sécurité API, tracking
├── Repository/             # Requêtes SQL/DQL, une classe par entité
├── Security/               # CustomAuthenticator (local + LDAP), ApiSecurityHandler
├── Service/                # Logique métier (collecte, export PDF, DependencyCheck…)
├── Util/                   # Fonctions utilitaires transverses
└── DataFixtures/           # Fixtures Doctrine (tests, hors périmètre production)
```

Ce regroupement par domaine (`Controller/<Domaine>/`) facilite la navigation lorsque le nombre de controllers augmente — voir [Architecture technique](architecture-technique.md) pour la vue d'ensemble applicative.

## ⚙️ Installation des dépendances

```bash
composer install
```

Aucune dépendance Node.js n'est nécessaire pour l'application elle-même (asset-mapper) — voir la note dans [Pour bien démarrer](../developpement/pour_bien_demarrer.md). Node.js n'est requis que pour la suite de tests E2E Playwright (`tests/e2e/`).

## 📚 Pour aller plus loin

- [Architecture technique](architecture-technique.md) : stack, conteneurs, sécurité API.
- [Pour bien démarrer](../developpement/pour_bien_demarrer.md) : mise en place d'un environnement de développement.
- [Gestion de la sécurité](../developpement/securite.md) : rôles, hôtes de confiance.

-**-- FIN --**-

[Retour au menu principal](/index.html)
