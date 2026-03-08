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

namespace App\Service;

use ZipArchive;
use Psr\Log\LoggerInterface;

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
            throw new \RuntimeException('Dossier de logs introuvable');
        }
    }

    /**
     * Liste les logs selon l'environnement et le type
     *
     * @param string|null $env
     * @param array|null $types
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

    public function createZip(array $logs): string
    {
        if (empty($logs)) {
            throw new \RuntimeException('Aucun log à archiver');
        }

        $zipPath = sys_get_temp_dir() . '/logs_' . date('Ymd_His') . '.zip';
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE);

        foreach ($logs as $log) {
            $zip->addFile($log['path'], $log['name']);
        }

        $zip->close();
        $this->logger->info('ZIP logs créé', ['zipPath' => $zipPath]);

        return $zipPath;
    }

    public function createZipFromFilenames(array $filenames): string
    {
        if (empty($filenames)) {
            throw new \RuntimeException('Aucun fichier sélectionné');
        }

        $zipPath = sys_get_temp_dir() . '/logs_selection_' . date('Ymd_His') . '.zip';
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE);

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
        }

        $zip->close();
        $this->logger->info('ZIP sélection logs créé', ['zipPath' => $zipPath]);
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
            str_starts_with($filename, 'app-') => 'application',
            str_starts_with($filename, 'request-') => 'request',
            str_starts_with($filename, 'messenger-') => 'messenger',
            str_starts_with($filename, 'deprecations-') => 'deprecation',
            default => 'main',
        };
    }
}
