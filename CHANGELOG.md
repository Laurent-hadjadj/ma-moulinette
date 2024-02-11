# Liste des évolutions et défauts corrigés

## V0.0.1 : 24/01/2022

### Création du projet sous symfony

* Reprise du projet développé eb HTML/CSS, Javascript + DexieJS ;
* Migration sous symfony 5.4, php 8.1.0 ;
* Migration dexieJs vers SQLite 4 ;
* Migration du code en HTML/JS vers PHP, Twig ;
* Migration vers webpack ;

## v0.0.2: 24/02/2022

### Fonctionnalités principales

* Création de la page **Home** :
  * Ajout du header/footer ;
  * Ajout du fil d’Ariane ;
  * Ajout d'une zone pour afficher les traces ;
  * Ajout d'un spinner (à revoir) ;
  * Ajout d'un bouton pour lancer la collecte des projets disponibles sur le serveur sonar ;
  * Affichage du nombre de projets disponible ;
  * Ajout d'un bouton pour afficher la liste des profils qualités ;
  * Affichage du nombre de profils disponible ;
  * Ajout d'une zone Favori, pour afficher les projets favoris (partielle) ;
  * Ajout du bouton "mon projet" pour accéder à la page des projets ;

* Création de la page **Projet** :
  * Ajout du header/footer ;
  * Ajout du fil d’Ariane ;
  * Ajout d'une zone pour afficher les traces ;
  * Ajout d'un spinner (à revoir) ;
  * Ajout d'un bouton pour lancer la collecte des données depuis le serveur Sonar ;
  * Ajout d'une liste de sélection avec recherche à partir de deux lettres ;
  * Ajout d'un petit-cœur, pour marquer les projets Favoris ;
  * Ajout d'un bouton permettant d'afficher l'ensemble des projets présents dans la base locale et ceux en favoris ;
  * Ajout d'un bouton permettant la génération et la restitution des indicateurs (i.e. la peinture) ;
  * Ajout d'une zone **Information** et NoSonar permettant d'afficher les informations générales sur le projet sélectionné :
    * Le nom ;
    * La clé ;
    * Le nombre de versions release ;
    * Le nombre de versions snapshot ;
    * Un bouton "Autre" qui permet d'afficher un graphique représentant la distribution des versions ;
    * La version de la dernière application analysée ;
    * La date de la dernière version analysée ;
    * Le nombre d'exclusions de type @supressWarbing et NoSonar ;
  * Ajout de la zone **Projet** permettant l'affichage des informations techniques du projet :
    * Le nombre de lignes de code ;
    * La couverture de tests ;
    * Le nombre de tests unitaire ;
    * Le niveau de duplications du code ;
    * Le nombre de défauts ;
    * La répartition de la dette technique : fiabilité, sécurité et mauvaise pratique ;
    * Les notes pour les indicateurs qualités et la répartition des hotspots ;
  * Ajout de la zone **Qualité** permettant d'afficher pour chaque indicateur qualité la distribution selon la sévérité ;
  * Ajout d'un bouton permettant d'aller sur la page **Sécurité** OWASP (en cours de migration) ;

* Création de la page **Profil** :
  * Ajout du header/footer ;
  * Ajout du fil d’Ariane ;
  * Affichage du tableau de répartition des règles par langage ;
  * Affichage de la distribution depuis un graphique ;

### Reste à faire

1. Afficher sur le page Home, les projets favoris ;
2. Ajouter la page OWASP Top 10 ;
3. Ajouter le Top 10 des violations ;
4. Ajouter un tableau consolidé permettant la distinction entre la partie frontend (présentation) et backend (métier) ;
5. Ajouter une timeline pour suivre les projets dans le temps et afficher les modifications ;
6. Ajouter les rapports PDF avec les éléments détaillés nécessaires pour les développeurs ;

## v0.0.3: 25/02/2022

* Correction de bug ;
* Tests de reprise de plusieurs gros projets à plus de 10000 défauts ;
* Migration de la page security en OWASP ;

## v0.0.4 - 01/03/2022

* Refactoring de la page projet, création d'une page détails ;
* Mise en place d'un système de sauvegarde pour les données calculées ;
* Correction des bugs ;

## v0.0.5 - 13/03/2022

* Ajout de la page dash, pour afficher des tableaux de suivi ;
* Ajout du tableau de suivi des versions analysées ;
* Ajout de graphique pour suivre l'évolution des violations par type ;
* Ajout du tableau de suivi des violations par modules (webapp - front, api - back et batch) ;
* Ajout du tableau de suivi les violations par sévérité (bloquant, critique et majeur) ;

