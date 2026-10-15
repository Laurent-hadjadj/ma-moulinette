# 🔎 Exploration — OWASP API Security Top 10 (2023)

!!! note "📌 Statut : ZAP retenu, ingestion + indicateurs implémentés (13/10/2026)"
    Ce document a démarré comme une veille (11/10/2026) sur les outils capables d'alimenter un futur
    tableau de bord **OWASP API Security Top 10 (2023)** dans Ma-Moulinette. À l'issue de cette veille,
    **ZAP a été retenu** comme scanner DAST et l'angle d'exploitation a changé : plutôt qu'un hypothétique
    mapping API Top 10 2023 (confirmé introuvable, voir plus bas), les alertes ZAP portent nativement des
    tags **OWASP Top 10 web 2017/2021**, exactement les deux référentiels déjà utilisés par le module
    OWASP SAST (SonarQube) existant. Le chantier d'implémentation (ingestion de rapport + table
    d'agrégation `owasp_dast`, complément DAST du module SAST) est **terminé et vert** pour ce premier
    incrément (voir section dédiée plus bas) — reste hors périmètre : la page de restitution visuelle.

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

POC exécuté en local (Docker Desktop) contre trois cibles réelles démarrées pour l'occasion, afin de sortir du papier : **ma-moulinette** (PHP, la stack e2e déjà en place), **`swaggerapi/petstore`** (API Java officielle swagger.io, spec **Swagger 2.0** native) et deux services **SOAP** Java (`jeromeguillaume/ws-soap-calculator` puis `fosbsb/soap-ws`, le premier ayant révélé un bug WSDL — voir plus bas).

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

- **ZAP** (`zap-baseline.py`, passif) : 59 contrôles passés, **8 findings réels** — CSP absent, `Permissions-Policy` absent, `X-Powered-By`/`Server` qui fuitent la stack, `X-Content-Type-Options` manquant. Le spider a buté sur des `400 Bad Request` (mismatch de Host attendu — `TRUST_HOST1`/`TRUST_HOST2` côté ma-moulinette limitent les hôtes de confiance à `localhost`/`127.0.0.1`, alors que ZAP, dans son propre conteneur, atteint la cible via `host.docker.internal`) : artefact de l'environnement de test, pas une vraie faille, mais à prendre en compte si un scan réel est un jour piloté depuis un conteneur séparé.
- **Nuclei** (tags génériques) : 15 résultats, tous `info` — mêmes en-têtes manquants, détection de la stack (Foundation CSS, WAF), plus une détection `nginx-eol` (version nginx utilisée par la stack e2e signalée comme proche de fin de vie — à vérifier séparément, hors périmètre API).

### Format des rapports — ce qui compte pour une future ingestion

- **ZAP** (`site[].alerts[]`) : chaque alerte porte `pluginid`, `alert`/`name`, `riskcode`/`riskdesc` (sévérité), `cweid`, `wascid`, `instances[]` (uri, method, evidence), `solution`. Structure propre et stable, mais **aucun champ ne référence directement une catégorie OWASP API Top 10 2023** — un mapping `cweid`/`pluginid` → `API1:2023`...`API10:2023` resterait à construire côté Ma-Moulinette.
- **Nuclei** (JSON Lines, une ligne par finding) : `template-id`, `info.severity`, `info.classification.cwe-id`, `matched-at`, request/response bruts. Tout aussi facile à parser ligne à ligne, même limite sur le mapping API Top 10 (aucun champ natif).

