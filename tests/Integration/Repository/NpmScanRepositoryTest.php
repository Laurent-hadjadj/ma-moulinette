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

namespace App\Tests\Integration\Repository;

use App\Entity\NpmScan;
use App\Repository\NpmScanRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class NpmScanRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private NpmScanRepository $repo;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->repo = $this->em->getRepository(NpmScan::class);

        $this->em->getConnection()->executeStatement('DELETE FROM ma_moulinette.npm_scan');
    }

    private function makeScan(string $group, string $artifact, string $version, \DateTimeImmutable $scanDate): NpmScan
    {
        $scan = (new NpmScan())
            ->setProjectGroup($group)
            ->setProjectArtifact($artifact)
            ->setProjectVersion($version)
            ->setScanDate($scanDate);
        $this->em->persist($scan);
        return $scan;
    }

    public function testFindByProjectVersionDateReturnsNullWhenNotFound(): void
    {
        $this->assertNull($this->repo->findByProjectVersionDate('fr.ma-moulinette', 'angular-demo', '1.0.0', new \DateTimeImmutable()));
    }

    public function testFindByProjectVersionDateReturnsExactMatch(): void
    {
        $scanDate = new \DateTimeImmutable('2026-11-20 10:00:00');
        $scan = $this->makeScan('fr.ma-moulinette', 'angular-demo', '1.0.0', $scanDate);
        $this->em->flush();

        $found = $this->repo->findByProjectVersionDate('fr.ma-moulinette', 'angular-demo', '1.0.0', $scanDate);

        $this->assertNotNull($found);
        $this->assertSame($scan->getId(), $found->getId());
    }

    public function testFindLatestForProjectReturnsMostRecentScanDate(): void
    {
        $this->makeScan('fr.ma-moulinette', 'angular-demo', '1.0.0', new \DateTimeImmutable('-2 days'));
        $recent = $this->makeScan('fr.ma-moulinette', 'angular-demo', '2.0.0', new \DateTimeImmutable('-1 day'));
        $this->em->flush();

        $found = $this->repo->findLatestForProject('fr.ma-moulinette', 'angular-demo', '2.0.0');

        $this->assertNotNull($found);
        $this->assertSame($recent->getId(), $found->getId());
    }

    public function testFindLatestForProjectReturnsNullWhenNoneMatch(): void
    {
        $this->assertNull($this->repo->findLatestForProject('fr.ma-moulinette', 'inconnu', '1.0.0'));
    }

    public function testListLatestPerProjectReturnsOneRowPerProject(): void
    {
        $this->makeScan('fr.ma-moulinette', 'angular-demo', '1.0.0', new \DateTimeImmutable('-2 days'));
        $this->makeScan('fr.ma-moulinette', 'angular-demo', '2.0.0', new \DateTimeImmutable('-1 day'));
        $this->makeScan('fr.ma-moulinette', 'autre-app', '1.0.0', new \DateTimeImmutable('-1 day'));
        $this->em->flush();

        $rows = $this->repo->listLatestPerProject();

        $this->assertCount(2, $rows);
        $byArtifact = [];
        foreach ($rows as $row) {
            $byArtifact[$row['project_artifact']] = $row['project_version'];
        }
        $this->assertSame('2.0.0', $byArtifact['angular-demo'], 'Doit garder la version la plus récente');
        $this->assertSame('1.0.0', $byArtifact['autre-app']);
    }

    public function testListHistoryByProjectOrdersByScanDateDescAcrossVersions(): void
    {
        $this->makeScan('fr.ma-moulinette', 'angular-demo', '1.0.0', new \DateTimeImmutable('-3 days'));
        $this->makeScan('fr.ma-moulinette', 'angular-demo', '2.0.0', new \DateTimeImmutable('-1 day'));
        $this->makeScan('fr.ma-moulinette', 'angular-demo', '2.0.0', new \DateTimeImmutable('-2 days'));
        // Autre projet : ne doit jamais apparaître dans l'historique ci-dessous.
        $this->makeScan('fr.ma-moulinette', 'autre-app', '1.0.0', new \DateTimeImmutable());
        $this->em->flush();

        $history = $this->repo->listHistoryByProject('fr.ma-moulinette', 'angular-demo');

        $this->assertCount(3, $history);
        $this->assertSame('2.0.0', $history[0]->getProjectVersion(), 'Le plus récent en premier');
        $this->assertSame('2.0.0', $history[1]->getProjectVersion());
        $this->assertSame('1.0.0', $history[2]->getProjectVersion(), 'Le plus ancien en dernier');
        foreach ($history as $scan) {
            $this->assertSame('angular-demo', $scan->getProjectArtifact());
        }
    }

    public function testListHistoryByProjectRespectsLimit(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->makeScan('fr.ma-moulinette', 'angular-demo', "1.0.{$i}", new \DateTimeImmutable("-{$i} days"));
        }
        $this->em->flush();

        $history = $this->repo->listHistoryByProject('fr.ma-moulinette', 'angular-demo', 2);

        $this->assertCount(2, $history);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $entityManager->close();
    }
}
