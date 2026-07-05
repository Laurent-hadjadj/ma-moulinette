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

use App\Controller\Batch\BatchCollecteOwaspController;
use App\Entity\{InformationProjet, Owasp};
use App\Repository\{InformationProjetRepository, OwaspRepository};
use App\Service\{ClientService, UrlBuilderService};
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\{AllowMockObjectsWithoutExpectations, DataProvider};
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AllowMockObjectsWithoutExpectations]
class BatchCollecteOwaspControllerTest extends TestCase
{
    private const MAVEN_KEY = 'com.acme:app';
    private const SONAR_URL = 'https://sonar.example.com';
    private const BUILT_URL_2017 = 'https://sonar.example.com/api/issues/search?...owasp2017';
    private const BUILT_URL_2021 = 'https://sonar.example.com/api/issues/search?...owasp2021';

    /** @var EntityManagerInterface&MockObject */
    private MockObject $em;

    /** @var InformationProjetRepository&MockObject */
    private MockObject $infoRepo;

    /** @var OwaspRepository&MockObject */
    private MockObject $owaspRepo;

    /** @var ClientService&MockObject */
    private MockObject $client;

    /** @var UrlBuilderService&MockObject */
    private MockObject $urlBuilder;

    /** @var LoggerInterface&MockObject */
    private MockObject $logger;

    /** @var ParameterBagInterface&MockObject */
    private MockObject $parameterBag;

    private BatchCollecteOwaspController $controller;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->infoRepo = $this->createMock(InformationProjetRepository::class);
        $this->owaspRepo = $this->createMock(OwaspRepository::class);
        $this->client = $this->createMock(ClientService::class);
        $this->urlBuilder = $this->createMock(UrlBuilderService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);

        $this->em->method('getRepository')->willReturnMap([
            [InformationProjet::class, $this->infoRepo],
            [Owasp::class, $this->owaspRepo],
        ]);

        // url-builder renvoie des URL distinctes selon le payload owaspTop10/owaspTop10-2021
        $this->urlBuilder->method('build')
            ->willReturnCallback(function (string $base, string $path, array $params) {
                return isset($params['owaspTop10-2021'])
                    ? self::BUILT_URL_2021
                    : self::BUILT_URL_2017;
            });

        // Mock du container pour $this->getParameter() — pattern AbstractController.
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnMap([
            ['parameter_bag', true],
            ['serializer', false],
        ]);
        $container->method('get')->willReturnMap([
            ['parameter_bag', 1, $this->parameterBag],
        ]);

