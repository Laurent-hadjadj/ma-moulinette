# Tests Unitaires

![Ma-Moulinette](/documentation/ressources/home-000.jpg)

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

La verions **9.6**  de phpunit doit être utilisée pour symfony 6 et PHP 8.

Le fichier de configuration pour PHPUnit par défaut est `phpunit.xml.dist`. Ce fichier est écrasé à chaque mise à jour de la recipe symfony, il convient donc de créer un fichier `phpunit.xml` qui contiendra la configuration pour l'application.

### Configuration : .env.test et .env.test.local

Le fichier `.env.test` contient les paramètres spécifiques aux tests, i.e. les paramètres utilises du fichier `.env`. Il est propre à symfony et vient surcharger le fichier PHPUnit.xml.

Attxntion, tout comme le fichier `PHPUnit.xml.dist`, le fichier `.env.test` est écrasé par la recipe symfony lors des mises à jour. Il faudra enregistrer les informations dans un dossier `.env.test.local`.

Exemple de paramètres utilises :

```properties
SYMFONY_PHPUNIT_LOCALE="fr_FR"
SYMFONY_DEPRECATIONS_HELPER='max[total]=10&max[self]=10&max[direct]=10&verbose=10'

DATABASE_URL="sqlite:///%kernel.project_dir%/var/data-test.db"
```

### Préparation de la base de données de test

Les tests unitaires sont éxuctés sur une base de données SQLIte.

La création de la base de données de tests est relativement facile à mettre en place. Il suffit de taper la commande suivante depuis le dossier du projet :

- `php bin/console --env=test doctrine:database:drop --force || true`
- `php bin/console --env=test doctrine:database:create`
- `php bin/console --env=test doctrine:schema:update --force`
- `php bin/console --env=test doctrine:migrations:migrate -n`

### Execution des tests unitaires

Il est possible d'executer tous les tests avec la commande suivante :

- `php bin\console phpunit`

Ou d'éxceuter simplement un test en particulier :

- `php ./bin/phpunit ./tests/Unit/Repository/UtilisateurRepositoryTest.php`

Ou  d'éxecuer un ensemble de tests :

- `php ./bin/phpunit --filter CaseTest`

-**-- FIN --**-

[Retour au menu principal](/README.md)
