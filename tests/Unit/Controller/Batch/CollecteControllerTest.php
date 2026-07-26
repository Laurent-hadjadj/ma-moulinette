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

use App\Controller\Batch\{BatchCollecteActuatorController, BatchCollecteAnomalieController,BatchCollecteAnomalieDetailController, BatchCollecteHotspotController, BatchCollecteHotspotDetailController, BatchCollecteHotspotOwaspController, BatchCollecteInformationProjetController, BatchCollecteLoggerController,BatchCollecteMesureController, BatchCollecteNoSonarController, BatchCollecteOwaspController, BatchCollecteTodoController, CollecteController};
use App\Entity\Historique;
use App\Repository\HistoriqueRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
class CollecteControllerTest extends TestCase
{
    private const PORTEFEUILLE = 'PF-1';
    private const MAVEN_KEY = 'fr.ma-moulinette:ma-moulinette';
    private const MODE = 'manual';
    private const USER = 'admin@ma-moulinette.fr';

    /** @var EntityManagerInterface&MockObject */
    private MockObject $em;

    /** @var HistoriqueRepository&MockObject */
    private MockObject $historiqueRepo;

    /** @var LoggerInterface&MockObject */
    private MockObject $logger;

    /** @var BatchCollecteInformationProjetController&MockObject */
    private MockObject $batchInfo;
    /** @var BatchCollecteMesureController&MockObject */
    private MockObject $batchMesure;
    /** @var BatchCollecteOwaspController&MockObject */
    private MockObject $batchOwasp;
    /** @var BatchCollecteHotspotController&MockObject */
    private MockObject $batchHotspot;
    /** @var BatchCollecteAnomalieController&MockObject */
    private MockObject $batchAnomalie;
    /** @var BatchCollecteAnomalieDetailController&MockObject */
    private MockObject $batchAnomalieDetail;
    /** @var BatchCollecteHotspotOwaspController&MockObject */
    private MockObject $batchHotspotOwasp;
    /** @var BatchCollecteHotspotDetailController&MockObject */
    private MockObject $batchHotspotDetail;
    /** @var BatchCollecteNoSonarController&MockObject */
    private MockObject $batchNoSonar;
    /** @var BatchCollecteTodoController&MockObject */
    private MockObject $batchTodo;
    /** @var BatchCollecteActuatorController&MockObject */
    private MockObject $batchActuator;
    /** @var BatchCollecteLoggerController&MockObject */
    private MockObject $batchLogger;

    private CollecteController $controller;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->historiqueRepo = $this->createMock(HistoriqueRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->em->expects($this->atLeastOnce())
            ->method('getRepository')
            ->with(Historique::class)
            ->willReturn($this->historiqueRepo);

        $this->batchInfo = $this->createMock(BatchCollecteInformationProjetController::class);
        $this->batchMesure = $this->createMock(BatchCollecteMesureController::class);
        $this->batchOwasp = $this->createMock(BatchCollecteOwaspController::class);
        $this->batchHotspot = $this->createMock(BatchCollecteHotspotController::class);
        $this->batchAnomalie = $this->createMock(BatchCollecteAnomalieController::class);
        $this->batchAnomalieDetail = $this->createMock(BatchCollecteAnomalieDetailController::class);
        $this->batchHotspotOwasp = $this->createMock(BatchCollecteHotspotOwaspController::class);
        $this->batchHotspotDetail = $this->createMock(BatchCollecteHotspotDetailController::class);
        $this->batchNoSonar = $this->createMock(BatchCollecteNoSonarController::class);
        $this->batchTodo = $this->createMock(BatchCollecteTodoController::class);
        $this->batchActuator = $this->createMock(BatchCollecteActuatorController::class);
        $this->batchLogger = $this->createMock(BatchCollecteLoggerController::class);

        $this->controller = new CollecteController(
            $this->em,
            $this->logger,
            $this->batchInfo,
            $this->batchMesure,
            $this->batchOwasp,
            $this->batchHotspot,
            $this->batchAnomalie,
            $this->batchAnomalieDetail,
            $this->batchHotspotOwasp,
            $this->batchHotspotDetail,
            $this->batchNoSonar,
            $this->batchTodo,
            $this->batchActuator,
            $this->batchLogger,
        );
    }

