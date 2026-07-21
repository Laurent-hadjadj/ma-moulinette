# 🏭 Commandes Prod

Les 8 commandes de `src/Command/Prod/`. 5 d'entre elles tournent automatiquement via le cron `cron-ma-moulinette` (Supercronic) — voir [`docker/etc/cron/ma-moulinette`](../developpement/gitlab-ci.md).

## ⏱️ `app:dependency-check:process`

**Objectif** : worker qui traite par lots la file d'ingestion des rapports OWASP DependencyCheck (table `dc_processing_queue`) — réclame des lignes en attente, décompresse/parse le JSON, délègue l'ingestion complète.

| Option | Type | Défaut | Description |
| --- | --- | --- | --- |
| `--batch` / `-b` | int | `5` | Nombre de rapports traités par batch |
| `--max-batches` / `-m` | int | `1` | Nombre de batches consécutifs avant de sortir |
| `--watch` / `-w` | int | — | **Dev uniquement** : boucle infinie, pause de N secondes (min. 5s) entre chaque tick |

```bash
php bin/console app:dependency-check:process --batch=10
```

**Impact** : 🟡 Modification (INSERT/UPDATE scans et findings, mise à jour du statut de la queue).

**Invocation** : cron, **toutes les minutes** — `--batch=5 --no-interaction --env=prod`.

Réclame aussi les lignes `processing` orphelines (worker mort) plus vieilles que 5 minutes et les repasse en `queued`. Au-delà de 3 tentatives, une ligne échoue définitivement (`status=failed`). `EntityManager::clear()` + `gc_collect_cycles()` après chaque rapport pour limiter la consommation mémoire.

