# Démarrage de l'environnement de développement

![Ma-Moulinette](/documentation/ressources/home-000.jpg)

- [x] Modifiez les paramètres **APP_ENV** et **APP_DEBUG**  du fichier `.env` :

```yaml
APP_ENV = dev
APP_DEBUG = 1
```

Par défaut, les programmes de démarrage et d'arrêt sont dans le dossier bin/ du projet.

- [x] Lancez le programme **symfony_start.bat** pour démarrer le serveur symfony ;
- [x] Lancez le programme **encore.bat** pour démarrer la compilation à la volée des ressources JS/CSS (**--watch**) ;

`Note :` pour arrêter le serveur symfony-cli, lancez la commande **symfony_stop.bat** ;

## Déploiement en production

- [x] Modifiez les paramètres **APP_ENV** et **APP_DEBUG** :

```yaml
APP_ENV = prod
APP_DEBUG = 0
```

- [x] Changez la clé **APP_SECRET** et **SECRET** ;

- [x] Supprimez les fichiers du dossier **public/build** ;
- [x] Supprimer le dossier **dev** et **prod** du dossier **var/cache** ;
- [x] Supprimer le fichier **dev.log** du dossier **var/log** ;
- [x] Lancez la commande pour compiler le fichier **.env** :  `composer dump-env prod`
- [x] Lancez la commande pour compiler le code PHP :

```plaintext
symfony composer dump-autoload --no-dev --classmap-authoritative
```

- [x] Lancez la commande pour compiler les fichiers css/js :  `npm run-script build`

```plaintext
npm run-script build

> ma-moulinette@1.2.4 build c:\sonar-dash.dev\ma-moulinette
> encore production --progress

Running webpack ...

99% done plugins FriendlyErrorsWebpackPlugin DONE  Compiled successfully in 18856ms                                           20:45:28

131 files written to public\build
webpack compiled successfully
```

-**-- FIN --**-

[Retour au menu principal](/README.md)
