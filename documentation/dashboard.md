# Gestion des portefeuilles

![Ma-Moulinette](/documentation/ressources/home-000.jpg)

## Backoffice de gestion

* [Dashboard](/documentation/indicateurs.md)
* [Utilisateur](/documentation/utilisateur.md)
* [Equipe](/documentation/equipe.md)
* [**Portefeuille**](/documentation/portefeuille.md)
* [Batch](/documentation/batch.md)

La gestion des **portefeuilles** s'appuie sur un contrôleur CRUD du bundle EasyAdmin. Les options disponibles sont les suivantes :

* [X] Je peux visualiser la liste des portefeuilles de projets ;
* [X] Je peux afficher le détail d'un portefeuilles ;
* [X] Je peux modifier le titre et la description d'un portefeuilles ;
* [X] Je peux supprimer un portefeuilles ;

L'entité `Portefeuille` permet de rassembler le ou les projets dans un groupe. Cette étape est obligatoire pour programmer les traitements de collecte automatiques où manuels.

## Accéder à l'interface d'administration

Il faut avoir le rôle `GESTIONNAIRE` et cliquer sur l'icône utilisateur en haut à droite.

![utilisateur-icône](/documentation/ressources/utilisateur-001.jpg)

Puis, depuis le menu latérale, cliquez sur l'icône **portefeuille**.
![portefeuille-icône](/documentation/ressources/portefeuille-000.jpg)

## Afficher la liste des portefeuilles

Par exemple, lors de la premère ouverture  la liste des portefeuilles est vide.

![portefeuille-liste](/documentation/ressources/portefeuille-001.jpg)

Pour chaque portefeuilles, le tableau affiche les éléments suivants  :

* [ ] Le titre ;
* [ ] L'équipe ;
* [ ] La liste (des projets) ;
* [ ] La date de modification ;
* [ ] La date de création ;

Le menu en fin de ligne permet de (consulter, éditer et supprimer le portefeuilles).

![portefeuille-menu](/documentation/ressources/utilisateur-003.jpg)

## Ajouter un nouveau portefeuilles

Il suffit de cliquez sur le bouton **Créer portefeuille** en haut à droite de l'écran. En suite, il suffit de saisir le `titre` du portefeuille, de sélectionner une `équipe` d'utilisateur et de choisir dans la `liste` déroulante les projets que l'on veut ajouter.

![portefeuille-ajouter](/documentation/ressources/portefeuille-002.jpg)

Il faudra cliquer sur le bouton `Créer` pour valider le formulaire.

![portefeuille-erreur](/documentation/ressources/portefeuille-003.jpg)

`Attention.` **Le titre** du portefeuille  doit être **unique**.

![portefeuille-liste](/documentation/ressources/portefeuille-004.jpg)

`Note :` Un portefeuilles a été ajoutée par défaut pour l'équipe de développement de `Ma Moulinette`. Il contient le projet `Ma Moulinette`.

## Consulter l'équipe

Il est possible de :

* [x] **supprimer** l'équipe ;
* [x] **revenir à la liste** ;
* [x] **éditer** l'équipe ;

![portefeuille-consulter](/documentation/ressources/portefeuille-005.jpg)

## Editez l'équipe

Il est possible de :

* [x] Modifier le titre de l'équipe ;
* [x] Modifier la description ;

![portefeuille-editer](/documentation/ressources/portefeuille-006.jpg)

Pour valider la modification, il suffit de cliquer sur le bouton `Sauvegarder les modifications`.

## Messages utilisateurs

* Ajout d'une nouvelle équipe.
![portefeuille-editer](/documentation/ressources/portefeuille-007.jpg)

* L'équipe existe déjà.
![portefeuille-editer](/documentation/ressources/portefeuille-008.jpg)

* Suppression de l'équipe.
![portefeuille-editer](/documentation/ressources/portefeuille-009.jpg)

* Mise à jour de l'équipe.
![portefeuille-editer](/documentation/ressources/portefeuille-010.jpg)

-**-- FIN --**-

[Retour au menu principal](/README.md)
