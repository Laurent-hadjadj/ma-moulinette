# 🏭 Commandes Prod

Les quinze commandes de `src/Command/Prod/`. douze d'entre elles tournent automatiquement via le cron `cron-ma-moulinette` (Supercronic) — voir [`docker/etc/cron/ma-moulinette`](../developpement/gitlab-ci.md).

!!! note "✅ Logger dédié `cron` (2026-10-15)"
    Onze des treize commandes de ce dossier journalisent désormais sur un channel Monolog dédié `cron` (`#[Autowire(service: 'monolog.logger.cron')]` posé sur le `LoggerInterface $logger` du constructeur) plutôt que sur le logger applicatif générique — fichier `var/log/cron.log` en prod (`cron-dev.log` en dev), exclu du canal `app.log`, consultable aussi depuis la page back-office **Journal des logs** (filtre « Cron »). Les deux exceptions restent sur le logger générique : `app:sonar:compare-metrics` (pas de `LoggerInterface` du tout, outil de diagnostic) et `app:dependency-check:reprocess` (outil de reprise manuelle, jamais planifié).

!!! warning "⚠️ `cron.log` absent si la file est vide (corrigé le 2026-11-22)"
    `app:dependency-check:process`, `app:zap:process` et `app:npm-audit:process` (les 3 workers « toutes les minutes ») ne journalisaient rien du tout quand la file à traiter est vide — un `StreamHandler` Monolog ne crée le fichier qu'au premier enregistrement effectivement écrit, donc `cron.log` pouvait rester totalement absent malgré des exécutions réussies chaque minute (constaté sur un environnement sans aucun rapport encore uploadé — Supercronic tournait bien, visible via `docker logs cron-ma-moulinette`, mais rien dans `cron.log`).\
    Un heartbeat `info` « queue vide » est désormais loggé toutes les 15 minutes par ces 3 commandes (aux minutes 00/15/30/45, calcul sans état persisté — pas de flood de `cron.log` une fois les files réellement actives).

## 1️⃣ ⏱️ `app:dependency-check:process`

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
    La commande retournait toujours `Command::SUCCESS`, même si des rapports individuels étaient passés en `failed` définitif après trois tentatives.
    Elle retourne désormais `Command::FAILURE` dès qu'au moins un rapport a échoué définitivement sur l'exécution — sans effet sur la planification cron (Supercronic ne fait rien de spécial sur un code non nul, il relance à la minute suivante quoi qu'il arrive), seule la visibilité du log change (`docker logs cron-ma-moulinette`).

## 2️⃣ 🕷️ `app:zap:process`

**Objectif** : worker qui traite par lots la file d'ingestion des rapports ZAP (DAST) (table `zap_processing_queue`) — même pattern exact que `app:dependency-check:process` ci-dessus (réclame les lignes en attente, décompresse/parse le JSON, délègue l'ingestion complète vers la table `owasp_dast`).

| Option | Type | Défaut | Description |
| --- | --- | --- | --- |
| `--batch` / `-b` | int | `5` | Nombre de rapports traités par batch |
| `--max-batches` / `-m` | int | `1` | Nombre de batches consécutifs avant de sortir |
| `--watch` / `-w` | int | — | **Dev uniquement** : boucle infinie, pause de N secondes (min. 5s) entre chaque tick |

```bash
php bin/console app:zap:process --batch=10
```

**Impact** : 🟡 Modification (INSERT/UPDATE scans, règles, findings et `owasp_dast`, mise à jour du statut de la queue).

**Invocation** : cron, **toutes les minutes** — `--batch=5 --no-interaction --env=prod`.

!!! note "✅ Commande existante mais absente du cron jusqu'au 2026-10-15"
    La commande (phase 1 du chantier ZAP) existait sans être planifiée : les rapports uploadés via `POST /api/secure/zap/upload` restaient en `queued` indéfiniment, faute de worker automatique. Ajoutée à `docker/etc/cron/ma-moulinette` le 2026-10-15, en pair exact avec `app:dependency-check:process`.

## 3️⃣ 🧹 `app:zap:purge`

**Objectif** : purge les lignes terminées (`done` ou `failed`) de la file ZAP (DAST) plus anciennes que N jours. Même logique que `app:dependency-check:purge` ci-dessous — ne touche jamais les lignes `queued` ni `processing`.

