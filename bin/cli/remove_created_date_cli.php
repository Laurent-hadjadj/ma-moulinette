<?php

/*
*   Ma-Moulinette
*   --------------
*   Copyright (c) 2021-2026.
*   Laurent HADJADJ <laurent_h@me.com>.
*   Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
*   ---
*   Vous pouvez obtenir une copie de la licence à l'adresse suivante :
*   https://www.gnu.org/licenses/agpl-3.0.html
*/

/**
 * Script pour supprimer la ligne "* Created at:" dans les fichiers PHP et JS.
 *
 * @version 1.0.0
 * @package Ma-Moulinette
 * @author  Laurent HADJADJ <laurent_h@me.com>
 * @license AGPL-3.0-or-later
 * @link    https://www.gnu.org/licenses/agpl-3.0.html
 */

declare(strict_types=1);

// Constantes
define('VERSION', '1.0.0');
define('APP_NAME', 'Ma-Moulinette - Remove Created Date');
define('DEFAULT_ROOT_DIR', __DIR__);

// Configuration par défaut

$config = [
    'root_dir' => DEFAULT_ROOT_DIR,
    'dry_run' => false,
    'pattern' => '/\s*\* Created at:.*\n/',
    'author_tag' => '@author',
    'file_extension' => 'php'
];

$config = [
    'root_dir' => DEFAULT_ROOT_DIR,
    'dry_run' => false,
    'pattern' => '/^\s*\* Created at:.*\n/m', // Modifié pour matcher le début de ligne
    'author_tag' => '@author',
    'file_extensions' => ['php', 'js'], // Ajout du support JS
    'min_block_lines' => 2 // Nombre minimum de lignes dans le bloc de commentaire
];

// Gestion des arguments
$options = getopt('', ['dry-run', 'help', 'version', 'root-dir:']);

if (isset($options['help'])) {
    displayHelp();
    exit(0);
}

if (isset($options['version'])) {
    echo APP_NAME . ' v' . VERSION . "\n";
    exit(0);
}

if (isset($options['root-dir'])) {
    $rootDirOption = $options['root-dir'];
    if (is_array($rootDirOption)) {
        $rootDirOption = (string) end($rootDirOption);
    }
    $config['root_dir'] = rtrim((string) $rootDirOption, DIRECTORY_SEPARATOR);
}

$config['dry_run'] = isset($options['dry-run']);

// Vérification du répertoire racine
if (!is_dir($config['root_dir']) || !is_readable($config['root_dir'])) {
    echo "[ERREUR] Le répertoire racine n'existe pas ou n'est pas accessible: {$config['root_dir']}\n";
    exit(1);
}

// Statistiques
$stats = [
    'files_scanned' => 0,
    'files_modified' => 0,
    'files_ignored' => 0,
    'occurrences_found' => 0,
    'errors' => 0,
    'start_time' => microtime(true)
];

// Exécution du script
displayHeader($config);
processDirectory($config['root_dir'], $config, $stats);
displayStats($stats);


/**
 * [Description for displayHeader]
 * Affiche l'en-tête du script
 *
 * @param array{root_dir: string, dry_run: bool, pattern: string, author_tag: string, file_extensions: list<string>, min_block_lines: int} $config Configuration du script
 *
 * @return void
 *
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright   Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
 */
function displayHeader(array $config): void
{
    echo "=== " . APP_NAME . " v" . VERSION . " ===\n";
    echo $config['dry_run'] ? "[MODE SIMULATION (--dry-run)]\n" : "[MODE REEL (modifications appliquées)]\n";
    echo "Dossier racine : {$config['root_dir']}\n\n";
}

/**
 * [Description for displayStats]
 * Affiche les statistiques finales
 *
 * @param array{files_scanned: int, files_modified: int, files_ignored: int, occurrences_found: int, errors: int, start_time: float} $stats Statistiques du script
 *
 * @return void
 *
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright   Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
 */
function displayStats(array $stats): void
{
    $executionTime = round(microtime(true) - $stats['start_time'], 2);

    echo "\n=== Statistiques ===\n";
    echo "- Fichiers scannés : {$stats['files_scanned']}\n";
    echo "- Occurrences trouvées : {$stats['occurrences_found']}\n";
    echo "- Fichiers modifiés : {$stats['files_modified']}\n";
    echo "- Fichiers ignorés : {$stats['files_ignored']}\n";
    echo "- Erreurs : {$stats['errors']}\n";
    echo "- Temps d'exécution : {$executionTime} secondes\n";
    echo "====================\n";
}

