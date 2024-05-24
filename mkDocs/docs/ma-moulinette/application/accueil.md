# Ma-Moulinette en images

![Ma-Moulinette](/assets/images/home/home-000.jpg)

## Évolutions

> En version **2.0.0** :

- [x] l'application est compatible multi-utilisateur (i.e. utilisation d'un serveur). La gestion des droits est renforcée et les préférences individuelles sont sauvegardées.
- [x] il est possible d'afficher les informations des projets favoris ou des versions des projets favoris.
- [x] Le *logger* est supprimé et laisse sa place à une boîte d'information.
- [x] l'icône **traitement** apparaît dès lors que vous avez le rôle **[BATCH]**.
- [x] l'icône **préférence** apparaît.
- [x] il est possible d'ouvrir, depuis la zone des favoris, le projet directement en cliquant sur l'icône de raccourci situé à côté du titre.
- [x] la gestion des préférences a été ajouté pour gérer ses projets et ses favoris.
- [x] la gestion de l’authentification a été améliorée. La sécurité a été renforcée.
- [x] l'identification de l'utilisateur et le renouvellement de son mot de passe a été ajouté.
- [x] prise en compte partielle du WCAG 2.2.
- [x] ajout du bloc Tags pour afficher le nombre de projet orphelin.

> En version **1.6.0** :

- [x] deux (2) nouveaux indicateurs apparaissent pour afficher le nombre de projets dont la visibilité est de type **public** ou **privé**.
- [x] la détection des changements sur les référentiels a été ajoutée.

> En version **1.5.0** :

- [x] la gestion des versions a été ajoutée.

## Page d'accueil

Cette page est la page d'ouverture de l'application. Elle permet :

- [x] de mettre à jour la liste du référentiel des applications SonarQube ;
- [x] de mettre à jour la liste du référentiel des règles SonarQube ;
- [x] d'afficher le nombre de projets de type **privé** ;
- [x] d'afficher le nombre de projets de type **public** ;
- [x] d'afficher le nombre de projet ayant un tags ;
- [ ] d'afficher les projets favoris par projet et/ou par version.

A l'ouverture de la page d'accueil, plusieurs situations peuvent se présenter à l'utilisateur comme :

- [ ] la version de l'application Ma-Moulinette installée n'est pas à jour ;
- [ ] l'application a détectée des changements sur le nombre de projets présent dans l'application et le nombre de projets existant sur le serveur SonarQube ;
- [ ] l'application à détectée un changement sur les référentiels de règles (profils) ;

> A tout moment, il est possible de revenir sur la page d'accueil en cliquant sur le nom de l'application **Ma Moulinette** situé en haut à gauche de la page.
![home](/assets/images/home/home-001.jpg)

### Les liens rapides

En haut à droite, cinq (5) liens sont affichés en fonction des droits de l'utilisateur (cf. chapitre sécurité) :

![home](/assets/images/home/home-002.jpg)

- [ ] `Utilisateur` : gestion des utilisateurs ;
- [ ] `Traitements` : gestion des traitements de masse ;
- [x] `Préférences` : gestion des préférences de l'utilisateur ;
- [x] `Dashboard` : informations sur l'application ;
- [x] `Logout` : pour se déconnecter de l'application ;

### La base de données locale n'est pas à jour

Si la version de l'application et de la base de données sont identiques, tout va bien. Par contre, si une différence est détectée, alors un message   est affiché à l'utilisateur connecté.

Il faudra passer le ou les scripts de migration pour aligner la version de l'application au shéma de la base de données.

![home](/assets/images/home/home-003.jpg)

### L'application a détectée des changements

Le processus de détection des changements sur les référentiels de projets et de profils signale toutes les modifications entre l'application Ma-Moulinette et le serveur SonarQube.

Lorsque l'application est installée pour la première fois, il est normal que le référentiel des projets et celui des profils soient vide.

> Ci-dessous le bloc **référentiel local**.

![home](/assets/images/home/home-004.jpg)

