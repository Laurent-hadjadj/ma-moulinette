# 🐘 Installation d'un environnement PostgreSQL

Ce guide décrit comment installer le schéma PostgreSQL de Ma-Moulinette sur un nouvel environnement. Pour le détail du schéma (tables, domaines fonctionnels, conventions relationnelles), voir [Architecture — base de données](../architecture/architecture-base-de-donnees.md).

!!! note "🪶 Contexte historique"
    Jusqu'en v1.6.0, l'application utilisait SQLite. La migration vers PostgreSQL (v2.0.0) a été un changement ponctuel, réalisé une fois ; ce guide décrit la procédure d'installation **actuelle** pour PostgreSQL, pas la procédure de migration SQLite → PostgreSQL elle-même (voir `CHANGELOG.md` pour cet historique).

## 🧰 Prérequis

- PostgreSQL 18 installé et accessible.
- Extension PHP `pdo_pgsql` activée.
- Droits suffisants pour créer un rôle et une base (utilisateur `postgres` ou équivalent superuser).

## 🐳 Option 1 — Installation automatique (Docker)

C'est le chemin recommandé, utilisé en développement comme en production (voir [Environnement d'exécution](../architecture/architecture-technique.md#-environnement-dexécution)).
Au démarrage du conteneur `database-ma-moulinette`, `docker/scripts/postgresql/init_sql_files.sh` exécute **automatiquement** tous les fichiers `.sql` trouvés sous `migrations/PosgreSQL/` (montés sur `/docker-entrypoint-initdb.d`), triés par ordre alphabétique de chemin — c'est pourquoi les dossiers sont numérotés (`00_init/`, `10_schema/`, `20_tables/`…).

Prérequis : définir `DB_USER_PASSWORD` (mot de passe applicatif, injecté dans `01_create_roles.sql` via la variable psql `:'db_user_password'`) — voir `docker/.env.template`.

```bash
docker compose -f docker/docker-compose.yml up -d database-ma-moulinette
```

## 🖥️ Option 2 — Installation manuelle (`psql`)

Depuis le dossier `migrations/PosgreSQL/`, exécuter le script maître qui enchaîne tous les sous-dossiers dans l'ordre :

```bash
psql -U postgres -v ON_ERROR_STOP=1 -v db_user_password='<mot_de_passe>' -f 99_master_install.sql
```

Sous Windows, s'assurer de l'encodage UTF-8 :

```bash
set PGCLIENTENCODING=UTF8
```

Ce script exécute, dans l'ordre :

- suppression/création du rôle et de la base (`00_init/`),
- création du schéma (`10_schema/`),
- 45 tables (`20_tables/`),
- 5 vues (`30_views/`),
- index (`40_indexes/`),
- fonctions (`50_functions/`),
- commentaires (`60_comments/`),
- contraintes (`70_constraints/`),
- droits (`80_grants/`),
- puis les fixtures de référence (`90_fixtures/`).

## ⚙️ Configuration applicative

Dans `.env.local` (jamais dans `.env` versionné) :

```bash
DATABASE_URL="postgresql://<utilisateur>:<mot_de_passe>@<hôte>:<port>/<base>?serverVersion=18&charset=utf8"
```

## ✅ Vérification

```bash
psql -U db_user -d ma_moulinette -c "SELECT version, date_version FROM ma_moulinette.ma_moulinette ORDER BY id DESC LIMIT 1;"
```

Doit retourner la dernière version applicative enregistrée dans les fixtures.

## 🛠️ Commande de maintenance ponctuelle : migration `compte_rendu`

!!! WARNING "Attention"
    Le champ `compte_rendu` de `BatchExecutionJournal` a été migré de `TEXT` vers `BYTEA` gzippé (v2.0.0 — voir [Traitement](../back-office/traitement.md#-suivi-dexécution)).

La commande `app:migrate-compte-rendu` (`src/Command/Maintenance/MigrateCompteRenduCommand.php`) reste disponible pour rejouer cette migration sur un environnement pas encore à niveau :

```bash
# Simulation, sans modifier la base
php bin/console app:migrate-compte-rendu --dry-run

# Migration par lots de 100 (défaut), reprise possible via --start-id
php bin/console app:migrate-compte-rendu --batch-size=100 --start-id=0
```

## 📚 Pour aller plus loin

- [Architecture — base de données](../architecture/architecture-base-de-donnees.md) : schéma détaillé, conventions relationnelles.
- [Pour bien démarrer](pour_bien_demarrer.md) : mise en place complète d'un environnement de développement.

-**-- FIN --**-

[Retour au menu principal](/index.html)
