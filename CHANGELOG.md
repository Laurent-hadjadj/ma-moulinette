# Changelog

Toutes les évolutions notables de Ma-Moulinette sont documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/), et ce projet adhère (au mieux, l'historique antérieur à cette convention restant dans son style d'origine) au [Semantic Versioning](https://semver.org/lang/fr/).

## [2.4.0] - 27-10-2026

### Ajouté

- **Page ZAP** : Une clé Maven peut recouvrir plusieurs cibles DAST réelles (ex. tier présentation + tier métier d'une même application, suivis sous la même clé côté SonarQube mais scannés séparément) — la page affiche désormais une « lane » (KPI, graphiques, tableaux et historique entièrement indépendants) par cible (`target_url`) distincte détectée pour le projet, plutôt que de mélanger leurs scans dans un historique unique. L'export PDF accepte un paramètre `target_url` pour cibler la lane à exporter (repli sur la cible la plus récemment scannée si omis, pour les liens déjà existants) ;
- **Page OWASP** : La modale « Détail » d'une catégorie (icône 🔎 du tableau de synthèse) affiche désormais la répartition par sévérité des **vulnérabilités** (Bloquant/Critique/Majeur/Mineur), en plus de celle des hotspots (Élevée/Moyenne/Faible) déjà présente — elle ne montrait jusqu'ici jamais les vulnérabilités, même quand la catégorie en comptait ;
- **Personnalisation du nom affiché** : Le nom de l'application affiché dans l'interface (titre des pages, en-tête, pied de page, mentions légales, rapports PDF…) est désormais piloté par la nouvelle variable d'environnement `MA_MOULINETTE_NAME` (défaut : `Ma-Moulinette`), exposée à tous les templates Twig via une variable globale `app_name` — plus besoin de modifier le code pour déployer l'application sous un autre nom. Ne concerne pas le nom de domaine `ma-moulinette.fr` ni le dépôt GitHub, cités tels quels. Voir « Pour bien démarrer » pour la marche à suivre ;
- **Page Projet** : Nouveau bloc « Accessibilité », basé sur le tag SonarQube `accessibility` (122 règles CSS/Web/JavaScript/TypeScript, pas de facette WCAG officielle côté SonarQube) : nombre d'anomalies et note A-E globale, détaillées directement par langage (4 lignes, sans modale). Suit le pattern RCI (pas celui, défaillant, de l'Empreinte Écologique) : l'historique est alimenté par les deux pipelines, y compris un enregistrement manuel depuis la page. Disponible dans les deux présentations (classique et tuiles).

### Corrigé

- **Collecte** : SonarQube renvoie parfois une clé `<null>` dans `ncloc_language_distribution` quand il ne détermine pas le langage d'une partie du code (ex. `<null>=71;css=79;java=1948;...` — sa propre interface affiche « UNKNOWN » pour ce même cas) — la clé brute `<null>` remontait telle quelle jusqu'à l'affichage. Normalisée en « Inconnu » une seule fois, à la collecte (`BuildMapHistoryService`, point d'entrée partagé par la collecte interactive, le batch/cron et la commande de reconstruction de l'historique), pour ne plus avoir à la retraiter à chaque peinture ;
- **Page ZAP** : Le scan « le plus récent » affiché en tête de page pouvait être choisi de façon non déterministe quand deux scans partageaient exactement le même horodatage (`generated_at`) — cas réel après ingestion d'un rapport ZAP multi-site scindé en plusieurs rapports mono-site (même horodatage hérité du rapport d'origine). Un critère de tri secondaire (`id` décroissant) départage désormais les égalités, en faveur du scan le plus récemment ingéré ;
- **Page ZAP** : Ajustements de la modale de détail d'une alerte (taille agrandie, marge basse manquante sur le pied de modale) et d'un identifiant de bouton de fermeture manquant dans la feuille de style ;
- **Page ZAP** : Depuis l'introduction des lanes, les modales de détail d'une alerte ne s'affichaient plus pour aucune alerte, quelle que soit la page (boucle du template sur une variable qui n'existait plus au niveau racine) — corrigé en itérant sur les alertes de chaque lane ;
- **Page OWASP** : Le bouton d'accès à la page ZAP ne tenait plus compte que les scans sont désormais répartis en plusieurs cibles (lane) pour un même projet — corrigé pour afficher le bouton dès qu'au moins une cible a un scan, la page ZAP se chargeant elle-même de les restituer toutes ;
- **Export PDF ZAP** : L'aperçu tronqué de la description d'une alerte affichait les balises `<p>` du HTML d'origine de façon littérale (texte échappé, pas interprété) — nettoyé avant troncature ;
- **Page Projet** : Le journal de collecte affichait le même message (« J'ai trouvé X failles. ») pour les référentiels OWASP 2017 et 2021, sans distinction — impossible de savoir lequel des deux comptes concernait quel référentiel. Chaque message précise désormais le référentiel ;
- **Page OWASP** : Au clic sur une barre du graphique « Répartition des failles », l'infobulle affichait `[object Object]%` au lieu du nombre réel — `context.parsed` est l'objet `{x, y}` du point sous Chart.js, pas directement la valeur ; corrigé pour lire `context.parsed.y` ;
- **Page Projet** : Lancer une collecte puis cliquer directement sur « Enregistrer » sans passer par « Afficher » faisait planter `enregistrement.js` (`TypeError: Cannot read properties of null (reading 'dataset')`, sans message pour l'utilisateur) — un élément du DOM posé uniquement par `peinture.js` était lu sans garde-fou. Message clair désormais affiché (« Aucune donnée à enregistrer : lance une Peinture avant d'enregistrer. ») ;
- **Page OWASP** : Trois bugs réels dans le calcul des vulnérabilités par catégorie, trouvés en recoupant les résultats réels d'un projet avec des appels API SonarQube directs — secours par tag `owasp-aXX` zéro-paddé ne matchant jamais le vrai format des tags (`owasp-a6`, pas `owasp-a06`), bascule tout-ou-rien par référentiel masquant les catégories classées uniquement par tag dès qu'une autre catégorie du même référentiel était classée par la facette officielle, répartition par sévérité structurellement vide pour toute catégorie classée par la facette officielle — voir la documentation « Page OWASP » pour le détail complet des 3 bugs ;
- **Page OWASP** : Le comptage par sévérité de la modale « Détail » d'une catégorie (hotspots) ne filtrait pas par référentiel — il sommait silencieusement 2017 et 2021, affichant le même résultat quel que soit l'onglet actif. La modale affichait aussi le contenu de la catégorie précédemment cliqué pendant un instant avant de se mettre à jour (ouverture avant la fin du chargement asynchrone) ;
- **Page OWASP** : La modale « Détail » d'une catégorie était trop étroite pour aligner ses 4 colonnes de sévérité des vulnérabilités sur une seule ligne (la 4ᵉ passait à la ligne, incohérent avec la ligne de hotspots juste en dessous) et son titre n'utilisait pas la police/le style du reste de la page — largeur et titre alignés sur la convention du reste de l'application ;
- **Page Projet (Accessibilité)** : Un langage absent du projet (aucune ligne dans `ncloc_language_distribution`, ex. TypeScript sur un projet qui n'en a pas) affichait `0` anomalie et aucune note — indiscernable d'un langage réellement présent et sans aucune anomalie trouvée. `0` reste désormais réservé à ce dernier cas ; un langage absent affiche un tiret grisé (nouvelle classe `.note-gris`) et son appel API `/api/issues/search` n'est plus effectué du tout ;
- **Page Projet (tuiles)** : Sur la tuile « Répartition », la ligne « Maintenabilité » (deux compteurs non compressibles, mauvaises pratiques réelles / total) débordait de la tuile dès qu'un projet comptait beaucoup de violations. Scindée en deux sous-lignes (« Réelles » / « Total », même convention que le détail par langage d'Accessibilité), sans changer la largeur des tuiles ;
- **Page Projet** : `clean_screen()` (effacement de l'écran avant une nouvelle collecte) ne nettoyait pas les champs Accessibilité (compteur et note globaux, détail par langage, attribut `data-accessibility-mesures-json` posé par la peinture) — les anciennes valeurs restaient affichées pendant le chargement d'une nouvelle collecte. Corrigé, sur le même modèle que le bloc Empreinte Écologique voisin ;
- **Migrations** : La table `accessibility` n'avait ses index et commentaires de colonnes que dans le script idempotent (`updates/13_create_accessibility_table.sql`), pas dans les fichiers centralisés utilisés par une installation complète (`40_indexes/indexes.sql`, `60_comments/comments.sql`) — une base créée via `99_master_install.sql` se serait retrouvée sans ces index/commentaires, contrairement à une base mise à jour via le script idempotent. Complété, aligné sur `eco_conception` ;
- **Historique** : L'entité Doctrine `Historique` n'avait jamais été mise à jour avec les 15 colonnes `accessibility_*` — contrairement à RCI et à l'Empreinte Écologique, dont les colonnes équivalentes sont mappées depuis leur introduction. La colonne SQL et la whitelist d'insertion dynamique (`HistoriqueRepository::$historiqueColumns`) étaient correctes, seule l'entité elle-même ignorait ces colonnes (aucun getter/setter, pas de contrainte de validation sur les notes). Complété, aligné sur le bloc Empreinte Écologique voisin.

### Sécurité

- **Page ZAP** : La description, la solution et la référence de chaque règle ZAP (HTML fourni par le catalogue de règles de ZAP) étaient rendues sans aucune neutralisation côté Symfony (`|raw`), malgré un commentaire de sécurité dans le code indiquant l'inverse — un rapport déposé via `POST /api/secure/zap/upload` (protégé par jeton, mais pas par validation de contenu) aurait pu faire exécuter du JavaScript dans le navigateur d'un utilisateur consultant la page. Ces 3 champs sont désormais neutralisés une seule fois, à l'ingestion, via [symfony/html-sanitizer](https://symfony.com/doc/current/html_sanitizer.html) (voir la documentation « Gestion de la sécurité »), avant toute persistance en base.

### Documentation

- **Architecture base de données** : La page décrivant le modèle de données n'avait pas suivi l'ajout des tables ZAP (DAST) (`zap_processing_queue`, `zap_scan`, `zap_rule`, `zap_finding`, `owasp_dast`) ni de `eco_conception` (Empreinte Écologique, Creedengo) — nouveau diagramme entité-relation pour le domaine ZAP, nouvelle section dédiée, et l'ensemble des compteurs (tables, index, commentaires, contraintes, fonctions) recomptés et mis à jour ;
- **Page ZAP** : Nouvelle section décrivant les lanes (plusieurs cibles sous une même clé Maven) et le paramètre `target_url` de l'export PDF ;
- **Sécurité** : Nouvelle section décrivant la neutralisation du HTML externe des règles ZAP ;
- **Page OWASP** : Nouvelles notes décrivant les 3 bugs du calcul par catégorie (secours par tag, fusion catégorie par catégorie, répartition par sévérité) et les bugs de la modale « Détail » (filtre référentiel manquant, contenu pas à jour, absence de sévérité des vulnérabilités) ;
- **Page Projet** : Nouvelle note décrivant le crash d'enregistrement sans Peinture préalable et son correctif ;
- **Pour bien démarrer** / **Architecture technique** : Nouvelle section décrivant la variable `MA_MOULINETTE_NAME` et sa distinction avec `MARQUE_ENTREPRISE_SHORT`/`MARQUE_ENTREPRISE_LONG` (auteur/éditeur, pas le nom du produit) ;
- **Page Projet** : Nouvelle section « Bloc Accessibilité » décrivant le choix du tag `accessibility` (plutôt que `wcag`), l'absence de détail par critère WCAG exploitable via l'API, le pattern d'historique (aligné sur RCI, pas sur l'Empreinte Écologique) et la distinction « 0 mesuré » / « non calculable » ;
- **README** / **Tests unitaires et d'intégration** : Statistiques (lignes de code, nombre de tests/assertions, couverture) régénérées via `app:admin:refresh-stats` et `composer test:coverage` (4 220 tests / 11 380 assertions, 80,95 % de lignes couvertes), version affichée alignée sur 2.4.0 ;
- **Tests** : `Entity\Accessibility` et `Repository\AccessibilityRepository` n'apparaissaient même pas dans le rapport de couverture (0 % réel, comme l'entité `EcoConception`, un gap déjà existant dans le projet) — 48 nouveaux tests ajoutés (`AccessibilityCaseTest` : 18 paires getter/setter, `AccessibilityValidationTest` : contraintes `Assert` NotBlank/Length/PositiveOrZero/Choice exercées avec le vrai validateur Symfony, `AccessibilityRepositoryTest` : `handleDatabaseException` et les branches `catch`/`rollback` des 3 méthodes SQL). `Entity\Accessibility` passe à 98,27 % de lignes couvertes, `AccessibilityRepository` à 100 % ;
- **Tests** : `Entity\Historique` n'avait jamais été mise à jour avec les 15 colonnes `accessibility_*`, contrairement à ses équivalentes `rci_*`/`eco_*` — 25 tests supplémentaires (`HistoriqueCaseTest`, `HistoriqueValidatorTest`) couvrent désormais les getters/setters et les contraintes de validation des 5 notes A-E, et `HistoriqueRepositoryTest` (Integration) prouve que ces colonnes sont réellement persistées par l'INSERT dynamique de `HistoriqueRepository`, pas silencieusement ignorées par sa whitelist statique. Une fixture `AccessibilityFixtures` (nouvelle, calquée sur `RciFixtures`) corrige par ailleurs une incohérence : Accessibilité disait suivre le pattern RCI partout, sauf pour son test Integration, qui construisait ses données sans fixture.

