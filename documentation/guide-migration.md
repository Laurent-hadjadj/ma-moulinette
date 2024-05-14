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

1. `00_initalisation.sql` pour la création de la base de données et du role ;
2. `01_structure.sql` pour la création des structures (tables, indexes, etc..) ;
3. `02_fixtures.sql` pour l'intégration des données de l'application ;
4. `03_grant_privileges.sql` pour appliquer les autorisations sur les objets de la base de données ;

> Important !

- [x] Le nom de la base et du schema est fixé par défaut à `ma_moulinette`.
- [ ] Le nom du role (i.e. de l'utilisateur) est par défaut `db_user`. **Vous pouvez le changer**.
- [ ] Le mot de passe est par défaut est `db_password`. **Vous devez le changer**.

## Script de Création de la base, du Schéma et des Tables

La création de la base de données PostgreSQL est repose sur l'éxection de plusieurs scripts SQL. Ce choix est volontaire et facilite l'intégration de l'application dans un environnement de production.

### Connexion à PostgreSQL et exécution des Scripts

Pour exécuter les scripts SQL, utilisez la commande suivante, en adaptant les paramètres à votre environnement :

```bash
psql -h [adresse_serveur] -U [nom_utilisateur] -w -d [nom_database] -f [chemin_complet_du_script]
```

- **[adresse_serveur]**: Remplacez par l'adresse de votre serveur de base de données, comme `localhost` ou une adresse IP.
- **[nom_utilisateur]**: Utilisez votre nom d'utilisateur pour la connexion à PostgreSQL.
- **[nom_database]** : Le nom de la base de données.
- **[chemin_complet_du_script]**: Indiquez le chemin complet où se trouve votre script SQL.

### Création de la base de données

```bash
psql -h [adresse_serveur] -U [nom_utilisateur] -w -f /chemin/00_initialisation.sql
```

**Note** : L'utilisteur doit avoir les droits suffisants pour créer la base de données.

> psql -h localhost -U postgres -w -f /opt/ma-moulinette/migration/PostgreSQL/00_initialisation.sql

### Création du schéma, des tables et indexes

```bash
psql -h [adresse_serveur] -U [nom_utilisateur] -w -f /chemin/01_structure.sql
```

**Note** : La connexion se fera avec l'utilisateur propriétaie de la bas de dnnées. Ici, `db_user`.

> psql -h localhost -U db_user -w -f /opt/ma-moulinette/migration/PostgreSQL/01_structure.sql

## Insertion des Données

Après avoir créé les structures de la base de données, exécutez le script d'insertion des données :

```bash
psql -h [adresse_serveur] -U [nom_utilisateur] -w -f /chemin/02_fixtures.sql
```

**Note** : La connexion se fera avec l'utilisateur propriétaie de la bas de dnnées. Ici, `db_user`.

> psql -h localhost -U db_user -w -f /opt/ma-moulinette/migration/PostgreSQL/02_fixtures.sql

### Attribution des Privilèges

Ensuite, assurez-vous que le rôle a les privilèges nécessaires pour opérer sur la base de données :

```bash
psql -h [adresse_serveur] -U [nom_utilisateur] -w -f /chemin/03_grant_privileges.sql
```

**Note** : La connexion se fera avec l'utilisateur administrateur de la bas de données. Ici, `postgres`.

> psql -h localhost -U db_user -w -f /opt/ma-moulinette/migration/PostgreSQL/03_grant_privileges.sql

## Vérification des Données

Pour confirmer que tout a été correctement configuré :

```bash
psql -h [adresse_serveur] -U [nom_utilisateur] -d [nom_base_de_données] -c "SELECT * FROM nom_base_de_données.ma_moulinette;"
```

- **[nom_base_de_données]**: Remplacez par le nom de votre base de données pour vérifier les résultats.

## Mises à jour des Fichiers de Configuration Environnementale

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

> DATABASE_URL="postgresql://**db_user**:**db_password@localhost**:**5432**/**ma_moulinette**?serveurVersion=**15**&charset=**utf8**"

## Conclusion

Suivez ces étapes pour configurer et peupler la base de données PostgreSQL après une migration de SQLite. Ce guide vous aidera à préparer, exécuter et vérifier vos scripts SQL pour assurer une transition sans erreur.
