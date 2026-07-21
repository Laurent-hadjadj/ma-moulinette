# 📖 DependencyCheck — référence

## 📦 Format du payload accepté

Schéma minimal validé (sous-ensemble du rapport OWASP DependencyCheck v1.1) :

```json
{
  "reportSchema": "1.1",
  "projectInfo": {
    "groupID":    "<groupId Maven>",
    "artifactID": "<artifactId Maven>",
    "version":    "<version>",
    "reportDate": "ISO 8601"
  },
  "dependencies": [],
  "scanInfo": {}
}
```

`reportSchema`, `groupID`/`artifactID`/`version` sont requis et non vides ; `dependencies` est requis (peut être vide) ; `scanInfo`/`reportDate` sont optionnels. Tout autre champ du rapport est conservé tel quel dans le payload stocké — le décodage ne valide que ce minimum.

Formats binaires acceptés (détection par magic bytes, pas par le seul `Content-Type` déclaré) :

| Format | Détection | Traitement |
| --- | --- | --- |
| JSON brut | `{` ou `[` après espaces | Aucune décompression |
| gzip | `1F 8B` | `gzdecode()` |
| zip | `50 4B 03 04` | Exactement un fichier dans l'archive, de nom `.json` (pas de contrainte de position dans l'arborescence) |
| tar.gz | `1F 8B` + `ustar` (offset 257 après décompression) | Un seul fichier `.json` |

## 🗺️ Mapping JSON → base de données (synthèse)

