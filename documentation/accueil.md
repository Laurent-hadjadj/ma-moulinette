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

`Note :` En version **1.5.0**, la gestion des version a été ajoutée.
Si la version de l'application et de la base de données est identique, tout va bien. Par contre si une différence est détéctée, alors un message est affiché à l'utilisateur connecté.
Il faudra passer le script de migration 1.5.0, qui contient également les modifications pour la version 1.3.0 et 1.4.0.

![home](/documentation/ressources/home-001a.jpg)


Notez en haut à droite, l'affichage de trois liens, en fonction des droits de l'utilisateur (cf. chapitre sécurité) :

- Gestion des utilisateurs ;
- Dashboard. Indicateurs applicatifs ;
- Logout. Pour se déconnecter de l'application ;

Dans la partie inférieure, la liste des applications favorites est affichée.
![home](/documentation/ressources/home-002.jpg)

-**-- FIN --**-

[Retour au menu principal](/README.md)
