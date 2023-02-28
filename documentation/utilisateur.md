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

* [x] admin, avec le rôle `Gestionnaire` et le statut `Actif`. Il est l'utilisateur par défaut.
* [ ] Aurélie, une utilisatrice de tests.
* [ ] Josh, un utilisateur de tests.
* [ ] Emma, une utilisateur de tests.

Les utilisateurs Aurélie, Josh et Emma ont pour rôle **GESTIONNAIRE**, **UTILISATEUR** et **BATCH**. Les compte de tests sont rattaché à une équipe et  `désactivé` par défaut.

Pour chaque utilisateur, le tableau de suivi affiche les éléments suivants  :

* L'avatar ;
* La personne (Nom + Prénom) ;
* L'adresse de courriel ;
* Son ou ses rôles ;
* L'équipe ;
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

`Aurélie` est notre `Gestionnaire`. C'est elle qui gère son équipe (Josh et Emmal).

![utilisateur](/documentation/ressources/utilisateur-005a.jpg)

Puis il faudra cliquer sur le bouton `Sauvegarder les modifications`.

Et Voilà !

![utilisateur](/documentation/ressources/utilisateur-005aa.jpg)

`Josh` est `Utilisateur`. C'est lui qui travaillera sur les données collectées par l'application `Ma Moulinette`.

![utilisateur](/documentation/ressources/utilisateur-005b.jpg)

Et voilà !
![utilisateur](/documentation/ressources/utilisateur-005bb.jpg)

`Emma` est notre gestionnaire de `Traitement`, c'est elle qui pilote le portefeuille des applications à mettre à jour automatiquement par `Ma Moulinette`. C'est elle qui pourra également lancer un traitement manuellement.

Et voilà !
![utilisateur](/documentation/ressources/utilisateur-005c.jpg)

Et voilà !
![utilisateur](/documentation/ressources/utilisateur-005cc.jpg)

Waouh !!! C'est fait. On a notre équipe de championnes :)

![utilisateur](/documentation/ressources/utilisateur-006.jpg)

-**-- FIN --**-

[Retour au menu principal](/README.md)