## v0.0.6 - 18/03/2022

* Corrections Sonarqube ;
* Amélioration de la page de suivi des indicateurs (Dash) ;
  * Affichage de la version de référence ;
  * Affichage des 9 dernières versions ;
* Ajout d'un formulaire d'ajout d'une version à partir des données Sonarqube disponible dans l'historique ;
* Refactoring de la sauvegarde ;
* Refactoring de la méthode d'ajout manuel d'une version ;
* Corrections des bugs ;

## v0.0.7 - 28/03/2022

* Version de recette ;
* Mise à jour de la documentation ;
* Mise à jour de la procédure de démarrage et d'arrêt ;

## v0.0.8 - 29/03/2022

* Corrections et amélioration ;
  * Ajout de la note Z pour les applications n'ayant de hotposts ;
  * Correction typographique ;

## v0.0.9 - 31/03/2022

* Remplacement des favicons et du logo ;
* Correction de l'affichage de la version de référence et des favoris (problème de Boxing du Boolean initial et favori );
* Ajout d'un lien "modification" sur la page de suivi des mesures.
  * Permet de supprimer une version ;
  * Active ou désactive, pour une version, le statut favori ;
  * Active ou désactive, pour une version, le statut référence ;
* Reworking des favoris dans la page home ;
* Correction de l'affichage quand le nombre de violations est >20000 (20k) ;

## v0.0.10 - 02/04/2022

* Correction de l'affichage des notes lorsque l'on importe une version manuellement depuis la fenêtre d'ajout de la page de suivi ;
* Blocage des boutons quand le projet n'est pas choisi dans la page projet ;

## v0.0.11 - 07/04/2022

* Mise en place du socle de génération de rapport PDF ;
* Ajout dans le tableau de suivi des violations par sévérité mineure ;
* Correction de la couleur pour indiquer la version de référence (rouge) ;

## v1.0.0 - 10/04/2022 - Release

* Ajout d'un exemple d'édition pour la page suivi des indicateurs ;
* Amélioration de la page et indication de la sévérité en rouge ou vert en fonction de la tendance.

## v1.1.0 - 24/04/2022 - Release

* Mise à jour du modèle de base de données ;
* Collecte des sévérités par type lors de la collecte, enregistrement dans la table historique, attention le modèle de la table est changé, il faut ajouter les nouveaux attributs (cf. readme);
* Suppression de la table temp_anomalie et réutilisation de la table anomalie, pour enregistrer le détail des sévérités par type ;
* Ajout dans la page du suivi des indicateurs, d'un tableau permettant d'afficher par version, le type d'anomalie par sévérité des trois dernières versions ;
* Correction du problème d'affichage des favoris et de la valeur de référence ;
* Ajout de la date de la version pour les tableaux de suivi ;

## v1.2.0 - 05/05/2022 - Release

* Corrections Sonarqube ;
* Ajustement du niveau de log (debug-->info) ;

## v1.2.1 - 08/05/2022 - Release

* Erreur : "Cannot instantiate interface Psr\Log\LoggerInterface". On n'utilise plus l'instanciation (new LoggerInterface) mais l'injection de dépendance ;
* Erreur de syntaxe dans l'utilisation de littérales dans le contrôleur ApiProjetContrôleur ;
* Suppression des dépendances inutiles dans HomeControleur et ProfilControleur ;
* Correction des fautes et erreur de typographie dans le fichier readme.md ;

## v1.2.2 - 11/05/2022 - Release

* Ajout d'une base vierge pour démarrer ;
* ApiProjectController : Warning: Undefined array key "date_enregistrement" (ligne 1461) ;
* Correction du format de la date : date("m/d/Y) à date("d/m/Y) pour la page dash, profil et home ;
* Correction de l'ordre des colonnes pour le tableau de suivi des sévérités par type ;
* Correction des hotspots corrigés et toujours en base ;
* Correction "Warning: Undefined array key [line]", suppression de l'affectation en double ;
* Correction de la page OWASP (erreurs typographiques lors de refactoring) ;

## v1.2.3 - 12/05/2022 - Release

* Correction du traitement et de l'affichage de failles par module.

## v1.2.4 - 17/05/2022 - Release

* Ajout de nouvelles exceptions sur les modules Backend et Autre ;
* Mise à de la documentation
* Correction du tri sur le tableau des hotspots (page OWASP / Détails).
* Ajout de l'attribut "niveau" à la table hotpsot_details pour gérer le tri ;
* Remplacement du mot "Batch" par "Autre" pour prendre en compte les modules de type "Batch" ou les Autres ;
* Modification des tables : Anomalie, Historique et HotspotDetails pour prendre en compte le changement de l'attribut "batch" par "autre" ;

