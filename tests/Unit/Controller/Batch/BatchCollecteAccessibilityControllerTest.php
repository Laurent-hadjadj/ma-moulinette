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

use App\Controller\Batch\BatchCollecteAccessibilityController;
use App\Repository\AccessibilityRepository;
use App\Service\{ClientService, DateTools, GetSonarVersionService, LanguageDistributionService, UrlBuilderService};
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * MODIF 26/10/2026 : Accessibilité (tag SonarQube `accessibility`).
 * Pattern calqué sur BatchCollecteEmpreinteEcologiqueControllerTest, en plus
 * simple : pas de feature-flag (pas de test "désactivé"), pas d'étape
 * rules/search préalable (tags=accessibility&languages=X directement sur
 * issues/search) — donc measures/component (1 appel) + 4 appels issues/search
 * (un par langage css/web/js/ts), au lieu de rules/search + measures + N appels.
 */
#[AllowMockObjectsWithoutExpectations]
class BatchCollecteAccessibilityControllerTest extends TestCase
{
    private const MAVEN_KEY = 'fr.ma-moulinette:ma-moulinette';

    /** @var EntityManagerInterface&MockObject */          private MockObject $em;
    /** @var ClientService&MockObject */                    private MockObject $client;
    /** @var DateTools&MockObject */                        private MockObject $dateTools;
    /* LanguageDistributionService est final et sans dépendances (pur parsing) :
     * on utilise une vraie instance plutôt qu'un mock. */
    private LanguageDistributionService $languageDistribution;
    /** @var UrlBuilderService&MockObject */                private MockObject $urlBuilder;
    /** @var GetSonarVersionService&MockObject */           private MockObject $sonarVersion;
    /** @var LoggerInterface&MockObject */                  private MockObject $logger;
    /** @var AccessibilityRepository&MockObject */          private MockObject $repo;
    /** @var ParameterBagInterface&MockObject */            private MockObject $parameterBag;

    private BatchCollecteAccessibilityController $controller;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->client = $this->createMock(ClientService::class);
        $this->dateTools = $this->createMock(DateTools::class);
        $this->languageDistribution = new LanguageDistributionService();
        $this->urlBuilder = $this->createMock(UrlBuilderService::class);
        $this->sonarVersion = $this->createMock(GetSonarVersionService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->repo = $this->createMock(AccessibilityRepository::class);
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);

        $this->em->method('getRepository')->willReturn($this->repo);

        $this->urlBuilder->method('build')->willReturn('https://sonar.example.com/api/...');
        $this->sonarVersion->method('componentKeyParam')->willReturn('components');
        $this->sonarVersion->method('issueStatusParam')->willReturn(['param' => 'issueStatuses', 'value' => 'OPEN,CONFIRMED,ACCEPTED']);
        $this->dateTools->method('minutesTo')->willReturn('0h:0min');

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnMap([['parameter_bag', true]]);
        $container->method('get')->willReturnMap([['parameter_bag', 1, $this->parameterBag]]);

