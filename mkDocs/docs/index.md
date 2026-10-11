# 🧩 Ma-Moulinette

[![Version](https://img.shields.io/badge/version-2.1.0-blue)](CHANGELOG.md)
[![PHP](https://img.shields.io/badge/PHP-%E2%89%A5%208.5-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Symfony](https://img.shields.io/badge/Symfony-8.1.6-000000?logo=symfony&logoColor=white)](https://symfony.com/)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-18-4169E1?logo=postgresql&logoColor=white)](https://www.postgresql.org/)
[![Tests](https://img.shields.io/badge/tests-3953%20passed-success?logo=phpunit&logoColor=white)](mkDocs/docs/ma-moulinette/developpement/test-unitaire.md)
[![Assertions](https://img.shields.io/badge/assertions-10457-success)](mkDocs/docs/ma-moulinette/developpement/test-unitaire.md)
[![Coverage](https://img.shields.io/badge/coverage-80.29%25-brightgreen)](mkDocs/docs/ma-moulinette/developpement/test-unitaire.md)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%206-blueviolet?logo=php&logoColor=white)](https://phpstan.org/)
[![Licence](https://img.shields.io/badge/licence-CC%20BY--NC--SA%204.0-lightgrey)](https://creativecommons.org/licenses/by-nc-sa/4.0/)

┏┓┏┓┏┓━┏━━┓━┏━━━┓
┃┃┃┃┃┃━┗┫┣┛━┃┏━┓┃
┃┃┃┃┃┃━━┃┃━━┃┗━┛┃
┃┗┛┗┛┃━━┃┃━━┃┏━━┛
┗┓┏┓┏┛━┏┫┣┓━┃┃
━┗┛┗┛━━┗━━┛━┗┛  NeXt 2.2.0 Release on October 2026 !

**Ma-Moulinette** est une application web open-source dont l’objectif est de **simplifier la consultation et le suivi des indicateurs de qualité logicielle** collectés depuis un serveur **SonarQube**.

Elle s’appuie sur les **API publiques** de SonarQube et est **compatible** avec les versions :

- SonarQube **8.9.9 LTS**
- SonarQube **9.9.8 LTS**
- SonarQube **10 LTA**
- SonarQube **2024 LTA**
- SonarQube **2025 LTA**
- SonarQube **2026**

!!! caution "Versions LTS/LTA attendues"
    Pour les versions **8, 9, 10, 2024 et 2025**, l'application part du principe que le serveur SonarQube interrogé est sur la **dernière version LTS/LTA connue** de sa ligne (tableau ci-dessous) — un point-release intermédiaire peut manquer des métriques récentes.

    **Exception : l'année en cours (2026)**. Aucune LTA finale n'est encore connue pour cette ligne, donc aucune version précise n'est exigée. Certaines métriques apparues en cours d'année (ex. `maintainability_issue_severity`, `reliability_issue_severity`,
    `security_issue_severity`) peuvent être absentes selon le point-release exact de votre serveur — ce n'est pas bloquant : elles sont collectées via un appel séparé et non bloquant, et remontent `null` si le serveur ne les connaît pas encore.

    | `SONAR_VERSION` | Dernière LTS/LTA connue | Statut |
    | --- | --- | --- |
    | `8` | 8.9.9 | Clôturée |
    | `9` | 9.9.8 | Clôturée |
    | `10` | 10.7 | Clôturée |
    | `2024` | 24.12.0 | Clôturée |
    | `2025` | 25.12.0 | Clôturée |
    | `2026` | *(en cours — pas encore de LTA finale)* | Année en cours, voir ci-dessus |

    ⚠️ À mettre à jour lors du passage à l'année 2027 : ajouter une ligne `2027` et fixer la LTA finale de la ligne 2026 dans ce tableau.

## 💡 Philosophie du projet

Conçue à la fois comme **outil de pilotage qualité** et **cadre pédagogique**, Ma-Moulinette est utilisée :

- comme **plateforme de démonstration et d’expérimentation** dans l’enseignement supérieur (BTS, BUT, Licence, etc.) ;
- comme **projet support** pour la formation aux bonnes pratiques de développement, d’intégration continue et de qualité logicielle.

Ce double objectif a guidé son développement : offrir une **application robuste, claire et documentée**, tout en restant **accessible aux étudiants et formateurs**.

## 📜 Licence

Ma-Moulinette est distribuée sous licence
**[Creative Commons CC BY-NC-SA 4.0 International](https://creativecommons.org/licenses/by-nc-sa/4.0/)**.

> Vous êtes libres de partager et d’adapter le projet à condition de :
>
> - **Citer l’auteur** (BY),
> - **Ne pas en faire un usage commercial** (NC),
> - **Partager sous la même licence** (SA).

## 📖 Histoire du projet

Le projet est né lors d’un échange avec mes étudiants en **stage PHP/Symfony**, autour de l’analyse de code et de la qualité logicielle avec **SonarQube**.
L’idée initiale : concevoir une **application web simple** en **HTML5/JS**, illustrant les bonnes pratiques de développement et d’architecture.

La première version, développée en une dizaine de jours, reposait sur :

- **HTML5 / CSS / JavaScript**,
- **IndexedDB (Dexie.js)** pour le stockage local.

Cette version légère et autonome permettait un **suivi local des projets SonarQube**, mais n’était pas adaptée à la production multi-utilisateurs.

Après plusieurs réécritures, le projet a été migré vers **PHP / Symfony**, permettant une architecture solide, une base de données centralisée et des traitements automatisés.

## 🚀 État actuel

Aujourd’hui, **Ma-Moulinette** est une application stable et évolutive, utilisée dans différents contextes (formation, évaluation continue, démonstration).

La **version 2.1.0** est publiée et apporte de nombreuses améliorations.

### ✅ Fonctionnalités disponibles

- [x] Framework **Symfony 8.0** sur **PHP 8.5 NTS** ;
- [x] Base de données **PostgreSQL 18** centralisée (SQLite décommissionné) ;
- [x] Gestion des **utilisateurs**, **groupes utilisateur**, **groupes fonctionnels** et **portefeuilles** de projets SonarQube (voir [Groupes](ma-moulinette/back-office/groupes.md)) ;
- [x] Authentification **locale + LDAP** (OpenLDAP, Microsoft AD) avec fallback et provisioning automatique (voir [Authentification](ma-moulinette/authentification/authentification.md)) ;
- [x] **Hiérarchie de rôles** étendue : `COLLECTE`, `SUIVI`, `BATCH`, `ACTUATOR`, `SECURITY`, `SECURITY_ANALYTICS`, `GESTIONNAIRE`, `INTERNAL` (+ `ACTIVITY`, utilisé mais hors hiérarchie — voir [Gestion de la sécurité](ma-moulinette/developpement/securite.md)) ;
- [x] **Collecte** manuelle (boucle synchrone + polling JS) ou automatique (commande console planifiée par cron) — voir [Traitement](ma-moulinette/back-office/traitement.md) ;
- [x] Module **OWASP DependencyCheck** : ingestion asynchrone des rapports CI, dashboard cross-projets, mutualisation des CVE (voir [DependencyCheck](ma-moulinette/dependency-check/architecture.md)) ;
- [x] Support **Actuator** : déclaration de points d'accès `/actuator/info`, collecte automatique des clés JSON déclarées à chaque analyse de projet, affichage (pastille + modale) sur la page Projet ; et collecte de la **répartition des LOGGER Java** pour les applications Spring Boot ;
- [x] **Historisation** des résultats et métriques en base pour les versions SonarQube **8, 9, 10, 2024, 2025 et 2026** ;
- [x] Tableaux de bord et **visualisation** des indicateurs (projet, profil, OWASP 2017/2021/2025, Clean Code, COSUI, répartition, activité, statistiques) ;
- [x] Back-office **EasyAdmin 5** avec CRUD dédiés pour groupes, portefeuilles et traitements ;
- [x] **Playwright** pour les tests End-to-End (voir [Tests End-to-End](ma-moulinette/developpement/test-e2e.md.old)) ;
- [x] Déploiement conteneurisé via **docker-compose** ;
- [x] Nouveau système de **documentation** avec **MkDocs** (thème Material).

### 📊 Qualité du code

Relevé du 11/10/2026 — voir [Tests unitaires et d'intégration](ma-moulinette/developpement/test-unitaire.md) pour le détail et la procédure de mise à jour.

| Indicateur | Valeur |
| --- | --- |
| Tests unitaires | **3 347** (9 068 assertions) |
| Tests d'intégration | **606** (1 389 assertions, 2 skips volontaires) |
| **Total** | **3 953** tests / **10 457** assertions |
| Tests End-to-End | **22** suites Playwright (voir [Tests End-to-End](ma-moulinette/developpement/test-e2e.md)) |
| Couverture de code (lignes) | **80,29 %** |
| Analyse statique | **PHPStan niveau 6** (68 erreurs résiduelles) |

### 🧱 Fonctionnalités en cours de finalisation

Liste des évolutions restantes :

- [x] ~~Finalisation de la refonte des **tests unitaires** et de la couverture ;~~
- [x] ~~Alignement et complétion de la documentation sur a version 2.0.0 ;~~
- [x] ~~Polissage de la **gestion des préférences utilisateurs** ;~~
- [ ] Finalisation de la récupération "j'ai oublié mon mot de passe" ;
- [x] ~~Ajout de nouveaux **indicateurs SonarQube 2026** ;~~
- [x] ~~Développement du support multi-projet filtré par groupe fonctionnel ;~~
- [ ] Améliorer la couverture des tests e2e;

-**-- FIN --**-
