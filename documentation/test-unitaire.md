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

## Configuration : PHPUnit.xmldist et PHPUnit.xml

La verions **9.6**  de phpunit doit être utilisée pour symfony 6 et PHP 8.

Le fichier de configuration pour PHPUnit par défaut est `phpunit.xml.dist`. Ce fichier est écrasé à chaque mise à jour de la recipe symfony, il convient donc de créer un fichier `phpunit.xml` qui contiendra la configuration pour l'application.

## Configuration : .env.test et .env.test.local

Le fichier `.env.test` contient les paramètres spécifiques aux tests, i.e. les paramètres utilises du fichier `.env`. Il est propre à symfony et vient surcharger le fichier PHPUnit.xml.

Attention, tout comme le fichier `PHPUnit.xml.dist`, le fichier `.env.test` est écrasé par la recipe symfony lors des mises à jour. Il faudra enregistrer les informations dans un dossier `.env.test.local`.

Exemple de paramètres utilises :

```properties
SYMFONY_PHPUNIT_LOCALE="fr_FR"
SYMFONY_DEPRECATIONS_HELPER='max[total]=10&max[self]=10&max[direct]=10&verbose=10'

DATABASE_URL="sqlite:///%kernel.project_dir%/var/data-test.db"
```

0 - symfony composer require --dev symfony/test-pack
1 - symfony composer require --dev orm-fixtures
2 -  création de la base de tests :
    php bin/console --env=test doctrine:database:create
    php bin/console --env=test doctrine:schema:update --force
	symfony console --env=test doctrine:database:drop --force || true
	symfony console --env=test doctrine:database:create
  symfony console --env=test doctrine:schema:update --force
	symfony console doctrine:migrations:migrate -n --env=test
	symfony console doctrine:fixtures:load -n --env=test
	symfony php bin/phpunit $(MAKECMDGOALS)

4 - php ./bin/phpunit ./tests/Unit/Repository/Main/UtilisateurRepositoryTest.php
5 - php ./bin/phpunit --filter UtilisateurkernelTest

-**-- FIN --**-

[Retour au menu principal](/README.md)
