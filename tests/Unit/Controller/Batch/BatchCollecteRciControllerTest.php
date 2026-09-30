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

namespace App\Tests\Unit\Controller\Batch;

use App\Controller\Batch\BatchCollecteRciController;
use App\Entity\Rci;
use App\Repository\RciRepository;
use App\Service\RciMeasures\BuildMeasuresRciService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AllowMockObjectsWithoutExpectations]
class BatchCollecteRciControllerTest extends TestCase
{
    private const MAVEN_KEY = 'fr.ma-moulinette:ma-moulinette';
    private const MODE = 'COLLECTE';
    private const USER = 'admin@ma-moulinette.fr';

    /** @var EntityManagerInterface&MockObject */
    private MockObject $em;
    /** @var RciRepository&MockObject */
    private MockObject $rciRepo;
    /** @var ParameterBagInterface&MockObject */
    private MockObject $params;
    /** @var BuildMeasuresRciService&MockObject */
    private MockObject $buildMeasures;
    /** @var LoggerInterface&MockObject */
    private MockObject $logger;

    private BatchCollecteRciController $controller;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->rciRepo = $this->createMock(RciRepository::class);
        $this->em->method('getRepository')->willReturnMap([
            [Rci::class, $this->rciRepo],
        ]);

        $this->params = $this->createMock(ParameterBagInterface::class);
        $this->params->method('get')->willReturnMap([
            ['sonar.version', '9'],
        ]);

        $this->buildMeasures = $this->createMock(BuildMeasuresRciService::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->controller = new BatchCollecteRciController(
            $this->em,
            $this->params,
            $this->buildMeasures,
            $this->logger
        );
    }

    private function stubHappyMeasures(): void
    {
        $this->buildMeasures->method('calculateGlobalMeasures')->willReturn([
            'code' => 200,
            'global_measures' => ['ncloc' => 8125],
            'severities' => ['BLOCKER' => 2, 'CRITICAL' => 1, 'MAJOR' => 0, 'MINOR' => 0, 'INFO' => 0],
        ]);
        $this->buildMeasures->method('calculateMeasuresByModule')->willReturn([
            'code' => 200,
            'ncloc' => ['frontend' => 6050, 'backend' => 1816, 'autre' => 0, 'inconnu' => 259],
            'severities' => [
                'frontend' => ['BLOCKER' => 1, 'CRITICAL' => 0, 'MAJOR' => 0, 'MINOR' => 0, 'INFO' => 0],
                'backend' => ['BLOCKER' => 0, 'CRITICAL' => 0, 'MAJOR' => 0, 'MINOR' => 0, 'INFO' => 0],
                'autre' => ['BLOCKER' => 0, 'CRITICAL' => 0, 'MAJOR' => 0, 'MINOR' => 0, 'INFO' => 0],
                'inconnu' => ['BLOCKER' => 0, 'CRITICAL' => 0, 'MAJOR' => 0, 'MINOR' => 0, 'INFO' => 0],
            ],
        ]);
        $this->buildMeasures->method('weightedWeights')
            ->willReturnCallback(fn (int $b, int $c, int $m, int $mi, int $i): int => $b * 10 + $c * 5 + $m * 3 + $mi);
        $this->buildMeasures->method('calculateRci')
            ->willReturnCallback(fn (?int $ncloc, int $poids): ?float => $ncloc === null || $ncloc <= 0 ? null : 90.0);
        $this->buildMeasures->method('getRCIRating')
            ->willReturnCallback(fn (?float $rci): ?string => $rci === null ? null : 'B');
    }

