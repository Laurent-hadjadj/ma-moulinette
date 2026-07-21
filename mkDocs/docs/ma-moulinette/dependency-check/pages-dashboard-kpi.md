# 🧭 DependencyCheck — Dashboard et KPI

Détail bloc par bloc des 2 pages d'agrégation cross-projets. Vue d'ensemble et rôles : [Pages et navigation](pages.md).

Sur ces 2 pages, aucun bouton n'est conditionné par un `is_granted(...)` Twig : toutes les variations d'affichage viennent de variables déjà calculées côté contrôleur (`analytics_mode`, `available_groupes`, `available_socles`).

## 📊 Dashboard agrégé

`GET /dependency-check/dashboard` (`dc_dashboard`) — `DependencyCheckPageController::dashboard()`.

### 🧭 Chemin de fer — Dashboard

<!-- markdownlint-disable MD046 -->
```text
Page Dashboard (/dependency-check/dashboard)
│
├── 🧵 Fil d'Ariane : Accueil › Dependency-Check › Dashboard
├── 🔔 Zone de messages (flash serveur + messages JS, voir plus bas)
│
├── 🏷️ Badge périmètre : "Vue globale" (ROLE_SECURITY_ANALYTICS) ou "Périmètre : {groupes}"
├── 🔀 Bascule Pilotage (prod) / Suivi (dev) (_vue_toggle.html.twig)
├── 🔎 Formulaire de filtres (affiché seulement si au moins un socle ou ≥2 groupes fonctionnels
│      disponibles) :
│      ├── Select Socle (auto-submit) + lien Effacer — masqué si aucun socle référencé
│      └── Select Groupe (auto-submit) + lien Effacer — affiché seulement si l'utilisateur a
│             ≥2 groupes fonctionnels ; toujours masqué en mode analytics (périmètre déjà global)
│
├── Si aucune donnée à agréger → callout info "Aucune donnée à agréger pour le moment." (fin)
└── Sinon, dans l'ordre :
       │
       ├── 1. 🎯 Synthèse cumulée (N projets) — 4 callouts CRITICAL/HIGH/MEDIUM/LOW,
       │       chacun avec le nombre d'applications impactées
       │
       ├── 2. 🆕 Nouvelles CVE CRITICAL depuis le dernier scan
       │       ├── Si vide → "Aucune nouvelle CVE CRITICAL depuis le scan précédent. 🎉"
       │       └── Sinon → table (CVE, CVSS, Nb apps, Dernière apparition, bouton "Voir apps")
       │              → ouvre une modale dédiée par CVE (application, version, socle, dépendance,
       │                date scan, badge "1ère analyse" si c'est la première fois qu'elle apparaît)
       │
       ├── 3. 🎯 Top du parc (mode analytics) / Mon périmètre (sinon)
       │       ├── Top 5 projets les plus exposés (CRITICAL/HIGH + bouton Détail)
       │       └── Top 10 CVE CRITICAL (CVSS, nb projets touchés, badges projets)
       │
       ├── 4. 🗺️ Cartographie projets × sévérité max — table triable (DataTables) :
       │       Projet | Sévérité max | Nb CVE | Socle | Dernier scan | Action
       │
       ├── 5. 🏗️ Synthèse par socle — table : Socle | Statut scan ("SCAN OK →" vers la page
       │       Executive du scan archétype, ou "SCAN MISSING") | Nb apps | CRIT/HIGH/MED/LOW | Sév. max
       │
       ├── 6. 🧩 Distribution archétype × socle — table : Socle (+ "⚠ fragmenté" si plusieurs
       │       archétypes coexistent) | Archétype | Nb apps | Applications
       │
       ├── 7. 🔗 Mutualisations possibles (top 10 par gain)
       │       ├── Bouton "Méthode de calcul du JH" → modale méthodologique partagée
       │       ├── Table : Dépendance | Sévérité max | Type (Convergence/Via socle) | Nb projets
       │       │      | Nb CVE | JH unit. | Gain mutu.
       │       └── Lien "Voir tous les mutualisables →" (préserve le filtre socle courant)
       │
       ├── 8. 🚨 CVE CRITICAL/HIGH touchant ≥ 2 projets — table paginée (Knp, 25/page) :
       │       Sévérité | CVSS | CVE | Nb projets | Projets (5 badges + compteur)
       │
       ├── 9. 📦 Top 20 dépendances vulnérables — table : Dépendance | Version | Vendor
       │       | Nb projets | Nb CVE
       │
       ├── 10. 🧫 Top 15 CWE par nombre de projets impactés — table : CWE | Nb projets | Projets
       │
       └── 11. 📊 Visualisations — 4 graphiques Chart.js (détail ci-dessous)

🪟 Modale méthodologique JH (formule, paramètres, exemples) — partagée avec Executive/Mutualisables
```
<!-- markdownlint-enable MD046 -->