## [2.3.0] - 25-10-2026

### Ajouté

- **Préférences** : Nouvelle option « Presentation » sur `/preferences` (même mécanisme de bascule Oui/Non que les favoris) permettant de choisir, pour la page Projet, entre la présentation classique (3 colonnes) et une nouvelle présentation en tuiles (une tuile par domaine : Application, Volumétrie, Complexité, Qualité interne, Tests & Violations, Analyse, Empreinte, Qualité, Répartition, Dette). Désactivée par défaut (présentation classique) pour ne rien changer aux utilisateurs existants ;
- **Mes Projets** : Ajout d'un encart d'information précisant que la colonne Version correspond à la version analysée lors de la dernière collecte, pas nécessairement à la dernière version publiée du projet ;
- **Page Projet** : Affichage de métriques déjà collectées et persistées par SonarQube, mais jamais montrées jusqu'ici, dans les deux présentations (tuile et classique) — statut du cycle de vie des violations (ouvertes/confirmées/réouvertes) et violations acceptées à fort impact dans le bloc Tests & Violations ; détail de la couverture (lignes/branches/conditions non couvertes) et de la duplication (lignes/blocs/fichiers dupliqués), avec une barre de progression pour chacune des deux mesures globales, dans le bloc Qualité interne ; nombre total de points de complexité cyclomatique et cognitive (nouvelle section « Points de complexité »), en plus des ratios déjà affichés, dans le bloc Complexité.

