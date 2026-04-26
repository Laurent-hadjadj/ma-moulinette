<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Projet;

use App\Controller\Projet\ApiPeintureController;
use App\Entity\Anomalie;
use App\Entity\AnomalieDetails;
use App\Entity\Hotspots;
use App\Entity\InformationProjet;
use App\Entity\Logger as LoggerEntity;
use App\Entity\Mesures;
use App\Entity\NoSonar;
use App\Entity\Todo;
use App\Entity\Utilisateur;
use App\Repository\AnomalieDetailsRepository;
use App\Repository\AnomalieRepository;
use App\Repository\HotspotsRepository;
use App\Repository\InformationProjetRepository;
use App\Repository\LoggerRepository;
use App\Repository\MesuresRepository;
use App\Repository\NoSonarRepository;
use App\Repository\TodoRepository;
use App\Service\IsValideMavenKey;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

#[AllowMockObjectsWithoutExpectations]
class ApiPeintureControllerTest extends TestCase
{
    private const MAVEN_KEY = 'com.acme:app';

    /** @var EntityManagerInterface&MockObject */
    private MockObject $em;
    /** @var IsValideMavenKey&MockObject */
    private MockObject $isValide;
    /** @var LoggerInterface&MockObject */
    private MockObject $logger;

    /** @var AnomalieRepository&MockObject */       private MockObject $anomalieRepo;
    /** @var AnomalieDetailsRepository&MockObject */ private MockObject $anomalieDetailsRepo;
    /** @var InformationProjetRepository&MockObject */ private MockObject $infoRepo;
    /** @var MesuresRepository&MockObject */         private MockObject $mesuresRepo;
    /** @var HotspotsRepository&MockObject */        private MockObject $hotspotsRepo;
    /** @var NoSonarRepository&MockObject */         private MockObject $noSonarRepo;
    /** @var TodoRepository&MockObject */            private MockObject $todoRepo;
    /** @var LoggerRepository&MockObject */          private MockObject $loggerRepo;

    private ApiPeintureController $controller;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->isValide = $this->createMock(IsValideMavenKey::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->anomalieRepo = $this->createMock(AnomalieRepository::class);
        $this->anomalieDetailsRepo = $this->createMock(AnomalieDetailsRepository::class);
        $this->infoRepo = $this->createMock(InformationProjetRepository::class);
        $this->mesuresRepo = $this->createMock(MesuresRepository::class);
        $this->hotspotsRepo = $this->createMock(HotspotsRepository::class);
        $this->noSonarRepo = $this->createMock(NoSonarRepository::class);
        $this->todoRepo = $this->createMock(TodoRepository::class);
        $this->loggerRepo = $this->createMock(LoggerRepository::class);

        $this->em->method('getRepository')->willReturnMap([
            [Anomalie::class, $this->anomalieRepo],
            [AnomalieDetails::class, $this->anomalieDetailsRepo],
            [InformationProjet::class, $this->infoRepo],
            [Mesures::class, $this->mesuresRepo],
            [Hotspots::class, $this->hotspotsRepo],
            [NoSonar::class, $this->noSonarRepo],
            [Todo::class, $this->todoRepo],
            [LoggerEntity::class, $this->loggerRepo],
        ]);

        // Container pour AbstractController::json()
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(false);

        $this->controller = new ApiPeintureController($this->em, $this->isValide, $this->logger);
        $this->controller->setContainer($container);
    }

    // ═══════════════════════ projetMesApplicationsListe ═══════════════════

    public function testProjetMesApplicationsListeReturns400WhenBodyIsInvalidJson(): void
    {
        $security = $this->createMock(Security::class);
        $response = $this->controller->projetMesApplicationsListe(
            new Request([], [], [], [], [], [], 'not-json'),
            $security
        );

        $this->assertJsonStatus($response, 400, 'alert');
    }

