# 🧭 DependencyCheck — Comparaison et Mutualisables

Détail bloc par bloc des 2 pages de comparaison et de mutualisation cross-projets. Vue d'ensemble et rôles : [Pages et navigation](pages.md).

Sur ces 2 pages, aucun bouton n'est conditionné par un `is_granted(...)` Twig : la sécurité repose sur l'attribut de classe `ROLE_SECURITY` et sur `isProjectAccessible()` (anti-IDOR) pour la sélection des applications.

## ⚖️ Comparaison

`GET /dependency-check/comparer` (`dc_comparer`) — `DependencyCheckPageController::comparer()`.

### 🧭 Chemin de fer — Comparaison

<!-- markdownlint-disable MD046 -->
```text
Page Comparer (/dependency-check/comparer)
│
├── 🧵 Fil d'Ariane : Accueil › Dependency-Check › Comparer
├── 🔔 Zone de messages (flash serveur uniquement — aucun message JS, voir plus bas)
│
├── 🏷️ Badge périmètre : "Vue globale" ou "Périmètre : {groupes}"
├── 🔀 Bascule Pilotage (prod) / Suivi (dev)
│
├── 🔎 Formulaire (GET) : 4 sélecteurs d'application (1er et 2e requis, 3e et 4e optionnels)
│      + bouton "Comparer" + lien "Réinitialiser" (si ≥2 apps déjà sélectionnées)
│
├── Si moins de 2 applications sélectionnées → callout warning "Sélectionnez au moins 2
│      applications..." + précision sur le filtrage automatique par périmètre
└── Sinon (≥2 applications sélectionnées) :
       ├── 📊 Synthèse comparée — tableau 1 colonne par application :
       │      Score sécu | Sévérité max | Deps total | Deps vuln. | CVE total
       │      | CRITICAL/HIGH/MEDIUM/LOW | Socle | Archétype | Date du scan
       │
       └── 🔁 CVE communes (N) — table triable (DataTables) :
              CVE | Sévérité | CVSS | Nb applications | 1 colonne ✓/— par application sélectionnée
              (vide → "Aucune CVE n'est partagée entre les applications sélectionnées.")
```
<!-- markdownlint-enable MD046 -->

Sélection des applications : formulaire GET propageant `?apps[]=group:artifact:version` (jusqu'à 4). Un triplet malformé ou hors périmètre (`isProjectAccessible()`) est silencieusement écarté, sans message dédié ; au-delà de 4 applications valides, seules les 4 premières sont retenues, également sans message.

### ⚠️ Messages remontés par la page  — Comparaison

Aucun appel `showMessage()` dans `index-dependency-check-comparer.js` (le seul rôle du script est d'initialiser la DataTable des CVE communes) ; aucune des conditions « moins de 2 apps » ou « application inconnue » ne déclenche de flash — elles sont gérées uniquement par l'affichage conditionnel du template.

| Sévérité | Déclencheur | Message |
| --- | --- | --- |
| `error` | Échec de `listLatestPerCoupleForView()` (chargement des applications disponibles dans les sélecteurs) | ❌ La récupération listLatestPerCoupleForView a échoué (Erreur {code}). {erreur} |
| `error` | Échec de `findCommonCvesForScans()` (calcul des CVE communes) | ❌ La récupération findCommonCvesForScans a échoué (Erreur {code}). {erreur} |

## 🔗 Mutualisables

`GET /dependency-check/mutualisables` (`dc_mutualisables`) — `DependencyCheckPageController::mutualisables()`.

### 🧭 Chemin de fer — Mutualisables

<!-- markdownlint-disable MD046 -->
```text
Page Mutualisables (/dependency-check/mutualisables)
│
├── 🧵 Fil d'Ariane : Accueil › Dependency-Check › Dashboard › Mutualisables
├── 🔔 Zone de messages (flash serveur uniquement — aucun message JS, voir plus bas)
│
├── 🏷️ Badge périmètre : "Vue globale" ou "Périmètre : {groupes}"
├── 🔀 Bascule Pilotage (prod) / Suivi (dev) (préserve le filtre socle courant)
│
├── 🔎 Formulaire de filtres (auto-submit) :
│      ├── Select Socle ("— Tous les socles —" ou un socle précis) + lien Effacer
│      └── Case à cocher "Masquer VIA SOCLE (déjà mutualisées par héritage)"
│
├── Compteur : "{N} dépendance(s) mutualisable(s)" (+ précisions filtre actif)
├── Si la limite de 5000 lignes est atteinte → callout warning dédié (voir note ci-dessous)
├── Bouton "Méthode de calcul du JH" (toujours visible) → modale méthodologique partagée
│
├── Si aucune dépendance mutualisable dans le périmètre → callout info dédié (fin)
└── Sinon → table triable (DataTables, 10/page) :
       Dépendance | Sévérité max | Type (Convergence / Via socle) | Nb projets | Nb CVE
       | JH unit. | Gain mutu. | Utilisée par (jusqu'à 5 badges projet, "+N autres" au-delà ;
       badge non cliquable si le projet est hors périmètre de l'utilisateur)
│
└── Lien retour "⬅️ Retour au dashboard"

🪟 Modale méthodologique JH (formule, paramètres, exemples) — partagée avec Executive/Dashboard
```
<!-- markdownlint-enable MD046 -->

Filtre socle : `?socle=parent_label@parent_version` (alias rétrocompatible `?archetype=`), validé contre la liste des socles réellement référencés. Filtre « Masquer VIA SOCLE » : post-filtrage PHP après l'agrégation SQL (`?hide_via_socle=1`).

!!! note "✅ Correction : signal désormais visible en cas de troncature au-delà de 5000 dépendances"
    La requête d'agrégation (`DcDependencyRepository::topMutualisableDependencies()`) applique une limite dure de 5000 lignes (triées par nombre de projets puis de CVE décroissant).
    La méthode récupère désormais une ligne supplémentaire (5001) pour détecter si le parc dépasse la limite, et renvoie un indicateur `truncated`. Si la limite est atteinte, la page affiche un callout d'avertissement invitant à affiner le filtre par socle pour réduire le périmètre — auparavant, la page affichait silencieusement 5000 lignes sans aucun signal indiquant que des dépendances mutualisables supplémentaires existaient au-delà.

### ⚠️ Messages remontés par la page

Aucun appel `showMessage()` dans `index-dependency-check-mutualisables.js` (le script initialise uniquement la DataTable et l'ouverture/fermeture de la modale JH).

| Sévérité | Déclencheur | Message |
| --- | --- | --- |
| `error` | Échec de `listLatestPerCoupleForView()` (construction des socles disponibles et du périmètre) | ❌ La récupération listLatestPerCoupleForView a échoué (Erreur {code}). {erreur} |
| `error` | Échec de `topMutualisableDependencies()` (requête d'agrégation) | ❌ La récupération topMutualisableDependencies a échoué (Erreur {code}). {erreur} |

## 📚 Pour aller plus loin

- [Pages et navigation](pages.md) : cartographie d'ensemble, rôles, filtres socle/vue.
- [Index, Détail scan et Export PDF](pages-index-projet.md), [Executive summary et Historique](pages-executive-history.md), [Dashboard et KPI](pages-dashboard-kpi.md) : détail des 6 autres pages.
- [Référence](reference.md) : formule de remédiation JH, glossaire.

-**-- FIN --**-

[Retour au menu principal](/index.html)
