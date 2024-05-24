# Ma-Moulinette en images

![Ma-Moulinette](/assets/images/home/home-000.jpg)

## Page COmité de SUIvi

Cette page affiche le tableau de suivi du Comité de Suivi des signalements SonarQube.

![suivi](/assets/images/cosui/cosui-001.jpg)

Le **setup** correspond à la valeur de la dernière analyse de répartition des signalements des modules de l'application. Si la valeur est à **NaN** le tableau affichera des valeurs égales à (0) zéro.

Le premier tableau présente les résultats de la dernière analyse SonarQube. Les informations sont les suivantes :

* **Application** : corresponds à la version de référence qui servira de version étalon pour les comparaisons.
* **Critère** : rassemble les critères qualité du moins importants au plus importants.
* **Version** : version de l'application.
* **Bloquant** : signalements bloquants.
* **Critique** : signalements critiques.
* **Majeur** : signalements majeurs.
* **Note** : note SonarQube.

![suivi](/assets/images/cosui/cosui-002.jpg)

En cliquant sur l'icône située à côté de la date de référence présente dans la colonne **Application**, vous afficherez les informations de références.

![suivi](/assets/images/cosui/cosui-003.jpg)

Si vous n'avez pas choisi de projet de référence, vous aurez un message d'erreur.

![suivi](/assets/images/cosui/cosui-002a.jpg)

Il est possible d'afficher les variations entre la version de référence et la dernière version en cliquant sur le bouton **Afficher les variations ?**

![suivi](/assets/images/cosui/cosui-004.jpg)

Le dernier tableau présente la répartition des signalements en fonction des modules applicatifs (front, back). Si une analyse de la répartition des signalements n'a pas été lancée, les valeurs seront toujours *null*.

![suivi](/assets/images/cosui/cosui-005.jpg)

Le graphique ci-dessous affiche un graphique de type radar entre la version de référence et la version courante de l'application selon les créitères suivants :

* [x] Fiabilité. 100 étant la meilleure note ;
* [x] Vulnérabilité. 100 étant la meilleure note ;
* [x] Hotspot. 100 étant la meilleure note ;
* [x] Maintenabilité. 100 étant la meilleure note ;
* [x] Couverture des tests unitaires. 100 étant la meilleure note ;
* [x] Ratio de la dette technique. 0 étant la meilleure note ;

![suivi](/assets/images/cosui/cosui-006.jpg)

-**-- FIN --**-

[Retour au menu principal](/index.html)
