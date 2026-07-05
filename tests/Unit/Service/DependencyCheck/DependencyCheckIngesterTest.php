<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2026
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

declare(strict_types=1);

namespace App\Tests\Unit\Service\DependencyCheck;

use App\Entity\{DcCve, DcDependency, DcFinding, DcProcessingQueue, DcScan};
use App\Repository\{DcCveRepository, DcDependencyRepository ,DcScanRepository};
use App\Service\DependencyCheck\DcLatestVersionUpdater;
use App\Service\DependencyCheck\DependencyCheckIngester;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * MODIF 2026-05-08 : test unit du DependencyCheckIngester.
 *
 * Couvre :
 *  - parse + extraction projectInfo / scanInfo
 *  - idempotence (scan deja existant)
 *  - cache local CVE (meme CVE dans N deps -> 1 seul persist)
 *  - cache local Dep (meme dep mentionnée N fois -> 1 seul persist)
 *  - findOrCreate CVE deja en BDD (lastSeenAt mis a jour)
 *  - normalizeSeverity (CRITICAL/HIGH/MEDIUM/LOW/INFO + alias MODERATE)
 *  - parsePurl (pkg:maven/vendor/product@version)
 *  - parseReportDate avec nanosecondes
 *  - sha1 absent -> fallback sur sha256+filename
 *  - rollback sur erreur
 *  - compteurs scan post-ingestion
 */
#[AllowMockObjectsWithoutExpectations]
class DependencyCheckIngesterTest extends TestCase
{
    /** @var EntityManagerInterface&\PHPUnit\Framework\MockObject\MockObject */
    private $em;
    /** @var DcCveRepository&\PHPUnit\Framework\MockObject\MockObject */
    private $cveRepo;
    /** @var DcDependencyRepository&\PHPUnit\Framework\MockObject\MockObject */
    private $depRepo;
    /** @var DcScanRepository&\PHPUnit\Framework\MockObject\MockObject */
    private $scanRepo;
    /** @var LoggerInterface&\PHPUnit\Framework\MockObject\MockObject */
    private $logger;
    /* MODIF 2026-05-14 : mock du service de
     * maintenance des flags is_latest_* injecte dans l'ingester. */
    /** @var DcLatestVersionUpdater&\PHPUnit\Framework\MockObject\MockObject */
    private $latestUpdater;

    private DependencyCheckIngester $ingester;

    /* MODIF 2026-05-17 : type corrigé object[] (tableau hétérogène DcScan/DcDependency/DcCve/DcFinding). */
    /** @var object[] */
    private array $persisted = [];

    protected function setUp(): void
    {
        $this->em            = $this->createMock(EntityManagerInterface::class);
        $this->cveRepo       = $this->createMock(DcCveRepository::class);
        $this->depRepo       = $this->createMock(DcDependencyRepository::class);
        $this->scanRepo      = $this->createMock(DcScanRepository::class);
        $this->logger        = $this->createMock(LoggerInterface::class);
        $this->latestUpdater = $this->createMock(DcLatestVersionUpdater::class);

        // Capture des persist
        $this->persisted = [];
        $this->em->method('persist')->willReturnCallback(function (object $entity) {
            $this->persisted[] = $entity;
        });

        $this->ingester = new DependencyCheckIngester(
            $this->em, $this->cveRepo, $this->depRepo, $this->scanRepo, $this->logger,
            $this->latestUpdater
        );
    }

    // ═══════════════════ Idempotence ══════════════════════════════════════════

    public function testReturnsExistingScanIfAlreadyProcessed(): void
    {
        $existing = $this->makeScan(42, 'fr.test', 'demo', '1.0');
        $this->scanRepo->method('findByProjectVersionDate')->willReturn($existing);

        $queue = $this->buildQueue($this->minimalReport());
        $result = $this->ingester->ingest($queue);

        $this->assertSame($existing, $result);
        $this->assertSame([], $this->persisted, 'Aucun persist si scan deja existant');
    }

    // ═══════════════════ Happy path basique ══════════════════════════════════

