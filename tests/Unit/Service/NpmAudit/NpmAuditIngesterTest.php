<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  https://www.gnu.org/licenses/agpl-3.0.html
 */

declare(strict_types=1);

namespace App\Tests\Unit\Service\NpmAudit;

use App\Entity\{NpmDependency, NpmFinding, NpmProcessingQueue, NpmScan, NpmVulnerability};
use App\Repository\{NpmDependencyRepository, NpmFindingRepository, NpmScanRepository, NpmVulnerabilityRepository};
use App\Service\NpmAudit\NpmAuditIngester;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Couvre :
 *  - idempotence (scan déjà existant)
 *  - parsing de l'enveloppe {"full": {...}, "prodOnly"?: {...}}
 *  - filtrage des entrées string de via[] (chaîne de dépendance, pas une vulnérabilité)
 *  - cache local vulnérabilité (même GHSA-ID dans N paquets)
 *  - extraction GHSA-ID depuis l'URL + repli sur hash du titre
 *  - parseFixAvailable (bool vs objet {name, version, isSemVerMajor})
 *  - normalizeSeverity (moderate/medium alias)
 *  - compteurs repris de metadata (pas recalculés)
 *  - croisement prod/dev (markIsProdForScan appelé seulement si prodOnly présent)
 *  - rollback sur erreur
 */
#[AllowMockObjectsWithoutExpectations]
class NpmAuditIngesterTest extends TestCase
{
    /** @var EntityManagerInterface&\PHPUnit\Framework\MockObject\MockObject */
    private $em;
    /** @var NpmVulnerabilityRepository&\PHPUnit\Framework\MockObject\MockObject */
    private $vulnRepo;
    /** @var NpmDependencyRepository&\PHPUnit\Framework\MockObject\MockObject */
    private $depRepo;
    /** @var NpmScanRepository&\PHPUnit\Framework\MockObject\MockObject */
    private $scanRepo;
    /** @var NpmFindingRepository&\PHPUnit\Framework\MockObject\MockObject */
    private $findingRepo;
    /** @var LoggerInterface&\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    private NpmAuditIngester $ingester;

    /** @var object[] */
    private array $persisted = [];

    protected function setUp(): void
    {
        $this->em          = $this->createMock(EntityManagerInterface::class);
        $this->vulnRepo     = $this->createMock(NpmVulnerabilityRepository::class);
        $this->depRepo      = $this->createMock(NpmDependencyRepository::class);
        $this->scanRepo      = $this->createMock(NpmScanRepository::class);
        $this->findingRepo  = $this->createMock(NpmFindingRepository::class);
        $this->logger       = $this->createMock(LoggerInterface::class);

        $this->persisted = [];
        $this->em->method('persist')->willReturnCallback(function (object $entity) {
            $this->persisted[] = $entity;
        });

        $this->ingester = new NpmAuditIngester(
            $this->em, $this->vulnRepo, $this->depRepo, $this->scanRepo, $this->findingRepo, $this->logger
        );
    }

    // ═══════════════════ Idempotence ══════════════════════════════════════════

    public function testReturnsExistingScanIfAlreadyProcessed(): void
    {
        $existing = $this->makeScan(42);
        $this->scanRepo->method('findByProjectVersionDate')->willReturn($existing);

        $queue = $this->buildQueue(['full' => $this->minimalFull()]);
        $result = $this->ingester->ingest($queue);

        $this->assertSame($existing, $result);
        $this->assertSame([], $this->persisted, 'Aucun persist si scan déjà existant');
    }

    // ═══════════════════ Happy path ═════════════════════════════════════════

