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

namespace App\Tests\Integration\Service\NpmAudit;

use App\Entity\{NpmProcessingQueue, NpmScan};
use App\Service\NpmAudit\NpmAuditIngester;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Ulid;

/**
 * Test Integration end-to-end de l'Ingester avec une fixture réduite d'un
 * vrai rapport npm audit (projet Angular open source public, sans donnée
 * client — contrairement aux fixtures DC internes). Valide :
 *  - parsing du vrai format npm audit v2 (enveloppe {"full": {...}})
 *  - dédup dependency/vulnerability dans une vraie transaction Doctrine
 *  - contraintes réelles (unique, FK) que les mocks Unit ne peuvent pas exercer
 *  - idempotence : 2ᵉ ingest du même rapport -> même scan
 */
class NpmAuditIngesterIntegrationTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private NpmAuditIngester $ingester;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->ingester = $container->get(NpmAuditIngester::class);

        $this->em->getConnection()->executeStatement('DELETE FROM ma_moulinette.npm_finding');
        $this->em->getConnection()->executeStatement('DELETE FROM ma_moulinette.npm_scan');
        $this->em->getConnection()->executeStatement('DELETE FROM ma_moulinette.npm_dependency');
        $this->em->getConnection()->executeStatement('DELETE FROM ma_moulinette.npm_vulnerability');
        $this->em->getConnection()->executeStatement('DELETE FROM ma_moulinette.npm_processing_queue');
    }

    public function testIngestRealSamplePopulatesExpectedCounts(): void
    {
        $samplePath = __DIR__ . '/../../../Fixtures/npm-audit-json/angular-demo-reduced.json';
        if (!file_exists($samplePath)) {
            $this->markTestSkipped('Fixture angular-demo-reduced.json absente.');
        }

        $queue = $this->insertQueueFromSample($samplePath);

        $scan = $this->ingester->ingest($queue);
        $this->em->clear();

        $scan = $this->em->getRepository(NpmScan::class)->find($scan->getId());

        $this->assertSame('fr.ma-moulinette', $scan->getProjectGroup());
        $this->assertSame('angular-demo', $scan->getProjectArtifact());
        $this->assertSame('1.0.0', $scan->getProjectVersion());

        $this->assertSame(1, $scan->getVulnCountLow());
        $this->assertSame(2, $scan->getVulnCountModerate());
        $this->assertSame(1, $scan->getVulnCountHigh());
        $this->assertSame(4, $scan->getVulnCountTotal());
        $this->assertSame(90, $scan->getDepCountProd());
        $this->assertSame(504, $scan->getDepCountTotal());

        // 4 paquets persistés (y compris @angular/build, dont via=["vite"]
        // ne produit aucun finding — le paquet lui-même reste persisté,
        // seul le finding est filtré)
        $this->assertSame(4, (int) $this->em->getConnection()
            ->executeQuery('SELECT COUNT(*) FROM ma_moulinette.npm_dependency')->fetchOne());
        // 6 findings réels (1 body-parser + 4 fast-uri + 1 qs)
        $this->assertSame(6, (int) $this->em->getConnection()
            ->executeQuery('SELECT COUNT(*) FROM ma_moulinette.npm_finding')->fetchOne());
        $this->assertSame(6, (int) $this->em->getConnection()
            ->executeQuery('SELECT COUNT(*) FROM ma_moulinette.npm_vulnerability')->fetchOne());

        $severities = $this->em->getConnection()
            ->executeQuery('SELECT DISTINCT severity FROM ma_moulinette.npm_vulnerability ORDER BY severity')
            ->fetchFirstColumn();
        $this->assertContains('high', $severities);
        $this->assertContains('moderate', $severities);
        $this->assertContains('low', $severities);
    }

    public function testIngestSampleTwiceIsIdempotent(): void
    {
        $samplePath = __DIR__ . '/../../../Fixtures/npm-audit-json/angular-demo-reduced.json';
        if (!file_exists($samplePath)) {
            $this->markTestSkipped('Fixture angular-demo-reduced.json absente.');
        }

        $queue1 = $this->insertQueueFromSample($samplePath);
        $scan1 = $this->ingester->ingest($queue1);
        $scanId1 = $scan1->getId();

        $this->em->clear();
        $queue2 = $this->insertQueueFromSample($samplePath, distinct: true, sameCreatedAt: $queue1->getCreatedAt());
        $scan2 = $this->ingester->ingest($queue2);

        $this->assertSame($scanId1, $scan2->getId(), 'Le 2ᵉ ingest doit retourner le même scan');
        $this->assertSame(1, (int) $this->em->getConnection()
            ->executeQuery('SELECT COUNT(*) FROM ma_moulinette.npm_scan')->fetchOne());
    }

    public function testProdOnlyCrossingPersistsIsProdCorrectly(): void
    {
        $samplePath = __DIR__ . '/../../../Fixtures/npm-audit-json/angular-demo-reduced.json';
        if (!file_exists($samplePath)) {
            $this->markTestSkipped('Fixture angular-demo-reduced.json absente.');
        }

        $full = json_decode((string) file_get_contents($samplePath), true);
        $envelope = [
            'full' => $full,
            'prodOnly' => [
                'vulnerabilities' => ['body-parser' => $full['vulnerabilities']['body-parser']],
                'metadata' => ['vulnerabilities' => [], 'dependencies' => []],
            ],
        ];
        $json = json_encode($envelope, JSON_THROW_ON_ERROR);
        $payloadGz = gzencode($json, 6);
        self::assertNotFalse($payloadGz);

        $queue = (new NpmProcessingQueue())
            ->setUlid((new Ulid())->toBase32())
            ->setPayloadGz($payloadGz)
            ->setPayloadSha256(hash('sha256', $json))
            ->setPayloadSize(strlen($json))
            ->setContentType(NpmProcessingQueue::CONTENT_JSON)
            ->setProjectGroup('fr.ma-moulinette')
            ->setProjectArtifact('angular-demo')
            ->setProjectVersion('1.0.0');
        $this->em->persist($queue);
        $this->em->flush();

        $scan = $this->ingester->ingest($queue);

        $isProdByDependency = $this->em->getConnection()->executeQuery(
            'SELECT d.name, f.is_prod FROM ma_moulinette.npm_finding f
             JOIN ma_moulinette.npm_dependency d ON f.dependency_id = d.id
             WHERE f.scan_id = :scan',
            ['scan' => $scan->getId()]
        )->fetchAllAssociative();

        foreach ($isProdByDependency as $row) {
            if ($row['name'] === 'body-parser') {
                $this->assertTrue((bool) $row['is_prod'], 'body-parser doit être marqué isProd=true');
            } else {
                $this->assertFalse((bool) $row['is_prod'], $row['name'] . ' doit être marqué isProd=false');
            }
        }
    }

    public function testInstalledReportPopulatesInstalledVersion(): void
    {
        $samplePath = __DIR__ . '/../../../Fixtures/npm-audit-json/angular-demo-reduced.json';
        if (!file_exists($samplePath)) {
            $this->markTestSkipped('Fixture angular-demo-reduced.json absente.');
        }

        $full = json_decode((string) file_get_contents($samplePath), true);
        $envelope = [
            'full' => $full,
            'installed' => [
                'dependencies' => [
                    'body-parser' => ['version' => '1.19.0'],
                ],
            ],
        ];
        $json = json_encode($envelope, JSON_THROW_ON_ERROR);
        $payloadGz = gzencode($json, 6);
        self::assertNotFalse($payloadGz);

        $queue = (new NpmProcessingQueue())
            ->setUlid((new Ulid())->toBase32())
            ->setPayloadGz($payloadGz)
            ->setPayloadSha256(hash('sha256', $json))
            ->setPayloadSize(strlen($json))
            ->setContentType(NpmProcessingQueue::CONTENT_JSON)
            ->setProjectGroup('fr.ma-moulinette')
            ->setProjectArtifact('angular-demo')
            ->setProjectVersion('1.0.0');
        $this->em->persist($queue);
        $this->em->flush();

        $scan = $this->ingester->ingest($queue);

        $installedByName = $this->em->getConnection()->executeQuery(
            'SELECT d.name, f.installed_version FROM ma_moulinette.npm_finding f
             JOIN ma_moulinette.npm_dependency d ON f.dependency_id = d.id
             WHERE f.scan_id = :scan',
            ['scan' => $scan->getId()]
        )->fetchAllAssociative();
        $byName = array_column($installedByName, 'installed_version', 'name');

        $this->assertSame('1.19.0', $byName['body-parser']);
        $this->assertNull($byName['qs'], 'qs absent de l\'arbre "installed" -> installed_version reste null');
    }

    private function insertQueueFromSample(
        string $samplePath,
        bool $distinct = false,
        ?\DateTimeImmutable $sameCreatedAt = null,
    ): NpmProcessingQueue {
        $fullJson = file_get_contents($samplePath);
        self::assertNotFalse($fullJson);
        $full = json_decode($fullJson, true, 512, JSON_THROW_ON_ERROR);
        $envelopeJson = json_encode(['full' => $full], JSON_THROW_ON_ERROR);

        $sha = hash('sha256', $envelopeJson);
        if ($distinct) {
            $sha = hash('sha256', $envelopeJson . microtime());
        }

        $payloadGz = gzencode($envelopeJson, 6);
        self::assertNotFalse($payloadGz);

        $queue = (new NpmProcessingQueue())
            ->setUlid((new Ulid())->toBase32())
            ->setPayloadGz($payloadGz)
            ->setPayloadSha256($sha)
            ->setPayloadSize(strlen($envelopeJson))
            ->setContentType(NpmProcessingQueue::CONTENT_JSON)
            ->setProjectGroup('fr.ma-moulinette')
            ->setProjectArtifact('angular-demo')
            ->setProjectVersion('1.0.0');

        if ($sameCreatedAt !== null) {
            $queue->setCreatedAt($sameCreatedAt);
        }

        $this->em->persist($queue);
        $this->em->flush();
        return $queue;
    }
}