    public function testCreatesScanWithSingleCveAndDep(): void
    {
        $this->scanRepo->method('findByProjectVersionDate')->willReturn(null);
        $this->cveRepo->method('findByCveId')->willReturn(null);
        $this->depRepo->method('findBySha1')->willReturn(null);

        $report = $this->minimalReport([
            'dependencies' => [[
                'sha1'     => str_repeat('a', 40),
                'fileName' => 'amqp-client-5.9.0.jar',
                'packages' => [['id' => 'pkg:maven/com.rabbitmq/amqp-client@5.9.0']],
                'vulnerabilities' => [[
                    'name'     => 'CVE-2023-46120',
                    'source'   => 'NVD',
                    'severity' => 'HIGH',
                    'cvssv3'   => ['baseScore' => 7.5, 'baseSeverity' => 'HIGH', 'attackVector' => 'NETWORK'],
                    'cwes'     => ['CWE-400'],
                    'description' => 'OOM dans amqp-client',
                    'references' => [['source' => 'NVD', 'url' => 'http://x', 'name' => 'ADV']],
                ]],
            ]],
        ]);

        $queue = $this->buildQueue($report);
        $scan = $this->ingester->ingest($queue);

        $this->assertInstanceOf(DcScan::class, $scan);
        $this->assertSame('fr.test:demo', $scan->getMavenKey());
        $this->assertSame('1.0', $scan->getProjectVersion());
        $this->assertSame(1, $scan->getDepCountTotal());
        $this->assertSame(1, $scan->getDepCountVulnerable());
        $this->assertSame(1, $scan->getCveCountHigh());
        $this->assertSame(1, $scan->getCveCountTotal());

        // 1 DcScan + 1 DcDependency + 1 DcCve + 1 DcFinding = 4 persists
        $this->assertCount(4, $this->persisted);
        $this->assertCount(1, array_filter($this->persisted, fn($e) => $e instanceof DcCve));
        $this->assertCount(1, array_filter($this->persisted, fn($e) => $e instanceof DcDependency));
        $this->assertCount(1, array_filter($this->persisted, fn($e) => $e instanceof DcFinding));
        $this->assertCount(1, array_filter($this->persisted, fn($e) => $e instanceof DcScan));

        // Verif Dep parsed correctement
        /** @var DcDependency $dep */
        $dep = current(array_filter($this->persisted, fn($e) => $e instanceof DcDependency));
        $this->assertSame('com.rabbitmq', $dep->getVendor());
        $this->assertSame('amqp-client', $dep->getProduct());
        $this->assertSame('5.9.0', $dep->getVersion());

        // Verif CVE parsed correctement
        /** @var DcCve $cve */
        $cve = current(array_filter($this->persisted, fn($e) => $e instanceof DcCve));
        $this->assertSame('CVE-2023-46120', $cve->getCveId());
        $this->assertSame('HIGH', $cve->getSeverity());
        $this->assertSame('7.5', $cve->getCvssV3Score());
        $this->assertSame(['CWE-400'], $cve->getCwes());
    }

    // ═══════════════════ Cache local CVE / Dep ════════════════════════════════

    public function testCacheLocalCveAvoidsDuplicatePersistInSameReport(): void
    {
        $this->scanRepo->method('findByProjectVersionDate')->willReturn(null);
        $this->cveRepo->method('findByCveId')->willReturn(null);
        $this->depRepo->method('findBySha1')->willReturn(null);

        // 2 deps differentes mais touchees par la MEME CVE
        $report = $this->minimalReport([
            'dependencies' => [
                [
                    'sha1' => str_repeat('1', 40), 'fileName' => 'lib1.jar',
                    'vulnerabilities' => [['name' => 'CVE-SHARED', 'severity' => 'HIGH']],
                ],
                [
                    'sha1' => str_repeat('2', 40), 'fileName' => 'lib2.jar',
                    'vulnerabilities' => [['name' => 'CVE-SHARED', 'severity' => 'HIGH']],
                ],
            ],
        ]);

        $scan = $this->ingester->ingest($this->buildQueue($report));

        $cves = array_values(array_filter($this->persisted, fn($e) => $e instanceof DcCve));
        $this->assertCount(1, $cves, 'CVE-SHARED ne doit etre persistee qu\'une fois (cache local)');
        $findings = array_values(array_filter($this->persisted, fn($e) => $e instanceof DcFinding));
        $this->assertCount(2, $findings, '2 findings (1 par dep) malgre 1 seule CVE');
        $this->assertSame(2, $scan->getCveCountTotal(), 'compteur CVE = 2 occurences');
    }

