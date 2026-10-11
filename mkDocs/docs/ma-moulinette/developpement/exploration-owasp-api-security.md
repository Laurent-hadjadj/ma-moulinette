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

## ❓ Questions ouvertes avant toute décision

1. Pour **ZAP** et **Nuclei** : valider en pratique le format exact du rapport JSON sur un vrai run contre une API de test, et vérifier si les alertes/findings se mappent facilement vers les 10 catégories de l'OWASP API Security Top 10 2023 (`API1:2023` à `API10:2023`), ou s'il faut construire ce mapping soi-même.
2. Pour les quatre outils ⚠️ : confirmer le format de sortie réel, l'activité de maintenance du dépôt (dernier commit, issues ouvertes), et si APIClarity reste activement maintenu.
3. Décider si le périmètre vise un **scan actif** (ZAP/Nuclei/Pynt/Cherrybomb — on pilote l'outil depuis l'extérieur) ou un **scan passif** (Ma-Moulinette) ou une **analyse de trafic passive** (APIClarity — nécessite un point de capture du trafic réel de l'API suivie, architecture plus lourde).

## 📚 Référence

- Liste source : [OWASP API Security Tools](https://community.owasp.org/api_security_tools) (communauté OWASP).

-**-- FIN --**-

[Retour au menu principal](/index.html)
