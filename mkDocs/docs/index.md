# Introduction

![Ma-Moulinette](assets/images/home/home-000.jpg)

┏┓┏┓┏┓━┏━━┓━┏━━━┓  
┃┃┃┃┃┃━┗┫┣┛━┃┏━┓┃  
┃┃┃┃┃┃━━┃┃━━┃┗━┛┃  
┃┗┛┗┛┃━━┃┃━━┃┏━━┛  
┗┓┏┓┏┛━┏┫┣┓━┃┃  
━┗┛┗┛━━┗━━┛━┗┛  NeXt 2.0.0 Release on November 2025 !

**Ma-Moulinette** est une application web open-source dont l’objectif est de **simplifier la consultation et le suivi des indicateurs de qualité logicielle** collectés depuis un serveur **SonarQube**.

Elle s’appuie sur les **API publiques** de SonarQube et est **compatible** avec les versions :

- SonarQube **8.9.9 LTS**
- SonarQube **9.9.4 LTS**
- SonarQube **10 LTA**

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

- [x] Base de données **PostgreSQL** centralisée ;
- [x] Gestion des **utilisateurs** et **groupes** ;
- [x] Gestion des **portefeuilles de projets** SonarQube ;
- [x] **Collecte asynchrone** des indicateurs manuellement ou via un batch PHP ;
- [x] **Historisation** des résultats et métriques en base ;
- [x] Tableaux de bord et **visualisation** des indicateurs ;
- [x] Nouveau système de **documentation** avec **MkDocs**.

---

### 🧱 Fonctionnalités en cours de finalisation

- [ ] Mise à jour et refonte des **tests unitaires** ;
- [ ] Alignement complet de la **documentation** avec la version 2.0.0 ;
- [ ] Finalisation de la **gestion des préférences utilisateurs** ;
- [ ] Support complet de la **collecte Actuator** ;
- [ ] Gestion avancée du **multi-projet (portefeuille étendu)** ;
- [ ] Mise en place de la **gestion de session sécurisée** ;
- [ ] Ajout de nouveaux **indicateurs SonarQube 10.x**.

-**-- FIN --**-