    // ═══════════════════════ Happy path complet ═══════════════════════════

    public function testCollecteHappyPathReturns200WithHistoriqueAndSavesInCollecteMode(): void
    {
        $this->stubAllBatchesHappy();

        $this->historiqueRepo->expects($this->once())
            ->method('insertHistoriqueAjoutProjet')
            ->willReturn(['code' => 200]);

        // Mode 'collecte' → historique non vide, compte_rendu vide
        $result = $this->controller->collecte(self::PORTEFEUILLE, self::MAVEN_KEY, 'collecte', self::USER);

        $this->assertSame(200, $result['code']);
        $this->assertStringContainsString('collecte et la mise à jour', $result['message']);
        $this->assertIsArray($result['historique']);
        $this->assertSame('', $result['compte_rendu']);
    }

    public function testCollecteHappyPathInManualModeReturnsCompteRenduNotHistorique(): void
    {
        $this->stubAllBatchesHappy();
        $this->historiqueRepo->method('insertHistoriqueAjoutProjet')->willReturn(['code' => 200]);

        $result = $this->controller->collecte(self::PORTEFEUILLE, self::MAVEN_KEY, 'manual', self::USER);

        $this->assertSame(200, $result['code']);
        // Mode != collecte → compte_rendu non vide, historique vide
        $this->assertSame('', $result['historique']);
        $this->assertNotEmpty($result['compte_rendu']);
        $this->assertStringContainsString('Collecte terminée', $result['compte_rendu']);
    }

    // ═══════════════════════ Info projet ══════════════════════════════════

    public function testCollecteReturns202WhenInformationProjetReturns100(): void
    {
        $this->batchInfo->expects($this->once())
            ->method('batchCollecteInformation')
            ->willReturn([
                'code' => 100,
                'message' => 'Projet à jour',
                'historique' => [
                    'code' => 100,
                    'mode_collecte' => 'manual',
                    'Locale' => [
                        'name' => 'App',
                        'key-analyse' => 'AY',
                        'version' => '1.0',
                        'date-analyse' => '2026-04-22',
                    ],
                    'SonarQube' => ['key-analyse' => 'AY', 'version' => '1.0'],
                ],
            ]);

        $this->batchMesure->expects($this->never())->method('batchCollecteMesure');

        $result = $this->controller->collecte(self::PORTEFEUILLE, self::MAVEN_KEY, self::MODE, self::USER);

        $this->assertSame(202, $result['code']);
        $this->assertStringContainsString('Projet à jour', $result['compte_rendu']);
    }

    public function testCollecteStopsWithErrorCodeWhenInformationProjetFails(): void
    {
        $this->batchInfo->expects($this->once())
            ->method('batchCollecteInformation')
            ->willReturn([
                'code' => 503,
                'message' => 'SonarQube down',
                'erreur' => 'Timeout',
            ]);

        $this->batchMesure->expects($this->never())->method('batchCollecteMesure');

        $result = $this->controller->collecte(self::PORTEFEUILLE, self::MAVEN_KEY, self::MODE, self::USER);

        $this->assertSame(503, $result['code']);
        $this->assertStringContainsString('SonarQube down', $result['compte_rendu']);
        $this->assertStringContainsString('503', $result['compte_rendu']);
    }

    // ═══════════════════════ Chaque étape peut court-circuiter ═══════════

    public function testCollecteStopsWhenMesureFails(): void
    {
        $this->batchInfo->method('batchCollecteInformation')
            ->willReturn($this->stubInfoHappy());
        $this->batchMesure->method('batchCollecteMesure')
            ->willReturn(['code' => 500, 'message' => 'mesure fail', 'erreur' => 'e']);

        $this->batchAnomalie->expects($this->never())->method('batchCollecteAnomalie');

        $result = $this->controller->collecte(self::PORTEFEUILLE, self::MAVEN_KEY, self::MODE, self::USER);

        $this->assertSame(500, $result['code']);
        $this->assertStringContainsString('mesure fail', $result['compte_rendu']);
    }

