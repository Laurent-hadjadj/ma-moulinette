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

use App\Controller\Batch\BatchCollecteEmpreinteEcologiqueController;
use App\Repository\EcoConceptionRepository;
use App\Service\{ClientService, DateTools, LanguageDistributionService, UrlBuilderService};
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * MODIF 2026-10-08 : Empreinte Écologique (plugins SonarQube Creedengo).
 * Pattern calqué sur BatchCollecteLoggerControllerTest (propriété d'activation
 * applicative identique). MODIF 2026-10-09 : note A-E + détail par langage.
 */
#[AllowMockObjectsWithoutExpectations]
class BatchCollecteEmpreinteEcologiqueControllerTest extends TestCase
{
    private const MAVEN_KEY = 'fr.ma-moulinette:ma-moulinette';

    /** @var EntityManagerInterface&MockObject */          private MockObject $em;
    /** @var ClientService&MockObject */                    private MockObject $client;
    /** @var DateTools&MockObject */                        private MockObject $dateTools;
    /* LanguageDistributionService est final et sans dépendances (pur parsing) :
     * on utilise une vraie instance plutôt qu'un mock. */
    private LanguageDistributionService $languageDistribution;
    /** @var UrlBuilderService&MockObject */                private MockObject $urlBuilder;
    /** @var LoggerInterface&MockObject */                  private MockObject $logger;
    /** @var EcoConceptionRepository&MockObject */          private MockObject $repo;
    /** @var ParameterBagInterface&MockObject */            private MockObject $parameterBag;

    private BatchCollecteEmpreinteEcologiqueController $controller;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->client = $this->createMock(ClientService::class);
        $this->dateTools = $this->createMock(DateTools::class);
        $this->languageDistribution = new LanguageDistributionService();
        $this->urlBuilder = $this->createMock(UrlBuilderService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->repo = $this->createMock(EcoConceptionRepository::class);
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);

        // Pas de ->with()/->expects() : certains tests (ex. seuils de notation
        // via reflection) n'invoquent jamais BatchCollecteEmpreinteEcologique()
        // et n'appellent donc jamais getRepository().
        $this->em->method('getRepository')->willReturn($this->repo);

        $this->urlBuilder->method('build')->willReturn('https://sonar.example.com/api/...');
        $this->dateTools->method('minutesTo')->willReturn('0h:0min');

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnMap([['parameter_bag', true]]);
        $container->method('get')->willReturnMap([['parameter_bag', 1, $this->parameterBag]]);

