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

namespace App\Tests\Integration\Repository;

use App\Entity\ZapScan;
use App\Repository\ZapScanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * MODIF 26/10/2026 : couverture du départage par id quand deux scans
 * partagent le même generatedAt — cas réel rencontré en scindant un rapport
 * ZAP multi-site en plusieurs rapports mono-site avant ingestion (même
 * horodatage `@generated` hérité du rapport d'origine sur chaque morceau).
 * Avant correctif, `ORDER BY generatedAt DESC` seul rendait le choix du
 * "scan le plus récent" non déterministe entre les deux.
 *
 * MODIF 26/10/2026 (suite) : un maven_key peut recouvrir plusieurs cibles
 * DAST réelles (ex. tier présentation + tier métier d'une même application,
 * suivis sous la même clé Maven) — couverture de
 * listDistinctTargetUrlsByMavenKey() et du scope target_url de
 * findLatestByMavenKey()/listHistoryByMavenKey().
 */
class ZapScanRepositoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $em = null;

    private const MAVEN_KEY = 'fr.ma-moulinette:zap-scan-repo-test';

    protected function setUp(): void
    {
        self::bootKernel();
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $this->em = $em;
        $this->em->getConnection()->executeStatement(
            "DELETE FROM ma_moulinette.zap_scan WHERE maven_key = '" . self::MAVEN_KEY . "'"
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->em?->close();
        $this->em = null;
    }

    private function getRepository(): ZapScanRepository
    {
        return $this->em->getRepository(ZapScan::class);
    }

    private function makeScan(string $generatedAt, int $alertCountTotal, ?string $targetUrl = null): ZapScan
    {
        $scan = (new ZapScan())
            ->setMavenKey(self::MAVEN_KEY)
            ->setProjectVersion('1.0')
            ->setTargetUrl($targetUrl)
            ->setGeneratedAt(new \DateTimeImmutable($generatedAt))
            ->setAlertCountTotal($alertCountTotal);
        $this->em->persist($scan);
        $this->em->flush();

        return $scan;
    }

    public function testFindLatestByMavenKeyReturnsMostRecentByGeneratedAt(): void
    {
        $this->makeScan('2026-10-01 10:00:00', 3);
        $recent = $this->makeScan('2026-10-13 10:00:00', 7);

        $latest = $this->getRepository()->findLatestByMavenKey(self::MAVEN_KEY, null);

        $this->assertNotNull($latest);
        $this->assertSame($recent->getId(), $latest->getId());
    }

    public function testFindLatestByMavenKeyBreaksTieByIdWhenGeneratedAtIsIdentical(): void
    {
        // Même scénario que le découpage d'un rapport ZAP multi-site :
        // 2 scans, même generatedAt, ingérés l'un après l'autre.
        $this->makeScan('2026-10-26 06:13:34', 5);
        $secondIngested = $this->makeScan('2026-10-26 06:13:34', 10);

        $latest = $this->getRepository()->findLatestByMavenKey(self::MAVEN_KEY, null);

        $this->assertNotNull($latest);
        $this->assertSame($secondIngested->getId(), $latest->getId());
        $this->assertSame(10, $latest->getAlertCountTotal());
    }

    public function testListHistoryByMavenKeyOrdersTiesByIdDescTooWhenGeneratedAtIsIdentical(): void
    {
        $first = $this->makeScan('2026-10-26 06:13:34', 5);
        $second = $this->makeScan('2026-10-26 06:13:34', 10);

        $history = $this->getRepository()->listHistoryByMavenKey(self::MAVEN_KEY, null);

        $this->assertCount(2, $history);
        $this->assertSame($second->getId(), $history[0]->getId());
        $this->assertSame($first->getId(), $history[1]->getId());
    }

    // ──── listDistinctTargetUrlsByMavenKey / scope target_url ──────────────────

    public function testListDistinctTargetUrlsByMavenKeyReturnsEachTargetOnceMostRecentFirst(): void
    {
        $this->makeScan('2026-10-20 10:00:00', 3, 'https://metier.example/app');
        $this->makeScan('2026-10-26 06:13:34', 5, 'https://presentation.example/app');
        // 2e scan sur la même cible métier, plus récent que le premier mais
        // moins que présentation : ne doit pas créer de doublon dans la liste.
        $this->makeScan('2026-10-22 10:00:00', 4, 'https://metier.example/app');

        $targets = $this->getRepository()->listDistinctTargetUrlsByMavenKey(self::MAVEN_KEY);

        $this->assertSame(
            ['https://presentation.example/app', 'https://metier.example/app'],
            $targets
        );
    }

    public function testFindLatestByMavenKeyScopedByTargetUrlDoesNotMixTargets(): void
    {
        // Cas réel : même maven_key, 2 cibles DAST distinctes (ex. tier
        // présentation + tier métier) — le scan "le plus récent" d'une cible
        // ne doit jamais être celui de l'autre cible, même si celle-ci a un
        // generatedAt plus récent.
        $presentation = $this->makeScan('2026-10-01 10:00:00', 3, 'https://presentation.example/app');
        $this->makeScan('2026-10-26 06:13:34', 10, 'https://metier.example/app');

        $latestPresentation = $this->getRepository()->findLatestByMavenKey(
            self::MAVEN_KEY,
            'https://presentation.example/app'
        );

        $this->assertNotNull($latestPresentation);
        $this->assertSame($presentation->getId(), $latestPresentation->getId());
    }

    public function testListHistoryByMavenKeyScopedByTargetUrlDoesNotMixTargets(): void
    {
        $this->makeScan('2026-10-01 10:00:00', 3, 'https://presentation.example/app');
        $this->makeScan('2026-10-10 10:00:00', 4, 'https://presentation.example/app');
        $this->makeScan('2026-10-26 06:13:34', 10, 'https://metier.example/app');

        $history = $this->getRepository()->listHistoryByMavenKey(
            self::MAVEN_KEY,
            'https://presentation.example/app'
        );

        $this->assertCount(2, $history);
        foreach ($history as $scan) {
            $this->assertSame('https://presentation.example/app', $scan->getTargetUrl());
        }
    }
}
