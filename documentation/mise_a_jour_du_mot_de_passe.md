# Mise à jour du mot de passe

![Ma-Moulinette](/documentation/ressources/home-000.jpg)


## Objectif

La page de mise à jour du mot de passe, répond à deux (2) objectifs :
- [x] Forcer le changement du mot de passe à la premiere connexion avec le compte `admin`, i.e. le mot de passe étant publique, il convient de le changer ;
- [ ] Permettre à un utilisateur de mettre à jour son mot de passe à tout moment ;

## Mise à jour du mot de passe

Pour mettre à jour son mot de passe, il convient préalabrement d'être authentifié. Pour le compte `admin`, une redicrection automatique est utilisée pour permettre à l'utilisateur de merttre à jour le mot de passe par défaut.

Pour tout les autres utilisateurs, il suffira de cliquer sur le menu Utilisateur pour selectionner l'option détails.

## Mise à jour du mot de passe Admin

> A la première connexion avec le compte **admin**.

![reset](/documentation/ressources/reset-001.jpg)

Le formulaire de mise à jour est constitué des éléments suivants :

1. Le compte utilisateur est affiché. Il n'est pas modifiable.
2. L'ancien mot de passe. L'utilisateur doit re-saisir son mot de passe.
3. Le nouveau mot de passe. L'utilisateur doit saisir son nouveau mot de passe.
4. La resaissie du mot de pase. L'utilisateur doit resaisir son nouveau mot de passe.


`Note :` Pour chaque champ de saisie, il est possible d'afficher en clair la valeur saisie.

Tout semble correcte.

![reset](/documentation/ressources/reset-002.jpg)

On affiche en clair les informations en cliquant sur le petit cercle situé à l'interieur du champ de saisie.

![reset](/documentation/ressources/reset-003.jpg)

On valide les données du formulaire en cliquant sur le bouton **valider**.

![reset](/documentation/ressources/reset-004.jpg)

>**Attention**, le nombre de tentative est de 5, après cela, le compte est véroullé. Il faudra mettre à jour manuellement le compte.

Ooups! encore une erreur. Le mot de passe est trop court.

![reset](/documentation/ressources/reset-005.jpg)

Le mot de passe a été changé. Je suis soulagé.

![reset](/documentation/ressources/reset-006.jpg)

-**-- FIN --**-

[Retour au menu principal](/README.md)
