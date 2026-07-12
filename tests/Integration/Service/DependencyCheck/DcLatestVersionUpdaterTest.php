<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

declare(strict_types=1);

namespace App\Tests\Integration\Service\DependencyCheck;

use App\Entity\DcScan;
use App\Service\DependencyCheck\DcLatestVersionUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * MODIF 2026-05-14 : tests Integration
 * de DcLatestVersionUpdater (maintien des flags is_latest_overall /
 * is_latest_release sur dc_scan).
 *
 * Couvre :
 *  - couple inexistant -> tous null, rien ne bouge
 *  - 1 scan release -> overall + release pointent dessus
 *  - 1 scan SNAPSHOT -> overall pointe dessus, release = null
 *  - 3 scans (mix release + dev) -> bons gagnants
 *  - tous SNAPSHOT -> release = null
 *  - isolation : recomputeFor(couple1) ne touche pas couple2
 *  - idempotence : 2 appels successifs -> meme resultat
 *  - reaffectation : changement d'etat initial puis recompute -> bons flags
 *  - recomputeAll : traite tous les couples distincts
 */
class DcLatestVersionUpdaterTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private DcLatestVersionUpdater $updater;
    private const DELETE_FROM = 'DELETE FROM ';
    private const VERSION_RELEASE = '1.0.0-RELEASE';
    private const VERSION_SNAPSHOT = '1.0.0-SNAPSHOT';


    protected function setUp(): void
    {
        self::bootKernel();
        $container     = static::getContainer();
        /** @var \Doctrine\ORM\EntityManagerInterface $em */
        $em = $container->get('doctrine')->getManager();
        $this->em      = $em;
        // Instanciation directe : le service est inline par le container tant
        // qu'aucun consumer ne l'injecte. On testera l'autowiring en bout de
        // chaine quand le hook ingestion (step 5) sera pose.
        $this->updater = new DcLatestVersionUpdater($this->em);

        // Purge via DQL (resolution du schema via mapping ORM, pas hardcode)
        $this->em->createQuery(self::DELETE_FROM . \App\Entity\DcFinding::class)->execute();
        $this->em->createQuery(self::DELETE_FROM . \App\Entity\DcDependency::class)->execute();
        $this->em->createQuery(self::DELETE_FROM . \App\Entity\DcCve::class)->execute();
        $this->em->createQuery(self::DELETE_FROM . DcScan::class)->execute();
        $this->em->createQuery(self::DELETE_FROM . \App\Entity\DcProcessingQueue::class)->execute();
        $this->em->clear();
    }

    protected function tearDown(): void
    {
        $this->em->close();
        parent::tearDown();
    }

    private function persistScan(string $group, string $artifact, string $version): DcScan
    {
        $scan = (new DcScan())
            ->setMavenKey($group . ':' . $artifact)
            ->setProjectGroup($group)
            ->setProjectArtifact($artifact)
            ->setProjectVersion($version)
            ->setScanDate(new \DateTimeImmutable())
            ->setDepCountTotal(0)
            ->setDepCountVulnerable(0)
            ->setCveCountCritical(0)
            ->setCveCountHigh(0)
            ->setCveCountMedium(0)
            ->setCveCountLow(0)
            ->setCveCountInfo(0)
            ->setCveCountTotal(0);
        $this->em->persist($scan);
        return $scan;
    }

    /** Refetch a scan to get its flags from the DB (bypass identity map staleness). */
    private function reload(int|string $id): DcScan
    {
        $this->em->clear();
        $scan = $this->em->getRepository(DcScan::class)->find($id);
        $this->assertNotNull($scan, "Scan #{$id} introuvable apres reload");
        return $scan;
    }

    /* ============ couple vide ============ */

    public function testRecomputeForReturnsNullsOnUnknownCouple(): void
    {
        $result = $this->updater->recomputeFor('fr.ma-moulinette', 'projet-inconnu');
        $this->assertSame(['overall_id' => null, 'release_id' => null], $result);
    }

    /* ============ 1 scan ============ */

    public function testSingleReleaseScanIsBothOverallAndRelease(): void
    {
        $s = $this->persistScan('fr.ma-moulinette', 'monapp', self::VERSION_RELEASE);
        $this->em->flush();

        $result = $this->updater->recomputeFor('fr.ma-moulinette', 'monapp');

        $this->assertSame((int) $s->getId(), $result['overall_id']);
        $this->assertSame((int) $s->getId(), $result['release_id']);

        $reloaded = $this->reload($s->getId());
        $this->assertTrue($reloaded->isLatestOverall());
        $this->assertTrue($reloaded->isLatestRelease());
    }

    public function testSingleSnapshotScanIsOverallButNotRelease(): void
    {
        $s = $this->persistScan('fr.ma-moulinette', 'monapp', self::VERSION_SNAPSHOT);
        $this->em->flush();

        $result = $this->updater->recomputeFor('fr.ma-moulinette', 'monapp');

        $this->assertSame((int) $s->getId(), $result['overall_id']);
        $this->assertNull($result['release_id']);

        $reloaded = $this->reload($s->getId());
        $this->assertTrue($reloaded->isLatestOverall());
        $this->assertFalse($reloaded->isLatestRelease());
    }

    /* ============ 3 scans mix release + dev ============ */

    public function testMixedScansPickCorrectWinners(): void
    {
        // Scenario realiste : on a 4.2.0-RELEASE deja en prod, 4.2.1-SNAPSHOT en cours
        $sRelease   = $this->persistScan('fr.ma-moulinette', 'monapp', '4.2.0-RELEASE');
        $sSnapshot  = $this->persistScan('fr.ma-moulinette', 'monapp', '4.2.1-SNAPSHOT');
        $sOldRel    = $this->persistScan('fr.ma-moulinette', 'monapp', '4.1.0-RELEASE');
        $this->em->flush();

        $result = $this->updater->recomputeFor('fr.ma-moulinette', 'monapp');

        // overall = max semver -> 4.2.1-SNAPSHOT
        $this->assertSame((int) $sSnapshot->getId(), $result['overall_id']);
        // release = max parmi release -> 4.2.0-RELEASE
        $this->assertSame((int) $sRelease->getId(),  $result['release_id']);

        $this->em->clear();
        $repo = $this->em->getRepository(DcScan::class);
        $rel   = $repo->find($sRelease->getId());
        $snap  = $repo->find($sSnapshot->getId());
        $oldR  = $repo->find($sOldRel->getId());

        $this->assertTrue($snap->isLatestOverall(),  '4.2.1-SNAPSHOT doit etre latest_overall');
        $this->assertFalse($rel->isLatestOverall(),  '4.2.0-RELEASE ne doit pas etre latest_overall');
        $this->assertFalse($oldR->isLatestOverall(), '4.1.0-RELEASE ne doit pas etre latest_overall');

        $this->assertTrue($rel->isLatestRelease(),   '4.2.0-RELEASE doit etre latest_release');
        $this->assertFalse($snap->isLatestRelease(), 'Snapshot ne doit jamais etre latest_release');
        $this->assertFalse($oldR->isLatestRelease(), '4.1.0-RELEASE ne doit pas etre latest_release (4.2.0 > 4.1.0)');
    }

    /* ============ tous SNAPSHOT ============ */

    public function testAllSnapshotScansHaveNoLatestRelease(): void
    {
        $s1 = $this->persistScan('fr.ma-moulinette', 'devapp', self::VERSION_SNAPSHOT);
        $s2 = $this->persistScan('fr.ma-moulinette', 'devapp', '1.0.1-SNAPSHOT');
        $s3 = $this->persistScan('fr.ma-moulinette', 'devapp', '1.1.0-SNAPSHOT');
        $this->em->flush();

        $result = $this->updater->recomputeFor('fr.ma-moulinette', 'devapp');

        $this->assertSame((int) $s3->getId(), $result['overall_id']);
        $this->assertNull($result['release_id']);

        $reloaded = $this->reload($s3->getId());
        $this->assertTrue($reloaded->isLatestOverall());
        $this->assertFalse($reloaded->isLatestRelease());
    }

    /* ============ isolation entre couples ============ */

    public function testRecomputeDoesNotAffectOtherCouples(): void
    {
        // Couple A : monapp
        $sA = $this->persistScan('fr.ma-moulinette', 'monapp', '2.0.0-RELEASE');
        // Couple B : autreapp, pre-flagge artificiellement TRUE pour verifier qu'on n'y touche pas
        $sB = $this->persistScan('fr.ma-moulinette', 'autreapp', '3.0.0-RELEASE');
        $sB->setIsLatestOverall(true)->setIsLatestRelease(true);
        $this->em->flush();

        $this->updater->recomputeFor('fr.ma-moulinette', 'monapp');

        // Verifie que sB n'a PAS ete touche
        $reloadedB = $this->reload($sB->getId());
        $this->assertTrue($reloadedB->isLatestOverall(),  'Couple B doit conserver son flag overall');
        $this->assertTrue($reloadedB->isLatestRelease(),  'Couple B doit conserver son flag release');

        // Et sA a bien ete maj
        $reloadedA = $this->reload($sA->getId());
        $this->assertTrue($reloadedA->isLatestOverall());
        $this->assertTrue($reloadedA->isLatestRelease());
    }

    /* ============ idempotence ============ */

    public function testRecomputeIsIdempotent(): void
    {
        $sRel   = $this->persistScan('fr.ma-moulinette', 'monapp', '1.2.0-RELEASE');
        $sSnap  = $this->persistScan('fr.ma-moulinette', 'monapp', '1.3.0-SNAPSHOT');
        $this->em->flush();

        $r1 = $this->updater->recomputeFor('fr.ma-moulinette', 'monapp');
        $r2 = $this->updater->recomputeFor('fr.ma-moulinette', 'monapp');
        $r3 = $this->updater->recomputeFor('fr.ma-moulinette', 'monapp');

        $this->assertSame($r1, $r2);
        $this->assertSame($r2, $r3);
        $this->assertSame((int) $sSnap->getId(), $r1['overall_id']);
        $this->assertSame((int) $sRel->getId(),  $r1['release_id']);
    }

    /* ============ reaffectation apres nouveau scan ============ */

    public function testReassignsFlagsWhenNewerScanArrives(): void
    {
        $sOld = $this->persistScan('fr.ma-moulinette', 'monapp', self::VERSION_RELEASE);
        $this->em->flush();
        $this->updater->recomputeFor('fr.ma-moulinette', 'monapp');

        // Premier passage : 1.0.0 est latest_overall + latest_release
        $reloaded = $this->reload($sOld->getId());
        $this->assertTrue($reloaded->isLatestOverall());
        $this->assertTrue($reloaded->isLatestRelease());

        // Arrivee d'une nouvelle version
        $sNew = $this->persistScan('fr.ma-moulinette', 'monapp', '2.0.0-RELEASE');
        $this->em->flush();
        $this->updater->recomputeFor('fr.ma-moulinette', 'monapp');

        // L'ancien scan ne doit plus etre flagge
        $oldReloaded = $this->reload($sOld->getId());
        $this->assertFalse($oldReloaded->isLatestOverall(),  '1.0.0 ne doit plus etre latest_overall');
        $this->assertFalse($oldReloaded->isLatestRelease(),  '1.0.0 ne doit plus etre latest_release');

        $newReloaded = $this->reload($sNew->getId());
        $this->assertTrue($newReloaded->isLatestOverall());
        $this->assertTrue($newReloaded->isLatestRelease());
    }

    /* ============ recomputeAll ============ */

    public function testRecomputeAllProcessesAllDistinctCouples(): void
    {
        // 3 couples distincts, 5 scans au total
        $sA1 = $this->persistScan('fr.ma-moulinette', 'appA', '1.0.0-RELEASE');
        $sA2 = $this->persistScan('fr.ma-moulinette', 'appA', '1.1.0-RELEASE');
        $sB1 = $this->persistScan('fr.ma-moulinette', 'appB', '2.0.0-SNAPSHOT');
        $sC1 = $this->persistScan('fr.ma-moulinette', 'appC', '3.0.0-RELEASE');
        $sC2 = $this->persistScan('fr.ma-moulinette', 'appC', '3.0.1-RELEASE');
        $this->em->flush();

        $nb = $this->updater->recomputeAll();
        $this->assertSame(3, $nb, '3 couples distincts attendus');

        $this->em->clear();
        $repo = $this->em->getRepository(DcScan::class);

        // Couple A : 1.1.0 gagne sur overall + release
        $this->assertFalse($repo->find($sA1->getId())->isLatestOverall());
        $this->assertTrue($repo->find($sA2->getId())->isLatestOverall());
        $this->assertTrue($repo->find($sA2->getId())->isLatestRelease());

        // Couple B : 2.0.0-SNAPSHOT seul -> overall=true, release=null
        $this->assertTrue($repo->find($sB1->getId())->isLatestOverall());
        $this->assertFalse($repo->find($sB1->getId())->isLatestRelease());

        // Couple C : 3.0.1 gagne
        $this->assertFalse($repo->find($sC1->getId())->isLatestOverall());
        $this->assertTrue($repo->find($sC2->getId())->isLatestOverall());
        $this->assertTrue($repo->find($sC2->getId())->isLatestRelease());
    }
}
