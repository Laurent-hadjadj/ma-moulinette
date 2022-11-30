# Ma-Moulinette en images

![Ma-Moulinette](/documentation/ressources/home-000.jpg)

## TOC

### Authentification

* [Authentification](/documentation/authentification.md)
* [Inscription](/documentation/inscription.md)
* [Bienvenue]((/documentation/bienvenue.md))
* [Gestion des utilisateurs](utilisateur.md)

### Ma-Moulinette

* [Accueil](/documentation/accueil.md)
* [Projet](/documentation/projet.md)
* [OWASP](/documentation/owasp.md)
* [Suivi](/documentation/suivi.md)
* [COSUI](/documentation/cosui.md)
* [**Répartition détaillée**](/documentation/repartition_details.md)

### Répartition détaillée par module

Cette page affiche la liste de l'ensemble des signalements par type et sévérité. Il est possible de lancer une collecte pour chaque type (fiabilité, sécurité et maintenabilité) et d'en calculer la répartition par module.

Le tableau ci-dessous présente la répartition des sévérités par type :

![repartition-module](/documentation/ressources/repartition-module-001.jpg)

Le menu est ouvert.
![repartition-module](/documentation/ressources/repartition-module-001a.jpg)

La page permet d'accéder au processus de **Collecte** (1) et au processus d'**Analyse** (2).

Pour chaque type (fiabilité, sécurité et maintenabilité), il est possible de collecter jusqu'à 50 000 signalements.

![repartition-module](/documentation/ressources/repartition-module-002.jpg)

Au lancement, on vérifie si, il existe un **set-up**, i.e. une collecte déjà présente pour ce projet. Si un **set-up** existe, on en créé un nouveau pour la nouvelle collecte.

![repartition-module](/documentation/ressources/repartition-module-004.jpg)

À la fin du traitement, l'indicateur de l'étape passe en orange. Attention, le traitement peut prendre plusieurs - dizaines de - minutes.

Le bouton **supprimer** permet de purger la base **tampon** des données du projet. Il faudra alors lancer un VACCUM sur la base pour la défragmenter.

L'indicateur de progression indique l'état d'avancement de la collecte. La durée est exprimée en minute et seconde.

La collecte progresse :)
![repartition-module](/documentation/ressources/repartition-module-005.jpg)

La collecte est terminée.
![repartition-module](/documentation/ressources/repartition-module-006.jpg)

La phase d'analyse permet de lancer et afficher le tableau de répartition par module des signalements sonarqube. Il faut pour cela cliquer sur le bouton **Analyser**.

Le bouton **Afficher** permet quant à lui d'afficher la dernière analyse si elle existe pour le projet.

![repartition-module](/documentation/ressources/repartition-module-003.jpg)

Le processus va permettre de répartir les signalements pour chacun des types en fonction de leurs natures (frontend, backend, autres).

![repartition-module](/documentation/ressources/repartition-module-007.jpg)
![repartition-module](/documentation/ressources/repartition-module-008.jpg)

-**-- FIN --**-

[Retour au menu principal](/README.md)
