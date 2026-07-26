# 🧩 Ma-Moulinette

[![Version](https://img.shields.io/badge/version-2.0.0-blue)](CHANGELOG.md)
[![PHP](https://img.shields.io/badge/PHP-%E2%89%A5%208.4-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Symfony](https://img.shields.io/badge/Symfony-8.0-000000?logo=symfony&logoColor=white)](https://symfony.com/)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-18-4169E1?logo=postgresql&logoColor=white)](https://www.postgresql.org/)
[![Tests](https://img.shields.io/badge/tests-3733%20passed-success?logo=phpunit&logoColor=white)](mkDocs/docs/ma-moulinette/developpement/test-unitaire.md)
[![Assertions](https://img.shields.io/badge/assertions-9980-success)](mkDocs/docs/ma-moulinette/developpement/test-unitaire.md)
[![Coverage](https://img.shields.io/badge/coverage-80.01%25-brightgreen)](mkDocs/docs/ma-moulinette/developpement/test-unitaire.md)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%206-blueviolet?logo=php&logoColor=white)](https://phpstan.org/)
[![Licence](https://img.shields.io/badge/licence-CC%20BY--NC--SA%204.0-lightgrey)](https://creativecommons.org/licenses/by-nc-sa/4.0/)

┏┓┏┓┏┓━┏━━┓━┏━━━┓\
┃┃┃┃┃┃━┗┫┣┛━┃┏━┓┃\
┃┃┃┃┃┃━━┃┃━━┃┗━┛┃\
┃┗┛┗┛┃━━┃┃━━┃┏━━┛\
┗┓┏┓┏┛━┏┫┣┓━┃┃\
━┗┛┗┛━━┗━━┛━┗┛  NeXt 2.0.0 Release on August 2026 !

**Ma-Moulinette** est une application web open-source dont l’objectif est de **simplifier la consultation et le suivi des indicateurs de qualité logicielle** collectés depuis un serveur **SonarQube**.

Elle s’appuie sur les **API publiques** de SonarQube et est **compatible** avec les versions :

- SonarQube **8.9.9 LTS**
- SonarQube **9.9.4 LTS**
- SonarQube **10 LTA**
- SonarQube **2024 LTA**
- SonarQube **2025 LTA**
- SonarQube **2026LTA**

> [!NOTE]
> L'application est compatible pour la version 8 et 9 avec l'extension track-logger-method-1.4.0-RELEASE et avec la version track-logger-method-2.0.0-RELEASE pour SonarQube 10 , 2024, 2025 et 2026.

## 💡 Philosophie du projet

Conçue à la fois comme **outil de pilotage qualité** et **cadre pédagogique**, Ma-Moulinette est utilisée :

- comme **plateforme de démonstration et d’expérimentation** dans l’enseignement supérieur (BTS, BUT, Licence, etc.) ;
- comme **projet support** pour la formation aux bonnes pratiques de développement, d’intégration continue et de qualité logicielle.

Ce double objectif a guidé son développement : offrir une **application robuste, claire et documentée**, tout en restant **accessible aux étudiants et formateurs**.

## 📜 Licence

> [!NOTE]
> Ma-Moulinette est distribuée sous licence
> **[Creative Commons CC BY-NC-SA 4.0 International](https://creativecommons.org/licenses/by-nc-sa/4.0/)**.

_
> [!TIP]
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

Cette version légère et autonome permettait un **suivi local des projets SonarQube**, mais n’était pas adaptée à un déploiement en production en condition de multi-utilisateurs.

Après plusieurs réécritures, le projet a été migré vers **PHP / Symfony**, permettant une architecture solide, une base de données centralisée et des traitements automatisés.

## 🚀 État actuel

> [!NOTE] Information sur le projet (relevé du 22/07/2026)
>
> Nombre de fichiers : 275
> Nombre de lignes : 78221
> Nombre de lignes de codes : 48798
> Nombre de classe: 275
> Nombre de méthode : 2863

### 📊 Qualité du code (relevé du 26/07/2026)

| Indicateur | Valeur |
| --- | --- |
| Tests unitaires (`tests/Unit`) | **3 135** tests / 8 649 assertions |
| Tests d'intégration (`tests/Integration`) | **602** tests / 1 351 assertions (2 skips volontaires) |
| **Total** | **3 737** tests / **10 000** assertions |
| Couverture — lignes | **80,01 %** (20 650 / 25 810) |
| Couverture — méthodes | 84,65 % (2 393 / 2 827) |
| Couverture — classes | 51,50 % (120 / 233) |
| Analyse statique | **PHPStan niveau 6** (150 erreurs résiduelles, campagne en cours) |

> [!TIP]
> Le détail des suites, la procédure de relevé et la stratégie d'analyse statique sont décrits dans
> [Tests unitaires et d'intégration](mkDocs/docs/ma-moulinette/developpement/test-unitaire.md).

Aujourd’hui, **Ma-Moulinette** est une application stable et évolutive, utilisée dans différents contextes (formation, évaluation continue, démonstration).

La **version 2.0.0** est actuellement en développement et apporte de nombreuses améliorations.

### ✅ Fonctionnalités disponibles

- [x] Base de données **PostgreSQL** centralisée ;
- [x] Gestion des **utilisateurs** et **groupes utilisateurs/fonctionnels**  et **préférences individuelles** ;
- [x] Support LDAP pour l’authentification et la gestion des utilisateurs ;
- [x] Refonte de la gestion des ROLES et des permissions ;
- [x] Support de la **collecte Actuator** pour les projets Java ;
- [x] Support de la **collecte SonarQube 10.x** ;
- [x] Gestion des **portefeuilles de projets** sonarQube ;
- [x] **Collecte asynchrone** des indicateurs manuellement ou via un batch PHP ;
- [x] **Historisation** des résultats et métriques en base ;
- [x] Intégration des **indicateurs SonarQube 10.x** ;
- [x] Ingestion des rapport Owasp Dependency-Check ;
- [x] Gestion des **sessions utilisateurs** et **sécurisation** de l’application ;
- [x] Tableaux de bord et **visualisation** des indicateurs, avec **filtrage** et **tri** pour les mesures Legacy et SonarQube 10.x ;
- [x] Tableaux de bord pour les indicateurs OWASP Dependency-Check ;
- [x] Publication de rapports d’analyse au format PDF pour les projets suivis, les portefeuilles et les groupes fonctionnels, les rapports incluent les indicateurs SonarQube et OWASP Dependency-Check ;
- [x] Amélioration des logs (traces plus détaillé, gestion des erreurs, etc.) et consultation des logs via l’interface web ;
- [x] Nouveau système de **documentation** avec **MkDocs**.
- [x] Ajout des tests unitaires et des tests d’intégration pour les composants critiques de l’application (3 733 tests, 9 980 assertions — voir le tableau « Qualité du code » ci-dessus) ;
- [x] Mise sous contrôle de l’**analyse statique** avec PHPStan (niveau 6).

### 🧱 Fonctionnalités en cours de finalisation

- [ ] Développement du support multi-projet filtré par groupe fonctionnel ;

Liste des évolutions restantes :

- [x] ~~Finalisation de la refonte des **tests unitaires** et de la couverture ;~~
- [x] ~~Alignement et complétion de la documentation sur a version 2.0.0 ;~~
- [x] ~~Polissage de la **gestion des préférences utilisateurs** ;~~
- [ ] Finalisation de la récupération "j'ai oublié mon mot de passe" ;
- [x] ~~Ajout de nouveaux **indicateurs SonarQube 2026** ;~~
- [x] ~~Développement du support multi-projet filtré par groupe fonctionnel ;~~
- [ ] Améliorer la couverture des tests e2e;

## 🔐 Compte administrateur

- [x] Son identifiant de connexion est <admin@ma-moulinette.fr>.
- [x] Son mot de passe est : `eYK8k4[T;99N!em^`

> [!WARNING]
> ☠️ **Important**, le mot de passe du compte `admin` étant rendu public, il est **obligatoire de le changer** lors du déploiement de l'application.

## Iconographie Ma-Moulinette

> Informations

- ✅ success
- 📌 primary/notice
- 📄 notice
- 🚫 Interdit
- ☠️ C'est pas bien

> Loggers

- ℹ️ info
- ❌ error
- ⚠️ warning
- 🔴 critical
- 🛠️ debug

-**-- FIN --**-