### 📊 Les 4 graphiques du Dashboard

| # | Type | Données tracées |
| --- | --- | --- |
| Évolution 30 jours | Ligne | 4 séries CRITICAL/HIGH/MEDIUM/LOW, 1 point par jour sur les 30 derniers jours |
| Cartographie projets | Bulles | 1 bulle par scan : X = dépendances totales, Y = total CVE, taille = nb CVE CRITICAL |
| Top 15 projets | Barres empilées | Les 15 projets avec le plus de CVE, 4 séries empilées CRITICAL/HIGH/MEDIUM/LOW |
| Répartition globale | Donut | Somme des 4 sévérités sur tout le périmètre visible, % masqué sous 5 % |

Chaque graphique affiche un texte de repli directement sur le canvas si son jeu de données est vide (« Aucune donnée sur les 30 derniers jours », etc.) — ce n'est pas un message `showMessage()`.

### 🎚️ Effet de `ROLE_SECURITY_ANALYTICS` sur cette page — Dashboard

1. Badge périmètre : « Vue globale » au lieu de « Périmètre : {groupes} ».
2. Toutes les requêtes d'agrégation portent sur tout le parc, pas seulement les projets de l'utilisateur.
3. Le select « Filtrer par groupe » disparaît (le mode analytics ignore la notion de groupe fonctionnel).
4. Le titre de la section 3 devient « Top du parc » au lieu de « Mon périmètre ».
5. Aucune colonne supplémentaire n'apparaît dans les tableaux — uniquement un élargissement du périmètre de données et des libellés.

### ⚠️ Messages remontés par la page — Dashboard

Chaque source de données agrégée échoue indépendamment (une table vide n'empêche pas les autres de s'afficher) ; tous les flashs suivent le même gabarit `"La récupération {méthode} a échoué (Erreur {code}). {erreur}"` :

| Sévérité | Déclencheur | Message |
| --- | --- | --- |
| `error` | Échec de `listLatestPerCoupleForView()` | ❌ La récupération listLatestPerCoupleForView a échoué (Erreur {code}). {erreur} |
| `error` | Échec de `countProjectsByCwe()` | ❌ La récupération countProjectsByCwe a échoué (Erreur {code}). {erreur} |
| `error` | Échec de `topVulnerableDependencies()` | ❌ La récupération topVulnerableDependencies a échoué (Erreur {code}). {erreur} |
| `error` | Échec de `findCriticalCvesAcrossProjects()` | ❌ La récupération findCriticalCvesAcrossProjects a échoué (Erreur {code}). {erreur} |
| `error` | Échec de `listTopCriticalCves()` | ❌ La récupération listTopCriticalCves a échoué (Erreur {code}). {erreur} |
| `error` | Échec de `topMutualisableDependencies()` | ❌ La récupération topMutualisableDependencies a échoué (Erreur {code}). {erreur} |
| `error` | Échec de `listNewCriticalCvesGroupedByCve()` | ❌ La récupération listNewCriticalCvesGroupedByCve a échoué (Erreur {code}). {erreur} |
| `error` | Échec de `aggregateSeverityByDay()` (graphique 30 jours) | ❌ La récupération aggregateSeverityByDay a échoué (Erreur {code}). {erreur} |
| `critical` (JS) | Le JSON injecté pour les 4 graphiques (`#dc-dashboard-data`) ne peut pas être parsé | 🔴 [DC dashboard] dataset JSON parse error (+ détails techniques repliables) |

