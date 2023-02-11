# Gestion des utilisateurs

![Ma-Moulinette](/documentation/ressources/home-000.jpg)

## Backoffice de gestion

* [Dashboard](/documentation/indicateurs.md)
* [**Utilisateur**](/documentation/utilisateur.md)
* [Equipe](/documentation/equipe.md)
* [Portefeuille](/documentation/portefeuille.md)
* [Batch](/documentation/batch.md)

La gestion des utilisateurs s'appuie sur un contrôleur CRUD du bundle EasyAdmin. Les options suivantes sont les suivantes :

* [X] Je peux visualiser la liste des utilisateurs ;
* [X] Je peux afficher les détails de l'utilisateur ;
* [X] Je peux modifier le rôle, l'équipe et le statut de l'utilisateur ;
* [X] Je peux supprimer un utilisateur ;

## Accéder à l'interface d'administration

Il faut avoir le rôle `GESTIONNAIRE` et cliquer sur l'icône utilisateurs en haut à gauche.

![utilisateur](/documentation/ressources/utilisateur-001.jpg)

## Afficher la liste des utilisateurs

![utilisateur](/documentation/ressources/utilisateur-002.jpg)

Par défaut quatre (4) utilisateurs sont créés.

* [x] admin, avec le rôle `Gestionnaire` et le statut `Actif`.
* [ ] Aurélie, sans rôles et avec le statut `désactivé`.
* [ ] Adam, sans rôles et avec le statut `désactivé`.
* [ ] Tal, sans rôles et avec le statut.`désactivé`

Pour chaque utilisateur, le tableau de suivi affiche les éléments suivants  :

* L'avatar ;
* La personne (Nom + Prénom) ;
* L'adresse de courriel ;
* L'équipe ;
* Son ou ses rôles ;
* Son statut, Actif ou Pas ;
* La date de modification ;
* La date de création ;

Le menu en fin de ligne permet de (consulter, éditer et supprimer l'utilisateur) :

![utilisateur](/documentation/ressources/utilisateur-003.jpg)

## Consulter la fiche utilisateur

Il est possible de `supprimer` l'utilisateur, de `revenir à la liste` où de l'`éditer`.

![utilisateur](/documentation/ressources/utilisateur-004.jpg)

## Editez les paramètres du compte

Il est possible de :

* [ ] Modifier l'adresse de courriel ;
* [ ] Choisir le rôle ou les rôles ;
* [ ] Ajouter l'utilisateur à une équipe ;
* [ ] Activer ou désactiver l'utilisateur ;

![utilisateur](/documentation/ressources/utilisateur-005.jpg)

Par défaut, l'utilisateur n'a pas de rôles, n'est pas rattaché à une équipe et n'est pas actif.

Pour activer le profil d'**Aurélie**, il faudra : choisir un profil, une équipe et cocher la case **Actif**.

`Aurélie` est notre `Gestionnaire`, c'est elle qui gère l'équipe (Adam et Tal).

![utilisateur](/documentation/ressources/utilisateur-005a.jpg)

Puis il faudra cliquer sur le bouton `Sauvegarder les modifications`.

`Adam` est `Utilisateur`, c'est lui qui travaillera sur les données collectées par l'application `Ma Moulinette`.

![utilisateur](/documentation/ressources/utilisateur-005b.jpg)

`Tal` est notre gestionnaire de `Traitement`, c'est elle qui gérera le portefeuille des applications à mettre à jour automatiquement par `Ma Moulinette`.

Waouh !!! C'est fait. On a notre équipe de championnes :)

![utilisateur](/documentation/ressources/utilisateur-006.jpg)

-**-- FIN --**-

[Retour au menu principal](/README.md)
