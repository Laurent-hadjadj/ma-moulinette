# Pour bien démarrer avec Ma Moulinette

![Ma-Moulinette](/assets/images/home/home-000.jpg)

## Les sources

Ma Moulinette est une application open source soumise à la licence CC 4.0 NC-BY-SA. Elle est disponible sur github et Bitbucket.

Il vous suffira de cloner les repository du projet pour obtenir l'application. Toutefois, les sources ne seront pas suffisantes pour démarrer l'application.

Il vous faudra :

> Pour l'environnement de production

- [x] L'application **symfony-cli** pour démarrer l'application et effectuer les différentes actions symfony et composer ;
- [x] La version **PHP 8.3.0** ;
- [x] Un serveur de base de données **PostgreSQL 15** ou plus ;
- [x] L'application **Nodejs 18.3.1** pour générer les composants javascript ;
- [x] Un serveur **rabbitMQ 3.13** ou plus pour gérer les traitements asynchrones ;
- [x] Un serveur **SonarQube 8 LTS** ou **9 LTS** (la version 10 est supportée) ;
- [x] La version **Python 3** et l'extension **mkDocs** pour la génération de la documentation markdown ;
- [x] L'application **ma-moulinette** ;

> Pour l'environnement de production

- [x] Un serveur **Nginx* avec **PHP-FPM** ;
- [x] La version **PHP 8.3.0** ;
- [x] Un serveur de base de données **PostgreSQL 15** ou plus ;
- [x] Un serveur **rabbitMQ 3.13** ou plus pour gérer les traitements asynchrones ;
- [x] Un serveur **SonarQube 8 LTS** ou **9 LTS** (la version 10 est supportée) ;
- [x] L'application **ma-moulinette** ;

## Environnement de développement

En fonction de votre environnement, vous pouvez avoir un fichier d'environnement ayant un nom différent.

- [x] **.env** est le fichier de propriétés par défaut contenant l'ensemble des propriétés de l'application ;
- [ ] **.env.local** est le fichier de propriétés spécifique à l'application.
- [ ] **.env.prod** est le fichier livré pour une mise en production de l'application.
- [x] **.env.test** est le fichier par défaut pour les tests unitaires de symfony.
- [ ] **.env.test.local** est le fichier spécifiques pour l'application.


- [x] modifiez les paramètres **APP_ENV** et **APP_DEBUG** du fichier `.env` ou des fichiers spécifiques :

Pour l'environnement de développement/recette :

```yaml
APP_ENV = dev
APP_DEBUG = 1
```

> outils de développement pour windows

Les scripts DOS sont disponibles dans le dossier **bin/** du projet.

|---environnement
----|---0_toolz
--------|---node-18.17.1
--------|---php-8.3.0-NTS
--------|---postgresql-15.6-1
--------|---python-3.12.3-embed
--------|---rabbitmq-3.13.1
--------|---sonarqube-9.9.4.87374
--------|---symfony-cli
----|---ma-moulinette
--------|---bin

- [x] **console-cli.bat** pour lancer la console symfony et executer des commandes symfony ou composer
- [x] **encore.bat** pour lancer la compilation à la volée des ressources JS/CSS ;
- [x] **mkdocs_serve.bat** pour lancer le serveur mkDocs ;
- [x] **phpunit.bat** pour lancer les tests unitaires ;
- [x] **rabbitmqadmin.py** pour lancer les commandes rabbitMQ en ligne de commande ;
- [x] **symfony_start.bat** pour lancer le serveur symfony-cli ;
- [x] **symfony_stop.bat** pour arrêter le serveur symfony-cli ;

## Déploiement en production

- [x] modifiez les paramètres **APP_ENV** et **APP_DEBUG** :

Pour l'environnement de production :

```yaml
APP_ENV = prod
APP_DEBUG = 0 ou 1 (si on veut avoir des logs complètes)
```

- [x] changez la clé **APP_SECRET** et **SECRET** ;

- [x] supprimez les fichiers du dossier **public/build** ;
- [x] supprimez le dossier **dev** et **prod** du dossier **var/cache** ;
- [x] supprimez le fichier **dev.log** du dossier **var/log** ;
- [x] lancez la commande pour compiler le fichier **.env** :  `composer dump-env prod`
- [x] lancez la commande pour compiler le code PHP :

```plaintext
symfony composer dump-autoload --no-dev --classmap-authoritative
```

- [x] lancez la commande pour compiler les fichiers css/js :  `npm run-script build`

```plaintext
npm run-script build

> ma-moulinette@1.2.4 build c:\sonar-dash.dev\ma-moulinette
> encore production --progress

Running webpack ...

99% done plugins FriendlyErrorsWebpackPlugin DONE  Compiled successfully in 18856ms                                           20:45:28

131 files written to public\build
webpack compiled successfully
```

-**-- FIN --**-

[Retour au menu principal](/index.html)
