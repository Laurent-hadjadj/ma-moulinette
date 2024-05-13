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

## Configuration

La verions 9.6  de phpunit doit être utilisée pour symfony 6 et PHP 8. Le fichier de configuration.

```xml
 <testsuites>
    <testsuite name="Tests unitaires">
      <directory>tests\</directory>
    </testsuite>
  </testsuites>
```

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
