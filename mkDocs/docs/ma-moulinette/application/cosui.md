# 📅 COSUI — Comité de suivi

Compare la **version courante** d'un projet (dernière ligne de `historique`, triée par date) à sa **version de référence** (ligne marquée `historique.initial = true`, définie depuis la page [Suivi](suivi.md#️-modifier-les-paramètres-dune-version)).
Accessible depuis [Projet](projet.md) via un lien avec la clé Maven encodée.

Aucun rôle métier spécifique n'est requis (`ROLE_UTILISATEUR` suffit — aucun contrôle de rôle explicite dans `CosuiController`, la seule protection vient du pare-feu global).

!!! note "🔓 Pas un vrai jeton signé"
    Comme sur [Répartition](repartition_details.md) et OWASP/Clean Code, le paramètre `token` de l'URL est une simple obfuscation **ROT13 + Base64** de la clé Maven (`CosuiController::decodeToken()`) — il n'y a aucune vérification cryptographique.

## 🗺️ Origine des données

```mermaid
flowchart LR
    Suivi[📈 Suivi<br/>marque une version<br/>initial = true] -.->|version de référence| Cosui[📅 COSUI]
    Repart[🧩 Répartition par module<br/>dernier setup] -.->|répartition présentation/métier| Cosui
    Hist[(historique)] --> Cosui
```

Si aucune version de référence n'a été définie, les valeurs par défaut sont affichées (version « 0.0.0 », notes « F », compteurs à zéro) plutôt qu'une erreur bloquante. De même, si aucune [Répartition par module](repartition_details.md) n'a encore été lancée pour ce projet, le tableau « Répartition des défauts » reste vide (`--`).

!!! note "✅ Avertissement affiché en cas de répartition partielle (2026-07-14)"
    Un bandeau d'avertissement s'affiche désormais sous le `setup` dès que la répartition lue n'est pas complète : `complet (100%)` → rien, `partiel (66%)`/`partiel (33%)` → 66 %/33 %, `inconnue` → 0 %.
    Le calcul (`ProjetCosuiService::controlToPercent()`) est fait une fois pour toutes côté serveur, pas dupliqué en JS.

COSUI est une page **100 % SSR** (server-side rendering) : elle ne fait **aucun appel réseau à SonarQube**, ni côté PHP ni côté JavaScript — tout provient de `historique` et `repartition` en base locale, via `ProjetCosuiService`.

## 🧭 Chemin de fer de la page

<!-- markdownlint-disable MD046 -->
```text
Page COSUI
│
├── 🧵 Fil d'Ariane : Accueil › Projet › COSUI
├── 🔔 Zone de messages (flash serveur uniquement — aucun message JS, voir plus bas)
│
├── 🆔 Setup (dernier cycle de répartition connu pour ce projet)
├── ⚠️ Bandeau « répartition partielle » (si control ≠ complet 100 %)
├── 🔀 Interrupteur « Afficher les variations ? » (flèches ▲/▼/= , purement client)
│
├── 📊 Tableau de comparaison des notes
│        ├── Colonne « Application référence » (+ bouton ℹ️ → modale de détail)
│        ├── Maintenabilité / Fiabilité / Vulnérabilité / Hotspot
│        └── Bloquant / Critique / Majeur / Note (référence vs courante)
│
├── 🧩 Tableau « Répartition des défauts » (version courante uniquement)
│        └── Maintenabilité / Fiabilité / Vulnérabilité × Métier / Présentation × Bloquant/Critique/Majeur
│
├── 🕸️ Graphique radar (6 axes, référence vs courante)
│
└── 🪟 Modale « Projet de référence » (déjà chargée en SSR, pas de nouvel appel réseau)
```
<!-- markdownlint-enable MD046 -->

## 📊 Tableau de comparaison des notes

Une colonne « référence » et une colonne « courante », avec pour chacune : version, date, notes de fiabilité/sécurité/maintenabilité/hotspot (badge coloré A-F), et le détail bloquant/critique/majeur par catégorie (bug, vulnérabilité, mauvaise pratique).
Une icône ℹ️ à côté de la date de référence ouvre une fenêtre récapitulant en détail cette version de référence (les données sont déjà chargées côté serveur, aucun nouvel appel réseau).

Un interrupteur **« Afficher les variations ? »** affiche/masque, purement côté navigateur (aucun rappel serveur), des flèches de tendance (▲/▼/=) à côté de chaque compteur, comparant référence et version courante.

## 🧩 Répartition des défauts

Tableau croisé Maintenabilité/Fiabilité/Vulnérabilité × Présentation/Métier × Bloquant/Critique/Majeur — **pour la version courante uniquement** (pas de comparaison référence/courante ici, contrairement au tableau du dessus), alimenté par le dernier cycle de la page [Répartition par module](repartition_details.md).

## 🕸️ Graphique radar

Compare visuellement référence et version courante sur 6 axes : fiabilité, vulnérabilité, hotspots, maintenabilité, couverture de tests, dette technique (ratio inversé). Les notes lettres sont converties en points sur 100 pour permettre le tracé (`note2point()` côté serveur).

!!! note "✅ Seuils du tooltip Hotspot alignés sur `note2point()` (2026-07-14)"
    Le serveur (`ProjetCosuiService::note2point()`) utilise des seuils fixes (A=100/B=80/C=60/D=30/E=10/F=0) pour construire les points du radar — et `construireRadarChart()` convertit bien l'axe **Hotspot** via cette même grille, au même titre que Fiabilité/Vulnérabilité/Maintenabilité.
    Les axes **Couverture** et **Dette** restent une bucketisation JS purement indicative (le serveur n'y calcule pas de note lettre, seulement une valeur numérique), donc rien à aligner pour eux.

## ⚠️ Messages remontés par la page

COSUI n'utilise jamais `showMessage()` côté JavaScript (`index-cosui.js` ne contient aucun appel) — la page ne fait aucun appel AJAX propre, donc **tous les messages viennent du flash serveur** au chargement (`CosuiController::projetCosui()`).

| Sévérité | Déclencheur | Message |
| --- | --- | --- |
| `error` | Paramètre `token` absent de l'URL | ❌ La requête est incorrecte (Erreur 400). |
| `error` | Décodage du token en échec (base64/format invalide) | ❌ La requête est incorrecte (Erreur 400). |
| *(dynamique, relayé du service)* | `ProjetCosuiService::generateRender()` retourne un code ≠ 200 (ex. aucune donnée de référence en base, échec de lecture du `setup`) | Le type et le message renvoyés par le service sont affichés tels quels (préfixés ❌) |
| `critical` | Exception `RuntimeException` inattendue pendant la génération | 🔴 Erreur lors de la génération COSUI. |

## 📚 Pour aller plus loin

- [Projet](projet.md) — point d'entrée vers COSUI.
- [Suivi](suivi.md) — définit la version de référence (`historique.initial = true`) et l'historique complet.
- [Répartition détaillée](repartition_details.md) — alimente le tableau « Répartition des défauts ».
- [Gestion de la sécurité](../developpement/securite.md) — détail des rôles.

-**-- FIN --**-

[Retour au menu principal](/index.html)
