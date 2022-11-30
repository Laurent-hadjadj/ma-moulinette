# Base de données

![Ma-Moulinette](/documentation/ressources/home-000.jpg)

## Création des bases de données

Les deux bases de données sont disponibles dans le dossier `ma-moulinette\var\` :

- [x] **ma-moulinette\var\data.db**
- [x] **ma-moulinette\var\temp.db**

Elles contiennent l'ensemble des tables définies depuis les class du dossier **Entity/Main** et **Entity/Secondary**.

Les tables pour la base **data** sont les suivantes :

- Anomalie
- AnomalieDetails
- Favori
- Historique
- HotspotsDetails
- HotspotOwasp
- Hotspots
- InformationProjet
- ListeProjet
- Mesures
- NoSonar
- Notes
- Owasp
- Profiles
- Utilisateur
- MaMoulinette

Les tables pour la base **temp** sont les suivantes :

- Repartition

### Génération des entities

La génération des entity, i.e. la création des **getter** et des **setter** est réalisée avec la commande :

`php bin/console make:entity --regenerate --overwrite App\Entity\Main\`
`php bin/console make:entity --regenerate --overwrite App\Entity\Secondary\`

Si tout va bien, vous devriez avoir une message comme celui-la :

```plaintext
 no change: src/Entity/Main/Anomalie.php
 no change: src/Entity/Main/AnomalieDetails.php
 no change: src/Entity/Main/Favori.php
 no change: src/Entity/Main/Historique.php
 no change: src/Entity/Main/HotspotDetails.php
 no change: src/Entity/Main/HotspotOwasp.php
 no change: src/Entity/Main/Hotspots.php
 no change: src/Entity/Main/InformationProjet.php
 no change: src/Entity/Main/ListeProjet.php
 no change: src/Entity/Main/MaMoulinette.php
 no change: src/Entity/Main/Mesures.php
 no change: src/Entity/Main/NoSonar.php
 no change: src/Entity/Main/Notes.php
 no change: src/Entity/Main/Owasp.php
 no change: src/Entity/Main/Profiles.php
 updated: src/Entity/Main/Utilisateur.php

  Success!
```

Pour créer le fichier de création automatique des relations, il suffit de lancer la commande :

```plaintext
php bin/console doctrine:migrations:diff --em default --namespace MigrationsDefault --no-interaction
```

```plaintext
php bin/console doctrine:migrations:diff --em secondary --namespace MigrationsSecondary --no-interaction
```

La commande permet de créer un fichier de version faisant état de l'cart entre l'entity et la base de données.

### Génération des fichiers de migrations

La commande utilisé permt de générer le script de montée de version. Le fichier est présent dans le dossier **migrations** situé à la racidne du projet. Pour chaque base de données, un fichier sera créé soit dans le sous dossier **default** ou dans le dossier **secondary**.

Pour la base de données **data** :

```plaintext
php bin/console doctrine:migrations:diff --em default --namespace MigrationsDefault --no-interaction
```

Pour la base de données **temp** :

```plaintext
php bin/console doctrine:migrations:diff --em secondary --namespace MigrationsSecondary --no-interaction
```

### Mise à jour de la base de données

A partir des fichiers générés lors de la procédure de migration, il est possible de mettre à jour les deux base de données en utilisant la commande **doctrine:migrations:migrate**.

Pour la base de données **data** :

```plaintext
php bin/console doctrine:migrations:migrate --em default --no-interaction
```

Pour la base de données **temp** :

```plaintext
php bin/console doctrine:migrations:migrate --em secondary --no-interaction
```

## Migration 1.0.0 vers 1.1.0

`Note :` Le fichier SQL `data-1.1.0.sql`, de mise à jour est disponible dans le dossier **/migrations/**.

- [x] La table **anomalie_details** doit être supprimée :

```sql
DROP TABLE anomalie_details;
```

[X] La table **temp_anomalie** doit être supprimée :

```sql
DROP TABLE temp_anomalie;
```

[X] La table **anomalie_details** doit être ajoutée avec la commande :

```sql
CREATE TABLE anomalie_details (
  id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  maven_key VARCHAR (128) NOT NULL,
  name VARCHAR (128) NOT NULL,
  bug_blocker INTEGER NOT NULL,
  bug_critical INTEGER NOT NULL,
  bug_info INTEGER NOT NULL,
  bug_major INTEGER NOT NULL,
  bug_minor INTEGER NOT NULL,
  vulnerability_blocker INTEGER NOT NULL,
  vulnerability_critical INTEGER NOT NULL,
  vulnerability_info INTEGER NOT NULL,
  vulnerability_major INTEGER NOT NULL,
  vulnerability_minor INTEGER NOT NULL,
  code_smell_blocker INTEGER NOT NULL,
  code_smell_critical INTEGER NOT NULL,
  code_smell_info INTEGER NOT NULL,
  code_smell_major INTEGER NOT NULL,
  code_smell_minor INTEGER NOT NULL,
  date_enregistrement DATETIME NOT NULL);
