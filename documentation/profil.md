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

## Page profil

Cette page affiche la liste des référentiel disponible sur le serveur sonarqube.

![profil](/documentation/ressources/profil-000.jpg)

`Evolutions 2.0.0`

* [x] Suppression du bouton de mise à jour du référentiel des profils. Maintenant la liste des référéentiels est à jour à chaque fois que l'on ouvre la page profil.
* [x] Ajout de la colonne `Outil` et de l'icône pour afficher le détail des changements pour le profil.
* [x] Suppression de la date dans la légende du tableau.

`Rappel :` Le nom du profil est unique, i.e `Ma petite Entreprise v1.0.0`. Il est défini dans le fichier `.env` par la propriété `SONAR_PROFILES`.

```plaintext
SONAR_PROFILES="Ma petite Entreprise V1.0.0"
```

La page affiche la liste des profils disponible et pour chacun :

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
