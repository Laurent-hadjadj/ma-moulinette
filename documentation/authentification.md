# Authentification

![Ma-Moulinette](/documentation/ressources/home-000.jpg)

## Processus

Le diagramme ci-dessous présente le processus d'authentification.

![authentification](/documentation/ressources/authentification-000.jpg)

## Connexion

L'authentification est obligatoire. L'utilisation de `ma-moulinette` doit avoir le rôle `UTILISATEUR` et son profil doit être au statut `actif`.

> En d'autres termes, avoir un compte utilisateur qui n'est pas au statut `actif` ne permet pas de se connecter.

![authentification](/documentation/ressources/authentification-001.jpg)

Le formulaire de connexion propose deux (2) options :

1 - Saisir son identifiant, son mot de passe et choisir si l'on souhaite ajouter un cookie de connexion automatique.

2 - Basculer sur le formulaire d'inscription.

`Note :` l'option de **changer son mot de passe** est dans le backlog.

L'identifiant est l'adresse mél de l'utilisateur. L'identifiant doit être unique.

![authentification](/documentation/ressources/authentification-002.jpg)

> Dans la version 2.0.0, un bouton pour afficher le mot de passe a été ajouté.

![authentification](/documentation/ressources/authentification-002b.jpg)

Les messages d'erreurs sont affichés sous le formulaire de connexion. C'est mon choix.

![authentification](/documentation/ressources/authentification-003.jpg)

> Si le nombre de tentatives est supérieur à trois (**3**), le compte sera bloqué pour une durée allant jusqu'à qinze (**15**) minutes

![authentification](/documentation/ressources/authentification-004.jpg)

## Mise à jour du mot de passe

Le mot de passe est obligatoirement mis à jour pour le compte admin, à la première connexion.

Pour les autres utilisateurs, il est possible depuis le menu d'identification de changer son mot de passe.

Une fois authentifié, l'utilisateur est redirigé vers la page d'accueil  de l'application.

![authentification](/documentation/ressources/authentification-005.jpg)

L'identifiant de connexion  affiché sous le menu en haut à droite de la page permet l'accès au menu **Détails**.

![authentification](/documentation/ressources/authentification-006.jpg)

En cliquant sur son identifiant,  le menu laisse apparaître un menu.

![authentification](/documentation/ressources/authentification-007.jpg)

Le bouton mettre à jour son mot de passe, activera la procédure de changement de son mot de passe à la prochaine connexion.

![authentification](/documentation/ressources/authentification-008.jpg)

![authentification](/documentation/ressources/authentification-009.jpg)

-**-- FIN --**-

[Retour au menu principal](/README.md)