!!! note "✅ Correction"
    La commande retournait toujours `Command::SUCCESS`, même si des rapports individuels étaient passés en `failed` définitif après 3 tentatives.
    Elle retourne désormais `Command::FAILURE` dès qu'au moins un rapport a échoué définitivement sur l'exécution — sans effet sur la planification cron (Supercronic ne fait rien de spécial sur un code non nul, il relance à la minute suivante quoi qu'il arrive), seule la visibilité du log change (`docker logs cron-ma-moulinette`).

## 🧹 `app:dependency-check:purge`

**Objectif** : purge les lignes terminées (`done` ou `failed`) de la file OWASP DependencyCheck plus anciennes que N jours. Ne touche jamais les lignes `queued` ni `processing` (celles-ci relèvent de `app:dependency-check:process`).

| Option | Type | Défaut | Description |
| --- | --- | --- | --- |
| `--days` / `-d` | int | `30` | Rétention en jours |
| `--dry-run` | flag | — | Affiche uniquement le décompte, sans suppression |

```bash
php bin/console app:dependency-check:purge --days=7 --dry-run
```

**Impact** : 🔴 Destructive (`DELETE` réel), avec dry-run.

**Invocation** : cron, **quotidien à 3h00** — `--days=30 --no-interaction --env=prod`.

Affiche systématiquement (dry-run ou non) un décompte par statut (`queued`/`processing`/`done`/`failed`) après exécution — utile pour surveiller la santé de la file à chaque passage.

## 🏷️ `app:sonar:update-tags`

**Objectif** : met à jour les tags SonarQube de tous les projets selon leur groupe de permissions (ou le tag `archive` pour le groupe "Archive"), et sauvegarde le mapping projet → groupe dans `var/mapping_projets_groupes.json`.

| Option | Type | Défaut | Description |
| --- | --- | --- | --- |
| `--url` | string | — | URL du serveur SonarQube (obligatoire) |
| `--token` | string | — | Token d'accès (alternative à `--login`/`--password`) |
| `--login` | string | — | Compte utilisateur (alternative à `--token`) |
| `--password` | string | — | Mot de passe associé à `--login` |
| `--dry-run` | flag | — | Simulation — les lectures (projets, groupes) restent réelles, seul l'appel d'écriture du tag est simulé |
| `--debug` | flag | — | Détail des appels API |

```bash
php bin/console app:sonar:update-tags --url="https://sonar.exemple.com" --token="token sonarQube" --dry-run
```

**Impact** : 🟡 Modification (côté serveur SonarQube distant).

**Invocation** : cron, **quotidien à 0h30** — `--url=${SONAR_URL} --token=${SONAR_TOKEN} --no-interaction --env=prod`.

La détection du "bon" groupe SonarQube pour un projet repose sur un critère précis : permissions strictement égales à `{codeviewer, securityhotspotadmin, user}` **et** description du groupe contenant un marqueur texte (`"2021"` par défaut) — une convention de nommage des groupes SonarQube.
Les deux critères sont paramétrables dans `config/packages/sonar_tags.yaml` (`sonar_tags.group_permissions`, `sonar_tags.group_description_marker`), sans avoir à modifier le code PHP si cette convention évolue (ex. nouveaux groupes "2025").

## 🔁 `app:collecte:run`

**Objectif** : déclenche les traitements automatiques Ma-Moulinette en appelant l'API publique de l'application (récupère la liste des traitements non démarrés, puis démarre chacun). Pensé pour être piloté par un ordonnanceur externe via son code de retour.

| Option | Type | Défaut | Description |
| --- | --- | --- | --- |
| `--url` | string | — | URL de l'application Ma-Moulinette (obligatoire) |
| `--token` | string | — | Token d'accès `API_CLIENT_TOKEN` (obligatoire) |
| `--flush` | int | `0` | Nombre max de traitements envoyés par exécution (`0` = pas de limite, tous les traitements disponibles sont envoyés) |
| `--dry-run` | flag | — | Simulation — le démarrage est simulé, mais la récupération de la liste reste un appel réel |
| `--debug` | flag | — | Détail des appels API |

```bash
php bin/console app:collecte:run --url="https://ma.moulinette.exemple.fr" --token="..." --flush=5 --dry-run
```

**Impact** : 🟣 Hybride (effet de bord distant réel côté API Ma-Moulinette).

**Invocation** : cron, **quotidien à 1h30** — `--url=${MAMOUL_URL} --token=${MAMOUL_TOKEN} --no-interaction --env=prod`, sans `--flush` (tous les traitements disponibles sont donc envoyés chaque nuit).

Configuration TLS/proxy des appels sortants via les variables `MAMOUL_CLI_*` (voir `docker/.env.template`) — vérification du certificat activée par défaut.

## 📅 `app:activity:collecte`

**Objectif** : collecte les tâches SonarQube CE "Activity" (historique des analyses lancées) via l'API `/api/ce/activity`, avec rattrapage automatique multi-fenêtres depuis la dernière date connue en base jusqu'à hier.

| Option | Type | Défaut | Description |
| --- | --- | --- | --- |
| `--from-date` | date `Y-m-d` | — | Début de plage explicite (à utiliser avec `--to-date`) |
| `--to-date` | date `Y-m-d` | — | Fin de plage explicite |
| `--catch-up-days` | int | `7` | Largeur max de chaque sous-fenêtre de rattrapage |
| `--init-days` | int | `1096` (~3 ans) | Profondeur du backfill si la base est vide |
| `--page-size` | int | `1000` | Taille de page API SonarQube |
| `--dry-run` | flag | — | Affiche les fenêtres calculées sans appeler l'API ni persister |

```bash
php bin/console app:activity:collecte --from-date=2026-05-01 --to-date=2026-05-23
```

**Impact** : 🟣 Hybride (insertion de nouvelles entités `Activity` ; les doublons sur `analyseId` sont ignorés, jamais remplacés).

**Invocation** : cron, **quotidien à 22h00**, sans option (mode automatique/rattrapage).

Pause de 5 secondes entre chaque page lors de la pagination (throttling volontaire). Si le nombre de résultats dépasse 10 000, un avertissement non bloquant invite à relancer avec un `--catch-up-days` plus petit. Retourne `Command::FAILURE` si au moins une fenêtre a échoué — adapté à la supervision cron.

## 🧹 `app:batch-profiling:purge`

**Objectif** : purge les lignes de la table `batch_profiling` (mesures de performance des traitements batch) plus anciennes que N jours, via la fonction PostgreSQL `ma_moulinette.purge_batch_profiling()`.

| Option | Type | Défaut | Description |
| --- | --- | --- | --- |
| `--days` / `-d` | int | `90` | Rétention en jours |
| `--dry-run` | flag | — | Affiche le décompte sans supprimer |

```bash
php bin/console app:batch-profiling:purge --days=30 --dry-run
```

**Impact** : 🔴 Destructive, avec dry-run.

**Invocation** : manuelle uniquement — absente du cron `cron-ma-moulinette` actuel.

!!! note "✅ Fonction SQL désormais appelée"
    La fonction PostgreSQL `purge_batch_profiling()` existait en base sans jamais être appelée par le code applicatif (voir [Profiling](../back-office/profiling.md)). Cette commande, ajoutée le 2026-07-20, est la première à l'exploiter.

## ♻️ `app:dependency-check:reprocess`

**Objectif** : re-traite manuellement un scan DependencyCheck déjà ingéré — le supprime puis le ré-ingère depuis le payload brut conservé en file, sans avoir à re-uploader le rapport JSON original. Utile pour appliquer rétroactivement une évolution du format d'ingestion.

| Argument/Option | Type | Description |
| --- | --- | --- |
| `ulid` (argument) | string | ULID de la ligne de queue à re-traiter |
| `--force` / `-f` | flag | Confirme la suppression du scan existant (obligatoire si un scan existe déjà et que `--dry-run` n'est pas utilisé) |
| `--dry-run` | flag | Affiche ce qui serait fait sans rien modifier |

```bash
php bin/console app:dependency-check:reprocess 01HZA3KGM4XYZ... --dry-run
php bin/console app:dependency-check:reprocess 01HZA3KGM4XYZ... --force
```

**Impact** : 🔴 Destructive (`DELETE` en cascade des findings liés) puis modification (ré-insertion), avec dry-run et garde-fou `--force` explicite.

**Invocation** : manuelle uniquement — voir [Exploitation DependencyCheck](../dependency-check/exploitation.md).

Le payload n'est disponible en file que pendant la période de rétention gérée par `app:dependency-check:purge` (30 jours par défaut) : passé ce délai, la commande échoue proprement avec un message explicite.

!!! caution "⚠️ Pas de transaction englobant suppression et ré-ingestion"
    La suppression de l'ancien scan est validée avant que la ré-ingestion ne soit tentée. Si la ré-ingestion échoue après coup, l'ancien scan est déjà supprimé et aucun nouveau n'a été créé — le payload restant en file, il suffit de relancer la commande pour recréer le scan.

## 📊 `app:sonar:compare-metrics`

**Objectif** : compare deux jeux de métriques SonarQube (fichiers JSON exportés depuis deux versions de SonarQube) et produit un rapport de différences (ajouts, suppressions, modifications) avec un score d'impact pondéré — utile lors d'une montée de version SonarQube.

| Argument/Option | Type | Défaut | Description |
| --- | --- | --- | --- |
| `source` (argument) | string | — | Fichier JSON ou nom de version source |
| `target` (argument) | string | — | Fichier JSON ou nom de version cible |
| `--dir` | string | `./bin/metrics/` | Répertoire des fichiers JSON si `source`/`target` ne sont pas des chemins directs |
| `--ignore-id` | flag | — | Ignore le champ `id` dans la comparaison |
| `--sonar-query-only` | flag | — | N'affiche que les requêtes API SonarQube reconstituées |
| `--sonar-project-key` | string | — | `maven_key` injectée dans les requêtes de `--sonar-query-only` |
| `--summary-only` | flag | — | N'affiche qu'un résumé (utiliser avec `--format`) |
| `--min-impact` | int | `0` | Filtre les métriques modifiées sous ce seuil d'impact |
| `--format` | string | `cli` | Format de sortie pour `--summary-only` (`cli`\|`md`\|`html`\|`json`) |

```bash
php bin/console app:sonar:compare-metrics metrics_8.9.9.json metrics_9.9.8.json --summary-only --format=md
```

**Impact** : 🔵 Lecture seule (fichiers JSON locaux uniquement, aucun accès base de données).

**Invocation** : manuelle uniquement — aucune référence dans le cron ni dans `.gitlab-ci.yml`.

!!! caution "⚠️ `--summary-only` sans `--format` échoue"
    `--format` vaut `cli` par défaut, un format non géré par `--summary-only` (qui n'accepte que `md`/`html`/`json`) — un simple `--summary-only` sans préciser `--format` retourne donc systématiquement une erreur.
    Toujours combiner les deux options.

## 📚 Pour aller plus loin

- [Vue d'ensemble des commandes](index.md)
- [Commandes Maintenance](maintenance.md)
- [Commandes Dev](dev.md)
- [Exploitation DependencyCheck](../dependency-check/exploitation.md)

-**-- FIN --**-

[Retour au menu principal](/index.html)