## v1.2.5 - 24/05/2022 - Release

* Corrections de l'affichage de la note pour les vulnérabilités et du nombre de mauvaises pratiques, du tableau de la fenêtre modale "ajouter une analyse" (BoardController.php et app-dash.sj) ;
* Correction de l'enregistrement des données collectées lors de l'ajout d'une analyse historisée. Ajout des attributs obligatoires dans la requête (facultatifs en version 1.2.0).
* Formatage et nettoyage de toutes les requêtes complexes SQL : `trim(preg_replace("/\s+/u", " ", $sql))` ;
* Remplacement dans la page de suivi des indicateurs des valeurs null enregistrées avec la valeur "-1" par un tiret "-" ;
* Ajout du rechargement de la page de suivi des indicateurs quand on clique sur le bouton "fermer" de la fenêtre "ajouter une analyse" ;
* Correction du problème des valeurs "null" pour les hotsposts (valeurs non calculés sur les anciennes version de sonarqube) ;
* Correction de la date dans le tableau de suivi quand on ajoute une version depuis l'historique (date du jour au lieu de la date de la version) ;

## v1.2.6 - 02/06/2022 - Release

* [Suivi des indicateurs] : Correction de la méthode de suppression d'une version dans la fenêtre modale "Modifier les paramètres" ;

```plaintext
Uncaught PHP Exception Doctrine\DBAL\Exception\SyntaxErrorException:
"An exception occurred while executing a query: SQLSTATE[HY000]:
General error: 1 near "4.1": syntax error"
```

* [Suivi des indicateurs] : Amélioration de l'affichage du graphique (mode responsive) ;
* [Suivi des indicateurs] : Correction des labels tronqués en haut du graphique ;
* [Suivi des indicateurs] : Modification de la méthode d'injection des données pour le graphique ;

## v1.3.0 - 03/07/2022 - RELEASE

* [Page Suivi] : Correction de l'affichage du tableau de suivi des anomalies par type et sévérité ;
* [Page Projet] : Correction - le bouton analyse Owasp est bloqué tant que l'utilisateur n'a pas choisi un projet ;
* [Page Répartition] Correction - "Impossible to access a key ("setup") on a string variable ("NaN")." ;
* [Page Suivi] : Remplacement des valeurs "null" par -1 au lieu de 0 ;
* [Page Suivi] : Amélioration de l'affichage du tableau de suivi par type d'anomalies (i.e. mise en couleur des types) ;
* [Page Suivi] : Prise en compte de la version de référence pour le tableau de suivi des anomalies par type et sévérité ;
* [Page Répartition] : Ajout du tableau de bord par répartition : frontend, backend, autre ;
* [Page Répartition] : Ajout de l'Indice de Confiance ;
* [Page Répartition] : Masquage des tableaux vides (Fiabilité, sécurité et maintenabilité) ;
* [Page Répartition] : Ajout de la notion de set-up pour la gestion des versions collectées dans la table temp_repartition ;
* [Architecture] :Séparation des EntityProviders : **default** pour la base des données agrégées et **secondary** pour la base des données d'analyse ;
* [Architecture] : Reworking des requêtes pour la base temp. Suppression des requêtes avec EntityManager (SQL), ajout des requêtes en utilisant le ManagerRegistry (findBy,etc...) ;
* [Page Répartition] : Amélioration de l'affichage du tableau des sévérités par type de la page "Repartition". Utilisation d'un accordéon pour réduire la taille de la page ;

## v1.4.0 - 06/07/2022 - RELEASE

* [Page Projet] : Modification du pictogramme (SVG) pour le bouton répartition ;
* Modification des appels de web-service Asynchrones (11). Utilisation de la méthode Promise + Callback (await) ;
* [Page Projet] : Modification de la fenêtre modale 'liste des projets'. Ajout des actions suivantes :
  * [V] Sélectionner un projet de la liste et déverrouiller tous les boutons ;
  * [S] Supprimer le projet de la liste des projets déjà analysés ;
  * [C] Lance la collecte des indicateurs sonarqube et le calcul des agrégats ;
  * [R] Lance la restitution des données calculés ;
  * [I] Ouvre la page de suivi des indicateurs ;
  * [O] Ouvre la page du rapport OWASP ;
  * [RM] Ouvre la page de suivi de la répartition par module ;