    public function testCreatesScanWithSingleFinding(): void
    {
        $this->scanRepo->method('findByProjectVersionDate')->willReturn(null);
        $this->vulnRepo->method('findByAdvisoryId')->willReturn(null);
        $this->depRepo->method('findByName')->willReturn(null);

        $full = $this->minimalFull([
            'vulnerabilities' => [
                'body-parser' => [
                    'name' => 'body-parser',
                    'severity' => 'high',
                    'isDirect' => false,
                    'via' => [
                        [
                            'source' => 1096070,
                            'name' => 'body-parser',
                            'title' => 'body-parser vulnerable to DoS',
                            'url' => 'https://github.com/advisories/GHSA-27r3-jxmh-p828',
                            'severity' => 'high',
                            'cwe' => ['CWE-405'],
                            'cvss' => ['score' => 7.5, 'vectorString' => 'CVSS:3.1/AV:N'],
                            'range' => '<1.20.3',
                        ],
                    ],
                    'effects' => [],
                    'range' => '<1.20.3',
                    'nodes' => ['node_modules/body-parser'],
                    'fixAvailable' => true,
                ],
            ],
            'metadata' => [
                'vulnerabilities' => ['info' => 0, 'low' => 0, 'moderate' => 0, 'high' => 1, 'critical' => 0, 'total' => 1],
            ],
        ]);

        $scan = $this->ingester->ingest($this->buildQueue(['full' => $full]));

        $this->assertSame(1, $scan->getVulnCountHigh());
        $this->assertSame(1, $scan->getVulnCountTotal());

        $this->assertCount(4, $this->persisted); // NpmScan + NpmDependency + NpmVulnerability + NpmFinding
        $deps = array_values(array_filter($this->persisted, fn($e) => $e instanceof NpmDependency));
        $this->assertCount(1, $deps);
        $this->assertSame('body-parser', $deps[0]->getName());

        $vulns = array_values(array_filter($this->persisted, fn($e) => $e instanceof NpmVulnerability));
        $this->assertCount(1, $vulns);
        $this->assertSame('GHSA-27R3-JXMH-P828', $vulns[0]->getAdvisoryId());
        $this->assertSame(['CWE-405'], $vulns[0]->getCwes());

        $findings = array_values(array_filter($this->persisted, fn($e) => $e instanceof NpmFinding));
        $this->assertCount(1, $findings);
        $this->assertFalse($findings[0]->isDirect());
        $this->assertTrue($findings[0]->isFixAvailable());
        $this->assertNull($findings[0]->getFixToVersion());
    }

    public function testIgnoresStringEntriesInVia(): void
    {
        $this->scanRepo->method('findByProjectVersionDate')->willReturn(null);
        $this->vulnRepo->method('findByAdvisoryId')->willReturn(null);
        $this->depRepo->method('findByName')->willReturn(null);

        $full = $this->minimalFull([
            'vulnerabilities' => [
                '@angular/build' => [
                    'name' => '@angular/build',
                    'severity' => 'low',
                    'isDirect' => true,
                    'via' => ['vite'], // chaîne = maillon de la chaîne de dépendance, pas une vuln
                    'effects' => [],
                    'range' => '1.0.0',
                    'nodes' => [],
                    'fixAvailable' => true,
                ],
            ],
        ]);

        $this->ingester->ingest($this->buildQueue(['full' => $full]));

        $findings = array_filter($this->persisted, fn($e) => $e instanceof NpmFinding);
        $this->assertCount(0, $findings, 'via[] = ["vite"] ne doit produire aucun finding');
    }

    public function testFixAvailableAsObjectExtractsVersionAndSemVerMajor(): void
    {
        $this->scanRepo->method('findByProjectVersionDate')->willReturn(null);
        $this->vulnRepo->method('findByAdvisoryId')->willReturn(null);
        $this->depRepo->method('findByName')->willReturn(null);

        $full = $this->minimalFull([
            'vulnerabilities' => [
                'vite' => [
                    'name' => 'vite', 'severity' => 'high', 'isDirect' => true,
                    'via' => [['url' => 'https://github.com/advisories/GHSA-587h-7wmj-h5pm', 'title' => 'vite issue', 'severity' => 'high']],
                    'effects' => [], 'range' => '1.0.0', 'nodes' => [],
                    'fixAvailable' => ['name' => 'vite', 'version' => '7.0.0', 'isSemVerMajor' => true],
                ],
            ],
        ]);

        $this->ingester->ingest($this->buildQueue(['full' => $full]));

        $findings = array_values(array_filter($this->persisted, fn($e) => $e instanceof NpmFinding));
        $this->assertCount(1, $findings);
        $this->assertTrue($findings[0]->isFixAvailable());
        $this->assertSame('7.0.0', $findings[0]->getFixToVersion());
        $this->assertTrue($findings[0]->getFixIsSemVerMajor());
    }

