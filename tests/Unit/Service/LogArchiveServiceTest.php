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

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Exception\EmptyLogSelectionException;
use App\Exception\LogDirectoryNotFoundException;
use App\Service\LogArchive\LogArchiveService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ZipArchive;

/**
 * Tests unitaires de LogArchiveService.
 *
 * Couvre :
 *  - listLogs : filtrage env, type, fichiers non-.log ignorés
 *  - createZipFromFilenames : happy path, entrée vide, tous fichiers invalides,
 *    protection path-traversal (/ et \)
 *  - createZip : exception sur tableau vide
 */
class LogArchiveServiceTest extends TestCase
{
    private string $tmpDir;
    private LogArchiveService $service;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/ma_moulinette_log_test_' . uniqid('', true);
        mkdir($this->tmpDir, 0777, true);

        $logger = $this->createStub(LoggerInterface::class);
        $this->service = new LogArchiveService($this->tmpDir, $logger);
    }

    protected function tearDown(): void
    {
        foreach ((scandir($this->tmpDir) ?: []) as $f) {
            if ($f === '.' || $f === '..') {
                continue;
            }
            $path = $this->tmpDir . '/' . $f;
            if (is_file($path)) {
                unlink($path);
            }
        }
        rmdir($this->tmpDir);
    }

    /* ================================================================
     * Helpers
     * ================================================================ */

    private function touch(string $filename, string $content = 'log'): void
    {
        file_put_contents($this->tmpDir . '/' . $filename, $content);
    }

    /* ================================================================
     * __construct
     * ================================================================ */

    public function testConstructorThrowsWhenLogDirDoesNotExist(): void
    {
        $logger = $this->createStub(LoggerInterface::class);

        $this->expectException(LogDirectoryNotFoundException::class);
        $this->expectExceptionMessage('Dossier de logs introuvable');

        new LogArchiveService('/nonexistent/path/that/cannot/exist_' . uniqid('', true), $logger);
    }

    /* ================================================================
     * listLogs
     * ================================================================ */

    public function testListLogsReturnsOnlyDotLogFiles(): void
    {
        $this->touch('prod.log');
        $this->touch('prod.log.gz');   // doit être ignoré (pas .log)
        $this->touch('readme.txt');     // doit être ignoré

        $logs = $this->service->listLogs();

        $this->assertCount(1, $logs);
        $this->assertSame('prod.log', $logs[0]['name']);
    }

    public function testListLogsFiltersByEnv(): void
    {
        $this->touch('prod.log');
        $this->touch('dev.log');
        $this->touch('app-prod.log');

        $logs = $this->service->listLogs('prod');
        $names = array_column($logs, 'name');

        $this->assertContains('prod.log', $names);
        $this->assertContains('app-prod.log', $names);
        $this->assertNotContains('dev.log', $names);
    }

    public function testListLogsFiltersByType(): void
    {
        $this->touch('prod.log');
        $this->touch('app-prod.log');
        $this->touch('request-prod.log');

        $logs = $this->service->listLogs(null, ['application']);
        $names = array_column($logs, 'name');

        $this->assertContains('app-prod.log', $names);
        $this->assertNotContains('prod.log', $names);
        $this->assertNotContains('request-prod.log', $names);
    }

    public function testListLogsReturnsEmptyWhenNoLogFiles(): void
    {
        $this->touch('readme.txt');

        $logs = $this->service->listLogs();

        $this->assertSame([], $logs);
    }

    public function testListLogsResolvesTypesCorrectly(): void
    {
        $this->touch('prod.log');
        $this->touch('app-prod.log');
        $this->touch('request-prod.log');
        $this->touch('messenger-prod.log');
        $this->touch('deprecations-prod.log');

        $logs = $this->service->listLogs();
        $byName = array_column($logs, 'type', 'name');

        $this->assertSame('main',        $byName['prod.log']);
        $this->assertSame('application', $byName['app-prod.log']);
        $this->assertSame('request',     $byName['request-prod.log']);
        $this->assertSame('messenger',   $byName['messenger-prod.log']);
        $this->assertSame('deprecation', $byName['deprecations-prod.log']);
    }

    /**
     * monolog.yaml écrit "request"/"messenger"/"deprecations" toujours sans
     * suffixe d'environnement (chemin statique, jamais `%kernel.environment%`),
     * et "app.log" (application, when@prod) sans tiret non plus. Ce sont les
     * seuls noms que l'appli produit réellement pour ces 4 types — contrairement
     * aux variantes "<prefix>-<env>.log" du test ci-dessus, qui ne
     * correspondent à aucun fichier jamais généré en pratique.
     */
    public function testListLogsResolvesTypesForRealUnsuffixedFilenames(): void
    {
        $this->touch('app.log');
        $this->touch('request.log');
        $this->touch('messenger.log');
        $this->touch('deprecations.log');

        $logs = $this->service->listLogs();
        $byName = array_column($logs, 'type', 'name');

        $this->assertSame('application', $byName['app.log']);
        $this->assertSame('request',     $byName['request.log']);
        $this->assertSame('messenger',   $byName['messenger.log']);
        $this->assertSame('deprecation', $byName['deprecations.log']);
    }

    public function testListLogsResolvesEnvFromFilename(): void
    {
        $this->touch('prod.log');
        $this->touch('app-dev.log');
        $this->touch('unknown-channel.log');

        $logs = $this->service->listLogs();
        $byName = array_column($logs, 'env', 'name');

        $this->assertSame('prod', $byName['prod.log']);
        $this->assertSame('dev',  $byName['app-dev.log']);
        $this->assertNull($byName['unknown-channel.log']);
    }

    /* ================================================================
     * createZipFromFilenames
     * ================================================================ */

    public function testCreateZipFromFilenamesProducesReadableZip(): void
    {
        $this->touch('prod.log', 'contenu prod');
        $this->touch('dev.log',  'contenu dev');

        $zipPath = $this->service->createZipFromFilenames(['prod.log', 'dev.log']);

        $this->assertFileExists($zipPath);

        $zip = new ZipArchive();
        $this->assertSame(true, $zip->open($zipPath));
        $this->assertSame(2, $zip->numFiles);
        $zip->close();

        unlink($zipPath);
    }

    public function testCreateZipFromFilenamesThrowsOnEmptyInput(): void
    {
        $this->expectException(EmptyLogSelectionException::class);

        $this->service->createZipFromFilenames([]);
    }

    public function testCreateZipFromFilenamesThrowsWhenAllFilesAreInvalid(): void
    {
        $this->expectException(EmptyLogSelectionException::class);
        $this->expectExceptionMessageMatches('/aucun fichier valide/i');

        // Aucun fichier créé → tous les noms sont introuvables
        $this->service->createZipFromFilenames(['fantome.log', 'absent.log']);
    }

    public function testCreateZipFromFilenamesBlocksForwardSlash(): void
    {
        $this->touch('prod.log');

        $this->expectException(EmptyLogSelectionException::class);

        // Seul le fichier avec slash est passé → aucun valide → exception
        $this->service->createZipFromFilenames(['../etc/passwd']);
    }

    public function testCreateZipFromFilenamesBlocksBackslash(): void
    {
        $this->touch('prod.log');

        $this->expectException(EmptyLogSelectionException::class);

        $this->service->createZipFromFilenames(['..\windows\system32\config']);
    }

    public function testCreateZipFromFilenamesSkipsInvalidButContinues(): void
    {
        $this->touch('prod.log', 'ok');

        // '../etc/passwd' ignoré car contient '/', 'prod.log' accepté
        $zipPath = $this->service->createZipFromFilenames(['../etc/passwd', 'prod.log']);

        $zip = new ZipArchive();
        $zip->open($zipPath);
        $this->assertSame(1, $zip->numFiles);
        $zip->close();

        unlink($zipPath);
    }

    /* ================================================================
     * createZip
     * ================================================================ */

    public function testCreateZipThrowsOnEmptyLogArray(): void
    {
        $this->expectException(EmptyLogSelectionException::class);

        $this->service->createZip([]);
    }

    public function testCreateZipProducesZipFromLogArray(): void
    {
        $this->touch('prod.log', 'data');

        $logs = [[
            'name' => 'prod.log',
            'path' => $this->tmpDir . '/prod.log',
        ]];

        $zipPath = $this->service->createZip($logs);

        $this->assertFileExists($zipPath);
        $zip = new ZipArchive();
        $zip->open($zipPath);
        $this->assertSame(1, $zip->numFiles);
        $zip->close();

        unlink($zipPath);
    }
}