* [Page Projet] : Ajout de l'attribut "liste" de type boolean à la table "Anomalie" pour gérer l'affichage ou non, du projet, dans la liste des projets déjà analysés.

## v1.5.0 - 12/10/2022 - RELEASE

* [Page_Home] : Gestion des versions ;
  * On vérifie si la version de l'application est dans la table [ma_moulinette] ;
  * On affiche un message  à l'utilisateur si la version de la base est plus ancienne que la version de l'application ;
* [Docker] : Mise à jour de la configuration pour la stack docker ;
* [Symfony] Migration 5.4.4 vers 6.1.4 ;
* [PHP] Migration 8.1.10 ;
* [Securité] Ajout d'un cipher de type legacy pour les accès depuis un serveur NGINX ;
* [Configuration] Remplacement des chemins absolus par des chemins relatifs pour la BD ;
* [Sécurité] Filtrage des connexions par type d'HOST (domaine, IP, RegEx) ;
* [Authentification] Gestion des utilisateurs.
  * Ajout de la table [utilisateur] ;
  * Ajout d'un formulaire de connexion [login] ;
  * Ajout d'un formulaire d'inscription [register];
  * Aout d'un formulaire de bienvenue [welcome], une fois l'inscription réalisée ;
  * Ajout de l'option **remember-me** ;
  * Ajout d'un firewall et des rôles [USER], [GESTIONNAIRE] et [PUBLIC_ACCESS] ;
  * Ajout des icônes **logout** et **dashboard** et **utilisateurs** dans le haut de page ;
* Ajout du module EasyAdmin :
  * Personalisation des pages, du menu et des contrôleurs CRUD ;
  * Ajout des indicateurs et des statistiques globales sur la page Dashboard ;
  * Ajout de la page de gestion des comptes utilsiateurs [ROLE_GESTIONNAIRE] ;
  * Ajout d'un lien vers la page [HOME] ;
* Activation et optimisation du cache opCahe (pour la prod uniquement);
* Refactoring complet de la documentation ;
* [Projet] Ajout de l'événement **Collecte** sur le bouton `C` du menu rapide ;
* [Erreurs] Personnalisation des erreurs HTTP (en production) ;
* [Utilisateur] Ajout du compte admin ;
* [Home] refactoring de la page :
  * Suppression du code JS et HTML pour les composants nécessitant un droits Admin dans sonarqube ;
  * Suppression des appels Asynchronne JS pour afficher le nombre de projet et de profil ;
  * Ajout de la table properties, contenant la nombre de projet, de profil en base et sur le serveur et leur date de modification ;
  * Ajout dans le contôleur du processus de gestion des projets et des profils (anciennenent en JS) ;
  * Amélioration de l'affichage des messages d'alerte. Suppression du statut pour des callout ;
  * Affichage du nombre de projet et de profil en plus/moins ;
  * Correction Sonarqube ;
* [Profil] Correction de l'affichage du statut Actif lors du rechargement de la liste ;
* [Paramétrage] Choix de la fréquence de mise à jour. Par défaut 1 jour ;

## v1.6.0 - 30/11/2022 - Release

* [CSS] Utilisation du référentiel des couleurs Sonarqube pour afficher les notes. Pages concernées : [home], [projet], [owasp], [suivi] et [cosui].
* [Page_COSUI] Ajout d'une page de synthèse de type Comité de Suivi (i.e COSUI) ;
* [CSS] La variation des indicateurs utilise maintenant la class [down] pour marquer une amélioration c'est-à-dire une baisse des signalements, la flèche est verte et pointe vers le bas et [up] pour marquer une augmentation des défauts. La flèche pointe vers le haut sa couleur est rouge ;
* [Page_Projet] Ajout du bouton COSUI ;
* [CSS] Refactorisation des class js- et -enabled pour la page [PROJET] ;
* [Page_Projet] Renommage de la page anomalie.details.html.twig en details.html.twig ;
* [Page_Home] Ajout d'un raccourci pour ouvrir directement la page de suivi ;
* [Page_Suivi] Amélioration de l'information utilisateur de la fenêtre modale d'ajout d'une version ;
* [Page_Suivi] Refactorisation de la page pour prendre en compte le référentiel des couleurs sonar ;
* [Page_Home] Ajout des indicateurs de visibilités ;
* [Page_Suivi] Amélioration de la gestion des codes HTTP (404) ;
* [Page_Dashbord] Ajout du contrôle d'intégrité de la base de données ;
* Mise à jour symfony 6.1.8 ;

## v2.0.0 - 01/01/2023 - RC1

