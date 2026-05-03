# Statistiques

![Ma-Moulinette](../../assets/images/home/home-000.jpg)

La page **Statistiques** offre une vue synthétique de l'utilisation de Ma-Moulinette et de l'activité de collecte. Elle a été introduite en v2.0.0.

## Évolutions

> En version **2.0.0**

* [x] Ajout de la **page principale** de suivi des statistiques concernant les utilisateurs et les projets.
* [x] Ajout du bouton de **mise à jour des statistiques** depuis la table des `events`.
* [x] Ajout de la **page de statistiques utilisateur** (nombre d'utilisateurs, durée de connexion, nombre de collectes, etc.).
* [x] Ajout de la **page de statistiques projet** (nombre de collectes, nombre de projets, nombre de défauts, etc.).

## Accès

La page est accessible depuis le haut de page pour les utilisateurs disposant du rôle **ROLE_GESTIONNAIRE**.

## Page principale

Elle affiche :

* Le **nombre total d'utilisateurs** (actifs / inactifs) ;
* Le **nombre total de projets** présents en base ;
* Le **nombre total de collectes** réalisées (manuelles + batch) ;
* Le **volume de défauts** suivis (bugs, vulnérabilités, hotspots, code smells) ;
* Un bouton **Mettre à jour** pour relancer l'agrégation à partir de la table `events`.

## Statistiques Utilisateur

Cette sous-page affiche par utilisateur :

* Le nombre de **connexions** sur les 30 derniers jours ;
* La **durée cumulée** des sessions ;
* Le nombre de **collectes manuelles** déclenchées ;
* Le dernier **projet consulté** ;
* Son **rôle principal** (`UTILISATEUR`, `GESTIONNAIRE`, etc.).

## Statistiques Projet

Cette sous-page affiche par projet :

* Le nombre de **collectes** réalisées ;
* La **dernière version** analysée ;
* Le nombre de **défauts** détectés (ventilé par type) ;
* Le **ratio de dette technique** ;
* Les utilisateurs ayant participé à la collecte.

## Source des données

Les statistiques sont calculées à partir des tables :

* `utilisateur` ;
* `historique` (versions analysées) ;
* `events` (journal d'actions utilisateurs) ;
* `batch_traitement` (exécutions batch) ;
* `anomalie`, `hotspots` (défauts).

> [CAPTURE À FAIRE] — capture des trois pages (principale, utilisateur, projet).

-**-- FIN --**-

[Retour au menu principal](/index.html)
