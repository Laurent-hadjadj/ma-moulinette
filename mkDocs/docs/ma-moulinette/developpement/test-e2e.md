# Tests End-to-End (E2E) Playwright

## Pourquoi des tests E2E

PHPUnit valide la logique backend (services, repos, controllers, contrats API). Il ne peut **pas** tester :

- Le rendu Twig dynamique avec données réelles
- Les interactions JS / jQuery / charts
- Les workflows multi-pages avec session
- Les permissions effectives au travers des firewalls
- L'UX (flash messages, modales, redirections)

Playwright pilote un vrai navigateur (Chromium par défaut) qui consomme l'application Symfony comme un utilisateur réel.

## Architecture

```text
tests/e2e/
├── package.json              # @playwright/test + scripts npm
├── tsconfig.json             # TS strict, ES2022, CommonJS
├── playwright.config.ts      # workers=1, séquentiel, baseURL :8000
├── .gitignore                # node_modules, test-results, reports
├── README.md                 # quickstart
├── helpers/
│   ├── users.ts              # 5 USERS typés + USER_GROUPS + ALL_GROUPS
│   ├── auth.ts               # login() / loginExpectFailure() / logout()
│   ├── admin.ts              # gotoCrudIndex() / gotoCrudNew() (EasyAdmin v4)
│   ├── db.ts                 # resetE2EData() + resetAndSeedAfterSpec0X()
│   └── fixtures.ts           # test étendu : bloque les requêtes d'images
│                              # (avatars, icônes — purement cosmétiques,
│                              # non vérifiées) pour réduire la charge sur
│                              # le serveur Symfony de dev et accélérer les runs
└── specs/
    ├── 01-smoke.spec.ts
    ├── 02-bootstrap-groupes.spec.ts
    ├── 03-activation-roles.spec.ts
    ├── 04-gestionnaire-init.spec.ts
    ├── 05-affectation-groupes.spec.ts
    ├── 06-collecte-manuelle.spec.ts
    ├── 07-suivi.spec.ts
    ├── 08-controle-acces.spec.ts
    ├── 09-crud-batch-portefeuille.spec.ts
    ├── 10-crud-actuator.spec.ts
    └── 11-owasp.spec.ts
```

## Stratégie : scénario d'onboarding séquentiel

Les specs construisent l'état pour la suivante (DB build-up). On simule le parcours réel d'une nouvelle installation Ma-Moulinette.

### Vue globale du build-up

```mermaid
graph TB
    Start([DB rebuilt<br/>5 users E2E disabled])
    Start --> S01
    S01[01 Smoke<br/>login OK / KO] --> S02
    S02[02 Bootstrap groupes<br/>5 groupes utilisateur] --> S03
    S03[03 Activation rôles<br/>4 users actifs + rôles] --> S04
    S04[04 Init Gestionnaire<br/>reset pwd + collectes] --> S05
    S05[05 Affectation<br/>groupe fonctionnel + assignments] --> S06
    S05 --> S07
    S06[06 Collecte manuelle<br/>tetris:TetrisGame]
    S07[07 Suivi<br/>nav /suivi]

    classDef actor fill:#e8f5e9,stroke:#2e7d32
    classDef internal fill:#e3f2fd,stroke:#1565c0
    classDef gestion fill:#fce4ec,stroke:#ad1457
    classDef collecte fill:#fff3e0,stroke:#e65100
    classDef suivi fill:#f3e5f5,stroke:#6a1b9a

    class S01,S02,S03 internal
    class S04,S05 gestion
    class S06 collecte
    class S07 suivi
```