    public function testAdvisoryIdFallsBackToTitleHashWhenNoGhsaInUrl(): void
    {
        $this->scanRepo->method('findByProjectVersionDate')->willReturn(null);
        $this->vulnRepo->method('findByAdvisoryId')->willReturn(null);
        $this->depRepo->method('findByName')->willReturn(null);

        $full = $this->minimalFull([
            'vulnerabilities' => [
                'retire-ish' => [
                    'name' => 'retire-ish', 'severity' => 'moderate', 'isDirect' => true,
                    'via' => [['url' => 'https://example.com/no-ghsa-here', 'title' => 'Titre libre sans GHSA', 'severity' => 'moderate']],
                    'effects' => [], 'range' => '1.0.0', 'nodes' => [], 'fixAvailable' => false,
                ],
            ],
        ]);

        $this->ingester->ingest($this->buildQueue(['full' => $full]));

        $vulns = array_values(array_filter($this->persisted, fn($e) => $e instanceof NpmVulnerability));
        $this->assertCount(1, $vulns);
        $this->assertStringStartsWith('TITLE-', $vulns[0]->getAdvisoryId());
    }

    // ═══════════════════ Version installée (rapport "installed") ═══════════

    public function testInstalledVersionNullWhenInstalledBlockAbsent(): void
    {
        $this->scanRepo->method('findByProjectVersionDate')->willReturn(null);
        $this->vulnRepo->method('findByAdvisoryId')->willReturn(null);
        $this->depRepo->method('findByName')->willReturn(null);

        $full = $this->minimalFull([
            'vulnerabilities' => [
                'body-parser' => [
                    'name' => 'body-parser', 'severity' => 'high', 'isDirect' => false,
                    'via' => [['url' => 'https://github.com/advisories/GHSA-27r3-jxmh-p828', 'title' => 'dos', 'severity' => 'high']],
                    'effects' => [], 'range' => '<1.20.3', 'nodes' => [], 'fixAvailable' => false,
                ],
            ],
        ]);

        $this->ingester->ingest($this->buildQueue(['full' => $full]));

        $findings = array_values(array_filter($this->persisted, fn($e) => $e instanceof NpmFinding));
        $this->assertCount(1, $findings);
        $this->assertNull($findings[0]->getInstalledVersion());
    }

    public function testInstalledVersionResolvedFromNestedTree(): void
    {
        $this->scanRepo->method('findByProjectVersionDate')->willReturn(null);
        $this->vulnRepo->method('findByAdvisoryId')->willReturn(null);
        $this->depRepo->method('findByName')->willReturn(null);

        $full = $this->minimalFull([
            'vulnerabilities' => [
                'body-parser' => [
                    'name' => 'body-parser', 'severity' => 'high', 'isDirect' => false,
                    'via' => [['url' => 'https://github.com/advisories/GHSA-27r3-jxmh-p828', 'title' => 'dos', 'severity' => 'high']],
                    'effects' => [], 'range' => '<1.20.3', 'nodes' => [], 'fixAvailable' => false,
                ],
            ],
        ]);
        $installed = [
            'dependencies' => [
                'express' => [
                    'version' => '4.19.2',
                    'dependencies' => [
                        'body-parser' => ['version' => '1.19.0'],
                    ],
                ],
            ],
        ];

        $this->ingester->ingest($this->buildQueue(['full' => $full, 'installed' => $installed]));

        $findings = array_values(array_filter($this->persisted, fn($e) => $e instanceof NpmFinding));
        $this->assertCount(1, $findings);
        $this->assertSame('1.19.0', $findings[0]->getInstalledVersion());
    }