| Option | Type | Défaut | Description |
| --- | --- | --- | --- |
| `--days` / `-d` | int | `30` | Rétention en jours |
| `--dry-run` | flag | — | Affiche uniquement le décompte, sans suppression |

```bash
php bin/console app:zap:purge --days=7 --dry-run
```

**Impact** : 🔴 Destructive (`DELETE` réel), avec dry-run.

**Invocation** : manuelle uniquement pour l'instant — pas encore ajoutée au cron (contrairement à son équivalent `app:dependency-check:purge`), à planifier une fois le volume de rapports ZAP réellement observé en prod.

Affiche systématiquement un décompte par statut (`queued`/`processing`/`done`/`failed`) après exécution, comme `app:dependency-check:purge`.

## 4️⃣ 🧹 `app:dependency-check:purge`

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

## 5️⃣ 🏷️ `app:sonar:update-tags`

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

Le groupe SonarQube d'un projet est détecté **directement par son nom**, parmi la liste configurée dans
`sonar_tags.target_groups` : seuls les projets dont l'un des groupes porte **exactement** un des noms de la
liste sont nettoyés puis retagués ; tous les autres sont **ignorés sans aucune modification** (comptés dans
"Ignorés" en fin d'exécution). Un projet archivé (groupe `Archive`) est donc ignoré lui aussi si le
périmètre est restreint, sauf s'il porte encore un groupe de la liste en plus d'`Archive`.

!!! warning "Noms de groupes définis par variable d'environnement, jamais committés"
    `sonar_tags.target_groups` ne contient **aucun nom de groupe en dur** dans `config/packages/sonar_tags.yaml`
    (dépôt GitHub public) : sa valeur est résolue depuis `SONAR_TAGS_TARGET_GROUPS` (liste séparée par des
    virgules, ex. `LOT-1,LOT-2,LOT-3,LOT-4`), à définir uniquement dans `.env.local` (jamais versionné).
    Variable absente ou vide → périmètre vide → **aucun** groupe ne peut jamais matcher : chaque projet
    reçoit alors le tag "aucun" avec un avertissement, rien n'est tagué par groupe (il n'y a plus de
    critère de repli — l'ancienne convention de nommage "2021", permissions+description, est retirée,
    devenue inutile depuis que les groupes cibles sont toujours explicites). Un garde-fou côté commande
    normalise systématiquement une valeur vide à `[]` (le processeur d'environnement `csv` de Symfony
    transforme une chaîne vide en `[""]`, pas en `[]` — sans ce garde-fou, le périmètre serait restreint à
    un groupe nommé "", qui n'existe jamais, avec le même effet pratique mais pour la mauvaise raison).

!!! info "Tag métier additionnel depuis un fichier JSON (`sonar_tags.business_tag_file_candidates`)"
    Après le retag par groupe, un **second tag optionnel** peut être ajouté — pas à la place du tag de
    groupe, les deux cohabitent (ex. `lot-1,adem`), l'API SonarQube remplaçant toute la liste de tags en un
    seul appel. La correspondance maven_key → code applicatif vient d'un fichier JSON (tableau d'objets
    `{"project_key": "...", "code_apli": "..."}`), cherché dans l'ordre des chemins listés dans
    `sonar_tags.business_tag_file_candidates` (`config/packages/sonar_tags.yaml`, par défaut `var/liste_code_appli.json`
    puis `migrations/liste_code_appli.json`) — le premier trouvé est utilisé. Aucun des deux trouvé, ou JSON
    invalide, n'est pas une erreur : l'étape est simplement ignorée (compteur « Tag métier » à 0), le reste
    du traitement (clean + retag par groupe) n'est pas affecté. Le tag métier n'est ajouté que pour les
    projets déjà traités par l'étape précédente (donc toujours dans le périmètre de `sonar_tags.target_groups`
    quand celui-ci est restreint) et présents dans le fichier — les autres entrées du fichier n'ont aucun
    effet.

    ⚠️ **Confidentialité** : ce fichier contient de vraies clés Maven client. `var/` est ignoré par git
    (sûr), mais `migrations/` est versionné et **public** (dépôt GitHub) — `liste_code_appli.json` y est
    exclu par précaution dans `migrations/.gitignore`, mais aucun autre fichier contenant de vraies données
    client ne doit jamais y être committé.

## 6️⃣ 🔁 `app:collecte:run`

**Objectif** : déclenche les traitements automatiques Ma-Moulinette en appelant l'API publique de l'application (récupère la liste des traitements non démarrés, puis démarre chacun). Pensé pour être piloté par un ordonnanceur externe via son code de retour.