!!! warning "⚠️ « ZAP supporte OWASP API Security » — à prendre avec précaution"
    ZAP revendique largement un support « OWASP » dans sa documentation et son add-on *Common Library* (tags par règle/alerte). Vérifié directement sur la page des tags (`zaproxy.org`, 11/10/2026) : les **seuls** tags OWASP existants couvrent l'**OWASP Top 10 générique web** — **2017** et **2021** — plus le *Web Security Testing Guide* (WSTG v4.2).
    **Aucun tag pour l'« OWASP API Security Top 10 » n'existe, ni en 2019 ni en 2023.** Ce n'est pas seulement un problème de version périmée (2019 au lieu de 2023) :

    ZAP ne mappe tout simplement pas vers *ce* référentiel-là — l'OWASP Top 10 web et l'OWASP API Security Top 10 sont deux projets OWASP distincts, à ne pas confondre malgré le nom « OWASP » partagé dans les deux. Toute mention de « support OWASP API Security » autour de ZAP (documentation tierce, marketing) renvoie en réalité à ce Top 10 web générique ou à un alignement CWE générique — jamais à un vrai mapping vers API1:2023...API10:2023.

    **Nuance importante** : ce n'est pas que la liste [OWASP API Security Tools](https://community.owasp.org/api_security_tools) source de ce document ait tort de citer ZAP/Nuclei — **plusieurs outils *commerciaux* de cette même liste revendiquent un classement par catégorie 2023** (42Crunch, Salt Security, Akto, Escape.tech) — mais vérifié en détail sur la page publique [42Crunch](https://42crunch.com/owasp-api-top-10.fr.html) (11/10/2026), ce classement reste **largement marketing** : les sections « Approche 42Crunch » reformulent les descriptions OWASP et les remplacent par des affirmations produit (« testé
    automatiquement dans API Scan ») **sans dire comment** — sur 5 des 10 catégories (API1, API6, API7, API8, API10), c'est même *moins* prescriptif techniquement que la page OWASP officielle elle-même. Seules API2 (validation JWT selon la RFC 8725) et API4 (limites de débit définies dans le contrat OpenAPI) apportent un détail un peu plus concret que le générique OWASP. Aucune requête d'exemple, aucun test généré, aucune description du contenu réel des contrôles revendiqués.

    La page OWASP officielle elle-même (API1:2023, vérifiée le 11/10/2026) n'est pas plus avancée sur ce point : sa section « How to Prevent » se limite à 4 recommandations génériques (autorisation par politique/hiérarchie, vérification de propriété par fonction, identifiants aléatoires, « écrire des tests ») — aucune procédure de test pas à pas, aucun exemple de requête, aucun outil cité au-delà d'une référence externe (*Authorization Testing Automation Cheat Sheet*) non développée sur la page.

    **Conclusion revue à la baisse** : aucune source consultée à ce stade — ni outil gratuit, ni page marketing commerciale, ni documentation OWASP officielle elle-même — ne fournit une méthodologie de détection suffisamment concrète pour être transposée directement en règle de classification côté Ma-Moulinette. Le travail de mapping `cweid`/`pluginid` → `API1:2023`...`API10:2023` resterait à
    construire manuellement, catégorie par catégorie, par expertise interne plutôt que par copie d'une source existante.

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

## 🔗 Intégration CI/CD — bonnes pratiques recensées (sources externes, 11/10/2026)

Deux articles externes (voir Référence) confirment indépendamment l'absence de mapping natif vers l'OWASP API Top 10 2023 côté ZAP (aucun des deux ne mentionne de numérotation `API1`...`API10`) et apportent des détails pratiques réutilisables si ZAP est retenu pour de la collecte automatisée :

