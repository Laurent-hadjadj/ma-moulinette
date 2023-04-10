# Ma-Moulinette en images

![Ma-Moulinette](/documentation/ressources/home-000.jpg)

## TOC

### Authentification

* [Authentification](/documentation/authentification.md)
* [Inscription](/documentation/inscription.md)
* [Bienvenue]((/documentation/bienvenue.md))
* [Gestion des utilisateurs](utilisateur.md)

### Ma-Moulinette

* [Accueil](/documentation/accueil.md)
* [Profil-details](/documentation/profil-details.md)
* [Profil](/documentation/profil.md)
* [**Projet**](/documentation/projet.md)
* [OWASP](/documentation/owasp.md)
* [Suivi](/documentation/suivi.md)
* [COSUI](/documentation/cosui.md)
* [Répartition détaillée](/documentation/repartition_details.md)

## Page Projet

La page projet permet, une fois sélectionné un projet, la collecte des données et/ou l'affichage des résultats de la dernière collecte.

![projet](/documentation/ressources/projet-000.jpg)

Dans le sélecteur des projets, je saisis les trois premières lettres de mon projet.
![projet](/documentation/ressources/projet-001.jpg)

Je choisis mon projet à partie du référentiel local.
![projet](/documentation/ressources/projet-001b.jpg)

* [x] je lance la collecte.
* [X] j'affiche les résultats.

`Note :` Dans le journal d'activités, je vérifie que l'étape `13` est terminée.

![projet](/documentation/ressources/projet-002.jpg)

Lorsque je clique sur le bouton, **Afficher les résultats** l'ensemble des indicateurs collecté et calculé est affiché.

![projet](/documentation/ressources/projet-003.jpg)

**Note** : Dans la version `2.0.0`, le bloc **type de version** a été modifié pour afficher les versions **autres** que Release ou Snapshop.

![projet](/documentation/ressources/projet-003a.jpg)

**Note** : Dans la version `2.0.0`, le nombre de commentaire **Todo** a été ajouté dans le pannel Informations.

Je peux ensuite enregistrer les indicateurs dans la base locale. Cette base sera utilisée pour le suivi des versions du projet.

**Note** : Dans la version `1.3.0`, un nouveau bouton est disponible.
En effet, le bouton **Répartition par module** permet l'accès à la page de collecte et l'analyse des signalements par type de sévérité. Il permet l'affichage des signalements par module applicatif.

![projet](/documentation/ressources/projet-003b.jpg)

**Note** : Dans la version `1.6.0`, un nouveau bouton est disponible.
À côté du bouton **Tableaux de suivi**, le bouton **Comité de Suivi** a été ajouté.

![projet](/documentation/ressources/projet-003c.jpg)

* [x] je peux afficher la répartition des versions (Release et SNAPSHOT).

![projet](/documentation/ressources/projet-008.jpg)

* [x] je peux afficher la répartition de la dette technique.

![projet](/documentation/ressources/projet-004.jpg)

* [x] je peux afficher le détail des hotspots.

![projet](/documentation/ressources/projet-005.jpg)

* [x] je peux aussi afficher la répartition détaillée des anomalies.

![projet](/documentation/ressources/projet-006.jpg)

En version `2.0.0`, la liste des tags TODO a été ajouté :

* [x] je peux afficher la liste des tags TODO par langages et par fichiers.

![projet](/documentation/ressources/projet-009.jpg)

Et enfin, je peux afficher la liste des projets que j'ai déjà analysés et ceux qui sont favoris :

![projet](/documentation/ressources/projet-007.jpg)

En version `1.4.0`, un bloc d'actions a été ajouté pour permettre de :

* [V] sélectionner un projet de la liste et déverrouiller tous les boutons ;
* [S] supprimer le projet de la liste des projets déjà analysés ;
* [C] lancer la collecte des indicateurs sonarqube et le calcul des agrégats ;
* [R] lancer la restitution des données calculées ;
* [I] ouvrir la page de suivi des indicateurs ;
* [O] ouvrir la page du rapport OWASP ;
* [RM] ouvrir la page de suivi de la répartition par module ;

![projet](/documentation/ressources/projet-007b.jpg)

En version `1.5.0`, l'implémentation de l'action `C` **collecte des données** a été ajouté ainsi que le statut sur les traitements.

![projet](/documentation/ressources/projet-007a.jpg)

Ci-dessous la liste des messages.

* [x] [00] - je dors !!!
* [x] [01] - le choix du projet a été validé.
* [x] [02] - la suppression de la liste est terminée.
* [x] [03] - la collecte est en cours. ...
* [x] [04] - la collecte des données est terminée.
* [x] [05] - l'affichage des résultats est terminé.

Par exemple, à la fin du traitement de l'action `R` :

![projet](/documentation/ressources/projet-007aa.jpg)

## Petits mémos

* [] Si l'on souhaite ne pas utiliser le sélecteur de recherche, il suffit de cliquer sur le bouton **V** pour sélectionner le projet directement depuis la liste.
![projet](/documentation/ressources/projet-007c.jpg)

* [] si l'on souhaite supprimer le projet de la liste, il suffit de cliquer sur le bouton **S**.
![projet](/documentation/ressources/projet-007d.jpg)

* []  si l'on souhaite lancer une collecte pour ce projet directement, il suffit de cliquer sur le bouton **C**.
![projet](/documentation/ressources/projet-007e.jpg)

* [] Si l'on souhaite afficher les résultats de la dernière collecte pour ce projet, il suffit de cliquer sur le bouton **R**.
![projet](/documentation/ressources/projet-007f.jpg)

* [] Si l'on souhaite ouvrir la page de suivi pour le projet, il suffit de cliquer sur le bouton **I**.
![projet](/documentation/ressources/projet-007g.jpg)

* []  si l'on souhaite ouvrir la page OWASP pour le projet, il suffit de cliquer sur le bouton **O**.
![projet](/documentation/ressources/projet-007h.jpg)

* []  Si l'on souhaite ouvrir le rapport des signalements par module pour le projet, il suffit de cliquer sur le bouton **RM**.
![projet](/documentation/ressources/projet-007i.jpg)

-**-- FIN --**-

[Retour au menu principal](/README.md)