| Option | Type | Défaut | Description |
| --- | --- | --- | --- |
| `--url` | string | — | URL de l'application Ma-Moulinette (obligatoire) |
| `--token` | string | — | Token d'accès `API_CLIENT_TOKEN` (obligatoire) |
| `--flush` | int | `0` | Nombre max de traitements envoyés par exécution (`0` = pas de limite, tous les traitements disponibles sont envoyés) |
| `--dry-run` | flag | — | Simulation — le démarrage est simulé, mais la récupération de la liste reste un appel réel |
| `--debug` | flag | — | Détail des appels API |

!!! note "Codes de réponse du jeton"
    Un jeton refusé par le serveur donne un **403** ; un serveur dont `API_CLIENT_TOKEN` n'est pas configuré (vide ou `changeme`) répond **503** : corriger la configuration du serveur, pas la commande.

```bash
php bin/console app:collecte:run --url="https://ma.moulinette.exemple.fr" --token="..." --flush=5 --dry-run
```

**Impact** : 🟣 Hybride (effet de bord distant réel côté API Ma-Moulinette).

**Invocation** : cron, **quotidien à 1h30** — `--url=${MAMOUL_URL} --token=${MAMOUL_TOKEN} --no-interaction --env=prod`, sans `--flush` (tous les traitements disponibles sont donc envoyés chaque nuit).

Configuration TLS/proxy des appels sortants via les variables `MAMOUL_CLI_*` (voir `docker/.env.template`) — vérification du certificat activée par défaut.

## 7️⃣ 📅 `app:activity:collecte`

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

## 8️⃣ 🧹 `app:batch-profiling:purge`

**Objectif** : purge les lignes de la table `batch_profiling` (mesures de performance des traitements batch) plus anciennes que N jours, via la fonction PostgreSQL `ma_moulinette.purge_batch_profiling()`.

| Option | Type | Défaut | Description |
| --- | --- | --- | --- |
| `--days` / `-d` | int | `90` | Rétention en jours |
| `--dry-run` | flag | — | Affiche le décompte sans supprimer |

```bash
php bin/console app:batch-profiling:purge --days=30 --dry-run
```

**Impact** : 🔴 Destructive, avec dry-run.

**Invocation** : cron, **quotidien à 4h00** — `--days=90 --no-interaction --env=prod` (ajoutée au cron le 2026-10-15 ; prévue depuis sa création le 2026-07-20 mais jamais planifiée jusque-là).

!!! note "✅ Fonction SQL désormais appelée"
    La fonction PostgreSQL `purge_batch_profiling()` existait en base sans jamais être appelée par le code applicatif (voir [Profiling](../back-office/profiling.md)). Cette commande, ajoutée le 2026-07-20, est la première à l'exploiter.

## 9️⃣ 🧹 `app:user-role-log:purge`

**Objectif** : purge le journal des modifications de rôle (`user_role_log`) plus ancien que N jours — corrige un écart RGPD (conservation illimitée jusqu'ici, voir [Conformité RGPD](../conformite/rgpd.md) section 13, action 1). La suppression manuelle ligne à ligne reste disponible depuis `/admin/journal-roles` (`ROLE_INTERNAL`).

| Option | Type | Défaut | Description |
| --- | --- | --- | --- |
| `--days` / `-d` | int | `365` (réglable via `USER_ROLE_LOG_RETENTION_DAYS`) | Rétention en jours |
| `--dry-run` | flag | — | Affiche le décompte sans supprimer |

```bash
php bin/console app:user-role-log:purge --days=180 --dry-run
```

**Impact** : 🔴 Destructive, avec dry-run.

**Invocation** : cron, **quotidien à 4h15** — `--days=365 --no-interaction --env=prod`.

## 🔟 🧹 `app:activity:purge`

**Objectif** : purge l'activité de collecte SonarQube (`activity` et `activity_historique`) plus ancienne que N jours — corrige un écart RGPD (conservation illimitée jusqu'ici, voir [Conformité RGPD](../conformite/rgpd.md) section 13, action 2). Les deux tables sont purgées sur le même seuil ; l'opération est idempotente (relancer la commande termine le travail restant en cas d'échec partiel).

| Option | Type | Défaut | Description |
| --- | --- | --- | --- |
| `--days` / `-d` | int | `365` (réglable via `ACTIVITY_RETENTION_DAYS`) | Rétention en jours |
| `--dry-run` | flag | — | Affiche le décompte sans supprimer |

