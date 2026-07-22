# 🛠️ Commandes Maintenance

Les 6 commandes de `src/Command/Maintenance/` — outils ponctuels (migration, audit, statistiques), invocation manuelle uniquement, aucune n'est présente dans le cron `cron-ma-moulinette`.

## 📋 `app:dc:audit-snapshot`

**Objectif** : affiche un état des lieux ASCII pur (sans emoji ni couleur ANSI, pour éviter les soucis d'encodage sous PowerShell) des scans DependencyCheck stockés en base : cohérence des compteurs de vulnérabilités et analyse des chaînages de "socles" (archétypes/BOM Maven).

Aucun argument ni option.

```bash
php bin/console app:dc:audit-snapshot
```

**Impact** : 🔵 Lecture seule.

4 sections de sortie : liste des scans, invariant `cveCountTotal = C+H+M+L+I`, groupage par projet avec chaînage `previous_scan_*`, socles/archétypes utilisés ou orphelins.

!!! note "Outil de diagnostic assumé comme temporaire"
    Documentée dans son propre code comme pouvant être retirée en fin de campagne qualité ou conservée comme outil de debug — pas une commande de production pérenne.

## 🔄 `app:dc:recompute-latest`

**Objectif** : recalcule pour tous les couples (groupe, artefact) de la table `dc_scan` les drapeaux `is_latest_overall` et `is_latest_release`. Utile après une migration de schéma ou en cas de suspicion de désynchronisation suite à une ingestion incomplète.

Aucun argument ni option.

```bash
php bin/console app:dc:recompute-latest
```

**Impact** : 🟡 Modification, **idempotente** — peut être relancée sans risque autant de fois que nécessaire.

La commande s'auto-valide : après le recalcul, elle vérifie l'invariant `nbLatestOverall === nbCouples` et retourne `Command::FAILURE` avec une piste SQL si l'invariant est violé (typiquement une version non parsable).

## 📈 `app:admin:refresh-stats`

**Objectif** : génère `var/admin-stats.json` (et sa copie versionnée `migrations/admin-stats.json`) avec des statistiques de code source (lignes de code/commentaires/vides par langage, via `cloc`) et le nombre de tests PHPUnit par suite (`unit`, `integration`), sans les exécuter.

Aucune option dédiée (seule l'option globale Symfony `-v`/`--verbose` active un mode diagnostic affichant les commandes `cloc` exécutées).

```bash
php bin/console app:admin:refresh-stats -v
```

**Impact** : 🟡 Modification de fichiers locaux uniquement (aucune base de données) — écrase les 2 fichiers JSON sans confirmation.

!!! note "📌 À exécuter à chaque publication de version"
    `StatistiqueController` lit `var/admin-stats.json` en priorité, puis se rabat sur `migrations/admin-stats.json` (le seul des deux versionné, donc le seul qui survit à un déploiement qui vide `var/`).
    Si cette commande n'est pas relancée — puis le fichier committé — avant de taguer une version, la page de statistiques de l'application continue d'afficher les métriques de la version précédente après déploiement.

!!! caution "⚠️ Nécessite le binaire `cloc`"
    La commande dépend d'un outil externe non embarqué (`cloc`, installable via `winget install AlDanial.Cloc` sous Windows). En son absence, elle échoue proprement avec un message d'installation plutôt que de planter.

## 🗜️ `app:migrate-compte-rendu`

**Objectif** : parcourt en lots les enregistrements de `BatchExecutionJournal` et compresse en gzip (niveau 9) les champs `compte_rendu` qui ne le sont pas encore, pour uniformiser le stockage en base. Voir [Guide de migration](../developpement/guide-migration.md) pour le contexte complet.

| Option | Type | Défaut | Description |
| --- | --- | --- | --- |
| `--batch-size` | int | `100` | Taille du lot de lecture/écriture |
| `--dry-run` | flag | — | Simulation, aucune écriture |
| `--start-id` | int | `0` | ID de départ (reprise d'une migration interrompue) |
| `--max-rows` | int | `0` (illimité) | Plafond du nombre d'enregistrements traités |
| `--content-type` | string | `''` | À `'texte'`, ignore les enregistrements déjà compressés |

```bash
php bin/console app:migrate-compte-rendu --batch-size=50 --dry-run
php bin/console app:migrate-compte-rendu --start-id=0 --batch-size=50
```

**Impact** : 🟣 Hybride.

## 🏗️ `app:historique:rebuild`

**Objectif** : reconstruit l'historique SonarQube d'un projet en rejouant, version par version, la récupération des analyses et métriques Sonar, puis en réinsérant chaque ligne dans la table `historique`.

| Option | Type | Défaut | Description |
| --- | --- | --- | --- |
| `--project` | string | — | Clé du projet Sonar (obligatoire) |
| `--dry-run` | flag | — | Affiche le détail par version sans insertion |

```bash
php bin/console app:historique:rebuild --project=sonarlint-visualstudio --dry-run
```

**Impact** : 🟣 Hybride.

!!! note "✅ Correction"
    Un appel de débogage bloquant a été retiré, et l'insertion en base a été corrigée : elle appelle désormais `HistoriqueRepository::insertHistoriqueAjoutProjet()` (une ligne à la fois, conforme à la vraie signature de cette méthode) au lieu d'une méthode `batchInsert()` qui n'existe pas sur ce repository.
    Le message final indique désormais le nombre de lignes insérées et d'échecs, et la commande retourne un code d'échec s'il y a eu au moins une erreur d'insertion.

## 🔍 `app:verify:historique`

**Objectif** : pour une `maven_key` donnée, audite la dernière ligne de la table `historique` et détecte les dérives silencieuses dans la chaîne JS ↔ Controller ↔ SQL — classe chaque colonne en NULL (dérive certaine), ZÉRO/vide (probable valeur de repli) ou OK, en excluant les colonnes légitimement nullables par conception.

| Argument | Type | Défaut | Description |
| --- | --- | --- | --- |
| `maven_key` | string | `fr.test:projet-fixture` | Projet Maven à auditer |

Cycle d'utilisation typique :

```bash
php bin/console app:seed:projet-fixture
# (peinture + enregistrement via l'UI)
php bin/console app:verify:historique fr.test:projet-fixture
```

**Impact** : 🔵 Lecture seule.

Retourne `Command::FAILURE` si aucune ligne historique n'est trouvée pour la clé donnée, ou si au moins une colonne NULL est détectée — utile pour une intégration dans un script de vérification. La liste des colonnes exclues (légitimement nullables) est maintenue à la main et doit être mise à jour si le schéma de `historique` évolue.

## 📚 Pour aller plus loin

- [Vue d'ensemble des commandes](index.md)
- [Commandes Prod](prod.md)
- [Commandes Dev](dev.md)
- [Guide de migration](../developpement/guide-migration.md)

-**-- FIN --**-

[Retour au menu principal](/index.html)
