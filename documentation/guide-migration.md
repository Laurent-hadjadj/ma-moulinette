# Guide Complet de la Migration de la Base de Données SQLite vers PostgreSQL

![Ma-Moulinette](/documentation/ressources/home-000.jpg)

Ce guide détaille les étapes nécessaires pour configurer une base de données PostgreSQL en migrant les données depuis SQLite. Il couvre la création du schéma, des tables, et l'insertion des données.

## Prérequis

- [x] PostgreSQL installé sur un serveur.
- [x] Activer le client PostgreSQL pour php (pdo_pgsql et pgsql).
- [x] Droits d'administrateur pour créer des schémas, des rôles et insérer des données.
- [x] Accès au terminal ou à un client SQL capable de se connecter à PostgreSQL.

## Préparation des Scripts SQL

Trois scripts sont nécessires :

1. `01_migration_postgre.sql` pour la structure de la base de données ;
2. `02_fixtures_postgre.sql`pour les données initiales ;
3. `03_grant_privileges_postgre.sql` pour données les droits à utilisateurs à la base de données ;

> Important !

- [ ] Le nom de la base, su schema et du role sont fixé par défaut à `ma_moulinette`.
- [ ] Le mot de passe est par défaut : `db_password`. **Vous devez le changer**.

### Script de Création de Schéma et de Tables

```sql
-- Remplacez 'ma_moulinette' pour le nom du schema et du role.
-- Remplacez 'votre_secure_password' par un mot de passe sécurisé

-- Création du schema
CREATE SCHEMA ma_moulinette;

-- Création d'un rôle
CREATE ROLE ma_moulinette LOGIN PASSWORD 'votre_secure_password';

-- Création des tables :

-- Création de la table activite

CREATE TABLE ma_moulinette.activite (
  id INTEGER NOT NULL, -- Identifiant unique de l'activité
  maven_key varchar(255) NOT NULL, -- Clé Maven unique identifiant le projet
  project_name varchar(64) NOT NULL, -- Nom du projet associé à l'activité
  analyse_id varchar(26) NOT NULL, -- Identifiant de l'analyse
  status varchar(16) NOT NULL, -- Statut de l'activité
  submitter_login varchar(32) NOT NULL, -- Login de l'utilisateur soumettant l'activité
  executed_at timestamp(0) NOT NULL, -- Date et heure d'exécution de l'activité
  CONSTRAINT activite_pkey PRIMARY KEY (id)
);
```

## Exécution des Scripts SQL

### Connexion à PostgreSQL et Exécution du Script de Création

Pour exécuter les scripts SQL, utilisez la commande suivante, en adaptant les paramètres à votre environnement :

```bash
psql -h [adresse_serveur] -U [nom_utilisateur] -w -f [chemin_complet_du_script]
```

- **[adresse_serveur]**: Remplacez par l'adresse de votre serveur de base de données, comme `localhost` ou une adresse IP.
- **[nom_utilisateur]**: Utilisez votre nom d'utilisateur pour la connexion à PostgreSQL.
- **[chemin_complet_du_script]**: Indiquez le chemin complet où se trouve votre script SQL.

Exécutez d'abord le script de création de schéma et de tables :

```bash
psql -h [adresse_serveur] -U [nom_utilisateur] -w -f /chemin/01_migration_postgre.sql
```

### Exécution du Script d'Insertion des Données

Après avoir créé les structures de la base de données, exécutez le script d'insertion des données :

```bash
psql -h [adresse_serveur] -U [nom_utilisateur] -w -f /chemin/02_fixtures_postgre.sql
```

### Exécution du Script d'Attribution des Privilèges

Ensuite, assurez-vous que le rôle a les privilèges nécessaires pour opérer sur la base de données :

```bash
psql -h [adresse_serveur] -U [nom_utilisateur] -w -f /chemin/03_grant_privileges_postgre.sql
```

## Vérification des Données

Pour confirmer que tout a été correctement configuré :

```bash
psql -h [adresse_serveur] -U [nom_utilisateur] -d [nom_base_de_données] -c "SELECT * FROM nom_base_de_données.ma_moulinette;"
```

- **[nom_base_de_données]**: Remplacez par le nom de votre base de données pour vérifier les résultats.

## MAJ des Fichiers de Configuration Environnementale

Après avoir effectué la migration de vos scripts SQL, il est également essentiel de mettre à jour vos fichiers de configuration environnementale pour adapter les changements au nouveau système de gestion de base de données PostgreSQL.

## Modification du .env et .env-prod

Pour les environnements de développement et de production, modifiez vos fichiers `.env` et `.env-prod` comme suit :

Commentez ou supprimez les anciennes configurations SQLite.

### Exemple de lignes à commenter ou supprimer

```bash
#DATABASE_DEFAULT_URL="sqlite:///%kernel.project_dir%/var/data.db"
#DATABASE_SECONDARY_URL="sqlite:///%kernel.project_dir%/var/temp.db"
#SQLITE_PATH="/%kernel.project_dir%/var/"
```

Ajoutez la nouvelle configuration pour PostgreSQL

Dans `.env` (pour le développement) et `.env-prod` (pour la production), ajoutez ou modifiez la configuration de la base de données :

```bash
DATABASE_URL="postgresql://<username>:<password>@<hostname>:<port>/<database>?serverVersion=<server_version>&charset=utf8"
```

## Conclusion

Suivez ces étapes pour configurer et peupler la base de données PostgreSQL après une migration de SQLite. Ce guide vous aidera à préparer, exécuter et vérifier vos scripts SQL pour assurer une transition sans erreur.
