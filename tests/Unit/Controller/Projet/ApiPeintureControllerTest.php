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

namespace App\Tests\Unit\Controller\Projet;

use App\Controller\Projet\ApiPeintureController;
use App\Entity\{Anomalie, AnomalieDetails, Hotspots, InformationProjet, LoggerDetail, Mesures, NoSonar, Todo, Utilisateur};
use App\Entity\Logger as LoggerEntity;
use App\Repository\{AnomalieDetailsRepository, AnomalieRepository, HotspotsRepository, InformationProjetRepository, LoggerDetailRepository, LoggerRepository, MesuresRepository, NoSonarRepository, TodoRepository};
use App\Service\IsValideMavenKey;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\{AllowMockObjectsWithoutExpectations, DataProvider};
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\{JsonResponse, Request};
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

#[AllowMockObjectsWithoutExpectations]
class ApiPeintureControllerTest extends TestCase
{
    private const MAVEN_KEY = 'fr.ma-moulinette:ma-moulinette';

    /** @var EntityManagerInterface&MockObject */
    private MockObject $em;
    /** @var IsValideMavenKey&MockObject */
    private MockObject $isValide;
    /** @var LoggerInterface&MockObject */
    private MockObject $logger;
    /** @var TokenInterface&MockObject */
    private MockObject $token;

    /** @var AnomalieRepository&MockObject */       private MockObject $anomalieRepo;
    /** @var AnomalieDetailsRepository&MockObject */ private MockObject $anomalieDetailsRepo;
    /** @var InformationProjetRepository&MockObject */ private MockObject $infoRepo;
    /** @var MesuresRepository&MockObject */         private MockObject $mesuresRepo;
    /** @var HotspotsRepository&MockObject */        private MockObject $hotspotsRepo;
    /** @var NoSonarRepository&MockObject */         private MockObject $noSonarRepo;
    /** @var TodoRepository&MockObject */            private MockObject $todoRepo;
    /** @var LoggerRepository&MockObject */          private MockObject $loggerRepo;
    /** @var LoggerDetailRepository&MockObject */    private MockObject $loggerDetailRepo;

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
        $this->loggerDetailRepo = $this->createMock(LoggerDetailRepository::class);

        $this->em->method('getRepository')->willReturnMap([
            [Anomalie::class, $this->anomalieRepo],
            [AnomalieDetails::class, $this->anomalieDetailsRepo],
            [InformationProjet::class, $this->infoRepo],
            [Mesures::class, $this->mesuresRepo],
            [Hotspots::class, $this->hotspotsRepo],
            [NoSonar::class, $this->noSonarRepo],
            [Todo::class, $this->todoRepo],
            [LoggerEntity::class, $this->loggerRepo],
            [LoggerDetail::class, $this->loggerDetailRepo],
        ]);

        // appUser() (trait AppUserAware) appelle AbstractController::getUser() qui interroge security.token_storage
        $this->token = $this->createMock(TokenInterface::class);
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($this->token);

        // Container pour AbstractController::json() + getUser()
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(
            fn(string $id): bool => $id === 'security.token_storage'
        );
        $container->method('get')->willReturnMap([
            ['security.token_storage', 1, $tokenStorage],
        ]);

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

