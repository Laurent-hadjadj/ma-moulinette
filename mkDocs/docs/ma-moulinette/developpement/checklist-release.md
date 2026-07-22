# ✅ Checklist — statistiques à rafraîchir avant une release

Plusieurs pages citent des chiffres sur l'état du projet (fichiers, classes, tests, couverture, PHPStan). Toutes ne sont pas de même nature : certaines doivent être recalculées à chaque release, d'autres sont figées dans le temps ou décrivent une fonctionnalité et non une mesure. Cette page sert de pense-bête pour ne pas oublier une page ni retoucher à tort une autre.

## 🔄 À rafraîchir à chaque release

Ces pages portent le **même relevé** (nombre de fichiers/lignes/classes/méthodes, tests, assertions, couverture, PHPStan) et doivent être mises à jour ensemble, avec la même date de relevé.

| Fichier | Contenu |
| --- | --- |
| `README.md` | Bloc « État actuel » (fichiers/lignes/classes/méthodes) + tableau « Qualité du code » (tests/assertions/couverture/PHPStan) + 3 badges shields.io en tête de fichier |
| `mkDocs/docs/index.md` | Tableau « Qualité du code » (même contenu que README, dupliqué volontairement pour la home mkDocs) |
| `mkDocs/docs/ma-moulinette/developpement/test-unitaire.md` | Page de référence : mêmes chiffres en détail + paragraphe sur la dette PHPStan résiduelle |

**Commandes pour régénérer chaque chiffre :**

```bash
# Fichiers/lignes par langage + nombre de tests listés (écrit var/admin-stats.json + migrations/admin-stats.json)
php bin/console app:admin:refresh-stats -v

# Tests + assertions exacts (composant unitaire et intégration)
php -d xdebug.mode=off vendor/bin/phpunit --testsuite unit
php -d xdebug.mode=off vendor/bin/phpunit --testsuite integration

# Couverture de code (lent — plusieurs minutes)
php -d xdebug.mode=off -d pcov.enabled=1 vendor/bin/phpunit --coverage-text=var/coverage/coverage.txt --coverage-html=var/coverage/coverage-html

# Erreurs PHPStan résiduelles
vendor/bin/phpstan analyse --no-progress

# Nombre exact de classes/méthodes (nécessite phploc dans le PATH, cf. note ci-dessous)
phploc src/
```

!!! note "✅ Nombre de classes/méthodes : phploc disponible"
    Le bloc « État actuel » utilisait un comptage à la main (`grep` sur `src/`), faute de `phploc` installé dans l'environnement. `phploc` (paquet Composer `cmgmyr/phploc`, fork maintenu de l'outil historique de Sebastian Bergmann) est désormais utilisé — installation via `composer global require cmgmyr/phploc`, ou build local. Lire les valeurs dans la sortie sous `Structure` : `Classes` (total, hors interfaces/traits) pour le nombre de classes, `Methods` pour le nombre de méthodes.

## 🧊 Historique — ne jamais réécrire

| Fichier | Pourquoi |
| --- | --- |
| `CHANGELOG.md` | Chaque bloc de version documente l'état **au moment où il a été écrit** (ex. section « Tests Unitaires » du bloc v2.0.0 WiP citant « 334 cas de tests » — très inférieur aux chiffres actuels). Réécrire ces chiffres avec les valeurs courantes falsifierait l'historique. Seul le bloc `## vX.Y.Z - ... - WiP` en tête de fichier reçoit les nouvelles entrées du jour. |

## 🏗️ Structurel — à vérifier seulement si le schéma/la fonctionnalité change

Ces chiffres ne bougent pas avec le volume de code ou de tests ; ils décrivent une structure ou une fonctionnalité précise du code. À corriger uniquement si cette structure change (table ajoutée, colonne de tableau UI ajoutée/retirée…), pas à chaque release.

| Fichier | Chiffre |
| --- | --- |
| `mkDocs/docs/ma-moulinette/architecture/architecture-base-de-donnees.md` | Nombre de tables (`45 tables` — un fichier par table sous `migrations/POSTGRESQL/20_tables/`) |
| `mkDocs/docs/ma-moulinette/application/clean-code.md` | Nombre de colonnes des tableaux (Clean Code projet/synthèse portefeuille, export PDF) |
| `mkDocs/docs/ma-moulinette/application/statistiques.md` | Nombre de colonnes du tableau de synthèse (22 colonnes) |
| `mkDocs/docs/ma-moulinette/application/cosui.md` | Nombre d'axes du radar COSUI (6 axes) |
| `mkDocs/docs/ma-moulinette/application/projet.md`, `application/suivi.md` | Listes des indicateurs/mesures affichés (volumétrie, notes A-E) — décrivent les champs SonarQube exposés, pas une statistique du projet Ma-Moulinette lui-même |
| `mkDocs/docs/ma-moulinette/back-office/dashboard.md` | Mention de la source `admin-stats.json`, pas de chiffre propre |
| `mkDocs/docs/ma-moulinette/commandes/maintenance.md` | Chiffres illustratifs figés dans un callout `✅ Correction du ...` (ex. « 67→68 fichiers ») décrivant un bug précis et sa correction — ne pas toucher, ce n'est pas un relevé courant |

## 📚 Pour aller plus loin

- [Tests unitaires et d'intégration](test-unitaire.md) : détail des suites, couverture, PHPStan.
- `README.md` (racine du dépôt, hors périmètre mkDocs) : vue d'ensemble et badges.

-**-- FIN --**-
