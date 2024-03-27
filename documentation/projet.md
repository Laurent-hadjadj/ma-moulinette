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
* [Préférences](/documentation/preferences.md)

## Évolutions

> En version 2.0.0

* [x] la page a été réorganisée.
* [x] ajout d'une boîte d'information pour les messages d'information et d'alerte.
* [x] il faut avoir au minimum le rôle `COLLECTE` pour lancer la collecte des indicateurs du projet.
* [x] la liste affiche uniquement les projets de l'équipe.
* [x] la fenêtre modale "Liste des projets" a été modifié pour prendre en compte les préférences de l'utilisateur.
* [x] un bookmark pour conserver le dernier projet analysé a été ajouté. Il permet l'accès aux informations sans rechercher le projet.
* [x] le rattachement à une équipe correspondant à un TAG de projet SonarQube est obligatoire.
* [x] le bloc **type de version** a été modifié pour afficher les versions **autres** que Release ou Snapshot.
* [x] le nombre de commentaires de type **Todo** a été ajouté dans le panel Informations.
* [X] Le ratio de dette technique a été ajouté.
* [x] Blocage du processus de collecte en cas d'erreur.

> En version 1.5.0

* [x] ajout de l'action `C` **collecte des données** et ajout du statut de l'action ('je dors',...).

> En version 1.6.0

* [x] ajout du bouton **Comité de Suivi** (COSUI).

> En version 1.4.0

* [x] le bloc d'actions a été ajouté pour permettre un accès rapide aux opérations courantes comme déverrouiller un projet de la liste, lancer une collecte ou afficher la page de suivi.

> En version 1.3.0

* [x] le bouton **Répartition par module** permet l'accès à la page de **collecte** et d'**analyse** des signalements par type de sévérité. Il permet l'affichage des signalements par module applicatif.

## Page Projet

La page projet permet, une fois sélectionné un projet ou avoir activé dans ses préférences l'option **bookmark**, la collecte des données et/ou l'affichage des résultats de la dernière collecte ainsi que l'accès aux différents tableaux de bord permettant le suivi des indicateurs.

![projet](/documentation/ressources/projet-000.jpg)

Si l'utilisateur n'est pas rattaché à une équipe, il ne pourra pas choisir de projet.

![projet](/documentation/ressources/projet-000b.jpg)

L'utilisateur est bien rattaché à une équipe mais, aucun `tag` présent sur le serveur SonarQube ne correspond.

![projet](/documentation/ressources/projet-000c.jpg)

> Depuis la version 2.0.0, il est possible d'activer un **bookmark** dans ses "préféreces". Si le bookmark est activé, le dernier projet affiché sera repris automatiquement et évitera ainsi la recherche depuis le selecteur.

Si vous n'avez pas mis en bookmark le projet, il faudra depuis sélecteur des projets, saisir les trois (3) premières lettres de son projet pour afficher la liste des projets disponibles et correspondant aux critères de recherche.

![projet](/documentation/ressources/projet-001.jpg)

> Depuis la version 2.0.0, seul les projets de l'équipe sont disponible dans le selecteur.

Je choisis mon projet à partir du référentiel local.

![projet](/documentation/ressources/projet-001b.jpg)

* [ ] je lance la collecte pour une nouvelle analyse.
* [X] j'affiche les résultats présents dans la base.

Si l'utilisateur n'a pas le rôle `Collecte` ou `Gestionnaire`, il ne pourra pas lancer la collecte des indicateurs qualités du projet.

![projet](/documentation/ressources/projet-001c.jpg)

`Note :` Dans le journal d'activités, je vérifie que l'étape `13` est terminée.

![projet](/documentation/ressources/projet-002.jpg)

Vous pouvez rencontrer des erreurs lors de la phase de collecte. Par exemple, ici le processus c'est arrêté à l'étape 7.

![projet](/documentation/ressources/projet-014.jpg)

Si vous relancer quand même l'analyse, une message vous indiquera que le processus a été interrompu.

![projet](/documentation/ressources/projet-015.jpg)

Lorsque je clique sur le bouton, **Afficher les résultats**, l'ensemble des indicateurs collecté et calculé est affiché.

Vous pouvez rencontrer des erreurs lors du processus de peinture, par exemple ici une erreur **400** c'est produit lors de la récupération des données.

![projet](/documentation/ressources/projet-016.jpg)

