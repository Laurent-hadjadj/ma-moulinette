<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Batch;

/* MODIF 2026-05-17 : tests unitaires pour
 * BatchCollecteCleanCodeController (5 scénarios). */

use App\Controller\Batch\BatchCollecteCleanCodeController;
use App\Repository\CleanCodeRepository;
use App\Service\{ClientService, ExtractName, UrlBuilderService};
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AllowMockObjectsWithoutExpectations]
class BatchCollecteCleanCodeControllerTest extends TestCase
{
    private const MAVEN_KEY = 'fr.ma-moulinette:ma-moulinette';
    private const SONAR_URL = 'https://sonar.example.com';
    private const BUILT_URL  = 'https://sonar.example.com/api/issues/search?test';

    /** @var EntityManagerInterface&MockObject */
    private MockObject $em;

    /** @var CleanCodeRepository&MockObject */
    private MockObject $cleanCodeRepo;

    /** @var ClientService&MockObject */
    private MockObject $client;

    /** @var ExtractName&MockObject */
    private MockObject $extractName;

    /** @var UrlBuilderService&MockObject */
    private MockObject $urlBuilder;

    /** @var LoggerInterface&MockObject */
    private MockObject $logger;

    /** @var ParameterBagInterface&MockObject */
    private MockObject $parameterBag;

    private BatchCollecteCleanCodeController $controller;

    protected function setUp(): void
    {
        $this->em            = $this->createMock(EntityManagerInterface::class);
        $this->cleanCodeRepo = $this->createMock(CleanCodeRepository::class);
        $this->client        = $this->createMock(ClientService::class);
        $this->extractName   = $this->createMock(ExtractName::class);
        $this->urlBuilder    = $this->createMock(UrlBuilderService::class);
        $this->logger        = $this->createMock(LoggerInterface::class);
        $this->parameterBag  = $this->createMock(ParameterBagInterface::class);

        $this->em->method('getRepository')->willReturn($this->cleanCodeRepo);

        $this->urlBuilder->method('build')->willReturn(self::BUILT_URL);
        $this->extractName->method('extractNameFromMavenKey')->willReturn('ma-moulinette');

        $this->parameterBag->method('get')->willReturnMap([
            ['sonar.url', self::SONAR_URL],
        ]);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnMap([
            ['parameter_bag', true],
        ]);
        $container->method('get')->willReturnMap([
            ['parameter_bag', 1, $this->parameterBag],
        ]);

        $this->controller = new BatchCollecteCleanCodeController(
            $this->em,
            $this->client,
            $this->extractName,
            $this->urlBuilder,
            $this->logger
        );
        $this->controller->setContainer($container);
    }

    // ─── 1. Facettes SQ 10+ non supportées (code HTTP 400) ───────────────────

    public function testSkipsGracefullyWhenMainFacetsNotSupported(): void
    {
        $this->client->expects($this->once())
            ->method('httpSonarQube')
            ->willReturn(['code' => 400, 'erreur' => 'facettes non supportées']);

        $result = $this->controller->BatchCollecteCleanCode(self::MAVEN_KEY, 'MANUEL', 'admin');

        $this->assertSame(200, $result['code']);
        $this->assertTrue($result['skipped']);
    }

    // ─── 2. Erreur lors du deleteCleanCodeMavenKey ───────────────────────────

    public function testReturnsErrorWhenDeleteFails(): void
    {
        $this->client->expects($this->exactly(4))
            ->method('httpSonarQube')
            ->willReturn(['code' => 200, 'json' => $this->buildMainFacetsPayload()]);

        $this->cleanCodeRepo->expects($this->once())
            ->method('deleteCleanCodeMavenKey')
            ->willReturn(['code' => 500, 'erreur' => 'delete failed']);

        $this->cleanCodeRepo->expects($this->never())->method('insertCleanCode');

        $result = $this->controller->BatchCollecteCleanCode(self::MAVEN_KEY, 'MANUEL', 'admin');

        $this->assertSame(500, $result['code']);
        $this->assertSame('delete failed', $result['erreur']);
    }

    // ─── 3. Erreur lors du insertCleanCode ───────────────────────────────────

    public function testReturnsErrorWhenInsertFails(): void
    {
        $this->client->expects($this->exactly(4))
            ->method('httpSonarQube')
            ->willReturn(['code' => 200, 'json' => $this->buildMainFacetsPayload()]);

        $this->cleanCodeRepo->expects($this->once())
            ->method('deleteCleanCodeMavenKey')
            ->willReturn(['code' => 200, 'erreur' => '']);

        $this->cleanCodeRepo->expects($this->once())
            ->method('insertCleanCode')
            ->willReturn(['code' => 500, 'erreur' => 'insert failed']);

        $result = $this->controller->BatchCollecteCleanCode(self::MAVEN_KEY, 'MANUEL', 'admin');

        $this->assertSame(500, $result['code']);
        $this->assertSame('insert failed', $result['erreur']);
    }

    // ─── 4. Happy path complet ───────────────────────────────────────────────