/**
 * [Description for displayHelp]
 * Affiche l'aide du script
 *
 * @return void
 *
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright   Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
 */
function displayHelp(): void
{
    echo <<<HELP
Ma-Moulinette - Remove Created Date v{VERSION}

Usage:
        php remove_created_at.php [options]

Options:
        --dry-run                   Simulation : aucune modification réelle
        --root-dir=PATH Spécifie    le répertoire racine à scanner (par défaut: répertoire courant)
        --displayHelp               Affiche cette aide
        --version_compare           Affiche la version du script

HELP;
}


/**
 * [Description for processFile]
 * Traite un fichier PHP
 *
 * @param string $filePath Chemin du fichier
 * @param array{root_dir: string, dry_run: bool, pattern: string, author_tag: string, file_extensions: list<string>, min_block_lines: int} $config Configuration du script
 * @param array{files_scanned: int, files_modified: int, files_ignored: int, occurrences_found: int, errors: int, start_time: float} $stats Statistiques (passé par référence)
 *
 * @return bool True si le fichier a été traité avec succès, false sinon
 *
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright   Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
 */
function processFile(string $filePath, array $config, array &$stats): bool
{
    $stats['files_scanned']++;

    if (!is_readable($filePath)) {
        $stats['errors']++;
        echo "[ERREUR] Fichier illisible : $filePath\n";
        return false;
    }

    $content = file_get_contents($filePath);
    if ($content === false) {
        $stats['errors']++;
        echo "[ERREUR] Impossible de lire : $filePath\n";
        return false;
    }

    // Vérifier si le fichier contient le pattern
    if (!preg_match($config['pattern'], $content)) {
        return false;
    }

    // Extraire le bloc de commentaire complet
    $blockPattern = '/\/\*\*.*?\*\//s';
    if (!preg_match($blockPattern, $content, $matches)) {
        $stats['files_ignored']++;
        echo "[IGNORE] Aucun bloc de commentaire trouvé : $filePath\n";
        return false;
    }

    $commentBlock = $matches[0];
    $originalBlock = $commentBlock;

    // Vérifier si le bloc contient @author
    if (!str_contains($commentBlock, $config['author_tag'])) {
        $stats['files_ignored']++;
        echo "[IGNORE] Bloc non standard (pas de '{$config['author_tag']}') : $filePath\n";
        return false;
    }

    // Compter les lignes du bloc
    $blockLines = substr_count($commentBlock, "\n") + 1;
    if ($blockLines < $config['min_block_lines']) {
        $stats['files_ignored']++;
        echo "[IGNORE] Bloc trop court : $filePath\n";
        return false;
    }

    // Supprimer la ligne Created at en préservant la mise en forme
    $newBlock = preg_replace($config['pattern'], '', $commentBlock);

    // Corriger les doubles étoiles qui pourraient apparaître
    $newBlock = preg_replace('/\*\s+\*/', '*', $newBlock);

    // Remplacer uniquement si le bloc a changé
    if ($newBlock === $originalBlock) {
        return false;
    }

    $stats['occurrences_found']++;
    $newContent = str_replace($originalBlock, $newBlock, $content);

    if ($config['dry_run']) {
        echo "[DRY-RUN] Fichier à modifier : $filePath\n";
        return true;
    }

    if (file_put_contents($filePath, $newContent) === false) {
        $stats['errors']++;
        echo "[ERREUR] Impossible d'écrire dans : $filePath\n";
        return false;
    }

    $stats['files_modified']++;
    echo "[MODIFIE] $filePath\n";
    return true;
}

/**
 * [Description for processDirectory]
 * Parcourt récursivement les répertoires
 *
 * @param string $dir Répertoire à scanner
 * @param array{root_dir: string, dry_run: bool, pattern: string, author_tag: string, file_extensions: list<string>, min_block_lines: int} $config Configuration du script
 * @param array{files_scanned: int, files_modified: int, files_ignored: int, occurrences_found: int, errors: int, start_time: float} $stats Statistiques (passé par référence)
 *
 * @return void
 *
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright   Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
 */
function processDirectory(string $dir, array $config, array &$stats): void
{
    $items = scandir($dir);
    if ($items === false) {
        $stats['errors']++;
        echo "[ERREUR] Impossible de scanner : $dir\n";
        return;
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $path = $dir . DIRECTORY_SEPARATOR . $item;

        if (is_dir($path)) {
            processDirectory($path, $config, $stats);
        } elseif (is_file($path)) {
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if (in_array($extension, $config['file_extensions'])) {
                processFile($path, $config, $stats);
            }
        }
    }
}
