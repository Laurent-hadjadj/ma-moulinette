# Préférences utilisateur

![Ma-Moulinette](../../assets/images/home/home-000.jpg)

La page **Préférences** permet à un utilisateur authentifié de personnaliser son expérience dans Ma-Moulinette. Elle a été introduite en v2.0.0.

## Accès

L'accès s'effectue depuis l'icône ![préférences](../../assets/images/preference/icone-preferences.png) située dans le haut de page. Cette icône est visible pour tout utilisateur authentifié disposant du rôle **ROLE_UTILISATEUR**.

## Évolutions

> En version **2.0.0**

* [x] Ajout du **support des versions de projet en favoris**.
* [x] Ajout du **support de la version** des projets favoris ou des versions favorites d'un même projet.
* [x] **Suppression de la notion de "bookmark"** au profit du **sessionStorage** du navigateur (persistant sur la session de navigation).

## Gestion des favoris

Deux modes d'affichage sont disponibles pour la page d'accueil :

* **Projets favoris** : chaque projet favori affiche sa **version la plus récente** ;
* **Versions favorites** : pour un projet favori, toutes les versions marquées comme favorites sont listées.

> Il n'est pas possible de mixer les deux modes sur la page d'accueil.

Le nombre maximal de favoris affichés est défini par la variable d'environnement `NOMBRE_FAVORI` (10 par défaut).

## Gestion des notifications

L'utilisateur peut choisir d'activer ou non :

* les **messages flash** en haut de page ;
* les **notifications JS** asynchrones (popover discret en bas à droite).

## Bookmark de projet (sessionStorage)

Quand l'option est activée, le **dernier projet consulté** est mémorisé dans le `sessionStorage` du navigateur. À la prochaine ouverture de la page **Projet**, le projet est automatiquement sélectionné, ce qui évite la recherche depuis le sélecteur.

> **Note** : le bookmark est local au navigateur et à l'onglet. Il n'est **pas** synchronisé entre différents appareils.

## Langue d'affichage

La langue peut être basculée entre **Français** (par défaut) et **Anglais**. Les libellés utilisent le composant `symfony/translation`.

## Thème

Trois modes d'affichage sont proposés :

* **Automatique** (suit le thème du système d'exploitation) ;
* **Clair** ;
* **Sombre**.

## Enregistrement

Les préférences sont persistées en base (table `utilisateur`, colonne `preferences` au format JSON). Le bouton **Enregistrer** valide les modifications. Un message flash confirme l'enregistrement.

> [CAPTURE À FAIRE] — capture de la page des préférences une fois la refonte UI terminée.

-**-- FIN --**-

[Retour au menu principal](/index.html)