        $this->controller = new BatchCollecteEmpreinteEcologiqueController(
            $this->em, $this->client, $this->dateTools, $this->languageDistribution, $this->urlBuilder, $this->logger
        );
        $this->controller->setContainer($container);
    }

    public function testReturns404WhenCreedengoDisabled(): void
    {
        $this->parameterBag->method('get')->willReturnMap([
            ['sonar.url', 'https://sonar.example.com'],
            ['creedengo.enabled', 'false'],
        ]);

        // Aucun appel HTTP ne doit partir
        $this->client->expects($this->never())->method('httpSonarQube');
        $this->repo->expects($this->never())->method('insertEcoConception');

        $result = $this->controller->BatchCollecteEmpreinteEcologique(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(404, $result['code']);
        $this->assertNull($result['historique']['eco_anomalies']);
        $this->assertNull($result['historique']['eco_rating']);
        $this->assertNull($result['historique']['eco_java_anomalies']);
        $this->assertNull($result['historique']['eco_python_rating']);
    }

    public function testReturnsErrorWhenRulesSearchFails(): void
    {
        $this->parameterBag->method('get')->willReturnMap([
            ['sonar.url', 'https://sonar.example.com'],
            ['creedengo.enabled', 'true'],
        ]);

        $this->client->expects($this->once())
            ->method('httpSonarQube')
            ->willReturn(['code' => 500, 'erreur' => 'sonar down']);

        $result = $this->controller->BatchCollecteEmpreinteEcologique(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(500, $result['code']);
    }

    public function testReturnsNeutralWhenNoCreedengoRulesOnServer(): void
    {
        $this->parameterBag->method('get')->willReturnMap([
            ['sonar.url', 'https://sonar.example.com'],
            ['creedengo.enabled', 'true'],
        ]);

        // Un langage hors périmètre (ex. ts) n'est pas retenu -> rulesByLang vide
        $this->client->expects($this->once())
            ->method('httpSonarQube')
            ->willReturn(['code' => 200, 'json' => ['rules' => [['key' => 'creedengo-typescript:GCI1', 'lang' => 'ts']]]]);

        $this->repo->expects($this->never())->method('deleteEcoConceptionMavenKey');

        $result = $this->controller->BatchCollecteEmpreinteEcologique(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(200, $result['code']);
        $this->assertNull($result['historique']['eco_anomalies']);
        $this->assertNull($result['historique']['eco_rating']);
    }

    public function testReturnsErrorWhenMeasuresCallFails(): void
    {
        $this->parameterBag->method('get')->willReturnMap([
            ['sonar.url', 'https://sonar.example.com'],
            ['creedengo.enabled', 'true'],
        ]);

        $this->client->expects($this->exactly(2))
            ->method('httpSonarQube')
            ->willReturnOnConsecutiveCalls(
                ['code' => 200, 'json' => ['rules' => [['key' => 'creedengo-java:GCI3', 'lang' => 'java']]]],
                ['code' => 500, 'erreur' => 'sonar down'],
            );

        $result = $this->controller->BatchCollecteEmpreinteEcologique(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(500, $result['code']);
    }

    public function testReturnsErrorWhenIssuesSearchFails(): void
    {
        $this->parameterBag->method('get')->willReturnMap([
            ['sonar.url', 'https://sonar.example.com'],
            ['creedengo.enabled', 'true'],
        ]);

        $this->client->expects($this->exactly(3))
            ->method('httpSonarQube')
            ->willReturnOnConsecutiveCalls(
                ['code' => 200, 'json' => ['rules' => [['key' => 'creedengo-java:GCI3', 'lang' => 'java']]]],
                ['code' => 200, 'json' => ['component' => ['measures' => [
                    ['metric' => 'ncloc', 'value' => '1000'],
                    ['metric' => 'ncloc_language_distribution', 'value' => 'java=1000'],
                ]]]],
                ['code' => 500, 'erreur' => 'sonar down'],
            );

        $this->repo->expects($this->never())->method('deleteEcoConceptionMavenKey');

        $result = $this->controller->BatchCollecteEmpreinteEcologique(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(500, $result['code']);
    }

    public function testReturnsErrorWhenDeleteFails(): void
    {
        $this->parameterBag->method('get')->willReturnMap([
            ['sonar.url', 'https://sonar.example.com'],
            ['creedengo.enabled', 'true'],
        ]);

        $this->client->expects($this->exactly(3))
            ->method('httpSonarQube')
            ->willReturnOnConsecutiveCalls(
                ['code' => 200, 'json' => ['rules' => [['key' => 'creedengo-java:GCI3', 'lang' => 'java']]]],
                ['code' => 200, 'json' => ['component' => ['measures' => [
                    ['metric' => 'ncloc', 'value' => '1000'],
                    ['metric' => 'ncloc_language_distribution', 'value' => 'java=1000'],
                ]]]],
                ['code' => 200, 'json' => ['total' => 42, 'effortTotal' => 120]],
            );

        $this->repo->expects($this->once())
            ->method('deleteEcoConceptionMavenKey')
            ->willReturn(['code' => 500, 'erreur' => 'delete failed']);

        $this->repo->expects($this->never())->method('insertEcoConception');

        $result = $this->controller->BatchCollecteEmpreinteEcologique(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(500, $result['code']);
    }

    public function testHappyPathSingleLanguagePersistsAndReturnsHistorique(): void
    {
        $this->parameterBag->method('get')->willReturnMap([
            ['sonar.url', 'https://sonar.example.com'],
            ['creedengo.enabled', 'true'],
        ]);

        // 120 min de dette / (1000 ncloc * 30 min) = 0,4% -> rating A
        $this->client->expects($this->exactly(3))
            ->method('httpSonarQube')
            ->willReturnOnConsecutiveCalls(
                ['code' => 200, 'json' => ['rules' => [['key' => 'creedengo-java:GCI3', 'lang' => 'java']]]],
                ['code' => 200, 'json' => ['component' => ['measures' => [
                    ['metric' => 'ncloc', 'value' => '1000'],
                    ['metric' => 'ncloc_language_distribution', 'value' => 'java=1000'],
                ]]]],
                ['code' => 200, 'json' => ['total' => 42, 'effortTotal' => 120]],
            );

        $this->repo->method('deleteEcoConceptionMavenKey')->willReturn(['code' => 200]);

        $capturedInsert = [];
        $this->repo->expects($this->once())
            ->method('insertEcoConception')
            ->with($this->callback(function (array $data) use (&$capturedInsert) {
                $capturedInsert = $data;
                return true;
            }))
            ->willReturn(['code' => 200]);

        $result = $this->controller->BatchCollecteEmpreinteEcologique(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(200, $result['code']);
        $this->assertSame(42, $result['historique']['eco_anomalies']);
        $this->assertSame(120, $result['historique']['eco_dette_minute']);
        $this->assertSame('A', $result['historique']['eco_rating']);
        $this->assertSame(42, $result['historique']['eco_java_anomalies']);
        $this->assertSame(120, $result['historique']['eco_java_dette_minute']);
        $this->assertSame('A', $result['historique']['eco_java_rating']);
        // Pas de code JS/PHP/Python détecté : null, pas 0 (distinct d'un langage réellement vide)
        $this->assertNull($result['historique']['eco_javascript_anomalies']);
        $this->assertNull($result['historique']['eco_php_anomalies']);
        $this->assertNull($result['historique']['eco_python_anomalies']);

        $this->assertSame(self::MAVEN_KEY, $capturedInsert['maven_key']);
        $this->assertSame(42, $capturedInsert['eco_anomalies']);
        $this->assertSame('A', $capturedInsert['eco_rating']);
        $this->assertSame('auto', $capturedInsert['mode_collecte']);
        $this->assertSame('u', $capturedInsert['utilisateur_collecte']);
    }

    public function testGetEcoRatingThresholds(): void
    {
        $reflection = new \ReflectionMethod(BatchCollecteEmpreinteEcologiqueController::class, 'getEcoRating');

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