## 📈 Pilotage SLA (KPI)

`GET /dependency-check/kpi` (`dc_kpi`) — `DependencyCheckPageController::kpi()`.

### 🧭 Chemin de fer — SLA

<!-- markdownlint-disable MD046 -->
```text
Page KPI (/dependency-check/kpi)
│
├── 🧵 Fil d'Ariane : Accueil › Dependency-Check › KPIs
├── 🔔 Zone de messages (flash serveur + messages JS, voir plus bas)
│
├── 🏷️ Badge périmètre : "Vue globale" ou "Périmètre : {groupes}" (même logique que le Dashboard)
├── 🔘 Boutons : Dashboard / Mutualisables
├── 🔀 Bascule Pilotage (prod) / Suivi (dev) — sans filtre socle/groupe sur cette page
│
├── Si aucun scan dans le périmètre → callout warning "Aucun projet dans votre périmètre." (fin)
└── Sinon :
       ├── Callout méthodologique : formule du score sécu + poids/plafond par sévérité
       │
       ├── 4 cartes KPI :
       │      ├── Score sécu moyen (/100)
       │      ├── % applications « zéro CRITICAL »
       │      ├── % scans de moins de 30 jours
       │      └── Nombre d'applications scannées dans le périmètre actuel
       │
       ├── 📈 Tendance CVE sur 90 jours — graphique en ligne, 4 séries CRITICAL/HIGH/MEDIUM/LOW
       │      (agrégation brute par jour, non dédupliquée, non filtrée par vue prod/dev)
       │
       └── 2 tableaux côte à côte :
              ├── 🔥 Top 5 applications les plus à risque (score le plus bas, tri secondaire : total CVE décroissant)
              └── ✅ Top 5 applications les plus saines (score le plus haut, tri secondaire : total CVE croissant)
```
<!-- markdownlint-enable MD046 -->

### 🧮 Calcul du score sécurité affiché

`Score = max(0, 100 − (10×min(CRITICAL,5) + 3×min(HIGH,10) + 1×min(MEDIUM,20) + 0,5×min(LOW,20)))`, calculé par application (`DcExecutiveAnalyticsService::computeScoreSecu()`).
Poids et plafonds par sévérité sont surchargeables via `config/packages/dc_remediation.yaml` — pénalité maximale cumulée par défaut : 100 (score plancher 0).

### 🎚️ Effet de `ROLE_SECURITY_ANALYTICS` sur cette page — SLA

Même mécanique que le Dashboard : badge « Vue globale » + les 4 cartes, le graphique 90 jours et les 2 tops sont calculés sur tout le parc au lieu du périmètre `groupe_fonctionnel`.
Cette page n'a ni filtre socle ni filtre groupe (contrairement au Dashboard) : le seul levier de scope est `ROLE_SECURITY_ANALYTICS` + la bascule prod/dev.

### ⚠️ Messages remontés par la page — SLA

| Sévérité | Déclencheur | Message |
| --- | --- | --- |
| `error` | Échec de `listLatestPerCoupleForView()` | ❌ La récupération listLatestPerCoupleForView a échoué (Erreur {code}). {erreur} |
| `error` | Échec de `aggregateSeverityByDay()` (tendance 90 jours) | ❌ La récupération aggregateSeverityByDay a échoué (Erreur {code}). {erreur} |
| `critical` (JS) | Le JSON injecté pour le graphique (`#dc-kpi-data`) ne peut pas être parsé | 🔴 [DC KPI] JSON parse error (+ détails techniques repliables) |

## 📚 Pour aller plus loin

- [Pages et navigation](pages.md) : cartographie d'ensemble, rôles, filtres socle/vue.
- [Index, Détail scan et Export PDF](pages-index-projet.md), [Executive summary et Historique](pages-executive-history.md), [Comparaison et Mutualisables](pages-comparer-mutualisables.md) : détail des 6 autres pages.
- [Référence](reference.md) : formule de remédiation JH, glossaire.

-**-- FIN --**-

[Retour au menu principal](/index.html)
