# Activité SonarQube

![Ma-Moulinette](../../assets/images/home/home-000.jpg)

La page **Activité** permet de suivre l'historique des tâches d'analyse (`api/ce/activity`) exécutées sur le serveur SonarQube. Elle a été introduite en v2.0.0 (contribution Quentin).

## Évolutions

> En version **2.0.0**

* [x] Ajout de la page de suivi de l'activité SonarQube.
* [x] Collecte asynchrone via **Symfony Messenger** (transport Doctrine).
* [x] Prise en charge des spécificités des versions **SonarQube 8, 9, 10, 2024, 2025 et 2026**.
* [x] Persistance dans la table `activite`.

## Accès

La page est accessible depuis le haut de page pour les utilisateurs disposant du rôle **ROLE_GESTIONNAIRE**.

## Contenu

La page présente :

* Un **tableau** des tâches d'analyse (succès, erreurs, durée moyenne) ;
* Un **graphique temporel** des exécutions par jour ;
* Un **filtre** par projet, par statut (`SUCCESS`, `FAILED`, `PENDING`) et par plage de dates ;
* Un **bouton** pour déclencher manuellement la collecte depuis J-1 jusqu'à la première date de l'année en cours.

## Déclenchement manuel

Le bouton **Collecter l'activité** lance un job **Messenger** qui interroge l'API SonarQube de façon paginée. La progression est visible sur la page **Suivi des traitements** du back-office.

Détails sur le processus : [Processus de récupération des tâches d'activité SonarQube](../architecture/processus-activity.md).

## Collecte automatique

Un **Scheduler Symfony** programme chaque nuit à **22h00** la collecte des tâches du jour précédent. Pour plus de détails : [Traitement quotidien de récupération des tâches d'activité](../architecture/processus-batch-activity.md).

## Captures

> [CAPTURE À FAIRE] — page d'activité avec un exemple de tableau et de graphique.

-**-- FIN --**-

[Retour au menu principal](/index.html)
