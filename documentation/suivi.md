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
[**Suivi**](/documentation/suivi.md)
[COSUI](/documentation/cosui.md)
[Répartition détaillée](/documentation/repartition_details.md)

### Page Suivi des indicateurs

La page permet l'affichage des 10 dernières versions de l'application sélectionnée.

![suivi](/documentation/ressources/suivi-001.jpg)

Cette page regroupe plusieurs indicateurs construit sur les données locales historisées et  trois boutons  d'action pour :

- [ ] Imprimer un rapport PDF ;
- [ ] Ajouter les indicateurs consolidés d'une version précédente à la version courrante ;
- [ ] Modifier les **favoris** et la **version de référence** ;

**Pensez à enregistrer  chaque collecte de données.**

Les tableaux sont les suivants :

- [x] Le tableau de suivi des indicateurs clés ;
![suivi](/documentation/ressources/suivi-002.jpg)

- [x] La courbe cumulé des signalements par type ;
![suivi](/documentation/ressources/suivi-003.jpg)

- [x] Le tableau de répartition des signalements par module (Présentation - Frontend, Métier - Backend et les Autres) ;
![suivi](/documentation/ressources/suivi-004.jpg)

- [x] Le tableau de répartition des signalements par niveau de criticité et leurs évolutions entre chaque version ;
![suivi](/documentation/ressources/suivi-005.jpg)

- [X] Le tableau de suvivi du niveau de criticité en fonction de la nature du signalement et leur évolution ;
![suivi](/documentation/ressources/suivi-006.jpg)

### Ajouter une analyse

Cette page permet d'ajouter une version présente dans l'historique, i.e. dont avous avez déjà collecter les informations et enregistré dans la table historique.

![suivi](/documentation/ressources/suivi-ajout-001.jpg)

Choisissez une version disponible dans la liste des versions.

![suivi](/documentation/ressources/suivi-ajout-002.jpg)

Si le projet n'existe plus dans sonarqube, un message d'erreur est affiché et aucune valeur n'est remontée.

![suivi](/documentation/ressources/suivi-ajout-003.jpg)

Si le projet n'est pas selectionné ou que le projet n'existe, mais que l'on clique quand même sur le bouton **Ajouter** alors **badaboum**.

![suivi](/documentation/ressources/suivi-ajout-004.jpg)

Si tout vas bien :) Les informations sont affichées.

![suivi](/documentation/ressources/suivi-ajout-005.jpg)

Il suffit de cliquer sur le bouton **Ajouter** pour ajouter cette version dans le tableau de comparaisons.

![suivi](/documentation/ressources/suivi-ajout-006.jpg)

**Note** : en version **1.6.0**, le formulaire a été simplifié. Il n'est plus possible de saisir les données manquantes lors de la collecte.

### Modifier la configuration

Il est possible de modifier les paramètres d'affichage d'une version en activant ou non l'option **favori** et/ou l'option **version de référence**.

![suivi-modification](/documentation/ressources/suivi-modification-001.jpg)

![suivi-modification](/documentation/ressources/suivi-modification-002.jpg)

`Note :` la version de référence est la version qui sera utilisé pour comparer les versions lors da présentation de la tendance.

-**-- FIN --**-

[Retour au menu principal](/README.md)