    public function testCollecteStopsWhenAnomalieFails(): void
    {
        $this->batchInfo->method('batchCollecteInformation')->willReturn($this->stubInfoHappy());
        $this->batchMesure->method('batchCollecteMesure')->willReturn($this->stubMesureHappy());
        $this->batchAnomalie->method('batchCollecteAnomalie')
            ->willReturn(['code' => 500, 'type' => 'error', 'message' => 'anomalie fail', 'erreur' => 'e']);

        $this->batchAnomalieDetail->expects($this->never())->method('BatchCollecteAnomalieDetail');

        $result = $this->controller->collecte(self::PORTEFEUILLE, self::MAVEN_KEY, self::MODE, self::USER);

        $this->assertSame(500, $result['code']);
    }

    public function testCollecteStopsWhenHotspotOwaspLoopFails(): void
    {
        $this->stubBatchesUpToOwasp();

        // Premier owaspKey (a0) échoue → court-circuit retourne 500
        $this->batchHotspotOwasp->expects($this->once())
            ->method('batchCollecteHotspotOwasp')
            ->willReturn([
                'code' => 500,
                'type' => 'error',
                'message' => 'hotspot owasp fail',
                'erreur' => 'err',
            ]);

        $this->batchNoSonar->expects($this->never())->method('batchCollecteNoSonar');

        $result = $this->controller->collecte(self::PORTEFEUILLE, self::MAVEN_KEY, self::MODE, self::USER);

        $this->assertSame(500, $result['code']);
    }

    public function testCollecteStopsWhenHistoriqueInsertFails(): void
    {
        $this->stubAllBatchesHappy();

        $this->historiqueRepo->expects($this->once())
            ->method('insertHistoriqueAjoutProjet')
            ->willReturn(['code' => 500, 'message' => 'db fail', 'erreur' => 'sql']);

        $result = $this->controller->collecte(self::PORTEFEUILLE, self::MAVEN_KEY, self::MODE, self::USER);

        $this->assertSame(500, $result['code']);
        $this->assertStringContainsString('Échec de mise à jour de la table Historique', $result['compte_rendu']);
    }

    // ═══════════════════════ Étapes intermédiaires — court-circuits manquants ══

    public function testCollecteStopsWhenAnomalieDetailFails(): void
    {
        $this->batchInfo->method('batchCollecteInformation')->willReturn($this->stubInfoHappy());
        $this->batchMesure->method('batchCollecteMesure')->willReturn($this->stubMesureHappy());
        $this->batchAnomalie->method('batchCollecteAnomalie')->willReturn($this->stubAnomalieHappy());
        $this->batchAnomalieDetail->method('BatchCollecteAnomalieDetail')
            ->willReturn(['code' => 500, 'type' => 'error', 'message' => 'anomalie detail fail', 'erreur' => 'e']);

        $this->batchHotspot->expects($this->never())->method('batchCollecteHotspot');

        $result = $this->controller->collecte(self::PORTEFEUILLE, self::MAVEN_KEY, self::MODE, self::USER);

        $this->assertSame(500, $result['code']);
    }

    public function testCollecteStopsWhenHotspotFails(): void
    {
        $this->batchInfo->method('batchCollecteInformation')->willReturn($this->stubInfoHappy());
        $this->batchMesure->method('batchCollecteMesure')->willReturn($this->stubMesureHappy());
        $this->batchAnomalie->method('batchCollecteAnomalie')->willReturn($this->stubAnomalieHappy());
        $this->batchAnomalieDetail->method('BatchCollecteAnomalieDetail')->willReturn($this->stubAnomalieDetailHappy());
        $this->batchHotspot->method('batchCollecteHotspot')
            ->willReturn(['code' => 500, 'message' => 'hotspot fail', 'erreur' => 'e']);

        $this->batchHotspotDetail->expects($this->never())->method('batchCollecteHotspotDetail');

        $result = $this->controller->collecte(self::PORTEFEUILLE, self::MAVEN_KEY, self::MODE, self::USER);

        $this->assertSame(500, $result['code']);
    }