Ici, les données pour le projets n'ont pas été trouvé en base.

![projet](/documentation/ressources/projet-017.jpg)

Si tout vas bien les données sont affichées :)

![projet](/documentation/ressources/projet-003.jpg)

Une fois la collecte terminée, il faut enregistrer les données pour ne pas les perdre. Pour cela, je clique sur le bouton enregistrer.

![projet](/documentation/ressources/projet-003d.jpg)

![projet](/documentation/ressources/projet-003b.jpg)

> Il faut disposer du droit **COLLECTE** pour lancer la commande d'enregistrement.

![projet](/documentation/ressources/projet-003a.jpg)

La page du projet propose plusieurs fenêtres modales pour :

> Afficher la répartition des versions (*Release*, *Snapshot* ou *Autres*)

![projet](/documentation/ressources/projet-008.jpg)

> Afficher la répartition de la dette technique.

![projet](/documentation/ressources/projet-004.jpg)

> Afficher le détail des hotspots.

![projet](/documentation/ressources/projet-005.jpg)

> Afficher la répartition détaillée des anomalies.

![projet](/documentation/ressources/projet-006.jpg)

> Afficher la liste des tags **TODO** par langages et par fichiers.

![projet](/documentation/ressources/projet-009.jpg)

Et enfin, je peux afficher la liste des projets que j'ai déjà analysée et ceux qui sont favoris en cliquant sur le bouton :

![projet](/documentation/ressources/projet-007.jpg)

Les actions suivantes sont disponibles directement depuis cette liste :

* [ ] **V** pour sélectionner le projet et déverrouiller tous les boutons ;
* [ ] **R** pour afficher les résultats ;
* [ ] **S** pour ouvrir la page de suivi des versions ;
* [ ] **C** pour ouvrir la page des indicateurs du COSUI ;
* [ ] **O** pour ouvrir la page du rapport OWASP 2017;
* [ ] **RM** pour ouvrir la page de répartition par module ;

![projet](/documentation/ressources/projet-007a.jpg)

Ci-dessous la liste des messages.

* [x] [00] - je dors !!!
* [x] [01] - le choix du projet a été validé.
* [ ] ~~[02] - la suppression de la liste est terminée.~~
* [ ] ~~[03] - la collecte est en cours.~~
* [ ] ~~[04] - la collecte des données est terminée.~~
* [x] [05] - l'affichage des résultats est terminé.

## Petit mémos

* [ ] Si l'on souhaite ne pas utiliser le sélecteur de recherche, il suffit de cliquer sur le bouton **V** pour sélectionner le projet directement depuis la liste.

![projet](/documentation/ressources/projet-007b.jpg)

* [ ] Si l'on souhaite afficher les résultats de la dernière collecte pour ce projet, il suffit de cliquer sur le bouton **R**.

![projet](/documentation/ressources/projet-007c.jpg)

Le message de fin de traitement est affiché.

![projet](/documentation/ressources/projet-007d.jpg)

* [ ] si l'on souhaite afficher le suivi des versions, il suffit de cliquer sur le bouton **S**.

![projet](/documentation/ressources/projet-007e.jpg)

* [ ]  si l'on souhaite afficher le tableau de suivi COSUI, il suffit de cliquer sur le bouton **C**.

![projet](/documentation/ressources/projet-007f.jpg)

* [ ]  si l'on souhaite ouvrir la page OWASP 2017 pour le projet, il suffit de cliquer sur le bouton **O**.

![projet](/documentation/ressources/projet-007g.jpg)

* [ ]  Si l'on souhaite ouvrir le rapport des signalements par module pour le projet, il suffit de cliquer sur le bouton **RM**.

![projet](/documentation/ressources/projet-007h.jpg)

## Les autres actions

![projet](/documentation/ressources/projet-010.jpg) pour ouvrir la page présentant le rapport d'analyse OWASP 2017 ;

![projet](/documentation/ressources/projet-011.jpg) pour ouvrir la page permettant de réaliser l'analyse du projet et la répartition des signalements par module (Présentation, métier et autres) ;

![projet](/documentation/ressources/projet-012.jpg) pour ouvrir la page de présentation des indicateurs du COmité de SUIvi ;

![projet](/documentation/ressources/projet-013.jpg) pour ouvrir la page de présentation des indicateurs par version ;

-**-- FIN --**-

[Retour au menu principal](/README.md)
