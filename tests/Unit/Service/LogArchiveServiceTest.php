<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\LogArchiveService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ZipArchive;

#[AllowMockObjectsWithoutExpectations]
class LogArchiveServiceTest extends TestCase
{
    /** @var LoggerInterface&MockObject */
    private MockObject $logger;

    private string $logDir;

    private LogArchiveService $service;

    /** @var list<string> chemins ZIP à supprimer en tearDown */
    private array $zipsToCleanup = [];

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);

        // Sandbox temporaire unique pour isoler les tests
        $this->logDir = sys_get_temp_dir() . '/ma-moulinette-logs-' . uniqid('', true);
        mkdir($this->logDir, 0777, true);

        // Fixtures — variété de noms pour exercer resolveType/resolveEnv
        $fixtures = [
            'app-dev.log'          => "dev application log\n",
            'app-prod.log'         => "prod application log\n",
            'request-dev.log'      => "request log line\n",
            'messenger-test.log'   => "messenger test log\n",
            'deprecations-dev.log' => "deprecation log\n",
            'dev.log'              => "main dev log\n",   // env via regex courte
            'prod.log'              => "main prod log\n",
            'notes.txt'            => "not a log file\n", // filtré par extension
            'random.log'           => "log sans env\n",   // type=main env=null
        ];
        foreach ($fixtures as $name => $content) {
            file_put_contents($this->logDir . '/' . $name, $content);
        }

        $this->service = new LogArchiveService($this->logDir, $this->logger);
    }

    protected function tearDown(): void
    {
        foreach ($this->zipsToCleanup as $zip) {
            @unlink($zip);
        }

        if (is_dir($this->logDir)) {
            foreach (glob($this->logDir . '/*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($this->logDir);
        }
    }

    public function testConstructorThrowsWhenLogDirDoesNotExist(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Dossier de logs introuvable');

        new LogArchiveService(
            sys_get_temp_dir() . '/ma-moulinette-noexist-' . uniqid('', true),
            $this->logger
        );
    }

    public function testListLogsReturnsOnlyDotLogFiles(): void
    {
        $logs = $this->service->listLogs();
        $names = array_column($logs, 'name');

        // notes.txt doit être filtré
        $this->assertNotContains('notes.txt', $names);

        // Tous les autres .log présents dans la sandbox
        $expected = [
            'app-dev.log', 'app-prod.log',
            'request-dev.log', 'messenger-test.log', 'deprecations-dev.log',
            'dev.log', 'prod.log', 'random.log',
        ];
        sort($names);
        sort($expected);
        $this->assertSame($expected, $names);
    }

    public function testListLogsEntryHasExpectedMetadataShape(): void
    {
        $logs = $this->service->listLogs();
        $entry = $this->findByName($logs, 'app-dev.log');

        $this->assertIsString($entry['path']);
        $this->assertIsInt($entry['size']);
        $this->assertIsInt($entry['mtime']);
        $this->assertSame('application', $entry['type']);
        $this->assertSame('dev', $entry['env']);
    }

    public function testListLogsFiltersByTypes(): void
    {
        $logs = $this->service->listLogs(env: null, types: ['request', 'messenger']);
        $names = array_column($logs, 'name');

        sort($names);
        $this->assertSame(['messenger-test.log', 'request-dev.log'], $names);
    }

    public function testListLogsFiltersByEnv(): void
    {
        $logs = $this->service->listLogs(env: 'prod');
        $names = array_column($logs, 'name');

        // prod.log (env via regex courte) + app-prod.log
        // random.log a env=null donc échappe au filtre (env && fileEnv exige les deux non null)
        sort($names);
        $this->assertContains('app-prod.log', $names);
        $this->assertContains('prod.log', $names);
        $this->assertNotContains('app-dev.log', $names);
        $this->assertNotContains('request-dev.log', $names);
    }

    public function testListLogsResolvesTypeAndEnvCorrectlyAcrossFilenames(): void
    {
        $logs = $this->service->listLogs();
        $map = [];
        foreach ($logs as $log) {
            $map[$log['name']] = ['type' => $log['type'], 'env' => $log['env']];
        }

        $this->assertSame(['type' => 'application', 'env' => 'dev'],  $map['app-dev.log']);
        $this->assertSame(['type' => 'request', 'env' => 'dev'],       $map['request-dev.log']);
        $this->assertSame(['type' => 'messenger', 'env' => 'test'],    $map['messenger-test.log']);
        $this->assertSame(['type' => 'deprecation', 'env' => 'dev'],   $map['deprecations-dev.log']);
        $this->assertSame(['type' => 'main', 'env' => 'dev'],          $map['dev.log']);
        $this->assertSame(['type' => 'main', 'env' => null],           $map['random.log']);
    }

    public function testCreateZipThrowsWhenLogsListIsEmpty(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Aucun log à archiver');

        $this->service->createZip([]);
    }

    public function testCreateZipPacksProvidedLogsAndReturnsValidArchive(): void
    {
        $logs = $this->service->listLogs(types: ['application']); // 2 fichiers

        $zipPath = $this->service->createZip($logs);
        $this->zipsToCleanup[] = $zipPath;

        $this->assertFileExists($zipPath);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($zipPath) === true);
        $this->assertSame(2, $zip->numFiles);

        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $names[] = $zip->getNameIndex($i);
        }
        sort($names);
        $zip->close();

        $this->assertSame(['app-dev.log', 'app-prod.log'], $names);
    }

    public function testCreateZipFromFilenamesThrowsWhenEmpty(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Aucun fichier sélectionné');

        $this->service->createZipFromFilenames([]);
    }

    public function testCreateZipFromFilenamesSkipsPathTraversalAttempts(): void
    {
        $zipPath = $this->service->createZipFromFilenames([
            '../evil.log',         // contient '/' → skipped
            '..\\sneaky.log',      // contient '\' → skipped
            'app-dev.log',         // OK, présent
        ]);
        $this->zipsToCleanup[] = $zipPath;

        $zip = new ZipArchive();
        $zip->open($zipPath);
        $this->assertSame(1, $zip->numFiles);
        $this->assertSame('app-dev.log', $zip->getNameIndex(0));
        $zip->close();
    }

    public function testCreateZipFromFilenamesSkipsMissingFilesAndLogsWarning(): void
    {
        // Un warning attendu pour le fichier absent
        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                'Fichier ignoré pour ZIP',
                $this->callback(fn (array $ctx) => $ctx['filename'] === 'absent.log')
            );

        $zipPath = $this->service->createZipFromFilenames(['app-dev.log', 'absent.log']);
        $this->zipsToCleanup[] = $zipPath;

        $zip = new ZipArchive();
        $zip->open($zipPath);
        $this->assertSame(1, $zip->numFiles);
        $zip->close();
    }

    private function findByName(array $logs, string $name): array
    {
        foreach ($logs as $log) {
            if ($log['name'] === $name) {
                return $log;
            }
        }
        $this->fail(sprintf('Entrée "%s" introuvable dans le résultat de listLogs', $name));
    }
}
