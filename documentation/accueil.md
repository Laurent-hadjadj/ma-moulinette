# Ma-Moulinette en images

![Ma-Moulinette](/documentation/ressources/home-000.jpg)

## TOC

### Authentification

[Authentification](/documentation/authentification.md)
[Inscription](/documentation/inscription.md)
[Bienvenue]((/documentation/bienvenue.md))
[Gestion des utilisateurs](utilisateur.md)

### Ma-Moulinette

[**Accueil**](/documentation/accueil.md)
[Projet](/documentation/projet.md)
[OWASP](/documentation/owasp.md)
[Suivi](/documentation/suivi.md)
[Répartition détaillée](/documentation/repartition_details.md)

### Page d'accueil

Cette page permet le chargement du référentiel des applications pris en charge par le serveur sonarqube et la consultation et la mise à jour des référentiels de règles.

![home](/documentation/ressources/home-001.jpg)

`Note :` En version **1.5.0**, la gestion des versions a été ajoutée.
Si la version de l'application et de la base de données est identique, tout va bien. Par contre si une différence est détéctée, alors un message est affiché à l'utilisateur connecté.
Il faudra passer le script de migration 1.5.0, qui contient également les modifications pour la version 1.3.0 et 1.4.0.

![home](/documentation/ressources/home-001a.jpg)

`Note :` En version **1.5.0**, la gestion des référentiels locaux, à savoir, les projets et les profil, est changée pour prendre en compte le multi-utilisateur et mieux synchroniser les référentiels avec les données du serveur sonarqube.

Une nouvelle table a été créée pour enregistrer les mises à jour des référentiels et permettre de tenir à jour les référentiels de l'application.

La mise à jour est signalée quand :

- [x] Le nombre de projet et ou de profil est différent de ceux présents sur le serveur sonarqube ;

Le contrôle se fait en fonction de la fréquence choisi, par défaut :

- [x] 1 jour pour les projets ;
- [x] 30 jours pour les profils ;

Cela veux dire que si la table de référence des projets et profils a été mise à jour dans la journée, il n'y aura pas de signalement en cas de différences avec le serveur sonarqube. En d'autres termes, il faudra lancer la mise à jour de la table locale manuellement, comme on le faisant précédement.

Par exemple, si la table des projets et des profils n'est à jour, un message s'affiche ainsi que le nombre de projet et/ou de profil nouveau.

![home](/documentation/ressources/home-001b.jpg)

Lorque l'on clique sur le bouton de mise à jour des projets ou des profils, l'indicateur se met à jour et le message disparait.

![home](/documentation/ressources/home-001c.jpg)

Notez en haut à droite, l'affichage de trois liens, en fonction des droits de l'utilisateur (cf. chapitre sécurité) :

- Gestion des utilisateurs ;
- Dashboard. Indicateurs applicatifs ;
- Logout. Pour se déconnecter de l'application ;

Dans la partie inférieure, la liste des applications favorites est affichée.
![home](/documentation/ressources/home-002.jpg)

-**-- FIN --**-

[Retour au menu principal](/README.md)
