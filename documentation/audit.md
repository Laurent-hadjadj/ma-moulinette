# Adit de sécurité

![Ma-Moulinette](/documentation/ressources/home-000.jpg)

## symfony security : check

Lancez la commande : `symfony check:security`

```console
symfony Security Check Report
=============================
No packages have known vulnerabilities.
```

| version |   date     | check |
|:-------:|------------|-------|
| 1.0.0   | 28/03/2022 | PASS  |
| 1.1.0   |            | ---   |
| 1.2.0   | 24/04/2022 | PASS  |
| 1.3.0   | 03/07/2022 | PASS  |
| 1.4.0   | 06/07/2022 | PASS  |
| 1.5.0   | 12/09/2022 | PASS  |
| 1.6.0   | 30/11/2022 | PASS  |

## npm audit

Lancez la commande : `npm audit`

`Audit du 30/11/2022`

```console
=== npm audit security report ===

found 0 vulnerabilities
 in 734 scanned packages
```

| version |   date     | package | check |
|:-------:|------------|---------|-------|
| 1.5.0   | 12/09/2022 |  926    | PASS  |
| 1.6.0   | 30/11/2022 |  734    | PASS  |

## Analyse de codes W3C et Sonarqube

Cette section présente les résultats de l'analyse sonarqube des versions **Release** de l'application ma-moulinette.

Les résultats de l'analyse du W3C ne vaut que pour la version 2.0.0-RELEASE.

### Version et référentiel sonarqube

La verison de sonarqube utilisée pour rélaiser l'ensemble des analyses est la version **8.9.9 (build 56886) LTS**.

La barière qualité est constituée des règles suivantes :

![quality-gate](/documentation/ressources/audit-001.jpg)

Les régles profils qualitités utilisés lors de l'analyse sont les suivantes :

* [x] HTML (61) ;
* [x] CSS (31) ;
* [x] JSON (12) ;
* [x] Javascript (231) ;
* [x] PHP (203) ;
* [x] YAML (19) ;

#### Analyse de la version **1.0.0**

Résultats sans filtres :

![sonarqube v1.0.0](/documentation/ressources/audit-v1.0.0a.jpg)

![ma-moulinette v1.0.0](/documentation/ressources/ma-moulinette-v1.0.0.jpg)

Résultats avec prise en compte des **Faux positifs**.

![sonarqube-001](/documentation/ressources/audit-v1.0.0.jpg)

Liste des "**faux positifs**" où "**ne sera pas corrigé**".

* [**009**] **Faux positif** `javascript:S1451` :  L'expression régulière ne fonctionne pas. Les fichiers on bien un entête de copyright.
* [**026**] **Faux positif** `php:S1451` : L'expression régulière ne fonctionne pas. Les fichiers on bien un entête de copyright.
* [**005**] **Faux positif** `Web:PageWithoutTitleCheck` : La balise <\title> est injectée en TWIG.
* [**020**] **Faux positif** `Web:UnclosedTagCheck` : Twig non pris en charge par le parseur HTML de sonarqube. Par exemple dans l'expresssion {% if toto < 1 %} Le parseur trouve une balse html ouvrante qui n'est jamais fermée.

* [**148**] `php:S116` **Ne sera pas corrigé** : Nommage des entity conforme à la norme SQL. Utilisation de PascalCase à la place de camelCase. **J'assume !!!**.
* [**043**] **Faux positif** php:S1578 : Framework Symfony.

* [**002**] **Faux positif** `Weak Cryptography` : Utiliation de la fonction Math.random pour effectuer un tirage aléatoire sur un tabeau de couleurs. Les deux sighanelemnts seront corrigés. *Oui Monsieur !!!*.

Les règles suivantes sont déclarées en : **Ne sera pas corrigé**. Il n'est pas toujours possible dans le "contexte fonctionnel" de l'application de faire autrement.
*Non je rigole !!!* Ces signalememnts seront donc pris en compte lors des prochaines versions.

##### Compléxité Cognitive

C'est la complexité liée aux besoins fonctionnels.

* [**007**] `php:S3776` : Complexité cognitive.
* [**006**] `javascript:S3776` : Complexité cognitive.

