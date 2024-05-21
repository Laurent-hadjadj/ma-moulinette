# Gestion des traitements (i.e. batch)

![Ma-Moulinette](/documentation/ressources/home-000.jpg)

## back-office de gestion

* [Dashboard](/documentation/dashboard.md)
* [Utilisateur](/documentation/utilisateur.md)
* [Équipe](/documentation/equipe.md)
* [Portefeuille](/documentation/portefeuille.md)
* [**Batch**](/documentation/batch.md)

La gestion des **Traitements** s'appuie sur un contrôleur CRUD du bundle EasyAdmin. Les options disponibles sont les suivantes :

* [X] Je peux visualiser la liste des traitements ;
* [X] Je peux afficher le détail d'un traitement ;
* [X] Je peux modifier le titre et la description d'un traitement ;
* [X] Je peux supprimer un traitement ;

L'entité `Batch` permet de traiter la collecte des indicateurs d'un portefeuille de projets. Un traitement peut-être `Automatique` ou `Manuel`.

Un traitement automatique est un traitement lancé depuis un orchestrateur de tâches, par exemple CRON. Un traitement manuel est un traitement lancé par un utilisateur depuis la page des traitements.

## Accéder à l'interface d'administration

Il faut avoir le rôle `BATCH` et cliquer sur l'icône **Traitements** en haut à droite.

![traitement-icône](/documentation/ressources/bo-traitement-000.jpg)

Il est possible d'accéder à la page de gestion des traitements, depuis le menu latérale en cliquant sur l'icône **traitement**.
![traitement-icône](/documentation/ressources/bo-traitement-001.jpg)

## Afficher la liste des traitements

La liste des traitements est vide par défaut. Nous avons ajouté un traitement pour la collecte des indicateurs de l'application **Ma-Moulinette**.

![portefeuille-liste](/documentation/ressources/bo-traitement-002.jpg)

Pour chaque traitement, le tableau affiche les éléments suivants  :

* [ ] Le traitement est `programmé` ;
* [ ] Le `titre` du traitement ;
* [ ] Le nom du `portefeuille` d'application ;
* [ ] La `description`du traitement ;
* [ ] Le nombre de `projets` du portefeuille ;
* [ ] Le nom du `responsable` du traitement ;
* [ ] La `date de modification` ;
* [ ] La `date d'enregistrement` ;

Le menu en fin de ligne permet, pour chaque traitement, de consulter, éditer et supprimer le traitement.

![utilisateur-menu](/documentation/ressources/utilisateur-003.jpg)

## Ajouter un nouveau traitement

Il suffit de cliquer sur le bouton **Créer Traitement** en haut à droite de l'écran. En suite, il suffira de saisir le `titre` du traitement, de sélectionner le `portefeuille` des projets et de saisir la description du traitement `description`.

![bo-traitement-ajouter](/documentation/ressources/bo-traitement-003.jpg)

Il faudra cliquer sur le bouton `Créer` pour valider le formulaire.

![bo-traitement-liste](/documentation/ressources/bo-traitement-004.jpg)

`Attention` le **titre** du traitement et le **portefeuille** doit être **unique**.

Exemple pour le `titre`.

![bo-traitement-erreur-titre](/documentation/ressources/bo-traitement-005.jpg)

Exemple pour le `portefeuille`.

![bo-traitement-erreur-portefeuille](/documentation/ressources/bo-traitement-005a.jpg)

`Note :` Un portefeuille a été ajouté par défaut pour l'équipe de développement de `Ma Moulinette`. Il contient le projet `Ma Moulinette`.

## Consulter le traitement

Il est possible de :

* [x] **supprimer** le traitement ;
* [x] **revenir à la liste** ;
* [x] **éditer** le traitement ;

![bo-traitement-consulter](/documentation/ressources/bo-traitement-006.jpg)

## Éditez l'équipe

Il est possible de :

* [x] Modifier le `titre` du traitement ;
* [x] Choisir un `portefeuille` ;
* [x] Modifier la `description`;

![bo-traitement-editer](/documentation/ressources/bo-traitement-007.jpg)

Pour valider la modification, il suffira de cliquer sur le bouton `Sauvegarder les modifications`.

## Messages utilisateurs

* Ajout d'un nouveau traitement.

![bo-traitement-message](/documentation/ressources/bo-traitement-008.jpg)

* Le traitement existe déjà. Il n'y a pas de message car on utilise la contrainte d'unicité et non le listner.

* Suppression du traitement.

![bo-traitement](/documentation/ressources/bo-traitement-010.jpg)

* Mise à jour du traitement.

![portefeuille-editer](/documentation/ressources/bo-traitement-011.jpg)

-**-- FIN --**-

[Retour au menu principal](/README.md)
