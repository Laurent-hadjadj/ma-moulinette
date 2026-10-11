<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

/*
 * Choix du storage de session pour l'environnement test — en PHP (pas en
 * YAML) car storage_factory_id est un id de SERVICE, résolu à la compilation
 * du container : %env(...)% est résolu trop tard et fait échouer la
 * validation des références de services ("has a dependency on a non-existent
 * service "%env(...)%""). getenv()/$_ENV ici s'exécutent à la compilation,
 * donc la bonne valeur est connue immédiatement.
 *
 * Défaut (mock_file) : nécessaire aux tests PHPUnit WebTestCase (plusieurs
 * kernels dans le même process). Surchargé en session.storage.factory.native
 * uniquement par docker/e2e/docker-compose.e2e.yml : mock_file n'a aucun
 * verrouillage de fichier entre read()/write(), ce qui perd le token CSRF de
 * façon intermittente dès que deux requêtes HTTP réelles concurrentes
 * touchent la même session (plusieurs vrais workers php-fpm) — jamais un
 * souci pour WebTestCase, qui reste mono-process.
 */
return static function (ContainerConfigurator $container): void {
    $storageFactoryId = $_ENV['APP_TEST_SESSION_STORAGE']
        ?? getenv('APP_TEST_SESSION_STORAGE')
        ?: 'session.storage.factory.mock_file';

    $container->extension('framework', [
        'session' => [
            'enabled' => true,
            'storage_factory_id' => $storageFactoryId,
        ],
    ]);
};
