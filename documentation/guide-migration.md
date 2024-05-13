
# Guide Complet de la Migration de la Base de Données SQLite vers PostgreSQL

Ce guide détaille les étapes nécessaires pour configurer une base de données PostgreSQL en migrant les données depuis SQLite. Il couvre la création du schéma, des tables, et l'insertion des données.

## Prérequis

- PostgreSQL installé sur votre système.
- Droits d'administrateur pour créer des schémas, des rôles et insérer des données.
- Accès au terminal ou à un client SQL capable de se connecter à PostgreSQL.

## Préparation des Scripts SQL

Vous utiliserez deux scripts principaux : `01_migration_postgresql.sql` pour la structure de la base de données et `02_fixtures_postgres.sql` pour les données initiales. Modifiez les noms de schéma et de rôle dans ces scripts selon vos besoins avant de les exécuter.

### Script de Création de Schéma et de Tables

```sql
-- Exemple de contenu pour `01_migration_postgresql.sql`
-- Remplacez 'your_schema', 'your_role' et 'your_secure_password' par les valeurs appropriées

BEGIN;

-- Création du schéma
CREATE SCHEMA your_schema AUTHORIZATION your_role;

-- Création d'un rôle avec mot de passe
CREATE ROLE your_role WITH LOGIN PASSWORD 'your_secure_password';

-- Création des tables
CREATE TABLE your_schema.nom_table (
    id SERIAL PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    date_creation TIMESTAMP DEFAULT NOW()
);

COMMIT;
```

## Exécution des Scripts SQL

### Connexion à PostgreSQL et Exécution du Script de Création

Pour exécuter vos scripts SQL, utilisez la commande suivante, en adaptant les paramètres à votre environnement :

```bash
psql -h [adresse_serveur] -U [nom_utilisateur] -w -f [chemin_complet_du_script]
```

- **[adresse_serveur]**: Remplacez par l'adresse de votre serveur de base de données, comme `localhost` ou une adresse IP.
- **[nom_utilisateur]**: Utilisez votre nom d'utilisateur pour la connexion à PostgreSQL.
- **[chemin_complet_du_script]**: Indiquez le chemin complet où se trouve votre script SQL.

Exécutez d'abord le script de création de schéma et de tables :

```bash
psql -h [adresse_serveur] -U [nom_utilisateur] -w -f c:\chemin\01_migration_postgresql.sql
```

### Exécution du Script d'Insertion des Données

Après avoir créé les structures de la base de données, exécutez le script d'insertion des données :

```bash
psql -h [adresse_serveur] -U [nom_utilisateur] -w -f c:\chemin\02_fixtures_postgres.sql
```

### Exécution du Script d'Attribution des Privilèges

Ensuite, assurez-vous que le rôle a les privilèges nécessaires pour opérer sur la base de données :

```bash
psql -h [adresse_serveur] -U [nom_utilisateur] -w -f c:\\chemin\\03_grant_privileges.sql
```

## Vérification des Données

Pour confirmer que tout a été correctement configuré :

```bash
psql -h [adresse_serveur] -U [nom_utilisateur] -d [nom_base_de_données] -c "SELECT * FROM your_schema.nom_table;"
```

- **[nom_base_de_données]**: Remplacez par le nom de votre base de données pour vérifier les résultats.

## MAJ des Fichiers de Configuration Environnementale

Après avoir effectué la migration de vos scripts SQL, il est également essentiel de mettre à jour vos fichiers de configuration environnementale pour adapter les changements au nouveau système de gestion de base de données PostgreSQL.

## Modification du .env et .env-prod

Pour les environnements de développement et de production, modifiez vos fichiers .env et .env-prod comme suit :

Commentez ou supprimez les anciennes configurations SQLite.

# Exemple de lignes à commenter ou supprimer

```bash
#DATABASE_DEFAULT_URL="sqlite:///%kernel.project_dir%/var/data.db"
#DATABASE_SECONDARY_URL="sqlite:///%kernel.project_dir%/var/temp.db"
#SQLITE_PATH="/%kernel.project_dir%/var/"
```

Ajoutez la nouvelle configuration pour PostgreSQL

ans .env (pour le développement) et .env-prod (pour la production), ajoutez ou modifiez la configuration de la base de données :

```bash
DATABASE_URL="postgresql://<username>:<password>@<hostname>:<port>/<database>?serverVersion=<server_version>&charset=utf8"
```

## Conclusion

Suivez ces étapes pour configurer et peupler votre base de données PostgreSQL après une migration de SQLite. Ce guide vous aidera à préparer, exécuter et vérifier vos scripts SQL pour assurer une transition sans erreur.