```bash
php bin/console app:activity:purge --days=180 --dry-run
```

**Impact** : 🔴 Destructive, avec dry-run.

**Invocation** : cron, **quotidien à 4h30** — `--days=365 --no-interaction --env=prod`.

Affiche systématiquement un décompte détaillé par table (`activity` / `activity_historique`) après exécution.

## 🔟+1️⃣ 🧹 `app:user-agent:purge`

**Objectif** : purge les événements et analyses User-Agent (`user_agent_event` et `user_agent_analysis`, donnée purement statistique de la page Statistiques) plus anciens que N jours — corrige un écart RGPD (conservation illimitée jusqu'ici, voir [Conformité RGPD](../conformite/rgpd.md) section 13, action 3). Les deux tables sont purgées sur le même seuil ; l'opération est idempotente.

| Option | Type | Défaut | Description |
| --- | --- | --- | --- |
| `--days` / `-d` | int | `180` (réglable via `USER_AGENT_RETENTION_DAYS`) | Rétention en jours |
| `--dry-run` | flag | — | Affiche le décompte sans supprimer |

```bash
php bin/console app:user-agent:purge --days=90 --dry-run
```

**Impact** : 🔴 Destructive, avec dry-run.

**Invocation** : cron, **quotidien à 4h45** — `--days=180 --no-interaction --env=prod`.

Affiche systématiquement un décompte détaillé par table (`user_agent_event` / `user_agent_analysis`) après exécution.

## 🔟+2️⃣ ♻️ `app:dependency-check:reprocess`

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

## 🔟+3️⃣ 📊 `app:sonar:compare-metrics`

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

## 🔟+4️⃣ ⏱️ `app:npm-audit:process`

**Objectif** : worker qui traite par lots la file d'ingestion des rapports npm audit (table `npm_processing_queue`) — même pattern exact que `app:dependency-check:process` ci-dessus (réclame les lignes en attente, décompresse/parse le JSON, délègue l'ingestion complète vers `npm_scan`/`npm_dependency`/`npm_vulnerability`/`npm_finding`).

| Option | Type | Défaut | Description |
| --- | --- | --- | --- |
| `--batch` / `-b` | int | `5` | Nombre de rapports traités par batch |
| `--max-batches` / `-m` | int | `1` | Nombre de batches consécutifs avant de sortir |
| `--watch` / `-w` | int | — | **Dev uniquement** : boucle infinie, pause de N secondes (min. 5s) entre chaque tick |

```bash
php bin/console app:npm-audit:process --batch=10
```

**Impact** : 🟡 Modification (INSERT/UPDATE scans, paquets, avis de sécurité et findings, mise à jour du statut de la queue).

**Invocation** : cron, **toutes les minutes** — `--batch=5 --no-interaction --env=prod`. Ajoutée à `docker/etc/cron/ma-moulinette` le 2026-11-21, en pair exact avec `app:dependency-check:process`/`app:zap:process`.

## 🔟+5️⃣ 🧹 `app:npm-audit:purge`

**Objectif** : purge les lignes terminées (`done` ou `failed`) de la file npm audit plus anciennes que N jours. Même logique que `app:dependency-check:purge` ci-dessus — ne touche jamais les lignes `queued` ni `processing`.

| Option | Type | Défaut | Description |
| --- | --- | --- | --- |
| `--days` / `-d` | int | `30` | Rétention en jours |
| `--dry-run` | flag | — | Affiche uniquement le décompte, sans suppression |

```bash
php bin/console app:npm-audit:purge --days=7 --dry-run
```

**Impact** : 🔴 Destructive (`DELETE` réel), avec dry-run.

**Invocation** : cron, **quotidien à 3h15** — `--days=30 --no-interaction --env=prod`. Décalée de 15 minutes par rapport à `app:dependency-check:purge` (3h00) pour éviter un chevauchement.

Affiche systématiquement (dry-run ou non) un décompte par statut (`queued`/`processing`/`done`/`failed`) après exécution.

## 🔟+6️⃣ 📚 Pour aller plus loin

- [Vue d'ensemble des commandes](index.md)
- [Commandes Maintenance](maintenance.md)
- [Commandes Dev](dev.md)
- [Exploitation DependencyCheck](../dependency-check/exploitation.md)

-**-- FIN --**-

[Retour au menu principal](/index.html)
