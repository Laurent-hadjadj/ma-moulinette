<?php
/**
 * Genere les hashes bcrypt (cost=13) pour les utilisateurs E2E.
 * Password = courriel (convention E2E validee 2026-04-24).
 *
 * Usage : php bin/e2e/generate-e2e-hashes.php
 *
 * La sortie est a copier dans migrations/PosgreSQL/90_fixtures/fixtures-e2e.sql
 */

// 5 users E2E (Phase K.2.b) :
//   1 ROLE_INTERNAL actif (bootstrap admin E2E)
//   4 ROLE_NONE disabled (activés + assignés en cours de scenario)
$users = [
    'interne@ma-moulinette.fr',                 // ROLE_INTERNAL (actif)
    'josh.liberman@ma-moulinette.fr',           // futur ROLE_UTILISATEUR
    'nathan.jones@ma-moulinette.fr',            // futur ROLE_COLLECTE
    'sophie.martin@ma-moulinette.fr',           // futur ROLE_COLLECTE + ROLE_SUIVI
    'aurelie.petit-coeur@ma-moulinette.fr',     // futur ROLE_GESTIONNAIRE
];

echo "-- Hashes bcrypt cost=13 generes le " . date('Y-m-d H:i:s') . "\n\n";

foreach ($users as $email) {
    $hash = password_hash($email, PASSWORD_BCRYPT, ['cost' => 13]);
    echo "-- {$email}\n";
    echo "-- {$hash}\n\n";
}
