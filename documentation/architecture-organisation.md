# Infrastructure et organisation

![Ma-Moulinette](/documentation/ressources/home-000.jpg)

## Les dossiers

> Pour la version locale.

Il existe deux environnements :

- [ ] L'environnement de production ;
- [ ] L'environnement de développement ;

La version de **production** contient l'application symfony, deux (2) bases vides, la version compilée des sources **JavaScript** et CSS (dans le dossier /public/build).

Le repository git n'est pas présent. Enfin, Le dossier **node_modules** n'étant pas nécessaire, il a été supprimé (car il est utilisé uniquement en développement).

Le dossier racine du projet est `ma-moulinette`. La version de production et la version de développement sont dans deux (2) dossiers différents.

À noter que ces dossiers peuvent être changés depuis la version **1.5.0**. En effet, les chemins absolus ont été remplacés par des chemins relatifs basés sur les variables symfony.

- [ PROD ] : **c:\sonar-dash.prod**
- [ DEV ] : **c:\sonar-dash.dev**

Ce point et important, pour le paramétrage des accès et le lancement de symfony-cli en développement par exemple.

En développement, les outils suivants sont utilisés pour faciliter les tâches de gestion, comme la mise à jour des paquets NPM ou PHP, et de démarrage du serveur d'application.

La liste ci-dessous indique les programmes utilisés depuis le dossier racine, i.e. `c:\sonar-dash.dev` par exemple.

- [X] `vscode-1.77.1.bat` : lance vsCode (portable) ;
- [X] `symfony_start.bat` : lance le serveur **symfony-cli**
- [X] `symfony_stop.bat` : arrête le serveur **symfony-cli**
- [X] `encore.bat` : lance **Webpack** en mode **watch**.
- [X] `MigrateSql.bat` : lance la mise à jour des entities **Main** et **Secondary**, la création des versions et la mise à jour des BD pour la version **sqlite** ;
- [X] `Console-cli.bat` : ouvre un terminal pour lancer les commandes symfony, composer et npm dans le dossier du projet ;
- [X] `Deploy.bat` : compile le code PHP/Twig, CSS et JavaScript pour l'environnement de production ;
- [ ] `stan.bat` : lance les outils d'Audit CSS, HTML et PHP et l'analyse du projet dans SonarQube ;
- [ ] `catchTool.bat` : lance des commandes d'optimisation du cache pour opCache ;
- [ ] `sqlite.exe` : permets de lancer les commandes de vaccum et d'optimisation de la base **data.db** et **temp.db** ;

## Les environnements

Chaque environnement dispose de son fichier de `.env`.

Le fichier **.env-prod** est un template de configuration.

Il peut être utilisé pour l'environnement de **dev** ou de **prod**. Il suffit pour cela de le renommer en **.env** et de le paramétrer en fonction de vos besoins ;

### Définition de l'environnement

Pour l'environnement de production, il suffit de passer les propriétés **APP_ENV** et **APP_DEBUG** à `prod` et `0`.

- [x] APP_ENV = **prod**
- [x] APP_DEBUG = **0**

Pour l'environnement de développement, il suffit de passer les propriétés **APP_ENV** et **APP_DEBUG** à `dev` et `1`.

- [x] APP_ENV = **dev**
- [x] APP_DEBUG = **1**

Enfin, il est obligatoire de changer la clé **APP_SECRET**, utilisée pour le chiffrement du token CSRF par exemple.

- [x] **APP_SECRET**=14vy9s67nm7oyis6fv347s923u

`Note :` La clé est symétrique et de taille d'environ 26 bits.

### Authentification au serveur SonarQube

Pour la connexion à SonarQube, il est possible d'utiliser un **token** ou ses identifiants de connexion comme son **login** et son **password**.

La configuration nécessaire est la suivante :

- [x] **SONAR_URL** = <https://monsonar.ma-petite-entreprise.fr>
- [x] **SONAR_TOKEN** = bzm8i46k4e56878lm7ilxcw8t7095df5k6i8hb01
- [ ] **SONAR_USER** = mon_login
- [ ] **SONAR_PASSWORD** = t5lf911a83lt

Le token est une clé symétrique de 40 bits générée depuis le serveur SonarQube.

`Note :` Il faudra utiliser l'une ou l'autre des méthodes d'authentification.

### Propriétés spécifiques au projet

Ici, nous allons spécifier :

- Le nom du profil commun à tous les référentiels qualités (i.e. les règles par langage),
- le nom de mon organisation,
- le nombre de favoris que je souhaite afficher dans le suivi de la page d’accueil,
- les hosts que je souhaite autoriser,
- la clé de chiffrement utilisée pour le cookie 'remmber-me',
- la fréquence de mise à jour de la liste des projets,
- la fréquence de mise à jour de la liste des profils de règles par langage,
- une clé (SALT) permettant de renforcer la sécurité lors du déclenchement d'un traitement depuis un client externes (i.e. sans passer par l'application),
- le chemin relatif vers le dossier audit depuis le dossier racine de l'application.