### Corrigé

- **Mes Projets** : Les en-têtes de colonne du tableau (DataTables) restaient alignés à gauche malgré la classe `text-center` ajoutée dans le template — la feuille de style du thème DataTables/Foundation (`table.dataTable thead th { text-align: left; }`) est plus spécifique que la classe utilitaire Foundation `.text-center` et la surcharge systématiquement, y compris sur la ligne d'en-tête groupée (colspan) que les réglages `columnDefs`/`className` de DataTables ne peuvent pas atteindre. Corrigé en ciblant l'ID de la table (`#table-mes-projets thead th.text-center`), pour les deux lignes d'en-tête ;
- **Page Projet (tuiles)** : Dans les nouveaux blocs de présentation en tuiles, chaque titre de section avait une balise `<span>` d'icône mal fermée (`<span aria-hidden="true">🏀<span>Titre</h2>`), ainsi qu'un attribut `title` mal orthographié (`titre=`) et une coquille visible (« NoS0NAR » au lieu de « NoSONAR ») — ces deux dernières également présentes dans le bloc classique correspondant, corrigées aux deux endroits ;
- **Page Projet (tuiles)** : Les tuiles utilisaient une largeur calée sur leur contenu (`large-auto`), ce qui donnait des tuiles de largeurs très inégales sur une même ligne au sein d'un même bloc — remplacées par des largeurs fixes par breakpoint. L'espacement entre tuiles est désormais géré par `grid-margin-x` (compense proprement la gouttière à tous les breakpoints, y compris en mode empilé sur mobile, contrairement à une marge manuelle sur les cellules) ;
- **Page Projet (tuiles)** : Les icônes de section (emoji) étaient répétées sans distinction entre plusieurs tuiles d'un même bloc (⚾/🏀/👜 réutilisés partout) — remplacées par des icônes distinctes et porteuses de sens ; la tuile « Répartition » dupliquée (deux tuiles portant le même nom pour des données différentes : violations/scope d'un côté, répartition par module applicatif de l'autre) a été renommée en « Modules » ;
- **Page Projet** : La ligne « Dette technique » de la tuile/bloc Dette pouvait chevaucher la ligne suivante (« Ratio de dette ») quand la durée affichée était longue, faute de place sur une ligne partagée avec le label et le bouton de détail — scindée en deux lignes (label + bouton, puis la durée sur sa propre ligne) ;
- **Page Projet** : En largeur mobile, la marge fixe de 2rem de part et d'autre du contenu de la page laissait trop peu de place aux tuiles, provoquant des débordements visuels — réduite (0,2 à 0,5rem selon la largeur) sur ce breakpoint uniquement ;
- **Page CoSUI** : Le graphique de distribution des notes touchait le pied de page, faute de marge sous le dernier bloc de contenu — ajout de la marge standard déjà appliquée ailleurs dans l'application ;
- **Sécurité HTTP** : Les tests `SecurityHeadersSubscriberTest` (Unit et Integration) attendaient encore un en-tête `Content-Security-Policy` en environnement de débogage, alors que le correctif CSP du 21/10 (désactivation volontaire du CSP quand `kernel.debug` est vrai, pour ne plus bloquer les scripts injectés par la Web Debug Toolbar) a changé ce comportement — tests mis à jour pour refléter le nouveau comportement voulu (aucun CSP en dev, CSP complet en prod) ;
- **Page Accueil** : Le batch d'analyse User-Agent (`runBatch()`) est désormais aussi déclenché depuis la page Accueil (en plus de Projet et Statistique), avec une taille de lot codée en dur (`100`) au lieu du paramètre configurable `USER_AGENT_BATCH_AUTOMATIQUE` déjà utilisé par les deux autres pages — corrigé pour respecter ce réglage, y compris la désactivation du batch quand la variable d'environnement est vide ;
- **User-Agent** : Deux appels concurrents à `runBatch()` (déclenchables depuis Accueil, Projet et Statistique) pouvaient sélectionner et traiter le même événement en double, faute de verrouillage lors de la réclamation du lot — nouvelle méthode `UserAgentEventRepository::claimPendingEvents()` (`SELECT ... FOR UPDATE SKIP LOCKED` dans une transaction courte), qui remplace l'ancien verrouillage ligne par ligne non protégé contre la concurrence. Même principe déjà utilisé par `DcProcessingQueueRepository`/`ZapProcessingQueueRepository::claimNextBatch()`.

## [2.2.0] - 18-10-2026

Ajout de nouvelles fonctionnalités à l'application.

### Ajouté

