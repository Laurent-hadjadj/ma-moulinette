# Gestion des portefeuilles

![Ma-Moulinette](/assets/images/home/home-000.jpg)

## Backoffice de gestion

La gestion des **portefeuille** s'appuie sur un contrôleur CRUD du bundle EasyAdmin. Les options disponibles sont les suivantes :

* [X] Je peux visualiser la liste des portefeuille de projets ;
* [X] Je peux afficher le détail d'un portefeuille ;
* [X] Je peux modifier le titre et la description d'un portefeuille ;
* [X] Je peux supprimer un portefeuille ;

L'entité `Portefeuille` permet de rassembler le ou les projets dans un groupe. Cette étape est obligatoire pour programmer les traitements de collectes automatiques où manuels.

## Accéder à l'interface d'administration

Il faut avoir le rôle `GESTIONNAIRE` et cliquer sur l'icône utilisateur en haut à droite.

![utilisateur-icône](/assets/images/bo-portefeuille/utilisateur-001.jpg)

Puis, depuis le menu latéral, cliquez sur l'icône **portefeuille**.

![portefeuille-icône](/assets/images/bo-portefeuille/portefeuille-000.jpg)

## Afficher la liste des portefeuille

Par exemple, lors de la première ouverture, la liste des portefeuilles est vide.

![portefeuille-liste](/assets/images/bo-portefeuille/portefeuille-001.jpg)

Pour chaque portefeuille, le tableau affiche les éléments suivants  :

* [ ] Le titre ;
* [ ] L'équipe ;
* [ ] La liste (des projets) ;
* [ ] La date de modification ;
* [ ] La date de création ;

Le menu en fin de ligne permet de (consulter, éditer et supprimer le portefeuille).

![portefeuille-menu](/assets/images/bo-utilisateur/utilisateur-003.jpg)

## Ajouter un nouveau portefeuille

Il suffit de cliquer sur le bouton **Créer portefeuille** en haut à droite de l'écran. En suite, il suffit de saisir le `titre` du portefeuille, de sélectionner une `équipe` d'utilisateur et de choisir dans la `liste` déroulante les projets que l'on veut ajouter.

![portefeuille-ajouter](/assets/images/bo-portefeuille/portefeuille-002.jpg)

Il faudra cliquer sur le bouton `Créer` pour valider le formulaire.

![portefeuille-erreur](/assets/images/bo-portefeuille/portefeuille-003.jpg)

`Attention.` **Le titre** du portefeuille doit être **unique**.

![portefeuille-liste](/assets/images/bo-portefeuille/portefeuille-004.jpg)

`Note :` Un portefeuille a été ajoutée par défaut pour l'équipe de développement de `Ma Moulinette`. Il contient le projet `Ma Moulinette`.

## Consulter l'équipe

Il est possible de :

* [x] **supprimer** l'équipe ;
* [x] **revenir à la liste** ;
* [x] **éditer** l'équipe ;

![portefeuille-consulter](/assets/images/bo-portefeuille/portefeuille-005.jpg)

## Éditez l'équipe

Il est possible de :

* [x] Modifier le titre de l'équipe ;
* [x] Modifier la description ;

![portefeuille-editer](/assets/images/bo-portefeuille/portefeuille-006.jpg)

Pour valider la modification, il suffit de cliquer sur le bouton `Sauvegarder les modifications`.

## Messages utilisateurs

* Ajout d'une nouvelle équipe.

![portefeuille-editer](/assets/images/bo-portefeuille/portefeuille-007.jpg)

* L'équipe existe déjà.

![portefeuille-editer](/assets/images/bo-portefeuille/portefeuille-008.jpg)

* Suppression de l'équipe.

![portefeuille-editer](/assets/images/bo-portefeuille/portefeuille-009.jpg)

* Mise à jour de l'équipe.

![portefeuille-editer](/assets/images/bo-portefeuille/portefeuille-010.jpg)

-**-- FIN --**-

[Retour au menu principal](/index.html)
