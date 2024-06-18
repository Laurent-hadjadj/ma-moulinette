# Introduction

![Ma-Moulinette](assets/images/home/home-000.jpg)

┏┓┏┓┏┓━┏━━┓━┏━━━┓  
┃┃┃┃┃┃━┗┫┣┛━┃┏━┓┃  
┃┃┃┃┃┃━━┃┃━━┃┗━┛┃  
┃┗┛┗┛┃━━┃┃━━┃┏━━┛  
┗┓┏┓┏┛━┏┫┣┓━┃┃  
━┗┛┗┛━━┗━━┛━┗┛  NeXt 2.0.0 Release on september 2024 !

Ma Moulinette est une application, locale (pour le moment...), qui a pour objectif de simplifier la consultation et le suivi des principaux indicateurs collectés et calculés depuis un sereur SonarQube.

L'application fonctionne avec la version SonarQube 8.9.9 LTS et 9.9.4 LTS. Elle s'appuie sur les API publiques de l'application SonarQube.

`Note :` L'application est compatible avec les versions 8, 9 et 10 de SonarQube.

## Histoire

C'est lors d'un échange avec mes étudiants en stage PHP/Symfony, que j'ai présenté SonarQube et l'application de gestion et de suivi des indicateurs de qualité. L'objectif étant de leur montrer la réalisation d'une petite application web en HTML5/JS et les bonnes pratiques de conception et de réalisation.

Par la suite, l'application a été améliorée et utilisée dans le cadre de mes travaux.

Cette première version développée en 10 jours a été réalisée en HTML5, CSS et Javascript. Le stockage en base de données utilisait indexedDB (dexieJs).
Cette application se voulait autonome, légère et simple d'utilisation. Elle permettait à un utilisateur de gérer localement son suivi des projets SonarQube.

Cependant, il n'a pas été possible de l'utiliser en condition de production. Pour autant, elle fonctionnait parfaitement dans notre environnement de développement.

Après de nombreux tests et réécritures de codes, la version a été abandonnée pour une application reposant sur le langage PHP et Symfony. Les premiers tests étant concluants, la migration de la version HTML5/JS a pu commencer.

Aujourd'hui, l'application est régulièrement utilisée et a fait l'objet de nombreuses évolutions.

La version 2.0.0 est en cours de développement. Elle contient les fonctionalités suivantes :

- [x] Une base de données PostgreSQL centralisée ;
- [x] La gestion des utilisateurs ;
- [x] La gestion des équipes ;
- [x] La Gestion des portefeuille de projets ;
- [x] La Gestion des traitements asynchrones de collecte ;
- [x] Un processus de collecte des indicateurs SonarQube ;
- [x] Un processus d'historisation des résultats en base de données ;
- [x] Des tableaux de suivi des indicateurs de qualité ;
- [x] Un nouveau système de gestion de la documentation (mkDocs) ;

-**-- FIN --**-