##### Compléxité Cyclomatique

C'est la compléxité liée au nombre d'instruction où de ligne de code pour réaliser une action.

* [**003**] `php:S1541` : Complexité cyclomatique.
* [**025**] `php:S134` : Control Flow supérieur à 4 (if, else, switch,...).
* [**006**] `javascript:S1541` : Complexité cyclomatique.

##### Tests Unitaires

Pas de tests unitaires prévus. Enfin, quand j'aurais appris à en faire ne php j'en ferais certainement.

* [***009***] common-js:InsufficientLineCoverage.
* [***044***] common-php:InsufficientLineCoverage.

##### Analyse de la version **1.1.0**

Résultats sans filtres :

![sonarquve 1.1.0](/documentation/ressources/audit-v1.1.0.jpg)

![ma-moulinette v1.1.0](/documentation/ressources/ma-moulinette-v1.1.0.jpg)

Résultats avec prise en compte des **Faux positifs**.

![sonarqube-001](/documentation/ressources/audit-v1.1.0.jpg)

##### Analyse de la version **1.2.0**

Résultats sans filtres :

![sonarquve 1.2.0](/documentation/ressources/audit-v1.2.0a.jpg)

![ma-moulinette v1.2.0](/documentation/ressources/ma-moulinette-v1.2.0.jpg)

Résultats avec prise en compte des **Faux positifs**.

![sonarqube-001](/documentation/ressources/audit-v1.2.0.jpg)

Liste des "**Faux positifs**" où "**ne sera pas corrigé**".

* [**06**] **Faux positif**Web:PageWithoutFaviconCheck : Include Twig depuis le composants header ;

##### Compléxité Cognitive (1.2.0)

* [**07**] `php:S3776` : Complexité cognitive.
* [**06**] `javascript:S3776` : Complexité cognitive.

##### Compléxité Cyclomatique (1.2.0)

* [**02**] `php:S1541` : Complexité cyclomatique.
* [**05**] `javascript:S1541` : Complexité cyclomatique.
* [**23**] `php:S134` : Control Flow supérieur à 4 (if, else, switch,...) ;

##### Analyse de la version **1.2.6**

Résultats sans filtres :

![sonarquve 1.2.6](/documentation/ressources/audit-v1.2.6.jpg)

![ma-moulinette v1.2.6](/documentation/ressources/ma-moulinette-v1.2.6.jpg)

Résultats avec prise en compte des **Faux positifs**.

![sonarquve 1.2.5](/documentation/ressources/audit-v1.2.5.jpg)

Liste des "**Faux positifs**" où "**ne sera pas corrigé**".

* [**15**] **Faux positifs** `php:S116` : Nommage des entity conforme à la norme SQL ;

##### Compléxité Cognitive (1.2.6)

* [**05**] `php:S3776` : Complexité cognitive.

##### Compléxité Cyclomatique (1.2.6)

* [**05**] `php:S1541` : Complexité cyclomatique.
* [**07**] `php:S134` : Control Flow supérieur à 4 (if, else, switch,...) ;

##### Analyse de la version **1.3.0**

Résultats sans filtres :

![sonarquve 1.3.0](/documentation/ressources/audit-v1.3.0a.jpg)

![ma-moulinette v1.3.0](/documentation/ressources/ma-moulinette-v1.3.0.jpg)

Résultats avec prise en compte des **Faux positifs**.

![sonarquve 1.3.0](/documentation/ressources/audit-v1.3.0.jpg)

Liste des "**Faux positifs**" où "**ne sera pas corrigé**".

* [**385**] `javascript:S109` : Ajout d'un commentaire ;

##### Analyse de la version **1.4.0**

Résultats sans filtres :

![sonarquve 1.4.0](/documentation/ressources/audit-v1.4.0a.jpg)

![ma-moulinette v1.4.0](/documentation/ressources/ma-moulinette-v1.4.0.jpg)

Résultats avec prise en compte des **Faux positifs**.

![sonarquve 1.4.0](/documentation/ressources/audit-v1.4.0.jpg)

Liste des "**Faux positifs**" où "**ne sera pas corrigé**".

