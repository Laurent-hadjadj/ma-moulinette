<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Service\LogArchive;

use ZipArchive;
use Psr\Log\LoggerInterface;
use App\Exception\{EmptyLogSelectionException, LogDirectoryNotFoundException, ZipCreationException};

/**
 * [Description LogArchiveService]
 */
class LogArchiveService
{
    private string $logDir;

    public function __construct(
        string $logDir,
        private LoggerInterface $logger)
    {
        $this->logDir = realpath($logDir);
        if (!$this->logDir) {
            throw new LogDirectoryNotFoundException($logDir);
        }
    }

    /**
     * [Description for listLogs]
     * Liste les logs selon l'environnement et le type
     *
     * @param string|null $env
     * @param array<int, string>|null $types
     *
     * @return list<array{
     *     name: string,
     *     path: string,
     *     type: string,
     *     env: string|null,
     *     size: int|false,
     *     mtime: int|false
     * }>
     *
     * Created at: 14/07/2026 16:27:04 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function listLogs(?string $env = null, ?array $types = null): array
    {
        $logs = [];
        $this->logger->debug('Scan dossier de logs', [
            'dir' => $this->logDir,
            'env' => $env,
            'types' => $types,
        ]);

        foreach (scandir($this->logDir) as $file) {
            if (!str_ends_with($file, '.log')) {
                continue;
            }

            $path = realpath($this->logDir . '/' . $file);
            if (!$path || !str_starts_with($path, $this->logDir)) {
                continue;
            }

            $type = $this->resolveType($file);
            $fileEnv = $this->resolveEnv($file);

            if ($types && !in_array($type, $types, true)) {
                continue;
            }

            if ($env && $fileEnv && $env !== $fileEnv) {
                continue;
            }

            $logs[] = [
                'name' => $file,
                'path' => $path,
                'type' => $type,
                'env'  => $fileEnv,
                'size' => filesize($path),
                'mtime'=> filemtime($path),
            ];
        }

        $this->logger->info('Logs listés', ['count' => count($logs)]);
        return $logs;
    }

    /**
     * [Description for createZip]
     * Archive les logs déjà listés par listLogs() : chaque entrée doit porter
     * au moins les clés 'path' (chemin absolu) et 'name' (nom dans l'archive).
     *
     * @param array<int, array<string, mixed>> $logs
     *
     * @return string
     *
     * Created at: 14/07/2026 16:27:55 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function createZip(array $logs): string
    {
        if (empty($logs)) {
            throw new EmptyLogSelectionException('Aucun log à archiver.');
        }

        $zipPath = sys_get_temp_dir() . '/logs_' . date('Ymd_His') . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            throw new ZipCreationException($zipPath);
        }

        foreach ($logs as $log) {
            $zip->addFile($log['path'], $log['name']);
        }

        $zip->close();
        $this->logger->info('ZIP logs créé', ['zipPath' => $zipPath]);

        return $zipPath;
    }

    /**
     * [Description for createZipFromFilenames]
     * Archive une sélection de journaux désignés par leur seul nom de fichier.
     * Tout nom contenant un séparateur de chemin, ou ne résolvant pas vers un
     * fichier du dossier de logs, est ignoré (protection contre la traversée de répertoire).
     *
     * @param array<int, string> $filenames
     *
     * @return string
     *
     * Created at: 14/07/2026 16:29:37 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function createZipFromFilenames(array $filenames): string
    {
        if (empty($filenames)) {
            throw new EmptyLogSelectionException('Aucun fichier sélectionné.');
        }

        $zipPath = sys_get_temp_dir() . '/logs_selection_' . date('Ymd_His') . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            throw new ZipCreationException($zipPath);
        }

        $added = 0;
        foreach ($filenames as $filename) {
            if (str_contains($filename, '/') || str_contains($filename, '\\')) {
                continue;
            }

            $path = realpath($this->logDir . '/' . $filename);
            if (!$path || !str_starts_with($path, $this->logDir) || !is_file($path)) {
                $this->logger->warning('Fichier ignoré pour ZIP', ['filename' => $filename]);
                continue;
            }

            $zip->addFile($path, $filename);
            $added++;
        }

        $zip->close();

        if ($added === 0) {
            if (file_exists($zipPath)) {
                unlink($zipPath);
            }
            throw new EmptyLogSelectionException('Aucun fichier valide trouvé dans la sélection.');
        }

        $this->logger->info('ZIP sélection logs créé', ['zipPath' => $zipPath, 'added' => $added]);
        return $zipPath;
    }

    /**
     * [Description for resolveEnv]
     * Retourne le type d'environnement : dev, prod ou test
     *
     * @param string $filename
     *
     * @return string|null
     *
     * Created at: 14/12/2025 11:36:33 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function resolveEnv(string $filename): ?string
    {
        if (preg_match('/-(dev|prod|test)\.log$/', $filename, $m)) {
            return $m[1];
        }

        if (preg_match('/^(dev|prod|test)\.log$/', $filename, $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * [Description for resolveType]
     * retourne le type de log : main, app, request, messenger
     *
     * @param string $filename
     *
     * @return string
     *
     * Created at: 14/12/2025 11:37:47 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function resolveType(string $filename): string
    {
        return match (true) {
            str_starts_with($filename, 'app-'), $filename === 'app.log' => 'application',
            str_starts_with($filename, 'request-'), $filename === 'request.log' => 'request',
            str_starts_with($filename, 'messenger-'), $filename === 'messenger.log' => 'messenger',
            str_starts_with($filename, 'deprecations-'), $filename === 'deprecations.log' => 'deprecation',
            default => 'main',
        };
    }
}
