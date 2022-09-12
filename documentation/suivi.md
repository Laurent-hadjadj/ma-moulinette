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

### Page Suivi des indicateurs

La page permet l'affichage des 10 dernières versions de l'application sélectionnée.

![suivi](/documentation/ressources/suivi-001.jpg)

Le tableau de suivi :

![suivi](/documentation/ressources/suivi-002.jpg)

La jolie courbe :

![suivi](/documentation/ressources/suivi-003.jpg)

La répartition par module :

![suivi](/documentation/ressources/suivi-004.jpg)

La répartition par type :

![suivi](/documentation/ressources/suivi-005.jpg)

La répartition par type et sévérité :

![suivi](/documentation/ressources/suivi-006.jpg)

### Page Suivi/modification

Il est possible de modifier les paramètres d'affichage d'une version en activant ou non l'option favori et/ou l'option version de référence.

![suivi-modification](/documentation/ressources/suivi-modification-001.jpg)

![suivi-modification](/documentation/ressources/suivi-modification-002.jpg)

`Note :` la version de référence est la version qui sera utilisé pour comparer la tendance.

### Page de suivi/répartition-module

Cette page affiche la liste des anomalies totales par type et sévérité. Il est possible de lancer une collecte pour chaque type (fiabilité, sécurité et maintenabilité) et d'en calculer la répartition par module.

Tableau de répartition des sévérités par type :

![repartition-module](/documentation/ressources/repartition-module-001.jpg)

Le menu est ouvert.
![repartition-module](/documentation/ressources/repartition-module-001a.jpg)

La page permet d'accéder au processus de **Collecte** (1) et au processus d'**Analyse** (2).

Pour chaque type (fiabilité, sécurité et maintenabilité), il est possible de collecter jusqu'à 50 000 signalements.

![repartition-module](/documentation/ressources/repartition-module-002.jpg)

Au lancement, on vérifie si, il existe un "set-up", une collecte déjà présente pour ce projet. Si un "set-up" existe, on en créé un nouveau pour la nouvelle collecte.

![repartition-module](/documentation/ressources/repartition-module-004.jpg)

À la fin du traitement, l'indicateur de l'étape passe en orange. Attention, le traitement peut prendre plusieurs minutes.

Le bouton "supprimer" permet de purger la base "tampon" des données du projet. Il faudra alors lancer un VACCUM sur la base pour la défragmenter.

L'indicateur de progression indique l'état d'avancement de la collecte. La durée est exprimée en minutes et secondes.

La collecte progresse :)
![repartition-module](/documentation/ressources/repartition-module-005.jpg)

La collecte est terminée.
![repartition-module](/documentation/ressources/repartition-module-006.jpg)

La phase d'analyse permet de lancer et afficher le tableau de répartition par module des signalements sonarqube. Il faut pour cela choisir le bouton **Analyser**.

Le bouton **Afficher** permet quant à lui d'afficher la dernière analyse si elle existe pour le projet.

![repartition-module](/documentation/ressources/repartition-module-003.jpg)

Le processus va permettre de répartir les signalements en pour chacun des types en fonction de leur nature (frontend, backend, autres).

![repartition-module](/documentation/ressources/repartition-module-007.jpg)
![repartition-module](/documentation/ressources/repartition-module-008.jpg)

-**-- FIN --**-

[Retour au menu principal](/README.md)
