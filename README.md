# Introduction

![Ma-Moulinette](/documentation/ressources/home-000.jpg)

┏┓┏┓┏┓━┏━━┓━┏━━━┓\
┃┃┃┃┃┃━┗┫┣┛━┃┏━┓┃\
┃┃┃┃┃┃━━┃┃━━┃┗━┛┃\
┃┗┛┗┛┃━━┃┃━━┃┏━━┛\
┗┓┏┓┏┛━┏┫┣┓━┃┃\
━┗┛┗┛━━┗━━┛━┗┛  NeXt 2.0.0 Release on september 2024 !

Ma Moulinette est une application, locale (pour le moment...), qui a pour objectif de simplifier la consultation et le suivi des principaux indicateurs collectés et calculés par la plateforme SonarQube.

L'application fonctionne avec la version SonarQube 8.9.9 LTS et supérieure. Elle s'appuie sur les API publiques de l'application.

`Note :` L'application est compatible avec les versions 8, 9 et 10 de SonarQube.

## Histoire

C'est lors d'un échange avec mes étudiants en stage PHP/Symfony, que j'ai présenté SonarQube et l'application de gestion et de suivi des indicateurs de qualité. L'objectif étant de leur montrer la réalisation d'une petite application web en HTML5/JS et les bonnes pratiques de conception et de réalisation.

Par la suite, l'application a été améliorée et utilisée dans le cadre de mes travaux.

Cette première version développée en 10 jours a été réalisée en HTML5, CSS et Javascript. Le stockage en base de données utilisait indexedDB (dexieJs). Cette application se voulait autonome, légère et simple d'utilisation. Elle permettait à un utilisateur de gérer localement son suivi des projets SonarQube.

Cependant, il n'a pas été possible de l'utiliser en condition de production. Pour autant, elle fonctionnait parfaitement dans notre environnement de développement.

Après de nombreux tests et réécritures de codes, la version a été abandonnée pour une application reposant sur le langage PHP et Symfony. Les premiers tests étant concluants, la migration de la version HTML5/JS a pu commencer.

Aujourd'hui, l'application est régulièrement utilisée et a fait l'objet de nombreuses évolutions.

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
* [Gestion des utilisateurs](/documentation/utilisateur.md)

### Ma-Moulinette

* [Accueil](/documentation/accueil.md)
* [Profil](/documentation/profil.md)
* [Profil-details](/documentation/profil-details.md)
* [Projet](/documentation/projet.md)
* [OWASP](/documentation/owasp.md)
* [Suivi](/documentation/suivi.md)
* [COSUI](/documentation/cosui.md)
* [Répartition détaillée](/documentation/repartition_details.md)
* [Préférences](/documentation/preferences.md)

### Backoffice de gestion

* [Dashboard](/documentation/dashboard.md)
* [Utilisateur](/documentation/utilisateur.md)
* [Équipe](/documentation/equipe.md)
* [Portefeuille](/documentation/portefeuille.md)
* [Batch](/documentation/batch.md)

### Pour bien démarrer

[Pour bien démarrer](/documentation/pour_bien_demarrer.md)

### Gestion des erreurs

* [Erreur HTTP](/documentation/http-erreur.md)
* [Erreurs courantes](/documentation/erreurs.md)

### Audit de sécurité

[Audit de sécurité](/documentation/audit.md)

-**-- FIN --**-
