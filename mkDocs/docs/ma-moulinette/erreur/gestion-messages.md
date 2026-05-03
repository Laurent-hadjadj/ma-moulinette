# Gestion des messages utilisateurs

![Ma-Moulinette](../../assets/images/home/home-000.jpg)

La version **2.0.0** de **Ma-Moulinette** introduit une **normalisation des messages** destinés aux utilisateurs, qu'ils soient générés côté serveur (PHP / Symfony) ou côté client (JavaScript / jQuery).

L'objectif : proposer une expérience cohérente entre les **messages flash**, les **notifications asynchrones** et les **erreurs HTTP**.

## Types de messages

| Type | Canal | Usage | Couleur |
| --- | --- | --- | --- |
| `success` | flash + JS | Action réussie | Vert |
| `info` | flash + JS | Information neutre | Bleu |
| `warning` | flash + JS | Point d'attention non bloquant | Orange |
| `danger` | flash + JS | Erreur bloquante | Rouge |
| `notice` | callout | Message pédagogique dans une page | Gris |

## Messages flash côté Symfony

Depuis un contrôleur, utilisez la méthode standard `addFlash` :

```php
$this->addFlash('success', 'La collecte des indicateurs a bien été enregistrée.');
$this->addFlash('warning', 'La version sélectionnée ne dispose pas de toutes les métriques.');
$this->addFlash('danger',  'Impossible de contacter le serveur SonarQube (HTTP 503).');
```

Les messages sont affichés par un partial Twig commun (`templates/_partials/flash.html.twig`) présent dans la base template. Ils s'auto-ferment après **5 secondes** (sauf `danger`).

## Messages JS / jQuery

La v2.0.0 **externalise** les constantes JavaScript (`assets/js/constants/messages.js`). Les messages sont désormais centralisés :

```javascript
export const MSG = {
    SUCCESS: {
        COLLECTE_OK: "La collecte a été réalisée avec succès.",
        VERSION_AJOUTEE: "La version a bien été ajoutée à l'historique.",
    },
    WARNING: {
        SONAR_INDISPONIBLE: "Le serveur SonarQube est temporairement indisponible.",
    },
    ERROR: {
        HTTP_401: "Votre session a expiré. Veuillez vous reconnecter.",
        HTTP_404: "La ressource demandée est introuvable.",
        HTTP_500: "Une erreur est survenue. L'incident a été tracé.",
        HTTP_503: "Le service est momentanément indisponible. Merci de réessayer.",
    },
};
```

Un composant JS `notify()` centralise l'affichage :

```javascript
import { notify } from './services/notify.js';
import { MSG } from './constants/messages.js';

notify('success', MSG.SUCCESS.COLLECTE_OK);
notify('danger',  MSG.ERROR.HTTP_503);
```

## Callouts et blocs pédagogiques

Pour afficher un message contextuel directement dans une page (hors flash), utilisez les **callouts** Foundation :

```twig
<div class="callout primary">
    <h5>{{ "Bon à savoir"|trans }}</h5>
    <p>Cette version est la version de référence pour le calcul des écarts.</p>
</div>
```

Variantes disponibles : `primary`, `secondary`, `success`, `warning`, `alert`.

## Traçabilité (v2.0.0)

Les messages de niveau `danger` et `warning` sont **systématiquement** journalisés dans Monolog :

```text
[2026-02-19T14:22:18+01:00] app.WARNING: [User:john.doe] Serveur SonarQube indisponible (HTTP 503)
```

Cette trace est utilisée pour alimenter la page **Statistiques** et le dashboard EasyAdmin.

## Localisation

Toutes les clés passent par le composant `symfony/translation` (fichiers `translations/messages.fr.yaml` et `translations/messages.en.yaml`). Pensez à utiliser le filtre `trans` dans Twig et la méthode `translate()` côté JS.

```twig
{{ 'collecte.success'|trans }}
```

## Messages d'erreur des formulaires

Les messages générés par les **Validators** Symfony sont affichés sous chaque champ de formulaire. Ils utilisent désormais la classe `is-invalid` de Foundation pour un rendu cohérent avec le reste de l'application.

-**-- FIN --**-

[Retour au menu principal](/index.html)
