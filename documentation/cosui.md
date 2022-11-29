# Ma-Moulinette en images

![Ma-Moulinette](/documentation/ressources/home-000.jpg)

## TOC

### Authentification

[Authentification](/documentation/authentification.md)
[Inscription](/documentation/inscription.md)
[Bienvenue]((/documentation/bienvenue.md))
[Gestion des utilisateurs](utilisateur.md)

### Ma-Moulinette

[Accueil](/documentation/accueil.md)
[Projet](/documentation/projet.md)
[OWASP](/documentation/owasp.md)
[Suivi](/documentation/suivi.md)
[**COSUI**](/documentation/cosui.md)
[Répartition détaillée](/documentation/repartition_details.md)

### Page COmité de SUIvi

Cette page affiche le tableau de suivi du Comité de Suivi des signalements Sonarqube.

![suivi](/documentation/ressources/cosui-001.jpg)

Le **setup** correspond à la valeur de la dernière analyse de répartition des signalements des modules de l'application. Si la valeur est à **NaN** le tableau afichera des valeurs égales à (0) zéro.

Le premier tableau présente les résultats de la dernière analyse sonarqube. Les informations sont les suivantes :

* **Application** : correspond à la version de référence qui servira de version étalon pour les comparaisons.
* **Critère** : rassemble les critères qualités du moins important au plus important.
* **Version** : version de l'application.
* **Bloquant** : signalements bloquants.
* **Critique** : signalements critiques.
* **Majeur** : signalements majeurs.
* **Note** : note Sonarqube.

![suivi](/documentation/ressources/cosui-002.jpg)

En cliquant sur l'icône située à côté de la date de référence présente dans la colonne **Application**, vous afficherez les informations de références.

![suivi](/documentation/ressources/cosui-003.jpg)

Il est possible d'afficher les variations entre la version de référence et la dernière version en cliquant sur le bouton **Afficher le variations ?**

![suivi](/documentation/ressources/cosui-004.jpg)

Le dernier tableau présente la répartition des signalement en fonction des modules applicatifs (front, back). Si une analyse de la répartition des signalement n'a pas été lancé, les valeurs seront toujours *null*.

![suivi](/documentation/ressources/cosui-005.jpg)

-**-- FIN --**-

[Retour au menu principal](/README.md)