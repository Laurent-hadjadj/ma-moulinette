# README

Ma Moulinette est une application quia pour objectif de simplifier la consultation des indicateurs collectée et calculés sur la plateforme sonarqube.

L'application fonctionne avec sonarqube 8.9.3 LTS. Elle s'apuie sur les API de l'outils

## Technologie

* PHP 8.1.0, HTML5, CSS 3 & javascript ES2015 ;
* Symfony 5.4.3, Zurb Foundation 6.7.4 ;
* sqlLite 4 ;

## Architecture

* Client web responsive ;
* Serveur d'application local via symfony server ;
* Accès aux API sur un seveur local ou distant via Token ou Login/Mot de passe ;

## Oragnisation du projet

* Todo

## Configuration

* Renommer le fichier .env-prod en .env ;

* Renseignez les propriétés suivantes :
  * SONAR_URL=<https://monsonar.a-moi-tout-seul.it>
  * SONAR_TOKEN=mon_token
  * SONAR_USER=mon_login
  * SONAR_PASSWORD=mon_password
  * SONAR_PROFILES="mon profil sonar"
  * SONAR_ORGANIZATION="ma petite Entreprise"

Attention, il ne faut pas mettre l'URL, le token, le user et de password entre guillemet.

## Installation des dépendances

Depuis la racine du projet :

* `composer install`
* `npm install`

## Création de la base de données

Depuis la racine du projet :

* php bin/console make:entity --regenerate
* php bin/console make:migration
* php bin/console doctrine:migrations:migrate

Note : vous pouvez utiliser le shell **console-cli.bat** pour récupérer l'environneemnt de développement autonome (php, Synfony, etc...)

La base de données est située dans : **ma-moulinette\var\data.db**

## Démarrage en développemnt

* Modifiez les parametres APP_ENV et APP_DEBUG :

```yaml
APP_ENV=dev
APP_DEBUG=1
```

Par défaut les fichiers sont dans le dossier bin/ du projet.

* lancez le programme **symfony_start.bat** pour démarrer le serveur Symfony ;
* lancez le programme **symfony_stop.bat** pour arrêter le serveur Symfony;
* lancez le programme **encore.bat** pour démarrer la compilation des ressouces JS/CSS ;

## Mise en production

* Modifiez les parametres APP_ENV et APP_DEBUG :

```yaml
APP_ENV=prod
APP_DEBUG=0
```

* Lancez la commande pour compiler le fichier .env : `composer dump-env prod`
* Lancez la commande pour compiler les fichiers css/js : `npm run build`
