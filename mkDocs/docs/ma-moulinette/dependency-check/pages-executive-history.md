# 🧭 DependencyCheck — Executive summary et Historique

Détail bloc par bloc des 2 pages d'analyse par version : la synthèse orientée décision et l'évolution d'un projet dans le temps. Vue d'ensemble et rôles : [Pages et navigation](pages.md).

Aucun bouton ni section de ces 2 pages n'est conditionné par un rôle Twig (`is_granted(...)`) : la sécurité repose entièrement sur l'attribut de classe `ROLE_SECURITY` et sur le contrôle de périmètre `isProjectAccessible()`. `ROLE_SECURITY_ANALYTICS` n'a aucun effet visible sur ces 2 pages (il ne sert qu'à contourner ce contrôle de périmètre, pas à changer l'affichage).

## 🎯 Executive summary

`GET /dependency-check/projet/{group}/{artifact}/{version}/executive` (`dc_projet_executive`) — `DependencyCheckPageController::executive()`.

### 🧭 Chemin de fer — Executive summary

<!-- markdownlint-disable MD046 -->
```text
Page Executive summary (/dependency-check/projet/{group}/{artifact}/{version}/executive)
│
├── 🧵 Fil d'Ariane : Accueil › Dependency-Check › {artifact} {version} › Vue exécutive
├── 🔔 Zone de messages (flash serveur + messages JS, voir plus bas)
│
├── Si aucun scan → callout warning "Aucun scan trouvé pour ce projet." (fin de page)
└── Sinon, 12 sections dans l'ordre :
       │
       ├── 1. 🎯 Contexte & périmètre
       │      ├── Boutons : Rapport PDF (nouvel onglet) / Dashboard
       │      ├── Callouts : Scan du (+ Engine) | Périmètre (deps analysées)
       │      │      | Socle (lien vers l'executive du scan archétype si connu, sinon "non scanné")
       │      │      | Hors périmètre (OS/JVM/infra, non couvert)
       │      └── Bouton retour : Vue détaillée des findings (page Détail scan)
       │
       ├── 2. Vue globale des vulnérabilités — 4 callouts (Total CVE / Dépendances impactées / CRITICAL / HIGH)
       │
       ├── 3. 📊 Répartition par criticité — donut Chart.js + table Criticité | Nombre | %
       ├── 4. Répartition par famille technologique — barres horizontales + table Famille | Nb CVE
       │      (ou "Aucune donnée à afficher." si vide)
       ├── 5. Top dépendances vulnérables — table Dépendance | Version | Sév. max | CRIT/HIGH/MED/LOW | Total CVE
       ├── 6. Analyse par CWE — radar Chart.js + table CWE | Occurrences
       ├── 7. Vulnérabilités prioritaires par CVSS — barres horizontales + table CVE/produit | CVSS | Sévérité
       │
       ├── 8. Table CVE → Décision → Justification — Dépendance | Version | CVE | Criticité
       │      | Décision (Upgrade/Mitigation/Surveillance) | Justification (tronquée 200 car.)
       │
       ├── 9. 💰 Plan de remédiation chiffré (JH)
       │      ├── Bouton "ℹ️ Méthode de calcul du JH" → ouvre la modale méthodologique
       │      └── Table Dépendance | Version | Sév. max | CRIT/HIGH/MED/LOW | Effort estimé (JH) + ligne TOTAL
       │
       ├── 10. Diff vs scan précédent — 2 sous-blocs, chacun avec le même gabarit
       │      (bandeau évolution nette, callouts Apparues/Disparues/Inchangées, tableaux détail) :
       │      ├── 10.a Comparaison même version (intra-version) — absente si aucun re-scan antérieur
       │      └── 10.b Comparaison version antérieure — absente s'il n'existe aucun scan antérieur
       │
       ├── 11. Top 5 actions recommandées (ou "Aucune action prioritaire identifiée" si vide)
       │
       └── 12. Méthodologie (texte pédagogique statique) : logique Décision, Plan de remédiation
              (2ᵉ bouton vers la modale JH), Diff vs scan précédent

🪟 Modale méthodologique JH (si un scan existe) : formule, 4 paramètres, table effort par famille,
   table bonus par sévérité, 5 exemples calculés figés (log4j-core, cxf-core, spring-core,
   jackson-databind, commons-text), renvoi vers config/packages/dc_remediation.yaml
```
<!-- markdownlint-enable MD046 -->

