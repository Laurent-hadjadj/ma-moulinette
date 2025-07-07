# Traitement quotidien de récupération des tâches d'Activité SonarQube

## Exigences

- [x] __Découplage__ : Utiliser Symfony Messenger et RabbitMQ permet de séparer l’exécution planifiée (Scheduler) du traitement réel (Handler). Si le traitement échoue ou prend du temps, il ne bloque pas la planification.
- [x] __Scalabilité__ : Avec RabbitMQ, tu peux facilement gérer un volume plus élevé en ajoutant des workers pour traiter les messages en parallèle.
- [x] __Tolérance aux pannes__ : Les messages non traités sont conservés dans RabbitMQ jusqu’à ce qu’un consommateur soit disponible.
- [x] __Réutilisation__ : Tu peux réutiliser les Handlers pour d’autres tâches similaires sans réécrire toute la logique.

## Schéma Résumé

1. `Scheduler Symfony` (22h00) → Crée un message contenant la plage de dates.
2. `Symfony Messenger` → Envoie le message à RabbitMQ.
3. `RabbitMQ` → Gère la file de messages.
4. `MessageHandler` → Consomme le message, appelle l’API, et traite les résultats.

## Processus Fonctionnel

> Tâche Planifiée (Scheduler Symfony) :

- Une tâche planifiée à 22h00 chaque nuit envoie un message avec les dates à J-1.
- Cela se fait via le Scheduler Symfony en utilisant une tâche CRON.

> Message Créé :

- Le Scheduler génère un message (via Symfony Messenger) contenant les informations nécessaires : la date de début et de fin pour la veille.
- Le message est envoyé dans une file RabbitMQ pour traitement.

> MessageHandler pour Consommer le Message :

- Un Handler traite ce message. Il récupère les dates, appelle l’API SonarQube (via un client HTTP Symfony), et traite les réponses (codes HTTP, données renvoyées).
- En cas d’erreur, il peut réessayer ou stocker les messages échoués dans une file dédiée.

> Utilisation de RabbitMQ :

- RabbitMQ est utilisé comme file d’attente pour permettre un traitement asynchrone. Cela permet de découpler la création du message (par le scheduler) de son traitement (par le handler).
- Si besoin, RabbitMQ peut aussi permettre de paralléliser le traitement.

## Architecture Technique

> Étape 1 : Le Scheduler Symfony

- Le Scheduler Symfony `SonarTaskSchedule` exécute une tâche planifiée chaque jour à 22h00. Cette tâche génère un message Symfony Messenger avec les données nécessaires (par exemple, les dates de J-1).
- Le message est envoyé dans RabbitMQ.
- Pour tester le scheduler :  `php bin/console scheduler:run --dry-run`

> Étape 2 : Le Message Symfony Messenger

- Le message `ApiCallMessage` contient les informations nécessaires pour l’appel API (dates J-1, etc.).
- Le message est envoyé dans une file RabbitMQ, où il sera consommé par un handler.

> Étape 3 : Le MessageHandler

- Le Handler consomme le message.
- Il utilise un client HTTP Symfony (HttpClient) pour appeler l’API SonarQube.
- Il traite les réponses et gère les erreurs (codes HTTP).
- Les tâches récupérées sont éventuellement enregistrées dans la base de données ou traitées immédiatement.

> Étape 4 : RabbitMQ

- RabbitMQ gère les files de messages. Si le handler est indisponible ou que le traitement échoue, RabbitMQ garde le message en attente ou le redirige vers une file d’échec.

> Propriétés de la queue

| Colonne | Valeur | Explication |
|---|---|---|
| source_name | activity_exchange | C'est le nom de l'exchange qui envoie les messages vers la queue. |
| source_kind | exchange | Cela indique que la source est un exchange. |
| destination_name | activity_queue | C'est le nom de la queue qui reçoit les messages. |
| destination_kind | queue | Cela confirme que la destination est une queue. |
| routing_key | activity_routing_key | La clé de routage utilisée pour envoyer les messages vers cette queue via l'exchange. |
| arguments | [] | Aucun argument supplémentaire n'est configuré pour le binding entre l'exchange et la queue. |

> démmarage des processus

- [x] php bin/console messenger:consume scheduler_sonar_task --memory-limit=128M
- [x] php bin/console messenger:consume async --time-limit=3600

Attendez les logs suivants :

- [app] SonarTaskSchedule exécuté → Confirmation que le scheduler fonctionne.
- [app] ApiCallHandler reçu → Confirmation que le handler traite correctement le message.

- [TASK HANDLER] Traitement de la tâche → Le handler a reçu une tâche.
- [TASK HANDLER] Tâche enregistrée dans la table activity → Une tâche a été insérée avec succès.
Commandes pour lancer le Worker une seule fois :

```bash
php bin/console messenger:consume scheduler_sonar_task --time-limit=0 --memory-limit=128M
```

- `--time-limit=0` : Laisse le Worker tourner indéfiniment.
- `--memory-limit=128M` : Redémarre automatiquement le Worker si la mémoire dépasse cette limite (optionnel).

```batch
php bin/console messenger:consume activity_queue --time-limit=3600 --memory-limit=128M --limit=10
```
