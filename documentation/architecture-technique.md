# Architecture technique

![Ma-Moulinette](/documentation/ressources/home-000.jpg)

## Infrastructure et environnement

Ma moulinette est une application web développée en PHP 8 Symfony 6 et JavaScript. Elle peut être déployée localement ou sur un serveur.

L'environnement de production se distingue de l'environnement de développement par l'absence des dépendances NPM utilisés lors du développement.

L'ensemble des ressources statiques à savoir le code CSS et JavaScript est compilé et utilise webpack. Enfin, le code PHP/TWIG est précompilé et optimisé pour accélérer le chargement des pages.

### En production

La liste ci-dessous indique la version du système d'exploitation et celle des frameworks utilisés en  production :

* [ ] OS : Linux Debian ;
* [x] Serveur d'application : nginx + php-FPM ;
* [x] Langages : PHP 8.1.10 ;
* [x] Base de données locale: SQLite 4 ;
* [ ] Base de données : postgresql ;
* [ ] Cache : opCache

### En développement

La liste ci-dessous indique la version du système d'exploitation et celle des frameworks utilisés en développement :

* [x] OS : Windows | Linux Debian ;
* [x] Serveur d'application : Symfony-cli 5.4.13 ;
* [x] Langages : PHP 8.1.10, HTML5, CSS 3 & Javascript ES2015 ;
* [x] Base de données : SQLite 4 ;
* [x] Cache : opCache (mode dégradé) ;
* [x] Design:  Zurb Foundation 6.7.5, Bootstrap 5 ;
* [x] Ressources : webpack Encore ;
* [x] Dépendances : nodejs 12.22.12, composer 2.4.1 ;
* [x] Autres : select2, chartjs ;

## Principe de développement

Les principes de développement retenus pour développer **ma-moulinette** s'appuient sur l'approche **Mobile First** et **API First**.

* Client web responsive (Zurb Foundation) ;
* Serveur d'application local (symfony server) ;
* Deux bases de données (data.db et temp.db) ;
* Accès aux API sur un serveur local ou distant via Token ou Login/Mot de passe ;

![Ma-Moulinette](/documentation/ressources/architecture-technique.jpg)

-**-- FIN --**-

[Retour au menu principal](/README.md)
