# Tests Unitaires

![Ma-Moulinette](/assets/images/home/home-000.jpg)

## Nombre de tests unitaires

- [x] Entity Case........ **289** tests, **356** vérifications.
- [x] Entity Kernel...... **43** tests, ***90** vérifications.
- [x] Entity Validator... **109** tests, **359** vérifications.
- [x] Entity Repository.. **100** tests, **243** vérifications.
- [x] Entity Performance. **1** tests, **1001** vérifications/

> Total : **542** tests, **2049** vérifications.

## Les différents tests unitaires

> Tests unitaires

Ces tests permettent de s’assurer que les tests unitaires basés sur du code source (par exemple une classe, une méthode, une condition) se comportent comme prévu.

> Tests d’intégration

Ces tests vérifient une combinaison de classes et interagissent généralement avec un Conteneur de service de Symfony. Ces tests ne couvrent pas encore l’ensemble de l'application.

> Tests d’application

Les tests d’application testent le comportement d’une application complète. Ils font appelles à des requêtes HTTP (réelles et simulées) pour tester que le La réponse est conforme aux attentes.

## Bonnes pratiques

Il existes trois (3) façon de mettre en place les tests unitaires :

1. On ne fait rien moi au début des développement de l'application ;
2. On développe tous les tests avant de démarrer les développement (TDD), moi il y a un (1) an ;
3. On développent les tests et le code en même temps (Test First), moi maintenant ;

## Les tests unitaires

L’écriture de tests unitaires dans une application Symfony n’est pas différente de l’écriture de tests unitaires PHPUnit standard.

Par convention, le répertoire doit répliquer le répertoire de l'application pour les tests unitaires. Il est recommandé de différencier les tests unitaires des tests fonctionnels.

Pour exécuter des tests, il est nécessaire de d'utiliser la commande : `./bin/phpunit`

### Les prérequis

Executer les commandes suivantes :

- `symfony composer require --dev symfony/test-pack`
- `symfony composer require --dev orm-fixtures`

### Configuration : PHPUnit.xmldist et PHPUnit.xml

La versions **9.6**  de phpunit doit être utilisée pour symfony 6 et PHP 8.

Le fichier de configuration pour PHPUnit par défaut est `phpunit.xml.dist`. Ce fichier est écrasé à chaque mise à jour de la recipe symfony, il convient donc de créer un fichier `phpunit.xml` qui contiendra la configuration pour l'application.

### Configuration : .env.test et .env.test.local

Le fichier `.env.test` contient les paramètres spécifiques aux tests, i.e. les paramètres utilises du fichier `.env`. Il est propre à symfony et vient surcharger le fichier PHPUnit.xml.

Attention, tout comme le fichier `PHPUnit.xml.dist`, le fichier `.env.test` est écrasé par la recipe symfony lors des mises à jour. Il faudra enregistrer les informations dans un dossier `.env.test.local`.

Exemple de paramètres utilises :

```properties
SYMFONY_PHPUNIT_LOCALE="fr_FR"
SYMFONY_DEPRECATIONS_HELPER='max[total]=10&max[self]=10&max[direct]=10&verbose=10'

#DATABASE_URL="sqlite:///%kernel.project_dir%/var/data-test.db"
DATABASE_URL='postgresql://db_user:db_password@localhost:5432/ma_moulinette?serveurVersion=15&charset=utf8'
```

### Création de la base de données de tests

Si la base de données n'a pas été créé, il suffit d'executer les commandes sql dans un terminal psql :

```sql
--- si on c'est connecté avec l'utilisateur db_user
ALTER ROLE db_user CREATEDB;
-- si on c'est connecté avec l'utilisateur postgres
CREATE DATABASE ma_moulinette_test WITH
    OWNER = db_user
    ENCODING = 'UTF8'
    LC_COLLATE = 'fr_FR.UTF-8'
    LC_CTYPE = 'fr_FR.UTF-8'
    LOCALE_PROVIDER = 'libc'
    TABLESPACE = pg_default
    CONNECTION LIMIT = -1
    IS_TEMPLATE = False;

COMMENT ON DATABASE ma_moulinette_test IS 'Base de données Ma-Moulinette de Tests';

ALTER ROLE db_user IN DATABASE ma_moulinette_test SET search_path TO ma_moulinette_test;
ALTER DATABASE ma_moulinette_test SET search_path TO ma_moulinette_test;
GRANT TEMPORARY, CONNECT ON DATABASE ma_moulinette_test TO PUBLIC;
GRANT ALL ON DATABASE ma_moulinette_test TO db_user;
```

### Préparation de la base de données de test

Les tests unitaires sont exécutés sur une base de données SQLite.

La création de la base de données de tests est relativement facile à mettre en place. Il suffit de taper la commande suivante depuis le dossier du projet :

- `php bin/console --env=test doctrine:database:drop --force`
- `php bin/console --env=test doctrine:database:create --if-not-exists`
- `php bin/console --env=test doctrine:schema:update --force`
- `php bin/console --env=test doctrine:migrations:migrate -n`

```dos
c:\environnement\ma-moulinette>php bin/console --env=test doctrine:database:drop --force
Dropped database "ma_moulinette_test" for connection named default

c:\environnement\ma-moulinette>php bin/console --env=test doctrine:database:create --if-not-exists
Created database "ma_moulinette_test" for connection named default

c:\environnement\ma-moulinette>php bin/console --env=test doctrine:schema:update --force
 Updating database schema...

     433 queries were executed

 [OK] Database schema updated successfully!
```

`note :` Il faut que l'utilisateur ait les droits de création. Vous pouvez ajouter le droit **CREATEDB** à l'utilisateur **db_user**.

### Chargement des fixtures

Pour charger les fixtures dans la base de données de TESTs, il suffit d’exécuter cette commande :

```bash
symfony console doctrine:fixtures:load --env=test
```

### Execution des tests unitaires

Il est possible d'executer tous les tests avec la commande suivante :

- `php bin\console phpunit`

Ou d’exécuter simplement un test en particulier :

- `php ./bin/phpunit ./tests/Unit/Repository/UtilisateurRepositoryTest.php`

Ou  d’exécuter un ensemble de tests :

- `php ./bin/phpunit --filter CaseTest`

### Couverture de code

> prérequis.

Il faudra ajouter le dépendances suivantes au fichier php.ini.

- [x] zend_extension=xdebug;
- [x] extension=pcov
- [x] pcov.enabled=1

Cette commande permet de générer automatiquement le rapport de couverture des tests pour le format clover.

```bash
php vendor/bin/phpunit --coverage-clover=build/logs/clover.xml --coverage-html=build/coverage-report --coverage-text
```

-**-- FIN --**-

[Retour au menu principal](/index.html)