    public function testBuildRciMeasuresHappyPathDeletesThenInsertsAndReturns200(): void
    {
        $this->stubHappyMeasures();

        $this->rciRepo->expects($this->once())->method('deleteRciMavenKey')
            ->with(['maven_key' => self::MAVEN_KEY])
            ->willReturn(['code' => 200, 'erreur' => '']);

        $this->rciRepo->expects($this->once())->method('insertRci')
            ->with($this->callback(function (array $data): bool {
                return $data['maven_key'] === self::MAVEN_KEY
                    && $data['ncloc'] === 8125
                    && $data['rating'] === 'B'
                    && $data['frontend_ncloc'] === 6050
                    && $data['backend_ncloc'] === 1816
                    && $data['autre_ncloc'] === 0
                    && $data['inconnu_ncloc'] === 259
                    && $data['mode_collecte'] === self::MODE
                    && $data['utilisateur_collecte'] === self::USER
                    && $data['date_enregistrement'] instanceof \DateTimeImmutable;
            }))
            ->willReturn(['code' => 200, 'erreur' => '']);

        $result = $this->controller->buildRciMeasures(self::MAVEN_KEY, self::MODE, self::USER);

        $this->assertSame(200, $result['code']);
        $this->assertArrayHasKey('historique', $result);
    }

    public function testBuildRciMeasuresStopsWhenGlobalMeasuresFail(): void
    {
        $this->buildMeasures->method('calculateGlobalMeasures')->willReturn([
            'code' => 500,
            'erreur' => 'Erreur SonarQube',
        ]);

        $this->buildMeasures->expects($this->never())->method('calculateMeasuresByModule');
        $this->rciRepo->expects($this->never())->method('deleteRciMavenKey');
        $this->rciRepo->expects($this->never())->method('insertRci');

        $result = $this->controller->buildRciMeasures(self::MAVEN_KEY, self::MODE, self::USER);

        $this->assertSame(500, $result['code']);
        $this->assertSame('Erreur SonarQube', $result['erreur']);
    }

    public function testBuildRciMeasuresStopsWhenModuleMeasuresFail(): void
    {
        $this->buildMeasures->method('calculateGlobalMeasures')->willReturn([
            'code' => 200,
            'global_measures' => ['ncloc' => 8125],
            'severities' => ['BLOCKER' => 0, 'CRITICAL' => 0, 'MAJOR' => 0, 'MINOR' => 0, 'INFO' => 0],
        ]);
        $this->buildMeasures->method('calculateMeasuresByModule')->willReturn([
            'code' => 404,
            'erreur' => 'Projet introuvable',
        ]);
        $this->buildMeasures->method('weightedWeights')->willReturn(0);
        $this->buildMeasures->method('calculateRci')->willReturn(100.0);
        $this->buildMeasures->method('getRCIRating')->willReturn('A');

        $this->rciRepo->expects($this->never())->method('deleteRciMavenKey');
        $this->rciRepo->expects($this->never())->method('insertRci');

        $result = $this->controller->buildRciMeasures(self::MAVEN_KEY, self::MODE, self::USER);

        $this->assertSame(404, $result['code']);
    }

    public function testBuildRciMeasuresStopsWhenDeleteFails(): void
    {
        $this->stubHappyMeasures();

        $this->rciRepo->method('deleteRciMavenKey')->willReturn(['code' => 500, 'erreur' => 'delete KO']);
        $this->rciRepo->expects($this->never())->method('insertRci');

        $result = $this->controller->buildRciMeasures(self::MAVEN_KEY, self::MODE, self::USER);

        $this->assertSame(500, $result['code']);
        $this->assertSame('delete KO', $result['erreur']);
    }

    public function testBuildRciMeasuresStopsWhenInsertFails(): void
    {
        $this->stubHappyMeasures();

        $this->rciRepo->method('deleteRciMavenKey')->willReturn(['code' => 200, 'erreur' => '']);
        $this->rciRepo->method('insertRci')->willReturn(['code' => 500, 'erreur' => 'insert KO']);

        $result = $this->controller->buildRciMeasures(self::MAVEN_KEY, self::MODE, self::USER);

        $this->assertSame(500, $result['code']);
        $this->assertSame('insert KO', $result['erreur']);
    }
}
