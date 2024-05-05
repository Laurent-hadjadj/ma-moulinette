# Tests Unitaires

![Ma-Moulinette](/documentation/ressources/home-000.jpg)

## Les différents tests unitaires

> Tests unitaires

Ces tests permettent de s’assurer que les tests unitaires basés sur du code source (par exemple une class, une méthode, un confition) se comportent comme prévu.
tests d'une unité de code = tests d'une méthode en utilisants un mock.

> Tests d’intégration

Ces tests vérifient une combinaison de classes et interagissent généralement avec un Conteneur de service de Symfony. Ces tests ne couvrent pas encore l’ensemble de l'application.

tests d'une class : tests d'un repository

> Tests d’application

Les tests d’application testent le comportement d’une application complète. Ils font appelles à des requêtes HTTP (réelles et simulées) pour tester que le La réponse est conforme aux attentes.
tests end-to-end.

Bonne pratique : Test Driver Development, on écrit tout les tests puis le code.
Bonne pratique : tests first > on écrit le test, puis le code au fure et a mesure.

> cypress pour php

## Les tests unitaires

L’écriture de tests unitaires dans une application Symfony n’est pas différente de l’écriture de tests unitaires PHPUnit standard.

Par convention, le répertoire doit répliquer le répertoire de l'application pour les tests unitaires. Il est recommandé de différencier les tests unitaires des tests fonctionnels.

Pour exécuter des tests, il est nécessaire de d'utiliser la commande : `bin/phpunit`

```xml
 <testsuites>
    <testsuite name="Tests unitaires">
      <directory>tests\Unit</directory>
    </testsuite>
  </testsuites>
```

symfony/framework-bundle  instructions:

* Run your application:
    1. Go to the project directory
    2. Create your code repository with the git init command
    3. Download the Symfony CLI at <https://symfony.com/download> to install a development web server

* Read the documentation at <https://symfony.com/doc>

 api-platform/core  instructions:

* Your API is almost ready:
    1. Create your first API resource in src/ApiResource;
    2. Go to /api to browse your API

* Using MakerBundle? Try php bin/console make:entity --api-resource

* To enable the GraphQL support, run composer require webonyx/graphql-php,
    then browse /api/graphql.

* Read the documentation at <https://api-platform.com/docs/>

 doctrine/doctrine-bundle  instructions:

* Modify your DATABASE_URL config in .env

* Configure the driver (postgresql) and
    server_version (16) in config/packages/doctrine.yaml

 phpstan/phpstan  instructions:

* Edit the phpstan.dist.neon file to configure PHPStan.

* For the full options, see
    <https://phpstan.org/user-guide/getting-started>

 symfony/messenger  instructions:

* You're ready to use the Messenger component. You can define your own message buses
    or start using the default one right now by injecting the message_bus service
    or type-hinting Symfony\Component\Messenger\MessageBusInterface in your code.

* To send messages to a transport and handle them asynchronously:

    1. Update the MESSENGER_TRANSPORT_DSN env var in .env if needed
       and framework.messenger.transports.async in config/packages/messenger.yaml;
    2. (if using Doctrine) Generate a Doctrine migration bin/console doctrine:migration:diff
       and execute it bin/console doctrine:migration:migrate
    3. Route your message classes to the async transport in config/packages/messenger.yaml.

* Read the documentation at <https://symfony.com/doc/current/messenger.html>

 symfony/mailer  instructions:

* You're ready to send emails.
* If you want to send emails via a supported email provider, install
    the corresponding bridge.
    For instance, composer require mailgun-mailer for Mailgun.
* If you want to send emails asynchronously:
    1. Install the messenger component by running composer require messenger;
    2. Add 'Symfony\Component\Mailer\Messenger\SendEmailMessage': amqp to the
       config/packages/messenger.yaml file under framework.messenger.routing
       and replace amqp with your transport name of choice.
* Read the documentation at <https://symfony.com/doc/master/mailer.html>

 symfony/phpunit-bridge  instructions:

* Write test cases in the tests/ folder
* Use MakerBundle's make:test command as a shortcut!
* Run the tests with php bin/phpunit

 symfony/webpack-encore-bundle  instructions:

* Install NPM and run npm install
* Compile your assets: npm run dev
* Or start the development server: npm run watch

No security vulnerability advisories found.
Using version ^8.0 for dama/doctrine-test-bundle

## Configuration des phpUnit

Le fichier `.env.test`

```properties
# define your env variables for the test env here
KERNEL_CLASS='App\Kernel'
PANTHER_APP_ENV=panther
PANTHER_ERROR_SCREENSHOT_DIR=./var/error-screenshots

SYMFONY_PHPUNIT_LOCALE="fr_FR"
SYMFONY_DEPRECATIONS_HELPER='max[total]=10&max[self]=10&max[direct]=10&verbose=10'

APP_SECRET='$ecretf0rt3st'

DATABASE_DEFAULT_URL="sqlite:///%kernel.project_dir%/var/data-test.db"
DATABASE_SECONDARY_URL="sqlite:///%kernel.project_dir%/var/temp-test.db"
SQLITE_PATH="/%kernel.project_dir%/var/"

SONAR_URL=http://localhost:9000
SONAR_TOKEN=squ_5b25994b1f2ba7b678eb1914ca68aa1ae35411da
SONAR_USER=""
SONAR_PASSWORD=""

SONAR_PROFILES="Ma-Petite-Entreprise V1.0.0 (2024)"
SONAR_ORGANIZATION="Ma Patite-Entreprise"

NOMBRE_FAVORI=10
TRUST_HOST1="localhost"
TRUST_HOST2="127.0.0.1"
SECRET='0hLa83lleBroue11e!';
MAJ_PROJET=0
MAJ_PROFIL=30
SALT='LK3B-598E4-NV82EX-D7X872F-P74R'
AUDIT='/var/audit'

RGAA='partiellement'
CGU_EDITEUR="Ma Moulinette, l'équipe en charge des développements de l'application Ma-Moulinette."
CGU_ADRESSE="Ma-moulinette, <br> La Mi-Voie, route du grand chemin. <br> BP 770, Brooklyn. N.Y"
CGU_SIRET=false
CGU_SIREN=false
CGU_NUMERO_SIRET="000 000 000 000 15"
CGU_NUMERO_SIREN="000 000 009"
CGU_DIRECTEUR_PUBLICATION="Laurent HADJADJ"
CGU_SOURCE_URL="https://github.com/Laurent-hadjadj/ma-moulinette"
CGU_SOURCE_SCM="github"
CGU_HEBERGEMENT="Ma-moulinette, <br> La Mi-Voie, route du grand chemin. <br> BP 770, Brooklyn. N.Y"
```

0 - symfony composer require --dev phpunit -> et non phpunit/phpunit
1 - symfony composer require --dev orm-fixtures
2 -  création de la base de tests :
bin/console doctrine:database:create --env=test
php bin/console doctrine:schema:update --env=test --force
3 -  symfony composer require --dev liip/test-fixtures-bunde:^3.0.0-alpha3

-**-- FIN --**-

[Retour au menu principal](/README.md)