- **USER_AGENT** : Personnalisation du nombre de `USER_AGENT` traité par le batch automatique (page PROJET) et manuel (index). Attention, deux nouvelles propriétés doivent être ajoutées.
- **Tests** : Ajout des tests unitaires pour couvrir les paramètres du nombre de tracements à réaliser en manuel ou automatique ;
- **RCI** : Intégration de l'indicateur RCI (plugin `sonar-rci`) aux indicateurs standards, avec un détail par module (frontend/backend/autre/inconnu) en plus de la mesure globale — nouvelle table `rci`, colonnes `historique.rci_*` (indicateur, note, nombre de lignes de code et poids des anomalies, pour le global et chaque module), calculé automatiquement à la suite de la collecte (aucune action manuelle requise). Côté page Projet : note A-E affichée à côté de la Quality Gate, et nouvelle fenêtre de détail (tableau + graphique en barres empilées RCI/dette) par module.
- **Anomalies** : Répartition des anomalies par portée SonarQube (`MAIN` = code source, `TEST` = code de test), globale et par catégorie (bug/vulnérabilité/mauvaise pratique) — 8 nouvelles colonnes sur la table `anomalie` et sur `historique`, calculées par les deux flux de collecte (batch/cron et interactif). Côté page Projet : ligne « Scope Main/Test » (répartition en %) et nouvelle fenêtre de détail (tableau + graphique en barres empilées) par catégorie, sur le même modèle que RCI.
- **Historique** : Chaque ligne de `historique` porte désormais la version du serveur SonarQube qui l'a produite (colonne `sonarqube_server_version`, simple marqueur de traçabilité, « inconnu » si la requête échoue — aucun affichage associé) ;
- **Suivi** : Tendance RCI et Scope Main/Test ajoutée à la page Suivi (tableau de synthèse + nouveau graphique en lignes pleines) et au rapport PDF (tableau de synthèse + nouvelle page dédiée RCI : note, indice global, indice ncloc, indice de dette, par version) ;
**Empreinte Écologique** : Nouvel indicateur basé sur les plugins SonarQube Creedengo (creedengo-java/javascript/php/python, détection d'anti-patterns éco-conception) — nombre d'anomalies, dette estimée et note A-E (seuils SQALE 5/10/20/50%, identiques au RCI), globaux et par langage (Java/JavaScript/PHP/Python), calculés automatiquement à la suite de la collecte (aucune action manuelle requise), nouvelle table `eco_conception` + colonnes `historique.eco_*`. Activable via la propriété applicative `CREEDENGO_ENABLED` (même principe que l'extension `track-logger-method`) : désactivé par défaut, les serveurs SonarQube sans ces plugins ne sont pas impactés et la page Projet affiche `-`. Côté page Projet : ligne « Empreinte Écologique » (nombre d'anomalies + badge de note).
- **Mesures** : Collecte des 3 métriques `maintainability_issue_severity`, `reliability_issue_severity`, `security_issue_severity` (apparues sur SonarQube entre les versions 26.3.0 et 26.9.0, pas encore documentées par SonarSource au moment de l'ajout) — nouvelles colonnes nullables sur `mesures` et `historique`, valeur brute (pas de note A-E, sémantique pas encore connue). Récupérées via un appel `/api/measures/component` séparé et non bloquant : un serveur qui ne les connaît pas encore renvoie simplement `null` sur ces 3 colonnes, sans impacter le reste de la collecte des mesures. Collecte uniquement pour l'instant (pas d'affichage sur la page Projet) ;
- **ZAP (DAST)** : Nouvel indicateur complémentaire au module OWASP existant (analyse statique SonarQube) : ingestion de rapports produits par le scanner dynamique [ZAP](https://www.zaproxy.org/), postés par la CI via `POST /api/secure/zap/upload` (token bearer dédié, file d'attente, worker `app:zap:process`, découplé des pipelines de collecte habituels — même principe que l'extension OWASP Dependency-Check déjà en place). Les alertes ZAP sont automatiquement regroupées par catégorie OWASP Top 10 (2017 et 2021, les deux référentiels déjà utilisés côté analyse statique), nouvelle table `owasp_dast`. Nouvelle page `/zap`, accessible comme Dependency-Check depuis la page OWASP (rôle `ROLE_SECURITY`) : indicateurs clés, répartition par catégorie et par niveau de risque, graphiques (dont l'évolution dans le temps) et historique des analyses. Nouvelle commande `app:zap:purge` (purge des rapports terminés plus anciens que N jours, même principe que `app:dependency-check:purge`) ;
- **Commandes Prod** : Journalisation dédiée des commandes planifiées par le cron Docker (`app:*`) sur un nouveau channel Monolog `cron` (fichier `var/log/cron.log` en production), plutôt que sur le journal applicatif générique — consultable depuis la page d'administration des journaux (nouveau filtre « Cron »). S'applique à 8 des 10 commandes de `src/Command/Prod/` (les 2 exceptions : `app:sonar:compare-metrics`, outil de diagnostic sans journalisation, et `app:dependency-check:reprocess`, outil de reprise manuelle jamais planifié) ;
- **RGPD** : Purge automatique du journal des modifications de rôle (`app:user-role-log:purge`), de l'activité de collecte SonarQube (`app:activity:purge`, tables `activity`/`activity_historique`) et des événements/analyses User-Agent (`app:user-agent:purge`, tables `user_agent_event`/`user_agent_analysis`), jusqu'ici conservés sans limite — comble l'écart identifié dans [l'analyse de conformité RGPD](mkDocs/docs/ma-moulinette/conformite/rgpd.md). Durées de rétention par défaut (365 jours pour les deux premiers, 180 jours pour la donnée purement statistique User-Agent) réglables sans redéploiement via les variables d'environnement `USER_ROLE_LOG_RETENTION_DAYS`/`ACTIVITY_RETENTION_DAYS`/`USER_AGENT_RETENTION_DAYS`, cron quotidien ;

### Modifié

- **Historique** : Pour une version RELEASE, RC, BETA ou ALPHA, enregistrer une nouvelle analyse de la **même** version remplace désormais la ligne existante au lieu d'en créer une nouvelle (une seule ligne par version, la plus récente). Les versions SNAPSHOT ne sont pas concernées : leur numéro de version ne change pas entre deux analyses (plusieurs par jour en développement), chaque analyse reste donc historisée comme avant ;
- **Code-Clean** : Suppression du style inline sur la page (dashboard) ;
- **Statistiques** : Ajustement de la taille du titre (index) ;
- **Tests** : Utilisation de la fixture dc-json 2048-1.0-SNAPSHOT dans les deux tests "skipped" par faute de trouver un rapport dans /var ;
- **Doc** : Mise à jour des statistiques de tests (README, page d'accueil mkDocs, test-unitaire.md) ;
- **RCI** : Le calcul global et le détail par module partagent désormais une seule taxonomie de sévérité (sélectionnée selon la version de SonarQube) au lieu de deux paires de colonnes distinctes (`rci_core_*`/`rci_2024_*` fusionnées en `rci_indicator`/`rci_rating`) ;
- **PHPStan** : Campagne de réduction de la dette niveau 6 (153 → 68 notifications) — ajout des types manquants (propriétés, paramètres, retours, generics `ServiceEntityRepository`), typage explicite des doublures PHPUnit (`&MockObject`/`&Stub`) sur les propriétés de test, sans changement de comportement ;
- **Tests** : Couverture de lignes portée de 79,54% à 80,29% (objectif SonarQube) — complète les tests getter/setter des entités `Historique`, `Mesures`, `Anomalie`, `Owasp`, `BatchExecutionJournal` (attributs ajoutés aux sessions précédentes jamais exercés par un test) et crée les tests manquants pour `Rci`/`CleanCode` (aucun test unitaire n'existait pour ces deux entités, seuls les tests d'intégration les touchaient indirectement) ;
- **Appels SonarQube** : Le paramètre `componentKeys` de `/api/issues/search` (renommé `components` depuis SonarQube 10.2) est désormais choisi dynamiquement selon la vraie version du serveur interrogé (`GetSonarVersionService`, mise en cache 5 minutes), au lieu d'être codé en dur — `components` par défaut, `componentKeys` uniquement sur confirmation d'un serveur 8/9. Aucun changement de comportement sur les serveurs actuellement supportés (l'ancienne clé reste acceptée partout, seul un avertissement de dépréciation disparaît des journaux SonarQube).
- **Anomalies actives** : Le filtre de statut sur `/api/issues/search` (`statuses`, déprécié depuis SonarQube 10.4) est lui aussi choisi dynamiquement selon la vraie version du serveur — `statuses=OPEN,CONFIRMED,REOPENED` pour les serveurs 8/9/10, `issueStatuses=OPEN,CONFIRMED,ACCEPTED` à partir de 2024 (modèle de statuts redéfini par SonarQube, pas un simple renommage). Au passage, les 6 contrôleurs concernés utilisent désormais le même périmètre d'anomalies « actives » (certains ne filtraient jusqu'ici que sur `OPEN` seul). Vérifié en conditions réelles (comparaison de totaux avant/après un changement de statut réel sur un projet) — voir `mkDocs/docs/ma-moulinette/application/projet.md` pour la règle complète et ses conséquences.

### Corrigé

- **Anomalies par module** : Un projet jamais collecté côté anomalies (frontend/backend/autre/inconnu, répartition par portée MAIN/TEST) affichait `0` comme s'il avait été collecté avec zéro anomalie par module, au lieu de `-` (pas encore collecté). Corrigé côté `ApiPeintureController` (`?? null` au lieu de `?? 0`) et côté JS (`peinture.js` route désormais ces 4 champs par le helper d'affichage commun ; les champs non collectés ne sont plus écrits dans un `data-*` attribut, pour qu'`enregistrement.js` les transmette comme réellement absents plutôt que comme la chaîne `"null"`, qui aurait fait échouer l'enregistrement en base) ;
- **RCI** : Le report des indicateurs RCI globaux (indicateur/note) vers `historique` était incomplet lors de l'enregistrement interactif (page Projet) — seul le détail par module était pris en compte, pas la mesure globale ;
- **RCI** : La lecture de la dernière mesure RCI ne gérait pas le cas d'un projet sans collecte RCI préalable (absence de ligne en base), ce qui pouvait remonter des valeurs incohérentes au lieu d'un simple « aucune donnée » ;
- **Répartition** : Sélection de la facette de sévérité par son nom plutôt que par sa position dans la réponse SonarQube, pour rester correcte quel que soit le nombre de facettes demandées ;
- **Anomalies** : Les colonnes `historique.repartition_frontend/backend/autre/inconnu` restaient toujours vides lors d'une collecte planifiée ou manuelle (seule la saisie interactive les renseignait) — la répartition par module n'était historisée que dans un seul des deux flux de collecte ;
- **Historique** : Le nom de colonne envisagé pour la version du serveur SonarQube (`version_sonar`) était déjà pris par `information_projet.version_sonar`, qui désigne un concept différent (nombre de versions connues) — renommé en `sonarqube_server_version` avant toute collecte réelle ;
- **Appels SonarQube** : Quand une facette demandée n'existe pas sur la version du serveur interrogé (ex. `owaspTop10-2021` sur un serveur en version 8, cf. `SONAR_VERSION`), le message technique brut de SonarQube est désormais remplacé par un message explicite pointant vers le paramètre à vérifier, au lieu d'être affiché tel quel ;
- **Rapport PDF Suivi** : Les pastilles de note A-E et Quality Gate (tableaux « Synthèse Qualité », « Suivi des anomalies », tuiles KPI de la page de garde) étaient invisibles dans le PDF — un reformatage antérieur avait répété la classe CSS (`note note-{{...}}`) sur plusieurs lignes, ce qui scinde la classe en plusieurs mots invalides en HTML (`note-` et la lettre séparément) au lieu d'un seul (`note-a`) ; le texte restait alors blanc sur fond blanc. Remis sur une seule ligne.

## [2.1.0] - 20-09-2026

Ajout de nouvelles fonctionnalités à l'application. Mise à jour des dépendances symfony et des statistiques du projet.

### Ajouté

- **Statistiques** : Ajout de l'analyse automatique des statistiques d'utilisation de l'application à l'ouverture de la page projet.
- **Markdown** : Ajout des règles d'exclusion légitimes (MD024).
- **SQL**: Ajout du script d'update automatique de la table ma_moulinette (mise à jour des version 2.0.0 vers 2.1.0).

### Modifié

- **Code-Clean** : Ajout d'une private static pour remplacer un string littéral dans la page `StatistiqueController` ;
- **Symfony** : Migration 8.0.15 en 8.1.7 ;
- **README** : Nouveau tableau sur l'état du projet ;

## [2.0.2] - 2026-09-19

Corrections mineures.

### Ajouté

**Avatar** : Ajout du focus lors de la sélection de l'avatar sur l’événement 'change-me'.

### Modifié

- **Code-Clean** : Correction des balises fermantes et manquantes dans les templates Twig (owasp, accueil, auth, profil, batch, activity, preference projet, plan-du-site, statistiques) ;

### Corrigé

- **Avatar** : Correction du contentType du script `details.js` qui bloquait l'appel Ajax sur le choix de l'avatar.

## [2.0.1] - 2026-09-13

Corrections mineures.

### Modifié

- **projet** : Correction du nettoyage des valeurs quand on relance une collecte sur un projet déjà collecté et affiché ;
- **code-clean** : Correction des balises fermantes dans les templates Twig (erreurs de parsing) ;
- **formatage** : Conversion de certains fichiers en UTF-8 BOM (Windows) vers UTF-8 (Linux) pour éviter les erreurs de parsing Twig, remplacement du formateur twig par html dans vscode et reformatage de tous les fichiers Twig ;

## [2.0.0] - 2026-09-06

Reprise complète du projet initial (Symfony 5.4 / SQLite) vers une architecture Symfony 8 / PostgreSQL 18. Périmètre couvert depuis `v1.6.0`.

### Ajouté

- **Framework** : intégration de `knplabs/knp-paginator-bundle` ;
- **Docker** : stack `docker-compose.yml` complète (base de données, PHP-FPM, cron, NGINX), crontab planifiant les jobs `OWASP DependencyCheck`, `clean-tags`, `collecte` et `activity` via un sidecar Supercronic ;
- **Base de données** : table `Actuator`, colonnes `tags`/`visibilité` sur `liste_projet`, colonnes `mode_collecte`/`utilisateur_collecte` sur la quasi-totalité des tables, tables `batch_traitement`/`batch_profiling` ;
- **Sécurité** : rôles étendus (`COLLECTE`, `BATCH`, `ACTUATOR`, `SUPER_ADMIN`, `INTERNAL`…) ; authentification **LDAP** (OpenLDAP, Microsoft AD) en complément du local, avec provisioning automatique ; honeypot anti-bot sur l'inscription ;
- **Back-office EasyAdmin** : CRUD dédiés groupes fonctionnels/utilisateur, portefeuilles de projets et batch ; nouvelle page **Journal des rôles** (consultation, archive CSV/PDF) ; nouvelle page **Logs applicatifs** ;
- **Traitement & Profiling** : nouveau module de file de traitement (déclenchement manuel/automatique de la collecte, files distinctes, identifiants Ulid, journal d'exécution) et nouvelle page de mesure des performances des traitements ;
- **Actuator** : nouveau module de collecte pour applications Java (déclaration de points d'accès, extraction des clés JSON à chaque analyse, pastille et modale sur la page Projet) ;
- **Pages applicatives** : refonte ou enrichissement de Accueil (vues Projet/Version/Vide), Profil (suivi des référentiels), Activité (nouvelle page), Projet (dette technique, LOGGER Java, répartition par langage, NoSonar), Suivi (tableau des mesures), OWASP (référentiels 2017/2021), Préférences (versions favorites multiples) ;
- **Authentification** : indicateur de qualité du mot de passe à l'inscription, changement de mot de passe forcé à la première connexion admin, option « voir mon mot de passe » ;
- **Accessibilité (RGAA)** : mise en conformité progressive de l'application (sémantique HTML, ARIA, sous-titres) ;
- **Tests** : suite **Playwright End-to-End** (22 suites, `tests/e2e/`) couvrant les parcours applicatifs et back-office critiques — voir [Tests End-to-End](mkDocs/docs/ma-moulinette/developpement/test-e2e.md) ; couverture unitaire/intégration portée à 80 % (`tests/Unit` + `tests/Integration`).

### Modifié

- **Framework** : migration PHP 8.5.5 NTS, Symfony 6.4 → 8.0, PostgreSQL 18 (depuis SQLite), Twig 3, Doctrine DBAL 4/ORM 3, EasyAdmin 4, PHPStan/PHPUnit ;
- **Documentation** : migration vers **mkDocs** (Material) ;
- **Transverse** : décommissionnement de Webpack au profit de **Symfony asset-mapper** ; réécriture des processus de collecte (suppression de la duplication batch/utilisateur) ; normalisation des messages (flash, JS, logs) ; centralisation du traitement des erreurs HTTP/SQL ;
- **Actuator** : collecte **best effort** (un point d'accès en échec n'interrompt plus la collecte du projet) ;
- **Analyse statique** : campagne de réduction PHPStan, dette ramenée de 4 820 à 150 erreurs résiduelles (niveau 6 retenu comme cible du projet).

### Supprimé

- Table `tags` (remplacée par les colonnes `tags`/`visibilité` de `liste_projet`) ;
- Bookmark de préférence en base de données (remplacé par `sessionStorage`) ;
- Suite `tests/Functional` (remplacée par la suite Playwright End-to-End) ;
- « Mode Test » applicatif (remplacé par une vraie base de données de test).

### Sécurité

- Durcissement des contrôles d'accès serveur sur les CRUD EasyAdmin (Utilisateur, Groupes, Portefeuille, Batch), jusque-là protégés par le seul pare-feu ;
- Filtrage par groupe fonctionnel sur les endpoints Suivi/COSUI (périmètre applicatif, pas seulement l'authentification) ;
- Limitation de débit (`symfony/rate-limiter`) sur l'endpoint public de healthcheck.

## v1.6.0 - 30/11/2022 - Release

- [CSS] Utilisation du référentiel des couleurs SonarQube pour afficher les notes. Pages concernées : [home], [projet], [owasp], [suivi] et [cosui].
- [Page_COSUI] Ajout d'une page de synthèse de type Comité de Suivi (i.e COSUI) ;
- [CSS] La variation des indicateurs utilise maintenant la class [down] pour marquer une amélioration c'est-à-dire une baisse des signalements, la flèche est verte et pointe vers le bas et [up] pour marquer une augmentation des défauts. La flèche pointe vers le haut sa couleur est rouge ;
- [Page_Projet] Ajout du bouton COSUI ;
- [CSS] Refactorisation des class js- et -enabled pour la page [PROJET] ;
- [Page_Projet] Renommage de la page anomalie.details.html.twig en details.html.twig ;
- [Page_Home] Ajout d'un raccourci pour ouvrir directement la page de suivi ;
- [Page_Suivi] Amélioration de l'information utilisateur de la fenêtre modale d'ajout d'une version ;
- [Page_Suivi] Refactorisation de la page pour prendre en compte le référentiel des couleurs sonar ;
- [Page_Home] Ajout des indicateurs de visibilités ;
- [Page_Suivi] Amélioration de la gestion des codes HTTP (404) ;
- [Page_Dashbord] Ajout du contrôle d'intégrité de la base de données ;
- Mise à jour symfony 6.1.8 ;

## v1.5.0 - 12/10/2022 - RELEASE

- [Page_Home] : Gestion des versions ;
  - On vérifie si la version de l'application est dans la table [ma_moulinette] ;
  - On affiche un message  à l'utilisateur si la version de la base est plus ancienne que la version de l'application ;
- [Docker] : Mise à jour de la configuration pour la stack docker ;
- [Symfony] Migration 5.4.4 vers 6.1.4 ;
- [PHP] Migration 8.1.10 ;
- [Securité] Ajout d'un cipher de type legacy pour les accès depuis un serveur NGINX ;
- [Configuration] Remplacement des chemins absolus par des chemins relatifs pour la BD ;
- [Sécurité] Filtrage des connexions par type d'HOST (domaine, IP, RegEx) ;
- [Authentification] Gestion des utilisateurs.
  - Ajout de la table [utilisateur] ;
  - Ajout d'un formulaire de connexion [login] ;
  - Ajout d'un formulaire d'inscription [register];
  - Ajout d'un formulaire de bienvenue [welcome], une fois l'inscription réalisée ;
  - Ajout de l'option **remember-me** ;
  - Ajout d'un firewall et des rôles [USER], [GESTIONNAIRE] et [PUBLIC_ACCESS] ;
  - Ajout des icônes **logout** et **dashboard** et **utilisateurs** dans le haut de page ;
- Ajout du module EasyAdmin :
  - Personnalisation des pages, du menu et des contrôleurs CRUD ;
  - Ajout des indicateurs et des statistiques globales sur la page Dashboard ;
  - Ajout de la page de gestion des comptes utilisateurs [ROLE_GESTIONNAIRE] ;
  - Ajout d'un lien vers la page [HOME] ;
- Activation et optimisation du cache opCahe (pour la prod uniquement);
- Refactoring complet de la documentation ;
- [Projet] Ajout de l'événement **Collecte** sur le bouton `C` du menu rapide ;
- [Erreurs] Personnalisation des erreurs HTTP (en production) ;
- [Utilisateur] Ajout du compte admin ;
- [Home] refactoring de la page :
  - Suppression du code JS et HTML pour les composants nécessitant un droits Admin dans SonarQube ;
  - Suppression des appels Asynchrone JS pour afficher le nombre de projet et de profil ;
  - Ajout de la table properties, contenant la nombre de projet, de profil en base et sur le serveur et leur date de modification ;
  - Ajout dans le contrôleur du processus de gestion des projets et des profils (anciennement en JS) ;
  - Amélioration de l'affichage des messages d'alerte. Suppression du statut pour des callout ;
  - Affichage du nombre de projet et de profil en plus/moins ;
  - Correction SonarQube ;
- [Profil] Correction de l'affichage du statut Actif lors du rechargement de la liste ;
- [Paramétrage] Choix de la fréquence de mise à jour. Par défaut 1 jour ;

## v1.4.0 - 06/07/2022 - RELEASE

- [Page Projet] : Modification du pictogramme (SVG) pour le bouton répartition ;
- Modification des appels de web-service Asynchrones (11). Utilisation de la méthode Promise + Callback (await) ;
- [Page Projet] : Modification de la fenêtre modale 'liste des projets'. Ajout des actions suivantes :
  - [V] Sélectionner un projet de la liste et déverrouiller tous les boutons ;
  - [S] Supprimer le projet de la liste des projets déjà analysés ;
  - [C] Lance la collecte des indicateurs SonarQube et le calcul des agrégats ;
  - [R] Lance la restitution des données calculés ;
  - [I] Ouvre la page de suivi des indicateurs ;
  - [O] Ouvre la page du rapport OWASP ;
  - [RM] Ouvre la page de suivi de la répartition par module ;
- [Page Projet] : Ajout de l'attribut "liste" de type boolean à la table "Anomalie" pour gérer l'affichage ou non, du projet, dans la liste des projets déjà analysés.

## v1.3.0 - 03/07/2022 - RELEASE

- [Page Suivi] : Correction de l'affichage du tableau de suivi des anomalies par type et sévérité ;
- [Page Projet] : Correction - le bouton analyse Owasp est bloqué tant que l'utilisateur n'a pas choisi un projet ;
- [Page Répartition] Correction - "Impossible to access a key ("setup") on a string variable ("NaN")." ;
- [Page Suivi] : Remplacement des valeurs "null" par -1 au lieu de 0 ;
- [Page Suivi] : Amélioration de l'affichage du tableau de suivi par type d'anomalies (i.e. mise en couleur des types) ;
- [Page Suivi] : Prise en compte de la version de référence pour le tableau de suivi des anomalies par type et sévérité ;
- [Page Répartition] : Ajout du tableau de bord par répartition : frontend, backend, autre ;
- [Page Répartition] : Ajout de l'Indice de Confiance ;
- [Page Répartition] : Masquage des tableaux vides (Fiabilité, sécurité et maintenabilité) ;
- [Page Répartition] : Ajout de la notion de set-up pour la gestion des versions collectées dans la table temp_repartition ;
- [Architecture] :Séparation des EntityProviders : **default** pour la base des données agrégées et **secondary** pour la base des données d'analyse ;
- [Architecture] : Reworking des requêtes pour la base temp. Suppression des requêtes avec EntityManager (SQL), ajout des requêtes en utilisant le ManagerRegistry (findBy,etc...) ;
- [Page Répartition] : Amélioration de l'affichage du tableau des sévérités par type de la page "Repartition". Utilisation d'un accordéon pour réduire la taille de la page ;

## v1.2.6 - 02/06/2022 - Release

- [Suivi des indicateurs] : Correction de la méthode de suppression d'une version dans la fenêtre modale "Modifier les paramètres" ;

```plaintext
Uncaught PHP Exception Doctrine\DBAL\Exception\SyntaxErrorException:
"An exception occurred while executing a query: SQLSTATE[HY000]:
General error: 1 near "4.1": syntax error"
```

- [Suivi des indicateurs] : Amélioration de l'affichage du graphique (mode responsive) ;
- [Suivi des indicateurs] : Correction des labels tronqués en haut du graphique ;
- [Suivi des indicateurs] : Modification de la méthode d'injection des données pour le graphique ;

## v1.2.5 - 24/05/2022 - Release

- Corrections de l'affichage de la note pour les vulnérabilités et du nombre de mauvaises pratiques, du tableau de la fenêtre modale "ajouter une analyse" (BoardController.php et app-dash.sj) ;
- Correction de l'enregistrement des données collectées lors de l'ajout d'une analyse historisée. Ajout des attributs obligatoires dans la requête (facultatifs en version 1.2.0).
- Formatage et nettoyage de toutes les requêtes complexes SQL : `trim(preg_replace("/\s+/u", " ", $sql))` ;
- Remplacement dans la page de suivi des indicateurs des valeurs null enregistrées avec la valeur "-1" par un tiret "-" ;
- Ajout du rechargement de la page de suivi des indicateurs quand on clique sur le bouton "fermer" de la fenêtre "ajouter une analyse" ;
- Correction du problème des valeurs "null" pour les hotsposts (valeurs non calculés sur les anciennes version de SonarQube) ;
- Correction de la date dans le tableau de suivi quand on ajoute une version depuis l'historique (date du jour au lieu de la date de la version) ;

## v1.2.4 - 17/05/2022 - Release

- Ajout de nouvelles exceptions sur les modules Backend et Autre ;
- Mise à de la documentation
- Correction du tri sur le tableau des hotspots (page OWASP / Détails).
- Ajout de l'attribut "niveau" à la table hotpspot_details pour gérer le tri ;
- Remplacement du mot "Batch" par "Autre" pour prendre en compte les modules de type "Batch" ou les Autres ;
- Modification des tables : Anomalie, Historique et HotspotDetails pour prendre en compte le changement de l'attribut "batch" par "autre" ;

## v1.2.3 - 12/05/2022 - Release

- Correction du traitement et de l'affichage de failles par module.

## v1.2.2 - 11/05/2022 - Release

- Ajout d'une base vierge pour démarrer ;
- ApiProjectController : Warning: Undefined array key "date_enregistrement" (ligne 1461) ;
- Correction du format de la date : date("m/d/Y) à date("d/m/Y) pour la page dash, profil et home ;
- Correction de l'ordre des colonnes pour le tableau de suivi des sévérités par type ;
- Correction des hotspots corrigés et toujours en base ;
- Correction "Warning: Undefined array key [line]", suppression de l'affectation en double ;
- Correction de la page OWASP (erreurs typographiques lors de refactoring) ;

## v1.2.1 - 08/05/2022 - Release

- Erreur : "Cannot instantiate interface Psr\Log\LoggerInterface". On n'utilise plus l'instanciation (new LoggerInterface) mais l'injection de dépendance ;
- Erreur de syntaxe dans l'utilisation de littérales dans le contrôleur ApiProjetContrôleur ;
- Suppression des dépendances inutiles dans HomeControleur et ProfilControleur ;
- Correction des fautes et erreur de typographie dans le fichier readme.md ;

## v1.2.0 - 05/05/2022 - Release

- Corrections SonarQube ;
- Ajustement du niveau de log (debug-->info) ;

## v1.1.0 - 24/04/2022 - Release

- Mise à jour du modèle de base de données ;
- Collecte des sévérités par type lors de la collecte, enregistrement dans la table historique, attention le modèle de la table est changé, il faut ajouter les nouveaux attributs (cf. readme);
- Suppression de la table temp_anomalie et réutilisation de la table anomalie, pour enregistrer le détail des sévérités par type ;
- Ajout dans la page du suivi des indicateurs, d'un tableau permettant d'afficher par version, le type d'anomalie par sévérité des trois dernières versions ;
- Correction du problème d'affichage des favoris et de la valeur de référence ;
- Ajout de la date de la version pour les tableaux de suivi ;

## v1.0.0 - 10/04/2022 - Release

- Ajout d'un exemple d'édition pour la page suivi des indicateurs ;
- Amélioration de la page et indication de la sévérité en rouge ou vert en fonction de la tendance.

## v0.0.11 - 07/04/2022

- Mise en place du socle de génération de rapport PDF ;
- Ajout dans le tableau de suivi des violations par sévérité mineure ;
- Correction de la couleur pour indiquer la version de référence (rouge) ;

## v0.0.10 - 02/04/2022

- Correction de l'affichage des notes lorsque l'on importe une version manuellement depuis la fenêtre d'ajout de la page de suivi ;
- Blocage des boutons quand le projet n'est pas choisi dans la page projet ;

## v0.0.9 - 31/03/2022

- Remplacement des favicons et du logo ;
- Correction de l'affichage de la version de référence et des favoris (problème de Boxing du Boolean initial et favori );
- Ajout d'un lien "modification" sur la page de suivi des mesures.
  - Permet de supprimer une version ;
  - Active ou désactive, pour une version, le statut favori ;
  - Active ou désactive, pour une version, le statut référence ;
- Reworking des favoris dans la page home ;
- Correction de l'affichage quand le nombre de violations est >20000 (20k) ;

## v0.0.8 - 29/03/2022

- Corrections et amélioration ;
  - Ajout de la note Z pour les applications n'ayant de hotposts ;
  - Correction typographique ;

## v0.0.7 - 28/03/2022

- Version de recette ;
- Mise à jour de la documentation ;
- Mise à jour de la procédure de démarrage et d'arrêt ;

## v0.0.6 - 18/03/2022

- Corrections Sonarqube ;
- Amélioration de la page de suivi des indicateurs (Dash) ;
  - Affichage de la version de référence ;
  - Affichage des 9 dernières versions ;
- Ajout d'un formulaire d'ajout d'une version à partir des données Sonarqube disponible dans l'historique ;
- Refactoring de la sauvegarde ;
- Refactoring de la méthode d'ajout manuel d'une version ;
- Corrections des bugs ;

## v0.0.5 - 13/03/2022

- Ajout de la page dash, pour afficher des tableaux de suivi ;
- Ajout du tableau de suivi des versions analysées ;
- Ajout de graphique pour suivre l'évolution des violations par type ;
- Ajout du tableau de suivi des violations par modules (webapp - front, api - back et batch) ;
- Ajout du tableau de suivi les violations par sévérité (bloquant, critique et majeur) ;

## v0.0.4 - 01/03/2022

- Refactoring de la page projet, création d'une page détails ;
- Mise en place d'un système de sauvegarde pour les données calculées ;
- Correction des bugs ;

## v0.0.3: 25/02/2022

- Correction de bug ;
- Tests de reprise de plusieurs gros projets à plus de 10000 défauts ;
- Migration de la page security en OWASP ;

## v0.0.2: 24/02/2022

### Fonctionnalités principales

- Création de la page **Home** :
  - Ajout du header/footer ;
  - Ajout du fil d’Ariane ;
  - Ajout d'une zone pour afficher les traces ;
  - Ajout d'un spinner (à revoir) ;
  - Ajout d'un bouton pour lancer la collecte des projets disponibles sur le serveur sonar ;
  - Affichage du nombre de projets disponible ;
  - Ajout d'un bouton pour afficher la liste des profils qualités ;
  - Affichage du nombre de profils disponible ;
  - Ajout d'une zone Favori, pour afficher les projets favoris (partielle) ;
  - Ajout du bouton "mon projet" pour accéder à la page des projets ;

- Création de la page **Projet** :
  - Ajout du header/footer ;
  - Ajout du fil d’Ariane ;
  - Ajout d'une zone pour afficher les traces ;
  - Ajout d'un spinner (à revoir) ;
  - Ajout d'un bouton pour lancer la collecte des données depuis le serveur Sonar ;
  - Ajout d'une liste de sélection avec recherche à partir de deux lettres ;
  - Ajout d'un petit-cœur, pour marquer les projets Favoris ;
  - Ajout d'un bouton permettant d'afficher l'ensemble des projets présents dans la base locale et ceux en favoris ;
  - Ajout d'un bouton permettant la génération et la restitution des indicateurs (i.e. la peinture) ;
  - Ajout d'une zone **Information** et NoSonar permettant d'afficher les informations générales sur le projet sélectionné :
    - Le nom ;
    - La clé ;
    - Le nombre de versions release ;
    - Le nombre de versions snapshot ;
    - Un bouton "Autre" qui permet d'afficher un graphique représentant la distribution des versions ;
    - La version de la dernière application analysée ;
    - La date de la dernière version analysée ;
    - Le nombre d'exclusions de type @supressWarbing et NoSonar ;
  - Ajout de la zone **Projet** permettant l'affichage des informations techniques du projet :
    - Le nombre de lignes de code ;
    - La couverture de tests ;
    - Le nombre de tests unitaire ;
    - Le niveau de duplications du code ;
    - Le nombre de défauts ;
    - La répartition de la dette technique : fiabilité, sécurité et mauvaise pratique ;
    - Les notes pour les indicateurs qualités et la répartition des hotspots ;
  - Ajout de la zone **Qualité** permettant d'afficher pour chaque indicateur qualité la distribution selon la sévérité ;
  - Ajout d'un bouton permettant d'aller sur la page **Sécurité** OWASP (en cours de migration) ;

- Création de la page **Profil** :
  - Ajout du header/footer ;
  - Ajout du fil d’Ariane ;
  - Affichage du tableau de répartition des règles par langage ;
  - Affichage de la distribution depuis un graphique ;

### Reste à faire

1. Afficher sur le page Home, les projets favoris ;
2. Ajouter la page OWASP Top 10 ;
3. Ajouter le Top 10 des violations ;
4. Ajouter un tableau consolidé permettant la distinction entre la partie frontend (présentation) et backend (métier) ;
5. Ajouter une timeline pour suivre les projets dans le temps et afficher les modifications ;
6. Ajouter les rapports PDF avec les éléments détaillés nécessaires pour les développeurs ;

## V0.0.1 : 24/01/2022

- Création du projet sous Symfony
- Reprise du projet développé eb HTML/CSS, Javascript + DexieJS ;
- Migration sous Symfony 5.4, php 8.1.0 ;
- Migration dexieJs vers SQLite 4 ;
- Migration du code en HTML/JS vers PHP, Twig ;
- Migration vers webpack ;
