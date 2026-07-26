# 🧪 Tests unitaires et d'intégration

## 📈 État des suites (relevé du 26/07/2026)

| Suite | Tests | Assertions | Nature |
| --- | ---: | ---: | --- |
| `unit` (`tests/Unit`) | 3 133 | 8 635 | Unitaires purs (mocks, aucune base réelle) |
| `integration` (`tests/Integration`) | 600 | 1 345 | `KernelTestCase`/`WebTestCase` sur PostgreSQL de test |
| **Total** | **3 733** | **9 980** | |

**Couverture de code** (suites `unit` + `integration` cumulées, via pcov) :

| Granularité | Couverture |
| --- | --- |
| Lignes | **80,01 %** (20 650 / 25 810) |
| Méthodes | 84,65 % (2 393 / 2 827) |
| Classes | 51,50 % (120 / 233) |

**Analyse statique** : **PHPStan niveau 6**, 150 erreurs résiduelles (voir [Analyse statique](#-analyse-statique-phpstan) plus bas).

!!! note "🔄 Chiffres à réactualiser"
    Ces valeurs sont un relevé manuel, pas une sortie de CI : elles se périment à chaque campagne de tests.
    Les régénérer avec `composer test` (compteurs), `composer test:coverage` (couverture) et `vendor/bin/phpstan analyse` (erreurs résiduelles).
    Deux tests sont `skipped` : ils dépendent d'un échantillon `dependency-check-report.json` absent de `var/`, ce qui est nominal hors environnement d'ingestion DependencyCheck.

## 📦 Suites de tests

`phpunit.xml.dist` déclare trois suites :

| Suite | Répertoire | Nature |
| --- | --- | --- |
| `unit` | `tests/Unit/` | Tests unitaires purs (mocks, pas de base de données réelle) |
| `integration` | `tests/Integration/` | Tests bout-en-bout via `KernelTestCase`/`WebTestCase`, base PostgreSQL de test réelle |
| `functional` | `tests/Functional/` | Vide actuellement (voir note ci-dessous) |

!!! note "🎭 Functional remplacé par Playwright"
    La suite `tests/Functional` a été vidée en 2026 (tous les tests restants étaient redondants avec la couverture Unit).
    Les parcours utilisateur bout-en-bout (JS, charts, rendu Twig dynamique) sont couverts séparément par une suite **Playwright** (`tests/e2e/`, projet Node.js distinct) — voir [Tests End-to-End](test-e2e.md).

## ▶️ Exécuter les tests

Commandes définies dans `composer.json` :

```bash
composer test              # toute la suite (unit + integration + functional)
composer test:unit         # tests/Unit uniquement
composer test:integration  # tests/Integration uniquement
composer test:coverage     # couverture de code (texte + HTML) via pcov
```

Équivalent direct (utile pour filtrer un test précis) :

```bash
php -d xdebug.mode=off vendor/bin/phpunit tests/Unit
php -d xdebug.mode=off vendor/bin/phpunit --filter testNomDuTest tests/Unit/Chemin/MonTest.php
```

!!! caution "⚠️ Ne jamais lancer deux process PHPUnit en parallèle"
    `tests/Integration` partage une base PostgreSQL réelle (`ma_moulinette_test`).
    Deux exécutions simultanées (même sur des sous-dossiers différents) se marchent dessus : fixtures remises à zéro pendant qu'un autre run teste, ce qui produit des cascades d'échecs qui ressemblent à de vraies régressions mais ne sont que de la contention.
    Toujours vérifier qu'aucun autre process `phpunit`/`composer test*` ne tourne (y compris dans un terminal externe) avant de lancer une suite.

## 🗄️ Base de données de test

`tests/Integration` s'exécute contre une vraie base PostgreSQL (`.env.test` + `.env.test.local`), pas SQLite. Mise en place / réinitialisation complète :

```bash
php bin/console --env=test doctrine:database:drop --force
php bin/console --env=test doctrine:database:create
php bin/console --env=test doctrine:schema:update --force
php bin/console --env=test doctrine:fixtures:load --no-interaction
```

`doctrine:schema:update --force` construit le schéma directement depuis le mapping des entités (`#[ORM\Column]`) — voir [Architecture — base de données](../architecture/architecture-base-de-donnees.md) pour la mise en garde sur la cohérence entité ↔ colonne SQL.

## 🔑 Variables d'environnement requises

Un certain nombre de variables `%env(...)%` référencées par `config/services.yaml` doivent être définies pour que le kernel de test démarre (sinon `EnvNotFoundException`).
Certaines (`DC_INGEST_TOKEN`) sont forcées directement dans `phpunit.xml.dist` ; les autres (`APP_CLIENT_TOKEN`, `APP_ALLOWED_ORIGINS`, tokens Sonar, placeholders LDAP…) sont dans `.env.test`/`.env.test.local`.
Une variable manquante se traduit typiquement par des dizaines d'erreurs identiques `EnvNotFoundException` à travers toute la suite `tests/Integration` — un seul ajout dans `.env.test.local` suffit à toutes les résoudre.

## 🌱 Fixtures de test

Les fixtures Doctrine (`src/DataFixtures/`) suivent une convention de nommage normalisée pour rester anonymes et cohérentes : clé maven canonique `fr.ma-moulinette:ma-moulinette`, domaine email `@ma-moulinette.fr`.
Les 5 utilisateurs nommés de `UtilisateurFixtures.php` (mot de passe fixture commun `test`, même hash que le compte `admin` — voir [Gestion de la sécurité](securite.md)) sont des jeux de données légitimes à conserver tels quels.

Dans les tests `tests/Unit` (mocks, sans lien avec `src/DataFixtures/`), une convention distincte est utilisée pour désigner plusieurs projets fictifs dans un même test : `projet-a`/`projet-b`/`projet-c`, et la sentinelle `projet-inconnu` pour les cas négatifs (projet absent du périmètre).

## 📊 Couverture de code

Prérequis `php.ini` :

```ini
zend_extension=xdebug
extension=pcov
pcov.enabled=1
```

```bash
composer test:coverage
```

Génère un rapport texte (`var/coverage/coverage.txt`), HTML (`var/coverage/coverage-html/`) et Clover (`var/coverage/clover.xml`).
Les chemins texte/HTML sont fixés par les options passées en ligne de commande dans le script `composer.json` (elles priment sur la configuration `<coverage>` de `phpunit.xml.dist`) ; seul le chemin Clover vient de `phpunit.xml.dist`.

## 🔎 Analyse statique (PHPStan)

L'analyse statique complète les tests : elle ne vérifie pas le comportement mais la **cohérence des types** — un tableau dont on ignore le contenu, une méthode appelée sur une valeur qui peut être `null`, un `??` posé sur une expression qui ne l'est jamais.

Configuration : `phpstan.dist.neon` (extensions Doctrine et Symfony activées, analyse de `bin/`, `config/`, `public/`, `src/`, `tests/`).

```bash
vendor/bin/phpstan analyse                       # tout le projet
vendor/bin/phpstan analyse src/Service/Xxx.php   # un fichier
vendor/bin/phpstan analyse src/ --level=8        # au-delà du niveau cible
```

### 🎚️ Niveau retenu : 6

PHPStan propose 11 niveaux (0 à 10), chacun ajoutant ses vérifications à celles du précédent :

| Niveau | Ce qui est vérifié en plus |
| --- | --- |
| 0–2 | Méthodes et variables inconnues, arguments surnuméraires |
| 3–4 | Types de retour incohérents, code mort, conditions toujours vraies ou fausses |
| 5 | Types des arguments passés aux appels |
| **6** | **Types manquants : tout `array` non détaillé, tout paramètre ou retour non typé** |
| 7 | Unions traitées partiellement (un `?Foo` utilisé sans vérifier le `null`) |
| 8 | Appels sur des valeurs potentiellement `null` |
| 9–10 | `mixed` strict, puis exhaustivité des types de PHP |

Le **niveau 6** est la cible du projet : il exige que tout soit typé, sans imposer la discipline du `mixed` strict — inatteignable à coût raisonnable ici, où les données viennent de requêtes SQL brutes (`array<string, mixed>`) et de payloads JSON.

!!! note "📉 Campagne de réduction en cours"
    La dette a été ramenée de **4 820 à 150 erreurs** (relevé du 26/07/2026).
    La quasi-totalité du `missingType.iterableValue` (355 occurrences, des `array` déclarés sans préciser leur contenu) a été résorbée le 26/07/2026 en typant précisément chaque tableau d'après son usage réel.
    Le reliquat actuel (`method.notFound` sur des mocks, `argument.type`, `missingType.property`…) est plus hétérogène et se traite au cas par cas.

!!! caution "🚫 Ne pas masquer une erreur"
    Une erreur PHPStan signale presque toujours un vrai problème de type.
    Corriger la cause, jamais le symptôme : pas de `@phpstan-ignore`, pas d'entrée de baseline, pas de `assert()` ni de cast ajouté uniquement pour faire taire l'analyse, pas d'élargissement d'un type de paramètre ou de retour.
    Exemple vécu : un `count($exec['erreurs']) ?? 0` signalé comme « expression jamais nulle » cachait en réalité un `count()` sur une clé absente du tableau — donc une `TypeError` fatale en PHP 8 dès que la branche d'erreur était empruntée.

Un fichier peut être durci au-delà du niveau du projet : `RapportInsightService` est ainsi vérifié au **niveau 8**, ce qui garantit réellement le contrat d'union littérale (`'up'|'down'|'flat'|'na'`) publié à ses appelants.

## 📚 Pour aller plus loin

- [Tests End-to-End](test-e2e.md) : suite Playwright.
- [Architecture — base de données](../architecture/architecture-base-de-donnees.md) : schéma PostgreSQL et pilotage via `doctrine:schema:update`.
- [Checklist statistiques (release)](checklist-release.md) : liste des pages à rafraîchir avant de taguer une version, et de celles à ne pas toucher.

-**-- FIN --**-

[Retour au menu principal](/index.html)