* [**01**] **Faux positif** `Web:PageWithoutTitleCheck` : La balise <\title> est injectée en TWIG ;

##### Analyse de la version **1.5.0**

Résultats sans filtres :

![sonarquve 1.5.0](/documentation/ressources/audit-v1.5.0a.jpg)

![ma-moulinette v1.5.0](/documentation/ressources/ma-moulinette-v1.5.0.jpg)

Résultats avec prise en compte des **Faux positifs**.

![sonarquve 1.5.0](/documentation/ressources/audit-v1.5.0.jpg)

Liste des "**Faux positifs**" où "**Ne sera pas corrigé**".

* [**010**] **Faux positif** `Web:PageWithoutTitleCheck` : La balise <\title> est injectée en TWIG ;
* [**014**] **Faux positif** `Web:UnclosedTagCheck` : Twig non pris en charge par le parseur HTML de sonarqube ;
* [**005**] **Faux positif** `php:S1451` : L'expression régulière ne fonctionne pas. Les fichiers on bien un entête de copyright.

* [**015**] **Sera corrigé** `javascript:S109` : Un commentaire a été ajouté.

* [**115**] **Ne sera pas corrigé** `php:S117` : Utilisation de pascalCase à la place de camelCase pour nommer les variables ;

##### Analyse de la version **1.6.0**

Résultats sans filtres :

![sonarquve 1.6.0](/documentation/ressources/audit-v1.6.0a.jpg)

![ma-moulinette v1.6.0](/documentation/ressources/ma-moulinette-v1.6.0.jpg)

Résultats avec prise en compte des **Faux positifs**.

![sonarquve 1.6.0](/documentation/ressources/audit-v1.6.0.jpg)

### Bilan Sonarqube 1.6.0

Pour la version 1.6.0 :

* [x] Fiabilité     : 61 bugs ;
* [x] Sécurité      : 0 failles ;
* [x] Hotspot       : 2 hotspots review ;
* [x] Mainetabilité : 1 865 mauvaises pratiques.

![ma-moulinette v1.6.0-suivi](/documentation/ressources/ma-moulinette-v1.6.0-suivi.jpg)

![ma-moulinette v1.6.0-cosui](/documentation/ressources/ma-moulinette-v1.6.0-cosui.jpg)

#### Fiablilité (1.6.0)

##### 31 signalements majeures

* [**17**] **Faux positif** `Web:PageWithoutTitleCheck` : Add a <\title> tag to this page.
* [**11**] **Sera corrigé** `Web:TableHeaderHasIdOrScopeCheck` : "<\th>" tags should have "id" or "scope" attributes.
* [**02**] **Sera corrigé** `Web:InputWithoutLabelCheck` : Associate a valid label to this input field..
* [**01**] **Sera corrigé** `php:S836` : Variables should be initialized before use.

##### 30 signalements mineures

* [**17**] **Faux positif** `Web:UnclosedTagCheck` : All HTML tags should be closed.

#### Sécurité (1.6.0)

Rien à signaler.

#### Maintenabilité (1.6.0)

##### 17 signalements bloquants

* [**16**] **Faux positif** `javascript:S1451` : Track lack of copyright and license headers.
* [**01**] **Sera corrigé** `yaml:ParsingErrorCheck` : YAML parser failure.

##### 133 signalements critiques

* [**05**] **Sera corrigé** `php:S1192` : String literals should not be duplicated.
* [**46**] **Sera corrigé** `javascript:S1192` : String literals should not be duplicated.
* [**32**] **Sera corrigé** `javascript:S3353` : Unchanged variables should be marked "const".
* [**32**] **Sera corrigé** `javascript:S121` : Control structures should use curly braces.
* [**01**] **Sera corrigé** `javascript:S4123` : "await" should only be used with promises.

* [**32**] **Ne sera pas corrigé** `php:S134` : Control flow statements "if", "for", "while", "switch" and "try" should not be nested too deeply.
* [**07**] **Ne sera pas corrigé** `php:S1541` : Cyclomatic Complexity of functions should not be too high.
* [**07**] **Ne sera pas corrigé** `javascript:S1541` : Cyclomatic Complexity of functions should not be too high.

