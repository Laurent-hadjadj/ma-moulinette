# 🧭 DependencyCheck — Index, Détail scan et Export PDF

Détail bloc par bloc des 3 pages qui forment le socle de navigation du module : la liste des projets scannés, le détail d'un scan, et son export PDF. Vue d'ensemble et rôles : [Pages et navigation](pages.md).

## 📦 Index — liste des projets scannés

`GET /dependency-check` (`dc_index`) — `DependencyCheckPageController::index()`.

### 🧭 Chemin de fer — liste des projets scannés

<!-- markdownlint-disable MD046 -->
```text
Page Index (/dependency-check)
│
├── 🧵 Fil d'Ariane : Accueil › Dependency-Check
├── 🔔 Zone de messages (flash serveur uniquement — aucun message JS, voir plus bas)
│
├── 🔘 Barre de boutons (inconditionnels) : Dashboard / Mutualisables / KPIs / Comparer / Analyse OWASP
├── 🔀 Bascule « Pilotage (prod) » / « Suivi (dev) » (partiel _vue_toggle.html.twig)
│      └── callout explicatif, texte différent selon le mode actif
│
├── Si aucun projet scanné → callout "Aucun projet n'a encore été scanné."
└── Sinon → tableau (pas de DataTables/pagination sur cette page)
       ├── Colonnes : Projet | Version | Deps total | Deps vuln. | CRITICAL | HIGH | MEDIUM | LOW | Scan date | Action
       ├── Badge "❓ Pas de release ingérée" (vue prod uniquement, si l'application n'a aucune release ingérée)
       ├── Compteurs de sévérité affichés seulement si > 0 (cellule vide sinon)
       └── Bouton « Détail » par ligne → page Détail scan
```
<!-- markdownlint-enable MD046 -->

### ⚠️ Messages remontés par la page — liste des projets scannés

Aucun appel `showMessage()` dans `index-dependency-check.js` — tous les messages de cette page viennent du flash serveur.

| Sévérité | Déclencheur | Message |
| --- | --- | --- |
| `info` | Utilisateur sans groupe fonctionnel valide (`allowedMavenKeys()` renvoie `null`) | ℹ️ Aucun projet accessible dans votre périmètre groupe_fonctionnel. |
| `error` | Échec de `listLatestPerCoupleForView()` | ❌ La récupération des projets a échoué (Erreur {code}). {erreur} |

## 🔍 Page détail  — scan

`GET /dependency-check/projet/{group}/{artifact}/{version}` (`dc_projet`) — `DependencyCheckPageController::projet()`.
Utilise le même point d'entrée JS que l'Index (`index-dependency-check.js`), qui ne contient aucune logique conditionnelle propre à l'une ou l'autre page.

### 🧭 Chemin de fer — scan

<!-- markdownlint-disable MD046 -->
```text
Page Détail scan (/dependency-check/projet/{group}/{artifact}/{version})
│
├── 🧵 Fil d'Ariane : Accueil › Dependency-Check › {artifact} {version}
├── 🔔 Zone de messages (flash serveur uniquement)
│
├── Si un scan existe pour ce triplet → barre de boutons :
│      ├── 🎯 Vue exécutive
│      ├── 📄 Rapport PDF (nouvel onglet)
│      ├── 📊 Dashboard
│      ├── 📈 Historique
│      └── 🔗 Mutualisables (filtré sur le socle du scan si connu, sinon non filtré)
│
├── 4 callouts de synthèse : Scan du (+ Engine) | Dépendances (vuln./total) | CVE détectées (total) | Critiques (CRITICAL/HIGH)
│      (affichés même sans scan, avec la valeur "---")
│
├── 📊 Répartition par sévérité : 5 compteurs (CRITICAL/HIGH/MEDIUM/LOW/INFO), pas de graphique sur cette page
│
└── 🔎 Liste des CVE détectées ({{ findings|length }})
       ├── Si vide → "Aucune CVE détectée."
       └── Sinon, un bloc par sévérité présente, ordre fixe CRITICAL → HIGH → MEDIUM → LOW → INFO → UNKNOWN
              └── Colonnes : CVSS | CVE | Dépendance | Version | CWE | Description (tronquée à 200 caractères)
```
<!-- markdownlint-enable MD046 -->

### ⚠️ Messages remontés par la page — scan

| Sévérité | Déclencheur | Message |
| --- | --- | --- |
| *(HTTP 403, pas un flash)* | Couple `group:artifact` hors du périmètre `groupe_fonctionnel` (`isProjectAccessible()`, contourné si `ROLE_SECURITY_ANALYTICS`) | ⚠️ Projet {group}:{artifact} hors du périmètre groupe_fonctionnel. |
| `warning` | Aucun scan trouvé pour ce group/artifact/version | ⚠️ Aucun scan trouvé pour {group}:{artifact}:{version}. Vérifier que la CI a bien envoyé un rapport DependencyCheck pour cette version. |
| `error` | Échec de `listForScan()` (récupération des findings) | ❌ La récupération des findings a échoué (Erreur {code}). {erreur} |