- **Jamais de scan actif en production** — les deux sources insistent : un scan actif envoie des requêtes potentiellement destructrices, à réserver à un environnement de staging/test. Cohérent avec l'approche déjà suivie dans ce POC (cibles jouets + e2e Docker, jamais une instance réelle en production).
- **Grille de lecture des findings** : 4 niveaux de risque (`Informational`/`Low`/`Medium`/`High`) **croisés** avec 4 niveaux de confiance (`Low`/`Medium`/`High`/`Confirmed`) — explique le `"riskdesc": "High (Low)"` rencontré sur le finding SQLi de l'appli réelle testée plus haut (risque élevé *si confirmé*, confiance faible ici). Filtrer sur `risks=Medium,High` pour une première passe, traiter en priorité le `High`+confiance élevée.
- **Faux positifs** : un fichier de règles (`zap-rules.tsv`, une ligne par `pluginid` avec `WARN`/`FAIL`/`IGNORE`) permet de couper le bruit sans modifier le scan lui-même — pertinent si Ma-Moulinette doit un jour exposer un mécanisme équivalent (acquitter un finding récurrent connu).
- **CI/CD** : `zaproxy/action-api-scan` (GitHub Actions) ou job direct avec l'image officielle (GitLab CI), `fail_action`/`allow_failure: false` pour bloquer un pipeline sur un `High` confirmé, artefact JSON+HTML conservé. Dans les deux cas l'API cible doit déjà être démarrée et joignable — même contrainte que le POC ci-dessus (DAST = application réelle en cours d'exécution, jamais le code source).
- **Authentification** : utiliser un compte de test à droits limités (jamais un compte admin), token injecté en en-tête ou contexte ZAP exporté (`.context`) pour les API authentifiées — à prévoir si on scanne un jour les routes `/api/secure/...` de ma-moulinette elle-même plutôt que ses seules routes publiques.

## 🧩 Exploration des add-ons, fonctionnalités et rapports ZAP (13/10/2026)

Deuxième passe, cette fois centrée uniquement sur ZAP (retenu comme candidat le plus solide, cf. plus haut), sur trois axes : les **add-ons** disponibles au-delà de ce qui est bakė dans l'image `stable`, les **fonctionnalités** utilisables(règles de scan, Automation Framework), et la **structure réelle des rapports**. Vérifié en direct contre une instance ZAP en mode daemon (API REST, `ghcr.io/zaproxy/zaproxy:stable` 2.17.0) et contre le catalogue marketplace officiel (`ZapVersions-2.17.xml`, dépôt `zaproxy/zap-admin`).

### Add-ons — 49 installés par défaut, 118 au catalogue marketplace

L'image `stable` embarque 49 add-ons (API `/JSON/autoupdate/view/installedAddons`), dont les briques déjà utilisées dans le POC (`OpenAPI Support`, `SOAP Support`, `GraphQL Support`, `Report Generation`, `Automation Framework`...).

Le catalogue marketplace complet en référence 118 — les 69 non installés sont pour la plupart alpha/beta, à installer à la demande (`/JSON/autoupdate/action installAddon/`). Les plus pertinents pour un usage API Security, non installés par défaut :

| Add-on | Statut | Description | Intérêt pour Ma-Moulinette |
| --- | --- | --- | --- |
| **JWT Support** | alpha | Détecte les requêtes JWT et les scanne pour trouver des vulnérabilités liées | Directement lié à l'API2:2023 (Broken Authentication) si les API testées utilisent des JWT |
| **Access Control Testing** | alpha | Outils de test de contrôle d'accès (comparaison de réponses entre rôles) | Pertinent pour API1/API3/API5:2023 (BOLA/BOPLA/BFLA) — nécessite de configurer plusieurs utilisateurs/rôles dans un contexte ZAP |
| **Attack Surface Detector** | alpha | Analyse le **code source** de l'application pour générer la liste des endpoints à tester | Alternative à une spec OpenAPI quand elle n'existe pas (cas de ma-moulinette aujourd'hui) — mais statut alpha, jamais testé en pratique ici |
| **Technology Detection** | release | Fingerprinting technologique (frameworks, serveurs) via signatures | Déjà couvert en partie par les règles passives existantes (`X-Powered-By`...), apport marginal |
| **Authentication Statistics** | alpha | Enregistre les statistiques de connexion/déconnexion par contexte pendant un scan | Utile pour vérifier qu'un scan authentifié reste bien connecté tout du long (évite un faux « rien trouvé » silencieux) |
| **gRPC Support** | alpha | Inspecte/attaque des endpoints gRPC, décode les messages protobuf | Hors périmètre actuel (ma-moulinette n'expose pas de gRPC), à garder en tête si ça change |
| **Collection: Scan Rules Pack** | alpha | Installe en un coup toutes les règles actives/passives release+beta+alpha | Raccourci pratique plutôt que d'installer les add-ons un par un |
| **MCP Integration** | alpha | Intègre un serveur **MCP (Model Context Protocol)** dans ZAP, jobs Automation Framework `mcp-config`/`mcp-import` | ⚠️ Point notable, pas testé : ZAP peut s'exposer comme serveur MCP (ou importer un serveur MCP externe comme cible) — pertinent si un agent IA doit un jour piloter un scan directement, à documenter séparément si creusé |

Le reste (Custom Payloads, FuzzDB Offensive, Parameter Digger, SAML Support Collection: Pentester Pack...) est soit déjà couvert fonctionnellement par l'existant, soit hors périmètre API REST/SOAP/GraphQL. Aucun de ces add-ons non installés n'a été testé en pratique à ce stade — statuts alpha/beta à prendre comme un signal de prudence avant tout usage autre qu'exploratoire.

### Fonctionnalités — règles de scan chargées et Automation Framework

- **Règles chargées (statut `release` uniquement, celles de l'image `stable`)** : 53 règles de scan actif (`/JSON/ascan/view/scanners/`), 61 règles de scan passif (`/JSON/pscan/view/scanners/`). Les add-ons `Active/Passive scanner rules (alpha|beta)` du marketplace ajouteraient davantage de règles, non activées par défaut(compromis volume/stabilité).
- **Automation Framework** (plans YAML, add-on déjà installé en beta) : liste des types de job officiels (`zaproxy.org`, 13/10/2026) — `spider`, `spiderAjax`, `spiderClient`, `openapi`, `soap`, `graphql`, `postman`, `import`/`export`/`prune`, `activeScan`/`activeScan-config`/`activeScan-policy`, `passiveScan-config`/`passiveScan-wait`, `alertFilter`, `replacer`, `requestor`, `script`,`sequence-activeScan`/`sequence-import`, `delay`, `exitStatus`, `report`, `insights`, `mcp-config`/`mcp-import`.
  Le job `report` (ci-dessous) et les jobs d'import de spec (`openapi`/`soap`/`graphql`/`postman`) sont ceux qui rapprochent le plus l'outil d'une collecte automatisée pilotée par un seul fichier YAML plutôt que par des flags `zap-api-scan.py` — piste à explorer si un vrai module de collecte est un jour implémenté (remplacerait les invocations CLI testées dans le POC du 11/10).

### Le rapport — 13 templates disponibles, structure vérifiée sur un cas réel

Interrogé en direct (`/JSON/reports/view/templates/`) : 13 templates, add-on `Report Generation` (déjà installé, release) :

| Template | Format | Sections notables |
| --- | --- | --- |
| `sarif-json` | **SARIF** | Format standard d'analyse statique (GitHub Code Scanning, etc.) |
| `traditional-json` / `traditional-json-plus` | JSON | `-plus` ajoute requêtes/réponses brutes complètes |
| `traditional-xml` / `traditional-xml-plus` | XML | idem |
| `traditional-html` / `traditional-html-plus` / `modern` / `risk-confidence-html` / `high-level-report` | HTML | `modern` = thèmes + graphiques, `high-level-report` = synthèse exécutive, `risk-confidence-html` = grille risque×confiance détaillée |
| `traditional-pdf` | PDF | Export direct, pas besoin de convertir le HTML |
| `traditional-md` | Markdown | Pertinent pour un commentaire de PR automatisé |
| `auth-report-json` | JSON | Diagnostics d'authentification (sessions, stockage web, captures d'écran) — pertinent si un scan authentifié est un jour piloté sur les routes `/api/secure/...` |

**Rapports réellement générés** (scan passif contre la propre stack e2e Docker de ma-moulinette, 30 alertes réelles collectées — CSP absent, fuites `Server`/`X-Powered-By`, etc., cohérent avec le POC du 11/10) et inspectés en détail :

- **`traditional-json-plus`** : chaque alerte porte un champ `tags` structuré en tableau `{tag, link}` — confirmation **plus précise** que le 11/10 : on y trouve `OWASP_2017_AXX`, `OWASP_2021_AXX`, **et une nouveauté inédite : `OWASP_2025_AXX`** (ZAP a commencé à tagger le futur Top 10 web 2025), plus `WSTG-v42-...`, `CWE-XXX`, et des tags internes ZAP (`POLICY_QA_STD`, `POLICY_PENTEST`, `SYSTEMIC`).
  **Toujours aucun tag `API1:2023`...`API10:2023`** — confirme une troisième fois, sur un rapport réellement généré cette fois, la conclusion de l'encadré ci-dessus.
- **`sarif-json`** : structure SARIF standard (`runs[].tool.driver.rules[]` avec `id`, `name`, `fullDescription`, `properties.references`) — les liens OWASP Cheat Sheet y sont repris —, et surtout `relationships[].target.toolComponent.name == "CWE"` : le mapping CWE est natif et structuré en SARIF, contrairement au JSON classique où `cweid` est un simple champ plat. Toujours rien côté API Top 10.

## ✅ Implémentation — ingestion + indicateurs (13/10/2026)

Décision prise : ZAP retenu, format `traditional-json` (léger). Module implémenté sur le gabarit **OWASP
Dependency-Check** (seul précédent dans le code d'une donnée externe poussée en rapport plutôt que tirée
par collecte SonarQube) — upload HTTP avec token dédié → file d'attente → worker cron → parsing → tables
→ indicateurs, entièrement découplé des pipelines `ApiCollecteController`/`CollecteController`.

- **5 nouvelles tables** : `zap_processing_queue`, `zap_scan`, `zap_rule`, `zap_finding`, et surtout
  **`owasp_dast`** — même forme que la table `owasp` (SAST SonarQube, colonnes `a1`..`a10`), mais avec
  l'échelle de risque ZAP (`Informational/Low/Medium/High`) au lieu de l'échelle SonarQube
  (`Bloquant/Critique/Majeur/Mineur/Info`), construite en parcourant les tags `OWASP_2017_AXX`/
  `OWASP_2021_AXX` de chaque alerte. Historisée nativement par scan (contrairement à `owasp` qui écrase à
  chaque collecte).
- Endpoint `POST /api/secure/zap/upload` (+ `GET .../status/{ulid}`), token dédié `ZapIngestTokenSubscriber`,
  identification projet via en-têtes `X-Maven-Key`/`X-Project-Version` (le rapport ZAP, contrairement à
  Dependency-Check, ne contient aucune info projet Maven).
- 34 tests Unit + 18 tests Integration, suite complète du projet rejouée sans régression.
- **Hors périmètre de ce premier incrément** : page de restitution visuelle (chantier suivant), mapping
  vers l'OWASP **API** Security Top 10 2023 (confirmé inexistant, voir encadrés plus haut).

### ⚠️ Nouveau constat : couverture très inégale des catégories 2021 par les règles automatisées

Vérifié sur deux sources officielles consultées le 13/10/2026 :
[`docs/scanners.md`](https://github.com/zaproxy/zaproxy/blob/main/docs/scanners.md) (registre brut des
~300 règles ZAP, pluginid → nom, aucune catégorie/CWE/tag/mapping OWASP — confirme une fois de plus
l'absence de tout mapping officiel) et le guide officiel
[Zapping the OWASP Top 10 2021](https://www.zaproxy.org/docs/guides/zapping-the-top-10-2021/).

Ce second document révèle que certaines catégories ne sont **quasiment pas couvertes par des règles
automatisées**, contrairement à d'autres très bien couvertes (A03 Injection notamment) :

- **A07 (authentification)** : quasiment aucune règle taguée citée — le guide s'appuie presque
  exclusivement sur des outils manuels (comparaison de sessions HTTP, Forced Browse, génération de tokens).
- **A09 (journalisation/supervision)** : **aucune règle de détection n'est citée du tout** — ZAP ne fait
  que générer du trafic (Spider, Active Scan, Fuzzer) qui *pourrait* déclencher des logs côté serveur,
  sans jamais vérifier la qualité de cette journalisation.
- **A10 (SSRF)** : repose uniquement sur l'add-on **OAST Support** (test hors bande), pas sur le socle de
  règles actives/passives déjà installées.

**Implication directe pour `owasp_dast`** : un compteur à `0` sur les catégories A07/A09/A10 ne signifie
**pas** « rien trouvé, projet sain » mais plutôt « ZAP ne teste quasiment pas cette catégorie en mode
automatisé » — une nuance d'interprétation à ne pas perdre lors de la conception de la future page de
restitution (ne pas afficher ces catégories comme s'il s'agissait d'un signal de qualité au même titre
que A01/A03/A05, qui sont elles réellement bien couvertes).

## ❓ Questions encore ouvertes

1. ~~Décision ZAP vs Nuclei~~ — **tranchée** : ZAP retenu (voir section Implémentation ci-dessus), pour
   les raisons déjà documentées (Swagger 2 refusé par Nuclei, SOAP non supporté).
2. ~~Mapping `cweid`/`pluginid` ZAP → catégories OWASP API Top 10 2023~~ — confirmé à 4 reprises
   maintenant (outils gratuits, pages marketing, doc OWASP officielle, registre `scanners.md`) qu'aucune
   source ne le fournit ; non poursuivi, hors périmètre (le module implémenté se limite au Top 10 **web**
   2017/2021, réellement tagué par ZAP).
3. Vérifier la robustesse du parseur WSDL de ZAP sur un WSDL d'application Java réelle du périmètre (pas
   une image de démo) avant de considérer le support SOAP comme acquis en production — toujours ouvert,
   indépendant du chantier d'ingestion.
4. Concevoir l'équivalent d'un `zap-rules.tsv` côté Ma-Moulinette (acquittement de findings connus) et
   décider du compte de test à utiliser pour scanner les routes authentifiées — pertinent seulement si un
   besoin de scan actif authentifié se présente ; non fait, non requis par le périmètre v1.
5. Add-ons alpha non testés (`JWT Support`, `Access Control Testing`, `Attack Surface Detector`, `MCP
   Integration`) — à évaluer en pratique si le besoin qu'ils couvrent devient concret, pas à installer par
   anticipation. `Access Control Testing` prend un relief nouveau vu le constat ci-dessus sur A07.
6. ~~Format de rapport~~ — **tranché** : `traditional-json` (léger), cf. section Implémentation.
7. Page de restitution visuelle (liste des scans, tableau A1-A10, lien depuis la page OWASP comme le
   bouton Dependency-Check) — chantier suivant explicitement différé, à concevoir en tenant compte du
   caveat de couverture inégale ci-dessus.

## 📚 Référence

- Liste source : [OWASP API Security Tools](https://community.owasp.org/api_security_tools) (communauté OWASP).
- [OWASP ZAP — Sécuriser une API REST (tech-insider.org)](https://tech-insider.org/fr/owasp-zap-securiser-api-rest-2026/)
- [OWASP ZAP API Security (oneuptime.com)](https://oneuptime.com/blog/post/2026-01-25-owasp-zap-api-security/view)
- [ZAP — Alert Tags (zaproxy.org)](https://www.zaproxy.org/docs/desktop/addons/common-library/alerttags/) — liste exacte des tags OWASP disponibles (2017/2021/2025 web, pas d'API Top 10).
- [ZAP — Automation Framework (zaproxy.org)](https://www.zaproxy.org/docs/automate/automation-framework/) — liste officielle des types de job.
- [ZAP — Zapping the OWASP Top 10 2021 (zaproxy.org)](https://www.zaproxy.org/docs/guides/zapping-the-top-10-2021/) — révèle la couverture très inégale des catégories par les règles automatisées (A07/A09/A10 faibles).
- [ZAP — docs/scanners.md (GitHub)](https://github.com/zaproxy/zaproxy/blob/main/docs/scanners.md) — registre brut des ~300 règles (pluginid → nom), sans catégorie ni mapping OWASP.
- [OWASP API1:2023 — Broken Object Level Authorization (officiel)](https://api-security.owasp.org/editions/2023/en/0xa1-broken-object-level-authorization/) — section « How to Prevent » générique, sans méthode de test détaillée.
- [42Crunch — OWASP API Top 10](https://42crunch.com/owasp-api-top-10.fr.html) — page marketing, à prendre avec la même précaution (cf. encadré).

-**-- FIN --**-

[Retour au menu principal](/index.html)