```

[X] La table **historique** doit être modifiée :

```sql
ALTER TABLE historique ADD COLUMN bug_blocker INTEGER ;
ALTER TABLE historique ADD COLUMN bug_critical INTEGER ;
ALTER TABLE historique ADD COLUMN bug_major INTEGER ;
ALTER TABLE historique ADD COLUMN bug_minor INTEGER ;
ALTER TABLE historique ADD COLUMN bug_info INTEGER ;
ALTER TABLE historique ADD COLUMN vulnerability_blocker INTEGER ;
ALTER TABLE historique ADD COLUMN vulnerability_critical INTEGER ;
ALTER TABLE historique ADD COLUMN vulnerability_major INTEGER ;
ALTER TABLE historique ADD COLUMN vulnerability_minor INTEGER ;
ALTER TABLE historique ADD COLUMN vulnerability_info INTEGER ;
ALTER TABLE historique ADD COLUMN code_smell_blocker INTEGER ;
ALTER TABLE historique ADD COLUMN code_smell_critical INTEGER ;
ALTER TABLE historique ADD COLUMN code_smell_major INTEGER ;
ALTER TABLE historique ADD COLUMN code_smell_minor INTEGER ;
ALTER TABLE historique ADD COLUMN code_smell_info INTEGER ;
```

## Migration 1.2.3 vers 1.2.4

`Note :` Le fichier SQL `data-1.2.4.sql` de mise à jour, est disponible dans le dossier **/migrations/**.

La version **1.2.4** introduit plusieurs changements :

- Remplacement de l'attribut **batch** par **autre** sur les tables **Anomalie**, **Historique** et **HotspotDetails** ;
- Ajout de l'attribut **niveau** pour la gestion du tri sur la table HotspotDetails ;

Les instructions suivantes permettent de remplacer, pour chaque table, l'attribut **batch** par **autre**.

### Pour la table Anomalie

```sql
ALTER TABLE anomalie RENAME batch TO autre;
```

### Pour la table Historique

```sql
ALTER TABLE historique RENAME batch TO autre;
```

### Pour la table Hotspotdetails

```sql
ALTER TABLE hotspot_details RENAME batch TO autre;
ALTER TABLE hotspot_details ADD COLUMN niveau INTERGER;
```

### Mise à jour de la colonne niveau

```sql
UPDATE hotspot_details SET niveau=1 WHERE severity='HIGH';
UPDATE hotspot_details SET niveau=2 WHERE severity='MEDIUM';
UPDATE hotspot_details SET niveau=3 WHERE severity='LOW';
```

## Migration 1.2.4 vers 1.3.0

`Note :` Le fichier SQL `data-1.3.0.sql` de mise à jour, est disponible dans le dossier **/migrations/**.

La version **1.3.0** introduit un changement majeur :

- Ajout d'une table **repartition** pour réaliser les calculs intermédiaires sur les indicateurs de sévérité par module.

```sql
CREATE TABLE repartition (
  id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  maven_key VARCHAR(128) NOT NULL, name VARCHAR(128) NOT NULL,
  component CLOB NOT NULL, type VARCHAR(16) NOT NULL,
  severity VARCHAR(8) NOT NULL, setup UNSIGNED BIG INT NOT NULL,
  date_enregistrement DATETIME NOT NULL);
```

## Migration 1.3.0 vers 1.4.0

`Note :` Le fichier SQL `data-1.4.0.sql` de mise à jour est disponible dans le dossier **/migrations/**.

La version **1.4.0** introduit un changement mineur :

```sql
ALTER TABLE anomalie ADD COLUMN liste BOOLEAN DEFAULT 1 NOT NULL;
```

## Migration 1.4.0 vers 1.5.0

`Note :` Le fichier SQL `data-1.5.0.sql` de mise à jour, est disponible dans le dossier **/migrations/**.

La version **1.5.0** introduit plusieurs changement à la base **data.db**:

- L'ajout d'une table des versions de ma-moulinette [ma_moulinette];
- L'ajout d'une table des utilisateurs : [utilisateur] ;

- [x] Création de la table ma_moulinette

```sql
CREATE TABLE ma_moulinette
(id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  version VARCHAR(8) NOT NULL,
  date_version DATETIME NOT NULL,
  is_default BOOLEAN DEFAULT FALSE NOT NULL,
  date_enregistrement DATETIME NOT NULL);
