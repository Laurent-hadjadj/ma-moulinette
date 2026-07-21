# 🧪 Commandes Dev

Les 5 commandes de `src/Command/Dev/` — toutes réservées à `env=dev` : chacune vérifie `$appEnv !== 'dev'` en tout début d'exécution et refuse de s'exécuter ailleurs (`Command::FAILURE`), y compris les commandes en lecture seule.

## 📥 `app:dc:ingest-local`

**Objectif** : injecte directement en base (table `dc_processing_queue`, statut `queued`) des rapports JSON OWASP DependencyCheck lus depuis le disque local, en reproduisant le comportement de l'endpoint `POST /api/secure/dependency-check/upload` — pour alimenter des campagnes de test sans upload HTTP réel.

| Argument | Type | Description |
| --- | --- | --- |
| `path` | string | Fichier `.json` unique, ou dossier (lecture non récursive de tous les `*.json`) |

```bash
bin/console app:dc:ingest-local var/                 # tous les *.json du dossier
bin/console app:dc:ingest-local var/tetris.json       # un fichier précis
```

**Impact** : 🟡 Modification (insertion, une ligne par fichier JSON valide).

Un fichier optionnel `dc-metadata.json` (même dossier) est lu automatiquement s'il existe, pour simuler les en-têtes `X-Parent-*`/`X-Archetype-Version` envoyés par la CI en production — voir [Socle technique et archétype](../architecture/architecture-java.md#-socle-technique-et-archétype-module-dependencycheck).

Traitement "au mieux" : chaque fichier est traité indépendamment ; une erreur sur un fichier (JSON invalide, `projectInfo` incomplet) incrémente un compteur d'erreurs et n'interrompt pas le traitement des fichiers suivants. Le code de sortie final reflète ce compteur — un succès partiel est possible avec un code de sortie non nul.

## 🎲 `app:dev:seed-dc-scans`

**Objectif** : génère aléatoirement des scans DependencyCheck plausibles (dépendances vulnérables réelles connues, CVE réelles historiques type Log4Shell/Spring4Shell, sévérités pondérées) rattachés à des projets existants, pour alimenter les fragments analytics du dashboard.

| Option | Type | Défaut | Description |
| --- | --- | --- | --- |
| `--count` / `-c` | int | `10` | Nombre de scans à générer |
| `--clean` | flag | — | `TRUNCATE ... CASCADE` des 4 tables DC avant génération |

```bash
php bin/console app:dev:seed-dc-scans --count=20
php bin/console app:dev:seed-dc-scans --clean
```

**Impact** : 🟣 Hybride — 🔴 destructive avec `--clean` (`TRUNCATE` sans confirmation ni dry-run).

Seuls les projets "éligibles" sont sélectionnés (`maven_key` contenant `:`, tags non vides), pour éviter de générer des scans rattachés à un artefact `unknown`.

## 🔌 `app:ldap:test`

**Objectif** : teste une connexion et un bind LDAP avec les paramètres définis dans les variables d'environnement (`LDAP_HOST`, `LDAP_PORT`, `LDAP_ENCRYPTION`, `LDAP_BIND_DN`, `LDAP_BIND_PASSWORD`), pour diagnostiquer un problème de configuration sans passer par l'UI.

Aucun argument ni option.

```bash
bin/console app:ldap:test
```

**Impact** : 🔵 Lecture seule.

!!! note "✅ Corrections"
    Deux corrections apportées : un garde-fou d'environnement a été ajouté (absent jusqu'ici, contrairement aux 4 autres commandes `Dev/` — les identifiants de bind pouvaient apparaître en clair dans le message d'erreur en cas d'échec) ; le code de sortie a été corrigé pour retourner un échec réel en cas d'erreur de bind, au lieu de toujours signaler un succès.

## 🌱 `app:seed:projet-fixture`

**Objectif** : crée (ou réinitialise) un projet de test complet, avec toutes les tables de collecte peuplées de valeurs distinctes et jamais nulles/zéro, pour valider de bout en bout la chaîne "peinture → enregistrement" et détecter tout défaut de correspondance de champs.

| Argument | Type | Défaut | Description |
| --- | --- | --- | --- |
| `maven_key` | string | `fr.test:projet-fixture` | Clé Maven du projet fictif |

```bash
php bin/console app:seed:projet-fixture
php bin/console app:seed:projet-fixture autre.maven:key
```

**Impact** : 🟣 Hybride — destruction ciblée et idempotente : `DELETE` sur 14 tables pour ce `maven_key` précis (dans une transaction, avec rollback en cas d'erreur) avant réinsertion. Ce n'est pas un `TRUNCATE` global.

Crée un utilisateur fixture (`fixture-tester@ma-moulinette.fr`) au premier lancement ; les lancements suivants réutilisent l'utilisateur existant sans toucher au mot de passe déjà en base.

## 🧾 `app:test-compte-rendu`

**Objectif** : vérifie que les 10 premiers enregistrements de `BatchExecutionJournal` contiennent un compte-rendu HTML valide et correctement compressé en gzip, pour diagnostiquer un problème de lecture/décompression du champ (`BYTEA` en base).

Aucun argument ni option.

```bash
bin/console app:test-compte-rendu
```

**Impact** : 🔵 Lecture seule stricte (uniquement un `SELECT`, aucune écriture).

Gère les deux représentations possibles du champ `BYTEA` (flux PHP ou chaîne binaire directe), et détecte la compression gzip via sa signature magique plutôt que de tenter systématiquement la décompression.

## 📚 Pour aller plus loin

- [Vue d'ensemble des commandes](index.md)
- [Commandes Prod](prod.md)
- [Commandes Maintenance](maintenance.md)
- [Annuaire LDAP local](../developpement/openldap-local.md)

-**-- FIN --**-

[Retour au menu principal](/index.html)
