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

## Analyse de code sonarqube

Analyse de la version **1.1.0**.

![sonarqube-001](/documentation/ressources/sonarqube-001.jpg)

New code.

![sonarqube-002](/documentation/ressources/sonarqube-002.jpg)

Overall code.

![sonarqube-003](/documentation/ressources/sonarqube-003.jpg)

Répartiton par dossier.

![sonarqube-002](/documentation/ressources/sonarqube-004.jpg)

Analyse Ma-moulinette.

![ma-moulinette](/documentation/ressources/ma-moulinette-001.jpg)

-**-- FIN --**-

[Retour au menu principal](/README.md)
