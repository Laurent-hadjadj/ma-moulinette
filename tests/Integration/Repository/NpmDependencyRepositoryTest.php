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

use App\Entity\{NpmDependency, NpmFinding, NpmScan, NpmVulnerability};
use App\Repository\NpmDependencyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class NpmDependencyRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private NpmDependencyRepository $repo;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->repo = $this->em->getRepository(NpmDependency::class);

        $this->em->getConnection()->executeStatement('DELETE FROM ma_moulinette.npm_finding');
        $this->em->getConnection()->executeStatement('DELETE FROM ma_moulinette.npm_scan');
        $this->em->getConnection()->executeStatement('DELETE FROM ma_moulinette.npm_dependency');
        $this->em->getConnection()->executeStatement('DELETE FROM ma_moulinette.npm_vulnerability');
    }

    public function testFindByNameReturnsNullWhenNotFound(): void
    {
        $this->assertNull($this->repo->findByName('inexistant'));
    }

    public function testFindByNameReturnsEntity(): void
    {
        $dep = (new NpmDependency())->setName('vite');
        $this->em->persist($dep);
        $this->em->flush();

        $found = $this->repo->findByName('vite');

        $this->assertNotNull($found);
        $this->assertSame($dep->getId(), $found->getId());
    }

    public function testTopVulnerableDependenciesOrdersByFindingCountDescending(): void
    {
        $scan = (new NpmScan())
            ->setProjectGroup('fr.ma-moulinette')->setProjectArtifact('angular-demo')->setProjectVersion('1.0.0')
            ->setScanDate(new \DateTimeImmutable());
        $this->em->persist($scan);

        $depFrequent = (new NpmDependency())->setName('undici');
        $depRare = (new NpmDependency())->setName('qs');
        $this->em->persist($depFrequent);
        $this->em->persist($depRare);
        $this->em->flush();

        // 2 findings pour undici, 1 pour qs
        foreach (['GHSA-AAAA-0000-0000', 'GHSA-BBBB-0000-0000'] as $advisoryId) {
            $vuln = (new NpmVulnerability())->setAdvisoryId($advisoryId)->setSeverity('high');
            $this->em->persist($vuln);
            $this->em->flush();
            $finding = (new NpmFinding())
                ->setScan($scan)->setDependency($depFrequent)->setVulnerability($vuln)
                ->setSeverityAtScan('high');
            $this->em->persist($finding);
        }
        $vulnRare = (new NpmVulnerability())->setAdvisoryId('GHSA-CCCC-0000-0000')->setSeverity('moderate');
        $this->em->persist($vulnRare);
        $this->em->flush();
        $findingRare = (new NpmFinding())
            ->setScan($scan)->setDependency($depRare)->setVulnerability($vulnRare)
            ->setSeverityAtScan('moderate');
        $this->em->persist($findingRare);
        $this->em->flush();

        $top = $this->repo->topVulnerableDependencies(10);

        $this->assertSame('undici', $top[0]['name']);
        $this->assertSame(2, (int) $top[0]['finding_count']);
        $this->assertSame('qs', $top[1]['name']);
        $this->assertSame(1, (int) $top[1]['finding_count']);
    }

    public function testListProjectImpactCountsDistinctProjectsOnLatestScanOnly(): void
    {
        // Projet A : 2 scans successifs, "vite" en low puis high (la bonne
        // valeur attendue est celle du scan le PLUS RÉCENT, pas une moyenne
        // ni le pire des deux scans confondus).
        $scanAOld = (new NpmScan())
            ->setProjectGroup('fr.ma-moulinette')->setProjectArtifact('proj-a')->setProjectVersion('1.0.0')
            ->setScanDate(new \DateTimeImmutable('-2 days'));
        $scanANew = (new NpmScan())
            ->setProjectGroup('fr.ma-moulinette')->setProjectArtifact('proj-a')->setProjectVersion('2.0.0')
            ->setScanDate(new \DateTimeImmutable('-1 day'));
        $scanB = (new NpmScan())
            ->setProjectGroup('fr.ma-moulinette')->setProjectArtifact('proj-b')->setProjectVersion('1.0.0')
            ->setScanDate(new \DateTimeImmutable());
        $this->em->persist($scanAOld);
        $this->em->persist($scanANew);
        $this->em->persist($scanB);

        $vite = (new NpmDependency())->setName('vite');
        $qs = (new NpmDependency())->setName('qs');
        $this->em->persist($vite);
        $this->em->persist($qs);
        $this->em->flush();

        $vulnLow = (new NpmVulnerability())->setAdvisoryId('GHSA-AAAA-0000-0000')->setSeverity('low');
        $vulnHigh = (new NpmVulnerability())->setAdvisoryId('GHSA-BBBB-0000-0000')->setSeverity('high');
        $vulnCritical = (new NpmVulnerability())->setAdvisoryId('GHSA-CCCC-0000-0000')->setSeverity('critical');
        $vulnModerate = (new NpmVulnerability())->setAdvisoryId('GHSA-DDDD-0000-0000')->setSeverity('moderate');
        $this->em->persist($vulnLow);
        $this->em->persist($vulnHigh);
        $this->em->persist($vulnCritical);
        $this->em->persist($vulnModerate);
        $this->em->flush();

        // Scan ancien de proj-a (vite/low) — ne doit PAS être compté.
        $this->em->persist((new NpmFinding())
            ->setScan($scanAOld)->setDependency($vite)->setVulnerability($vulnLow)->setSeverityAtScan('low'));
        // Scan récent de proj-a (vite/high) — celui qui compte.
        $this->em->persist((new NpmFinding())
            ->setScan($scanANew)->setDependency($vite)->setVulnerability($vulnHigh)->setSeverityAtScan('high'));
        // proj-b : vite/critical + qs/moderate (qs unique à proj-b).
        $this->em->persist((new NpmFinding())
            ->setScan($scanB)->setDependency($vite)->setVulnerability($vulnCritical)->setSeverityAtScan('critical'));
        $this->em->persist((new NpmFinding())
            ->setScan($scanB)->setDependency($qs)->setVulnerability($vulnModerate)->setSeverityAtScan('moderate'));
        $this->em->flush();

        $result = $this->repo->listProjectImpact(200);

        $this->assertSame(200, $result['code']);
        $byName = array_column($result['liste'], null, 'name');

        $this->assertSame(2, (int) $byName['vite']['nb_projects'], 'proj-a (1x via le scan le plus récent) + proj-b = 2, pas 3');
        $this->assertSame(2, (int) $byName['vite']['nb_findings'], 'le finding "low" du scan ancien de proj-a ne doit pas être compté');
        $this->assertSame(5, (int) $byName['vite']['worst_severity_rank'], 'critical=5 : le pire constaté sur les scans retenus (high du scan récent + critical de proj-b)');

        $this->assertSame(1, (int) $byName['qs']['nb_projects']);
        $this->assertSame(3, (int) $byName['qs']['worst_severity_rank'], 'moderate=3');

        // Ordre : vite (2 projets) avant qs (1 projet).
        $this->assertSame('vite', $result['liste'][0]['name']);
        $this->assertSame('qs', $result['liste'][1]['name']);
    }

    public function testListProjectImpactRespectsLimit(): void
    {
        $scan = (new NpmScan())
            ->setProjectGroup('fr.ma-moulinette')->setProjectArtifact('proj-a')->setProjectVersion('1.0.0')
            ->setScanDate(new \DateTimeImmutable());
        $this->em->persist($scan);

        for ($i = 1; $i <= 3; $i++) {
            $dep = (new NpmDependency())->setName("pkg-{$i}");
            $vuln = (new NpmVulnerability())->setAdvisoryId("GHSA-{$i}AAA-0000-0000")->setSeverity('low');
            $this->em->persist($dep);
            $this->em->persist($vuln);
            $this->em->flush();
            $this->em->persist((new NpmFinding())
                ->setScan($scan)->setDependency($dep)->setVulnerability($vuln)->setSeverityAtScan('low'));
        }
        $this->em->flush();

        $result = $this->repo->listProjectImpact(2);

        $this->assertSame(200, $result['code']);
        $this->assertCount(2, $result['liste']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $entityManager->close();
    }
}
