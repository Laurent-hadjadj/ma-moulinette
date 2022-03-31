# Liste des évolutions et défauts corrigés

## V0.0.1 : 24/01/2022

### Création du projet sous symfony

* Reprise du projet développé eb HTML/CSS, Javascript + DexieJS ;
* Migration sous symfony 5.4, php 8.1.0 ;
* Migration dexieJs vers sqlite 4 ;
* Migration du code en HTML/JS vers PHP, Twig ;
* Migration vers webpack ;

## v0.0.2: 24/02/2022

### Fonctionalités princiaples

* Création de la page **Home** :
  * Ajout du header/footer ;
  * Ajout du fil d'Arianne ;
  * Ajout d'une zone pour afficher les traces ;
  * Ajout d'un spinner (à revoir) ;
  * Ajout d'un bouton pour lancer la collecte des projets disponibles sur le serveur sonar ;
  * Affichage du nombre de projets disponible ;
  * Ajout d'un bouton pour affciher la liste des profils qualités ;
  * Affichage du nombre de profils disponible ;
  * Ajout d'une zonne Favori, pour afficher les projets favori (partielle) ;
  * Ajout du bouton "mon projet" pour accèder à la page des projets ;

* Création de la page **Projet** :
  * Ajout du header/footer ;
  * Ajout du fil d'Arianne ;
  * Ajout d'une zone pour afficher les traces ;
  * Ajout d'un spinner (à revoir) ;
  * Ajout d'un bouton pour lancer la collecte des données depuis le serveur Sonar ;
  * Ajout d'un liste de selection avec recherche à partir de deux lettres ;
  * Ajout d'un petit-coeur, pour marquer les projets Favoris;
  * Ajout d'un bouton permettant d'afficher l'ensemble des projets présents dans la base locale et ceux en favoris ;
  * Ajout d'un bouton permettant la génération et la restitution des indicateurs (i.e. la peinture) ;
  * Ajout d'une zone **Information** et NoSonar permettant d'afficher les informations générales sur le projet sélectionné :
    * Le nom ;
    * La clé ;
    * Le nombre de verion release ;
    * Le nombre de version snapshot ;
    * Un bouton "Autre" qui permet d'afficher un graphique représentant la distribution des versions ;
    * La version de la dernière application analysée ;
    * La date de la dernière version analysée ;
    * Le nombre d'exclusion de type @supressWarbing et NoSonar ;
  * Ajout de la zone **Projet** permettant l'affichage des informations technique du projet :
    * Le nombre de ligne de code ;
    * La couverture de tests ;
    * Le nombre de tests unitaire ;
    * Le niveau de duplication du code ;
    * Le nombre de défaut ;
    * La répartition de de la dette technique : fiabilité, sécurité et mauvaise pratique ;
    * Le notes pour les indicateurs qualités et la répartitons des hotspots ;
  * Ajout de la zone **Qualité** permettant d'afficher pour chaque indicateur qualité la distridution selon la sévérité ;
  * Ajout d'un bouton permettant d'aller sur la page **Sécurité** OWASP (en cours de migration) ;

* Création de la page **Profil** :
  * Ajout du header/footer ;
  * Ajout du fil d'Arianne ;
  * Affichage du tableau de répartition des règles par langage ;
  * Affichage de la distribution depuis un graphique ;

### Reste à faire

1. Afficher sur le page Home, les projets favori ;
2. Ajouter la page OWASP Top 10 ;
3. Ajouter le Top 10 des violations ;
4. Ajouter un tableau consolidé permettant la distinction entre la partie frontend (présentation) et backend (métier) ;
5. Ajouter une timeline pour suivre les projets dans le temps et afficher les modifications ;
6. Ajouter les rapports PDF avec les élements détaillés nécessaire pour les développeurs ;

## v0.0.3: 25/02/2022

* Correction de bug ;
* Tests de reprise de plusieurs gros projets à plus de 10000 défauts ;
* Migration de la page security en Owasp ;

## v0.0.4 - 01/03/2022

* Refactoring de la page projet, création d'une page détails ;
* Mise en place d'un système de sauvegade pour les données calculées ;
* Correction des bugs ;

## v0.0.5 - 13/03/2022

* Ajout de la page dash, pour afficher des tableaux de suivi ;
* Ajout du tableau de suivi des versions analysés ;
* Ajout de graphique pour suivre l'évolution des violations par type ;
* Ajout du tableau de suivi des violations par modules (webapp - front, api - back et batch ) ;
* Ajout du tableau de suivi les violations par sévérité (bloquant, critique et majeur) ;

## v0.0.6 - 18/03/2022

* Corrections Sonarqube ;
* Amélioration de la page de suivi des indicateurs (Dash) ;
  * Affichage de la version de référence ;
  * Affichage des 9 dernières versions ;
* Ajout d'un formulaire d'ajout d'une version à partir des données Sonarqube disponible dans l'historique ;
* Refactoring de la sauvegarde ;
* Refactoring de la méthode d'jout manuel d'une version ;
* Corrections des bugs ;

## v0.0.7 - 28/03/2022

* Version de recette ;
* Mise à jour de la documentation ;
* Mise à jour de la procédure de démarrage et d'arrêt ;

## v0.0.8 - 29/03/2022

* Corrections et amélioration ;
  * Ajout de la note Z pour les applications n'ayant de hotposts ;
  * Correction typographique ;

## v0.0.9 - 30/03/2022

* Remplacement des favivon et du logo ;
* Correction de l'afficahge de la version de référence et des favoris (problème de Boxing du Boolean);
* Ajout d'un lien "modification" sur la page de suivi des mesures.
