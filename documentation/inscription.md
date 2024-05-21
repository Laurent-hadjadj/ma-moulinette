# Inscription

![Ma-Moulinette](/documentation/ressources/home-000.jpg)

## Présentation du formulaire

L'inscription est obligatoire pour se connecter.

![inscription](/documentation/ressources/registration-001.jpg)

Le formulaire d'inscription propose cinq (6) options :

1. Saisir son nom.
2. Saisir son prénom.
3. Choisir son avatar.
4. Saisir son adresse mél. (cf. spécification).
5. Saisir son mot de passe et le vérifier.
6. Valider son inscription.

> Nouveauté en version 2.0.0

* [x] Gestion et contrôle de l'adresse électronique réécrit ;
* [x] Ajout du bouton "voir mon mot de passe" ;
* [ ] Ajout d'un champ de vérification du mot de passe ;
* [x] Ajout d'une jauge pour mesurer la qualité du mot de passe ;
* [x] Désactivation du bouton "inscription" et amélioration des contrôles de surface.

## Spécifications

L'adresse de courriel respecte la **RFC 3696** pour laquelle :

* [x] la partie gauche de l'adresse doit avoir une longueur de 64 caractères ;
* [x] la partie gauche ne peut pas commencer par le symbole arobase `@` ;
* [X] l'adresse de courriel doit avoir au plus un symbole arobase `@` ;
* [x] le nom de domaine doit être qualifiée et avoir une longueur de 255 caractères maximum.

Le mot de passe doit avoir une longueur minimale de **8** caractères et au maximum de **52** caractères ;

## Choisir son avatar

En cliquant sur le bouton `Changer`, une fenêtre modale apparaît. Il est dès lors possible de choisir parmi **171** icônes présentes.

![inscription](/documentation/ressources/registration-008.jpg)

Cool, non !

![inscription](/documentation/ressources/registration-009.jpg)

## Contrôle de l'adresse

Commencer par un **@** est interdit ;

![inscription](/documentation/ressources/registration-002.jpg)

Mettre un point **.** derrière le symbole **@** n'est pas autorisé.

![inscription](/documentation/ressources/registration-003.jpg)

L'adresse de courriel est correcte.

![inscription](/documentation/ressources/registration-004.jpg)

## Contrôle du mot de passe

Le mot de passe a une longueur inférieure à huit (8) caractères.

![inscription](/documentation/ressources/registration-005.jpg)

Le mot de passe est trop long ! Il doit avoir une longueur de moins de cinquante-trois (53) caractères.

![inscription](/documentation/ressources/registration-006.jpg)

Mon mot de passe est de bonne qualité.

![inscription](/documentation/ressources/registration-010.jpg)

Je peux afficher mon mot de passe en cliquant sur le petit rond situé dans le champ mot de passe.

![inscription](/documentation/ressources/registration-011.jpg)

Je ressaisi mon mot de passe pour activer le bouton *m'inscrire*.

![inscription](/documentation/ressources/registration-012.jpg)

-**-- FIN --**-

[Retour au menu principal](/README.md)
