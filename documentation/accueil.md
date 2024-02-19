# Ma-Moulinette en images

![Ma-Moulinette](/documentation/ressources/home-000.jpg)

## TOC

### Authentification

- [Authentification](/documentation/authentification.md)
- [Inscription](/documentation/inscription.md)
- [Bienvenue]((/documentation/bienvenue.md))
- [Gestion des utilisateurs](utilisateur.md)

### Ma-Moulinette

- [**Accueil**](/documentation/accueil.md)
- [Profil-details](/documentation/profil-details.md)
- [Profil](/documentation/profil.md)
- [Preferences](/documentation/preferences.md)
- [Projet](/documentation/projet.md)
- [OWASP](/documentation/owasp.md)
- [Suivi](/documentation/suivi.md)
- [COSUI](/documentation/cosui.md)
- [Répartition détaillée](/documentation/repartition_details.md)
- [Préférences](/documentation/preferences.md)

## Évolutions

> En version **2.0.0** :

- [x] l'application est compatible multi-utilisateur (i.e. utilisation d'un serveur). La gestion des droits est renforcée et les préférences individuelles sont sauvegardées.
- [x] il est possible d'afficher les informations des projets favoris ou des versions des projets favoris.
- [x] Le *logger* est supprimé et laisse sa place à une boîte d'information.
- [x] l'icône **traitement** apparaît dès lors que vous avez le rôle **[BATCH]**.
- [x] l'icône **préférence** apparaît.
- [x] il est possible d'ouvrir, depuis la zone des favoris, le projet directement en cliquant sur l'icône de raccourci situé à côté du titre.
- [x] la gestion des préférences a été ajouté pour gérer ses projets et ses favoris.
- [x] la gestion de l'authentificationa a été améliorée. La sécurité a été renforcée.
- [x] l'identification de l'utilisateur et le renouvellement de son mot de passe a été ajouté.
- [x] prise en compte partielle du WCAG 2.2.

> En version **1.6.0** :

- [x] deux (2) nouveaux indicateurs apparaissent pour afficher le nombre de projets dont la visibilité est de type **public** ou **privé**.
- [x] la détection des changements sur les référentiels a été ajoutée.

> En version **1.5.0** :

- [x] la gestion des versions a été ajoutée.

## Page d'accueil

Cette page est la page d'ouverture de l'application. Elle permet :

- [ ] de mettre à jour la liste du référentiel des applications SonarQube ;
- [ ] de mettre à jour la liste du référentiel des règles SonarQube ;
- [ ] d'afficher le nombre de projets de type **privé** ;
- [ ] d'afficher le nombre de projets de type **public**
- [ ] d'afficher les projets favoris par projet et/ou par version.

![home](/documentation/ressources/home-001.jpg)

Si la version de l'application et de la base de données sont identiques, tout va bien. Par contre, si une différence est détectée, alors un message est affiché à l'utilisateur connecté.

Il faudra passer le ou les scripts de migration pour aligner la version de l'application au shéma de la base de données.

![home](/documentation/ressources/home-001a.jpg)

La détection des changements des référentiels signale toutes modifications sur le référentiel des projets ou celui des règles SonarQube.

La mise à jour est signalée quand :

- [x] le nombre de projets et/ou de profils est différent de ceux présents sur le serveur SonarQube ;

Le contrôle se fait en fonction de la fréquence choisie, par défaut :

- [x] 1 jour pour les projets ;
- [x] 30 jours pour les profils ;

Cela veut dire que si la table de référence des projets et des profils a été mise à jour dans la journée, il n'y aura pas de signalement en cas de différences avec le serveur SonarQube. En d'autres termes, il faudra lancer la mise à jour de la table locale manuellement, comme on le faisait précédemment.

Si la table des projets et des profils n'est pas à jour, un message s'affiche pour indiquer que la mise à jour est recommandée. Le nombre de projets et/ou de profils en plus ou en moins est indiqué.

![home](/documentation/ressources/home-001b.jpg)

Lorsque l'on clique sur le bouton de mise à jour des projets ou des profils, l'indicateur se met à jour et le message disparaît.

![home](/documentation/ressources/home-001c.jpg)

Pour effectuer la mise à jour, il faut avoir le rôle **[GESTIONNAIRE]**.

![home](/documentation/ressources/home-001e.jpg)

![home](/documentation/ressources/home-001d.jpg)

Notez, en haut à droite, l'affichage de cinq (5) liens en fonction des droits de l'utilisateur (cf. chapitre sécurité) :

![home](/documentation/ressources/home-002.jpg)

- [ ] `Utilisateur` : gestion des utilisateurs ;
- [ ] `Traitements` : gestion des traitements de masse ;
- [x] `Préfernces` : gestion des préférences de l'utilisateur ;
- [x] `Dashboard` : informations sur l'application ;
- [x] `Logout` : pour se déconnecter de l'application ;

Le bouton **mon projet** permet l'accès à la page des projets.

![home](/documentation/ressources/home-003.jpg)

Dans la partie inférieure, la liste des applications favorites est affichée par projet et/ou par version.

- [x] si l'utilisateur a désactivé dans ces préférences la gestion des favoris, alors rien n'est affiché.

![home](/documentation/ressources/home-004.jpg)

- [x] si l'utilisateur a activé dans ces préférences la gestion des favoris par projet.

![home](/documentation/ressources/home-005.jpg)

- [x] si l'utilisateur a activé dans ces préférences la gestion des favoris par version de projet.

![home](/documentation/ressources/home-006.jpg)

Il est possible d'afficher directement le projet en cliquant sur l'icône située à côté du titre de la boîte d'information.

![home](/documentation/ressources/home-007.jpg)

-**-- FIN --**-

[Retour au menu principal](/README.md)