        $this->assertJsonStatus($response, 400, 'error');
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
                ['key' => 'fr.ma-moulinette:ma-moulinette'],
                ['key' => 'fr.ma-moulinette:projet-b'],
            ]]);

        $user = $this->createMock(Utilisateur::class);
        $user->method('getPreference')->willReturn([
            'favori_projet' => ['fr.ma-moulinette:ma-moulinette', 'fr.ma-moulinette:projet-b', 'fr.ma-moulinette:projet-inconnu'],
            'favori_version' => ['fr.ma-moulinette:ma-moulinette' => '1.0.0'],
        ]);
        // appUser() (trait AppUserAware) lit security.token_storage, pas le param Security
        $this->token->method('getUser')->willReturn($user);
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
        $this->assertTrue($byKey['fr.ma-moulinette:ma-moulinette']['favori']);
        $this->assertFalse($byKey['fr.ma-moulinette:projet-b']['favori']);
    }

    // ═══════════════════════ peintureProjetVersion ═════════════════════════

    public function testPeintureProjetVersionReturns400WhenMavenKeyMissing(): void
    {
        $response = $this->controller->peintureProjetVersion($this->jsonRequest([]));

        $this->assertJsonStatus($response, 400, 'error');
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
            ->willReturn(['code' => 200, 'info' => [
                'version_release_sonar' => 3,
                'version_snapshot_sonar' => 1,
                'version_autre_sonar' => 0,
                'version' => '1.0.0',
                'date' => '2026-04-22',
                'analyse_key' => 'AY1',
            ]]);

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

        $this->assertJsonStatus($response, 400, 'error');
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

        $payload = $this->decode($this->controller->peintureProjetAnomalie($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
        ])));

        $this->assertSame(200, $payload['code']);
        $this->assertSame('1h', $payload['dette']);
        $this->assertSame(2, $payload['bug_total']);
        $this->assertSame(4, $payload['frontend']);
    }

    public function testPeintureProjetAnomalieReturnsLargeBugCount(): void
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

        $payload = $this->decode($this->controller->peintureProjetAnomalie($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
        ])));

        $this->assertSame(200, $payload['code']);
        $this->assertSame(30, $payload['bug_total']);
    }

    public function testPeintureProjetAnomalieReturnsZeroDebtWhenAllValuesAreZero(): void
    {
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

        $payload = $this->decode($this->controller->peintureProjetAnomalie($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
        ])));

        $this->assertSame(5, $payload['bug_total']);
        $this->assertSame(0, $payload['detteMinute']);
    }

    public function testPeintureProjetAnomaliePropagatesRepositoryError(): void
    {
        $this->isValide->method('isValideInformation')->willReturn(['code' => 200]);
        $this->anomalieRepo->expects($this->once())
            ->method('selectAnomalie')
            ->willReturn(['code' => 500, 'erreur' => 'db']);

        /* MODIF 2026-05-24 : migration terminée, type = 'error'. */
        $this->assertJsonStatus(
            $this->controller->peintureProjetAnomalie($this->jsonRequest(['maven_key' => self::MAVEN_KEY])),
            500,
            'error'
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

    public function testPeintureProjetAnomalieDetailsReturnsNullsWhenListIsEmpty(): void
    {
        $this->isValide->method('isValideInformation')->willReturn(['code' => 200]);

        $this->anomalieDetailsRepo->expects($this->once())
            ->method('selectAnomalieDetailsMavenKey')
            ->willReturn(['code' => 200, 'liste' => []]);

        $payload = $this->decode($this->controller->peintureProjetAnomalieDetails($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
        ])));

        $this->assertSame(200, $payload['code']);
        $this->assertNull($payload['bugBlocker']);
        $this->assertNull($payload['vulnerabilityBlocker']);
        $this->assertNull($payload['codeSmellBlocker']);
        $this->assertSame(0, $payload['CodeSmellRealTotal']);
    }

    public function testPeintureProjetAnomalieDetailsPropagatesRepositoryError(): void
    {
        $this->isValide->method('isValideInformation')->willReturn(['code' => 200]);

        $this->anomalieDetailsRepo->expects($this->once())
            ->method('selectAnomalieDetailsMavenKey')
            ->willReturn(['code' => 500, 'erreur' => 'db error']);

        $this->assertJsonStatus(
            $this->controller->peintureProjetAnomalieDetails($this->jsonRequest(['maven_key' => self::MAVEN_KEY])),
            500,
            'error'
        );
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
        /* MODIF 2026-05-07 : mock selectNoSonarComponentOrderByRule
         * (ajouté côté controller le 2026-05-06 pour alimenter le tableau modale). */
        $this->noSonarRepo->expects($this->once())
            ->method('selectNoSonarComponentOrderByRule')
            ->willReturn(['code' => 200, 'liste' => []]);

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

    // ═══════════════════════ peintureProjetLoggerDetails ═══════════════════
    // MODIF 2026-05-15 : nouveau endpoint logger-details.

    public function testPeintureProjetLoggerDetailsReturns400WhenMavenKeyMissing(): void
    {
        $response = $this->controller->peintureProjetLoggerDetails($this->jsonRequest([]));

        $this->assertJsonStatus($response, 400, 'error');
    }

    public function testPeintureProjetLoggerDetailsReturns404WhenProjectInvalid(): void
    {
        $this->isValide->method('isValideInformation')->willReturn(['code' => 404]);

        $response = $this->controller->peintureProjetLoggerDetails($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
        ]));

        $this->assertJsonStatus($response, 404, 'warning');
    }

    public function testPeintureProjetLoggerDetailsReturnsFourDatasetsOnHappyPath(): void
    {
        $this->isValide->method('isValideInformation')->willReturn(['code' => 200]);

        $byLevel = [['level' => 'info', 'nb' => 5], ['level' => 'warn', 'nb' => 2]];
        $byFw    = [['framework' => 'SLF4J', 'nb' => 6], ['framework' => null, 'nb' => 1]];
        $byMx    = [
            ['level' => 'info', 'framework' => 'SLF4J', 'nb' => 4],
            ['level' => 'info', 'framework' => null,    'nb' => 1],
            ['level' => 'warn', 'framework' => 'SLF4J', 'nb' => 2],
        ];
        $details = [
            ['id' => 1, 'file_path' => 'a/b/Foo.java', 'line_number' => 12, 'level' => 'info', 'framework' => 'SLF4J'],
            ['id' => 2, 'file_path' => 'a/b/Bar.java', 'line_number' => 34, 'level' => 'warn', 'framework' => 'SLF4J'],
        ];

        $this->loggerDetailRepo->expects($this->once())
            ->method('countByMavenKeyGroupedByLevel')
            ->with(self::MAVEN_KEY)
            ->willReturn(['code' => 200, 'liste' => $byLevel]);
        $this->loggerDetailRepo->expects($this->once())
            ->method('countByMavenKeyGroupedByFramework')
            ->willReturn(['code' => 200, 'liste' => $byFw]);
        $this->loggerDetailRepo->expects($this->once())
            ->method('countByMavenKeyGroupedByLevelAndFramework')
            ->willReturn(['code' => 200, 'liste' => $byMx]);
        $this->loggerDetailRepo->expects($this->once())
            ->method('selectByMavenKey')
            ->willReturn(['code' => 200, 'liste' => $details]);

        $payload = $this->decode($this->controller->peintureProjetLoggerDetails($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
        ])));

        $this->assertSame(200, $payload['code']);
        $this->assertSame(self::MAVEN_KEY, $payload['maven_key']);
        $this->assertSame($byLevel, $payload['breakdown_level']);
        $this->assertSame($byFw,    $payload['breakdown_framework']);
        $this->assertSame($byMx,    $payload['breakdown_matrix']);
        $this->assertSame($details, $payload['details']);
    }

    public function testPeintureProjetLoggerDetailsReturnsEmptyArraysWhenNoData(): void
    {
        $this->isValide->method('isValideInformation')->willReturn(['code' => 200]);
        $empty = ['code' => 200, 'liste' => []];
        $this->loggerDetailRepo->method('countByMavenKeyGroupedByLevel')->willReturn($empty);
        $this->loggerDetailRepo->method('countByMavenKeyGroupedByFramework')->willReturn($empty);
        $this->loggerDetailRepo->method('countByMavenKeyGroupedByLevelAndFramework')->willReturn($empty);
        $this->loggerDetailRepo->method('selectByMavenKey')->willReturn($empty);

        $payload = $this->decode($this->controller->peintureProjetLoggerDetails($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
        ])));

        $this->assertSame(200, $payload['code']);
        $this->assertSame([], $payload['breakdown_level']);
        $this->assertSame([], $payload['breakdown_framework']);
        $this->assertSame([], $payload['breakdown_matrix']);
        $this->assertSame([], $payload['details']);
    }

    public function testPeintureProjetLoggerDetailsLogsAndDegradesGracefullyOnPartialRepoError(): void
    {
        $this->isValide->method('isValideInformation')->willReturn(['code' => 200]);

        /* 2 sur 4 queries en erreur — le controller doit logger mais retourner 200 avec
         * listes vides pour les queries en échec (page projet reste affichable). */
        $this->loggerDetailRepo->method('countByMavenKeyGroupedByLevel')
            ->willReturn(['code' => 200, 'liste' => [['level' => 'info', 'nb' => 1]]]);
        $this->loggerDetailRepo->method('countByMavenKeyGroupedByFramework')
            ->willReturn(['code' => 500, 'erreur' => 'sql boom']);
        $this->loggerDetailRepo->method('countByMavenKeyGroupedByLevelAndFramework')
            ->willReturn(['code' => 200, 'liste' => []]);
        $this->loggerDetailRepo->method('selectByMavenKey')
            ->willReturn(['code' => 500, 'erreur' => 'sql boom']);

        $this->logger->expects($this->atLeast(2))
            ->method('error')
            ->with($this->stringContains('Échec lecture logger_details'), $this->anything());

        $payload = $this->decode($this->controller->peintureProjetLoggerDetails($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
        ])));

        $this->assertSame(200, $payload['code']);
        $this->assertSame([['level' => 'info', 'nb' => 1]], $payload['breakdown_level']);
        $this->assertSame([], $payload['breakdown_framework']);
        $this->assertSame([], $payload['details']);
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

    /* MODIF 2026-05-24 : migration terminée — toutes les méthodes
     * retournent désormais 'error' (plus de 'alert'). Colonne expectedType400 unifiée.
     */
    public static function methodsWithValidation(): array
    {
        return [
            'anomalie'         => ['peintureProjetAnomalie',        'error'],
            'anomalie_details' => ['peintureProjetAnomalieDetails', 'error'],
            'hotspots'         => ['peintureProjetHotspots',        'error'],
            'hotspots_details' => ['peintureProjetHotspotsDetails', 'error'],
            'no_sonar'         => ['peintureProjetNoSonar',         'error'],
            'todo'             => ['peintureProjetTodo',            'error'],
            'logger'           => ['peintureProjetLogger',          'error'],
        ];
    }

    #[DataProvider('methodsWithValidation')]
    public function testEachMethodReturns400WhenMavenKeyMissing(string $methodName, string $expectedType): void
    {
        $response = $this->controller->$methodName($this->jsonRequest([]));

        $this->assertJsonStatus($response, 400, $expectedType);
    }

    #[DataProvider('methodsWithValidation')]
    public function testEachMethodReturns404WhenProjectInvalid(string $methodName, string $expectedType): void
    {
        // expectedType non utilise ici (404 = 'warning' partout), mais requis par le dataProvider partage.
        unset($expectedType);
        $this->isValide->method('isValideInformation')->willReturn(['code' => 404]);

        $response = $this->controller->$methodName($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
        ]));

        $this->assertJsonStatus($response, 404, 'warning');
    }

    /* MODIF 2026-05-07 : sentinel -1 → null (cf MODIF 2026-05-04 côté controller).
     * `null` signifie "plugin logger non collecté" — frontend (displayHelper.displayNumber) affiche '-' pour null,
     * différencie du cas `0` qui signifie "0 logger collecté". */
    public function testPeintureProjetLoggerReturnsNullWhenEmpty(): void
    {
        $this->isValide->method('isValideInformation')->willReturn(['code' => 200]);
        $this->loggerRepo->expects($this->once())
            ->method('selectLogger')
            ->willReturn(['code' => 200, 'liste' => []]);

        $payload = $this->decode($this->controller->peintureProjetLogger($this->jsonRequest([
            'maven_key' => self::MAVEN_KEY,
        ])));

        $this->assertSame(200, $payload['code']);
        $this->assertNull($payload['total']);
        $this->assertNull($payload['logger_info']);
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
