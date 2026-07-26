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
    private const MAVEN_KEY = 'fr.ma-moulinette:ma-moulinette';
    private const SONAR_URL = 'https://sonar.example.com';
    private const BUILT_URL_2017 = 'https://sonar.example.com/api/issues/search?...owasp2017';
    private const BUILT_URL_2021 = 'https://sonar.example.com/api/issues/search?...owasp2021';
    private const BUILT_URL_TAG_FALLBACK = 'https://sonar.example.com/api/issues/search?...tags';

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

        // url-builder renvoie des URL distinctes selon owaspTop10/owaspTop10-2021/tags (secours)
        $this->urlBuilder->method('build')
            ->willReturnCallback(function (string $base, string $path, array $params) {
                if (isset($params['tags'])) {
                    return self::BUILT_URL_TAG_FALLBACK;
                }
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

    /**
     * @return array<int, array{0: int}>
     */
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

        /* MODIF 2026-05-07 : init [] (intelephense by-ref). */
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

    // ─── 10. Secours par tag quand la facette officielle ne classe rien (MODIF 2026-07-18) ─

    public function testFallsBackToTagCountWhenFacetTotalIsZero(): void
    {
        $this->parameterBag->method('get')->willReturnMap([
            ['sonar.url', self::SONAR_URL],
            ['sonar.version', '10'],
        ]);

        $this->client->expects($this->exactly(3))
            ->method('httpSonarQube')
            ->willReturnCallback(function (string $url) {
                if ($url === self::BUILT_URL_2017) {
                    return ['code' => 200, 'json' => $this->buildOwaspPayload(0)];
                }
                if ($url === self::BUILT_URL_2021) {
                    return ['code' => 200, 'json' => $this->buildOwaspPayload(
                        total: 5,
                        facets: [['val' => 'a3', 'count' => 5]]
                    )];
                }
                // secours par tag (owasp-a01/owasp-a04, zéro-paddés)
                return ['code' => 200, 'json' => [
                    'total' => 2,
                    'effortTotal' => 20,
                    'issues' => [
                        ['status' => 'OPEN', 'severity' => 'BLOCKER', 'tags' => ['owasp-a01']],
                        ['status' => 'OPEN', 'severity' => 'MAJOR', 'tags' => ['owasp-a04']],
                    ],
                ]];
            });

        $this->infoRepo->method('selectInformationProjetVersion')->willReturn(['code' => 200, 'info' => [
            'date' => '2026-04-22', 'version' => '1.0',
        ]]);
        $this->owaspRepo->method('deleteOwaspMavenKey')->willReturn(['code' => 200]);

        $capturedList = [];
        $this->owaspRepo->expects($this->once())
            ->method('insertOwasp')
            ->with($this->callback(function (array $list) use (&$capturedList) {
                $capturedList = $list;
                return true;
            }))
            ->willReturn(['code' => 200]);

        $result = $this->controller->BatchCollecteOwasp(self::MAVEN_KEY, 'manual', 'admin');

        $this->assertSame(200, $result['code']);
        // 2017 : facette vide -> secours par tag (2 violations : a1, a4)
        $this->assertSame(2, $result['owasp2017']);
        $this->assertSame('tag', $capturedList[0]['source']);
        $this->assertSame(1, $capturedList[0]['a1']);
        $this->assertSame(1, $capturedList[0]['a4']);
        $this->assertSame(1, $capturedList[0]['a1_blocker']);
        $this->assertSame(1, $capturedList[0]['a4_major']);

        // 2021 : facette officielle non vide, pas de secours
        $this->assertSame(5, $result['owasp2021']);
        $this->assertSame('facet', $capturedList[1]['source']);
        $this->assertSame(5, $capturedList[1]['a3']);
    }

    public function testTagFallbackIsMemoizedAcrossBothReferentials(): void
    {
        $this->parameterBag->method('get')->willReturnMap([
            ['sonar.url', self::SONAR_URL],
            ['sonar.version', '10'],
        ]);

        $tagCallCount = 0;
        $this->client->expects($this->exactly(3))
            ->method('httpSonarQube')
            ->willReturnCallback(function (string $url) use (&$tagCallCount) {
                if ($url === self::BUILT_URL_TAG_FALLBACK) {
                    $tagCallCount++;
                    return ['code' => 200, 'json' => [
                        'total' => 1,
                        'effortTotal' => 5,
                        'issues' => [
                            ['status' => 'OPEN', 'severity' => 'MINOR', 'tags' => ['owasp-a02']],
                        ],
                    ]];
                }
                return ['code' => 200, 'json' => $this->buildOwaspPayload(0)];
            });

        $this->infoRepo->method('selectInformationProjetVersion')->willReturn(['code' => 200, 'info' => [
            'date' => '2026-04-22', 'version' => '1.0',
        ]]);
        $this->owaspRepo->method('deleteOwaspMavenKey')->willReturn(['code' => 200]);

        $capturedList = [];
        $this->owaspRepo->expects($this->once())
            ->method('insertOwasp')
            ->with($this->callback(function (array $list) use (&$capturedList) {
                $capturedList = $list;
                return true;
            }))
            ->willReturn(['code' => 200]);

        $this->controller->BatchCollecteOwasp(self::MAVEN_KEY, 'manual', 'admin');

        // Un seul appel HTTP de secours, réutilisé pour 2017 ET 2021 (le tag
        // n'est pas spécifique à un référentiel).
        $this->assertSame(1, $tagCallCount);
        $this->assertSame('tag', $capturedList[0]['source']);
        $this->assertSame('tag', $capturedList[1]['source']);
        $this->assertSame(1, $capturedList[0]['a2']);
        $this->assertSame(1, $capturedList[1]['a2']);
    }

    public function testKeepsSourceFacetWhenTagFallbackAlsoFindsNothing(): void
    {
        $this->parameterBag->method('get')->willReturnMap([
            ['sonar.url', self::SONAR_URL],
            ['sonar.version', '8'],
        ]);

        $this->client->expects($this->exactly(2))
            ->method('httpSonarQube')
            ->willReturnCallback(function (string $url) {
                if ($url === self::BUILT_URL_TAG_FALLBACK) {
                    return ['code' => 200, 'json' => ['total' => 0, 'effortTotal' => 0, 'issues' => []]];
                }
                return ['code' => 200, 'json' => $this->buildOwaspPayload(0)];
            });

        $this->infoRepo->method('selectInformationProjetVersion')->willReturn(['code' => 200, 'info' => [
            'date' => '2026-04-22', 'version' => '1.0',
        ]]);
        $this->owaspRepo->method('deleteOwaspMavenKey')->willReturn(['code' => 200]);

        $capturedList = [];
        $this->owaspRepo->expects($this->once())
            ->method('insertOwasp')
            ->with($this->callback(function (array $list) use (&$capturedList) {
                $capturedList = $list;
                return true;
            }))
            ->willReturn(['code' => 200]);

        $this->controller->BatchCollecteOwasp(self::MAVEN_KEY, 'manual', 'admin');

        $this->assertSame('facet', $capturedList[0]['source']);
        $this->assertSame(0, $capturedList[0]['a1']);
    }

    public function testTagFallbackHttpFailureDoesNotCrashAndKeepsSourceFacet(): void
    {
        $this->parameterBag->method('get')->willReturnMap([
            ['sonar.url', self::SONAR_URL],
            ['sonar.version', '8'],
        ]);

        $this->client->expects($this->exactly(2))
            ->method('httpSonarQube')
            ->willReturnCallback(function (string $url) {
                if ($url === self::BUILT_URL_TAG_FALLBACK) {
                    return ['code' => 500, 'erreur' => 'tag fallback failed'];
                }
                return ['code' => 200, 'json' => $this->buildOwaspPayload(0)];
            });

        $this->infoRepo->method('selectInformationProjetVersion')->willReturn(['code' => 200, 'info' => [
            'date' => '2026-04-22', 'version' => '1.0',
        ]]);
        $this->owaspRepo->method('deleteOwaspMavenKey')->willReturn(['code' => 200]);

        $this->logger->expects($this->atLeastOnce())->method('warning');

        $capturedList = [];
        $this->owaspRepo->expects($this->once())
            ->method('insertOwasp')
            ->with($this->callback(function (array $list) use (&$capturedList) {
                $capturedList = $list;
                return true;
            }))
            ->willReturn(['code' => 200]);

        $result = $this->controller->BatchCollecteOwasp(self::MAVEN_KEY, 'manual', 'admin');

        $this->assertSame(200, $result['code']);
        $this->assertSame('facet', $capturedList[0]['source']);
    }

    // ─── 11. facets structurellement absent alors que total > 0 (MODIF 2026-07-26) ─
    // Cas réel observé : SonarQube peut renvoyer total > 0 avec facets: [] (pas
    // même de facets[0]) quand la requête ne demande pas explicitement la
    // facette owaspTop10 — ce que fait le double d'e2e SonarFixtureClientService,
    // qui réutilise le même fixture issues/search.json pour tous les endpoints
    // /api/issues/search. Avant fix : facets[0]['values'] plantait (Undefined
    // array key 0 → ErrorException fatale, cf. BatchCollecteOwaspControllerTest
    // ne le couvrait pas car buildOwaspPayload() garantit toujours facets[0]).
    public function testFallsBackToTagCountWhenFacetsArrayIsStructurallyEmpty(): void
    {
        $this->parameterBag->method('get')->willReturnMap([
            ['sonar.url', self::SONAR_URL],
            ['sonar.version', '8'],
        ]);

        $this->client->expects($this->exactly(2))
            ->method('httpSonarQube')
            ->willReturnCallback(function (string $url) {
                if ($url === self::BUILT_URL_TAG_FALLBACK) {
                    return ['code' => 200, 'json' => [
                        'total' => 1,
                        'effortTotal' => 5,
                        'issues' => [
                            ['status' => 'OPEN', 'severity' => 'MAJOR', 'tags' => ['owasp-a07']],
                        ],
                    ]];
                }
                // total > 0 mais facets structurellement vide (pas de facets[0]).
                return ['code' => 200, 'json' => [
                    'total' => 132,
                    'effortTotal' => 40,
                    'facets' => [],
                    'issues' => [],
                ]];
            });

        $this->infoRepo->method('selectInformationProjetVersion')->willReturn(['code' => 200, 'info' => [
            'date' => '2026-04-22', 'version' => '1.0',
        ]]);
        $this->owaspRepo->method('deleteOwaspMavenKey')->willReturn(['code' => 200]);

        $capturedList = [];
        $this->owaspRepo->expects($this->once())
            ->method('insertOwasp')
            ->with($this->callback(function (array $list) use (&$capturedList) {
                $capturedList = $list;
                return true;
            }))
            ->willReturn(['code' => 200]);

        $result = $this->controller->BatchCollecteOwasp(self::MAVEN_KEY, 'manual', 'admin');

        $this->assertSame(200, $result['code']);
        $this->assertSame('tag', $capturedList[0]['source']);
        $this->assertSame(1, $capturedList[0]['a7']);
        $this->assertSame(1, $capturedList[0]['a7_major']);
    }

    // ─── helpers ─────────────────────────────────────────────────────────────

    /**
     * Construit un payload SonarQube /api/issues/search minimal exploitable
     * par le controller.
     *
     * @param array<int, array{val: string, count: int}>                       $facets
     * @param array<int, array{status: string, severity: string, tags: array<int, string>}> $issues
     *
     * @return array{total: int, effortTotal: int, facets: array<int, array{values: array<int, array{val: string, count: int}>}>, issues: array<int, array{status: string, severity: string, tags: array<int, string>}>}
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
