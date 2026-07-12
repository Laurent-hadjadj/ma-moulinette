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

namespace App\Tests\Integration\Repository;

use App\Entity\DcScan;
use App\Repository\DcScanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * MODIF 2026-05-13 : test intégration
 * pour findArchetypeScansBatch. Couvre le path SQL réel (OR composite avec
 * QueryBuilder + 2 buckets simple/groupé + dedup), que les tests Unit ne
 * peuvent pas valider (mock fragile sur createQueryBuilder).
 *
 * Scénarios :
 *  - input vide / paires invalides -> map vide
 *  - 1 label simple existant -> map de 1
 *  - 1 label groupé (group:artifact) existant -> map de 1
 *  - mix simple + groupé en 1 appel -> map de 2
 *  - paire orpheline (pas de scan correspondant) -> absente de la map
 *  - paires dupliquées en input -> 1 seule entrée dans la map
 *  - 2 versions différentes du même artifact -> 2 entrées distinctes
 */
class DcScanRepositoryFindArchetypeScansBatchTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private DcScanRepository $repo;
    private const DELETE_QUERY = 'DELETE FROM ';
    private const VERSION_RELEASE = '4.2.0-RELEASE';
    private const SPRING_BOOT_FAM_CONFIG = 'springboot-config@4.2.0-RELEASE';

    protected function setUp(): void
    {
        self::bootKernel();
        $container   = static::getContainer();
        /** @var \Doctrine\ORM\EntityManagerInterface $em */
        $em = $container->get('doctrine')->getManager();
        $this->em    = $em;
        $this->repo  = $this->em->getRepository(DcScan::class);

        // Purge via DQL : l'ORM résout le schéma de mapping correctement,
        // pas besoin de hardcoder "ma_moulinette." (qui foire si la BDD test
        // utilise un autre schéma). Suit l'ordre des FK : findings -> deps/cves
        // -> scans -> queue.
        $this->em->createQuery(self::DELETE_QUERY . \App\Entity\DcFinding::class)->execute();
        $this->em->createQuery(self::DELETE_QUERY . \App\Entity\DcDependency::class)->execute();
        $this->em->createQuery(self::DELETE_QUERY . \App\Entity\DcCve::class)->execute();
        $this->em->createQuery(self::DELETE_QUERY . DcScan::class)->execute();
        $this->em->createQuery(self::DELETE_QUERY . \App\Entity\DcProcessingQueue::class)->execute();
        $this->em->clear();

        // 4 scans variés : label simple, label groupé, 2 versions d'un même artifact.
        $this->persistScan(
            mavenKey: 'springboot-config:springboot-config',
            group: 'springboot-config',
            artifact: 'springboot-config',
            version: self::VERSION_RELEASE
        );
        $this->persistScan(
            mavenKey: 'springboot-config:springboot-config',
            group: 'springboot-config',
            artifact: 'springboot-config',
            version: '4.1.0-RC1'
        );
        $this->persistScan(
            mavenKey: 'fr.ma-moulinette:projet-config',
            group: 'fr.ma-moulinette',
            artifact: 'projet-config',
            version: self::VERSION_RELEASE
        );
        $this->persistScan(
            mavenKey: 'fr.ma-moulinette:projet-parent',
            group: 'fr.ma-moulinette',
            artifact: 'projet-parent',
            version: '3.0.0-RELEASE'
        );

        $this->em->flush();
    }

    protected function tearDown(): void
    {
        $this->em->close();
        parent::tearDown();
    }

    private function persistScan(
        string $mavenKey,
        string $group,
        string $artifact,
        string $version,
    ): void {
        $scan = (new DcScan())
            ->setMavenKey($mavenKey)
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
    }

    public function testReturnsEmptyMapOnEmptyInput(): void
    {
        $this->assertSame([], $this->repo->findArchetypeScansBatch([]));
    }

    public function testReturnsEmptyMapWhenAllPairsInvalid(): void
    {
        $result = $this->repo->findArchetypeScansBatch([
            ['label' => '', 'version' => '1.0.0'],
            ['label' => 'foo', 'version' => ''],
            ['label' => null, 'version' => '1.0.0'],
        ]);
        $this->assertSame([], $result);
    }

    public function testFindsSimpleLabelScan(): void
    {
        $result = $this->repo->findArchetypeScansBatch([
            ['label' => 'springboot-config', 'version' => self::VERSION_RELEASE],
        ]);

        $this->assertCount(1, $result);
        $this->assertArrayHasKey(self::SPRING_BOOT_FAM_CONFIG, $result);
        $scan = $result[self::SPRING_BOOT_FAM_CONFIG];
        $this->assertInstanceOf(DcScan::class, $scan);
        $this->assertSame('springboot-config', $scan->getProjectArtifact());
        $this->assertSame(self::VERSION_RELEASE,         $scan->getProjectVersion());
    }

    public function testFindsGroupedLabelScan(): void
    {
        $result = $this->repo->findArchetypeScansBatch([
            ['label' => 'fr.ma-moulinette:projet-config', 'version' => '4.2.0-RELEASE'],
        ]);

        $this->assertCount(1, $result);
        $this->assertArrayHasKey('fr.ma-moulinette:projet-config@4.2.0-RELEASE', $result);
        $scan = $result['fr.ma-moulinette:projet-config@4.2.0-RELEASE'];
        $this->assertSame('fr.ma-moulinette',      $scan->getProjectGroup());
        $this->assertSame('projet-config', $scan->getProjectArtifact());
    }

    public function testHandlesMixedSimpleAndGroupedInOneCall(): void
    {
        $result = $this->repo->findArchetypeScansBatch([
            ['label' => 'springboot-config', 'version' => self::VERSION_RELEASE],
            ['label' => 'fr.ma-moulinette:projet-config',    'version' => '4.2.0-RELEASE'],
            ['label' => 'fr.ma-moulinette:projet-parent', 'version' => '3.0.0-RELEASE'],
        ]);

        $this->assertCount(3, $result);
        $this->assertArrayHasKey(self::SPRING_BOOT_FAM_CONFIG,    $result);
        $this->assertArrayHasKey('fr.ma-moulinette:projet-config@4.2.0-RELEASE',      $result);
        $this->assertArrayHasKey('fr.ma-moulinette:projet-parent@3.0.0-RELEASE',  $result);
    }

    public function testOrphanPairsAreAbsentFromMap(): void
    {
        $result = $this->repo->findArchetypeScansBatch([
            ['label' => 'springboot-config', 'version' => self::VERSION_RELEASE],   // existe
            ['label' => 'unknown-socle',         'version' => '1.0.0-RELEASE'],    // orphelin
            ['label' => 'fr.ma-moulinette:projet-inconnu',        'version' => '2.0.0-RELEASE'],    // orphelin groupé
        ]);

        $this->assertCount(1, $result);
        $this->assertArrayHasKey(self::SPRING_BOOT_FAM_CONFIG, $result);
        $this->assertArrayNotHasKey('unknown-socle@1.0.0-RELEASE',      $result);
        $this->assertArrayNotHasKey('fr.ma-moulinette:projet-inconnu@2.0.0-RELEASE',     $result);
    }

    public function testDedupsDuplicatePairsInInput(): void
    {
        $result = $this->repo->findArchetypeScansBatch([
            ['label' => 'springboot-config', 'version' => self::VERSION_RELEASE],
            ['label' => 'springboot-config', 'version' => self::VERSION_RELEASE], // doublon strict
            ['label' => 'springboot-config', 'version' => self::VERSION_RELEASE], // doublon strict
        ]);

        $this->assertCount(1, $result);
        $this->assertArrayHasKey(self::SPRING_BOOT_FAM_CONFIG, $result);
    }

    public function testReturnsTwoDistinctEntriesForTwoVersionsOfSameArtifact(): void
    {
        $result = $this->repo->findArchetypeScansBatch([
            ['label' => 'springboot-config', 'version' => self::VERSION_RELEASE],
            ['label' => 'springboot-config', 'version' => '4.1.0-RC1'],
        ]);

        $this->assertCount(2, $result);
        $this->assertArrayHasKey(self::SPRING_BOOT_FAM_CONFIG, $result);
        $this->assertArrayHasKey('springboot-config@4.1.0-RC1',     $result);
        $this->assertNotSame(
            $result[self::SPRING_BOOT_FAM_CONFIG]->getProjectVersion(),
            $result['springboot-config@4.1.0-RC1']->getProjectVersion()
        );
    }
}