```

- [x] Mise à jour de la table des versions ;

```sql
INSERT INTO ma_moulinette (version, date_version, date_enregistrement)VALUES ('1.0.0', '2022-01-04', date('now'));
INSERT INTO ma_moulinette (version, date_version, date_enregistrement) VALUES ('1.1.0', '2022-04-24', date('now'));
INSERT INTO ma_moulinette (version, date_version, date_enregistrement) VALUES ('1.2.0', '2022-05-05', date('now'));
INSERT INTO ma_moulinette (version, date_version, date_enregistrement) VALUES ('1.2.6', '2022-06-02', date('now'));
INSERT INTO ma_moulinette (version, date_version, date_enregistrement) VALUES ('1.3.0', '2022-07-03', date('now'));
INSERT INTO ma_moulinette (version, date_version, date_enregistrement) VALUES ('1.4.0', '2022-07-06', date('now'));
INSERT INTO ma_moulinette (version, date_version, date_enregistrement) VALUES ('1.5.0', '2022-09-18', date('now'));
```

- [x] Ajout de la table des utilisateurs  ;

```sql
CREATE TABLE utilisateur
(id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  avatar VARCHAR(128) DEFAULT NULL),
  prenom VARCHAR(32) NOT NULL,
  nom VARCHAR(64) NOT NULL,
  courriel VARCHAR(180) NOT NULL,
  password VARCHAR(255) NOT NULL,
  actif BOOLEAN DEFAULT 0 NOT NULL,
  date_modification DATETIME DEFAULT NULL,
  date_enregistrement DATETIME NOT NULL,
  roles CLOB NOT NULL --(DC2Type:json)
);
```

- [x] Création d'un indexe ;

```sql
CREATE UNIQUE INDEX UNIQ_1D1C63B344FB41C9 ON utilisateur (courriel);
```

- [x] Ajout du compte admin

```sql
-- ## Ajoute le compte admin

INSERT INTO utilisateur
(courriel, roles,  password, nom, prenom, date_enregistrement, actif, avatar )
VALUES
('admin@ma-moulinette.fr', '["ROLE_GESTIONNAIRE"]',
'$2y$10$g1KdFM/ARBc7DG0UClLOl./4Cv.urhltS8zWPtOVzq78qkcSjliGa',
'admin','@ma-moulinette','1980-01-01 00:00:00', 1, 'chiffre/01.png');
```

- [x] Ajout de la table des properties

```sql
CREATE TABLE properties (
  id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  type VARCHAR(255) NOT NULL,
  projet_bd INTEGER NOT NULL,
  projet_sonar INTEGER NOT NULL,
  profil_bd INTEGER NOT NULL,
  profil_sonar INTEGER NOT NULL,
  date_creation DATETIME NOT NULL,
  date_modification_projet DATETIME DEFAULT NULL,
  date_modification_profil DATETIME DEFAULT NULL);
  ```

## Migration 1.5.0 vers 1.6.0

`Note :` Le fichier SQL `data-1.6.0.sql` de mise à jour, est disponible dans le dossier **/migrations/**.

- [x] Mise à jour de la table **ma_mouilinette** ;

```sql
BEGIN TRANSACTION;

-- ## Ajout de la version 1.6.0 dans la table ma_moulinette
INSERT INTO ma_moulinette (version, date_version, date_enregistrement) VALUES ('1.6.0', '2022-11-29', date('now'));

COMMIT;
```

- [x] Ajout de la tables **tags** et de la visibilité ;

```sql
BEGIN TRANSACTION;

-- ## Ajout de la table tags
CREATE TABLE IF NOT EXISTS tags (
 "id" INTEGER NOT NULL,
 "maven_key" VARCHAR(128) NOT NULL,
 "name" VARCHAR(64) NOT NULL,
 "tags" CLOB NOT NULL,
 "visibility" VARCHAR(8) NOT NULL,
 "date_enregistrement" DATETIME NOT NULL,
 PRIMARY KEY("id" AUTOINCREMENT)
);

COMMIT;
```

- [x] Mise à jour de la table **equipe** ;

```sql
BEGIN TRANSACTION;

-- ## Ajout de la table equipe
CREATE TABLE IF NOT EXISTS equipe (
 "id" INTEGER NOT NULL,
 "nom" VARCHAR(32) NOT NULL,
 "description" VARCHAR(128) NOT NULL,
 "date_modification" DATETIME DEFAULT NULL,
 "date_enregistrement" DATETIME NOT NULL,
 PRIMARY KEY("id" AUTOINCREMENT)
);

COMMIT;
```

```sql
BEGIN TRANSACTION;

-- ## Ajout de la table portefeuilles
CREATE TABLE IF NOT EXISTS portefeuille (
 "id" INTEGER NOT NULL,
 "equipe" VARCHAR(32) NOT NULL,
 "liste" CLOB NOT NULL,
 "nom" VARCHAR(32) NOT NULL,
 "date_modification" DATETIME DEFAULT NULL,
 "date_enregistrement" DATETIME NOT NULL,
 PRIMARY KEY("id" AUTOINCREMENT)
);

COMMIT;
```

- [x] Ajout de l'attribut **equipe** à la table **utilisateur** ;

```sql
BEGIN TRANSACTION;

-- ## Ajout de l'attribut equipe dans la table utiliasteur
ALTER TABLE utilisateur ADD COLUMN equipe CLOB DEFAULT NULL;

COMMIT;
```

-**-- FIN --**-

[Retour au menu principal](/README.md)