    public function testCacheLocalDepAvoidsDuplicatePersistInSameReport(): void
    {
        $this->scanRepo->method('findByProjectVersionDate')->willReturn(null);
        $this->cveRepo->method('findByCveId')->willReturn(null);
        $this->depRepo->method('findBySha1')->willReturn(null);

        // 2 entrees pour la MEME dep (sha1 identique) avec 2 CVE differentes
        $sameSha1 = str_repeat('a', 40);
        $report = $this->minimalReport([
            'dependencies' => [
                [
                    'sha1' => $sameSha1, 'fileName' => 'shared.jar',
                    'vulnerabilities' => [['name' => 'CVE-1', 'severity' => 'CRITICAL']],
                ],
                [
                    'sha1' => $sameSha1, 'fileName' => 'shared.jar',
                    'vulnerabilities' => [['name' => 'CVE-2', 'severity' => 'MEDIUM']],
                ],
            ],
        ]);

        $this->ingester->ingest($this->buildQueue($report));

        $deps = array_values(array_filter($this->persisted, fn($e) => $e instanceof DcDependency));
        $this->assertCount(1, $deps, 'Meme sha1 = 1 seule DcDependency');
    }

    public function testReusesExistingCveFromDatabaseAndUpdatesLastSeen(): void
    {
        $this->scanRepo->method('findByProjectVersionDate')->willReturn(null);
        $this->depRepo->method('findBySha1')->willReturn(null);

        $existingCve = (new DcCve())->setCveId('CVE-OLD')->setSource('NVD')->setSeverity('HIGH');
        $oldDate = new \DateTimeImmutable('2024-01-01');
        $existingCve->setLastSeenAt($oldDate);
        $this->cveRepo->method('findByCveId')->willReturn($existingCve);

        $report = $this->minimalReport([
            'dependencies' => [[
                'sha1' => str_repeat('a', 40), 'fileName' => 'lib.jar',
                'vulnerabilities' => [['name' => 'CVE-OLD', 'severity' => 'HIGH']],
            ]],
        ]);

        $this->ingester->ingest($this->buildQueue($report));

        $cves = array_filter($this->persisted, fn($e) => $e instanceof DcCve);
        $this->assertCount(0, $cves, 'CVE deja existante : pas de persist supplementaire');
        $this->assertGreaterThan($oldDate, $existingCve->getLastSeenAt(), 'lastSeenAt doit etre rafraichi');
    }

    // ═══════════════════ Compteurs ════════════════════════════════════════════

    public function testCountersAreCorrectAcrossSeverities(): void
    {
        $this->scanRepo->method('findByProjectVersionDate')->willReturn(null);
        $this->cveRepo->method('findByCveId')->willReturn(null);
        $this->depRepo->method('findBySha1')->willReturn(null);

        $report = $this->minimalReport([
            'dependencies' => [
                ['sha1' => str_repeat('1', 40), 'fileName' => 'a.jar',
                    'vulnerabilities' => [
                    ['name' => 'CVE-C1', 'severity' => 'CRITICAL'],
                    ['name' => 'CVE-H1', 'severity' => 'HIGH'],
                ]],
                ['sha1' => str_repeat('2', 40), 'fileName' => 'b.jar',
                    'vulnerabilities' => [
                    ['name' => 'CVE-M1', 'severity' => 'MEDIUM'],
                    ['name' => 'CVE-M2', 'severity' => 'MODERATE'], // alias MEDIUM
                    ['name' => 'CVE-L1', 'severity' => 'LOW'],
                ]],
                ['sha1' => str_repeat('3', 40), 'fileName' => 'clean.jar'], // pas de vulns
            ],
        ]);

        $scan = $this->ingester->ingest($this->buildQueue($report));

        $this->assertSame(3, $scan->getDepCountTotal());
        $this->assertSame(2, $scan->getDepCountVulnerable(), 'clean.jar n\'est pas vulnerable');
        $this->assertSame(1, $scan->getCveCountCritical());
        $this->assertSame(1, $scan->getCveCountHigh());
        $this->assertSame(2, $scan->getCveCountMedium(), 'MODERATE est alias de MEDIUM');
        $this->assertSame(1, $scan->getCveCountLow());
        $this->assertSame(5, $scan->getCveCountTotal());
    }

    // ═══════════════════ Edge cases parsing ═══════════════════════════════════

    public function testFallbackWhenSha1Missing(): void
    {
        $this->scanRepo->method('findByProjectVersionDate')->willReturn(null);
        $this->cveRepo->method('findByCveId')->willReturn(null);
        $this->depRepo->method('findBySha1')->willReturn(null);

        $report = $this->minimalReport([
            'dependencies' => [[
                'sha256' => str_repeat('s', 64),
                'fileName' => 'no-sha1.jar',
                'vulnerabilities' => [['name' => 'CVE-X', 'severity' => 'HIGH']],
            ]],
        ]);

        $this->ingester->ingest($this->buildQueue($report));

        /** @var DcDependency $dep */
        $dep = current(array_filter($this->persisted, fn($e) => $e instanceof DcDependency));
        $this->assertSame(40, strlen($dep->getSha1()), 'sha1 fallback sur sha256+fileName, longueur 40');
    }