!!! note "✅ Précision sur le lien « socle » de la cartographie"
    Le lien qui boucle sur `dc_projet_executive` dans la [cartographie des pages](pages.md#-cartographie-des-pages) correspond à la cellule **Socle** de la section 1 (Contexte) : quand un scan archétype/parent-POM est connu, elle ouvre la page exécutive de ce scan archétype.
    La section « Synthèse par socle » qui liste un statut par socle avec un lien « SCAN OK → » vers cette même page se trouve en réalité sur le [Dashboard](pages-dashboard-kpi.md#-dashboard-agrégé), pas sur cette page.

### ⚠️ Messages remontés par la page — Executive summary

| Sévérité | Déclencheur | Message |
| --- | --- | --- |
| `warning` | Aucun scan trouvé pour ce group/artifact/version | ⚠️ Aucun scan trouvé pour {group}:{artifact}:{version}. |
| `error` | Échec de `listForScan()` (récupération des findings) — les findings sont alors vidés, le reste de la page continue à s'afficher | ❌ La récupération des findings a échoué (Erreur {code}). {erreur} |
| *(HTTP 403, pas un flash)* | Couple hors périmètre groupe_fonctionnel (`isProjectAccessible()`) | ⚠️ Projet {group}:{artifact} hors du périmètre groupe_fonctionnel. |
| `critical` (JS) | Le JSON injecté pour les graphiques (`#dc-exec-data`) ne peut pas être parsé | 🔴 [DC executive] dataset JSON parse error (+ détails techniques repliables) |

## 📈 Historique

`GET /dependency-check/projet/{group}/{artifact}/history` (`dc_projet_history`) — `DependencyCheckPageController::history()`. Route déclarée avant `dc_projet` pour que le segment `history` ne soit pas capturé par le paramètre `{version}`.

### 🧭 Chemin de fer — Historique

<!-- markdownlint-disable MD046 -->
```text
Page Historique (/dependency-check/projet/{group}/{artifact}/history)
│
├── 🧵 Fil d'Ariane : Accueil › Dependency-Check › Historique {artifact}
├── 🔔 Zone de messages (flash serveur + messages JS, voir plus bas)
│
├── 🔘 Boutons : Dashboard / Liste projets
│
├── Si aucun scan pour ce couple (group, artifact) → callout warning "Aucun scan trouvé pour
│      ce projet." (fin de page)
└── Sinon :
       ├── 3 callouts de synthèse : Scans cumulés | Versions distinctes | Période couverte
       │
       ├── 📈 Évolution des CVE par sévérité — graphique en ligne (CRITICAL/HIGH/MEDIUM/LOW),
       │      1 point par scan, axe X = date, tooltip enrichi avec la version au survol
       │
       └── 📋 Liste des scans (le plus récent en premier)
              └── Colonnes : Date scan | Version | Deps total | Deps vuln. | CRITICAL | HIGH
                     | MEDIUM | LOW | Actions (Détail / Exec)
                     — chaque cellule de sévérité affiche le delta vs le scan précédent
                       (↓ vert = baisse, ↑ rouge = hausse, "—" pour le premier scan)
```
<!-- markdownlint-enable MD046 -->

### ⚠️ Messages remontés par la page  — Historique

| Sévérité | Déclencheur | Message |
| --- | --- | --- |
| `error` | Échec de `listHistoryForProject()` — l'historique devient vide, la page bascule alors sur le callout "Aucun scan trouvé" en plus du message | ❌  La récupération listHistoryForProject a échoué (Erreur {code}). {erreur} |
| *(HTTP 403, pas un flash)* | Couple hors périmètre groupe_fonctionnel (`isProjectAccessible()`) | ⚠️ Projet {group}:{artifact} hors du périmètre du groupe_fonctionnel. |
| `critical` (JS) | Le JSON injecté pour le graphique (`#dc-history-data`) ne peut pas être parsé | 🔴 [DC History] JSON parse error (+ détails techniques repliables) |

## 📚 Pour aller plus loin

- [Pages et navigation](pages.md) : cartographie d'ensemble, rôles, filtres socle/vue.
- [Index, Détail scan et Export PDF](pages-index-projet.md), [Dashboard et KPI](pages-dashboard-kpi.md), [Comparaison et Mutualisables](pages-comparer-mutualisables.md) : détail des 6 autres pages.
- [Référence](reference.md) : formule de remédiation JH, glossaire.

-**-- FIN --**-

[Retour au menu principal](/index.html)