    public function testCollecteStopsWhenHotspotDetailFails(): void
    {
        $this->batchInfo->method('batchCollecteInformation')->willReturn($this->stubInfoHappy());
        $this->batchMesure->method('batchCollecteMesure')->willReturn($this->stubMesureHappy());
        $this->batchAnomalie->method('batchCollecteAnomalie')->willReturn($this->stubAnomalieHappy());
        $this->batchAnomalieDetail->method('BatchCollecteAnomalieDetail')->willReturn($this->stubAnomalieDetailHappy());
        $this->batchHotspot->method('batchCollecteHotspot')->willReturn($this->stubHotspotHappy());
        $this->batchHotspotDetail->method('batchCollecteHotspotDetail')
            ->willReturn(['code' => 500, 'message' => 'hotspot detail fail', 'erreur' => 'e']);

        $this->batchOwasp->expects($this->never())->method('batchCollecteOwasp');

        $result = $this->controller->collecte(self::PORTEFEUILLE, self::MAVEN_KEY, self::MODE, self::USER);

        $this->assertSame(500, $result['code']);
    }

    public function testCollecteStopsWhenOwaspFails(): void
    {
        $this->stubBatchesUpToOwasp();
        // override OWASP to fail
        $this->batchOwasp = $this->createMock(\App\Controller\Batch\BatchCollecteOwaspController::class);
        $this->batchOwasp->method('batchCollecteOwasp')
            ->willReturn(['code' => 500, 'message' => 'owasp fail', 'erreur' => 'e']);

        // rebuild controller with new batchOwasp
        $this->controller = new CollecteController(
            $this->em, $this->logger, $this->batchInfo, $this->batchMesure,
            $this->batchOwasp, $this->batchHotspot, $this->batchAnomalie,
            $this->batchAnomalieDetail, $this->batchHotspotOwasp, $this->batchHotspotDetail,
            $this->batchNoSonar, $this->batchTodo, $this->batchActuator, $this->batchLogger,
        );

        $result = $this->controller->collecte(self::PORTEFEUILLE, self::MAVEN_KEY, self::MODE, self::USER);

        $this->assertSame(500, $result['code']);
    }

    public function testCollecteStopsWhenNoSonarFails(): void
    {
        $this->stubAllBatchesHappy();
        // override noSonar to fail
        $this->batchNoSonar = $this->createMock(\App\Controller\Batch\BatchCollecteNoSonarController::class);
        $this->batchNoSonar->method('batchCollecteNoSonar')
            ->willReturn(['code' => 500, 'message' => 'nosonar fail', 'erreur' => 'e']);

        $this->controller = new CollecteController(
            $this->em, $this->logger, $this->batchInfo, $this->batchMesure,
            $this->batchOwasp, $this->batchHotspot, $this->batchAnomalie,
            $this->batchAnomalieDetail, $this->batchHotspotOwasp, $this->batchHotspotDetail,
            $this->batchNoSonar, $this->batchTodo, $this->batchActuator, $this->batchLogger,
        );

        $result = $this->controller->collecte(self::PORTEFEUILLE, self::MAVEN_KEY, self::MODE, self::USER);

        $this->assertSame(500, $result['code']);
    }

    public function testCollecteStopsWhenTodoFails(): void
    {
        $this->stubAllBatchesHappy();
        $this->batchTodo = $this->createMock(\App\Controller\Batch\BatchCollecteTodoController::class);
        $this->batchTodo->method('batchCollecteTodo')
            ->willReturn(['code' => 500, 'message' => 'todo fail', 'erreur' => 'e']);

        $this->controller = new CollecteController(
            $this->em, $this->logger, $this->batchInfo, $this->batchMesure,
            $this->batchOwasp, $this->batchHotspot, $this->batchAnomalie,
            $this->batchAnomalieDetail, $this->batchHotspotOwasp, $this->batchHotspotDetail,
            $this->batchNoSonar, $this->batchTodo, $this->batchActuator, $this->batchLogger,
        );

        $result = $this->controller->collecte(self::PORTEFEUILLE, self::MAVEN_KEY, self::MODE, self::USER);

        $this->assertSame(500, $result['code']);
    }

