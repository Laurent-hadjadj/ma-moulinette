# ⏱️ Traitement quotidien de récupération des tâches d'activité

!!! note "🐇 Mécanisme entièrement revu depuis la conception initiale"
    Ce processus a d'abord été conçu autour d'un trio Symfony Messenger + RabbitMQ + Scheduler (`ActivityMessage`/`ActivityHandler`/`ProcessTaskHandler` + `ActivityYesterdaySchedule`). Il a été **remplacé par une commande console synchrone unique** (commentaire de la classe : *« remplace le trio Messenger ... + ActivityYesterdaySchedule par une Command synchrone »*), pour simplifier le débogage (plus de queue à inspecter) et permettre un rattrapage automatique. Toute doc antérieure décrivant RabbitMQ pour ce processus est obsolète.

## ⚙️ Commande `app:activity:collecte`

```bash
# Automatique : détecte la dernière date en base, rattrape jusqu'à J-1
php bin/console app:activity:collecte

# Plage explicite
php bin/console app:activity:collecte --from-date=2026-05-01 --to-date=2026-05-23

# Fenêtre de rattrapage et taille de page personnalisées
php bin/console app:activity:collecte --catch-up-days=14 --page-size=500

# Simulation (affiche les fenêtres de traitement sans exécuter)
php bin/console app:activity:collecte --dry-run
```

Sans option, la commande détecte automatiquement la dernière date déjà enregistrée dans la table `activity` et rattrape par fenêtres de 7 jours (par défaut) jusqu'à J-1 — pas besoin de rejouer manuellement les jours manqués après un incident.

## 📅 Planification automatique

La commande est planifiée chaque nuit à **22h00** via **Supercronic**, dans le conteneur `cron-ma-moulinette` (voir [Environnement d'exécution](../architecture/architecture-technique.md#-environnement-dexécution)) :

```cron
0 22 * * * php /var/www/bin/console app:activity:collecte --no-interaction --env=prod
```

Aucun Symfony Scheduler natif n'est utilisé (`src/Schedule.php` reste un squelette vide).

## 📚 Pour aller plus loin

- [Processus de récupération des tâches](processus-activity.md) : logique de pagination selon la version de SonarQube.
- [Page Activité](../application/activite.md) : déclenchement manuel, consultation.

-**-- FIN --**-

[Retour au menu principal](/index.html)
