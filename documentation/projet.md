# Ma-Moulinette en images

![Ma-Moulinette](/documentation/ressources/home-000.jpg)

## TOC

### Authentification

[Authentification](/documentation/authentification.md)
[Inscription](/documentation/inscription.md)
[Bienvenue]((/documentation/bienvenue.md))
[Gestion des utilisateurs](utilisateur.md)

### Ma-Moulinette

[Accueil](/documentation/accueil.md)
[Projet](/documentation/projet.md)
[OWASP](/documentation/owasp.md)
[Suivi](/documentation/suivi.md)

## Page Projet

La page projet permet, une fois sélectionné un projet, de collecter les données et/ou d'afficher les résultats de la dernière collecte.

Je choisi mon projet à partie du référentiel local.
![projet](/documentation/ressources/projet-001.jpg)

Je lance la collecte et j'affiche les résultats.
![projet](/documentation/ressources/projet-002.jpg)

Je peux ensuite enregistrer les indicateurs dans la base locale. Cette base sera utilisée pour le suivi des versions du projet.

Note : Dans la version `1.3.0`, un nouveau bouton est disponible.
En effet, le bouton "Répartition par module" permet l'accès à la page de collecte et l'analyse des signalements par type de sévérité. Elle permet l'affichage des signalements par module applicatif.

![projet](/documentation/ressources/projet-009.jpg)

Je peux afficher la répartition des versions (Release et SNAPSHOT).

![projet](/documentation/ressources/projet-008.jpg)

Je peux afficher la répartition de la dette technique :

![projet](/documentation/ressources/projet-004.jpg)

Je peux afficher le détail des hotspots :

![projet](/documentation/ressources/projet-005.jpg)

Je peux aussi afficher la répartition détaillée des anomalies :

![projet](/documentation/ressources/projet-006.jpg)

Et je peux afficher la liste des projets que j'ai déjà analysés et ceux qui sont favoris :

![projet](/documentation/ressources/projet-007.jpg)

En version `1.4.0`, un bloc d'actions a été ajouté pour permettre de :

* [V] Sélectionner un projet de la liste et déverrouiller tous les boutons ;
* [S] Supprimer le projet de la liste des projets déjà analysés ;
* [C] Lancer la collecte des indicateurs sonarqube et le calcul des agrégats ;
* [R] Lancer la restitution des données calculés ;
* [I] Ouvrir la page de suivi des indicateurs ;
* [O] Ouvrir la page du rapport OWASP ;
* [RM] Ouvrir la page de suivi de la répartition par module ;

![projet](/documentation/ressources/projet-007b.jpg)

-**-- FIN --**-

[Retour au menu principal](/README.md)
