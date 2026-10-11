# 🔎 Exploration — OWASP API Security Top 10 (2023)

!!! note "📌 Statut : exploratoire, aucun développement engagé"
    Ce document consigne une première passe de veille (11/10/2026) sur les outils capables d'alimenter un futur tableau de bord **OWASP API Security Top 10 (2023)** dans Ma-Moulinette, sur le même principe que les modules existants (SonarQube, OWASP Dependency-Check, Actuator) : un outil externe produit un rapport structuré, Ma-Moulinette le collecte, l'historise et le restitue. Rien n'est encore retenu ni implémenté — ce document sert de point de départ pour décider d'une éventuelle suite.

## 🎯 Objectif de la veille

Partir de la liste communautaire [OWASP API Security Tools](https://community.owasp.org/api_security_tools), en retenir uniquement les outils **non-commerciaux** (licence open source réelle, pas un essai gratuit d'un produit commercial), et évaluer leur capacité à produire une sortie structurée (JSON de préférence) qu'un nouveau `Batch*Controller` pourrait collecter, de la même façon que la collecte Dependency-Check lit déjà un rapport JSON externe.

## 📋 Outils non-commerciaux recensés

| Outil | Licence | Nature | Sortie exploitable ? |
| --- | --- | --- | --- |
| **ZAP (Zed Attack Proxy)** | Apache 2 | Scanner DAST généraliste, mode « API Scan » piloté par spec OpenAPI/GraphQL | ✅ Fort — rapport JSON/XML natif, API REST pour piloter une collecte automatisée |
| **Nuclei** (ProjectDiscovery) | MIT | Scanner à templates YAML, communauté très active, templates tagués `api` | ✅ Fort — sortie `-jsonl` native, CLI simple à orchestrer en batch |
| **APIClarity** | Apache 2 | Conçu spécifiquement autour de la détection OWASP API Top 10 par analyse de trafic | ⚠️ À vérifier — projet historiquement le plus aligné sur le besoin, statut de maintenance du dépôt non confirmé |
| **Pynt** | Apache 2 | Génère des tests de sécurité à partir d'une spec OpenAPI ou d'une collection Postman | ⚠️ À vérifier — édition OSS vs fonctionnalités réservées à l'offre commerciale |
| **Cherrybomb** (BLST Security) | Apache 2 | Valide une spec OpenAPI contre des mauvaises configurations de sécurité avant mise en prod | ⚠️ À vérifier — format de sortie CLI exact non confirmé |
| **Automatic API Attack Tool** (Imperva) | MIT | Génère des attaques automatiquement à partir d'une spec OpenAPI | ⚠️ À vérifier — maturité/activité réelle du dépôt non confirmée |
| **WuppieFuzz** (TNO) | Apache 2 | Fuzzing guidé par couverture, piloté par spec OpenAPI | ⚠️ À vérifier — format de sortie non confirmé |
| graphql-cop | MIT | Scanner GraphQL uniquement | Trop spécifique, sauf si GraphQL entre un jour dans le périmètre |
| openapi3-fuzzer, jerry-curl, Wfuzz, ffuf, ExploitSpec, Astra, httpie, http-tanker, Hurl, Hoppscotch, rest-assured, curl | diverses (GPL/MIT/BSD/Apache) | Briques génériques (clients HTTP, fuzzers bas niveau) | Pas des scanners « API Top 10 » clé en main |
| Darkmoon | GPL-3.0 | Self-hosted, projet très jeune/peu connu | Risque de maturité insuffisante |

## 🧭 Analyse

Deux outils se détachent *a priori* comme candidats les plus solides :

- **ZAP** : le plus mature et le plus documenté de la liste, déjà un standard de facto en DAST, rapport JSON directement parseable, large communauté et nombreuses intégrations CI existantes.
- **Nuclei** : sortie JSON très simple à consommer, templates communautaires déjà tagués pour des vulnérabilités API (BOLA, mass assignment, etc. existent dans `nuclei-templates`).

Les quatre outils marqués ⚠️ sont, sur le papier, plus directement pensés pour l'API Top 10 — en particulier **APIClarity**, dont l'objectif de conception initial était précisément la détection des risques OWASP API Top 10 à partir du trafic observé. Aucun des quatre n'a cependant été vérifié en pratique (format de sortie réel, mapping explicite vers les catégories 2023, activité de maintenance du dépôt) : ce sont des pistes à creuser, pas des recommandations.

Le reste de la liste (fuzzers génériques, clients HTTP bas niveau, outil GraphQL-only) est écarté pour cette première sélection : ce sont des briques utiles à un pentest manuel, pas des scanners clé en main produisant un rapport structuré exploitable par une collecte automatisée.

## 🧪 POC — résultats (11/10/2026)

POC exécuté en local (Docker Desktop) contre trois cibles réelles démarrées pour l'occasion, afin de sortir
du papier : **ma-moulinette** (PHP, la stack e2e déjà en place), **`swaggerapi/petstore`** (API Java officielle swagger.io, spec **Swagger 2.0** native) et deux services **SOAP** Java (`jeromeguillaume/ws-soap-calculator` puis `fosbsb/soap-ws`, le premier ayant révélé un bug WSDL — voir plus bas).

### Prérequis confirmés en pratique

| | ZAP | Nuclei |
| --- | --- | --- |
| Image Docker | `ghcr.io/zaproxy/zaproxy:stable` (l'ancienne `zaproxy/zap-stable` sur Docker Hub est dépréciée) | `projectdiscovery/nuclei` |
| Dépendance externe | Aucune (Java embarqué dans l'image) | **Templates à récupérer séparément** (`-update-templates`, télécharge `nuclei-templates` depuis GitHub au premier run — prévoir un volume persistant en usage régulier) |
| Mode d'appel | `zap-api-scan.py -t <cible> -f openapi\|soap\|graphql -J rapport.json` | `nuclei -u <cible> -tags ... -jsonl -o rapport.jsonl` |
| Cible | Application démarrée et joignable en HTTP — **confirmé : aucun des deux ne lit du code source**, uniquement du trafic HTTP réel | idem |

### Résultat n°1 — Swagger 2.0 (Petstore, Java)

- **ZAP** : a importé la spec Swagger 2.0 sans problème (`-f openapi` accepte aussi Swagger 2.0, pas seulement OpenAPI 3.x), a énuméré les vrais endpoints (`/pet`, `/store`, `/user`...) et les a testés avec l'Active Scan. Résultat : 116 contrôles passés, **4 findings réels** (CORS mal configuré, version serveur Jetty exposée dans l'en-tête `Server`, en-têtes de sécurité manquants).
- **Nuclei** : **`-im openapi` refuse la spec** avec l'erreur `not an OpenAPI spec (missing 'openapi' field)` — le fichier est un Swagger 2.0 — son importer n'accepte que le champ `openapi` (OpenAPI 3.x), pas `swagger: "2.0"`. Repli sur un scan   générique par tags (`-tags api,exposure,misconfig`) sans connaissance de la spec : Nuclei ne scanne alors que l'URL racine, sans jamais découvrir `/pet`, `/store`, `/user` — 12 résultats, tous de sévérité `info` (détection Swagger, en-têtes manquants), rien de spécifique à l'API réelle.
- **Conclusion pour ce cas d'usage (Java/Swagger 2) : ZAP est le seul des deux à exploiter réellement la spec.** C'est un point bloquant pour Nuclei sur ce périmètre précis, pas un détail mineur.

### Résultat n°2 — SOAP (Java)

- **Nuclei n'a aucun support SOAP documenté** (confirmé : pas de format d'import SOAP, l'outil est pensé REST/HTTP générique) — écarté d'office sur ce volet.
- **ZAP** (`-f soap`) a d'abord **crashé** sur `ws-soap-calculator` : `NullPointerException` dans `WSDLCustomParser.parseWSDL` (`requestParts` null). Inspection du WSDL : chaque opération n'a qu'un `<wsdl:output>`, **aucun `<wsdl:input>`** — WSDL non conforme généré par cette image de démo précise, pas  une limite générale de ZAP.
- Rejoué contre un second service SOAP (`fosbsb/soap-ws`, WSDL standard avec input/output complets) : **ça fonctionne**, 115 contrôles passés, 5 findings réels dont un spécifique SOAP (« SOAP XML Injection »).
- **Conclusion** : le parseur WSDL de ZAP n'est pas défensif face à un WSDL mal formé (plante au lieu de signaler une erreur propre) — à garder en tête si les applications Java réelles à scanner ont des WSDL générés de façon non standard (certains frameworks internes legacy en produisent). Sur un WSDL correct, ça fonctionne nativement, y compris pour détecter une catégorie de vulnérabilité propre à SOAP (injection XML).

### Résultat n°3 — ma-moulinette (PHP)

Les deux outils tournent sans spec (aucune n'existe encore côté ma-moulinette) :

- **ZAP** (`zap-baseline.py`, passif) : 59 contrôles passés, **8 findings réels** — CSP absent, `Permissions-Policy` absent, `X-Powered-By`/`Server` qui fuitent la stack, `X-Content-Type-Options` manquant. Le spider a buté sur des `400 Bad Request` (mismatch de Host attendu — `TRUST_HOST1`/`TRUST_HOST2` côté ma-moulinette limitent les hôtes de confiance à `localhost`/`127.0.0.1`, alors que ZAP, dans son
propre conteneur, atteint la cible via `host.docker.internal`) : artefact de l'environnement de test, pas une vraie faille, mais à prendre en compte si un scan réel est un jour piloté depuis un conteneur séparé.
- **Nuclei** (tags génériques) : 15 résultats, tous `info` — mêmes en-têtes manquants, détection de la stack (Foundation CSS, WAF), plus une détection `nginx-eol` (version nginx utilisée par la stack e2e signalée comme proche de fin de vie — à vérifier séparément, hors périmètre API).

### Format des rapports — ce qui compte pour une future ingestion

- **ZAP** (`site[].alerts[]`) : chaque alerte porte `pluginid`, `alert`/`name`, `riskcode`/`riskdesc` (sévérité), `cweid`, `wascid`, `instances[]` (uri, method, evidence), `solution`. Structure propre et stable, mais **aucun champ ne référence directement une catégorie OWASP API Top 10 2023** — un mapping `cweid`/`pluginid` → `API1:2023`...`API10:2023` resterait à construire côté Ma-Moulinette.
- **Nuclei** (JSON Lines, une ligne par finding) : `template-id`, `info.severity`, `info.classification.cwe-id`, `matched-at`, request/response bruts. Tout aussi facile à parser ligne à ligne, même limite sur le mapping API Top 10 (aucun champ natif).

## 🔍 Vérification des 5 outils ⚠️ (dépôt GitHub, 11/10/2026)

Vérification directe via l'API GitHub (date du dernier push, archivage) et le README de chaque dépôt (mention explicite d'OWASP API Security, format de sortie) :

| Outil | Dépôt réel | Dernier push | Statut | Mentionne OWASP API Security ? | Format de sortie documenté |
| --- | --- | --- | --- | --- | --- |
| **APIClarity** | `openclarity/apiclarity` (déplacé, l'ancien `apiclarity/apiclarity` redirige) | 2024-10-08 | 🔴 **Archivé** (lecture seule, abandonné par ses mainteneurs) | Non — description générique « identify API security risks », pas de référence au Top 10 2023 précisément | Sans objet, projet mort |
| **Pynt** | `pynt-io/pynt` | 2025-09-14 (~13 mois) | 🟡 Actif mais peu de mouvement, 62 ⭐, 4 issues ouvertes | Non | Non documenté dans le README (orienté wrapper Postman/Newman/GitHub Actions, aucune structure JSON décrite) |
| **Cherrybomb** (BLST Security) | `blst-security/cherrybomb` | 2024-10-25 (~1 an) | 🟡 Ralenti, 1239 ⭐ mais 38 issues ouvertes | Non — « common issues and vulnerabilities » générique | `--format cli/txt/json` existe (option confirmée par ailleurs), mais le README ne détaille pas le schéma JSON |
| **Automatic API Attack Tool** (Imperva) | `imperva/automatic-api-attack-tool` | 2023-05-13 (~3,5 ans) | 🔴 Abandonné en pratique | Non — cite des vecteurs génériques (accès illégal aux ressources, XSS, SQLi, RFI) | Console + rapports **TestNG** (XML), pas de JSON |
| **WuppieFuzz** (TNO) | `TNO-S3/WuppieFuzz` | 2026-10-07 (**4 jours**) | 🟢 Très activement maintenu, 35 issues ouvertes (signe de communauté vivante, pas d'abandon) | Non | Rapports de couverture (endpoints/code) + base de requêtes visualisée via Grafana ; option `output_format` existe mais non détaillée dans le README (valeur par défaut « human-readable ») |

**Conclusions de cette vérification :**

- **Aucun des 5 outils ne mentionne l'OWASP API Security Top 10 dans son README** — l'hypothèse initiale (section « Analyse » plus haut, en particulier pour APIClarity) était trop optimiste sur ce point précis ; tous restent génériques (« API security issues/risks »), le mapping vers les catégories 2023 resterait à construire quel que soit l'outil.
- **APIClarity et Automatic API Attack Tool sont morts** (archivé / 3,5 ans sans activité) — à retirer définitivement de toute short-list.
- **WuppieFuzz est le seul des 5 activement maintenu** (push il y a 4 jours) — si un besoin de fuzzing guidé par couverture se présente un jour, c'est le seul candidat sérieux parmi les cinq, mais son format de sortie reste à vérifier en pratique (non documenté clairement dans le README).
- **Pynt et Cherrybomb** sont dans un entre-deux (actifs mais ralentis, pas abandonnés) — pas prioritaires vu que ZAP couvre déjà mieux le périmètre Java/Swagger/SOAP testé dans le POC.

## ❓ Questions encore ouvertes

1. **Décision à prendre : ZAP comme outil retenu plutôt que Nuclei pour le volet Java/Swagger/SOAP**, vu les deux blocages concrets de Nuclei ci-dessus (Swagger 2 refusé, SOAP non supporté). Nuclei reste pertinent en complément pour du REST/OpenAPI 3.x pur si ce cas se présente, mais n'est pas suffisant seul sur le périmètre décrit (Java Swagger 2 + SOAP).
2. Construire le mapping `cweid`/`pluginid` ZAP → catégories OWASP API Top 10 2023 — aucun outil testé ou vérifié ne le fournit nativement, c'est un travail à faire côté Ma-Moulinette quel que soit l'outil retenu.
3. Vérifier la robustesse du parseur WSDL de ZAP sur un WSDL d'application Java réelle du périmètre (pas une image de démo) avant de considérer le support SOAP comme acquis en production.

## 📚 Référence

- Liste source : [OWASP API Security Tools](https://community.owasp.org/api_security_tools) (communauté OWASP).

-**-- FIN --**-

[Retour au menu principal](/index.html)