    public function testSuccessfulCollecte(): void
    {
        $mainPayload = $this->buildMainFacetsPayload(
            total: 20,
            ccCounts: [
                ['val' => 'CONSISTENT',  'count' => 5],
                ['val' => 'INTENTIONAL', 'count' => 3],
            ],
            severityCounts: [
                ['val' => 'HIGH',   'count' => 8],
                ['val' => 'MEDIUM', 'count' => 12],
            ],
            qualityCounts: [
                ['val' => 'MAINTAINABILITY', 'count' => 10],
                ['val' => 'SECURITY',        'count' => 5],
            ]
        );

        $this->client->expects($this->exactly(4))
            ->method('httpSonarQube')
            ->willReturnOnConsecutiveCalls(
                ['code' => 200, 'json' => $mainPayload],
                ['code' => 200, 'json' => ['paging' => ['total' => 3]]],
                ['code' => 200, 'json' => ['paging' => ['total' => 2]]],
                ['code' => 200, 'json' => ['facets' => [
                    ['property' => 'cwe', 'values' => [
                        ['val' => 'CWE-79', 'count' => 4],
                        ['val' => 'CWE-89', 'count' => 2],
                    ]],
                ]]],
            );

        $this->cleanCodeRepo->expects($this->once())
            ->method('deleteCleanCodeMavenKey')
            ->willReturn(['code' => 200, 'erreur' => '']);

        /* MODIF 2026-05-07 : init [] (intelephense by-ref). */
        $capturedMap = [];
        $this->cleanCodeRepo->expects($this->once())
            ->method('insertCleanCode')
            ->with($this->callback(function (array $map) use (&$capturedMap) {
                $capturedMap = $map;
                return true;
            }))
            ->willReturn(['code' => 200, 'erreur' => '']);

        $result = $this->controller->BatchCollecteCleanCode(self::MAVEN_KEY, 'MANUEL', 'admin');

        $this->assertSame(200, $result['code']);

        // Champs persistés
        $this->assertSame(self::MAVEN_KEY, $capturedMap['maven_key']);
        $this->assertSame('ma-moulinette', $capturedMap['project_name']);
        $this->assertSame(20, $capturedMap['issue_total']);
        $this->assertSame(5,  $capturedMap['cc_consistent']);
        $this->assertSame(3,  $capturedMap['cc_intentional']);
        $this->assertSame(0,  $capturedMap['cc_adaptable']);
        $this->assertSame(0,  $capturedMap['cc_responsible']);
        $this->assertSame(8,  $capturedMap['impact_high']);
        $this->assertSame(12, $capturedMap['impact_medium']);
        $this->assertSame(0,  $capturedMap['impact_blocker']);
        $this->assertSame(10, $capturedMap['quality_maintainability']);
        $this->assertSame(5,  $capturedMap['quality_security']);
        $this->assertSame(0,  $capturedMap['quality_reliability']);
        $this->assertSame(3,  $capturedMap['owasp_top10']);
        $this->assertSame(2,  $capturedMap['sans_top25']);
        $this->assertSame(6,  $capturedMap['cwe']); // 4 + 2
        $this->assertSame('MANUEL', $capturedMap['mode_collecte']);
        $this->assertSame('admin',  $capturedMap['utilisateur_collecte']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $capturedMap['date_enregistrement']);

        // Clé `historique` avec les 15 colonnes pour ApiEnregistrementController
        $this->assertArrayHasKey('historique', $result);
        $historique = $result['historique'];
        $this->assertSame(5, $historique['cc_consistent']);
        $this->assertSame(3, $historique['cc_intentional']);
        $this->assertSame(3, $historique['owasp_top10']);
        $this->assertSame(2, $historique['sans_top25']);
        $this->assertSame(6, $historique['cwe']);
    }

    // ─── 5. Appels compliance silencieux en cas d'erreur HTTP ────────────────

    public function testComplianceCallsAreSilentOnFailure(): void
    {
        $this->client->expects($this->exactly(4))
            ->method('httpSonarQube')
            ->willReturnOnConsecutiveCalls(
                ['code' => 200, 'json' => $this->buildMainFacetsPayload(5)],
                ['code' => 404, 'erreur' => 'owasp non supporté'],
                ['code' => 404, 'erreur' => 'sans non supporté'],
                ['code' => 404, 'erreur' => 'cwe non supporté'],
            );

        $this->cleanCodeRepo->method('deleteCleanCodeMavenKey')
            ->willReturn(['code' => 200, 'erreur' => '']);

        /* MODIF 2026-05-07 : init [] (intelephense by-ref). */
        $capturedMap = [];
        $this->cleanCodeRepo->method('insertCleanCode')
            ->willReturnCallback(function (array $map) use (&$capturedMap) {
                $capturedMap = $map;
                return ['code' => 200, 'erreur' => ''];
            });

        $result = $this->controller->BatchCollecteCleanCode(self::MAVEN_KEY, 'MANUEL', 'admin');

        $this->assertSame(200, $result['code']);
        $this->assertSame(0, $capturedMap['owasp_top10']);
        $this->assertSame(0, $capturedMap['sans_top25']);
        $this->assertSame(0, $capturedMap['cwe']);
    }

    // ─── helpers ─────────────────────────────────────────────────────────────

    /**
     * @param array<int, array{val: string, count: int}> $ccCounts
     * @param array<int, array{val: string, count: int}> $severityCounts
     * @param array<int, array{val: string, count: int}> $qualityCounts
     *
     * @return array{
     *     paging: array{total: int},
     *     facets: array<int, array{property: string, values: array<int, array{val: string, count: int}>}>,
     * }
     */
    private function buildMainFacetsPayload(
        int $total = 10,
        array $ccCounts = [],
        array $severityCounts = [],
        array $qualityCounts = []
    ): array {
        return [
            'paging' => ['total' => $total],
            'facets' => [
                ['property' => 'cleanCodeAttributeCategories', 'values' => $ccCounts],
                ['property' => 'impactSeverities',             'values' => $severityCounts],
                ['property' => 'impactSoftwareQualities',      'values' => $qualityCounts],
            ],
        ];
    }
}
