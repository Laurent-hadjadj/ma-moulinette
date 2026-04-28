# Introduction

![Ma-Moulinette](assets/images/home/home-000.jpg)

┏┓┏┓┏┓━┏━━┓━┏━━━┓  
┃┃┃┃┃┃━┗┫┣┛━┃┏━┓┃  
┃┃┃┃┃┃━━┃┃━━┃┗━┛┃  
┃┗┛┗┛┃━━┃┃━━┃┏━━┛  
┗┓┏┓┏┛━┏┫┣┓━┃┃  
━┗┛┗┛━━┗━━┛━┗┛  NeXt 2.0.0 — Work in Progress (cible 2026) !

**Ma-Moulinette** est une application web open-source dont l'objectif est de **simplifier la consultation et le suivi des indicateurs de qualité logicielle** collectés depuis un serveur **SonarQube**.

Elle s'appuie sur les **API publiques** de SonarQube et est **compatible** avec les versions :

- SonarQube **8.9.9 LTS**
- SonarQube **9.9.4 LTS**
- SonarQube **10 LTA**
- SonarQube **2024**
- SonarQube **2025 LTA**
- SonarQube **2026**

---

## 💡 Philosophie du projet

Conçue à la fois comme **outil de pilotage qualité** et **cadre pédagogique**, Ma-Moulinette est utilisée :

- comme **plateforme de démonstration et d’expérimentation** dans l’enseignement supérieur (BTS, BUT, Licence, etc.) ;
- comme **projet support** pour la formation aux bonnes pratiques de développement, d’intégration continue et de qualité logicielle.

Ce double objectif a guidé son développement : offrir une **application robuste, claire et documentée**, tout en restant **accessible aux étudiants et formateurs**.

---

## 📜 Licence

Ma-Moulinette est distribuée sous licence
**[Creative Commons CC BY-NC-SA 4.0 International](https://creativecommons.org/licenses/by-nc-sa/4.0/)**.

> Vous êtes libres de partager et d’adapter le projet à condition de :
>
> - **Citer l’auteur** (BY),
> - **Ne pas en faire un usage commercial** (NC),
> - **Partager sous la même licence** (SA).

---

## 📖 Histoire du projet

Le projet est né lors d’un échange avec mes étudiants en **stage PHP/Symfony**, autour de l’analyse de code et de la qualité logicielle avec **SonarQube**.
L’idée initiale : concevoir une **application web simple** en **HTML5/JS**, illustrant les bonnes pratiques de développement et d’architecture.

La première version, développée en une dizaine de jours, reposait sur :

- **HTML5 / CSS / JavaScript**,
- **IndexedDB (Dexie.js)** pour le stockage local.

Cette version légère et autonome permettait un **suivi local des projets SonarQube**, mais n’était pas adaptée à la production multi-utilisateurs.

Après plusieurs réécritures, le projet a été migré vers **PHP / Symfony**, permettant une architecture solide, une base de données centralisée et des traitements automatisés.

---

## 🚀 État actuel

Aujourd’hui, **Ma-Moulinette** est une application stable et évolutive, utilisée dans différents contextes (formation, évaluation continue, démonstration).

La **version 2.0.0** est actuellement en développement et apporte de nombreuses améliorations.

### ✅ Fonctionnalités disponibles

- [x] Framework **Symfony 8.0** sur **PHP 8.5 NTS** ;
- [x] Base de données **PostgreSQL 18** centralisée (SQLite décommissionné) ;
- [x] Gestion des **utilisateurs**, **groupes**, **équipes** et **portefeuilles** de projets SonarQube ;
- [x] Authentification **locale + LDAP** (OpenLDAP, Microsoft AD) avec fallback et provisioning automatique ;
- [x] **Hiérarchie de rôles** étendue : `COLLECTE`, `SUIVI`, `BATCH`, `ACTUATOR`, `GESTIONNAIRE`, `ACTIVITY`, `INTERNAL` ;
- [x] **Collecte asynchrone** des indicateurs manuellement ou via Symfony Messenger (transport Doctrine) ;
- [x] **Scheduler Symfony** pour la collecte automatique nocturne (activité SonarQube) ;
- [x] Support **Actuator** et collecte de la **répartition des LOGGER Java** pour les applications Spring Boot ;
- [x] **Historisation** des résultats et métriques en base pour les versions SonarQube **8, 9, 10, 2024, 2025 et 2026** ;
- [x] Tableaux de bord et **visualisation** des indicateurs (projet, profil, OWASP 2017/2021, COSUI, répartition, activité, statistiques) ;
- [x] Back-office **EasyAdmin 5** avec CRUD dédiés pour groupes, portefeuilles et batchs ;
- [x] **Cypress** pour les tests fonctionnels ;
- [x] Déploiement conteneurisé via **docker-compose** ;
- [x] Nouveau système de **documentation** avec **MkDocs** (thème Material).

---

### 🧱 Fonctionnalités en cours de finalisation

- [x] ~~Finalisation de la refonte des **tests unitaires** et de la couverture ;~~
- [x] ~~Complétion de la documentation **FR / EN** ;~~
- [x] ~~Polissage de la **gestion des préférences utilisateurs** ;~~
- [x] ~~Finalisation de la récupération "j'ai oublié mon mot de passe" ;~~
- [x] ~~Ajout de nouveaux **indicateurs SonarQube 2026**.~~

-**-- FIN --**-
