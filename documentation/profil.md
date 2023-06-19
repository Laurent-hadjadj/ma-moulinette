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

Cette page affiche la liste des référentiels disponibles sur le serveur sonarqube.

> Evolutions 2.0.0

* [x] Il faut avoir le Rôle `Gestionnaire` pour mettre à jour la liste des référentiels.
* [x] Ajout de la colonne `Outil` et de l'icône pour afficher le détail des changements pour le profil.
* [x] Suppression de la date dans la légende du tableau.

![profil](/documentation/ressources/profil-000a.jpg)

Si la liste des référentiels de règles est vide, il faudra cliquer sur le bouton `Mise à jour de la table`.

![profil](/documentation/ressources/profil-000b.jpg)

> Seul les utiliateurs ayant le rôle **Gestionnaire** peuvent réaliser cette action.

![profil](/documentation/ressources/profil-000c.jpg)

Vous devez avoir au moins un profil déclaré sur le serveur sonarqube correspondant à la clé définie dans le fichier de propriétés.

![profil](/documentation/ressources/profil-000d.jpg)

Une fois la liste mise à jour, le tableau des profils est affiché. Toutefois, il n'est pas possible de consulter le détails d'un profil sans rafraichir la page (F5).

![profil](/documentation/ressources/profil-000e.jpg)

`Rappel :` Le nom du profil est unique, i.e `Ma petite Entreprise v1.0.0`. Il est défini dans le fichier `.env` par la propriété `SONAR_PROFILES`.

```properties
SONAR_PROFILES="Ma petite Entreprise V1.0.0"
```

La page affiche la liste des profils disponibles et pour chacun :

* Sa version ;
* Le langage ;
* Le nombre de règles ;
* La date de dernière modification ;
* Son statut, **activé** ou **désactivé** ;

Le bouton `Afficher la répartition des langages` affiche un graphique représentant la répartition des règles par langage.

![profil](/documentation/ressources/profil-001.jpg)

## Liste des référentiels

Ci-dessous notre référentiel pour les applications `JAVA`, `WEB`, `PHP`, `PYTHON`.

![profil](/documentation/ressources/profil-002.jpg)

## Répartition des règles par langage

Ci-desssous un exemple de réprésentation.

![profil](/documentation/ressources/profil-003.jpg)

-**-- FIN --**-

[Retour au menu principal](/README.md)
