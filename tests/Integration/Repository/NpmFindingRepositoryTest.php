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
use App\Repository\NpmFindingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Couvre listForScan() et markIsProdForScan() contre une vraie base
 * PostgreSQL — markIsProdForScan() a déjà corrigé un vrai bug SQL
 * (ANY() invalide avec ArrayParameterType::STRING, remplacé par IN()),
 * formalisé ici en test automatisé après vérification manuelle.
 */
class NpmFindingRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private NpmFindingRepository $repo;
    private NpmScan $scan;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->repo = $this->em->getRepository(NpmFinding::class);

        $this->em->getConnection()->executeStatement('DELETE FROM ma_moulinette.npm_finding');
        $this->em->getConnection()->executeStatement('DELETE FROM ma_moulinette.npm_scan');
        $this->em->getConnection()->executeStatement('DELETE FROM ma_moulinette.npm_dependency');
        $this->em->getConnection()->executeStatement('DELETE FROM ma_moulinette.npm_vulnerability');

        $this->scan = (new NpmScan())
            ->setProjectGroup('fr.ma-moulinette')->setProjectArtifact('angular-demo')->setProjectVersion('1.0.0')
            ->setScanDate(new \DateTimeImmutable());
        $this->em->persist($this->scan);
        $this->em->flush();
    }

    private function addFinding(string $depName, string $advisoryId, string $severity): NpmFinding
    {
        $dep = $this->em->getRepository(NpmDependency::class)->findOneBy(['name' => $depName])
            ?? (new NpmDependency())->setName($depName);
        $vuln = (new NpmVulnerability())->setAdvisoryId($advisoryId)->setSeverity($severity);
        $this->em->persist($dep);
        $this->em->persist($vuln);
        $this->em->flush();

        $finding = (new NpmFinding())
            ->setScan($this->scan)->setDependency($dep)->setVulnerability($vuln)
            ->setSeverityAtScan($severity);
        $this->em->persist($finding);
        $this->em->flush();
        return $finding;
    }

    public function testListForScanOrdersBySeverityCriticalFirst(): void
    {
        $this->addFinding('qs', 'GHSA-LOW0-0000-0000', 'low');
        $this->addFinding('vite', 'GHSA-CRIT-0000-0000', 'critical');
        $this->addFinding('undici', 'GHSA-HIGH-0000-0000', 'high');

        $result = $this->repo->listForScan((int) $this->scan->getId());

        $this->assertSame(200, $result['code']);
        $this->assertCount(3, $result['liste']);
        $this->assertSame('critical', $result['liste'][0]['severity']);
        $this->assertSame('high', $result['liste'][1]['severity']);
        $this->assertSame('low', $result['liste'][2]['severity']);
    }

    public function testListForScanReturnsEmptyListForUnknownScan(): void
    {
        $result = $this->repo->listForScan(999999);

        $this->assertSame(200, $result['code']);
        $this->assertSame([], $result['liste']);
    }

    public function testMarkIsProdForScanWithNamesSetsTrueFalseCorrectly(): void
    {
        $this->addFinding('vite', 'GHSA-AAAA-0000-0000', 'high');
        $this->addFinding('undici', 'GHSA-BBBB-0000-0000', 'high');
        $this->addFinding('body-parser', 'GHSA-CCCC-0000-0000', 'moderate');

        $this->repo->markIsProdForScan((int) $this->scan->getId(), ['vite', 'undici']);

        $rows = $this->em->getConnection()->executeQuery(
            'SELECT d.name, f.is_prod FROM ma_moulinette.npm_finding f
             JOIN ma_moulinette.npm_dependency d ON f.dependency_id = d.id
             WHERE f.scan_id = :scan',
            ['scan' => $this->scan->getId()]
        )->fetchAllAssociative();

        $byName = array_column($rows, 'is_prod', 'name');
        $this->assertTrue((bool) $byName['vite']);
        $this->assertTrue((bool) $byName['undici']);
        $this->assertFalse((bool) $byName['body-parser']);
    }

    public function testMarkIsProdForScanWithEmptyListSetsAllFalse(): void
    {
        $this->addFinding('vite', 'GHSA-DDDD-0000-0000', 'high');

        $this->repo->markIsProdForScan((int) $this->scan->getId(), []);

        $isProd = $this->em->getConnection()->executeQuery(
            'SELECT is_prod FROM ma_moulinette.npm_finding WHERE scan_id = :scan',
            ['scan' => $this->scan->getId()]
        )->fetchOne();

        $this->assertFalse((bool) $isProd);
    }

    public function testCountByOriginAndSeverityGroupsCorrectly(): void
    {
        $this->addFinding('vite', 'GHSA-AAAA-0000-0000', 'high')->setIsProd(true);
        $this->addFinding('undici', 'GHSA-BBBB-0000-0000', 'high')->setIsProd(true);
        $this->addFinding('body-parser', 'GHSA-CCCC-0000-0000', 'moderate')->setIsProd(false);
        $this->addFinding('qs', 'GHSA-DDDD-0000-0000', 'low'); // isProd reste null (indéterminé)
        $this->em->flush();

        $result = $this->repo->countByOriginAndSeverity((int) $this->scan->getId());

        $this->assertSame(200, $result['code']);
        $byKey = [];
        foreach ($result['liste'] as $row) {
            $byKey[$row['group_key']][$row['severity']] = (int) $row['cnt'];
        }
        $this->assertSame(2, $byKey['prod']['high']);
        $this->assertSame(1, $byKey['dev']['moderate']);
        $this->assertSame(1, $byKey['unknown']['low']);
    }

    public function testCountByDirectAndSeverityGroupsCorrectly(): void
    {
        $this->addFinding('vite', 'GHSA-EEEE-0000-0000', 'high')->setIsDirect(true);
        $this->addFinding('undici', 'GHSA-FFFF-0000-0000', 'critical')->setIsDirect(false);
        $this->addFinding('body-parser', 'GHSA-GGGG-0000-0000', 'high')->setIsDirect(false);
        $this->em->flush();

        $result = $this->repo->countByDirectAndSeverity((int) $this->scan->getId());

        $this->assertSame(200, $result['code']);
        $byKey = [];
        foreach ($result['liste'] as $row) {
            $byKey[$row['group_key']][$row['severity']] = (int) $row['cnt'];
        }
        $this->assertSame(1, $byKey['direct']['high']);
        $this->assertSame(1, $byKey['transitive']['critical']);
        $this->assertSame(1, $byKey['transitive']['high']);
    }

    public function testTopVulnerableDependenciesForScanOrdersByFindingCountDesc(): void
    {
        $this->addFinding('vite', 'GHSA-HHHH-0000-0000', 'high');
        $this->addFinding('vite', 'GHSA-IIII-0000-0000', 'moderate');
        $this->addFinding('undici', 'GHSA-JJJJ-0000-0000', 'low');

        $result = $this->repo->topVulnerableDependenciesForScan((int) $this->scan->getId(), 10);

        $this->assertSame(200, $result['code']);
        $this->assertCount(2, $result['liste']);
        $this->assertSame('vite', $result['liste'][0]['name']);
        $this->assertSame(2, (int) $result['liste'][0]['finding_count']);
        $this->assertSame('undici', $result['liste'][1]['name']);
        $this->assertSame(1, (int) $result['liste'][1]['finding_count']);
    }

    public function testTopVulnerableDependenciesForScanRespectsLimit(): void
    {
        $this->addFinding('vite', 'GHSA-KKKK-0000-0000', 'high');
        $this->addFinding('undici', 'GHSA-LLLL-0000-0000', 'high');
        $this->addFinding('body-parser', 'GHSA-MMMM-0000-0000', 'high');

        $result = $this->repo->topVulnerableDependenciesForScan((int) $this->scan->getId(), 2);

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
