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

namespace App\Tests\Integration\Service\DependencyCheck;

use App\Entity\{DcCve, DcDependency, DcFinding, DcProcessingQueue, DcScan};
use App\Service\DependencyCheck\DependencyCheckIngester;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Ulid;

/**
 * MODIF 2026-05-08 : test Integration end-to-end
 * de l'Ingester avec le sample reel 3 MB de production.
 *
 * Valide :
 *  - parsing du vrai format DC v1.1
 *  - dedup CVE/dep dans une vraie transaction Doctrine
 *  - compteurs scan corrects (vs. l'analyse manuelle markdown)
 *  - Idempotence : 2eme ingest du meme rapport -> meme scan
 *
 */
class DependencyCheckIngesterIntegrationTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private DependencyCheckIngester $ingester;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->ingester = $container->get(DependencyCheckIngester::class);

        // Cleanup tables DC pour isolation
        $this->em->getConnection()->executeStatement('DELETE FROM ma_moulinette.dc_finding');
        $this->em->getConnection()->executeStatement('DELETE FROM ma_moulinette.dc_scan');
        $this->em->getConnection()->executeStatement('DELETE FROM ma_moulinette.dc_dependency');
        $this->em->getConnection()->executeStatement('DELETE FROM ma_moulinette.dc_cve');
        $this->em->getConnection()->executeStatement('DELETE FROM ma_moulinette.dc_processing_queue');
    }

    public function testIngestRealSamplePopulatesExpectedCounts(): void
    {
        $samplePath = __DIR__ . '/../../../../var/dependency-check-report.json';
        if (!file_exists($samplePath)) {
            $this->markTestSkipped('Sample dependency-check-report.json absent dans var/');
        }

        $queue = $this->insertQueueFromSample($samplePath);

        $scan = $this->ingester->ingest($queue);
        $this->em->clear();

        // Recharge depuis la BDD
        $scan = $this->em->getRepository(DcScan::class)->find($scan->getId());

        $this->assertSame('fr.ma-moulinette:ma-moulinette', $scan->getMavenKey());
        $this->assertSame('4.2.2-RELEASE', $scan->getProjectVersion());
        $this->assertSame('12.2.0', $scan->getEngineVersion());

        // Comptages attendus pour le sample reel (validés en lot 2.3)
        $this->assertSame(201, $scan->getDepCountTotal());
        $this->assertSame(28, $scan->getDepCountVulnerable());
        $this->assertSame(96, $scan->getCveCountTotal());

        // Repartition par severity
        $this->assertGreaterThan(0, $scan->getCveCountCritical());
        $this->assertGreaterThan(0, $scan->getCveCountHigh());
        $this->assertGreaterThan(0, $scan->getCveCountMedium());

        // Tables peuplees correctement
        $this->assertSame(28, (int) $this->em->getConnection()
            ->executeQuery('SELECT COUNT(*) FROM ma_moulinette.dc_dependency')->fetchOne());
        $this->assertSame(96, (int) $this->em->getConnection()
            ->executeQuery('SELECT COUNT(*) FROM ma_moulinette.dc_finding')->fetchOne());

        // Sanity check : 4 severites distinctes presentes en CVE
        $severities = $this->em->getConnection()
            ->executeQuery("SELECT DISTINCT severity FROM ma_moulinette.dc_cve ORDER BY severity")
            ->fetchFirstColumn();
        $this->assertContains('CRITICAL', $severities);
        $this->assertContains('HIGH', $severities);
        $this->assertContains('MEDIUM', $severities);
    }

    public function testIngestSampleTwiceIsIdempotent(): void
    {
        $samplePath = __DIR__ . '/../../../../var/dependency-check-report.json';
        if (!file_exists($samplePath)) {
            $this->markTestSkipped('Sample dependency-check-report.json absent dans var/');
        }

        // 1ere ingestion : le scan est cree
        $queue1 = $this->insertQueueFromSample($samplePath);
        $scan1 = $this->ingester->ingest($queue1);
        $scanId1 = $scan1->getId();

        // 2eme ingestion via une autre row queue mais meme contenu
        // -> meme (group, artifact, version, scan_date) -> idempotence
        $this->em->clear();
        $queue2 = $this->insertQueueFromSample($samplePath, distinct: true);
        $scan2 = $this->ingester->ingest($queue2);

        $this->assertSame($scanId1, $scan2->getId(), 'Le 2eme ingest doit retourner le meme scan');

        // 1 seul scan en BDD au final
        $this->assertSame(1, (int) $this->em->getConnection()
            ->executeQuery('SELECT COUNT(*) FROM ma_moulinette.dc_scan')->fetchOne());
    }

    /**
     * Insere une row queue avec le payload du sample.
     */
    private function insertQueueFromSample(string $samplePath, bool $distinct = false): DcProcessingQueue
    {
        $jsonString = file_get_contents($samplePath);
        $sha = hash('sha256', $jsonString);
        if ($distinct) {
            // Pour éviter UniqueViolation sur sha256 quand on en met 2
            $sha = hash('sha256', $jsonString . microtime());
        }

        $queue = (new DcProcessingQueue())
            ->setUlid((new Ulid())->toBase32())
            ->setPayloadGz(gzencode($jsonString, 6))
            ->setPayloadSha256($sha)
            ->setPayloadSize(strlen($jsonString))
            ->setContentType(DcProcessingQueue::CONTENT_JSON)
            ->setProjectGroup('fr.ma-moulinette')
            ->setProjectArtifact('ma-moulinette')
            ->setProjectVersion('4.2.2-RELEASE');

        $this->em->persist($queue);
        $this->em->flush();
        return $queue;
    }
}