* Mise à jour webpack-5.75.0 et webpack-encore 4.1.2 ;
* Migration symfony 6.2.10 ;
* Migration symfony 7.0 ;
* Migration webpack to AssetMapper ;
* Fix : Définition de la timezone [Europe/Paris] pour la gestion des dates;
* Fix : Correction type attribut hotspot_high varchar(4)-->integer ;
* Fix : Ajustement de la taille des bulles utilisées pour les notes.
* Fix : Correction des deux hotspots -> remplacement de Math.random() par chance() ;
* Fix : Affichage du nombre de règles à la place du language dans le tableau profil ;
* Refactor: mises à jour des commentaires ;
* CC : Externalisation des constantes JS ;
* Code Clean W3C & sonarqube ;
* [Docker] Ajout du fichier docker-compose.yml ;
* [BD] Ajout aux scripts SQLite, des scripts PostgreSQL ;
* [BD] Suppression de la table `tags` ;
* [BD] Ajout des "tags" et de la "visibilité" dans la table `liste_projet` ;
* [Page_Home] Correction de l'affichage NaN pour les projets de type privé quand la table est vide ;
* [Page_Home] Ajout de la vue **Projet** pour l'affichage des projets favoris, de la vue **Version** pour l'affichage des versions favorites et de vue **Vide** utiliée quand l'utilisateur a désactivé l'affichage des favoris ;
* [Page_Portefeuille] Initialise la liste des projets à "Aucun projet" quand la table des projets est vide ;
* [EasyAdmin] Ajout d'un **listner** pour afficher les messages utilisateur pour les évenements des class **equipe**, **portefeuille** et **batch** ;
* [EasyAdmin] Ajout d'un **Validator** pour la contrainte d'unicité sur l'attribut `titre` ;
* [EasyAdmin] Ajout d'un controller CRUD pour gérer les **équipes** ;
* [EasyAdmin] Ajout d'un controller CRUD pour gérer les **portefeuilles** de projets ;
* [EasyAdmin] Ajout d'un controller CRUD pour gérer les **batch** ;
* [Header] Ajout d'un lien pour l'accès à la page de suivi des batch ;
* [Security] Ajout du ROLE_BATCH avec héritage du ROLE_COLLECTE et ROLE_UTILISATEUR ;
* [Security] Ajout du ROLE_COLLECTE avec héritage du ROLE_UTILISATEUR ;
* [Page_Projet] Ajout du nombre de version de type "autre" ;
* [Page_Projet] Suppression de l'icône animée lors de la collecte ;
* [Page_Projet] Correction alignement de la colone type du tableau de présentation de la dette technique ;
* [Page_Projet] Ajout du projet par défaut dans localStorage en marque page ;
* [Page_Projet] Collecte des TODO ;
* [Page_Projet] Limiter la liste des projets aux projets d'une équipe ;
* [Page_Traitement] Ajout de la page de suivi des traitements automatique ;
* [Traitement] Ajout d'un processus automatique et manuel pour la collecte des indicateurs sonarqube ;
* [Page_Traitement] Ajout d'un bouton d'affichage du journal d'execution du batch ;
* [Tests_Unitaires] OK (251 tests, 2865 assertions) ;
* [Page_Profil] Amélioration de la page profil pour mobile ;
* [Page_Profil] Consultation des changements par profil ;
* [Page_Profil] Suppression du bouton de rafraichissement de la liste des profils ;
* [Page_Profil_Details] Affichage des changements sur les règles des profils qualités ;
* [Page_Profil] Prise en compte du rôle `Gestionnaire` pour mettre à jour la liste des référentiels ;
* [Page_Suivi_Activité] Ajout de la page de suivi de l'activité Sonarqube ;
* [Page_Inscription] Renforcement de la sécurité. Ajout de la propriété hash_property_path.
* [Page_Inscription] Ajout du controle de la saisie du mot de passe.
* [Page_Inscription] Ajout de l'indicateur de qualité du mot de passe.
* [Page_Footer] Refonte du footer pour prendre en compte les liens |Plan du site | accéssibilité | Mentions légales | Données personnelles.
* [Page_Home] Vérification des droits pour mettre à jour la table des projets et celles des profils qualités.
* [Page_Login] Ajout de l'option voir mon mot de passe ;
* [Page_Login] Ajout, je veux changer mon mot de passe ;
* [Page_Login] TODO : Ajout, j'ai oublié mon mot de passe, aider-moi :)
* [Composant_Menu] Ajout du nom de l'utilisateur ;
* [Composant_Menu] Ajout des informations utilisateurs utiles ;
