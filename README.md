# Introduction

![Ma-Moulinette](/documentation/ressources/home-000.jpg)

Ma Moulinette est une application, locale (pour le moment...), qui a pour objectif de simplifier la consultation et le suivi des principaux indicateurs collectés et calculés par la plateforme sonarqube.

L'application fonctionne avec la version sonarqube 8.9.9 LTS et supérieure. Elle s'appuie sur les API publiques de l'application.

`Note :` L'application est compatible avec le version 8 et 9 de sonarqube.

## Histoire

L'application a été développée pour pallier la suppression et/ou l'abandon de certaines fonctionnalités présentes dans la version 5.4.3 LTS de sonarqube.

L'objectif étant de recréer ses fonctionnalités dans une application locale et indépendante. En d'autres termes, il fallait que l'application puisse être utilisée simplement depuis un poste de travail sans installer un serveur dédié pour la collecte et la présentation des indicateurs.

Cette première version développée en 10 jours a été réalisée en HTML5, CSS et Javascript. Le stockage en base de données s'appuyait sur indexedDB (dexieJs).

Cette solution a été abandonnée à cause des problèmes **CORS** rencontrés lors du déploiement sur l'environnement de production.

Pour contourner le problème des accès **CORS** en environnement sécurisé, il a été décidé d'utiliser un client en PHP (i.e. à la place des solutions javascript utilisées) pour réaliser les appels de web-services.

La réécriture totale de l'application a débuté le **28 janvier 2022**, après plusieurs jours de tests infructueux.

## Architecture

* [Architecture technique](/documentation/architecture-technique.md)
* [Architecture des applications JAVA](/documentation/architecture-java.md)
* [Configuration et organisation](/documentation/architecture-organisation.md)
* [Base de données](/documentation/architecture-base-de-donnees.md)

## Ma-Moulinette en images

### Authentification

* [Authentification](/documentation/authentification.md)
* [Inscription](/documentation/inscription.md)
* [Bienvenue](/documentation/bienvenue.md)
* [Gestion des utilisateurs](utilisateur.md)

### Ma-Moulinette

* [Accueil](/documentation/accueil.md)
* [Projet](/documentation/projet.md)
* [OWASP](/documentation/owasp.md)
* [Suivi](/documentation/suivi.md)
* [COSUI](/documentation/cosui.md)

### Backoffice de gestion

* [Dashboard](/documentation/indicateurs.md)
* [Utilisateur](/documentation/utilisateur.md)
* [Equipe](/documentation/equipe.md)
* [Portefeuille](/documentation/portefeuille.md)
* [Batch](/documentation/batch.md)

### Pour bien démarrer

[Pour bien démarrer](/documentation/pour_bien_démarre.md)

### Gestion des erreurs

* [Erreur HTTP](/documentation/http-erreur.md)
* [Erreurs courrantes](/documentation/erreur.md)

### Audit de sécurité

[Audit de sécurité](/documentation/audit.md)

-**-- FIN --**-
