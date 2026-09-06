<?php

// ==============================================================================
// Ma-Moulinette - Script routeur pour php -S (secours quand symfony serve
// est indisponible, ex. verrou transitoire sur le log symfony-cli sous Windows).
// ==============================================================================
//
// Problèmes : le serveur web intégré de PHP (`php -S`) ne recopie PAS les
// variables d'environnement du shell dans $_SERVER/$_ENV (contrairement au
// SAPI CLI classique) — seul getenv() les voit encore. Or Symfony résout
// APP_ENV via $_SERVER (Symfony\Component\Runtime + Dotenv::bootEnv()), jamais
// via getenv(). Sans ce pont, `APP_ENV=test php -S ...` démarre bien un
// process ou getenv('APP_ENV') vaut 'test', mais Symfony charge quand meme
// .env + .env.local (APP_ENV=dev) car $_SERVER['APP_ENV'] est vide à ce
// moment-la — et .env.local pointe vers la VRAIE base de dev (ma_moulinette),
// pas ma_moulinette_test.
//
// `bin/console --env=test` n'est PAS affecté par ce problème : le flag --env
// force l'environnement indépendamment de $_SERVER, avant même la lecture
// de Dotenv — d'ou la confusion initiale (les diagnostics via bin/console
// donnaient toujours la bonne base, laissant croire à un problème ailleurs).
//
// USAGE :
//   $env:APP_ENV='test'
//   php -S 127.0.0.1:8000 -t public bin/e2e/test-router.php
//
// (utilisé depuis la racine du projet ; __DIR__ remonte donc de bin/e2e/
// jusqu'a la racine pour retrouver public/index.php)
// ==============================================================================

$_SERVER['APP_ENV'] = getenv('APP_ENV') ?: 'dev';
$_ENV['APP_ENV'] = $_SERVER['APP_ENV'];

return require dirname(__DIR__, 2) . '/public/index.php';