    public function testInstalledVersionJoinsMultipleDistinctVersionsFound(): void
    {
        $this->scanRepo->method('findByProjectVersionDate')->willReturn(null);
        $this->vulnRepo->method('findByAdvisoryId')->willReturn(null);
        $this->depRepo->method('findByName')->willReturn(null);

        $full = $this->minimalFull([
            'vulnerabilities' => [
                'fast-uri' => [
                    'name' => 'fast-uri', 'severity' => 'moderate', 'isDirect' => false,
                    'via' => [['url' => 'https://github.com/advisories/GHSA-w74x-v6f9-j3xg', 'title' => 'spoof', 'severity' => 'moderate']],
                    'effects' => [], 'range' => '>=3.0.0 <3.1.9', 'nodes' => [], 'fixAvailable' => false,
                ],
            ],
        ]);
        // Conflit de résolution npm : 2 versions distinctes de fast-uri dans l'arbre.
        $installed = [
            'dependencies' => [
                'fast-uri' => ['version' => '3.0.5'],
                'ajv' => [
                    'version' => '8.0.0',
                    'dependencies' => [
                        'fast-uri' => ['version' => '3.1.0'],
                    ],
                ],
            ],
        ];

        $this->ingester->ingest($this->buildQueue(['full' => $full, 'installed' => $installed]));

        $findings = array_values(array_filter($this->persisted, fn($e) => $e instanceof NpmFinding));
        $this->assertCount(1, $findings);
        $this->assertSame('3.0.5, 3.1.0', $findings[0]->getInstalledVersion());
    }

    public function testInstalledVersionNullWhenPackageNotFoundInInstalledTree(): void
    {
        $this->scanRepo->method('findByProjectVersionDate')->willReturn(null);
        $this->vulnRepo->method('findByAdvisoryId')->willReturn(null);
        $this->depRepo->method('findByName')->willReturn(null);

        $full = $this->minimalFull([
            'vulnerabilities' => [
                'body-parser' => [
                    'name' => 'body-parser', 'severity' => 'high', 'isDirect' => false,
                    'via' => [['url' => 'https://github.com/advisories/GHSA-27r3-jxmh-p828', 'title' => 'dos', 'severity' => 'high']],
                    'effects' => [], 'range' => '<1.20.3', 'nodes' => [], 'fixAvailable' => false,
                ],
            ],
        ]);
        $installed = ['dependencies' => ['autre-paquet' => ['version' => '1.0.0']]];

        $this->ingester->ingest($this->buildQueue(['full' => $full, 'installed' => $installed]));

        $findings = array_values(array_filter($this->persisted, fn($e) => $e instanceof NpmFinding));
        $this->assertCount(1, $findings);
        $this->assertNull($findings[0]->getInstalledVersion());
    }

    // ═══════════════════ Cache local vulnérabilité ══════════════════════════

    public function testCacheLocalVulnerabilityAvoidsDuplicatePersistAcrossPackages(): void
    {
        $this->scanRepo->method('findByProjectVersionDate')->willReturn(null);
        $this->vulnRepo->method('findByAdvisoryId')->willReturn(null);
        $this->depRepo->method('findByName')->willReturn(null);

        $sharedVia = ['url' => 'https://github.com/advisories/GHSA-aaaa-bbbb-cccc', 'title' => 'shared', 'severity' => 'moderate'];
        $full = $this->minimalFull([
            'vulnerabilities' => [
                'pkg-a' => ['name' => 'pkg-a', 'severity' => 'moderate', 'isDirect' => true, 'via' => [$sharedVia], 'effects' => [], 'range' => '1.0.0', 'nodes' => [], 'fixAvailable' => false],
                'pkg-b' => ['name' => 'pkg-b', 'severity' => 'moderate', 'isDirect' => false, 'via' => [$sharedVia], 'effects' => [], 'range' => '1.0.0', 'nodes' => [], 'fixAvailable' => false],
            ],
        ]);

        $this->ingester->ingest($this->buildQueue(['full' => $full]));

        $vulns = array_filter($this->persisted, fn($e) => $e instanceof NpmVulnerability);
        $this->assertCount(1, $vulns, 'Même GHSA-ID dans 2 paquets => 1 seule NpmVulnerability persistée');
        $findings = array_filter($this->persisted, fn($e) => $e instanceof NpmFinding);
        $this->assertCount(2, $findings, '2 findings (1 par paquet) malgré 1 seule vulnérabilité partagée');
    }