> ci-dessous le bloc **Tags**.

![home](/assets/images/home/home-005.jpg)

> Ci-dessous le bloc **Visibilité**.

![home](/assets/images/home/home-006.jpg)

> Ci-dessous le bloc **favoris**.

![home](/assets/images/home/home-007.jpg)

La mise à jour est signalée quand :

- [x] le nombre de projets et/ou de profils est différent de ceux présents sur le serveur SonarQube ;

Le contrôle se fait en fonction de la fréquence choisie, par défaut :

- [x] 1 jour pour les projets ;
- [x] 30 jours pour les profils ;

Cela veut dire que si la table de références des projets et des profils a été mise à jour dans la journée, il n'y aura pas de signalement en cas de différences avec le serveur SonarQube. En d'autres termes, il faudra lancer la mise à jour de la table locale manuellement, comme on le faisait précédemment.

Si la table des projets et des profils n'est pas à jour, un message s'affiche pour indiquer que la mise à jour est recommandée. Le nombre de projets et/ou de profils en plus ou en moins est indiqué.

![home](/assets/images/home/home-008.jpg)

Lorsque le nombre de **projets** a changé :

![home](/assets/images/home/home-009.jpg)

Lorsque le nombre de **profils** a changé :

![home](/assets/images/home/home-010.jpg)

### Rôle nécessaire pour effectuer une mise à jour

Pour effectuer la mise à jour, il faut avoir le rôle **[COLLECTEUR]**.

Par défaut, le bouton est désactivé si l'utilisateur n'a pas de droits suffisant :

![home](/assets/images/home/home-011.jpg)

Un message d'erreur est affiché si l'utilisateur tente de forcer l'action.

![home](/assets/images/home/home-012.jpg)

### Mise à jour d'un référentiel

En cliquant sur le bouton de mise à jour des projets ou des profils, l'indicateur se met à jour et le message disparaît.

![home](/assets/images/home/home-013.jpg)

### Les messages d'erreur

Les messages d'erreurs peuvent apparaître si :

> Les paramètres de la requête ne sont pas correctes.

![home](/assets/images/home/home-014.jpg)

> Le serveur n'est pas disponible.

![home](/assets/images/home/home-015.jpg)

> Aucun projet n'a été trouvé.

![home](/assets/images/home/home-016.jpg)

### Le bloc Tags

Le bloc **Tags** affiche le nombre de projet disponible dans l'application **Ma-Moulinette** et le nombre d'application disposant d'un **Tags**.

![home](/assets/images/home/home-017.jpg)

> Important.

Un **projet** doit être rattaché à une **équipe** et un **utilisateur**. ce rattachement se fait par le biais d'un **Tag** disponible sur le projet disponible sur le serveur SonarQube.

En d'autres termes, si un projet n'a pas de Tag SonarQube, il ne sera jamais disponible dans la liste des applications.

L'icone **info** disponible dans la zone de légende du formulaire, affiche une information sur ce sujet dans une fenêtre modale.

![home](/assets/images/home/home-018.jpg)

### Le bloc Visibilité

ce bloc d'information indique affiche le nombre de projet **public** ou **privé**.

Le bouton **mon projet** permet l'accès à la page des projets.

![home](/assets/images/home/home-019.jpg)

Dans la partie inférieure, la liste des applications favorites est affichée par projet et/ou par version.

- [x] si l'utilisateur a désactivé dans ces préférences la gestion des favoris, alors rien n'est affiché.

- [x] si l'utilisateur a activé dans ces préférences la gestion des favoris par **projet**.

![home](/assets/images/home/home-020.jpg)

- [x] si l'utilisateur a activé dans ces préférences la gestion des favoris par **version de projet**.

![home](/assets/images/home/home-021.jpg)

Il est possible d'afficher directement le projet en cliquant sur l'icône située à **côté du titre** de la boîte d'information.

![home](/assets/images/home/home-021.jpg)

-**-- FIN --**-

[Retour au menu principal](/index.html)
