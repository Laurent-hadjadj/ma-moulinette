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

namespace App\Tests\Unit\Repository;

use App\Entity\DcDependency;
use App\Repository\DcDependencyRepository;
use Doctrine\DBAL\{Connection, Result};
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * MODIF 2026-05-08 : tests Unit pour DcDependencyRepository.
 * Couvre topVulnerableDependencies + handleDatabaseException.
 */
#[AllowMockObjectsWithoutExpectations]
class DcDependencyRepositoryTest extends TestCase
{
    /**
     * @param array<int, array<string, mixed>>|\Throwable $rowsOrException
     */
    private function buildRepo(array|\Throwable $rowsOrException, ?int $expectedLimitParam = null): DcDependencyRepository
    {
        $connection = $this->createMock(Connection::class);

        if ($rowsOrException instanceof \Throwable) {
            $matcher = $expectedLimitParam !== null
                ? $connection->expects($this->once())->method('executeQuery')->with($this->isString(), ['lim' => $expectedLimitParam])
                : $connection->method('executeQuery');
            $matcher->willThrowException($rowsOrException);
        } else {
            $result = $this->createStub(Result::class);
            $result->method('fetchAllAssociative')->willReturn($rowsOrException);

            if ($expectedLimitParam !== null) {
                $connection->expects($this->once())
                    ->method('executeQuery')
                    ->with($this->isString(), ['lim' => $expectedLimitParam])
                    ->willReturn($result);
            } else {
                $connection->method('executeQuery')->willReturn($result);
            }
        }

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connection);
        $em->method('getClassMetadata')->willReturn(new ClassMetadata(DcDependency::class));

        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($em);