    public function testReusesExistingVulnerabilityFromDatabaseAndUpdatesLastSeen(): void
    {
        $this->scanRepo->method('findByProjectVersionDate')->willReturn(null);
        $this->depRepo->method('findByName')->willReturn(null);

        $existing = (new NpmVulnerability())->setAdvisoryId('GHSA-OLD-0000-0000')->setSeverity('low');
        $oldDate = new \DateTimeImmutable('2024-01-01');
        $existing->setLastSeenAt($oldDate);
        $this->vulnRepo->method('findByAdvisoryId')->willReturn($existing);

        $full = $this->minimalFull([
            'vulnerabilities' => [
                'pkg' => ['name' => 'pkg', 'severity' => 'low', 'isDirect' => true,
                    'via' => [['url' => 'https://github.com/advisories/GHSA-OLD-0000-0000', 'title' => 'old', 'severity' => 'low']],
                    'effects' => [], 'range' => '1.0.0', 'nodes' => [], 'fixAvailable' => false],
            ],
        ]);

        $this->ingester->ingest($this->buildQueue(['full' => $full]));

        $vulns = array_filter($this->persisted, fn($e) => $e instanceof NpmVulnerability);
        $this->assertCount(0, $vulns, 'Vulnérabilité déjà existante : pas de persist supplémentaire');
        $this->assertGreaterThan($oldDate, $existing->getLastSeenAt());
    }

    // ═══════════════════ Compteurs (repris de metadata) ═════════════════════

    public function testCountersAreReadDirectlyFromMetadataNotRecomputed(): void
    {
        $this->scanRepo->method('findByProjectVersionDate')->willReturn(null);
        $this->vulnRepo->method('findByAdvisoryId')->willReturn(null);
        $this->depRepo->method('findByName')->willReturn(null);

        $full = $this->minimalFull([
            'vulnerabilities' => [], // aucun finding réel
            'metadata' => [
                'vulnerabilities' => ['info' => 0, 'low' => 1, 'moderate' => 2, 'high' => 3, 'critical' => 0, 'total' => 6],
                'dependencies' => ['prod' => 90, 'dev' => 415, 'optional' => 156, 'peer' => 18, 'peerOptional' => 0, 'total' => 504],
            ],
        ]);

        $scan = $this->ingester->ingest($this->buildQueue(['full' => $full]));

        $this->assertSame(1, $scan->getVulnCountLow());
        $this->assertSame(2, $scan->getVulnCountModerate());
        $this->assertSame(3, $scan->getVulnCountHigh());
        $this->assertSame(6, $scan->getVulnCountTotal());
        $this->assertSame(90, $scan->getDepCountProd());
        $this->assertSame(415, $scan->getDepCountDev());
        $this->assertSame(504, $scan->getDepCountTotal());
    }

    // ═══════════════════ Croisement prod/dev ═══════════════════════════════

    public function testMarkIsProdForScanNotCalledWhenProdOnlyAbsent(): void
    {
        $this->scanRepo->method('findByProjectVersionDate')->willReturn(null);
        $this->vulnRepo->method('findByAdvisoryId')->willReturn(null);
        $this->depRepo->method('findByName')->willReturn(null);

        $this->findingRepo->expects($this->never())->method('markIsProdForScan');

        $this->ingester->ingest($this->buildQueue(['full' => $this->minimalFull()]));
    }

    public function testMarkIsProdForScanCalledWithProdPackageNamesWhenProdOnlyPresent(): void
    {
        $this->scanRepo->method('findByProjectVersionDate')->willReturn(null);
        $this->vulnRepo->method('findByAdvisoryId')->willReturn(null);
        $this->depRepo->method('findByName')->willReturn(null);

        $this->findingRepo->expects($this->once())
            ->method('markIsProdForScan')
            ->with($this->callback('is_int'), ['vite', 'undici']);

        $envelope = [
            'full' => $this->minimalFull(),
            'prodOnly' => $this->minimalFull([
                'vulnerabilities' => [
                    'vite' => ['name' => 'vite', 'severity' => 'high', 'isDirect' => true, 'via' => [], 'effects' => [], 'range' => '1', 'nodes' => [], 'fixAvailable' => false],
                    'undici' => ['name' => 'undici', 'severity' => 'high', 'isDirect' => false, 'via' => [], 'effects' => [], 'range' => '1', 'nodes' => [], 'fixAvailable' => false],
                ],
            ]),
        ];

        $this->ingester->ingest($this->buildQueue($envelope));
    }