* [**10**] **Ne sera pas corrigé** `php:S3776` : Cognitive Complexity of functions should not be too high.
* [**07**] **Ne sera pas corrigé** `javascript:S3776` : Cognitive Complexity of functions should not be too high.

##### 1087 signalements majeures

###### Faux positifs - majeures

* [**17**] **Faux positif** `Web:PageWithoutFaviconCheck` : Favicons should be used in all pages.
* [**09**] **Faux positif** `common-php:DuplicatedBlocks` : Source files should not have any duplicated blocks.
* [**07**] **Faux positif** `common-web:DuplicatedBlocks` : Source files should not have any duplicated blocks.
* [**04**] **Faux positif** `Web:S1829` : Web pages should not contain absolute URIs.

###### Corrections - majeures

* [**256**] **Sera corrigé** `Web:MaxLineLengthCheck` : Lines should not be too long.
* [**126**] **Sera corrigé** `Web:InlineStyleCheck` : The "style" attribute should not be used.
* [**38**] **Sera corrigé** `php:S125` : Sections of code should not be commented out.
* [**28**] **Sera corrigé** `common-php:InsufficientCommentDensity` : Source files should have a sufficient density of comment lines.
* [**20**] **Sera corrigé** `javascript:S122` : Statements should be on separate lines.
* [**16**] **Sera corrigé** `javascript:S1440` : === and !== should be used instead of == and !=.
* [**15**] **Sera corrigé** `javascript:S3760` : Arithmetic operators should only have numbers as operands.
* [**12**] **Sera corrigé** `Web:LinkToNothingCheck` : Links should not target "#" or javascript:void(0)

###### On ne fait rien - majeures

* [**380**] **Ne sera pas corrigé** `javascript:S109` : Magic numbers should not be used. Un commentaire sera ajouté notammement pour les codes HTTP ou les indices ;
* [**63**] **Ne sera pas corrigé** `common-php:InsufficientLineCoverage` : Lines should have sufficient coverage by tests.
* [**15**] **Ne sera pas corrigé** `common-js:InsufficientLineCoverage` : Lines should have sufficient coverage by tests.
* [**13**] **Ne sera pas corrigé** `php:S2042` : Classes should not have too many lines of code.
* [**10**] **Ne sera pas corrigé** `php:S1151` : "switch case" clauses should not have too many lines of code.

##### 1087 signalements mineures

###### Faux positifs - mineures

* [**64**] **Faux positifs** `php:S1578` : File names should comply with a naming convention.

###### Corrections - mineures

* [**172**] **Sera corrigé** `php:S117` : Local variable and function parameter names should comply with a naming convention
* [**171**] **Sera corrigé** `php:S116` : Field names should comply with a naming convention.
* [**47**] **Sera corrigé** `javascript:S1438` : Statements should end with semicolons.
* [**31**] **Sera corrigé** `javascript:S1105` : An open curly brace should be located at the end of a line.
* [**28**] **Sera corrigé** `javascript:S1441` : Quotes for string literals should be used consistently.
* [**21**] **Sera corrigé** `javascript:S3723` : Trailing commas should be used.
* [**17**] **Sera corrigé** `javascript:S3524` : Braces and parentheses should be used consistently with arrow functions.
* [**15**] **Sera corrigé** `php:S1481` : Unused local variables should be removed.
* [**10**] **Sera corrigé** `Web:IllegalTabCheck` : Tabulation characters should not be used.
* [**10**] **Sera corrigé** `javascript:S117` : Variable, property and parameter names should comply with a naming convention.
* [**07**] **Sera corrigé** `javascript:S3498` : Object literal shorthand syntax should be used.
* [**07**] **Sera corrigé** `javascript:S1537` : Trailing commas should not be used.
* [**05**] **Sera corrigé** `javascript:S3512` : Template strings should be used instead of concatenation.

###### On ne fait rien - mineures

* [**31**] **Ne Sera pas corrigé** `Web:NonConsecutiveHeadingCheck` : Heading tags should be used consecutively from "H1" to "H6".

-**-- FIN --**-

[Retour au menu principal](/README.md)