    /**
     * Seule une erreur interne Ma-Moulinette (recherche du point d'accès en base,
     * clé 'fatal' à true) stoppe encore la collecte du projet à l'étape Actuator.
     */
    public function testCollecteStopsWhenActuatorHasFatalError(): void
    {
        $this->stubAllBatchesHappy();
        $this->batchActuator = $this->createMock(\App\Controller\Batch\BatchCollecteActuatorController::class);
        $this->batchActuator->method('BatchCollecteActuatorInfo')
            ->willReturn(['code' => 500, 'message' => 'actuator fail', 'erreur' => 'e', 'json' => [], 'fatal' => true]);

        $this->controller = new CollecteController(
            $this->em, $this->logger, $this->batchInfo, $this->batchMesure,
            $this->batchOwasp, $this->batchHotspot, $this->batchAnomalie,
            $this->batchAnomalieDetail, $this->batchHotspotOwasp, $this->batchHotspotDetail,
            $this->batchNoSonar, $this->batchTodo, $this->batchActuator, $this->batchLogger,
        );

        $result = $this->controller->collecte(self::PORTEFEUILLE, self::MAVEN_KEY, self::MODE, self::USER);

        $this->assertSame(500, $result['code']);
    }

    /**
     * MODIF 2026-07-23 : Actuator est "best effort" — un échec non fatal (endpoint
     * distant injoignable, erreur HTTP, timeout) ne stoppe plus la collecte du
     * projet : le JSON d'échec est simplement transmis à l'historique (pastille
     * rouge côté page Projet) et le reste de la collecte se termine normalement.
     */
    public function testCollecteContinuesWhenActuatorFailsNonFatally(): void
    {
        $this->stubAllBatchesHappy();
        $this->batchActuator = $this->createMock(\App\Controller\Batch\BatchCollecteActuatorController::class);
        $failureJson = ['date_extraction' => '2026-07-23 00:00:00', 'code' => 500, 'message' => 'Timeout.'];
        $this->batchActuator->method('BatchCollecteActuatorInfo')
            ->willReturn(['code' => 500, 'erreur' => ['Timeout.'], 'json' => $failureJson]);

        $this->historiqueRepo->expects($this->once())
            ->method('insertHistoriqueAjoutProjet')
            ->with($this->anything(), $failureJson)
            ->willReturn(['code' => 200]);

        $this->controller = new CollecteController(
            $this->em, $this->logger, $this->batchInfo, $this->batchMesure,
            $this->batchOwasp, $this->batchHotspot, $this->batchAnomalie,
            $this->batchAnomalieDetail, $this->batchHotspotOwasp, $this->batchHotspotDetail,
            $this->batchNoSonar, $this->batchTodo, $this->batchActuator, $this->batchLogger,
        );

        $result = $this->controller->collecte(self::PORTEFEUILLE, self::MAVEN_KEY, self::MODE, self::USER);

        $this->assertSame(200, $result['code']);
    }

    public function testCollecteStopsWhenLoggerFails(): void
    {
        $this->stubAllBatchesHappy();
        $this->batchLogger = $this->createMock(\App\Controller\Batch\BatchCollecteLoggerController::class);
        $this->batchLogger->method('BatchCollecteLogger')
            ->willReturn(['code' => 500, 'message' => 'logger fail', 'erreur' => 'e']);

        $this->controller = new CollecteController(
            $this->em, $this->logger, $this->batchInfo, $this->batchMesure,
            $this->batchOwasp, $this->batchHotspot, $this->batchAnomalie,
            $this->batchAnomalieDetail, $this->batchHotspotOwasp, $this->batchHotspotDetail,
            $this->batchNoSonar, $this->batchTodo, $this->batchActuator, $this->batchLogger,
        );

        $result = $this->controller->collecte(self::PORTEFEUILLE, self::MAVEN_KEY, self::MODE, self::USER);

        $this->assertSame(500, $result['code']);
    }

    // ═══════════════════════ Sanitisation HTML ═════════════════════════════

