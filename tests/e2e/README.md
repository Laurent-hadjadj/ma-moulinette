# Tests E2E Ma-Moulinette (Phase K Playwright)

Tests bout-en-bout du parcours d'onboarding Ma-Moulinette, exécutés avec Playwright + TypeScript.

## Pré-requis

- Node.js >= 20
- PostgreSQL up avec `ma_moulinette` rebuilt (cf. `bin/e2e/rebuild-database.ps1`)
- Symfony serve sur `http://localhost:8000` avec `APP_ENV=test` (active `SonarFixtureClientService` → pas de SonarQube réel requis)

## Première installation

```bash
cd tests/e2e
npm install
npm run install:browsers
```

## Lancer les tests

### Méthode automatique (recommandée)

```powershell
.\bin\e2e\run-e2e.ps1                     # rebuild + warmup + tous les specs
.\bin\e2e\run-e2e.ps1 -Headed             # avec navigateur visible
.\bin\e2e\run-e2e.ps1 -Spec "01-smoke"    # un seul spec
.\bin\e2e\run-e2e.ps1 -SkipRebuild        # garde la DB existante (debug, ~3s par spec)
```

### Méthode manuelle (debug fin)

```powershell
# Terminal 1 : rebuild DB (1ere fois) ou reset rapide (entre runs)
.\bin\e2e\rebuild-database.ps1            # ~30s, prompt postgres password
# OU
.\bin\e2e\reset-e2e-data.ps1              # ~3s, sans prompt (db_user)

# Terminal 2 : Symfony en env test
$env:APP_ENV = "test"
symfony serve --no-tls --port=8000

# Terminal 3 : Playwright
cd tests\e2e
npm test                  # headless
npm run test:headed       # navigateur visible
npm run test:ui           # mode UI interactif (idéal debug)
npm run test:debug        # pas-à-pas
npm run report            # ouvrir le dernier rapport HTML
```

### Reset entre runs

Chaque spec mutante appelle `resetE2EData()` dans son `beforeAll()` → tu peux re-jouer un spec à l'infini sans rebuild manuel. Le reset utilise `db_user` (pas de prompt postgres).

## Structure

```plaintext
tests/e2e/
├── package.json
├── tsconfig.json
├── playwright.config.ts     ← workers=1, séquentiel, baseURL=:8000
├── helpers/
│   ├── auth.ts              ← login() / logout() / loginExpectFailure()
│   └── users.ts             ← USERS (5 fixtures), USER_GROUPS, ALL_GROUPS
└── specs/
    ├── 01-smoke.spec.ts     ← canari : login page + login internal + login refusé
    ├── 02-bootstrap-groupes.spec.ts  (à venir)
    ├── 03-activation-roles.spec.ts   (à venir)
    ├── 04-gestionnaire-init.spec.ts  (à venir)
    ├── 05-affectation-groupes.spec.ts (à venir)
    ├── 06-collecte-manuelle.spec.ts  (à venir)
    └── 07-suivi.spec.ts              (à venir)
```

## Convention de nommage

Préfixe numérique 2 chiffres → ordre d'exécution garanti par tri alphabétique. Chaque spec assume que les précédentes sont passées (DB build-up).

## Utilisateurs E2E (fixtures-e2e.sql)

| User | Initial | Cible (après step 3) | Groupe (après step 5) |
| --- | --- | --- | --- |
| `interne@ma-moulinette.fr` | actif, `ROLE_INTERNAL` | `ROLE_INTERNAL` | ADMIN |
| `josh.liberman@ma-moulinette.fr` | disabled, `ROLE_NONE` | `ROLE_UTILISATEUR` | CONSULTATION |
| `nathan.jones@ma-moulinette.fr` | disabled, `ROLE_NONE` | `ROLE_COLLECTE` | COLLECTE |
| `sophie.martin@ma-moulinette.fr` | disabled, `ROLE_NONE` | `ROLE_COLLECTE` + `ROLE_SUIVI` | GESTIONNAIRE METIER |
| `aurelie.petit-coeur@ma-moulinette.fr` | disabled, `ROLE_NONE` | `ROLE_GESTIONNAIRE` | GESTIONNAIRE APPLICATIF |

Convention : password = courriel (bcrypt cost 13 généré via `bin/e2e/generate-e2e-hashes.php`).
