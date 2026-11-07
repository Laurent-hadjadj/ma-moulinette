# 🧩 Ma-Moulinette

[![Version](https://img.shields.io/badge/version-2.7.0-blue)](CHANGELOG.md)
[![PHP](https://img.shields.io/badge/PHP-%E2%89%A5%208.5.12-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Symfony](https://img.shields.io/badge/Symfony-8.1.11-000000?logo=symfony&logoColor=white)](https://symfony.com/)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-18-4169E1?logo=postgresql&logoColor=white)](https://www.postgresql.org/)
[![Tests](https://img.shields.io/badge/tests-4230%20passed-success?logo=phpunit&logoColor=white)](mkDocs/docs/ma-moulinette/developpement/test-unitaire.md)
[![Assertions](https://img.shields.io/badge/assertions-11418-success)](mkDocs/docs/ma-moulinette/developpement/test-unitaire.md)
[![Coverage](https://img.shields.io/badge/coverage-81.02%25-brightgreen)](mkDocs/docs/ma-moulinette/developpement/test-unitaire.md)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%207-blueviolet?logo=php&logoColor=white)](https://phpstan.org/)
[![Licence](https://img.shields.io/badge/licence-AGPL%203.0-blue)](LICENSE)

┏┓┏┓┏┓━┏━━┓━┏━━━┓\
┃┃┃┃┃┃━┗┫┣┛━┃┏━┓┃\
┃┃┃┃┃┃━━┃┃━━┃┗━┛┃\
┃┗┛┗┛┃━━┃┃━━┃┏━━┛\
┗┓┏┓┏┛━┏┫┣┓━┃┃\
━┗┛┗┛━━┗━━┛━┗┛  NeXt 2.7.0 Release on November 2026 !

**Ma-Moulinette** est une application web open-source dont l’objectif est de **simplifier la consultation et le suivi des indicateurs de qualité logicielle** collectés depuis un serveur **SonarQube**.

Elle s’appuie sur les **API publiques** de SonarQube et est **compatible** avec les versions :

- SonarQube **8.9.9 LTS**
- SonarQube **9.9.8 LTS**
- SonarQube **10 LTA**
- SonarQube **2024 LTA**
- SonarQube **2025 LTA**
- SonarQube **2026** (2026-10)

> [!NOTE]
> L'application est compatible pour la version 8 et 9 avec l'extension track-logger-method-1.4.0-RELEASE et avec la version track-logger-method-2.0.0-RELEASE pour SonarQube 10 , 2024, 2025 et 2026.

_
> [!CAUTION]
> Pour les versions **8, 9, 10, 2024 et 2025**, l'application part du principe que le serveur SonarQube interrogé est sur la **dernière version LTS/LTA connue** de sa ligne (tableau ci-dessous) — un point-release intermédiaire peut manquer des métriques récentes.
>
> **Exception : l'année en cours (2026)**. Aucune LTA finale n'est encore connue pour cette ligne, donc aucune version précise n'est exigée. Certaines métriques apparues en cours d'année (ex. `maintainability_issue_severity`, `reliability_issue_severity`, `security_issue_severity`, cf. [CHANGELOG](CHANGELOG.md)) peuvent être absentes selon le point-release exact de votre serveur — ce n'est pas bloquant : elles sont collectées via un appel séparé et non bloquant, et remontent `null` si le serveur ne les connaît pas encore.
>
> | `SONAR_VERSION` | Dernière LTS/LTA connue | Statut |
> | --- | --- | --- |
> | `8` | 8.9.9 | Clôturée |
> | `9` | 9.9.8 | Clôturée |
> | `10` | 10.7 | Clôturée |
> | `2024` | 24.12.0 | Clôturée |
> | `2025` | 25.12.0 | Clôturée |
> | `2026` | *(en cours — pas encore de LTA finale)* | Année en cours, voir ci-dessus |
>
> ⚠️ À mettre à jour lors du passage à l'année 2027 : ajouter une ligne `2027` et fixer la LTA finale de la ligne 2026 dans ce tableau.

## 💡 Philosophie du projet

Conçue à la fois comme **outil de pilotage qualité** et **cadre pédagogique**, Ma-Moulinette est utilisée :

- comme **plateforme de démonstration et d’expérimentation** dans l’enseignement supérieur (BTS, BUT, Licence, etc.) ;
- comme **projet support** pour la formation aux bonnes pratiques de développement, d’intégration continue et de qualité logicielle.

Ce double objectif a guidé son développement : offrir une **application robuste, claire et documentée**, tout en restant **accessible aux étudiants et formateurs**.

## 📜 Licence

> [!NOTE]
>
> - **Code** : [GNU AGPL 3.0 ou ultérieure](LICENSE) (`AGPL-3.0-or-later`), licence à copyleft : le logiciel reste ouvert, y compris lorsqu'il est proposé comme service web.
> - **Documentation** (`README.md`, `mkDocs/docs/`) : [CC BY-SA 4.0](mkDocs/LICENSE.md).
>
> **Changement de licence.** Depuis le 4 novembre 2026, le code est distribué sous AGPL 3.0. Jusqu'à la version 2.5.0 incluse (tag `v2.5.0`), il était publié sous [CC BY-NC-SA 4.0](LICENSES/CC-BY-NC-SA-4.0.txt) ; ces versions restent sous cette licence. L'usage commercial est désormais autorisé, à condition de respecter le copyleft. Les en-têtes de fichiers antérieurs mentionnent encore l'ancienne licence : en cas de contradiction, le fichier [LICENSE](LICENSE) prévaut. **Motif et portée du changement : [LICENSING.md](LICENSING.md)**. Attributions : [NOTICE](NOTICE).

## 📖 Histoire du projet

Le projet est né lors d’un échange avec mes étudiants en **stage PHP/Symfony**, autour de l’analyse de code et de la qualité logicielle avec **SonarQube**.
L’idée initiale : concevoir une **application web simple** en **HTML5/JS**, illustrant les bonnes pratiques de développement et d’architecture.

La première version, développée en une dizaine de jours, reposait sur :

- **HTML5 / CSS / JavaScript**,
- **IndexedDB (Dexie.js)** pour le stockage local.

Cette version légère et autonome permettait un **suivi local des projets SonarQube**, mais n’était pas adaptée à un déploiement en production en condition de multi-utilisateurs.

Après plusieurs réécritures, le projet a été migré vers **PHP / Symfony**, permettant une architecture solide, une base de données centralisée et des traitements automatisés.

## 🚀 État actuel

> [!NOTE] Information sur le projet (relevé du 07/11/2026)
>

| Langage   | Fichiers  | Code   | Commentaires | Vides | Total |
| --------- | --------: | -----: | -----------: | ----: | ----: |
|  PHP      |  323      | 56110  | 24886        | 10199 | 91195 |
|  HTML    |  157      | 28600  | 1154         | 2041  | 31795 |
|  JS      |  93       | 11222  | 4668         | 1826  | 17716 |
|  CSS     |  39       | 6640   | 1382         | 1403  | 9425 |
|  SQL     |  84       | 3346   | 1760         | 772   | 5878 |
|  MIGRATION |  20     | 1145   | 337          | 222   | 1704 |

### 📊 Qualité du code (relevé du 07/11/2026)

| Indicateur | Valeur |
| --- | --- |
| Tests unitaires (`tests/Unit`) | **3 527** tests / 9 646 assertions |
| Tests d'intégration (`tests/Integration`) | **703** tests / 1 772 assertions |
| **Total** | **4 230** tests / **11 418** assertions |
| Tests End-to-End (`tests/e2e`) | **22** suites Playwright |
| Couverture — lignes | **81,02 %** |
| Analyse statique | **PHPStan niveau 7** (0 erreur sur tout le périmètre configuré) |

> [!TIP]
> Le détail des suites, la procédure de relevé et la stratégie d'analyse statique sont décrits dans
> [Tests unitaires et d'intégration](mkDocs/docs/ma-moulinette/developpement/test-unitaire.md).

Aujourd’hui, **Ma-Moulinette** est une application stable et évolutive, utilisée dans différents contextes (formation, évaluation continue, démonstration).

La **version 2.7.0** corrige un bug critique introduit en 2.6.0 et ajoute un compteur de projets collectés/à collecter sur la page Projet (voir [CHANGELOG.md](CHANGELOG.md)).

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
- [x] Ajout des tests unitaires et des tests d’intégration pour les composants critiques de l’application (4 230 tests, 11 418 assertions — voir le tableau « Qualité du code » ci-dessus) ;
- [x] Mise sous contrôle de l’**analyse statique** avec PHPStan (niveau 7, 0 erreur sur tout le périmètre).

### 🧱 Fonctionnalités en cours de finalisation

- [ ] Développement du support multi-projet filtré par groupe fonctionnel ;

Liste des évolutions restantes :

- [ ] Finalisation de la récupération "j'ai oublié mon mot de passe" ;
- [ ] Migration des Security Hotspots SonarQube (`/api/hotspots/*`), dépréciés depuis la version SonarQube 2026.4, vers `issues/search` ;

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
