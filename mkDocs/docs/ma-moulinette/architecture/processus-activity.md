# 🔄 Processus de récupération des tâches d'activité SonarQube

## 🎯 Objectif fonctionnel

Récupérer, pour un projet donné, l'historique des tâches d'analyse (`api/ce/activity`) entre J-1 et la première date disponible de l'année en cours, puis les persister dans la table `activity` — voir [Page Activité](../application/activite.md).

## 📖 Pagination selon la version de SonarQube

L'API `api/ce/activity` ne se comporte pas de la même façon selon la version du serveur, ce qui impose une stratégie différente pour retrouver la date la plus ancienne accessible :

- **SonarQube 8** : limité à 1000 tâches par requête, sans compteur de total fiable. Il faut récupérer les 1000 premières tâches disponibles et utiliser la date de la dernière comme borne la plus ancienne accessible.
- **SonarQube 9 et supérieur** : le champ `paging.total` est disponible. On calcule le numéro de la dernière page (taille de page `ps=1000`), on la récupère, et la date de la tâche la plus ancienne de cette page devient la borne de départ.

Une fois la borne identifiée, la collecte avance jour par jour jusqu'à J-1.

## ⚙️ Mécanisme d'exécution

Le déclenchement (manuel depuis la page Activité, ou automatique par cron) exécute la commande console `app:activity:collecte` (`ActivityCollecteCommand`), qui appelle l'API SonarQube en pages successives et enregistre directement les résultats — voir [Traitement quotidien](processus-batch-activity.md) pour le détail de la planification automatique.

-**-- FIN --**-

[Retour au menu principal](/index.html)