    public function testCollecteSanitizesMavenKeyAndModeToAvoidHtmlInjection(): void
    {
        $this->stubAllBatchesHappy();
        $this->historiqueRepo->method('insertHistoriqueAjoutProjet')->willReturn(['code' => 200]);

        $result = $this->controller->collecte(
            self::PORTEFEUILLE,
            'fr.ma-moulinette:<script>alert(1)</script>',
            'manual<img>',
            self::USER
        );

        $this->assertSame(200, $result['code']);
        // Les balises HTML doivent être échappées dans le compte-rendu
        $this->assertStringNotContainsString('<script>alert(1)</script>', $result['compte_rendu']);
        $this->assertStringNotContainsString('<img>', $result['compte_rendu']);
        $this->assertStringContainsString('&lt;script&gt;', $result['compte_rendu']);
    }

    // ═══════════════════════ stubs happy ═══════════════════════════════════

    private function stubAllBatchesHappy(): void
    {
        $this->batchInfo->method('batchCollecteInformation')->willReturn($this->stubInfoHappy());
        $this->batchMesure->method('batchCollecteMesure')->willReturn($this->stubMesureHappy());
        $this->batchAnomalie->method('batchCollecteAnomalie')->willReturn($this->stubAnomalieHappy());
        $this->batchAnomalieDetail->method('BatchCollecteAnomalieDetail')->willReturn($this->stubAnomalieDetailHappy());
        $this->batchHotspot->method('batchCollecteHotspot')->willReturn($this->stubHotspotHappy());
        $this->batchHotspotDetail->method('batchCollecteHotspotDetail')->willReturn($this->stubHotspotDetailHappy());
        $this->batchOwasp->method('batchCollecteOwasp')->willReturn([
            'code' => 200, 'message' => 'owasp ok',
            'owasp2017' => 3, 'owasp2021' => 5,
        ]);
        $this->batchHotspotOwasp->method('batchCollecteHotspotOwasp')->willReturn([
            'code' => 200, 'message' => 'hotspot owasp ok',
            'data' => [], // empty → html non généré
            'info' => 'mode', 'owasp_2017' => 0, 'owasp_2021' => 0,
        ]);
        // MODIF 2026-06-07 : clés no_sonar par langage (CollecteController:762-768)
        $this->batchNoSonar->method('batchCollecteNoSonar')->willReturn([
            'code' => 200, 'message' => 'nosonar ok',
            'historique' => [
                'java_no_sonar' => 1, 'python_no_sonar' => 0, 'php_no_sonar' => 0,
                'check_style' => 0, 'no_pmd' => 0, 'suppress_warning' => 2,
                'total_no_sonar' => 1,
            ],
        ]);
        // MODIF 2026-06-07 : clés to do par langage (CollecteController:813-821)
        $this->batchTodo->method('batchCollecteTodo')->willReturn([
            'code' => 200, 'message' => 'todo ok',
            'historique' => [
                'java_todo' => 5, 'python_todo' => 0, 'php_todo' => 1,
                'xml_todo' => 0, 'web_todo' => 0, 'javascript_todo' => 1,
                'typescript_todo' => 0, 'ruby_todo' => 0, 'total_todo' => 7,
            ],
        ]);
        $this->batchActuator->method('BatchCollecteActuatorInfo')->willReturn([
            'code' => 200, 'message' => 'actuator ok',
            'dataJson' => ['build' => []],
            'json' => ['date_extraction' => '2026-07-23 00:00:00', 'code' => 200, 'message' => 'actuator ok'],
        ]);
        $this->batchLogger->method('BatchCollecteLogger')->willReturn([
            'code' => 200, 'message' => 'logger ok',
            'historique' => [
                'logger_info' => 1, 'logger_warn' => 2,
                'logger_error' => 3, 'logger_debug' => 4,
            ],
        ]);
    }

    private function stubBatchesUpToOwasp(): void
    {
        $this->batchInfo->method('batchCollecteInformation')->willReturn($this->stubInfoHappy());
        $this->batchMesure->method('batchCollecteMesure')->willReturn($this->stubMesureHappy());
        $this->batchAnomalie->method('batchCollecteAnomalie')->willReturn($this->stubAnomalieHappy());
        $this->batchAnomalieDetail->method('BatchCollecteAnomalieDetail')->willReturn($this->stubAnomalieDetailHappy());
        $this->batchHotspot->method('batchCollecteHotspot')->willReturn($this->stubHotspotHappy());
        $this->batchHotspotDetail->method('batchCollecteHotspotDetail')->willReturn($this->stubHotspotDetailHappy());
        $this->batchOwasp->method('batchCollecteOwasp')->willReturn([
            'code' => 200, 'message' => 'owasp ok',
            'owasp2017' => 0, 'owasp2021' => 0,
        ]);
    }