    public function testProjetMesApplicationsListeReturns406WhenEmpty(): void
    {
        $this->anomalieRepo->expects($this->once())
            ->method('selectAnomalieByProjectName')
            ->willReturn(['code' => 200, 'liste' => []]);

        $response = $this->controller->projetMesApplicationsListe(
            $this->jsonRequest(['dummy' => 1]),
            $this->createMock(Security::class)
        );

        $this->assertJsonStatus($response, 406, 'primary');
    }

    public function testProjetMesApplicationsListeReturnsFilteredFavoris(): void
    {
        $this->anomalieRepo->expects($this->once())
            ->method('selectAnomalieByProjectName')
            ->willReturn(['code' => 200, 'liste' => [
                ['key' => 'com.acme:app'],
                ['key' => 'com.acme:api'],
            ]]);

        $user = $this->createMock(Utilisateur::class);
        $user->method('getPreference')->willReturn([
            'favori_projet' => ['com.acme:app', 'com.acme:api', 'com.acme:absent'],
            'favori_version' => ['com.acme:app' => '1.0.0'],
        ]);
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);

        $response = $this->controller->projetMesApplicationsListe(
            $this->jsonRequest(['whatever' => 1]),
            $security
        );

        $payload = $this->decode($response);
        $this->assertSame(200, $payload['code']);

