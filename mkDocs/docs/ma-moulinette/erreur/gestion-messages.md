# 💬 Gestion des messages utilisateurs

Depuis la v2.0.0, les messages destinés aux utilisateurs suivent une convention commune, qu'ils soient générés côté serveur (PHP/Symfony) ou côté client (JavaScript/jQuery).

## 🏷️ Types de messages

| Type | Icône | Usage |
| --- | --- | --- |
| `success` | ✅ | Action réussie |
| `info` | ℹ️ | Information neutre |
| `warning` | ⚠️ | Point d'attention non bloquant |
| `error` / `alert` | ❌ | Erreur |
| `critical` | 🔴 | Erreur grave |
| `debug` | 🛠️ | Information de diagnostic (dev) |
| `primary` / `secondary` | 📌 / 📄 | Message neutre stylé |

!!! note "🗑️ Deux types définis mais inatteignables via `showMessage()`"
    Le dictionnaire d'icônes de `messageHelper.js` définit aussi `delete` (🗑️) et `notAuthorize` (🚫), mais ces deux valeurs ne figurent pas dans la liste `allowedTypes` de `showMessage()` — un appel avec l'un de ces types retomberait donc sur `critical`. Aucun appel `showMessage('delete', ...)`/`showMessage('notAuthorize', ...)` n'existe dans le code actuel : ce sont des entrées résiduelles, pas des types utilisables aujourd'hui.

## 🖥️ Messages flash côté Symfony

Convention utilisée par la majorité des controllers applicatifs : un seul type de flash Symfony (`notice`), portant un tableau structuré `type`/`message` :

```php
$this->addFlash('notice', ['type' => 'success', 'message' => 'La collecte des indicateurs a bien été enregistrée.']);
$this->addFlash('notice', ['type' => 'warning', 'message' => 'La version sélectionnée ne dispose pas de toutes les métriques.']);
$this->addFlash('notice', ['type' => 'error',   'message' => 'Impossible de contacter le serveur SonarQube (HTTP 503).']);
```

Ces messages sont affichés par le partial commun `templates/_message.html.twig`, inclus dans la base template. En environnement `dev`, un message peut porter une clé `debug` supplémentaire affichée sous le message principal.

!!! note "⚠️ Deux exceptions à cette convention"
    - `AccueilController` utilise `addFlash('info', ['type' => 'primary', 'message' => ...])` — un type de flash différent (`info` au lieu de `notice`), même structure `type`/`message`.
    - Les contrôleurs CRUD EasyAdmin (`GroupeUtilisateurCrudController`, `PortefeuilleCrudController`, `GroupeFonctionnelCrudController`) utilisent `addFlash('danger', 'Message brut')` — type `danger` et message en simple chaîne, affichés par le layout natif d'EasyAdmin plutôt que par `_message.html.twig`.
    - Cohérent fonctionnellement (EasyAdmin a son propre mécanisme de flash), mais hors de la convention `notice`/`type`/`message` décrite ci-dessus.

## 🧩 Messages JS

Le helper `assets/js/common/messageHelper.js` centralise l'affichage :

```javascript
import { showMessage, hideMessage } from '../../common/messageHelper.js';

showMessage('success', 'La collecte a été réalisée avec succès.');
showMessage('error', 'Une erreur est survenue.', detailsTechniquesOptionnelles);
hideMessage();
```

`showMessage(type, message, technicalDetails = null)` applique automatiquement l'icône et la classe CSS correspondant au type (repli sur `critical` si le type n'est pas reconnu), et adapte l'attribut ARIA `role` (`alert` pour les types `alert`/`error`/`critical`, `status` sinon).

## 🈁 Localisation

Seule la langue **française** est actuellement fournie (`translations/*.fr.yml`) — pas de traduction anglaise disponible à ce jour.

## ✅ Messages d'erreur des formulaires

Les messages générés par les contraintes de validation Symfony (`Assert\...`) sont affichés via `form_errors(...)`, sous forme d'une simple liste sous le champ concerné — sans classe CSS dédiée (pas de `is-invalid`/`invalid-feedback` dans le projet).

-**-- FIN --**-

[Retour au menu principal](/index.html)
