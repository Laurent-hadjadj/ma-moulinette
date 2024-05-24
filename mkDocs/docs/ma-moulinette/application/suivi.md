# Ma-Moulinette en images

![Ma-Moulinette](/assets/images/home/home-000.jpg)

## Page Suivi des indicateurs

La page permet l'affichage des 10 dernières versions de l'application sélectionnée.

![suivi](/assets/images/suivi/suivi-001.jpg)

Cette page regroupe plusieurs indicateurs construits sur les données locales historisées et trois boutons d'action pour :

* [ ] Imprimer un rapport PDF ;
* [ ] Ajouter les indicateurs consolidés d'une version précédente à la version courante ;
* [ ] Modifier les **favoris** et la **version de référence** ;

> Pensez à enregistrer chaque collecte de données.

Si aucune données n'est enregistrée dans la table *historique*, alors la page s'ouvre sans aucune information.

![suivi](/assets/images/suivi/suivi-001a.jpg)

> Si la clé du projet n'est pas correcte alors un message est affiché.

![suivi](/assets/images/suivi/suivi-001b.jpg)

Les tableaux sont les suivants :

* [x] le tableau de suivi des indicateurs clés ;
![suivi](/assets/images/suivi/suivi-002.jpg)

* [x] la courbe cumulée des signalements par type ;
![suivi](/assets/images/suivi/suivi-003.jpg)

* [x] le tableau de répartition des signalements par module (Présentation - Frontend, Métier - Backend et les Autres) ;
![suivi](/assets/images/suivi/suivi-004.jpg)

* [x] le tableau de répartition des signalements par niveau de criticité et leurs évolutions entre chaque version ;
![suivi](/assets/images/suivi/suivi-005.jpg)

* [X] le tableau de suivi du niveau de criticité en fonction de la nature du signalement et leur évolution ;
![suivi](/assets/images/suivi/suivi-006.jpg)

### Ajouter une analyse

Cette page permet d'ajouter une version présente dans l'historique, i.e. dont vous avez déjà collecté les informations et enregistré dans la table historique.

![suivi](/assets/images/suivi/suivi-ajout-001.jpg)

Choisissez une version disponible dans la liste des versions.

![suivi](/assets/images/suivi/suivi-ajout-002.jpg)

Si le projet n'existe plus dans SonarQube, un message d'erreur est affiché et aucune valeur n'est remontée.

![suivi](/assets/images/suivi/suivi-ajout-003.jpg)

Si le projet n'est pas sélectionné ou que le projet n'existe, mais que l'on clique quand même sur le bouton, **Ajouter** alors **badaboum**.

![suivi](/assets/images/suivi/suivi-ajout-004.jpg)

Si tout va bien :) Les informations sont affichées.

![suivi](/assets/images/suivi/suivi-ajout-005.jpg)

Il suffit de cliquer sur le bouton **Ajouter** pour ajouter cette version dans le tableau de comparaisons.

![suivi](/assets/images/suivi/suivi-ajout-006.jpg)

**Note** : en version **1.6.0**, le formulaire a été simplifié. Il n'est plus possible de saisir les données manquantes lors de la collecte.

### Modifier la configuration

Il est possible de définir les propriétés d'une version d'un projet.

* [ ] La version favorite d'un projet est propre à chaque utilisateur.
* [ ] La version de référence pour ce projet est partagée par tous les utilisateurs. Elle est définie par l'utilisateur ayant le rôle **gestionnaire**.
* [ ] La suppression, i.e. la poubelle, permet de supprimer la version du projet de l'historique. Seul l'utilisateur ayant le rôle **gestionnaire** peut réaliser cette action.

![suivi-modification](/assets/images/suivi/suivi-modification-001.jpg)

#### Les favoris

La version favorite d'un projet et/ou d'une version est définie pour chaque utilisateur dans ses préférences (depuis la version 2.0.0).

> Une seule instance du projet peut être en favoris par contre il est possible d'avoir plusieurs versions de ce projet en favoris.

Il suffit pour cela de cliquer sur le bouton de sélection pour choisir de mettre la version en favori.

Si un projet n'a pas été sélectionné, i.e. la clé maven est non défini, alors un message d'erreur est affiché à l'utilisateur.

![suivi-modification](/assets/images/suivi/suivi-modification-004.jpg)

Si tout va bien, le projet est enregistré dans les favoris de l'utilisateur.

![suivi-modification](/assets/images/suivi/suivi-modification-008.jpg)

Si l'utilisateur souhaite supprimer la version des favoris, il lui suffira de désélectionner la version à partir du bouton de sélection.

![suivi-modification](/assets/images/suivi/suivi-modification-010.jpg)

Enfin, en mode **test**, un message d'erreur est affiché.

![suivi-modification](/assets/images/suivi/suivi-modification-009.jpg)

#### La version de référence

La version de référence correspond à la version initiale à partir de laquelle toutes les mesures sont comparées.

> Il ne peut y a voir qu'une seule version de référence.

Si un projet n'a pas été sélectionné, i.e. la clé maven est non défini, alors un message d'erreur est affiché à l'utilisateur.

![suivi-modification](/assets/images/suivi/suivi-modification-004.jpg)

Il suffit de cliquer sur le bouton de sélection de la version pour enregistrer la version en référence pour les calculs.

![suivi-modification](/assets/images/suivi/suivi-modification-002.jpg)

Le choix de la version de référence n'est possible que si l'utilisateur a le rôle **gestionnaire**.

![suivi-modification](/assets/images/suivi/suivi-modification-003.jpg)

#### Supprimer la version

Il suffit de cliquer sur le bouton *effacer* la version de l'historique, i.e. la poubelle. Cette action nécessite d'avoir le rôle **gestionnaire**.

![suivi-modification](/assets/images/suivi/suivi-modification-003.jpg)

Ici, la suppression a été simulée en mode TEST (TU).

![suivi-modification](/assets/images/suivi/suivi-modification-005.jpg)

Le projet a été correctement supprimé.

![suivi-modification](/assets/images/suivi/suivi-modification-006.jpg)

Le projet a été correctement supprimé ainsi que dans la liste des projets/versions de favoris.

![suivi-modification](/assets/images/suivi/suivi-modification-007.jpg)

-**-- FIN --**-

[Retour au menu principal](/index.html)
