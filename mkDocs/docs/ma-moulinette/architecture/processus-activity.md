# Processus de récupération des tâches entre deux dates

## Description Fonctionnelle

L’objectif est de concevoir un processus asynchrone qui permet de récupérer les tâches d’une API SonarQube entre le jour précédent (j-1) et la première date de l’année en cours. Ce processus :

1. Identification des bornes temporelles : détermine les dates disponibles dans l’API (depuis j-1 jusqu’à la première date de l'année).
2. Récupération des données : effectue des appels paginés pour collecter toutes les tâches.
3. Publication dans une queue : traite et publie les données récupérées dans une file de messages pour un traitement ultérieur.
4. Le processus est déclenché par un bouton sur l’interface utilisateur et doit s’exécuter de manière asynchrone pour ne pas bloquer la page ou le backend.

## Description Technique

Architecture du Processus

> Front-End (UI) :

- Un bouton déclenche un appel à un endpoint backend.
- L’état du processus peut être suivi (optionnel) via un système de notifications ou une interface.

> Back-End (API) :

- Le back-end expose un endpoint HTTP pour lancer le processus.
- Le processus récupère les données via des appels asynchrones à l’API SonarQube.
- Les résultats sont envoyés dans une queue Messenger.

> API SonarQube :

- Appels paginés via p=<numero de page> et ps=<taille de page> pour récupérer les tâches.
- Filtrage des données selon les dates de début et de fin spécifiées.

> Messaging System :

- Une queue Messenger reçoit les messages contenant les données récupérées pour un traitement ultérieur.

Pour SonarQube 8 : Limité à 1000 tâches par requête, il faudra prendre les 1000 premières tâches disponibles et utiliser la date de la dernière tâche comme date la plus ancienne accessible.

Pour SonarQube 9 et supérieur :

Récupérer le total de tâches (paging.total).
Calculer le numéro de la dernière page en fonction de la taille de page (ps=1000).
Récupérer cette dernière page et extraire la date de la dernière tâche, qui correspond à la date la plus ancienne.
Une fois cette date identifiée, on peut lancer un batch pour récupérer les tâches, jour par jour, jusqu'à J-1, et publier chaque tâche dans une queue Messenger.