        return new DcDependencyRepository($registry);
    }

    public function testHandleDatabaseExceptionFallsBackToGenericMessage(): void
    {
        $repo = $this->buildRepo([]);
        $exception = new \RuntimeException('boom');

        $result = $repo->handleDatabaseException($exception);

        $this->assertSame(500, $result['code']);
        $this->assertSame('boom', $result['erreur']);
    }

    public function testTopVulnerableDependenciesReturnsMappedRows(): void
    {
        $repo = $this->buildRepo([
            [
                'pkg_coordinates' => 'pkg:maven/org.apache.logging.log4j/log4j-core@2.14.0',
                'file_name' => 'log4j-core-2.14.0.jar',
                'vendor' => 'org.apache.logging.log4j',
                'product' => 'log4j-core',
                'version' => '2.14.0',
                'nb_projets' => '5',
                'nb_cves' => '3',
            ],
            [
                'pkg_coordinates' => 'pkg:maven/com.fasterxml.jackson.core/jackson-databind@2.9.0',
                'file_name' => 'jackson-databind-2.9.0.jar',
                'vendor' => 'com.fasterxml.jackson.core',
                'product' => 'jackson-databind',
                'version' => '2.9.0',
                'nb_projets' => '3',
                'nb_cves' => '8',
            ],
        ], expectedLimitParam: 20);

        $result = $repo->topVulnerableDependencies(20);

        $this->assertSame(200, $result['code']);
        $this->assertSame('', $result['erreur']);
        $this->assertCount(2, $result['liste']);
        $this->assertSame('log4j-core', $result['liste'][0]['product']);
        $this->assertSame(5, $result['liste'][0]['nb_projets']);
        $this->assertSame(3, $result['liste'][0]['nb_cves']);
        $this->assertSame('jackson-databind', $result['liste'][1]['product']);
        $this->assertSame(8, $result['liste'][1]['nb_cves']);
    }

    public function testTopVulnerableDependenciesPassesLimitParam(): void
    {
        $repo = $this->buildRepo([], expectedLimitParam: 50);

        $result = $repo->topVulnerableDependencies(50);

        $this->assertSame(200, $result['code']);
        $this->assertSame([], $result['liste']);
    }

    public function testTopVulnerableDependenciesUsesDefaultLimitOf20(): void
    {
        $repo = $this->buildRepo([], expectedLimitParam: 20);

        $result = $repo->topVulnerableDependencies();

        $this->assertSame(200, $result['code']);
    }

    public function testTopVulnerableDependenciesReturnsErrorOnDatabaseException(): void
    {
        $repo = $this->buildRepo(new \RuntimeException('db fail'), expectedLimitParam: 20);

        $result = $repo->topVulnerableDependencies(20);

        $this->assertSame(500, $result['code']);
        $this->assertSame('db fail', $result['erreur']);
    }

    /* ============ topMutualisableDependencies ============
     * MODIF 2026-05-11: tests Unit pour
     * la nouvelle méthode. Pattern aligné sur topVulnerableDependencies. */

    public function testTopMutualisableDependenciesReturnsMappedRows(): void
    {
        // MODIF 2026-05-12 : ajout
        // nb_archetypes_distincts + is_via_archetype dans le mapping.
        $repo = $this->buildRepo([
            [
                'pkg_coordinates' => 'pkg:maven/org.apache.logging.log4j/log4j-core@2.14.0',
                'file_name' => 'log4j-core-2.14.0.jar',
                'vendor' => 'org.apache.logging.log4j',
                'product' => 'log4j-core',
                'version' => '2.14.0',
                'nb_projets' => '7',
                'nb_cves' => '4',
                'nb_critical' => '2',
                'nb_high' => '1',
                'nb_medium' => '1',
                'nb_low' => '0',
                'nb_archetypes_distincts' => '1',  // toutes apps même archétype
            ],
            [
                'pkg_coordinates' => 'pkg:maven/com.fasterxml.jackson.core/jackson-databind@2.9.0',
                'file_name' => 'jackson-databind-2.9.0.jar',
                'vendor' => 'com.fasterxml.jackson.core',
                'product' => 'jackson-databind',
                'version' => '2.9.0',
                'nb_projets' => '3',
                'nb_cves' => '8',
                'nb_critical' => '0',
                'nb_high' => '5',
                'nb_medium' => '2',
                'nb_low' => '1',
                'nb_archetypes_distincts' => '3',  // 3 archétypes/groupes distincts
            ],
        ]);

        $result = $repo->topMutualisableDependencies();

        $this->assertSame(200, $result['code']);
        $this->assertSame('', $result['erreur']);
        $this->assertCount(2, $result['liste']);

        $log4j = $result['liste'][0];
        $this->assertSame('log4j-core', $log4j['product']);
        $this->assertSame(7, $log4j['nb_projets']);
        $this->assertSame(4, $log4j['nb_cves']);
        $this->assertSame(2, $log4j['nb_critical']);
        $this->assertSame(1, $log4j['nb_high']);
        $this->assertSame(1, $log4j['nb_archetypes_distincts']);
        $this->assertTrue($log4j['is_via_archetype']); // 1 archétype = via héritage
        $this->assertArrayNotHasKey('has_blocking_without_fix', $log4j);

        $jackson = $result['liste'][1];
        $this->assertSame('jackson-databind', $jackson['product']);
        $this->assertSame(0, $jackson['nb_critical']);
        $this->assertSame(5, $jackson['nb_high']);
        $this->assertSame(3, $jackson['nb_archetypes_distincts']);
        $this->assertFalse($jackson['is_via_archetype']); // 3 archétypes = convergence
    }

    /* MODIF 2026-05-12 : test dédié au flag is_via_archetype
     * pour couvrir les 2 cas limites (0 archetype → via, 1 → via, 2+ → convergence). */
    public function testTopMutualisableDependenciesFlagsViaArchetypeCorrectly(): void
    {
        $base = [
            'pkg_coordinates' => 'pkg:m/g/a@v', 'file_name' => 'f', 'vendor' => 'v', 'product' => 'p',
            'version' => '1', 'nb_projets' => '2', 'nb_cves' => '1',
            'nb_critical' => '0', 'nb_high' => '1', 'nb_medium' => '0', 'nb_low' => '0',
        ];
        $repo = $this->buildRepo([
            ['product' => 'a-1arch'] + ['nb_archetypes_distincts' => '1'] + $base,
            ['product' => 'b-2arch'] + ['nb_archetypes_distincts' => '2'] + $base,
            ['product' => 'c-5arch'] + ['nb_archetypes_distincts' => '5'] + $base,
        ]);

        $result = $repo->topMutualisableDependencies();
        $flags = array_column($result['liste'], 'is_via_archetype', 'product');
        $this->assertTrue($flags['a-1arch']);
        $this->assertFalse($flags['b-2arch']);
        $this->assertFalse($flags['c-5arch']);
    }

    public function testTopMutualisableDependenciesReturnsEmptyListWhenScopeIsEmptyArray(): void
    {
        // Scope = [] : retour direct sans toucher la DB.
        $repo = $this->buildRepo([]);

        $result = $repo->topMutualisableDependencies(2, 200, []);

        $this->assertSame(['code' => 200, 'liste' => [], 'erreur' => ''], $result);
    }

    public function testTopMutualisableDependenciesReturnsEmptyListWhenNoData(): void
    {
        $repo = $this->buildRepo([]);

        $result = $repo->topMutualisableDependencies();

        $this->assertSame(200, $result['code']);
        $this->assertSame([], $result['liste']);
    }

    public function testTopMutualisableDependenciesReturnsErrorOnDatabaseException(): void
    {
        $repo = $this->buildRepo(new \RuntimeException('boom'));

        $result = $repo->topMutualisableDependencies();

        $this->assertSame(500, $result['code']);
        $this->assertSame('boom', $result['erreur']);
    }

    /* MODIF 2026-07-22 : régression troncature silencieuse — la requête
     * demande hardLimit+1 lignes pour détecter si le parc dépasse la limite. */

    /**
     * @return array{pkg_coordinates: string, file_name: string, vendor: string, product: string,
     *     version: string, nb_projets: string, nb_cves: string, nb_critical: string,
     *     nb_high: string, nb_medium: string, nb_low: string, nb_archetypes_distincts: string}
     */
    private function buildDependencyRow(string $product): array
    {
        return [
            'pkg_coordinates' => 'pkg:m/g/' . $product . '@1', 'file_name' => $product . '.jar',
            'vendor' => 'v', 'product' => $product, 'version' => '1',
            'nb_projets' => '2', 'nb_cves' => '1',
            'nb_critical' => '0', 'nb_high' => '1', 'nb_medium' => '0', 'nb_low' => '0',
            'nb_archetypes_distincts' => '1',
        ];
    }

    public function testTopMutualisableDependenciesFlagsTruncatedWhenMoreRowsThanHardLimit(): void
    {
        // hardLimit=2, la requête interne demande hardLimit+1=3 lignes ; le mock
        // en renvoie 3 -> troncature détectée, résultat ramené à hardLimit=2.
        $repo = $this->buildRepo([
            $this->buildDependencyRow('a'),
            $this->buildDependencyRow('b'),
            $this->buildDependencyRow('c'),
        ]);

        $result = $repo->topMutualisableDependencies(2, 2);

        $this->assertTrue($result['truncated']);
        $this->assertCount(2, $result['liste']); // tronqué à hardLimit, pas hardLimit+1
        $this->assertSame(['a', 'b'], array_column($result['liste'], 'product'));
    }

    public function testTopMutualisableDependenciesNotTruncatedWhenRowsWithinHardLimit(): void
    {
        $repo = $this->buildRepo([
            $this->buildDependencyRow('a'),
            $this->buildDependencyRow('b'),
        ]);

        $result = $repo->topMutualisableDependencies(2, 2);

        $this->assertFalse($result['truncated']);
        $this->assertCount(2, $result['liste']);
    }
}
