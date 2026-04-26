<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Batch;

use App\Controller\Batch\BatchCollecteActuatorController;
use App\Controller\Batch\BatchCollecteAnomalieController;
use App\Controller\Batch\BatchCollecteAnomalieDetailController;
use App\Controller\Batch\BatchCollecteHotspotController;
use App\Controller\Batch\BatchCollecteHotspotDetailController;
use App\Controller\Batch\BatchCollecteHotspotOwaspController;
use App\Controller\Batch\BatchCollecteInformationProjetController;
use App\Controller\Batch\BatchCollecteLoggerController;
use App\Controller\Batch\BatchCollecteMesureController;
use App\Controller\Batch\BatchCollecteNoSonarController;
use App\Controller\Batch\BatchCollecteOwaspController;
use App\Controller\Batch\BatchCollecteTodoController;
use App\Controller\Batch\CollecteController;
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
    private const MAVEN_KEY = 'com.acme:app';
    private const MODE = 'manual';
    private const USER = 'admin@acme.fr';

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
            ->willReturn(['code' => 500, 'type' => 'alert', 'message' => 'anomalie fail', 'erreur' => 'e']);

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
                'type' => 'alert',
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
            ->willReturn(['code' => 500, 'type' => 'alert', 'message' => 'anomalie detail fail', 'erreur' => 'e']);

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

    public function testCollecteStopsWhenActuatorFails(): void
    {
        $this->stubAllBatchesHappy();
        $this->batchActuator = $this->createMock(\App\Controller\Batch\BatchCollecteActuatorController::class);
        $this->batchActuator->method('BatchCollecteActuatorInfo')
            ->willReturn(['code' => 500, 'message' => 'actuator fail', 'erreur' => 'e']);

        $this->controller = new CollecteController(
            $this->em, $this->logger, $this->batchInfo, $this->batchMesure,
            $this->batchOwasp, $this->batchHotspot, $this->batchAnomalie,
            $this->batchAnomalieDetail, $this->batchHotspotOwasp, $this->batchHotspotDetail,
            $this->batchNoSonar, $this->batchTodo, $this->batchActuator, $this->batchLogger,
        );

        $result = $this->controller->collecte(self::PORTEFEUILLE, self::MAVEN_KEY, self::MODE, self::USER);

        $this->assertSame(500, $result['code']);
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
            'com.acme:<script>alert(1)</script>',
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
        $this->batchNoSonar->method('batchCollecteNoSonar')->willReturn([
            'code' => 200, 'message' => 'nosonar ok',
            'historique' => ['no_sonar' => 1, 'suppress_warning' => 2],
        ]);
        $this->batchTodo->method('batchCollecteTodo')->willReturn([
            'code' => 200, 'message' => 'todo ok',
            'historique' => ['todo' => 7],
        ]);
        $this->batchActuator->method('BatchCollecteActuatorInfo')->willReturn([
            'code' => 200, 'message' => 'actuator ok',
            'dataJson' => ['json' => ['build' => []]],
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

    private function stubMesureHappy(): array
    {
        return [
            'code' => 200, 'message' => 'mesure ok',
            'data' => ['maven_key' => self::MAVEN_KEY],
            'historique' => [
                'nom_projet' => 'App', 'nombre_ligne' => 1000, 'nombre_ligne_code' => 800,
                'nombre_classes' => 50, 'nombre_functions' => 200, 'nombre_files' => 30,
                'language_distribution' => ['java' => 100],
                'sqale_debt_ratio' => 1.5, 'coverage' => 80.0,
                'duplicated_lines_density' => 2.0, 'tests' => 150, 'issues' => 10,
            ],
        ];
    }

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

    private function stubHotspotDetailHappy(): array
    {
        return [
            'code' => 200, 'message' => 'hotspot detail ok',
            'nombre' => 6,
            'historique' => [
                'menace_potentielle_totale' => 6,
                'menace_potentielle_review_high' => 1,
                'menace_potentielle_review_medium' => 1,
                'menace_potentielle_review_low' => 1,
                'menace_potentielle_reviewed_high' => 1,
                'menace_potentielle_reviewed_medium' => 1,
                'menace_potentielle_reviewed_low' => 1,
            ],
        ];
    }
}