| Champ JSON | Colonne | Table |
| --- | --- | --- |
| `projectInfo.groupID`/`artifactID`/`version` | `project_group`/`project_artifact`/`project_version` | `dc_scan` |
| `projectInfo.reportDate` | `scan_date` | `dc_scan` |
| `scanInfo.engineVersion` | `engine_version` | `dc_scan` |
| `dependencies[].sha1`/`sha256`/`md5` | `sha1`/`sha256`/`md5` | `dc_dependency` (clé de dédup : `sha1`) |
| `dependencies[].packages[0].id` (purl) | `pkg_coordinates`, `vendor`/`product`/`version` extraits | `dc_dependency` |
| `dependencies[].vulnerabilities[].name` | `cve_id` | `dc_cve` (clé de dédup : `cve_id`) |
| `.cvssv3.baseScore`/`.baseSeverity`/`.attackVector` (clé JSON en minuscules) | `cvss_v3_score`/`cvss_v3_severity`/`cvss_v3_attack_vector` | `dc_cve` |
| `.cwes[]` | `cwes` (JSONB) | `dc_cve` |
| — (calculé à l'ingestion, snapshot) | `severity_at_scan`/`cvss_at_scan` | `dc_finding` |

Le schéma complet (colonnes, types, contraintes, index) est documenté dans [Architecture — base de données](../architecture/architecture-base-de-donnees.md#-dependencycheck-owasp).

Trois en-têtes HTTP optionnels complètent l'upload avec des métadonnées absentes du rapport DependencyCheck lui-même (socle technique, archétype) — voir [Socle technique et archétype](../architecture/architecture-java.md#-socle-technique-et-archétype-module-dependencycheck).

## 🌐 Codes HTTP

### `POST /api/secure/dependency-check/upload`

| Code | Quand |
| --- | --- |
| 202 | Nouveau payload accepté et mis en file |
| 200 | Payload déjà reçu (idempotence) — même `ulid` retourné |
| 400 | Payload vide ou JSON malformé |
| 401 | Token absent ou invalide |
| 413 | Payload trop volumineux (brut > 10 Mo ou décompressé > 50 Mo) |
| 415 | Format non reconnu ou `Content-Type` incompatible |
| 422 | Schéma DependencyCheck invalide (`reportSchema`/`projectInfo`/`dependencies` manquant ou incohérent), ou archive gzip/zip/tar.gz corrompue ou mal formée (mauvais nombre de fichiers, absence de `.json`) |
| 500 | Erreur serveur (insertion base) |
| 503 | Endpoint désactivé : `DC_INGEST_TOKEN` non configuré côté serveur |

### `GET /api/secure/dependency-check/status/{ulid}`

| Code | Quand |
| --- | --- |
| 200 | Ulid trouvé : `{ulid, status, sha256, project, attempts, created_at, ...}` |
| 401 | Token absent ou invalide |
| 404 | Ulid inconnu ou mal formé |
| 503 | Endpoint désactivé |

## 🧮 Formule JH (effort de remédiation)

Chaque dépendance vulnérable à mettre à jour se voit attribuer un effort estimé en **jours-homme (JH)**, utilisé pour le plan de remédiation (page executive) et le calcul du gain de mutualisation (dashboard, page mutualisables). La formule est **paramétrable par famille technologique** (`config/packages/dc_remediation.yaml`) plutôt que fixe, car l'effort de validation dépend fortement de l'écosystème — un bump Log4j ne se valide pas comme un bump Spring Framework.

```text
JH = (effort_test[famille] + surcharge_upgrade) × marge_sécurité + bonus[sévérité_max]
     arrondi au demi-jour
```

Implémentation : `DcExecutiveAnalyticsService::computeDepJh(string $family, string $sevMax): float`. Les 4 paramètres (`upgrade_overhead`, `safety_margin`, `severity_bonus`, `test_effort_by_family`) sont injectés depuis le YAML et exposés via des getters pour la modale méthodologique partagée entre les pages executive, dashboard et mutualisables — modifier une valeur ne nécessite qu'un `bin/console cache:clear`, aucun code PHP à toucher.

La famille technologique d'une dépendance est déduite par une heuristique `guessFamily(vendor, product)` (regex sur le nom du produit — `log4j`, `commons-`, `jackson`, `spring-`, etc., avec repli sur `Autres`).

!!! caution "⚠️ Ne pas confondre avec l'ancienne formule fixe"
    Une formule antérieure plus simple (`base + bonus si CRITICAL`) a existé et apparaît encore dans certaines notes de conception archivées. Elle a été remplacée par la formule paramétrable ci-dessus — en cas de doute, la source de vérité est `DcExecutiveAnalyticsService::computeDepJh()`, pas un document de conception daté.

## 📓 Glossaire

| Terme | Définition |
| --- | --- |
| **CVE** | Common Vulnerabilities and Exposures — identifiant standard d'une vulnérabilité (ex. `CVE-2023-46120`) |
| **CWE** | Common Weakness Enumeration — type de faiblesse (ex. `CWE-400` = consommation excessive de ressources) |
| **CVSS** | Common Vulnerability Scoring System — score de gravité de 0 à 10 |
| **NVD** | National Vulnerability Database — référentiel de vulnérabilités du NIST (États-Unis) |
| **purl** | Package URL — format standard d'identification d'un package (ex. `pkg:maven/com.rabbitmq/amqp-client@5.9.0`) |
| **ULID** | Universally Unique Lexicographically Sortable Identifier — alternative à l'UUID, triable par date de création |
| **Finding** | Une CVE détectée dans une dépendance précise d'un scan donné. Une CVE touchant N dépendances génère N findings |
| **Socle** | Parent POM/BOM d'entreprise partagé par plusieurs applications, qui fixe les versions de dépendances communes |
| **Archétype** | Template Maven ayant servi à générer le squelette initial d'un projet |
| **`FOR UPDATE SKIP LOCKED`** | Clause PostgreSQL (≥ 9.5) qui ignore les lignes déjà verrouillées par une autre transaction — permet plusieurs workers concurrents sans collision |

## 📚 Pour aller plus loin

- [Architecture d'ingestion](architecture.md) : pipeline, décisions techniques.
- [Pages et navigation](pages.md) : parcours utilisateur, filtres.
- [Architecture — base de données](../architecture/architecture-base-de-donnees.md) : schéma complet des 4 tables.

-**-- FIN --**-

[Retour au menu principal](/index.html)