    public function testParsesReportDateWithNanoseconds(): void
    {
        $this->scanRepo->method('findByProjectVersionDate')->willReturn(null);
        $this->cveRepo->method('findByCveId')->willReturn(null);
        $this->depRepo->method('findBySha1')->willReturn(null);

        $report = $this->minimalReport(['projectInfo' => [
            'groupID' => 'fr.test', 'artifactID' => 'demo', 'version' => '1.0',
            'reportDate' => '2026-05-08T06:15:17.838775800Z',
        ]]);

        $scan = $this->ingester->ingest($this->buildQueue($report));

        $this->assertSame('2026-05-08 06:15:17', $scan->getScanDate()->format('Y-m-d H:i:s'));
    }

    public function testThrowsOnInvalidJson(): void
    {
        $queue = (new DcProcessingQueue())
            ->setUlid('00000000000000000000000001')
            ->setPayloadGz(gzencode('not json at all', 6))
            ->setPayloadSha256(str_repeat('x', 64))
            ->setPayloadSize(15)
            ->setContentType('json')
            ->setProjectGroup('fr.test')->setProjectArtifact('demo')->setProjectVersion('1.0');

        $this->scanRepo->method('findByProjectVersionDate')->willReturn(null);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('JSON invalide');
        $this->ingester->ingest($queue);
    }

    public function testThrowsOnMissingCveName(): void
    {
        $this->scanRepo->method('findByProjectVersionDate')->willReturn(null);
        $this->depRepo->method('findBySha1')->willReturn(null);

        $report = $this->minimalReport([
            'dependencies' => [[
                'sha1' => str_repeat('a', 40), 'fileName' => 'x.jar',
                'vulnerabilities' => [['severity' => 'HIGH']], // pas de "name"
            ]],
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('cve_id manquant');
        $this->ingester->ingest($this->buildQueue($report));
    }

    public function testRollbackOnExceptionDuringFlush(): void
    {
        $this->scanRepo->method('findByProjectVersionDate')->willReturn(null);
        $this->cveRepo->method('findByCveId')->willReturn(null);
        $this->depRepo->method('findBySha1')->willReturn(null);

        $this->em->expects($this->once())->method('rollback');
        $this->em->method('flush')->willThrowException(new \RuntimeException('flush boom'));

        $report = $this->minimalReport([
            'dependencies' => [[
                'sha1' => str_repeat('a', 40), 'fileName' => 'x.jar',
                'vulnerabilities' => [['name' => 'CVE-1', 'severity' => 'HIGH']],
            ]],
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('flush boom');
        $this->ingester->ingest($this->buildQueue($report));
    }

    // ═══════════════════ Helpers ═════════════════════════════════════════════

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function minimalReport(array $overrides = []): array
    {
        $base = [
            'reportSchema' => '1.1',
            'scanInfo' => ['engineVersion' => '12.2.0'],
            'projectInfo' => [
                'groupID' => 'fr.test', 'artifactID' => 'demo', 'version' => '1.0',
                'reportDate' => '2026-05-08T08:00:00Z',
            ],
            'dependencies' => [],
        ];
        return array_replace_recursive($base, $overrides);
    }

    /**
     * @param array<string, mixed> $report
     */
    private function buildQueue(array $report): DcProcessingQueue
    {
        $json = json_encode($report);
        return (new DcProcessingQueue())
            ->setUlid('00000000000000000000000001')
            ->setPayloadGz(gzencode($json, 6))
            ->setPayloadSha256(hash('sha256', $json))
            ->setPayloadSize(strlen($json))
            ->setContentType('json')
            ->setProjectGroup('fr.test')
            ->setProjectArtifact('demo')
            ->setProjectVersion('1.0');
    }

    /**
     * Cree un DcScan factice avec ID force par reflection (pour les retours mockes).
     */
    private function makeScan(int $id, string $g, string $a, string $v): DcScan
    {
        $scan = (new DcScan())
            ->setMavenKey($g . ':' . $a)
            ->setProjectGroup($g)->setProjectArtifact($a)->setProjectVersion($v)
            ->setScanDate(new \DateTimeImmutable());
        $ref = new \ReflectionProperty(DcScan::class, 'id');
        $ref->setValue($scan, $id);
        return $scan;
    }
}
