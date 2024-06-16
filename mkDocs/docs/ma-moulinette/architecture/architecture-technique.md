# Architecture technique

![Ma-Moulinette](/assets/images/home/home-000.jpg)

## Infrastructure et environnement

Ma moulinette est une application web développée en PHP 8 symfony 6 et JavaScript. Elle peut être déployée localement ou sur un serveur.

L'environnement de production se distingue de l'environnement de développement par l'absence des dépendances NPM utilisés lors du développement.

L'ensemble des ressources statiques, à savoir le code CSS et JavaScript est compilé et utilise webpack. Enfin, le code PHP/TWIG est précompilé et optimisé pour accélérer le chargement des pages.

Ci-dessous une représentation technique de l'environnement de développement et de production.

![Ma-Moulinette](/assets/images/architecture/architcture-technique-001.png)

Ci-dessous une représentation technique des couches logicielles.

![Ma-Moulinette](/assets/images/architecture/architcture-technique-002.png)


### En production

La liste ci-dessous indique la version du système d'exploitation et celle des frameworks utilisés en production :

* [ ] OS : Linux Debian ;
* [x] Serveur d'application : nginx + php-FPM ;
* [x] Langages : PHP 8.3.0 ;
* [x] Base de données locale : SQLite 4 ;
* [ ] Base de données : PostgreSQL ;
* [ ] Cache : opCache

### En développement

La liste ci-dessous indique la version du système d'exploitation et celle des frameworks utilisés en développement :

* [x] OS : Windows | Linux Debian ;
* [x] Serveur d'application : symfony-cli ^5.5.8 ;
* [x] Langages : symfony ^6.4.8, PHP 8.3.0 NTS, HTML 5, CSS 3 & Javascript ES2015 ;
* [x] Tests unitaires : phpUnit ^9.6.19 ;
* [x] Linter : EsLint, styleLint ;
* [x] Base de données : SQLite 4 ou PostgreSQL 15 ;
* [x] Cache : opCache (mode dégradé) ;
* [x] Design :  Zurb Foundation 6.8.1, Bootstrap 5 ;
* [x] Ressources : webpack Encore ;
* [x] Dépendances : nodejs 18.17.1, composer 2.6.5 ;
* [x] Autres : jquery, select2, chartjs ;

## Principe de développement

Les principes de développement retenus pour développer **ma-moulinette** s'appuient sur l'approche **Mobile First** et **API First**.

* Client web responsive (Zurb Foundation) ;
* Serveur d'application local (symfony-cli server) ;
* Deux bases de données (data.db et temp.db) pour la version SQLite et deux schéma pour PostgreSQL ;
* Un serveur rabbitMQ pour les gérer les files de message ;
* Accès aux API SonarQube sur un serveur local ou distant via Token ou Login/Mot de passe ;

![Ma-Moulinette](/assets/images/architecture/architecture-fonctionelle.jpg)

-**-- FIN --**-

[Retour au menu principal](/index.html)
