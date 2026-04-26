<?php
/**
 * Verifie que les hashes generes par generate-e2e-hashes.php matchent
 * bien le password (= courriel).
 */

$tests = [
    'interne@ma-moulinette.fr'                 => '$2y$13$JlicpFQ1i.8dvDw/km3eUOSFaT/P5YYdhYr3Ino3xqYTJ4CkhRlsa',
    'josh.liberman@ma-moulinette.fr'           => '$2y$13$GEOYGiuyGRAKufl7XddBHuYTrc9xZexVAh.aipq/BXw0XQKlHdl/G',
    'nathan.jones@ma-moulinette.fr'            => '$2y$13$gseCc/kAykkE1YasmtH4EO5AM7oP7MGhl16MTLcDqJClM00tzxp2a',
    'sophie.martin@ma-moulinette.fr'           => '$2y$13$i0shoO8BOWdPvhbfoQUPAewWbFZjclp5oVf8bjwERqy9v5/rRtpR.',
    'aurelie.petit-coeur@ma-moulinette.fr'     => '$2y$13$6qjRl6RDCIxFmaPGnUZ5AO5CRNp36IjCaDZoGLdaJ6bdyq8od5PQa',
];

foreach ($tests as $password => $hash) {
    $ok = password_verify($password, $hash);
    $status = $ok ? '[OK]' : '[KO]';
    echo "{$status} {$password}\n";
    echo "      hash: {$hash}\n";
    if (!$ok) {
        echo "      regenere : " . password_hash($password, PASSWORD_BCRYPT, ['cost' => 13]) . "\n";
    }
    echo "\n";
}
