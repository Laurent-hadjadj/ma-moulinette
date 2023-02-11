# Architecture technique

![Ma-Moulinette](/documentation/ressources/home-000.jpg)

## Infrastructure et environnement

Ma moulinette est une application web développée en PHP et JavaScript. Elle peut être déployée localement ou sur un serveur.

L'environnement de production se distingue de l'environnement de développement par l'absence des dépendances NPM utilisés lors du développement.

L'ensemble des ressources statiques à savoir le code CSS et JavaScript est compilé pour le webpack. Enfin, le code PHP/TWIG est précompilé et optimisé pour accélérer le chargement des pages.

### En production

La liste ci-dessous indique la version du système d'exploitation et celle des frameworks utilisés en  production :

* OS : Linux Debian 11 ;
* Serveur d'application : nginx + php-FPM ;
* Langages : PHP 8.1.10 ;
* Base de données : SQLite 4 ;
* Cache : opCache

### En développement

La liste ci-dessous indique la version du système d'exploitation et celle des frameworks utilisés en développement :

* OS : Windows|Linux Debian 11 ;
* Serveur d'application : Symfony-cli 5.4.13 ;
* Langages : PHP 8.1.10, HTML5, CSS 3 & Javascript ES2015 ;
* Base de données ; SQLite 4 ;
* Cache : opCache (mode dégradé) ;
* Design:  Zurb Foundation 6.7.5, Bootstrap 5 ;
* Ressources : webpack Encore ;
* Dépendances : nodejs 12.22.12, composer 2.4.1 ;
* Autres : select2, chartjs ;

## Principe de développement

Les principes de développements retenus pour développer **ma-moulinette** s'appuient sur l'approche **Mobile First** et **API First**.

* Client web responsive (Zurb Foundation) ;
* Serveur d'application local (symfony server) ;
* Deux bases de données (data.db et temp.db) ;
* Accès aux API sur un serveur local ou distant via Token ou Login/Mot de passe ;

![Ma-Moulinette](/documentation/ressources/architecture-technique.jpg)

-**-- FIN --**-

[Retour au menu principal](/README.md)
