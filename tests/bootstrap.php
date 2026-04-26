<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

// Chargement des variables d'environnement pour l'environnement de test.
// Ordre de priorité Symfony : .env.test.local > .env.test > .env.local > .env
(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

if ($_SERVER['APP_DEBUG'] ?? false) {
    umask(0000);
}

// Définit la timezone par défaut (Europe/Paris) pour tous les tests
// — cohérent avec DATETIME_IMMUTABLE utilisé dans les entités v2.0.0.
date_default_timezone_set('Europe/Paris');