        // 'absent' ignoré (pas dans liste) ; 'app' favori=true, 'api' favori=false
        $this->assertCount(2, $payload['projets']);
        $byKey = [];
        foreach ($payload['projets'] as $p) {
            $byKey[$p['key']] = $p;
        }
        $this->assertTrue($byKey['com.acme:app']['favori']);
        $this->assertFalse($byKey['com.acme:api']['favori']);
    }

    // ═══════════════════════ peintureProjetVersion ═════════════════════════

    public function testPeintureProjetVersionReturns400WhenMavenKeyMissing(): void
    {
        $response = $this->controller->peintureProjetVersion($this->jsonRequest([]));

        $this->assertJsonStatus($response, 400, 'alert');
    }

    public function testPeintureProjetVersionReturns404WhenProjectIsInvalid(): void
    {
        $this->isValide->method('isValideInformation')->willReturn(['code' => 404]);

        $response = $this->controller->peintureProjetVersion($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
        ]));

        $this->assertJsonStatus($response, 404, 'warning');
    }

    public function testPeintureProjetVersionReturnsDatasetFromRepository(): void
    {
        $this->isValide->method('isValideInformation')->willReturn(['code' => 200]);
        $this->infoRepo->expects($this->once())
            ->method('selectInformationProjetVersion')
            ->willReturn(['code' => 200, 'info' => [[
                'version_release_sonar' => 3,
                'version_snapshot_sonar' => 1,
                'version_autre_sonar' => 0,
                'version' => '1.0.0',
                'date' => '2026-04-22',
                'analyse_key' => 'AY1',
            ]]]);

        $payload = $this->decode($this->controller->peintureProjetVersion($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
        ])));

        $this->assertSame(200, $payload['code']);
        $this->assertSame(3, $payload['release']);
        $this->assertSame([3, 1, 0], $payload['dataset']);
        $this->assertSame('1.0.0', $payload['version']);
    }

    // ═══════════════════════ peintureProjetMesures ═════════════════════════

    public function testPeintureProjetMesuresReturns400WhenMavenKeyMissing(): void
    {
        $response = $this->controller->peintureProjetMesures($this->jsonRequest([]));

        $this->assertJsonStatus($response, 400, 'alert');
    }

    public function testPeintureProjetMesuresReturns404WhenProjectInvalid(): void
    {
        $this->isValide->method('isValideInformation')->willReturn(['code' => 404]);

        $response = $this->controller->peintureProjetMesures($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
        ]));

        $this->assertJsonStatus($response, 404, 'warning');
    }

    public function testPeintureProjetMesuresReturnsErrorWhenRepoFails(): void
    {
        $this->isValide->method('isValideInformation')->willReturn(['code' => 200]);
        $this->mesuresRepo->method('selectMesuresVersionLast')->willReturn([
            'code' => 500, 'erreur' => 'db',
        ]);

        $payload = $this->decode($this->controller->peintureProjetMesures($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
        ])));

        $this->assertSame(500, $payload['code']);
    }

    public function testPeintureProjetMesuresReturnsCompleteMeasuresPayload(): void
    {
        $this->isValide->method('isValideInformation')->willReturn(['code' => 200]);
        $this->mesuresRepo->expects($this->once())
            ->method('selectMesuresVersionLast')
            ->willReturn(['code' => 200, 'mesures' => [
                'project_name' => 'App',
                'ncloc_language_distribution' => '{"java":100}',
                'ncloc' => 800,
                'coverage' => 85,
                'bugs' => 2,
            ]]);

        $payload = $this->decode($this->controller->peintureProjetMesures($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
        ])));

        $this->assertSame(200, $payload['code']);
        $this->assertSame('App', $payload['name']);
        $this->assertSame(['java' => 100], $payload['ncloc_language_distribution']);
        $this->assertSame(2, $payload['bugs']);
    }

    // ═══════════════════════ peintureProjetAnomalie ════════════════════════

    public function testPeintureProjetAnomalieReturnsDetteAndTypeBuckets(): void
    {
        $this->isValide->method('isValideInformation')->willReturn(['code' => 200]);
        $this->anomalieRepo->expects($this->once())
            ->method('selectAnomalie')
            ->willReturn(['code' => 200, 'liste' => [[
                'dette' => '1h', 'dette_minute' => 60,
                'dette_reliability' => '15m', 'dette_reliability_minute' => 15,
                'dette_vulnerability' => '10m', 'dette_vulnerability_minute' => 10,
                'dette_code_smell' => '35m', 'dette_code_smell_minute' => 35,
                'bug' => 2, 'vulnerability' => 3, 'code_smell' => 5,
                'blocker' => 1, 'critical' => 2, 'major' => 3, 'info' => 1, 'minor' => 3,
                'frontend' => 4, 'backend' => 3, 'autre' => 2, 'inconnu' => 1,
            ]]]);
        // Plugin tracker-logger absent : selectLogger renvoie liste vide → pas de soustraction
        $this->loggerRepo->expects($this->once())
            ->method('selectLogger')
            ->willReturn(['code' => 200, 'liste' => false]);

        $payload = $this->decode($this->controller->peintureProjetAnomalie($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
        ])));

        $this->assertSame(200, $payload['code']);
        $this->assertSame('1h', $payload['dette']);
        $this->assertSame(2, $payload['bug']);
        $this->assertSame(2, $payload['bug_total']);
        $this->assertSame(0, $payload['bug_logger']);
        $this->assertSame(4, $payload['frontend']);
    }

    public function testPeintureProjetAnomalieSubtractsLoggerHitsFromBugCount(): void
    {
        $this->isValide->method('isValideInformation')->willReturn(['code' => 200]);
        $this->anomalieRepo->expects($this->once())
            ->method('selectAnomalie')
            ->willReturn(['code' => 200, 'liste' => [[
                'dette' => '1h', 'dette_minute' => 60,
                'dette_reliability' => '15m', 'dette_reliability_minute' => 15,
                'dette_vulnerability' => '10m', 'dette_vulnerability_minute' => 10,
                'dette_code_smell' => '35m', 'dette_code_smell_minute' => 35,
                'bug' => 30, 'vulnerability' => 3, 'code_smell' => 5,
                'blocker' => 1, 'critical' => 2, 'major' => 3, 'info' => 1, 'minor' => 27,
                'frontend' => 4, 'backend' => 3, 'autre' => 2, 'inconnu' => 1,
            ]]]);
        // Plugin tracker-logger actif : 27 hits → bug réel = 30 - 27 = 3
        $this->loggerRepo->expects($this->once())
            ->method('selectLogger')
            ->willReturn(['code' => 200, 'liste' => [
                'logger_info' => 20,
                'logger_warn' => 5,
                'logger_error' => 2,
                'logger_debug' => 0,
            ]]);

        $payload = $this->decode($this->controller->peintureProjetAnomalie($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
        ])));

        $this->assertSame(200, $payload['code']);
        $this->assertSame(3, $payload['bug']);          // bug réel
        $this->assertSame(30, $payload['bug_total']);   // raw sonar
        $this->assertSame(27, $payload['bug_logger']);  // logger hits
    }

    public function testPeintureProjetAnomalieClampsBugRealToZero(): void
    {
        // Edge case : si logger > bug (ne devrait pas arriver mais on protège), bug = 0
        $this->isValide->method('isValideInformation')->willReturn(['code' => 200]);
        $this->anomalieRepo->method('selectAnomalie')->willReturn(['code' => 200, 'liste' => [[
            'dette' => '0', 'dette_minute' => 0,
            'dette_reliability' => '0', 'dette_reliability_minute' => 0,
            'dette_vulnerability' => '0', 'dette_vulnerability_minute' => 0,
            'dette_code_smell' => '0', 'dette_code_smell_minute' => 0,
            'bug' => 5, 'vulnerability' => 0, 'code_smell' => 0,
            'blocker' => 0, 'critical' => 0, 'major' => 0, 'info' => 0, 'minor' => 0,
            'frontend' => 0, 'backend' => 0, 'autre' => 0, 'inconnu' => 0,
        ]]]);
        $this->loggerRepo->method('selectLogger')->willReturn(['code' => 200, 'liste' => [
            'logger_info' => 100, 'logger_warn' => 0, 'logger_error' => 0, 'logger_debug' => 0,
        ]]);

        $payload = $this->decode($this->controller->peintureProjetAnomalie($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
        ])));

        $this->assertSame(0, $payload['bug']);
        $this->assertSame(5, $payload['bug_total']);
        $this->assertSame(100, $payload['bug_logger']);
    }

    public function testPeintureProjetAnomaliePropagatesRepositoryError(): void
    {
        $this->isValide->method('isValideInformation')->willReturn(['code' => 200]);
        $this->anomalieRepo->expects($this->once())
            ->method('selectAnomalie')
            ->willReturn(['code' => 500, 'erreur' => 'db']);

        $this->assertJsonStatus(
            $this->controller->peintureProjetAnomalie($this->jsonRequest(['maven_key' => self::MAVEN_KEY])),
            500,
            'alert'
        );
    }

    // ═══════════════════════ peintureProjetAnomalieDetails ═════════════════

    public function testPeintureProjetAnomalieDetailsReturnsAllSeverityCounts(): void
    {
        $this->isValide->method('isValideInformation')->willReturn(['code' => 200]);

        $liste = [[]];
        foreach (['bug', 'vulnerability', 'code_smell'] as $type) {
            foreach (['blocker', 'critical', 'major', 'minor', 'info'] as $sev) {
                $liste[0]["{$type}_{$sev}"] = 1;
            }
        }

        $this->anomalieDetailsRepo->expects($this->once())
            ->method('selectAnomalieDetailsMavenKey')
            ->willReturn(['code' => 200, 'liste' => $liste]);

        $payload = $this->decode($this->controller->peintureProjetAnomalieDetails($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
        ])));

        $this->assertSame(200, $payload['code']);
        $this->assertSame(1, $payload['bugBlocker']);
        $this->assertSame(1, $payload['codeSmellInfo']);
    }

    // ═══════════════════════ peintureProjetHotspots ════════════════════════

    public function testPeintureProjetHotspotsReturnsNoteAWhenNoToReview(): void
    {
        $this->isValide->method('isValideInformation')->willReturn(['code' => 200]);
        $this->hotspotsRepo->expects($this->exactly(2))
            ->method('countHotspotsStatus')
            ->willReturnCallback(function (array $map) {
                $key = $map['status'] === 'TO_REVIEW' ? 'to_review' : 'reviewed';
                // to_review=0 → branche "note=A" sans calculNoteHotspot
                $nombre = $map['status'] === 'TO_REVIEW' ? 0 : 50;
                return ['code' => 200, 'nombre' => [[$key => $nombre]]];
            });

        $payload = $this->decode($this->controller->peintureProjetHotspots($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
        ])));

        $this->assertSame(200, $payload['code']);
        $this->assertSame('A', $payload['note']);
    }

    public function testPeintureProjetHotspotsComputesNoteFromRatio(): void
    {
        $this->isValide->method('isValideInformation')->willReturn(['code' => 200]);
        $this->hotspotsRepo->expects($this->exactly(2))
            ->method('countHotspotsStatus')
            ->willReturnCallback(function (array $map) {
                // reviewed=50, to_review=100 → 50*100/100 + 50 = 100 → note A
                $value = $map['status'] === 'TO_REVIEW' ? 100 : 50;
                $key = $map['status'] === 'TO_REVIEW' ? 'to_review' : 'reviewed';
                return ['code' => 200, 'nombre' => [[$key => $value]]];
            });

        $payload = $this->decode($this->controller->peintureProjetHotspots($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
        ])));

        $this->assertSame('A', $payload['note']);
    }

    // ═══════════════════════ peintureProjetHotspotsDetails ═════════════════

    public function testPeintureProjetHotspotsDetailsAggregatesByProbability(): void
    {
        $this->isValide->method('isValideInformation')->willReturn(['code' => 200]);
        $this->hotspotsRepo->expects($this->exactly(2))
            ->method('selectHotspotsByNiveau')
            ->willReturnCallback(function (array $map) {
                $base = $map['status'] === 'TO_REVIEW' ? 10 : 1;
                return ['code' => 200, 'liste' => [
                    ['niveau' => '1', 'hotspot' => $base],
                    ['niveau' => '2', 'hotspot' => $base * 2],
                    ['niveau' => '3', 'hotspot' => $base * 3],
                ]];
            });

        $payload = $this->decode($this->controller->peintureProjetHotspotsDetails($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
        ])));

        $this->assertSame(200, $payload['code']);
        $this->assertSame(10, $payload['to_review_high']);
        $this->assertSame(20, $payload['to_review_medium']);
        $this->assertSame(30, $payload['to_review_low']);
        $this->assertSame(60, $payload['to_review_total']);
        $this->assertSame(1, $payload['reviewed_high']);
        $this->assertSame(6, $payload['reviewed_total']);
    }

    // ═══════════════════════ peintureProjetNoSonar ═════════════════════════

    public function testPeintureProjetNoSonarReturnsRuleCounts(): void
    {
        $this->isValide->method('isValideInformation')->willReturn(['code' => 200]);
        $this->noSonarRepo->expects($this->once())
            ->method('selectNoSonarRuleGroupByRule')
            ->willReturn(['code' => 200, 'liste' => [
                ['rule' => 'java:NoSonar', 'total' => 5],
                ['rule' => 'java:S1309', 'total' => 3],
                ['rule' => 'java:S1310', 'total' => 2],
                ['rule' => 'java:S1315', 'total' => 4],
                ['rule' => 'python:NoSonar', 'total' => 7],
                ['rule' => 'php:NoSonar', 'total' => 1],
            ]]);

        $payload = $this->decode($this->controller->peintureProjetNoSonar($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
        ])));

        $this->assertSame(200, $payload['code']);
        $this->assertSame(22, $payload['total']);
        $this->assertSame(5, $payload['nosonar']);
        $this->assertSame(3, $payload['s1309']);
        $this->assertSame(3, $payload['suppress_warning']);
        $this->assertSame(2, $payload['no_pmd']);
        $this->assertSame(4, $payload['check_style']);
        $this->assertSame(5, $payload['java_no_sonar']);
        $this->assertSame(7, $payload['python_no_sonar']);
        $this->assertSame(1, $payload['php_no_sonar']);
        $this->assertSame(13, $payload['total_no_sonar']);
    }

    // ═══════════════════════ peintureProjetTodo ════════════════════════════

    public function testPeintureProjetTodoAggregatesAcrossLanguages(): void
    {
        $this->isValide->method('isValideInformation')->willReturn(['code' => 200]);
        $this->todoRepo->expects($this->once())
            ->method('selectTodoRuleGroupByRule')
            ->willReturn(['code' => 200, 'liste' => [
                ['rule' => 'java:S1135', 'total' => 2],
                ['rule' => 'javascript:S1135', 'total' => 1],
                ['rule' => 'typescript:S1135', 'total' => 3],
                ['rule' => 'Web:S1135', 'total' => 4],
                ['rule' => 'xml:S1135', 'total' => 5],
            ]]);
        $this->todoRepo->expects($this->once())
            ->method('selectTodoComponentOrderByRule')
            ->willReturn(['code' => 200, 'liste' => []]);

        $payload = $this->decode($this->controller->peintureProjetTodo($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
        ])));

        $this->assertSame(200, $payload['code']);
        $this->assertSame(15, $payload['todo']);
        $this->assertSame(2, $payload['java']);
        $this->assertSame(5, $payload['xml']);
    }

    // ═══════════════════════ peintureProjetLogger ══════════════════════════

    public function testPeintureProjetLoggerSumsAllLevels(): void
    {
        $this->isValide->method('isValideInformation')->willReturn(['code' => 200]);
        $this->loggerRepo->expects($this->once())
            ->method('selectLogger')
            ->willReturn(['code' => 200, 'liste' => [
                'logger_info' => 10,
                'logger_warn' => 3,
                'logger_error' => 2,
                'logger_debug' => 5,
            ]]);

        $payload = $this->decode($this->controller->peintureProjetLogger($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
        ])));

        $this->assertSame(200, $payload['code']);
        $this->assertSame(20, $payload['total']);
        $this->assertSame(10, $payload['logger_info']);
    }

    // ═══════════════════ Repo error propagation ════════════════════════════

    public function testPeintureProjetVersionPropagatesRepoError(): void
    {
        $this->isValide->method('isValideInformation')->willReturn(['code' => 200]);
        $this->infoRepo->method('selectInformationProjetVersion')->willReturn([
            'code' => 500, 'erreur' => 'fail',
        ]);

        $payload = $this->decode($this->controller->peintureProjetVersion($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
        ])));

        $this->assertSame(500, $payload['code']);
    }

    public function testPeintureProjetAnomalieDetailsPropagatesRepoError(): void
    {
        $this->isValide->method('isValideInformation')->willReturn(['code' => 200]);
        $this->anomalieDetailsRepo->method('selectAnomalieDetailsMavenKey')->willReturn([
            'code' => 500, 'erreur' => 'fail',
        ]);

        $payload = $this->decode($this->controller->peintureProjetAnomalieDetails($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
        ])));

        $this->assertSame(500, $payload['code']);
    }

    public function testPeintureProjetHotspotsPropagatesRepoError(): void
    {
        $this->isValide->method('isValideInformation')->willReturn(['code' => 200]);
        $this->hotspotsRepo->method('countHotspotsStatus')->willReturn([
            'code' => 500, 'erreur' => 'fail',
        ]);

        $payload = $this->decode($this->controller->peintureProjetHotspots($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
        ])));

        $this->assertSame(500, $payload['code']);
    }

    public function testPeintureProjetHotspotsDetailsPropagatesRepoError(): void
    {
        $this->isValide->method('isValideInformation')->willReturn(['code' => 200]);
        $this->hotspotsRepo->method('selectHotspotsByNiveau')->willReturn([
            'code' => 500, 'erreur' => 'fail',
        ]);

        $payload = $this->decode($this->controller->peintureProjetHotspotsDetails($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
        ])));

        $this->assertSame(500, $payload['code']);
    }

    public function testPeintureProjetNoSonarPropagatesRepoError(): void
    {
        $this->isValide->method('isValideInformation')->willReturn(['code' => 200]);
        $this->noSonarRepo->method('selectNoSonarRuleGroupByRule')->willReturn([
            'code' => 500, 'erreur' => 'fail',
        ]);

        $payload = $this->decode($this->controller->peintureProjetNoSonar($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
        ])));

        $this->assertSame(500, $payload['code']);
    }

    public function testPeintureProjetTodoPropagatesRepoError(): void
    {
        $this->isValide->method('isValideInformation')->willReturn(['code' => 200]);
        $this->todoRepo->method('selectTodoRuleGroupByRule')->willReturn([
            'code' => 500, 'erreur' => 'fail',
        ]);

        $payload = $this->decode($this->controller->peintureProjetTodo($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
        ])));

        $this->assertSame(500, $payload['code']);
    }

    public function testPeintureProjetLoggerPropagatesRepoError(): void
    {
        $this->isValide->method('isValideInformation')->willReturn(['code' => 200]);
        $this->loggerRepo->method('selectLogger')->willReturn([
            'code' => 500, 'erreur' => 'fail',
        ]);

        $payload = $this->decode($this->controller->peintureProjetLogger($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
        ])));

        $this->assertSame(500, $payload['code']);
    }

    // ═══════════════════ 400 / 404 bulk coverage ═══════════════════════════

    public static function methodsWithValidation(): array
    {
        return [
            'anomalie' => ['peintureProjetAnomalie'],
            'anomalie_details' => ['peintureProjetAnomalieDetails'],
            'hotspots' => ['peintureProjetHotspots'],
            'hotspots_details' => ['peintureProjetHotspotsDetails'],
            'no_sonar' => ['peintureProjetNoSonar'],
            'todo' => ['peintureProjetTodo'],
            'logger' => ['peintureProjetLogger'],
        ];
    }

    #[DataProvider('methodsWithValidation')]
    public function testEachMethodReturns400WhenMavenKeyMissing(string $methodName): void
    {
        $response = $this->controller->$methodName($this->jsonRequest([]));

        $this->assertJsonStatus($response, 400, 'alert');
    }

    #[DataProvider('methodsWithValidation')]
    public function testEachMethodReturns404WhenProjectInvalid(string $methodName): void
    {
        $this->isValide->method('isValideInformation')->willReturn(['code' => 404]);

        $response = $this->controller->$methodName($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
        ]));

        $this->assertJsonStatus($response, 404, 'warning');
    }

    public function testPeintureProjetLoggerReturnsMinusOneWhenEmpty(): void
    {
        $this->isValide->method('isValideInformation')->willReturn(['code' => 200]);
        $this->loggerRepo->expects($this->once())
            ->method('selectLogger')
            ->willReturn(['code' => 200, 'liste' => []]);

        $payload = $this->decode($this->controller->peintureProjetLogger($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
        ])));

        $this->assertSame(200, $payload['code']);
        // Défaut -1 quand liste vide (indique "non collecté")
        $this->assertSame(-1, $payload['total']);
        $this->assertSame(-1, $payload['logger_info']);
    }

    // ═══════════════════════ helpers ═══════════════════════════════════════

    private function jsonRequest(array $body): Request
    {
        return new Request([], [], [], [], [], [], json_encode($body, JSON_FORCE_OBJECT));
    }

    private function decode(JsonResponse $response): array
    {
        return json_decode($response->getContent(), true);
    }

    private function assertJsonStatus(JsonResponse $response, int $code, ?string $type = null): void
    {
        $payload = $this->decode($response);
        $this->assertSame($code, $payload['code']);
        if ($type !== null) {
            $this->assertSame($type, $payload['type'] ?? null);
        }
    }
}
