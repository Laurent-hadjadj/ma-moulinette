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
* [**Profil**](/documentation/profil.md)
* [Profil-details](/documentation/profil-details.md)
* [Projet](/documentation/projet.md)
* [OWASP](/documentation/owasp.md)
* [Suivi](/documentation/suivi.md)
* [COSUI](/documentation/cosui.md)
* [Répartition détaillée](/documentation/repartition_details.md)
* [Préférences](/documentation/preferences.md)

## Page profil

Cette page affiche la liste des référentiels disponibles sur le serveur SonarQube.

> Evolutions 2.0.0

* [x] Il faut avoir le Rôle `Gestionnaire` pour mettre à jour la liste des référentiels.
* [x] Ajout de la colonne `Outil` et de l'icône pour afficher le détail des changements pour le profil.
* [x] Suppression de la date dans la légende du tableau.
* [x] La requête de mise à jour de la liste des profils a été migré en POST.

La consultation et/ou la mise à jour de la liste des profils qualités est disponnible en cliquant, depuis la page d'**accueil**, sur le bouton suivant `Profils qualités SonarQube`:

![profil](/documentation/ressources/profil-000.jpg)

La page affiche la liste des profils disponibles et pour chacun :

* Sa version ;
* Le langage ;
* Le nombre de règles ;
* La date de dernière modification ;
* Son statut, **activé** ou **désactivé** ;

![profil](/documentation/ressources/profil-001.jpg)

Plusieurs actions sont disponibles :

* [x] je peux afficher le détails des règles pour une profil, i.e. les règles lié à un langage de programmation ;
* [x] je peux mettre à jour les profils ;
* [x] je peux afficher un graphique représentant la répartition des règles par langage ;

## Mettre à jour le liste des profils

Si la liste des référentiels de règles est vide, un message d'information sera affiché à l'utilisateur.

![profil](/documentation/ressources/profil-002.jpg)

Il faudra dès lors cliquer sur le bouton `Mise à jour de la table`.

![profil](/documentation/ressources/profil-003.jpg)

La requête de mise à jour utilise la méthode `POST`. Si vous essayez de lancer directement la mise à jour avec un appel de type `GET` un message d'erreur sera affiché.

![profil](/documentation/ressources/profil-005.jpg)

> Seul les utilisateurs ayant le rôle **gestionnaire** peuvent réaliser cette action.

Si vous tentez de mettre à jour la liste des profils alors que vous n'avez pas le rôle **gestionnaire**, vous serez averti par un message.

![profil](/documentation/ressources/profil-006.jpg)

Vous devez avoir au moins un profil déclaré sur le serveur SonarQube correspondant à la clé définie dans le fichier de propriétés.

![profil](/documentation/ressources/profil-007.jpg)

> **Rappel**  Le nom du profil est unique, i.e **Ma petite Entreprise v1.0.0**. Il est défini dans le fichier **.env** ou **.env.local.php** par la propriété **SONAR_PROFILES**.

```properties
SONAR_PROFILES="Ma petite Entreprise V1.0.0"
```

![profil](/documentation/ressources/profil-012.jpg)

## Liste des référentiels actifs

Ci-dessous notre référentiel pour les applications `JAVA`, `WEB`, `PHP`, `PYTHON`.

![profil](/documentation/ressources/profil-013.jpg)

## Liste des référentiels non actifs

Le bouton `Afficher les autres profils` ![profil](/documentation/ressources/profil-015.jpg) affiche une fenêtre modal donnant les profils qui ne sont pas actifs pour le language sélectionné.

![profil](/documentation/ressources/profil-014.jpg)

## Répartition des règles par langage

Le bouton `Afficher la répartition des langages` ![profil](/documentation/ressources/profil-009.jpg)
affiche un graphique représentant la répartition des règles par langage.

![profil](/documentation/ressources/profil-011.jpg)

-**-- FIN --**-

[Retour au menu principal](/README.md)