Aucun appel `showMessage()` côté JS sur cette page (même script que l'Index, sans logique conditionnelle).

## 📄 Export PDF

`GET /dependency-check/projet/{group}/{artifact}/{version}/pdf` (`dc_projet_pdf`) — `DependencyCheckPageController::projetPdf()`, délègue à `PdfExportService::generateDcPdf()`.
Pas de template HTML : réponse binaire directe (`Content-Type: application/pdf`).

### 🧭 Chemin de fer — Export PDF

<!-- markdownlint-disable MD046 -->
```text
Export PDF (/dependency-check/projet/{group}/{artifact}/{version}/pdf)
│
├── 📝 Sous-PDF 1 (portrait) : page de garde
│      └── Si un scan existe → page "Synthèse executive" (saut de page)
│             ├── Bandeau "⚠️ Données incomplètes" (si la récupération des findings a échoué)
│             ├── Identification (projet / group / version / date du scan / engine)
│             ├── Synthèse globale (dépendances analysées/vulnérables, total CVE)
│             ├── Répartition par sévérité (CRITICAL/HIGH/MEDIUM/LOW/INFO)
│             ├── Top N dépendances vulnérables
│             └── Top N familles CWE (saut de page)
│
├── 📝 Sous-PDF 2 (paysage) : détail des vulnérabilités — généré uniquement si des findings existent
│      └── une section par sévérité présente, ordre fixe CRITICAL → HIGH → MEDIUM → LOW → INFO → UNKNOWN
│             └── Colonnes : # | CVSS | CVE | Dépendance | Version | CWE | Description (tronquée à 350 caractères)
│
├── 📝 Sous-PDF 3 (portrait) : glossaire — toujours généré, même sans scan
│      └── CVE, CWE, CVSS (+ barème 0-10 → sévérité), vecteur/complexité d'attaque, exploitabilité,
│          OWASP DependencyCheck, sévérité d'un finding, mutualisation cross-projets
│
└── 🚀 Fusion FPDI (mergeDcSubPdfs) : orientation native préservée par page, pied de page commun
       ("Généré le {date}" | type de document | "Page {n}/{nb}"), filigrane diagonal sur toutes les
       pages sauf la page de garde (activé par défaut si le type de document contient "confidentiel")
```
<!-- markdownlint-enable MD046 -->

### ⚠️ Messages remontés par la page

| Sévérité | Déclencheur | Message |
| --- | --- | --- |
| *(HTTP 403, pas un flash)* | Couple hors périmètre groupe_fonctionnel (`isProjectAccessible()`) | ⚠️ Projet {group}:{artifact} hors du périmètre groupe_fonctionnel. |
| `warning` (redirection) | Aucun scan trouvé pour cette version | ⚠️ Aucun scan trouvé pour {group}:{artifact}:{version}. Impossible de générer le rapport PDF. |
| *(bandeau dans le PDF)* | Échec de `listForScan()` (récupération des findings) | ⚠️ Données incomplètes. La récupération des vulnérabilités a échoué... (affiché en tête de la synthèse executive du PDF) |

!!! note "✅ Correction : signal désormais visible en cas de scan absent ou de findings non récupérés"
    `projetPdf()` ne renvoyait auparavant aucun signal dans ces deux cas : un scan absent produisait un PDF silencieux (garde + glossaire seuls), et un échec de récupération des findings n'était visible que dans les logs serveur.
     Un message flash ne peut pas s'afficher sur cette route (la réponse est un fichier binaire, pas une page HTML) : en l'absence de scan, la page redirige donc désormais vers le Détail scan avec le flash d'avertissement habituel ; en cas d'échec de récupération des findings, le PDF est toujours généré mais affiche un bandeau d'avertissement en tête de la synthèse executive.

## 📚 Pour aller plus loin

- [Pages et navigation](pages.md) : cartographie d'ensemble, rôles, filtres socle/vue.
- [Executive summary et Historique](pages-executive-history.md), [Dashboard et KPI](pages-dashboard-kpi.md), [Comparaison et Mutualisables](pages-comparer-mutualisables.md) : détail des 6 autres pages.
- [Architecture d'ingestion](architecture.md) : pipeline, décisions techniques.
- [Référence](reference.md) : formule de remédiation JH, glossaire.

-**-- FIN --**-

[Retour au menu principal](/index.html)