        $this->controller = new BatchCollecteAccessibilityController(
            $this->em,
            $this->client,
            $this->dateTools,
            $this->languageDistribution,
            $this->urlBuilder,
            $this->sonarVersion,
            $this->logger
        );
        $this->controller->setContainer($container);
        $this->parameterBag->method('get')->willReturnMap([
            ['sonar.url', 'https://sonar.example.com'],
        ]);
    }

    public function testReturnsErrorWhenMeasuresCallFails(): void
    {
        $this->client->expects($this->once())
            ->method('httpSonarQube')
            ->willReturn(['code' => 500, 'erreur' => 'sonar down']);

        $this->repo->expects($this->never())->method('deleteAccessibilityMavenKey');

        $result = $this->controller->BatchCollecteAccessibility(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(500, $result['code']);
    }

    public function testReturnsErrorWhenIssuesSearchFails(): void
    {
        $this->client->expects($this->exactly(2))
            ->method('httpSonarQube')
            ->willReturnOnConsecutiveCalls(
                ['code' => 200, 'json' => ['component' => ['measures' => [
                    ['metric' => 'ncloc', 'value' => '1000'],
                    ['metric' => 'ncloc_language_distribution', 'value' => 'css=1000'],
                ]]]],
                ['code' => 500, 'erreur' => 'sonar down'],
            );

        $this->repo->expects($this->never())->method('deleteaccessibilityMavenKey');

        $result = $this->controller->BatchCollecteAccessibility(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(500, $result['code']);
    }

    public function testReturnsErrorWhenDeleteFails(): void
    {
        // Seul 'css' a du ncloc dans la distribution : web/js/ts sont ignorés sans appel API
        // (langage absent du projet), donc 2 appels seulement (measures + css).
        $this->client->expects($this->exactly(2))
            ->method('httpSonarQube')
            ->willReturnOnConsecutiveCalls(
                ['code' => 200, 'json' => ['component' => ['measures' => [
                    ['metric' => 'ncloc', 'value' => '1000'],
                    ['metric' => 'ncloc_language_distribution', 'value' => 'css=1000'],
                ]]]],
                ['code' => 200, 'json' => ['total' => 42, 'effortTotal' => 120]],
            );

        $this->repo->expects($this->once())
            ->method('deleteAccessibilityMavenKey')
            ->willReturn(['code' => 500, 'erreur' => 'delete failed']);

        $this->repo->expects($this->never())->method('insertAccessibility');

        $result = $this->controller->BatchCollecteAccessibility(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(500, $result['code']);
    }

    public function testHappyPathAllLanguagesPersistsAndReturnsHistorique(): void
    {
        // Les 4 langages sont présents dans la distribution : measures, puis
        // css, web, js, ts (self::$langues), un appel issues/search chacun.
        $this->client->expects($this->exactly(5))
            ->method('httpSonarQube')
            ->willReturnOnConsecutiveCalls(
                ['code' => 200, 'json' => ['component' => ['measures' => [
                    ['metric' => 'ncloc', 'value' => '2600'],
                    ['metric' => 'ncloc_language_distribution', 'value' => 'css=1000;web=500;js=800;ts=300'],
                ]]]],
                ['code' => 200, 'json' => ['total' => 42, 'effortTotal' => 120]], // css : 0,4% -> A
                ['code' => 200, 'json' => ['total' => 0, 'effortTotal' => 0]],    // web : 0% -> A
                ['code' => 200, 'json' => ['total' => 5, 'effortTotal' => 600]],  // js : 2,5% -> A
                ['code' => 200, 'json' => ['total' => 0, 'effortTotal' => 0]],    // ts : 0% -> A
            );

        $this->repo->method('deleteAccessibilityMavenKey')->willReturn(['code' => 200]);

        $capturedInsert = [];
        $this->repo->expects($this->once())
            ->method('insertAccessibility')
            ->with($this->callback(function (array $data) use (&$capturedInsert) {
                $capturedInsert = $data;
                return true;
            }))
            ->willReturn(['code' => 200]);

        $result = $this->controller->BatchCollecteAccessibility(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(200, $result['code']);
        $this->assertSame(47, $result['historique']['accessibility_anomalies']);
        $this->assertSame(720, $result['historique']['accessibility_dette_minute']);
        $this->assertSame('A', $result['historique']['accessibility_rating']);
        $this->assertSame(42, $result['historique']['accessibility_css_anomalies']);
        $this->assertSame(120, $result['historique']['accessibility_css_dette_minute']);
        $this->assertSame('A', $result['historique']['accessibility_css_rating']);
        $this->assertSame(0, $result['historique']['accessibility_web_anomalies']);
        $this->assertSame('A', $result['historique']['accessibility_web_rating']);
        $this->assertSame(5, $result['historique']['accessibility_javascript_anomalies']);
        $this->assertSame('A', $result['historique']['accessibility_javascript_rating']);
        $this->assertSame(0, $result['historique']['accessibility_typescript_anomalies']);
        $this->assertSame('A', $result['historique']['accessibility_typescript_rating']);

        $this->assertSame(self::MAVEN_KEY, $capturedInsert['maven_key']);
        $this->assertSame(47, $capturedInsert['accessibility_anomalies']);
        $this->assertSame('A', $capturedInsert['accessibility_rating']);
        $this->assertSame('auto', $capturedInsert['mode_collecte']);
        $this->assertSame('u', $capturedInsert['utilisateur_collecte']);
    }

    public function testLanguageAbsentFromProjectYieldsNullNotZero(): void
    {
        // Seul 'css' a du ncloc : web/js/ts n'existent pas dans le projet, pas d'appel
        // issues/search pour eux, et anomalies/dette_minute/rating doivent rester null
        // (et non 0, qui serait une vraie mesure "0 anomalie trouvée").
        $this->client->expects($this->exactly(2))
            ->method('httpSonarQube')
            ->willReturnOnConsecutiveCalls(
                ['code' => 200, 'json' => ['component' => ['measures' => [
                    ['metric' => 'ncloc', 'value' => '1000'],
                    ['metric' => 'ncloc_language_distribution', 'value' => 'css=1000'],
                ]]]],
                ['code' => 200, 'json' => ['total' => 42, 'effortTotal' => 120]],
            );

        $this->repo->method('deleteAccessibilityMavenKey')->willReturn(['code' => 200]);
        $this->repo->method('insertAccessibility')->willReturn(['code' => 200]);

        $result = $this->controller->BatchCollecteAccessibility(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(200, $result['code']);
        $this->assertSame(42, $result['historique']['accessibility_css_anomalies']);
        $this->assertSame('A', $result['historique']['accessibility_css_rating']);
        $this->assertNull($result['historique']['accessibility_web_anomalies']);
        $this->assertNull($result['historique']['accessibility_web_dette_minute']);
        $this->assertNull($result['historique']['accessibility_web_rating']);
        $this->assertNull($result['historique']['accessibility_javascript_anomalies']);
        $this->assertNull($result['historique']['accessibility_javascript_rating']);
        $this->assertNull($result['historique']['accessibility_typescript_anomalies']);
        $this->assertNull($result['historique']['accessibility_typescript_rating']);
    }

    public function testGetAccessibilityRatingThresholds(): void
    {
        $reflection = new \ReflectionMethod(BatchCollecteAccessibilityController::class, 'getaccessibilityRating');

        // ncloc=1000, coût=30min/ligne -> 30000 min de référence
        $this->assertSame('A', $reflection->invoke($this->controller, 1000, 1000));    // 3.3%
        $this->assertSame('B', $reflection->invoke($this->controller, 2500, 1000));    // 8.3%
        $this->assertSame('C', $reflection->invoke($this->controller, 5000, 1000));    // 16.7%
        $this->assertSame('D', $reflection->invoke($this->controller, 12000, 1000));   // 40%
        $this->assertSame('E', $reflection->invoke($this->controller, 20000, 1000));   // 66.7%
        $this->assertNull($reflection->invoke($this->controller, 1000, null));
        $this->assertNull($reflection->invoke($this->controller, 1000, 0));
    }
}