    public function testMarkIsProdForScanFailureIsLoggedNotThrown(): void
    {
        $this->scanRepo->method('findByProjectVersionDate')->willReturn(null);
        $this->vulnRepo->method('findByAdvisoryId')->willReturn(null);
        $this->depRepo->method('findByName')->willReturn(null);
        $this->findingRepo->method('markIsProdForScan')->willThrowException(new \RuntimeException('sql boom'));

        $envelope = ['full' => $this->minimalFull(), 'prodOnly' => $this->minimalFull()];

        // Ne doit pas relancer l'exception : le scan est déjà ingéré avec succès,
        // seul isProd reste indéterminé.
        $scan = $this->ingester->ingest($this->buildQueue($envelope));
        $this->assertSame(0, $scan->getVulnCountTotal());
    }

    // ═══════════════════ Erreurs ═════════════════════════════════════════════

    public function testThrowsOnMissingFullBlock(): void
    {
        $this->scanRepo->method('findByProjectVersionDate')->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('"full" absent');
        $this->ingester->ingest($this->buildQueue(['notFull' => []]));
    }

    public function testThrowsOnInvalidJson(): void
    {
        $payloadGz = gzencode('not json at all', 6);
        self::assertNotFalse($payloadGz);
        $queue = (new NpmProcessingQueue())
            ->setUlid('00000000000000000000000001')
            ->setPayloadGz($payloadGz)
            ->setPayloadSha256(str_repeat('x', 64))
            ->setPayloadSize(15)
            ->setContentType('json')
            ->setProjectGroup('fr.ma-moulinette')
            ->setProjectArtifact('angular-demo')
            ->setProjectVersion('1.0');

        $this->scanRepo->method('findByProjectVersionDate')->willReturn(null);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('JSON invalide');
        $this->ingester->ingest($queue);
    }

    public function testRollbackOnExceptionDuringFlush(): void
    {
        $this->scanRepo->method('findByProjectVersionDate')->willReturn(null);
        $this->vulnRepo->method('findByAdvisoryId')->willReturn(null);
        $this->depRepo->method('findByName')->willReturn(null);

        $this->em->expects($this->once())->method('rollback');
        $this->em->method('flush')->willThrowException(new \RuntimeException('flush boom'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('flush boom');
        $this->ingester->ingest($this->buildQueue(['full' => $this->minimalFull()]));
    }

    // ═══════════════════ Helpers ═════════════════════════════════════════════

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function minimalFull(array $overrides = []): array
    {
        $base = [
            'auditReportVersion' => 2,
            'vulnerabilities' => [],
            'metadata' => [
                'vulnerabilities' => ['info' => 0, 'low' => 0, 'moderate' => 0, 'high' => 0, 'critical' => 0, 'total' => 0],
                'dependencies' => ['prod' => 1, 'dev' => 1, 'optional' => 0, 'peer' => 0, 'peerOptional' => 0, 'total' => 2],
            ],
        ];
        return array_replace_recursive($base, $overrides);
    }

    /**
     * @param array<string, mixed> $envelope
     */
    private function buildQueue(array $envelope): NpmProcessingQueue
    {
        $json = json_encode($envelope, JSON_THROW_ON_ERROR);
        $payloadGz = gzencode($json, 6);
        self::assertNotFalse($payloadGz);
        return (new NpmProcessingQueue())
            ->setUlid('00000000000000000000000001')
            ->setPayloadGz($payloadGz)
            ->setPayloadSha256(hash('sha256', $json))
            ->setPayloadSize(strlen($json))
            ->setContentType('json')
            ->setProjectGroup('fr.ma-moulinette')
            ->setProjectArtifact('angular-demo')
            ->setProjectVersion('1.0');
    }

    private function makeScan(int $id): NpmScan
    {
        $scan = (new NpmScan())
            ->setProjectGroup('fr.ma-moulinette')->setProjectArtifact('angular-demo')->setProjectVersion('1.0')
            ->setScanDate(new \DateTimeImmutable());
        $ref = new \ReflectionProperty(NpmScan::class, 'id');
        $ref->setValue($scan, $id);
        return $scan;
    }
}
