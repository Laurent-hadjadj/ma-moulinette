# Gestion des utilisateurs

![Ma-Moulinette](/documentation/ressources/home-000.jpg)

## back-office de gestion

* [Dashboard](/documentation/dashboard.md)
* [**Utilisateur**](/documentation/utilisateur.md)
* [Équipe](/documentation/equipe.md)
* [Portefeuille](/documentation/portefeuille.md)
* [Batch](/documentation/batch.md)

La gestion des utilisateurs s'appuie sur un contrôleur CRUD du bundle EasyAdmin. Les options disponibles sont les suivantes :

* [X] Je peux visualiser la liste des utilisateurs ;
* [X] Je peux afficher le détail de l'utilisateur ;
* [X] Je peux modifier le rôle, l'équipe et le statut de l'utilisateur ;
* [X] Je peux supprimer un utilisateur ;

## Accéder à l'interface d'administration

Il faut avoir le rôle `GESTIONNAIRE` et cliquer sur l'icône utilisateurs en haut à gauche.

![utilisateur](/documentation/ressources/utilisateur-001.jpg)

## Afficher la liste des utilisateurs

![utilisateur](/documentation/ressources/utilisateur-002.jpg)

Par défaut, cinq (5) utilisateurs sont créés.

* [x] admin, avec le rôle `Gestionnaire` et le statut `Actif`. Il est l'utilisateur par défaut.
* [ ] Aurélie, une utilisatrice de tests.
* [ ] Josh, un utilisateur de tests.
* [ ] Emma, une utilisatrice de tests.
* [ ] Nathan, un utilisateur de tests.

Les utilisateurs **Aurélie**, **Josh**, **Emma** et **Nathan** ont pour rôle **GESTIONNAIRE**, **UTILISATEUR**, **BATCH** et **COLLECTE**. Les comptes de tests sont rattachés à une équipe et sont `désactivés` par défaut.

Pour chaque utilisateur, le tableau de suivi affiche les éléments suivants :

* L'avatar ;
* La personne (Nom + Prénom) ;
* L'adresse de courriel ;
* Son ou ses rôles ;
* L'équipe ;
* Son statut, Actif : Oui ou Non ;
* La date de modification ;
* La date de création ;

Le menu en fin de ligne permet de (consulter, éditer et supprimer l'utilisateur) :

![utilisateur](/documentation/ressources/utilisateur-003.jpg)

## Consulter la fiche utilisateur

Il est possible de `supprimer` l'utilisateur, de `revenir à la liste` où de l'`éditer`.

![utilisateur](/documentation/ressources/utilisateur-004.jpg)

## Éditez les paramètres du compte

Il est possible de :

* [ ] Modifier l'adresse de courriel ;
* [ ] Choisir le rôle ou les rôles ;
* [ ] Ajouter l'utilisateur à une équipe ;
* [ ] Activer ou désactiver l'utilisateur ;

![utilisateur](/documentation/ressources/utilisateur-005.jpg)

Par défaut, l'utilisateur n'a pas de rôles, n'est pas rattaché à une équipe et n'est pas actif.

Pour activer le profil d'**Aurélie**, il faudra : choisir un profil, une équipe et cocher la case **Actif**.

> **Aurélie** est notre `Gestionnaire`. C'est elle qui gère son équipe (Josh, Emma et Nathan).

![utilisateur](/documentation/ressources/utilisateur-005a.jpg)

Puis il faudra cliquer sur le bouton `Sauvegarder les modifications`.

Et Voilà !

![utilisateur](/documentation/ressources/utilisateur-005aa.jpg)

> **Emma** est notre gestionnaire de `Traitement`, c'est elle qui pilote le portefeuille des applications à mettre à jour automatiquement par `Ma Moulinette`. C'est elle qui pourra également lancer un traitement manuellement.

Et voilà !
![utilisateur](/documentation/ressources/utilisateur-005c.jpg)

Et voilà !
![utilisateur](/documentation/ressources/utilisateur-005cc.jpg)

> **Nathan** est notre gestionnaire de `Collecte`, c'est lui qui aura la responsabilité de lancer toutes les taches de collectes de données ou de mise à jour de référentiels.

Et voilà !

![utilisateur](/documentation/ressources/utilisateur-005d.jpg)

Et voilà !
![utilisateur](/documentation/ressources/utilisateur-005dd.jpg)

`Josh` est `Utilisateur`. C'est lui qui travaillera sur les données collectées par l'application `Ma Moulinette`.

![utilisateur](/documentation/ressources/utilisateur-005b.jpg)

Et voilà !
![utilisateur](/documentation/ressources/utilisateur-005bb.jpg)

Waouh !!! C'est fait. On a notre équipe de championnes :)

![utilisateur](/documentation/ressources/utilisateur-006.jpg)

-**-- FIN --**-

[Retour au menu principal](/README.md)
