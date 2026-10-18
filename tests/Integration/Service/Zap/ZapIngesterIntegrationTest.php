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

namespace App\Tests\Integration\Service\Zap;

use App\Entity\{OwaspDast, ZapProcessingQueue, ZapScan};
use App\Service\Zap\ZapIngester;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Ulid;

/**
 * MODIF 2026-10-13 : test Integration end-to-end de l'Ingester ZAP avec le
 * fixture réaliste `tests/Fixtures/zap-json/report-ok.json`, sur le gabarit
 * DependencyCheckIngesterIntegrationTest.
 *
 * Valide :
 *  - parsing du vrai format ZAP `traditional-json`
 *  - dedup règle dans une vraie transaction Doctrine
 *  - compteurs scan corrects
 *  - construction owasp_dast (2017 + 2021) à partir des tags réels du fixture
 *  - idempotence (2e ingest du même rapport -> même scan)
 */
class ZapIngesterIntegrationTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private ZapIngester $ingester;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->ingester = $container->get(ZapIngester::class);

        $this->em->getConnection()->executeStatement('DELETE FROM ma_moulinette.owasp_dast');
        $this->em->getConnection()->executeStatement('DELETE FROM ma_moulinette.zap_finding');
        $this->em->getConnection()->executeStatement('DELETE FROM ma_moulinette.zap_scan');
        $this->em->getConnection()->executeStatement('DELETE FROM ma_moulinette.zap_rule');
        $this->em->getConnection()->executeStatement('DELETE FROM ma_moulinette.zap_processing_queue');
    }

    public function testIngestRealSamplePopulatesExpectedCounts(): void
    {
        $samplePath = __DIR__ . '/../../../Fixtures/zap-json/report-ok.json';
        $this->assertFileExists($samplePath, 'Fixture report-ok.json absente de tests/Fixtures/zap-json/');

        $queue = $this->insertQueueFromSample($samplePath);

        $scan = $this->ingester->ingest($queue);
        $this->em->clear();

        $scan = $this->em->getRepository(ZapScan::class)->find($scan->getId());

        $this->assertSame('fr.ma-moulinette:ma-moulinette', $scan->getMavenKey());
        $this->assertSame('1.0', $scan->getProjectVersion());
        $this->assertSame('2.17.0', $scan->getZapVersion());

        // 3 alertes dans le fixture : 1 High (SQLi), 1 Medium (CSP), 1 Low (X-Powered-By)
        $this->assertSame(3, $scan->getAlertCountTotal());
        $this->assertSame(1, $scan->getRiskCountHigh());
        $this->assertSame(1, $scan->getRiskCountMedium());
        $this->assertSame(1, $scan->getRiskCountLow());
        $this->assertSame(0, $scan->getRiskCountInformational());

        $this->assertSame(3, (int) $this->em->getConnection()
            ->executeQuery('SELECT COUNT(*) FROM ma_moulinette.zap_rule')->fetchOne());
        $this->assertSame(3, (int) $this->em->getConnection()
            ->executeQuery('SELECT COUNT(*) FROM ma_moulinette.zap_finding')->fetchOne());

        // owasp_dast : 2 lignes (2017 et 2021), chaque alerte du fixture porte
        // exactement 1 tag de chaque année -> 3 catégories touchées par référentiel
        $owaspRows = $this->em->getRepository(OwaspDast::class)->findBy(['scan' => $scan]);
        $this->assertCount(2, $owaspRows);

        foreach ($owaspRows as $owaspDast) {
            if ($owaspDast->getReferentialOwasp() === 2021) {
                // A05 (CSP, Medium), A01 (X-Powered-By, Low), A03 (SQLi, High)
                $this->assertSame(1, $owaspDast->getCategoryCount(5));
                $this->assertSame(1, $owaspDast->getCategoryCount(1));
                $this->assertSame(1, $owaspDast->getCategoryCount(3));
            } else {
                // 2017 : A06 (CSP), A03 (X-Powered-By), A01 (SQLi)
                $this->assertSame(1, $owaspDast->getCategoryCount(6));
                $this->assertSame(1, $owaspDast->getCategoryCount(3));
                $this->assertSame(1, $owaspDast->getCategoryCount(1));
            }
        }
    }

    public function testIngestSampleTwiceIsIdempotent(): void
    {
        $samplePath = __DIR__ . '/../../../Fixtures/zap-json/report-ok.json';

        // 1ere ingestion : le scan est crée
        $queue1 = $this->insertQueueFromSample($samplePath);
        $scan1 = $this->ingester->ingest($queue1);
        $scanId1 = $scan1->getId();

        // 2eme ingestion via une autre row queue mais meme contenu
        // -> meme (maven_key, project_version, target_url, generated_at) -> idempotence
        $this->em->clear();
        $queue2 = $this->insertQueueFromSample($samplePath, distinct: true);
        $scan2 = $this->ingester->ingest($queue2);

        $this->assertSame($scanId1, $scan2->getId(), 'Le 2eme ingest doit retourner le meme scan');
        $this->assertSame(1, (int) $this->em->getConnection()
            ->executeQuery('SELECT COUNT(*) FROM ma_moulinette.zap_scan')->fetchOne());
    }

    private function insertQueueFromSample(string $samplePath, bool $distinct = false): ZapProcessingQueue
    {
        $json = file_get_contents($samplePath);
        self::assertNotFalse($json);
        $sha = hash('sha256', $json);
        if ($distinct) {
            // Pour éviter UniqueViolation sur sha256 quand on en insère 2
            $sha = hash('sha256', $json . microtime());
        }

        $payloadGz = gzencode($json, 6);
        self::assertNotFalse($payloadGz);

        $queue = (new ZapProcessingQueue())
            ->setUlid((new Ulid())->toBase32())
            ->setPayloadGz($payloadGz)
            ->setPayloadSha256($sha)
            ->setPayloadSize(strlen($json))
            ->setContentType(ZapProcessingQueue::CONTENT_JSON)
            ->setMavenKey('fr.ma-moulinette:ma-moulinette')
            ->setProjectVersion('1.0')
            ->setStatus(ZapProcessingQueue::STATUS_PROCESSING);

        $this->em->persist($queue);
        $this->em->flush();

        return $queue;
    }
}