        $this->controller = new BatchCollecteOwaspController(
            $this->em,
            $this->client,
            $this->urlBuilder,
            $this->logger
        );
        $this->controller->setContainer($container);
    }

    // ─── 1. Erreur HTTP sur OWASP 2017 ────────────────────────────────────────

    #[DataProvider('httpErrorCodesProvider')]
    public function testReturnsErrorWhenOwasp2017HttpCallFails(int $httpCode): void
    {
        $this->parameterBag->method('get')->willReturnMap([
            ['sonar.url', self::SONAR_URL],
            ['sonar.version', '10'],
        ]);

        $this->client->expects($this->once())
            ->method('httpSonarQube')
            ->willReturn(['code' => $httpCode, 'erreur' => "HTTP $httpCode"]);

        $this->logger->expects($this->once())->method('error');

        $result = $this->controller->BatchCollecteOwasp(self::MAVEN_KEY, 'manual', 'admin');

        $this->assertSame(['code' => $httpCode, 'erreur' => "HTTP $httpCode"], $result);
    }

    public static function httpErrorCodesProvider(): array
    {
        return [[400], [401], [500], [503]];
    }

    // ─── 2. sonar.version = 8 : 2021 pas appelé ──────────────────────────────

    public function testSkipsOwasp2021WhenSonarVersionIs8(): void
    {
        $this->parameterBag->method('get')->willReturnMap([
            ['sonar.url', self::SONAR_URL],
            ['sonar.version', '8'],
        ]);

        // Un seul appel httpSonarQube (2017 uniquement)
        $this->client->expects($this->once())
            ->method('httpSonarQube')
            ->with(self::BUILT_URL_2017)
            ->willReturn(['code' => 200, 'json' => $this->buildOwaspPayload(
                total: 3,
                facets: [['val' => 'a1', 'count' => 2], ['val' => 'a3', 'count' => 1]]
            )]);

        $this->infoRepo->expects($this->once())
            ->method('selectInformationProjetVersion')
            ->willReturn(['code' => 200, 'info' => [
                'date' => '2026-04-22',
                'version' => '1.0.0',
            ]]);

        $this->owaspRepo->expects($this->once())->method('deleteOwaspMavenKey')->willReturn(['code' => 200]);
        $this->owaspRepo->expects($this->once())
            ->method('insertOwasp')
            ->with($this->callback(fn (array $list) => count($list) === 1 && $list[0]['referential_owasp'] === 2017))
            ->willReturn(['code' => 200]);

        $result = $this->controller->BatchCollecteOwasp(self::MAVEN_KEY, 'manual', 'admin');

        $this->assertSame(200, $result['code']);
        $this->assertSame(3, $result['owasp2017']);
        $this->assertSame('NC', $result['owasp2021']);
    }

    // ─── 3. Erreur HTTP sur OWASP 2021 ──────────────────────────────────────

    public function testReturnsErrorWhenOwasp2021HttpCallFails(): void
    {
        $this->parameterBag->method('get')->willReturnMap([
            ['sonar.url', self::SONAR_URL],
            ['sonar.version', '10'],
        ]);

        $this->client->expects($this->exactly(2))
            ->method('httpSonarQube')
            ->willReturnOnConsecutiveCalls(
                ['code' => 200, 'json' => $this->buildOwaspPayload(0)],
                ['code' => 500, 'erreur' => 'owasp 2021 failed']
            );

        $result = $this->controller->BatchCollecteOwasp(self::MAVEN_KEY, 'manual', 'admin');

        $this->assertSame(['code' => 500, 'erreur' => 'owasp 2021 failed'], $result);
    }

    // ─── 4. selectInformationProjetVersion échoue ────────────────────────────

    public function testReturnsErrorWhenInformationProjetVersionQueryFails(): void
    {
        $this->parameterBag->method('get')->willReturnMap([
            ['sonar.url', self::SONAR_URL],
            ['sonar.version', '8'],
        ]);

        $this->client->expects($this->once())
            ->method('httpSonarQube')
            ->willReturn(['code' => 200, 'json' => $this->buildOwaspPayload(0)]);

        $this->infoRepo->expects($this->once())
            ->method('selectInformationProjetVersion')
            ->willReturn(['code' => 503, 'erreur' => 'DB down']);

        $this->owaspRepo->expects($this->never())->method('deleteOwaspMavenKey');
        $this->owaspRepo->expects($this->never())->method('insertOwasp');

        $result = $this->controller->BatchCollecteOwasp(self::MAVEN_KEY, 'manual', 'admin');

        $this->assertSame(['code' => 503, 'erreur' => 'DB down'], $result);
    }

    // ─── 5. Aucune info projet ────────────────────────────────────────────────

    public function testReturns404WhenNoProjectInfoIsFound(): void
    {
        $this->parameterBag->method('get')->willReturnMap([
            ['sonar.url', self::SONAR_URL],
            ['sonar.version', '8'],
        ]);

        $this->client->expects($this->once())
            ->method('httpSonarQube')
            ->willReturn(['code' => 200, 'json' => $this->buildOwaspPayload(0)]);

        $this->infoRepo->expects($this->once())
            ->method('selectInformationProjetVersion')
            ->willReturn(['code' => 200, 'info' => []]);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('Aucun projet trouvé'));

        $result = $this->controller->BatchCollecteOwasp(self::MAVEN_KEY, 'manual', 'admin');

        $this->assertSame(404, $result['code']);
    }

    // ─── 6. deleteOwasp échoue ───────────────────────────────────────────────

    public function testReturnsErrorWhenDeleteOwaspFails(): void
    {
        $this->stubOk(sonarVersion: '8');

        $this->owaspRepo->expects($this->once())
            ->method('deleteOwaspMavenKey')
            ->willReturn(['code' => 500, 'erreur' => 'delete failed']);

        $this->owaspRepo->expects($this->never())->method('insertOwasp');

        $result = $this->controller->BatchCollecteOwasp(self::MAVEN_KEY, 'manual', 'admin');

        $this->assertSame(['code' => 500, 'erreur' => 'delete failed'], $result);
    }

    // ─── 7. insertOwasp échoue ───────────────────────────────────────────────

    public function testReturnsErrorWhenInsertOwaspFails(): void
    {
        $this->stubOk(sonarVersion: '8');

        $this->owaspRepo->expects($this->once())->method('deleteOwaspMavenKey')->willReturn(['code' => 200]);
        $this->owaspRepo->expects($this->once())
            ->method('insertOwasp')
            ->willReturn(['code' => 500, 'erreur' => 'insert failed']);

        $result = $this->controller->BatchCollecteOwasp(self::MAVEN_KEY, 'manual', 'admin');

        $this->assertSame(['code' => 500, 'erreur' => 'insert failed'], $result);
    }

    // ─── 8. Happy path version 10+ : 2017 ET 2021 persistés ────────────────

    public function testHappyPathWithBothReferentialsPersistsSeparateRows(): void
    {
        $this->parameterBag->method('get')->willReturnMap([
            ['sonar.url', self::SONAR_URL],
            ['sonar.version', '10'],
        ]);

        $this->client->expects($this->exactly(2))
            ->method('httpSonarQube')
            ->willReturnOnConsecutiveCalls(
                ['code' => 200, 'json' => $this->buildOwaspPayload(
                    total: 5,
                    facets: [['val' => 'a1', 'count' => 3], ['val' => 'a2', 'count' => 2]]
                )],
                ['code' => 200, 'json' => $this->buildOwaspPayload(
                    total: 7,
                    facets: [['val' => 'a4', 'count' => 4], ['val' => 'a6', 'count' => 3]]
                )],
            );

        $this->infoRepo->expects($this->once())
            ->method('selectInformationProjetVersion')
            ->willReturn(['code' => 200, 'info' => [
                'date' => '2026-04-22',
                'version' => '2.0.0',
            ]]);

        $this->owaspRepo->expects($this->once())->method('deleteOwaspMavenKey')->willReturn(['code' => 200]);

        $this->owaspRepo->expects($this->once())
            ->method('insertOwasp')
            ->with($this->callback(function (array $list) {
                // Correction du bug : [0]=2017, [1]=2021 distincts, pas d'écrasement
                return count($list) === 2
                    && $list[0]['referential_owasp'] === 2017
                    && $list[1]['referential_owasp'] === 2021;
            }))
            ->willReturn(['code' => 200]);

        $result = $this->controller->BatchCollecteOwasp(self::MAVEN_KEY, 'auto', 'batch');

        $this->assertSame(200, $result['code']);
        $this->assertSame(5, $result['owasp2017']);
        $this->assertSame(7, $result['owasp2021']);
        $this->assertStringContainsString('OWASP', $result['message']);
    }

    // ─── 9. La map produit par prepareOwaspData contient a1..a10 + severities ─

    public function testPersistedMapHasExpectedShape(): void
    {
        $this->parameterBag->method('get')->willReturnMap([
            ['sonar.url', self::SONAR_URL],
            ['sonar.version', '8'],
        ]);

        $payload = $this->buildOwaspPayload(
            total: 3,
            facets: [
                ['val' => 'a1', 'count' => 1],
                ['val' => 'a5', 'count' => 2],
            ],
            issues: [
                ['status' => 'OPEN', 'severity' => 'BLOCKER', 'tags' => ['owasp-a1']],
                ['status' => 'OPEN', 'severity' => 'CRITICAL', 'tags' => ['owasp-a5']],
                ['status' => 'CLOSED', 'severity' => 'MAJOR', 'tags' => ['owasp-a1']], // ignoré (status)
            ]
        );

        $this->client->expects($this->once())->method('httpSonarQube')
            ->willReturn(['code' => 200, 'json' => $payload]);

        $this->infoRepo->method('selectInformationProjetVersion')
            ->willReturn(['code' => 200, 'info' => [
                'date' => '2026-04-22', 'version' => '1.0',
            ]]);

        /* MODIF 2026-05-07 [tests-validators] : init [] (intelephense by-ref). */
        $capturedMap = [];
        $this->owaspRepo->method('deleteOwaspMavenKey')->willReturn(['code' => 200]);
        $this->owaspRepo->expects($this->once())
            ->method('insertOwasp')
            ->with($this->callback(function (array $list) use (&$capturedMap) {
                $capturedMap = $list[0];
                return true;
            }))
            ->willReturn(['code' => 200]);

        $this->controller->BatchCollecteOwasp(self::MAVEN_KEY, 'manual', 'admin');

        $this->assertSame(self::MAVEN_KEY, $capturedMap['maven_key']);
        $this->assertSame('1.0', $capturedMap['version']);
        $this->assertSame('manual', $capturedMap['mode_collecte']);
        $this->assertSame('admin', $capturedMap['utilisateur_collecte']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $capturedMap['date_version']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $capturedMap['date_enregistrement']);

        // Compteurs a1..a10 depuis les facets
        $this->assertSame(1, $capturedMap['a1']);
        $this->assertSame(2, $capturedMap['a5']);
        $this->assertSame(0, $capturedMap['a3']);

        // Severity buckets : 'OPEN' + 'owasp-a1' + 'BLOCKER' → a1_blocker=1 ;
        // l'issue CLOSED sur a1 ne doit PAS être comptée (filtre status)
        $this->assertSame(1, $capturedMap['a1_blocker']);
        $this->assertSame(0, $capturedMap['a1_major']); // CLOSED ignorée
        $this->assertSame(1, $capturedMap['a5_critical']);
    }

    // ─── helpers ─────────────────────────────────────────────────────────────

    /**
     * Construit un payload SonarQube /api/issues/search minimal exploitable
     * par le controller.
     */
    private function buildOwaspPayload(int $total = 0, array $facets = [], array $issues = []): array
    {
        return [
            'total' => $total,
            'effortTotal' => 10,
            'facets' => [
                ['values' => $facets],
            ],
            'issues' => $issues,
        ];
    }

    /**
     * Stubs le happy path jusqu'à la persistence (sonar + info projet OK).
     */
    private function stubOk(string $sonarVersion): void
    {
        $this->parameterBag->method('get')->willReturnMap([
            ['sonar.url', self::SONAR_URL],
            ['sonar.version', $sonarVersion],
        ]);

        $this->client->method('httpSonarQube')
            ->willReturn(['code' => 200, 'json' => $this->buildOwaspPayload(1)]);

        $this->infoRepo->method('selectInformationProjetVersion')
            ->willReturn(['code' => 200, 'info' => [
                'date' => '2026-04-22',
                'version' => '1.0',
            ]]);
    }
}