- [x] **SONAR_PROFILES** = "mon profil v1.0.0"
- [x] **SONAR_ORGANIZATION** = "ma petite Entreprise"
- [x] **NOMBRE_FAVORI** = 10
- [x] **TRUST_HOST1** = "^ma-petite-entreprise\.fr$"
- [x] **TRUST_HOST2** = "10.0.0.1"
- [x] **SECRET** = '>Yw5<3pR]$lFeVg147'
- [x] **MAJ_PROJET** = 0
- [x] **MAJ_PROFIL** = 30
- [x] **SALT** = "YE4T-9AU62-36HG7A-58ABS39-76JC"
- [x] **AUDIT** = "/var/audit"

`Note :` Pour les **TRUST_HOST**, il est possible d'utiliser une URL, un domaine ou une adresse IP.

`Note :` Pour **MAJ_PROJET**, la valeur **0** indique la date d'aujourd'hui. **1** indiquera la date d'hier, etc...
De la même façon, pour le variable **MAJ_PROFIL**, la valeur **30** correspond à 30 jours à partir d'aujourd'hui.

### Environnement de production

Le fichier des propriétés de développement s'obtient en utilisant la commande : `composer dump-env prod`

Le fichier obtenu se nomme `.env.local.php`.

```php
<?php

// This file was generated by running "composer dump-env prod"

return array (
  'APP_ENV' => 'prod',
  'APP_DEBUG' => '1',
  'APP_SECRET' => '14vy9s67nm7oyis6fv347s923u',
  'MESSENGER_TRANSPORT_DSN' => 'doctrine://default?auto_setup=0',
  'DATABASE_URL' => "postgresql://db_user:db_password@localhost:5432/ma_moulinette?serverVersion=15&charset=utf8",
  'SONAR_URL' => 'https://monsonar.ma-petite-entreprise.fr',
  'SONAR_TOKEN' => 'bzm8i46k4e56878lm7ilxcw8t7095df5k6i8hb01',
  'SONAR_USER' => '',
  'SONAR_PASSWORD' => '',
  'SONAR_PROFILES' => 'mon profil v1.0.0',
  'SONAR_ORGANIZATION' => 'ma petite Entreprise',
  'NOMBRE_FAVORI' => '10',
  'TRUST_HOST1' => '^ma-petite-entreprise\.fr$',
  'TRUST_HOST2' => '10.0.0.1',
  'SECRET' => '>Yw5<3pR]$lFeVg147',
  'MAJ_PROJET' => '0',
  'MAJ_PROFIL' => '30',
  'SALT' => 'YE4T-9AU62-36HG7A-58ABS39-76JC',
  'AUDIT' => '/var/audit',
  'RGAA' => 'partiellement',
  'CGU_EDITEUR' => 'Ma Moulinette, l\'équipe en charge des développements de l\'application Ma-Moulinette.',
  'CGU_ADRESSE' => 'Ma-moulinette, <br> La Mi-Voie, route du grand chemin. <br> BP 770, Brooklyn. N.Y',
  'CGU_SIRET' => 'false',
  'CGU_SIREN' => 'false',
  'CGU_NUMERO_SIRET' => '000 000 000 000 15',
  'CGU_NUMERO_SIREN' => '000 000 009',
  'CGU_DIRECTEUR_PUBLICATION' => 'Laurent HADJADJ',
  'CGU_SOURCE_URL' => 'https://github.com/Laurent-hadjadj/ma-moulinette',
  'CGU_SOURCE_SCM' => 'github',
  'CGU_HEBERGEMENT' => 'Ma-moulinette, <br> La Mi-Voie, route du grand chemin. <br> BP 770, Brooklyn. N.Y',
  'LOCK_DSN' => 'semaphore',
  'CORS_ALLOW_ORIGIN' => '^https?://(localhost|127\\.0\\.0\\.1)(:[0-9]+)?$',
);
```

## Installation des dépendances

Pour l'environnement de développement, il est nécessaire d'installer les dépendances **PHP** et **NPM**. Pour cela, il suffit de passer les commandes suivantes à la racine du projet **ma-moulinette**.

Pour **composer**, il est possible de lancer la commande directement ou depuis **symfony-cli**.

### Mise à jour de composer

- [ ] `php composer.phar self-update` où
- [ ] `php composer self-update` où
- [x] `symfony composer self-update`

Si tout va bien, vous aurez le message suivant :

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

Si les npm sont déjà présents dans le dossier, vous aurez un message qui ressemble à celui-là :

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