    /**
     * @return array{
     *     code: int,
     *     message: string,
     *     historique: array{
     *         analyse_key: string,
     *         version: string,
     *         date_version: string,
     *         version_release: int, version_snapshot: int, version_autre: int,
     *         version_release_sonar: int, version_snapshot_sonar: int,
     *         version_autre_sonar: int, version_sonar: int,
     *     },
     * }
     */
    private function stubInfoHappy(): array
    {
        return [
            'code' => 200,
            'message' => 'info ok',
            'historique' => [
                'analyse_key' => 'AY1',
                'version' => '1.0.0',
                'date_version' => '2026-04-22',
                'version_release' => 1, 'version_snapshot' => 0, 'version_autre' => 0,
                'version_release_sonar' => 1, 'version_snapshot_sonar' => 0,
                'version_autre_sonar' => 0, 'version_sonar' => 1,
            ],
        ];
    }

    // MODIF 2026-06-07 : historique complété (~80 clés lues par CollecteController:305-385)
    /**
     * @return array{
     *     code: int,
     *     message: string,
     *     data: array{maven_key: string},
     *     historique: array<string, int|float|string>,
     * }
     */
    private function stubMesureHappy(): array
    {
        return [
            'code' => 200, 'message' => 'mesure ok',
            'data' => ['maven_key' => self::MAVEN_KEY],
            'historique' => [
                // volume
                'lines' => 1000, 'ncloc' => 800, 'ncloc_language_distribution' => 'java=800',
                'files' => 30, 'classes' => 50, 'functions' => 200,
                // commentaires
                'comment_lines' => 100, 'comment_lines_density' => 12.5, 'comment_lines_rating' => 'A',
                // couverture
                'coverage' => 80.0, 'branch_coverage' => 75.0, 'line_coverage' => 82.0,
                'lines_to_cover' => 600, 'conditions_to_cover' => 200, 'uncovered_conditions' => 50,
                'coverage_rating' => 'B',
                // tests
                'tests' => 150, 'test_execution_time' => 3200, 'test_errors' => 0,
                'test_failures' => 0, 'skipped_tests' => 2, 'test_success_density' => 98.7,
                // duplication
                'duplicated_blocks' => 3, 'duplicated_files' => 2, 'duplicated_lines' => 40,
                'duplicated_lines_density' => 2.0, 'duplicated_lines_rating' => 'A',
                // issues
                'open_issues' => 10, 'reopened_issues' => 1, 'confirmed_issues' => 2,
                'false_positive_issues' => 0, 'high_impact_accepted_issues' => 0, 'accepted_issues' => 0,
                'violations' => 10, 'blocker_violations' => 0, 'critical_violations' => 1,
                'major_violations' => 5, 'minor_violations' => 3, 'info_violations' => 1,
                'software_quality_blocker_issues' => 0, 'software_quality_high_issues' => 1,
                'software_quality_info_issues' => 0, 'software_quality_low_issues' => 2,
                'software_quality_medium_issues' => 3,
                // complexité
                'complexity' => 42, 'cognitive_complexity' => 38, 'complexity_ratio' => 5.2,
                'cognitive_complexity_ratio' => 4.7, 'complexity_rating' => 'A', 'cognitive_complexity_rating' => 'A',
                // maintenabilité
                'code_smells' => 5, 'sqale_index' => 120, 'sqale_rating' => 'A', 'sqale_debt_ratio' => 1.5,
                'maintainability_issues' => 5, 'software_quality_maintainability_rating' => 'A',
                'software_quality_maintainability_debt_ratio' => 1.2,
                'effort_to_reach_maintainability_rating_a' => 0,
                'software_quality_maintainability_remediation_effort' => 60,
                'software_quality_maintainability_issues' => 4,
                'effort_to_reach_software_quality_maintainability_rating_a' => 0,
                // fiabilité
                'bugs' => 2, 'reliability_rating' => 'B', 'reliability_remediation_effort' => 30,
                'reliability_issues' => 2, 'software_quality_reliability_rating' => 'B',
                'software_quality_reliability_remediation_effort' => 30,
                'software_quality_reliability_issues' => 2,
                // sécurité
                'vulnerabilities' => 1, 'security_rating' => 'A', 'security_remediation_effort' => 10,
                'software_quality_security_rating' => 'A',
                'software_quality_security_remediation_effort' => 10,
                'software_quality_security_issues' => 1,
                'security_hotspots' => 0, 'security_hotspots_reviewed' => 0, 'security_review_rating' => 'A',
                // identité
                'maven_key' => self::MAVEN_KEY, 'project_name' => 'App',
                'mode_collecte' => 'collecte', 'utilisateur_collecte' => 'u',
            ],
        ];
    }

