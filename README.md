# README

## Introduction

Ma Moulinette est une application, locale, qui a pour objectif de simplifier la consultation et le suivi des principaux indicateurs collectés et calculés par la plateforme sonarqube.

L'application fonctionne avec sonarqube 8.9.3 LTS. Elle s'appuie sur les API publiques de l'application.

## Histoire

L'application a été développée pour pallier la suppression et/ou l'abandon de certaines fonctionalités présentent dans la version 5.4.3 LTS de Sonarqube.

L'objectif étant de recréer ses fonctionnalité dans une application locale et indépendante. En d'autres termes, il fallait que l'application puisse être utilisé simplement depuis son poste de travail sans installer un serveur dédié pour la collecte et la présentation des indicateurs.
Cette première version développée en 10 jours a été réalisée en HTML5, CSS et javascript. Le stockage en base de données s'appuyait sur indexedDB.

Cette solution a été abandonée à cause des problèmes de CORS rencontrés.

La migration de l'application a débuté le 28 janvier 2022.

## Technologie

Ma moulinette s'appuie sur les technologies suivantes :

* PHP 8.1.0, HTML5, CSS 3 & javascript ES2015 ;
* Symfony 5.4.3, Zurb Foundation 6.7.4 ;
* sqlLite 4 ;
* select2, chartjs.

## Architecture

Elle est développée selon les principes "Mobile First" et "API First".

* Client web responsive (Zurb Foundation);
* Serveur d'application local (symfony server) ;
* Accès aux API sur un seveur local ou distant via Token ou Login/Mot de passe ;

## Organisation du projet

* Todo

## Configuration

Le fichier **.env-prod** est un template de configuration. Il est nécessaire de le renommer en **.env** et de le paramétrer en fonction de vos besoins ;

Les propriétés suivantes sont disponibles :

* APP_ENV='prod'
* APP_DEBUG='0'

* SONAR_URL='<https://monsonar.a-moi-tout-seul.it>'
* SONAR_TOKEN='mon_token'
* SONAR_USER='mon_login'
* SONAR_PASSWORD='mon_password'
* SONAR_PROFILES='mon profil sonar'
* SONAR_ORGANIZATION='ma petite Entreprise'

* NOMBRE_FAVORI=10

`APP_ENV` : défini le type d'environnement **dev** ou **prod** ;
`APP_DEBUG` : active ou désactive le debug ;
`SONAR_URL` : Correspond l'URL du serveur sonarqube ;
`SONAR_TOKEN` : Correspond au token d'accès généré sur la plateforme sonarqube. Il suffit de faire un copier/coller sans ajouter de guillemets ;
`SONAR_USER` : Correspond au login de l'utilisateur ;
`SONAR_PASSWORD` : Correspond au mot de passe de l'utilisateur ;
`SONAR_PROFILES` : Permet de définir le nom du profil correspondant au nom données pour un jeu de régles ;
`SONAR_ORGANIZATION` : Permet de personaliser le nom de l'établissement utilisé dans les rapports ;
`NOMBRE_FAVORI` : Défini le nombre de version affiché en page d'accueil correpondant aux applications marquées comme favorites.

## Installation des dépendances

En mode développment, il est nécessaire d'installer les dépenances PHP et NPM.

* `composer install`
* `npm install`

En mode production, seul le dossier **vendor** est utilisé, les dépendances npm ne sont pas nécessaires.

## Création de la base de données

La base de données est disponible dans le dossier : **ma-moulinette\var\data.db**
Elle contient l'ensemble des tables définies depuis les class du dossier **entity**.

Les tables créé sont les suivantes :

* Anomalie
* AnomalieDetails
* Favori
* Historique
* HotspotsDetails
* HotspotOwasp
* Hotspots
* InformationProjet
* ListeProjet
* Mesures
* NoSonar
* Notes
* Owasp
* Profiles
* TempAnomalies

La génération des entity, i.e. la création des **getter** et des **setter* est réalisée avec la commande : `php bin/console make:entity --regenerate`

**Attention** : La table `Historique` est particulière.
En effet, cette table contient une clé primaire composée des attribut `maven_key`, `version` et `date_version`. Lors de la génération automatique, si l'anotation ` #[ORM\Id]` est présente, seule le getter est créé. Une erreur se produit alors quand on veut enregistrer les données dans la table (i.e. il manque les setter).
L'astuce est de générer ces trois attribut sans le marqueur de clé primaire puis de les ajouter pour avoir les setter et les getter sur ces trois attibuts.

Enfin, l'enregistrement ne fonctionne pas si l'attribut **date_version** est de type `Datetime`, il a été modifié en **varChar()** mais est typé manuellement en Datetime dans la base. En d'autres termes, si l'on souhaite recréer la base de données depuis les class d'Entity, il ne faudra pas oublier de re-typer l'attibut **date_versin** de la relation **Hitorique** en Datetime.

Pour créer le fichier de création automatique des relations, il suffit de lancer la commande : `php bin/console make:migration`

Pour créer la base de données,il suffit de lancer la commande `php bin/console doctrine:migrations:migrate`

Il ne faudra pas oublier de changer le type de l'attribut `date_version` de la relation **Historique**.

## Démarrage en développemnt

* Modifiez les parametres APP_ENV et APP_DEBUG :

```yaml
APP_ENV=dev
APP_DEBUG=1
```

Par défaut les programes de démarrage et d'arrêt sont dans le dossier bin/ du projet.

* Lancez le programme **symfony_start.bat** pour démarrer le serveur Symfony ;
* Lancez le programme **symfony_stop.bat** pour arrêter le serveur Symfony;
* Lancez le programme **encore.bat** pour démarrer la compilation à la volé des ressouces JS/CSS ;

## Mise en production

* Modifiez les parametres APP_ENV et APP_DEBUG :

```yaml
APP_ENV=prod
APP_DEBUG=0
```

* Supprimer les fichiers du dossier **public/build**
* Supprimer le dossier **dev** et **prod** du dossier **var/cache**
* Supprimer le fichier dev.log du dossier  **var/log**
* Lancez la commande pour compiler le fichier **.env** : `composer dump-env prod`
* Lancez la commande pour compiler les fichiers css/js : `npm run build`

## Accès à l'application

Il est possible de configuer un proxy pour lancer l'application sur un domaine local (i.e sonar-dash.wip) ou depuis l'adresse local sur le port 8000. L'adresse par défaut est : 
<http://localhost:8000>

L'utilisation du serveur local en https n'est pas recommandé si vous n'avez pas main sur se le serveur apache/nginx servant de proxy à la plateforme sonarqube.

## Symfony security:check

### 28/03/2022

```console
Symfony Security Check Report
=============================
No packages have known vulnerabilities.
```
