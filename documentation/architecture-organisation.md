# Infrastructure et organisation

![Ma-Moulinette](/documentation/ressources/home-000.jpg)

## Les dossiers

Il existe deux environnements :

- [ ] L'environement de production ;
- [ ] L'environnement de développement ;

La version de **production** contient l'application symfony, deux bases vides, la version compilée des sources **javascript** et CSS (dans le dossier /public/build).

Le repository git n'est pas présent. Enfin, Le dossier **node_modules** n'étant pas nécessaire, il a été supprimé (car il est utilisé uniquement en développement).

Le dossier racine du projet est `ma-moulinette`. La version de production et la version de développement sont dans deux dossiers différents.

A noter que ces dossiers peuvent être changés depuis la version 1.5.0. En effet les chemins absolus ont été remplacés par des chemins relatifs basée sur les varaibles symfony.

- [ PROD ] : **c:\sonar-dash.prod**
- [ DEV ] : **c:\sonar-dash.dev**

Ce point et important, pour le paramétrage des accès et le lancemment de symfony-cli en développement par exemple.

En développement les outils suivants sont utilisés pour faciliter les tâches de gestion, comme la mise à jour des paquets NPM ou PHP, et de démarrage du serveur d'application.

La liste ci-dessous indique les programmes utilisées depuis le dossier racine, i.e. `c:\sonar-dash.dev` par exemple.

- [X] vscode-1.69.2.bat : Lance vsCode (portable) ;
- [X] symfony_start.bat : Démarrage du serveur **symfony-cli**
- [X] symfony_stop.bat : Arrêt du serveur **symfony-cli**
- [X] encore.bat : démmarage de **Webpack** en mode **watch**.
- [X] MigrateSql.bat : Lance la mise à jour des entity Main et Secondary, la création des versions et la mise à jours des BD ;
- [X] Console-cli.bat : Ouvre un terminal pour lancer les commandes symfony, composer et npm dans le dossier du projet ;
- [X] Deploy.bat : compile le code PHP/Twig, CSS et Javascript pour l'environnement de production ;
- [ ] stan.bat : Lance les outils d'Audit CSS, HTML et PHP et l'analyse du projet dans SonarQube ;
- [ ] catchTool.bat : Lance des commandes d'optimisationdu cache pour opCache ;
- [ ] sqlite.exe : Permet de lancer les commandes de vaccum et d'optimisationde la base data.db et temp.db ;

## Les environnements

Chaque environnement dispose de son fichier de `.env`.

Le fichier **.env-prod** est un template de configuration.

Il peut être utilisé pour l'environnement de dev ou de prod. Il suffit pour cela de le renommer en **.env** et de le paramétrer en fonction de vos besoins ;

### Définition de l'enviromment

Pour l'environement de production, il suffit de passer les propriétés **APP_ENV** et **APP_DEBUG** à `prod` et `0`.

- [x] APP_ENV = **prod**
- [x] APP_DEBUG = **0**

Pour l'environement de développement, il suffit de passer les propriétés **APP_ENV** et **APP_DEBUG** à `dev` et `1`.

- [x] APP_ENV = **dev**
- [x] APP_DEBUG = **1**

Enfin, il est oblogatoire de changer la clé **APP_SECRET**, utilisée pour le chiffrement du token CSRF par exemple.

- [x] **APP_SECRET**=14vy9s67nm7oyis6fv347s923u

`Note :` La clé est symétrique et de taille d'environ 26 bits.

### Authentification au serveur Sonarqube

Pour la connexion à Sonarqube, il est possible d'utiliser un **token** ou ses identifiants de connexion comme par exemple son **login** et son **password**.

La configuration nécessaire est a suivante :

- [x] SONAR_URL = <https://monsonar.ma-petite-entreprise.fr>
- [x] SONAR_TOKEN = bzm8i46k4e56878lm7ilxcw8t7095df5k6i8hb01
- [ ] SONAR_USER = mon_login
- [ ] SONAR_PASSWORD = t5lf911a83lt

Le taken est une clé symétrique de 40 bits généré depuis le serveur Sonarqube.

`Note :` Il faudra utiliser l'une ou l'autre des méthodes d'authentification.

### Propriétés spécifiques au projet

Ici, nous allons spécifier le nom du profil commun à tous les référentiels qualités (i.e. les règles par langage), le nom de mon organisation, le nombre de favori que je souhate afficher dans le suivi de la page d'acueil, les hosts que je souhaite autoriser et la clé de chiffrement utilisée pour le cokie 'remmber-me'.

- [x] SONAR_PROFILES = "mon profil v1.0.0"
- [x] SONAR_ORGANIZATION = "ma petite Entreprise"
- [x] NOMBRE_FAVORI = 10
- [x] TRUST_HOST1="^ma-petite-entreprise\.fr$"
- [x] TRUST_HOST2="10.0.0.1"
- [X] SECRET='>Yw5<3pR]$lFeVg147';

`Note :` Pour les **TRUST_HOST**, on peut utiliser une URL, un domaine ou une adresse IP.

## Installation des dépendances

Pour l'environnement de développement, il est nécessaire d'installer les dépendances PHP et NPM. Pour cela il suffit les commandes suivantes à la racine du projet **ma-moulinette**.

Pour composer, il est possible de lancer la commande directement ou depuis symfony-cli.

### Mise à jour de composer

- [ ] `php composer.phar self-update` où
- [ ] `php composer self-update` où
- [x] `symfony composer self-update`

Si tout va bien vous aurez le message suivant :

```plaintext
You are already using the latest available Composer version 2.4.1 (stable channel).
```

### Installation des bundles symfony

- [ ] `php composer.phar install` où
- [ ] `php composer install` où
- [X] `symfony composer install`

Si les bundles symfony sont déjà installés, vous aurez très certainement ce message.

```plaintext
Installing dependencies from lock file (including require-dev)
Verifying lock file contents can be installed on current platform.
Nothing to install, update or remove
Generating optimized autoload files
89 packages you are using are looking for funding.
Use the `composer fund` command to find out more!

Run composer recipes at any time to see the status of your Symfony recipes.

Executing script cache:clear [OK]
Executing script assets:install public [OK]
```

### Installation des npm pour le projet

- [x] `npm install`

Si les npm sont déjà présent dans le dossier, vous aurez un message qui ressemble à celui-la :

```plaintext
audited 921 packages in 6.575s

104 packages are looking for funding
  run `npm fund` for details

found 0 vulnerabilities
```

Il n'est pas nécessaire de compiler les ressources JS et CSS. Il faut cependant ne pas oublier de lancer `encore` avec la commande : `npm run watch`

Seul le dossier **vendor** est utilisé, les dépendances npm ne sont pas nécessaires sur l'environnement de production.

-**-- FIN --**-

[Retour au menu principal](/README.md)