    /**
     * @return array{
     *     code: int,
     *     message: string,
     *     data: array<string, int|string>,
     *     historique: array<string, int>,
     * }
     */
    private function stubAnomalieHappy(): array
    {
        return [
            'code' => 200, 'message' => 'anomalie ok',
            'data' => [
                'anomalie_total' => 10, 'dette' => '1h', 'dette_minute' => 60,
                'dette_reliability' => '15m', 'dette_reliability_minute' => 15,
                'dette_vulnerability' => '15m', 'dette_vulnerability_minute' => 15,
                'dette_code_smell' => '30m', 'dette_code_smell_minute' => 30,
                'bug' => 2, 'vulnerability' => 3, 'code_smell' => 5,
                'frontend' => 1, 'backend' => 2, 'autre' => 3, 'inconnu' => 4,
                'blocker' => 1, 'critical' => 2, 'major' => 3, 'info' => 2, 'minor' => 2,
            ],
            'historique' => ['bug' => 2, 'vulnerability' => 3],
        ];
    }

    /**
     * @return array{code: int, message: string, historique: array<string, int>}
     */
    private function stubAnomalieDetailHappy(): array
    {
        $historique = [];
        foreach (['bug', 'vulnerability', 'code_smell'] as $type) {
            foreach (['blocker', 'critical', 'major', 'minor', 'info'] as $sev) {
                $historique["{$type}_{$sev}"] = 1;
            }
        }
        return [
            'code' => 200, 'message' => 'detail ok',
            'historique' => $historique,
        ];
    }

    /**
     * @return array{code: int, message: string, historique: array{hotspot_high: int, hotspot_medium: int, hotspot_low: int, nombre_hotspot: int}}
     */
    private function stubHotspotHappy(): array
    {
        return [
            'code' => 200, 'message' => 'hotspot ok',
            'historique' => [
                'hotspot_high' => 1, 'hotspot_medium' => 2,
                'hotspot_low' => 3, 'nombre_hotspot' => 6,
            ],
        ];
    }

    // MODIF 2026-06-07 : renommage review_→to_review_ (CollecteController:604-608)
    /**
     * @return array{
     *     code: int,
     *     message: string,
     *     nombre: int,
     *     historique: array{
     *         menace_potentielle_totale: int,
     *         menace_potentielle_to_review_high: int,
     *         menace_potentielle_to_review_medium: int,
     *         menace_potentielle_to_review_low: int,
     *         menace_potentielle_reviewed_high: int,
     *         menace_potentielle_reviewed_medium: int,
     *         menace_potentielle_reviewed_low: int,
     *     },
     * }
     */
    private function stubHotspotDetailHappy(): array
    {
        return [
            'code' => 200, 'message' => 'hotspot detail ok',
            'nombre' => 6,
            'historique' => [
                'menace_potentielle_totale' => 6,
                'menace_potentielle_to_review_high' => 1,
                'menace_potentielle_to_review_medium' => 1,
                'menace_potentielle_to_review_low' => 1,
                'menace_potentielle_reviewed_high' => 1,
                'menace_potentielle_reviewed_medium' => 1,
                'menace_potentielle_reviewed_low' => 1,
            ],
        ];
    }
}