| # | Spec | Acteur | Action |
| --- | --- | --- | --- |
| 01 | `01-smoke` | infra | login `interne` OK, login disabled refusé |
| 02 | `02-bootstrap-groupes` | interne | crée 5 groupes utilisateur (ADMIN, CONSULTATION, …) |
| 03 | `03-activation-roles` | interne | active 4 users + assigne rôles cibles |
| 04 | `04-gestionnaire-init` | aurelie | **A.** reset password (`test.fixme`, flaky — voir plus bas) · **B.** update projets + collecte profils (seedé indépendamment de A) |
| 05 | `05-affectation-groupes` | aurelie | crée groupe fonctionnel + assigne users aux groupes |
| 06 | `06-collecte-manuelle` | nathan | collecte sur tetris:TetrisGame → enregistre |
| 07 | `07-suivi` | sophie | navigation projet → page suivi, définit une version de référence, supprime une version |
| 08 | `08-controle-acces` | josh, nathan, sophie, aurélie, interne | contrôle d'accès par rôle (indépendant du build-up, propre reset) |
| 09 | `09-crud-batch-portefeuille` | nathan (+ ROLE_BATCH transverse) | crée un portefeuille puis un batch qui le référence (EasyAdmin) |
| 10 | `10-crud-actuator` | nathan (+ ROLE_ACTUATOR transverse) | ajoute un point d'accès Actuator (contrôleur custom, pas EasyAdmin) |
| 11 | `11-owasp` | nathan | collecte générale puis consultation du dashboard OWASP |
| 12 | `12-dependency-check` | nathan (+ ROLE_SECURITY transverse) | upload CI d'un rapport OWASP DependencyCheck, traitement du worker, consultation liste/détail/dashboard |
| 13 | `13-clean-code` | nathan | collecte générale puis consultation du dashboard Clean Code (1 projet) et de la synthèse portefeuille |
| 14 | `14-cosui` | nathan | Comité de Suivi (notes référence/courante, répartition des défauts, radar), seed direct en base |
| 15 | `15-repartition` | nathan | Répartition — bouton Historique (lecture d'une analyse déjà complète), seed direct en base |
| 16 | `16-statistiques` | nathan | 5 pages du module Statistiques (index, dashboard, sonar report, projets, utilisateur) |

**Conséquences** :

- `workers: 1`, `fullyParallel: false` dans `playwright.config.ts`
- Préfixe numérique des fichiers → ordre garanti par tri alphabétique
- DB rebuilt **une fois** avant la suite (pas entre specs)
- Si une spec plante au milieu, les suivantes échouent en cascade — c'est le comportement attendu (révèle la régression à la racine)

## Diagrammes des parcours

### Spec 01 — Smoke

Quatre canaris pour valider l'infrastructure (Symfony serve up + APP_ENV=test + fixtures-e2e.sql chargées).

```mermaid
sequenceDiagram
    actor PW as Playwright
    participant Login as /login
    participant Auth as CustomAuthenticator

    rect rgb(232, 245, 233)
    Note over PW,Auth: Test 1 — Page /login se rend
    PW->>Login: GET /login
    Login-->>PW: form fields visibles
    end

    rect rgb(232, 245, 233)
    Note over PW,Auth: Test 2 — Login internal réussit
    PW->>Login: POST {interne@…, password}
    Auth->>Auth: actif=true ∧ pwd OK
    Auth-->>PW: redirect /accueil
    end

    rect rgb(255, 235, 238)
    Note over PW,Auth: Test 3 — User disabled refusé
    PW->>Login: POST {aurelie@…, password}
    Auth->>Auth: actif=false → reject
    Auth-->>PW: stay /login
    end

    rect rgb(255, 235, 238)
    Note over PW,Auth: Test 4 — Mauvais password refusé
    PW->>Login: POST {interne@…, "wrong"}
    Auth-->>PW: stay /login
    end
```

### Spec 02 — Bootstrap des groupes utilisateur

L'internal crée les 5 groupes utilisateur via l'UI EasyAdmin.

```mermaid
sequenceDiagram
    actor PW as Playwright (interne)
    participant CRUD as /admin/groupe-utilisateur
    participant DB

    PW->>CRUD: login interne
    CRUD-->>PW: /accueil

    loop 5 groupes : ADMIN, CONSULTATION, COLLECTE, GESTIONNAIRE METIER, GESTIONNAIRE APPLICATIF
        PW->>CRUD: GET /new
        CRUD-->>PW: formulaire
        PW->>CRUD: POST {groupe, description}
        CRUD->>CRUD: normalize() → lowercase
        CRUD->>DB: INSERT groupe_utilisateur
        DB-->>CRUD: OK
        CRUD-->>PW: redirect /index
    end

    PW->>CRUD: GET /index
    CRUD-->>PW: liste avec admin, consultation, collecte, gestionnaire metier, gestionnaire applicatif
```

### Spec 03 — Activation des rôles

L'internal active chaque user et lui assigne ses rôles cibles via le formulaire EasyAdmin (case `actif` + checkboxes rôles).

```mermaid
sequenceDiagram
    actor PW as Playwright (interne)
    participant CRUD as /admin/utilisateur
    participant Role as RoleManagerService
    participant DB

    PW->>CRUD: login interne

    loop Pour chaque user (Josh, Nathan, Sophie, Aurélie)
        PW->>CRUD: GET /index → trouve la ligne par email
        PW->>CRUD: GET /:id/edit (href extrait du dropdown)
        PW->>PW: check #actif
        PW->>PW: uncheck ROLE_NONE
        PW->>PW: check rôle(s) cible(s)
        PW->>CRUD: POST save
        CRUD->>Role: normalize(roles, currentRoles, target, editor)
        Role-->>CRUD: rôles validés
        CRUD->>DB: UPDATE actif=true, roles=[...]
    end
```

**Affectations cibles** :

| User | Rôle(s) cible |
| --- | --- |
| Josh LIBERMAN | `ROLE_UTILISATEUR` |
| Nathan JONES | `ROLE_COLLECTE` |
| Sophie MARTIN | `ROLE_COLLECTE` + `ROLE_SUIVI` |
| Aurélie PETIT-COEUR | `ROLE_GESTIONNAIRE` (+ `reset_password=true` via seed) |

### Spec 04 — Init Gestionnaire

Scindé en deux cas indépendants le 2026-07-26 (voir [Cas A mis de côté](#cas-a-reset-password-mis-de-côté--testfixme) plus bas pour le détail des correctifs tentés).

**Cas A** — Aurélie passe par le flow nominal "1ère connexion → reset password" puis se reconnecte avec le nouveau mot de passe. Marqué `test.fixme` : reste flaky (~50% d'échec) malgré deux correctifs applicatifs réels (voir plus bas), cause exacte non identifiée.

**Cas B** — Update projets + collecte profils, seedé directement en état post-reset (via `resetAndSeedAfterSpec04()`) : ne dépend pas du succès du cas A.

```mermaid
sequenceDiagram
    actor PW as Playwright (Aurélie)
    participant Login as /login
    participant Reset as /mot-de-passe/mise-a-jour
    participant Accueil as /accueil
    participant Profil as /profil
    participant Sonar as SonarFixtureClientService
    participant DB

    rect rgb(255, 235, 238)
    Note over PW,Reset: Cas A (test.fixme, flaky)
    PW->>Login: POST {aurelie@…, OLD_PWD}
    Note right of Login: reset_password=true détecté
    Login-->>PW: redirect /reset

    PW->>Reset: type old + new + confirm
    PW->>Reset: submit (form.requestSubmit())
    Reset->>DB: UPDATE password=hash(NEW), reset_password=false
    Reset-->>PW: redirect /accueil

    PW->>Login: logout puis re-login (NEW_PWD)
    Login-->>PW: /accueil
    end

    rect rgb(255, 243, 224)
    Note over PW,DB: Cas B — Update projets (login direct, seed SQL)
    PW->>Accueil: click #bouton-mise-a-jour-referential
    Accueil->>Sonar: GET /api/components/search_projects
    Sonar-->>Accueil: 1 projet tetris (fixture)
    Accueil->>DB: INSERT liste_projet
    end

    rect rgb(232, 234, 246)
    Note over PW,DB: Cas B — Collecte profils (via /profil)
    PW->>Accueil: click #bouton-profil-quality
    Accueil-->>PW: nav /profil
    PW->>Profil: click #bouton-refresh-profil
    Profil->>Sonar: GET /api/qualityprofiles/search
    Sonar-->>Profil: 28 profils (fixture)
    Profil->>DB: INSERT profiles (×28)
    end
```

#### Cas A (reset password) mis de côté — `test.fixme`

Deux bugs applicatifs réels ont été trouvés et corrigés au passage (conservés indépendamment du statut du test) :

- **`assets/js/auth/reset.js`** : le bouton "Valider" changeait son propre `type` en `submit` puis rappelait `.click()` sur lui-même, depuis son propre handler de clic, pour forcer la soumission — un `click()` imbriqué sur le même élément peut être avalé par le flag anti-réentrance du spec HTML (clic sans effet visible, ni erreur ni navigation). Remplacé par `form.requestSubmit()` sur le `<form>` englobant.
- **`src/Form/ResetPasswordFormType.php`** : `autocomplete="off"` sur les 3 champs password — Chrome ignore délibérément cette valeur sur les champs de type password depuis 2014. Remplacé par les valeurs sémantiques `current-password` / `new-password`, que Chrome respecte réellement.
- **`templates/auth/reset.html.twig`** : `autocomplete="username"` ajouté au champ email en lecture seule qui précède les champs password, pour éviter que Chrome ne l'associe à tort au champ password suivant.

Malgré ces trois correctifs, environ un run sur deux voit encore le champ "Ancien mot de passe" écrasé par l'email du compte entre la saisie (vérifiée correcte via `toHaveValue` juste après frappe) et le clic sur "Valider". Cause exacte non identifiée à ce jour. Le test est mis de côté via `test.fixme()` plutôt que laissé flaky en CI ; la collecte projets/profils (cas B) reste couverte séparément puisqu'elle n'en dépend pas.

**Décision du 2026-08-02** : ce cas reste mis de côté définitivement, pas juste en pause. Dans un déploiement s'appuyant sur LDAP pour l'authentification, le changement de mot de passe ne passe pas par ce formulaire applicatif (géré côté annuaire) — le parcours "reset password interne" devient un cas secondaire, pas le chemin nominal. Pas de reprise prévue sauf besoin métier nouveau qui le remettrait au premier plan.

### Spec 05 — Affectation des groupes

Aurélie crée le groupe fonctionnel et affecte chaque user à son groupe utilisateur.

```mermaid
sequenceDiagram
    actor PW as Playwright (Aurélie)
    participant GFCRUD as /admin/groupe-fonctionnel
    participant UCRUD as /admin/utilisateur
    participant DB

    PW->>GFCRUD: GET /new
    Note right of GFCRUD: tag list ← liste_projet (chargée en spec 04)
    PW->>GFCRUD: select tag tetris-game + nom + description
    PW->>GFCRUD: submit
    GFCRUD->>DB: INSERT groupe_fonctionnel

    loop 5 affectations
        PW->>UCRUD: GET /index → ligne du user
        PW->>UCRUD: GET /:id/edit
        PW->>UCRUD: select groupe_utilisateur cible
        PW->>UCRUD: submit save
        UCRUD->>DB: UPDATE utilisateur

        alt Self-edit Aurélie OU édition internal
            UCRUD-->>PW: session invalidée → /login
            PW->>PW: re-login Aurélie (recovery)
        else Cas normal
            UCRUD-->>PW: redirect /index
        end
    end
```

**Affectations groupe utilisateur** :

| User | Groupe utilisateur |
| -- | --- |
| interne | `admin` |
| Josh | `consultation` |
| Nathan | `collecte` |
| Sophie | `gestionnaire metier` |
| Aurélie | `gestionnaire applicatif` |

### Spec 06 — Collecte manuelle

Nathan lance la collecte SonarQube complète sur tetris:TetrisGame. C'est le test le plus long (~2 min) car il enchaîne ~13 phases d'API.

```mermaid
sequenceDiagram
    actor PW as Playwright (Nathan)
    participant Projet as /projet
    participant Collecte as ApiCollecteController
    participant Batch as BatchCollecte*
    participant Sonar as SonarFixtureClientService
    participant DB

    PW->>Projet: GET /projet
    Projet->>DB: SELECT WHERE tag LIKE Nathan.fonctionnel%
    DB-->>Projet: tetris:TetrisGame

    PW->>Projet: select #liste-projet → tetris
    Projet->>PW: #bouton-collecte-indicateur activé

    PW->>Projet: click collecte
    Projet->>Collecte: orchestration

    rect rgb(255, 248, 225)
    loop 13 phases
        Collecte->>Batch: BatchCollecteInfo / Mesure / Anomalie / Hotspot / Owasp / Repartition / NoSonar / Todo / Logger / …
        Batch->>Sonar: GET /api/issues/search, /api/measures/component, /api/hotspots/search, …
        Sonar-->>Batch: JSON fixture
        Batch->>DB: INSERT/UPDATE
        Batch-->>Projet: log(« Phase N OK »)
    end
    end

    Projet-->>PW: log « (13) La collecte des données est terminée. »

    rect rgb(232, 245, 233)
    Note over PW,Projet: Peinture — vérifié : chaque appel répond 200
    PW->>Projet: click #bouton-affiche-indicateur
    Projet->>Projet: remplissage() peint le DOM (peinture.js)
    Projet->>Projet: ~11 appels GET/POST /api/secure/peinture/projet/*
    end

    rect rgb(227, 242, 253)
    Note over PW,DB: Enregistrement — vérifié : 200 sur la réponse attendue
    PW->>Projet: click #bouton-enregistrement-indicateur
    Projet->>DB: POST /api/secure/enregistrement → UPSERT historique
    end
```

**Sentinel de fin** : on attend `(13) La collecte des données est terminée.` dans `<textarea id="log">.value` (timeout 90s).

**Vérification renforcée (2026-07-26)** : les étapes peinture et enregistrement ne se contentaient auparavant que d'un `waitForTimeout` (absence de crash visible, pas de vérification réelle). Le spec capture désormais les réponses réseau et vérifie explicitement le code HTTP 200 de chaque appel `/api/secure/peinture/projet/*` et de `POST /api/secure/enregistrement`.

**Limite connue** : ce spec ne produit qu'**une seule ligne d'historique** par run, car `tests/fixtures/sonarqube/project_analyses/search-page1.json` ne contient qu'une seule analyse (`projectVersion: "1.1.0-RELEASE"`). C'est suffisant pour valider le pipeline de collecte/peinture/enregistrement, mais **insuffisant** pour tester des scénarios qui nécessitent plusieurs versions historiques côte à côte (suppression d'une ligne, changement de version par défaut — cf. spec 07 étendue ci-dessous). Enrichir cette fixture avec des entrées supplémentaires est un pré-requis pour ces scénarios.

### Spec 07 — Suivi

Sophie (COLLECTE+SUIVI) navigue vers la page de suivi, puis modifie l'historique du projet (version de référence, suppression d'une version) depuis la modale "Modifier les paramètres".

```mermaid
sequenceDiagram
    actor PW as Playwright (Sophie)
    participant Projet as /projet
    participant SuiviSet as /suivi/set
    participant Suivi as /suivi
    participant Api as /api/secure/suivi/version/*

    PW->>Projet: GET /projet
    PW->>Projet: select tetris:TetrisGame
    Projet->>PW: #bouton-tableau-de-bord activé

    PW->>Projet: click #bouton-tableau-de-bord
    Projet-->>SuiviSet: GET /suivi/set?maven_key=tetris
    SuiviSet->>SuiviSet: stocke clé en session
    SuiviSet-->>Suivi: redirect /suivi
    Suivi-->>PW: page de suivi

    Note over PW,Suivi: Sur historique seedé (2 versions, pas une vraie collecte)
    PW->>Suivi: click .js-modifier-analyse
    Suivi->>Api: POST version/liste
    Api-->>Suivi: modale ouverte, tableau des versions

    PW->>Api: PUT version/reference (switch)
    Api-->>PW: 200, version de référence changée
    PW->>Suivi: reload + réouvre la modale
    Suivi-->>PW: switch coché = persistance confirmée

    PW->>Api: PUT version/poubelle (icône poubelle)
    Api-->>PW: 200, ligne masquée
    PW->>Suivi: reload + réouvre la modale
    Suivi-->>PW: version absente = suppression confirmée
```

**Suppression d'historique et version de référence** (implémenté) :

- **Suppression** : `PUT /api/secure/suivi/version/poubelle` (`ApiSuiviController.php:799`), déclenché par le clic sur `[id^=poubelle-]` dans la modale "Modifier les paramètres" (`.js-modifier-analyse`). Succès (200) → la ligne est **masquée** (`$('#ligne-N').hide()`), pas retirée du DOM à l'instant du clic (mais bien supprimée en base — vérifié par un `page.reload()` qui montre la version disparue de la liste).
- **Version de référence** ("Référence", à ne pas confondre avec "Favori" ou "Suivi") : `PUT /api/secure/suivi/version/reference` (`ApiSuiviController.php:658`), switch `#switch-reference-{ligne}`, exclusion mutuelle gérée côté JS (une seule case cochée à la fois) et confirmée en base par `page.reload()`.
- Rôle requis : `ROLE_SUIVI` seul (Sophie convient).
- Messages de succès/erreur affichés via `#message-box`/`#message-text` (JS pur, cf. `messageHelper.js`), **pas** le flash serveur `.js-flash-box` utilisé par les autres specs — ces deux actions sont de l'AJAX sans rechargement de page.

!!! note "🗄️ Contournement de la limite fixture SonarQube (une seule analyse)"
    `tests/fixtures/sonarqube/project_analyses/search-page1.json` n'a qu'une seule entrée : une vraie collecte ne peut donc produire qu'**une seule** ligne d'historique, insuffisant pour tester suppression/changement de référence (il faut au moins 2 versions).
    Contournement : `migrations/POSTGRESQL/95_e2e/seed-after-spec-07-historique-tetris.sql` insère directement 2 lignes dans `historique` pour `tetris:TetrisGame` (via `resetAndSeedForSuiviHistorique()` dans `helpers/db.ts`), sans passer par une collecte réelle. Ce seed ne teste que les actions de la page Suivi, pas le mécanisme de collecte lui-même (déjà couvert par le spec 06).

!!! caution "🐛 Deux bugs de test réels trouvés en écrivant ce scénario"
    - **Course entre le rendu Twig et le binding jQuery** : `.js-modifier-analyse` est rendu côté serveur mais son handler de clic n'est attaché qu'une fois le module ES `index-suivi.js` chargé/exécuté (import asynchrone). Cliquer trop tôt ne déclenche ni requête réseau ni erreur JS — juste un clic silencieusement perdu. Corrigé par un `page.waitForFunction()` qui attend explicitement que jQuery ait bien un handler `click` enregistré sur l'élément avant de cliquer.
    - **Label de switch qui intercepte le clic** : `#switch-reference-N` est un `<input type="checkbox">` cliqué au sens propre par l'utilisateur via son `<label class="switch-paddle">` (le rendu visuel du switch), qui le recouvre. Cliquer l'input directement échoue (Playwright : *"intercepts pointer events"*) ; corrigé en ciblant `label[for="switch-reference-N"]`, ce qui correspond aussi au geste réel de l'utilisateur.
    - Par ailleurs, le serveur de dev mono-worker sert les nombreux modules ES (importmap) séquentiellement : un `page.reload()` peut dépasser 60s en attendant l'événement `load` complet. Contourné avec `waitUntil: 'domcontentloaded'` (on n'a pas besoin des sous-ressources pour vérifier l'état persisté) et un timeout de spec relevé à 90s.

### Spec 08 — Contrôle d'accès par rôle

Indépendant du scénario d'onboarding 01-07 (son propre reset+seed). Vérifie que chaque rôle métier protège effectivement les pages/actions qui lui sont associées, dans les deux mécanismes de contrôle présents dans l'app :

```mermaid
sequenceDiagram
    actor PW as Playwright
    participant Strict as Page/action #[IsGranted]<br/>(/admin/logs, accueil, /dependency-check, /activity)
    participant Souple as Page isGranted() manuel<br/>(/traitement/suivi, /actuator)

    rect rgb(255, 235, 238)
    Note over PW,Strict: Sans le rôle requis (ex. Josh, Nathan)
    PW->>Strict: GET/POST route protégée
    Strict-->>PW: 403 réel (AccessDeniedException)
    end

    rect rgb(232, 245, 233)
    Note over PW,Strict: Avec le rôle requis (ex. interne, Aurélie, Nathan+rôles transverses)
    PW->>Strict: GET/POST route protégée
    Strict-->>PW: 200
    end

    rect rgb(255, 243, 224)
    Note over PW,Souple: Sans le rôle requis (ex. Sophie)
    PW->>Souple: GET page
    Souple-->>PW: 200 + flash "Vous devez avoir le rôle X"
    end

    rect rgb(232, 234, 246)
    Note over PW,Souple: Avec le rôle requis (Nathan + rôles transverses)
    PW->>Souple: GET page
    Souple-->>PW: 200, pas de flash de refus<br/>(un flash "aucune donnée" reste possible et légitime)
    end
```

| Mécanisme | Comportement | Rôles couverts | Test négatif (sans le rôle) | Test positif (avec le rôle) |
| --- | --- | --- | --- | --- |
| **Strict** — `#[IsGranted(...)]` (classe ou méthode) | Vraie `AccessDeniedException` → **HTTP 403 réel** | `ROLE_INTERNAL` (`/admin/logs`), `ROLE_GESTIONNAIRE` (actions accueil), `ROLE_SECURITY` (`/dependency-check`), `ROLE_ACTIVITY` (`/activity`) | Josh, Nathan (selon le cas) | interne, Aurélie, Nathan (rôles transverses cumulés, voir plus bas) |
| **Souple** — `isGranted()` manuel dans le contrôleur | Pas d'exception : flash message + rendu normal, **HTTP 200** | `ROLE_BATCH` (`/traitement/suivi`), `ROLE_ACTUATOR` (`/actuator`) | Sophie | Nathan (rôles transverses cumulés) |

`ROLE_SECURITY_ANALYTICS` n'est **pas** un contrôle d'accès : c'est un feature-flag d'affichage (vue restreinte vs vue org-wide) à l'intérieur de pages déjà protégées par `ROLE_SECURITY` — aucun accès n'est refusé sans lui. Hors périmètre de ce spec, à couvrir séparément comme test fonctionnel si besoin.

Sélecteur du flash pour le contrôle souple : `.js-flash-box .callout-message` (`templates/_message.html.twig`). Attention à ne pas confondre le message de **refus de rôle** ("Vous devez avoir le rôle …") avec un message légitime de **données absentes** ("Aucun traitement trouvé") qui utilise la même classe CSS quand le rôle est correct mais qu'il n'y a rien à afficher.

**Rôles transverses** : `ROLE_SECURITY`, `ROLE_ACTIVITY`, `ROLE_BATCH` et `ROLE_ACTUATOR` ne sont portés nativement par aucun des 5 users du scénario d'onboarding (modules indépendants du récit 01-07). Un seed dédié (`resetAndSeedAfterSpec08()` → `95_e2e/seed-after-spec-08-roles-transverses.sql`) les cumule tous sur Nathan pour couvrir le cas positif de chacun.

**Piège `page.request.post()` sur `/api/secure/*`** : `App\EventSubscriber\ApiClientHeaderSubscriber` bloque en 403 toute requête sans un `Origin`/`Referer` autorisé **et** le header `X-Internal-Front: front-app`, avant même d'atteindre le contrôleur/`IsGranted`. Un vrai `fetch()` déclenché depuis la page les ajoute automatiquement ; `page.request.post()` non — sans ces en-têtes explicites, le test obtient un 403 pour **tous** les users (bon rôle ou pas), ce qui ne teste rien du tout. Voir `INTERNAL_FRONT_HEADERS` dans `08-controle-acces.spec.ts`.

**Bug applicatif trouvé et corrigé** : `templates/actuator/index.html.twig:79` appelait `pagination.getTotalItemCount` sans garde — un vrai `Twig\Error\RuntimeError` (500) pour tout utilisateur sans `ROLE_ACTUATOR`, car le contrôleur retourne `pagination = null` avant même d'atteindre le check de rôle. Corrigé par `pagination is not empty ? pagination.getTotalItemCount : 0`.

### Spec 09 — CRUD Portefeuille et Batch

EasyAdmin, `ROLE_BATCH` (protège les deux CRUD, pas de rôle "portefeuille" dédié). Nathan cumule ce rôle via le seed dédié `resetAndSeedForCrudTransverse()`.

```mermaid
sequenceDiagram
    actor PW as Playwright (Nathan)
    participant PCRUD as /admin/portefeuille
    participant BCRUD as /admin/batch
    participant DB

    PW->>PCRUD: GET /new
    PW->>PCRUD: select groupeFonctionnel = "tetris-game"
    Note right of PCRUD: valeur réelle = le tag, pas le nom<br/>d'affichage du groupe ("tetris-test")
    PW->>PCRUD: select liste[] = tetris:TetrisGame
    PW->>PCRUD: nom du portefeuille = "tetris-test-quotidien"
    PW->>PCRUD: submit
    PCRUD->>DB: INSERT portefeuille

    PW->>BCRUD: GET /new
    Note right of BCRUD: ChoiceField "portefeuille" peuplé depuis<br/>SELECT groupe_fonctionnel FROM portefeuille<br/>— vide si aucun Portefeuille n'existe encore
    PW->>BCRUD: titre + select portefeuille = "tetris-game" + description
    PW->>BCRUD: submit
    BCRUD->>DB: INSERT batch
```

**Ordre imposé par une dépendance de données réelle** : le `ChoiceField` "portefeuille" du formulaire Batch est peuplé depuis `SELECT groupe_fonctionnel FROM ma_moulinette.portefeuille` — sans Portefeuille existant, seul le placeholder "Aucun" est disponible. D'où l'ordre Portefeuille → Batch.

**Piège de valeur** : les deux `ChoiceField` ("groupeFonctionnel" côté Portefeuille, "portefeuille" côté Batch) stockent en réalité le **tag/groupe_fonctionnel** ("tetris-game"), pas le nom d'affichage saisi par l'utilisateur ("tetris-test" ou "tetris-test-quotidien"). Toujours vérifier la valeur réelle des `<option>` dans le DOM avant d'écrire `selectOption(...)`, ne pas supposer que c'est le libellé visible.

**Bug historique déjà corrigé** (non régressé, vérifié en documentant ce spec) : `BatchCrudController::updateEntity()` référençait autrefois une colonne `titre` fantôme sur la table `portefeuille`.

### Spec 10 — CRUD Actuator

Contrôleur custom (`ActuatorController`, pas EasyAdmin), `ROLE_ACTUATOR`. Nathan cumule ce rôle via le même seed `resetAndSeedForCrudTransverse()`.

```mermaid
sequenceDiagram
    actor PW as Playwright (Nathan)
    participant Info as /actuator/info
    participant Client as SonarFixtureClientService
    participant DB

    PW->>Info: GET /actuator/info
    Note right of Info: ActuatorController pré-ajoute une<br/>ActuatorInfo vide à l'entité avant de<br/>créer le formulaire : la ligne clé/description<br/>est déjà présente, pas besoin de cliquer "Ajouter clé"
    PW->>Info: remplit nomApplication, mavenKey, personne, url,<br/>actuatorUser, actuatorPassword, clé/description
    PW->>Info: submit

    Info->>Client: httpActuator(url) — ping "joignabilité"
    Client-->>Info: 200 fixe (mocké, voir note)
    Info->>DB: INSERT actuator (password chiffré)
    Info-->>PW: redirect /actuator + flash succès
```

**Pièges réels rencontrés en écrivant ce test** (pas des artefacts Playwright — de vrais comportements applicatifs) :

- **Auto-deadlock réseau** : la création fait un vrai ping HTTP (`ActuatorController::urlActuatorEstJoignable()`) avant d'enregistrer. Pointer cette URL vers le serveur e2e lui-même échoue systématiquement : ce serveur n'a qu'**un seul worker PHP-CGI**, occupé par la requête `actuator/info` en cours — il ne peut pas répondre à sa propre requête de ping avant le timeout (3s), classée "injoignable" à tort. **Fix retenu** : `SonarFixtureClientService` (déjà le double de test pour SonarQube) surcharge maintenant aussi `httpActuator()` pour renvoyer un succès fixe sans appel réseau — cohérent avec le principe déjà appliqué à `httpSonarQube()`. L'URL saisie dans le test n'a donc plus besoin d'être réellement joignable, juste de respecter le format `Assert\Url` (schéma http/https, ≥12 caractères).
- **Champ `actuatorUser` "optionnel" mais bloquant** : `ActuatorFormType` ne définit pas `'required' => false` sur ce champ (pourtant sans contrainte `NotBlank` côté validation Symfony) — le widget rendu porte donc l'attribut HTML5 `required` par défaut. Vide, il bloque silencieusement la soumission native (tooltip navigateur "Please fill out this field", pas d'erreur serveur visible). Le test le remplit systématiquement ; à corriger côté formulaire si on veut un champ réellement optionnel de bout en bout.
- **Footer fixe qui chevauche le bouton submit** : `.footer-fixed` recouvre `#valider-formulaire-enregistrement` à la hauteur de viewport par défaut de Playwright. `click({force: true})` ne suffit pas (le clic finit sur le footer, pas le bouton) — la solution retenue est d'agrandir le viewport (`page.setViewportSize`) avant d'interagir avec le formulaire.
- Pas d'id custom sur les champs (`ActuatorFormType` n'a pas de `getBlockPrefix()`) : sélecteurs par `name$=...` plutôt que par id.

### Spec 11 — Module OWASP

Nathan (`ROLE_COLLECTE`, pas de rôle transverse nécessaire).

```mermaid
sequenceDiagram
    actor PW as Playwright (Nathan)
    participant Projet as /projet
    participant Owasp as /owasp

    Note over PW,Projet: 1. Collecte générale — identique au spec 06
    PW->>Projet: collecte complète sur tetris:TetrisGame

    Note over PW,Owasp: 2. Navigation (pas de collecte OWASP isolée)
    PW->>Projet: click #bouton-analyse-owasp
    Projet-->>Owasp: location.href = /owasp?token=... (pas un nouvel appel API)
    Owasp-->>PW: dashboard OWASP

    Note over PW,Owasp: 3. Vérification (sélecteurs DOM, pas le canvas)
    PW->>Owasp: assert #nombre-faille-owasp, #a1..#a10, #tbody visibles
```

**Point clé découvert avant d'écrire ce test** : il n'existe **aucun déclenchement OWASP isolé** côté UI. Le bouton `#bouton-analyse-owasp` sur `/projet` ne fait que naviguer vers `/owasp?token=...` (token = ROT13+base64 de `salt|maven_key`, généré côté template) — la collecte OWASP fait partie de l'orchestration unique de collecte générale (mêmes phases que le spec 06 : "Collecte des menaces OWASP" / "...potentielles"). Ce test rejoue donc le flux complet de spec 06 avant de vérifier `/owasp`, ce qui le rend aussi long (~1-2 min).

`OwaspController` utilise le même trait `ProjetPerimetreGuard` que `/projet` et `/suivi` (mêmes messages "Erreur 404"/"Erreur 406") — non re-testé ici, déjà couvert par les messages génériques du trait sur les autres specs.

**Canvas Chart.js non testable** : `#owasp-bar-chart` est un `<canvas>` — son contenu (rendu pixel) n'est pas inspectable via le DOM par Playwright. Le test vérifie sa présence/visibilité, jamais son contenu, et cible plutôt les données adjacentes (résumé chiffré, tableau `#a1`-`#a10`, tableau détaillé `#tbody`).

### Spec 12 — Dependency-Check

Nathan (`ROLE_SECURITY`, cumulé via `resetAndSeedForDependencyCheck()` — module transverse comme spec 08/09/10, aucun des 5 users n'a ce rôle nativement dans le récit d'onboarding).

```mermaid
sequenceDiagram
    actor PW as Playwright (simulateur CI)
    participant Api as /api/secure/dependency-check/upload
    participant Worker as app:dependency-check:process
    actor Nathan as Playwright (Nathan)
    participant Index as /dependency-check
    participant Detail as /dependency-check/projet/...
    participant Dash as /dependency-check/dashboard

    Note over PW,Api: 1. Upload (PUBLIC_ACCESS, header X-DependencyCheck-Token, pas de session)
    PW->>Api: POST rapport JSON (tetris:TetrisGame, 1 dep, 1 CVE HIGH)
    Api-->>PW: 202, ulid, status=queued

    Note over PW,Worker: 2. Traitement (aucun cron en e2e — invoqué explicitement)
    PW->>Worker: processDcQueue() (bin/e2e/process-dc-queue.ps1, APP_ENV=test)
    Worker-->>Worker: dc_processing_queue → dc_scan/dc_finding/dc_dependency/dc_cve

    Note over Nathan,Index: 3. Consultation
    Nathan->>Index: GET /dependency-check
    Index-->>Nathan: ligne TetrisGame (tetris), badge HIGH=1
    Nathan->>Detail: click "Detail"
    Detail-->>Nathan: CVE-2023-46120 visible
    Nathan->>Dash: GET /dependency-check/dashboard
    Dash-->>Nathan: badge-scope "tetris-game"
```

**Ingestion asynchrone en 2 étapes, pas un simple POST** : `POST /api/secure/dependency-check/upload` ne fait qu'enqueuer le rapport (`dc_processing_queue`, statut `queued`) — un worker séparé (`bin/console app:dependency-check:process`) doit ensuite tourner pour produire les `dc_scan`/`dc_finding`/`dc_dependency`/`dc_cve` que les pages lisent réellement. Aucun cron ne tourne dans la stack e2e (contrairement à la prod) : le spec invoque le worker directement via `processDcQueue()` (`tests/e2e/helpers/dc.ts` → `bin/e2e/process-dc-queue.ps1`, `APP_ENV=test`).

**Pas d'équivalent `SonarFixtureClientService` nécessaire** : l'ingestion est un appel **entrant** (POST reçu par l'appli, simulateur CI), pas un appel sortant à mocker — le POST direct via `page.request` est donc un test fidèle du vrai flux CI, avec le vrai header `X-DependencyCheck-Token` (`DC_INGEST_TOKEN` en `.env.test`).

!!! caution "🐛 Bug réel trouvé : reset e2e incomplet"
    `migrations/POSTGRESQL/95_e2e/reset-e2e-data.sql` ne purgeait aucune des 5 tables `dc_*` (contrairement à `historique`, `actuator`, etc.). Rejouer ce spec (ou la suite complète) une seconde fois sans rebuild complet de la base laissait le rapport précédent en base : l'upload retombait alors sur la branche idempotence de `ApiDependencyCheckUploadController` (sha256 déjà vu → 200 "Rapport déjà reçu" au lieu de 202 "queued"), un faux échec qui n'avait rien à voir avec le code testé. Corrigé en ajoutant `dc_finding`, `dc_dependency`, `dc_cve`, `dc_scan`, `dc_processing_queue` au `TRUNCATE ... CASCADE`.

### Spec 13 — Clean Code

Nathan (`ROLE_COLLECTE`, aucun rôle transverse — même périmètre tetris-game que spec 06/11).

```mermaid
sequenceDiagram
    actor PW as Playwright (Nathan)
    participant Projet as /projet
    participant CC as /clean-code
    participant Synth as /clean-code/synthese

    Note over PW,Projet: 1. Collecte + affichage + enregistrement (identique spec 06)
    PW->>Projet: collecte complète sur tetris:TetrisGame, "Enregistrer"

    Note over PW,CC: 2. Dashboard 1 projet (bouton masqué en e2e → token reconstruit)
    PW->>CC: goto /clean-code?token=buildProjetToken(mavenKey)
    CC-->>PW: score de risque "medium", gouvernance RESPONSIBLE >5%, 2 canvas Chart.js

    Note over PW,Synth: 3. Synthèse portefeuille
    PW->>Synth: click #bouton-synthese-portefeuille
    Synth-->>PW: tag "tetris-game", ligne TetrisGame + badge de risque
```

**Bouton masqué en e2e, token reconstruit côté test** : le bouton "Clean Code" sur `/projet` n'apparaît que si `version_serveur_sonar != 8` (`templates/projet/index.html.twig`), or `SONAR_VERSION` vaut `8` en `.env.test.local`. Changer cette valeur globalement pour révéler le bouton risquait de perturber d'autres specs qui dépendent implicitement du comportement v8 — plus sûr de reconstruire le token côté test. Le token est un `rot13(base64("salt|maven_key"))` où `salt` est un hash "sdbm" (`hash*65599 + charCode`, calculé avec les opérateurs bitwise JS natifs) — **le salt n'est en réalité jamais vérifié côté serveur** (`decodeToken()` ne lit que la 2e partie après le `|`), donc sa valeur exacte importe peu tant que le format est respecté. Reproduit dans `tests/e2e/helpers/token.ts::buildProjetToken()`, réutilisable pour toute future spec sur Suivi/OWASP/Répartition/COSUI qui voudrait éviter de cliquer le bouton correspondant.

!!! caution "🐛 Fixture SonarQube enrichie : facettes vides = indicateurs à zéro"
    `tests/fixtures/sonarqube/issues/search.json` (déjà utilisée par les specs 06/11) avait `"facets": []` — insuffisant pour produire des indicateurs Clean Code exploitables : `BatchCollecteCleanCodeController::extractFacetCounts()` retournait un tableau vide, donc toutes les colonnes `cc_*`/`quality_*`/`impact_*` étaient insérées à 0 après collecte (score de risque, % RESPONSIBLE, % sécurité systématiquement nuls, aucune carte/graphique testable pour de vrai).
    Enrichi avec les 3 facettes `cleanCodeAttributeCategories`/`impactSeverities`/`impactSoftwareQualities` (valeurs produisant un niveau de risque `medium`, une gouvernance RESPONSIBLE à 9,1 % et une exposition sécurité à 12,9 %), **sans toucher `paging.total`** (132) dont dépendent déjà `owasp_top10`/`sans_top25` sur les specs existantes — aucune régression, uniquement additif.

**Page `/clean-code/synthese` — risque calculé différemment de `/clean-code`** : le dashboard 1-projet calcule le score de risque sur `clean_code.issue_total`, alors que la synthèse portefeuille le calcule sur `historique.violations` (cf. commentaire du template : *"Risque CC calculé sur violations (total issues)"*) — deux dénominateurs différents pour la même formule pondérée. Le spec ne prédit donc pas le niveau exact sur la page synthèse (juste la présence d'un badge `.badge-level` valide), pour ne pas dupliquer une hypothèse de calcul non vérifiée.

### Spec 14 — COSUI

Nathan (`ROLE_COLLECTE`, aucun rôle transverse — même périmètre tetris-game que spec 06/11/13). Token de navigation réutilisé tel quel depuis `buildProjetToken()` (spec 13).

```mermaid
sequenceDiagram
    actor PW as Playwright (Nathan)
    participant Cosui as /projet/cosui

    Note over PW,Cosui: seed direct : 2 lignes historique (référence + courante,<br/>notes/compteurs réels) + 1 ligne repartition control='complet (100%)'
    PW->>Cosui: goto /projet/cosui?token=buildProjetToken(mavenKey)
    Cosui-->>PW: setup 100%, notes courantes, tableau répartition, radar

    PW->>Cosui: click #affiche-projet-reference
    Cosui-->>PW: modale — notes de la version de référence (plus mauvaises)
```

!!! caution "🐛 Prérequis de données : le flux spec 06 ne suffit pas"
    Contrairement à Clean Code, `/projet/cosui` a deux prérequis que la collecte manuelle standard ne produit jamais :

    - une ligne `historique` avec `initial = true` (« version de référence ») — la fixture SonarQube (`project_analyses`) ne produit qu'**une seule analyse**, donc jamais de 2e version à marquer comme référence ;
    - une ligne `repartition` avec `control <> 'initial'` — le flux spec 06 ne déclenche jamais l'action « Répartition par module ».

    Sans ces deux prérequis, la page reste en mode « valeurs par défaut » (pas d'erreur bloquante, mais rien d'intéressant à vérifier). Seed dédié : `migrations/POSTGRESQL/95_e2e/seed-after-spec-14-cosui-tetris.sql` (2 lignes historique avec notes/compteurs réels + 1 ligne repartition `control='complet (100%)'`), via `resetAndSeedForCosui()`.

!!! caution "🐛 Bug réel trouvé et corrigé : colonne Fiabilité du tableau Répartition toujours à 0"
    `ProjetCosuiService::generateRender()` construit la clé de variable de rendu à partir du même identifiant interne (`bug`) que celui utilisé pour retrouver la colonne Doctrine (`frontendBugBlocker`, etc.) — mais le template attend `nombre_metier_reliability_*`/`nombre_presentation_reliability_*` (label « Fiabilité »). Sans correspondance entre `bug` et `reliability`, cette colonne affichait toujours 0 quelle que soit la donnée réelle en base — un test unitaire existant ne couvrait que le cas par défaut (`'--'`), jamais le cas peuplé. Corrigé par une table de correspondance dédiée (`$prefixLabels`) juste avant l'injection dans le rendu ; les colonnes Vulnérabilité/Maintenabilité n'étaient pas concernées (leur identifiant interne correspond déjà au label attendu).

**Bug connu documenté, non corrigé (hors périmètre)** : `HistoriqueRepository::selectHistoriqueProjetLast/Reference` sélectionne `menace_potentielle_totale` sans alias `AS nombre_hotspot` — le compteur Hotspot (`#hotspot-01`) affiche donc toujours 0 quelle que soit la donnée en base (la note lettre `#note-04`, elle, fonctionne correctement — alimentée par `security_review_rating AS note_hotspot`, un alias distinct). Non asserté à une valeur non nulle dans ce spec.

### Spec 15 — Répartition

Nathan (`ROLE_COLLECTE`, aucun rôle transverse — même périmètre tetris-game que spec 06/11/13/14). Token identique aux autres pages signées.

```mermaid
sequenceDiagram
    actor PW as Playwright (Nathan)
    participant Repart as /repartition

    Note over PW,Repart: seed direct : 1 ligne repartition control='complet (100%)'<br/>avec compteurs granulaires par module × catégorie × sévérité
    PW->>Repart: goto /repartition?token=buildProjetToken(mavenKey)
    Repart-->>PW: page chargée (écrit aussi une ligne "initiale" pour le setup courant)

    PW->>Repart: click #bouton-historique
    Repart-->>PW: mode=Historique, setup seedé, 4 tableaux (synthèse + Fiabilité/Sécurité/Maintenabilité)
    Note over PW,Repart: IdC = "---" sur chaque ligne (bug corrigé, voir ci-dessous)
```

!!! caution "🐛 Cycle Collecte → Analyse non jouable avec les fixtures actuelles"
    Comme COSUI, le cycle réel (collecte → analyse) ne peut pas produire de données exploitables avec les fixtures e2e actuelles : `BatchCollecteRepartitionController` attend une facette `severities` que `tests/fixtures/sonarqube/issues/search.json` ne fournit pas (enrichie pour le spec 13 avec `cleanCodeAttributeCategories`, pas `severities`) — le total lu au chargement de la page est donc toujours 0, et chaque bouton de collecte affiche juste « pas de données à collecter » sans jamais appeler l'API. Ce spec teste uniquement le bouton **Historique** (lecture pure d'une ligne déjà complète), via un seed direct en base (`seed-after-spec-15-repartition-tetris.sql`, `resetAndSeedForRepartition()`) — pas le cycle collecte/analyse en direct.

!!! caution "🐛 Bug réel trouvé et corrigé (signalé par l'utilisateur) : IdC incohérent en mode Historique"
    Le mode Historique réutilisait `generateTableRow()`/`calculateIdc()` (`assets/js/mon-application/repartition-module/index-repartition-module.js`), qui divise le total **historisé** par les compteurs **live** du DOM (`elements[...].dataset`, figés au chargement de la page courante — donc les chiffres SonarQube du moment présent, pas ceux qui étaient vrais quand l'analyse historique a été enregistrée). Les deux instantanés n'ayant aucune raison de correspondre, l'IdC affiché en mode Historique n'avait pas de sens. `calculateIdc()` n'avait d'ailleurs qu'un seul appelant dans tout le fichier : c'est bien tout le calcul qui était inadapté à son usage réel, pas une erreur de bord. Corrigé en supprimant l'appel à `calculateIdc()` dans `generateTableRow()` (fonction elle-même utilisée uniquement par `historique()`) : la colonne IdC affiche désormais `---` pour ce mode, un `control = 'complet (100%)'` garantissant déjà la complétude par construction. Ce spec vérifie explicitement ce `---` sur les 3 tableaux détaillés (Fiabilité/Sécurité/Maintenabilité).

### Spec 16 — Statistiques

Nathan (`ROLE_COLLECTE`, groupe tetris-game). Aucune des 5 pages du module n'exige de rôle spécifique (seul `ROLE_UTILISATEUR` implicite) — seul le bouton "Analyse UserAgent"/"Relancer l'analyse" (route `runBatchAnalysis`) exige `ROLE_INTERNAL`, absent du DOM pour Nathan (`{% if is_granted('ROLE_INTERNAL') %}` côté template, pas juste masqué en CSS).

```mermaid
sequenceDiagram
    actor PW as Playwright (Nathan)
    participant Idx as /statistiques
    participant Dash as /statistiques/dashboard
    participant Sonar as /statistiques/ma-moulinette
    participant Proj as /statistiques/projet
    participant User as /statistiques/utilisateur

    PW->>Idx: goto /statistiques
    Idx-->>PW: 4 cartes visibles, bouton batch absent

    PW->>Dash: click carte Dashboard
    Dash-->>PW: page technique (PHP/Symfony/PostgreSQL/RAM) + vraies stats cloc/phpunit

    PW->>Sonar: goto /statistiques/ma-moulinette
    Sonar-->>PW: 2 canvas Chart.js + données JSON

    PW->>Proj: goto /statistiques/projet
    Proj-->>PW: ligne TetrisGame (seed spec 07 réutilisé)

    PW->>User: goto /statistiques/utilisateur
    User-->>PW: canvas présents (masqués sans activité trackée), bouton batch absent
```

**Seed réutilisé, pas de nouveau seed dédié** : `/statistiques/projet` lit `historique` sans jointure (`HistoriqueRepository::selectAllProjetsDerniereSynthese()`) — le seed déjà existant du spec 07 (`resetAndSeedForSuiviHistorique()`, 2 lignes historique minimales pour tetris:TetrisGame) suffit à y afficher une ligne, sans relancer une vraie collecte (~180s). Les colonnes non peuplées par ce seed minimal (notes, coverage…) s'affichent en `–`, sans bloquer la page.

!!! caution "🐛 Bug réel trouvé et corrigé : /statistiques/dashboard plantait en 500"
    `StatistiqueController::adminDashboard()` exécutait `SELECT count(*) FROM ma_moulinette.pg_catalog.pg_tables WHERE schemaname = 'ma_moulinette'` — un adressage à 3 parties (`base.schéma.table`) que PostgreSQL ne supporte pas : les références entre bases de données ne sont pas implémentées nativement, et `pg_catalog` est un schéma système accessible directement, jamais imbriqué sous un autre schéma applicatif.
    Corrigé en retirant le préfixe `ma_moulinette.` erroné (`FROM pg_catalog.pg_tables`). Aucun test unitaire existant ne couvrait ce cas (connexion mockée) — seul un vrai appel PostgreSQL via e2e pouvait le révéler.

**Chemin « vraies données » exercé, pas seulement le repli** : `/statistiques/dashboard` lit `var/admin-stats.json` (gitignoré) puis `migrations/admin-stats.json` en repli — les deux sont absents par défaut, la page affichant alors un bandeau "données figées". Le seed du spec appelle désormais `refreshAdminStats()` (helper `tests/e2e/helpers/stats.ts`, invoque `php bin/console app:admin:refresh-stats --env=test` via `bin/e2e/refresh-admin-stats.ps1`, même convention que `processDcQueue()` du spec 12) avant la visite de la page, pour que le bandeau "Données générées le…" s'affiche réellement. Aucune assertion n'est figée sur les chiffres cloc eux-mêmes (non reproductibles selon l'environnement) — seulement sur l'absence du bandeau de repli et la présence des 7 lignes du tableau de code.
    Prérequis : `cloc` installé et sur le `PATH` du processus PowerShell (`winget install AlDanial.Cloc`) — sur ce poste, `cloc.exe` est fourni avec la distribution PHP dans `0_toolz\php-8.5.5-NTS\`.

**Canvas masqués sans activité utilisateur trackée** : sur `/statistiques/utilisateur`, `#chart-avg-session-duration`/`#chart-nb-session-unique` restent masqués côté JS tant que leurs `data-*` sont vides (`"[]"`, aucune session Nathan trackée sur ce seed minimal) — comportement attendu, vérifié en présence (`toBeAttached()`), pas en visibilité.

## Reset rapide entre runs (≈ 3s, sans prompt password)

Pour itérer rapidement sur une spec qui mute la DB, chaque spec mutante appelle `resetE2EData()` (et éventuellement `resetAndSeedAfterSpec0X()`) dans son `test.beforeAll()`.
Équivalent du reset entre tests d'intégration Symfony.

```typescript
import { resetAndSeedAfterSpec05 } from '../helpers/db';

test.describe('06 — Collecte manuelle', () => {
  test.beforeAll(() => {
    resetAndSeedAfterSpec05();   // ~5s, pas de prompt password
  });

  test('...', async ({ page }) => { /* ... */ });
});
```

**Sous le capot** :

- `tests/e2e/helpers/db.ts` → spawn `bin/e2e/reset-e2e-data.ps1` puis `bin/e2e/seed-e2e.ps1`
- Ces scripts utilisent `db_user` (password connu via `.env`) → `PGPASSWORD` env var → **pas de prompt**
- `migrations/PosgreSQL/95_e2e/reset-e2e-data.sql` :
  - TRUNCATE toutes les tables de données projet (mesures, anomalie, hotspots, etc.)
  - DELETE les groupes/portefeuilles custom (garde "Aucun" + "En attente")
  - DELETE les 5 users E2E + reload `fixtures-e2e.sql`
- `migrations/PosgreSQL/95_e2e/seed-after-spec-0X-…sql` : replicat de l'état de fin de chaque spec via SQL pour permettre l'isolation au debug (dont `seed-after-spec-08-roles-transverses.sql`, indépendant du récit d'onboarding — cumule sur Nathan les rôles `ROLE_SECURITY`/`ROLE_ACTIVITY`/`ROLE_BATCH`/`ROLE_ACTUATOR`, réutilisé par `resetAndSeedAfterSpec08()` (spec 08) ET `resetAndSeedForCrudTransverse()` (specs 09/10, qui ont en plus besoin d'un groupe fonctionnel existant, donc chaînent depuis `resetAndSeedAfterSpec05()` plutôt que `resetAndSeedAfterSpec03()`) ; ainsi que `seed-after-spec-07-historique-tetris.sql`, qui insère 2 lignes d'historique directement en base pour tester suppression/référence sur la page Suivi sans dépendre d'une vraie collecte, réutilisé par `resetAndSeedForSuiviHistorique()` ; et `resetAndSeedForDependencyCheck()` (spec 12, alias sémantique de `resetAndSeedForCrudTransverse()` — le rôle `ROLE_SECURITY` y est déjà inclus) ; ainsi que `seed-after-spec-14-cosui-tetris.sql`, qui insère 2 lignes historique (notes/compteurs réels) + 1 ligne repartition `control='complet (100%)'` pour tester COSUI sans dépendre d'une vraie collecte ni d'une analyse de répartition, réutilisé par `resetAndSeedForCosui()` ; ainsi que `seed-after-spec-15-repartition-tetris.sql`, qui insère 1 ligne repartition control='complet (100%)' pour tester le bouton Historique de la page Répartition sans dépendre d'un cycle collecte/analyse en direct, réutilisé par `resetAndSeedForRepartition()`)
- Conserve : référentiels OWASP, versions ma_moulinette, admin de prod, groupes par défaut

### Quand utiliser quoi

| Situation | Commande | Durée | Prompt password |
| --- | --- | --- | --- |
| Première fois après pull / structure DB modifiée | `.\bin\e2e\rebuild-database.ps1` | ~30s | ✅ (postgres superuser) |
| Run E2E complet propre | `.\bin\e2e\run-e2e.ps1` | rebuild + warmup + tests | ✅ |
| Itération sur une spec (debug) | `.\bin\e2e\run-e2e.ps1 -SkipRebuild -Spec "02"` | reset auto via `beforeAll` ~3-5s + tests | ❌ |
| Reset manuel hors Playwright | `.\bin\e2e\reset-e2e-data.ps1` | ~3s | ❌ |

## Utilisateurs E2E

Chargés via `migrations/PosgreSQL/90_fixtures/fixtures-e2e.sql`.

| User | État initial | Cible (post step 3) | Groupe (post step 5) |
| --- | --- | --- | -- |
| `interne@ma-moulinette.fr` | actif, `ROLE_INTERNAL` | `ROLE_INTERNAL` | ADMIN |
| `josh.liberman@ma-moulinette.fr` | disabled, `ROLE_NONE` | `ROLE_UTILISATEUR` | CONSULTATION |
| `nathan.jones@ma-moulinette.fr` | disabled, `ROLE_NONE` | `ROLE_COLLECTE` | COLLECTE |
| `sophie.martin@ma-moulinette.fr` | disabled, `ROLE_NONE` | `ROLE_COLLECTE` + `ROLE_SUIVI` | GESTIONNAIRE METIER |
| `aurelie.petit-coeur@ma-moulinette.fr` | disabled, `ROLE_NONE` | `ROLE_GESTIONNAIRE` | GESTIONNAIRE APPLICATIF |

**Convention** : password = courriel (bcrypt cost 13).
**Génération des hashes** : `php bin/e2e/generate-e2e-hashes.php`
**Vérification des hashes** : `php bin/e2e/verify-e2e-hashes.php`

`ROLE_NONE` est ajouté à `config/packages/security.yaml` comme rôle sentinelle (sans héritage, sans privilège). Il bloque l'accès à toutes les routes mais permet de stocker un user en attente d'affectation.

## Pré-requis

- **Node.js** ≥ 20 (`node -v`)
- **PostgreSQL 18** up (db_user / db_password)
- **PHP 8.5.5** + Symfony CLI (`symfony -v`)
- **psql** dans le PATH

## Installation Playwright (une fois)

```powershell
cd tests\e2e
npm install
npm run install:browsers   # Chromium + deps
```

## Lancer les tests

### Méthode automatique (recommandée)

Script PowerShell qui enchaîne tout :

```powershell
# Depuis la racine du projet
.\bin\e2e\run-e2e.ps1                     # tous les specs, headless
.\bin\e2e\run-e2e.ps1 -Headed             # avec navigateur visible
.\bin\e2e\run-e2e.ps1 -Spec "01-smoke"    # un spec en particulier (substring match)
.\bin\e2e\run-e2e.ps1 -SkipRebuild        # garder la DB existante (debug)
.\bin\e2e\run-e2e.ps1 -SkipServer         # ne pas démarrer Symfony serve (déjà up ailleurs)
```

Le script :

1. Rebuild la DB `ma_moulinette` (sauf si `-SkipRebuild`)
2. Démarre Symfony serve avec `APP_ENV=test` en daemon (port 8000) si pas déjà up
3. Warmup le cache test (évite les 502 sur les assets)
4. Lance `npx playwright test` depuis `tests/e2e/`
5. Stoppe le daemon Symfony à la fin (uniquement si on l'a démarré)

### Méthode manuelle (debug fin)

```powershell
# Terminal 1 : DB clean
.\bin\e2e\rebuild-database.ps1

# Terminal 2 : Symfony en env test
$env:APP_ENV = "test"
symfony serve --no-tls --port=8000

# Terminal 3 : Playwright
cd tests\e2e
npm test                  # headless
npm run test:headed       # avec navigateur visible
npm run test:ui           # mode UI interactif (idéal debug)
npm run test:debug        # pas-à-pas
npm run report            # ouvrir le dernier rapport HTML
```

## Pourquoi `APP_ENV=test`

`config/services_test.yaml` substitue `App\Service\ClientService` par `App\Tests\Support\SonarFixtureClientService` qui sert des **fixtures JSON locales** (capturées depuis un vrai SonarQube via `bin/e2e/capture-sonar-fixtures.ps1`).

Conséquences :

- Pas besoin d'un serveur SonarQube up
- Tests reproductibles (même JSON à chaque run)
- Cas particuliers (`null`, valeurs absentes) figés dans les fixtures

Les fixtures sont dans `tests/fixtures/sonarqube/`. Endpoints couverts (Phase I + extensions) :

| URL pattern | Fixture | Utilisé par |
| --- | --- | --- |
| `/api/components/app` | `components/app.json` | InformationProjet |
| `/api/components/search_projects` | `components/search_projects.json` | Update projets accueil |
| `/api/measures/component` | `measures/component.json` | Mesures |
| `/api/issues/search` | `issues/search.json` | Anomalies, NoSonar, Todo, Logger, Repartition |
| `/api/hotspots/search` | `hotspots/search.json` | Hotspots, OWASP |
| `/api/hotspots/show` | `hotspots/show.json` | HotspotDetails |
| `/api/project_analyses/search` | `project_analyses/search-page1.json` | Versions historique |
| `/api/qualityprofiles/search` | `qualityprofiles/search.json` | Collecte profils (28 profils synthétiques) |

## Conventions d'écriture

### Helpers

- **`auth.login(page, USERS.foo)`** : raccourci pour login
- **`auth.loginExpectFailure(page, email, pwd)`** : assertion d'échec (reste sur /login)
- **`auth.logout(page)`** : déconnexion + assertion redirect /login
- **`admin.gotoCrudIndex(page, 'utilisateur')`** : nav vers `/admin/utilisateur`
- **`admin.gotoCrudNew(page, 'utilisateur')`** : nav vers `/admin/utilisateur/new`
- **`db.resetE2EData()`** : reset DB sans prompt
- **`db.resetAndSeedAfterSpec0X()`** : reset + seed à l'état de fin de la spec X
- **`db.resetAndSeedForCrudTransverse()`** : état post-spec-05 + rôles transverses (`ROLE_SECURITY`/`ROLE_ACTIVITY`/`ROLE_BATCH`/`ROLE_ACTUATOR`) cumulés sur Nathan — utilisé par les specs 09/10 (CRUD Batch/Portefeuille/Actuator)

### Locators

Préférer dans cet ordre :

1. **`page.getByRole(...)`** (a11y-first, recommandé Playwright)
2. **`page.getByLabel(...)`** (formulaires)
3. **`page.locator('#id')`** (stable mais lié à la structure DOM)
4. **`page.locator('input[name$="..."]')`** (formulaires Symfony, indépendant du prefix)

Éviter les sélecteurs CSS de classe (changent souvent).

### Cas particuliers EasyAdmin v4

- Les actions CRUD (edit/delete/show) sont dans un **dropdown** : on peut cliquer le toggle puis le lien, OU extraire le href directement via `getAttribute('href')` + `goto()` (plus rapide)
- Les boutons "Créer" et "Sauvegarder" sont **hors du `<form>`** (toolbar header) : utiliser `getByRole('button', { name: 'Créer', exact: true })`
- Les `<select>` avec `data-action-name="..."` sont enrichis par TomSelect : `selectOption()` sur le `<select>` natif (caché) marche
- Le label de checkbox de rôle peut entrer en collision avec le combobox de groupe utilisateur du même nom (ex: "Utilisateur") : préférer `input[type="checkbox"][value="ROLE_UTILISATEUR"]`

### Assertions

- `await expect(page).toHaveURL(...)` : navigation
- `await expect(locator).toBeVisible()` : rendu UI
- `await expect(locator).toContainText('...')` : contenu (⚠️ ne marche pas pour `<textarea>`)
- `await expect(locator).toHaveValue(...)` : valeur d'un input/textarea
- `await expect(locator).toHaveCount(n)` : compteurs (listes, tableaux)
- `await expect(locator).not.toHaveClass(/disabled-bouton/)` : état UI dynamique

### Form submission gotchas

- **Reset password** : le bouton est `type="button"` initialement ; `reset.js` valide les champs puis appelle `form.requestSubmit()` sur le `<form>` englobant (pas d'AJAX, vraie navigation POST). Voir [Cas A mis de côté](#cas-a-reset-password-mis-de-côté--testfixme) pour l'historique des correctifs et la flakiness résiduelle non résolue.
- **HTML5 pattern v-flag** : Chrome 132+ rejette `[a-zA-Z0-9._@-]` car `@-]` est ambigu. Utiliser `[-a-zA-Z0-9._@]` (hyphen en début).
- **Autofill Chrome sur formulaires multi-password** : `autocomplete="off"` est ignoré par Chrome sur les champs `type="password"` depuis 2014 — utiliser les valeurs sémantiques `current-password`/`new-password`. Un champ identifiant en lecture seule précédant un champ password doit porter `autocomplete="username"` explicitement, sinon Chrome peut l'associer à tort au champ suivant.
- **Bouton rendu côté serveur, handler attaché en JS async** : un bouton présent dans le HTML Twig peut ne pas encore avoir son event listener jQuery attaché (module ES chargé de façon asynchrone) — cliquer trop tôt ne produit ni requête réseau ni erreur, juste un clic perdu. Si un clic sur un élément JS-driven semble ne rien faire, attendre explicitement le binding avant de cliquer plutôt que d'ajouter un `waitForTimeout` :
  ```typescript
  await page.waitForFunction(() => {
    const el = document.querySelector('.ma-classe');
    return !!(window as any).jQuery?._data(el, 'events')?.click?.length;
  });
  ```
- **Switch (checkbox stylé en toggle)** : cliquer l'`<input type="checkbox">` directement échoue souvent (*"intercepts pointer events"*) car son `<label class="switch-paddle">` le recouvre visuellement. Cibler `label[for="id-du-switch"]` — c'est aussi le geste réel de l'utilisateur.

## Debug

- **Mode UI** (`npm run test:ui`) : timeline complète, time-travel debugging, locator picker
- **Trace** : auto-générée sur retry → `npx playwright show-trace test-results/.../trace.zip`
- **Screenshot** : auto-pris en cas d'échec (`test-results/`)
- **Rapport HTML** : `npm run report`
- **error-context.md** : généré dans `test-results/<spec>/`, contient le snapshot ARIA du DOM au moment de l'échec — précieux pour identifier les sélecteurs

## Prochaines étapes

- **Cas A (reset password) : mis de côté définitivement** (décision du 2026-08-02, pas juste en attente) — dans un déploiement LDAP, le changement de mot de passe ne passe pas par l'application, ce parcours devient secondaire. `test.fixme` reste en place, pas de reprise prévue sauf besoin métier nouveau.
- **Bug avatar** : URL `/assets/avatar/chiffre/02.png` ne se résout pas (à investiguer côté AssetMapper / `getAvatarUrl()`)
- **`actuatorUser` non réellement optionnel** : `ActuatorFormType` ne force pas `'required' => false`, le widget HTML5 bloque une soumission avec ce champ vide alors qu'aucune contrainte Symfony ne l'exige (cf. spec 10)
- **Cypress-style isolation** (option future) : si on veut chaque spec totalement auto-suffisante, snapshoter la DB après chaque spec pour restauration rapide

> Cette doc est complétée au fur et à mesure (sections debug, patterns récurrents, gotchas) — pensez à enrichir au fil des évolutions.
