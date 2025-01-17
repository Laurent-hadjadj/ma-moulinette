<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Batch;

use App\Controller\Batch\CollecteController;
use App\Service\FileLogger;
use Doctrine\ORM\EntityManagerInterface;
use App\Controller\Batch\BatchCollecteActuatorController;
use App\Controller\Batch\BatchCollecteInformationProjetController;
use App\Controller\Batch\BatchCollecteMesureController;
use App\Controller\Batch\BatchCollecteNoteController;
use App\Controller\Batch\BatchCollecteOwaspController;
use App\Controller\Batch\BatchCollecteHotspotController;
use App\Controller\Batch\BatchCollecteAnomalieController;
use App\Controller\Batch\BatchCollecteAnomalieDetailController;
use App\Controller\Batch\BatchCollecteHotspotOwaspController;
use App\Controller\Batch\BatchCollecteHotspotDetailController;
use App\Controller\Batch\BatchCollecteNoSonarController;
use App\Controller\Batch\BatchCollecteTodoController;
use App\Controller\Batch\BatchCollecteLoggerController;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Psr\Container\ContainerInterface;

/**
 * [Description CollecteControllerTest]
 */
class CollecteControllerTest extends TestCase
{
    private EntityManagerInterface $em;
    private FileLogger $logger;

    private CollecteController $controller;
    private ContainerInterface $container;
    private ParameterBagInterface $parameterBag;

    private BatchCollecteInformationProjetController $batchCollecteInformation;
    private BatchCollecteMesureController $batchCollecteMesure;
    private BatchCollecteNoteController $batchCollecteNote;
    private BatchCollecteOwaspController $batchCollecteOwasp;
    private BatchCollecteHotspotController $batchCollecteHotspot;
    private BatchCollecteAnomalieController $batchCollecteAnomalie;
    private BatchCollecteAnomalieDetailController $batchCollecteAnomalieDetail;
    private BatchCollecteHotspotOwaspController $batchCollecteHotspotOwasp;
    private BatchCollecteHotspotDetailController $batchCollecteHotspotDetail;
    private BatchCollecteNoSonarController $batchCollecteNoSonar;
    private BatchCollecteTodoController $batchCollecteTodo;
    private BatchCollecteActuatorController $batchCollecteActuator;
    private BatchCollecteLoggerController $batchCollecteLogger;

    private static $anomalieDetail = 'Anomalie Detail';
    private static $hotspotDetail = 'Hotspot Detail';
    private static $owaspA0 = 'Owasp a0';
    private static $owaspA1 = 'Owasp a1';
    private static $owaspA2 = 'Owasp a2';
    private static $owaspA3 = 'Owasp a3';
    private static $owaspA4 = 'Owasp a4';
    private static $owaspA5 = 'Owasp a5';
    private static $owaspA6 = 'Owasp a6';
    private static $owaspA7 = 'Owasp a7';
    private static $owaspA8 = 'Owasp a8';
    private static $owaspA9 = 'Owasp a9';
    private static $erreurCollecte = 'Erreur lors de la collecte des informations';
    private static $informationProjet = 'Information projet';
    private static $s09Owasp = '09 - OWASP';
    private static $s11NoSonar = '11 - NOSONAR';
    private static $s12Todo = '12 - TODO';
    private static $s14LoggerActuator = '14 - LoggerActuator';
    private static $owaspA0A10 = 'Owasp a0-a10';
    private static $noteHotspot = 'Note Hotspot';

    protected function setUp(): void
    {
        // Mock L'entity Manager
        $this->em = $this->createMock(EntityManagerInterface::class);

        // Mock FileLogger
        $this->logger = $this->createMock(FileLogger::class);

        // Mock ParameterBagInterface
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);

        // Mock ContainerInterface
        $this->container = $this->createMock(ContainerInterface::class);
        $this->container->method('has')->with('parameter_bag')->willReturn(true);
        $this->container->method('get')->with('parameter_bag')->willReturn($this->parameterBag);

        // Mock BatchCollecte controllers
        $this->batchCollecteInformation = $this->createMock(BatchCollecteInformationProjetController::class);
        $this->batchCollecteMesure = $this->createMock(BatchCollecteMesureController::class);
        $this->batchCollecteNote = $this->createMock(BatchCollecteNoteController::class);
        $this->batchCollecteOwasp = $this->createMock(BatchCollecteOwaspController::class);
        $this->batchCollecteHotspot = $this->createMock(BatchCollecteHotspotController::class);
        $this->batchCollecteAnomalie = $this->createMock(BatchCollecteAnomalieController::class);
        $this->batchCollecteAnomalieDetail = $this->createMock(BatchCollecteAnomalieDetailController::class);
        $this->batchCollecteHotspotOwasp = $this->createMock(BatchCollecteHotspotOwaspController::class);
        $this->batchCollecteHotspotDetail = $this->createMock(BatchCollecteHotspotDetailController::class);
        $this->batchCollecteNoSonar = $this->createMock(BatchCollecteNoSonarController::class);
        $this->batchCollecteTodo = $this->createMock(BatchCollecteTodoController::class);
        $this->batchCollecteActuator = $this->createMock(BatchCollecteActuatorController::class);
        $this->batchCollecteLogger = $this->createMock(BatchCollecteLoggerController::class);

        // Instantiate the controller with mocked dependencies
        $this->controller = new CollecteController($this->em, $this->logger, $this->batchCollecteInformation, $this->batchCollecteMesure, $this->batchCollecteNote, $this->batchCollecteOwasp, $this->batchCollecteHotspot, $this->batchCollecteAnomalie, $this->batchCollecteAnomalieDetail, $this->batchCollecteHotspotOwasp, $this->batchCollecteHotspotDetail, $this->batchCollecteNoSonar, $this->batchCollecteTodo, $this->batchCollecteActuator, $this->batchCollecteLogger);
        $this->controller->setContainer($this->container);
    }

    public function testCollecteSuccess()
    {
        // Mock pour chaque service
        $this->batchCollecteInformation->method('batchCollecteInformation')->willReturn([
            'code' => 200,
            'message' => 'informatonProjet',
            'data' => ['analyse_key' => 'azerty1o',
            'version_release' => 5, 'version_snapshot' => 2, 'version_autre' => 3,
            'version' => '1.0.0-RELEASE', 'date_version' => '2024-08-09']
        ]);

        $this->batchCollecteMesure->method('batchCollecteMesure')->willReturn([
            'code' => 200,
            'message' => 'Mesure',
            'data' => ['nom_projet' => 'maven_key', 'nombre_ligne' => 100, 'nombre_ligne_code' => 1500, 'language_distribution' => ['java' => 60, 'php' => 40],  'sqale_debt_ratio' => 1.23, 'coverage' => 75, 'duplicated_lines_density' => 5, 'tests' => 20, 'issues' => 3,
            ]
        ]);

        $this->batchCollecteNote->method('batchCollecteNote')
            ->withConsecutive(
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'reliability'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'security'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'sqale']
            )
            ->willReturnOnConsecutiveCalls(
                ['code' => 200, 'message' => ['value' => 'A'], 'data' => ['note_reliability' => 'A']],
                ['code' => 200, 'message' => ['value' => 'B'], 'data' => ['note_security' => 'B']],
                ['code' => 200, 'message' => ['value' => 'C'], 'data' => ['note_sqale' => 'C']]
            );

        $this->batchCollecteAnomalie->method('batchCollecteAnomalie')->willReturn([
            'code' => 200,
            'message' => 'Anomalie',
            'data' => ['violations' => 1, 'dette' => 120, 'nombre_bug' => 2, 'nombre_vulnerability' => 0,
            'nombre_code_smell' => 0, 'frontend' => 0, 'backend' => 0, 'autre' => 0, 'nombre_anomalie_bloquant' => 1,
            'nombre_anomalie_critique' => 0, 'nombre_anomalie_info' => 0, 'nombre_anomalie_majeur' => 0,   'nombre_anomalie_mineur' => 0]]);

        $this->batchCollecteAnomalieDetail->method('BatchCollecteAnomalieDetail')->willReturn([
            'code' => 200,
            'message' => static::$anomalieDetail,
            'data' => ['bug_blocker' => 1, 'bug_critical' => 1, 'bug_major' => 1, 'bug_minor' => 1, 'bug_info' => 1, 'bug_critical' => 0, 'bug_major' => 0, 'bug_minor' => 0, 'bug_info' => 0, 'vulnerability_blocker' => 1, 'vulnerability_critical' => 1, 'vulnerability_major' => 1, 'vulnerability_minor' => 1, 'vulnerability_info' => 1, 'vulnerability_critical' => 0, 'vulnerability_major' => 0, 'vulnerability_minor' => 0, 'vulnerability_info' => 0, 'code_smell_blocker' => 1, 'code_smell_critical' => 1, 'code_smell_major' => 1, 'code_smell_minor' => 1, 'code_smell_info' => 1, 'code_smell_critical' => 0, 'code_smell_major' => 0, 'code_smell_minor' => 0, 'code_smell_info' => 0]]);

        $this->batchCollecteHotspot->method('batchCollecteHotspot')->willReturn([
            'code' => 200,
            'message' => 'Hotspot',
            'data' => ['hotspot_high' => 1, 'hotspot_medium' => 0,
            'hotspot_low' => 10, 'nombre_hotspot' => 11]]);

        $this->batchCollecteNote->method('batchCollecteNoteHotspot')->willReturn([
            'code' => 200,
            'message' => static::$noteHotspot,
            'data' => ['note_hotspot' => 'A']]);

        $this->batchCollecteHotspotDetail->method('batchCollecteHotspotDetail')->willReturn([
            'code' => 200,
            'message' => static::$hotspotDetail,
        ]);
        $this->batchCollecteOwasp->method('batchCollecteOwasp')->willReturn([
            'code' => 200,
            'message' => ['nombre' => 10],
            'data' => ['effort_total' => 150],
            'nombre' => 10]);

            $message = 'A0 : Effacement des données de la table hotspotOwasp pour le projet.';

            // Les valeurs pour 'withConsecutive'
            $consecutiveParams = [];
            for ($i = 0; $i <= 10; $i++) {
                $consecutiveParams[] = ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a' . $i];
            }

            // Les valeurs pour 'willReturnOnConsecutiveCalls'
            $consecutiveReturns = [
                ['code' => 200, 'info' => 'effacement', 'message' => $message, 'owasp_2017' => '', 'owasp_2021' => '', 'data' => []]
            ];

            for ($i = 1; $i <= 10; $i++) {
                $consecutiveReturns[] = [
                    'code' => 200,
                    'info' => 'enregistrement',
                    'owasp_2017' => 10,
                    'owasp_2021' => 2,
                    'message' => '',
                    'data' => [
                        'referentiel_owasp' => 2017,
                        'menace' => 'a' . $i,
                        'security_category' => 'NC',
                        'rule_key' => 'NC',
                        'probability' => 'NC',
                        'status' => 'NC',
                        'resolution' => '',
                        'niveau' => -1
                    ]
                ];
            }

            // Appel optimisé
            $this->batchCollecteHotspotOwasp->method('batchCollecteHotspotOwasp')
                ->withConsecutive(...$consecutiveParams)
                ->willReturnOnConsecutiveCalls(...$consecutiveReturns);


        $this->batchCollecteNoSonar->method('batchCollecteNoSonar')->willReturn([
            'code' => 200,
            'message' => ['suppress_warning' => 1, 'no_sonar' => 1],
            'data' => ['suppress_warning' => 1, 'no_sonar' => 1]
        ]);
        $this->batchCollecteTodo->method('batchCollecteTodo')->willReturn([
            'code' => 200,
            'nombre' => 5,
            'message' => ['todo'=>['rule' => 'java:007', 'component' => '/src/cool/toto.java', 'line' => 10]],
            'data' => ['todo'=>5]]);

        $this->batchCollecteActuator->method('BatchCollecteActuatorInfo')->willReturn([
            'code' => 200,
            'message' => ['json' => '{}']
        ]);

        $this->batchCollecteLogger->method('BatchCollecteLogger')->willReturn([
                'code' => 200,
                'message' => [
                    'logger_info' => 1,
                    'logger_warn' => 1,
                    'logger_error' => 1,
                    'logger_debug' => 1,
                ],
                'data' => [
                    'logger_info' => ['total' => 1],
                    'logger_warn' => ['total' => 1],
                    'logger_error' => ['total' => 1],
                    'logger_debug' => ['total' => 1]
                ]]);

        $historiqueRepository = $this->createMock(\App\Repository\HistoriqueRepository::class);
        $this->em->method('getRepository')->willReturn($historiqueRepository);
        $historiqueRepository->method('insertHistoriqueAjoutProjet')->willReturn([
            'code' => 200
        ]);

        $result = $this->controller->collecte('portefeuille', 'maven_key', 'mode_collecte', 'utilisateur_collecte');

        $this->assertSame(200, $result['code']);
        $this->assertArrayHasKey('Collecte', $result);
        $this->assertArrayHasKey('data', $result);

        $collecte= $result['Collecte'];
        $this->assertArrayHasKey('01 - INFORMATION PROJET', $collecte[1]);
        $this->assertArrayHasKey('02 - MESURE', $collecte[2]);
        $this->assertArrayHasKey('03 - NOTE RELIABILITY', $collecte[3]);
        $this->assertArrayHasKey('03 - NOTE SECURITY', $collecte[4]);
        $this->assertArrayHasKey('03 - NOTE SQALE', $collecte[5]);
        $this->assertArrayHasKey('04 - ANOMALIE', $collecte[6]);
        $this->assertArrayHasKey('05 - ANOMALIE DETAIL', $collecte[7]);
        $this->assertArrayHasKey('06 - HOTSPOT', $collecte[8]);
        $this->assertArrayHasKey('07 - NOTE HOTSPOT', $collecte[9]);
        $this->assertArrayHasKey('08 - HOTSPOT DETAIL', $collecte[10]);
        $this->assertArrayHasKey(static::$s09Owasp, $collecte[11]);
        $this->assertArrayHasKey('10 - HOTSPOT OWASP A0', $collecte[12]);
        $this->assertArrayHasKey('10 - HOTSPOT OWASP A1', $collecte[13]);
        $this->assertArrayHasKey('10 - HOTSPOT OWASP A2', $collecte[14]);
        $this->assertArrayHasKey('10 - HOTSPOT OWASP A3', $collecte[15]);
        $this->assertArrayHasKey('10 - HOTSPOT OWASP A4', $collecte[16]);
        $this->assertArrayHasKey('10 - HOTSPOT OWASP A5', $collecte[17]);
        $this->assertArrayHasKey('10 - HOTSPOT OWASP A6', $collecte[18]);
        $this->assertArrayHasKey('10 - HOTSPOT OWASP A7', $collecte[19]);
        $this->assertArrayHasKey('10 - HOTSPOT OWASP A8', $collecte[20]);
        $this->assertArrayHasKey('10 - HOTSPOT OWASP A9', $collecte[21]);
        $this->assertArrayHasKey('10 - HOTSPOT OWASP A10', $collecte[22]);
        $this->assertArrayHasKey(static::$s11NoSonar, $collecte[23]);
        $this->assertArrayHasKey(static::$s12Todo, $collecte[24]);
        $this->assertArrayHasKey('13 - Actuator', $collecte[25]);
        $this->assertArrayHasKey(static::$s14LoggerActuator, $collecte[26]);

        $data = $result['data'];

        $this->assertArrayHasKey('analyse_key', $result['data']);

        /** InformationProjet */
        $this->assertArrayHasKey('analyse_key', $result['data']);
        $this->assertEquals('azerty1o', $data['analyse_key']);
        $this->assertEquals( 5, $data['version_release']);
        $this->assertEquals(2, $data['version_snapshot']);
        $this->assertEquals(3, $data['version_autre']);
        $this->assertEquals('1.0.0-RELEASE', $data['version']);
        $this->assertEquals('2024-08-09', $data['date_version']);

        /** Mesures */
        $this->assertEquals('maven_key', $data['nom_projet']);
        $this->assertEquals(100, $data['nombre_ligne']);
        $this->assertEquals(1500, $data['nombre_ligne_code']);
        $this->assertEquals(['java' => 60, 'php' => 40], $data['language_distribution']);
        $this->assertEquals(1.23, $data['sqale_debt_ratio']);
        $this->assertEquals(75, $data['coverage']);
        $this->assertEquals(5, $data['duplicated_lines_density']);
        $this->assertEquals(20, $data['tests']);
        $this->assertEquals(3, $data['issues']);

        /** Notes */
        $this->assertEquals('A', $data['note_reliability']);
        $this->assertEquals('B', $data['note_security']);
        $this->assertEquals('C', $data['note_sqale']);
        $this->assertEquals('A', $data['note_hotspot']);

        /** Anomalies */
        $this->assertEquals(1, $data['violations']);
        $this->assertEquals(120, $data['dette']);
        $this->assertEquals(2, $data['nombre_bug']);
        $this->assertEquals(0, $data['nombre_vulnerability']);
        $this->assertEquals(0, $data['nombre_code_smell']);
        $this->assertEquals(0, $data['frontend']);
        $this->assertEquals(0, $data['backend']);
        $this->assertEquals(0, $data['autre']);
        $this->assertEquals(1, $data['nombre_anomalie_bloquant']);
        $this->assertEquals(0, $data['nombre_anomalie_critique']);
        $this->assertEquals(0, $data['nombre_anomalie_info']);
        $this->assertEquals(0, $data['nombre_anomalie_majeur']);
        $this->assertEquals(0, $data['nombre_anomalie_mineur']);

        /** AnomalieDetails */
        $this->assertEquals(1, $data['bug_blocker']);
        $this->assertEquals(0, $data['bug_critical']);
        $this->assertEquals(0, $data['bug_major']);
        $this->assertEquals(0, $data['bug_minor']);
        $this->assertEquals(0, $data['bug_info']);
        $this->assertEquals(0, $data['bug_critical']);
        $this->assertEquals(0, $data['bug_major']);
        $this->assertEquals(0, $data['bug_minor']);
        $this->assertEquals(0, $data['bug_info']);
        $this->assertEquals(1, $data['vulnerability_blocker']);
        $this->assertEquals(0, $data['vulnerability_critical']);
        $this->assertEquals(0, $data['vulnerability_major']);
        $this->assertEquals(0, $data['vulnerability_minor']);
        $this->assertEquals(0, $data['vulnerability_info']);
        $this->assertEquals(0, $data['vulnerability_critical']);
        $this->assertEquals(0, $data['vulnerability_major']);
        $this->assertEquals(0, $data['vulnerability_minor']);
        $this->assertEquals(0, $data['vulnerability_minor']);
        $this->assertEquals(0, $data['vulnerability_info']);
        $this->assertEquals(1, $data['code_smell_blocker']);
        $this->assertEquals(0, $data['code_smell_critical']);
        $this->assertEquals(0, $data['code_smell_major']);
        $this->assertEquals(0, $data['code_smell_minor']);
        $this->assertEquals(0, $data['code_smell_info']);
        $this->assertEquals(0, $data['code_smell_critical']);
        $this->assertEquals(0, $data['code_smell_major']);
        $this->assertEquals(0, $data['code_smell_minor']);
        $this->assertEquals(0, $data['code_smell_info']);

        /** Hotspots */
        $this->assertEquals(1, $data['hotspot_high']);
        $this->assertEquals(0, $data['hotspot_medium']);
        $this->assertEquals(10, $data['hotspot_low']);
        $this->assertEquals(11, $data['nombre_hotspot']);

        /** Hotspots Details */

        /** OWASP */
        $this->assertEquals(150, $collecte[11][static::$s09Owasp]['data']['effort_total']);
        $this->assertEquals(10, $collecte[11][static::$s09Owasp]['message']['nombre']);

        /** Hotspot OWASP */
        $this->assertEquals($message, $collecte[12]['10 - HOTSPOT OWASP A0']);
        $this->assertEquals('effacement', $collecte[12]['info']);
        $this->assertEquals('', $collecte[12]['owasp_2017']);
        $this->assertEquals('', $collecte[12]['owasp_2021']);
        $this->assertEquals([], $collecte[12]['data']);
        $this->assertEquals('', $collecte[13]['10 - HOTSPOT OWASP A1']);
        $this->assertEquals('enregistrement', $collecte[13]['info']);
        $this->assertEquals(10, $collecte[13]['owasp_2017']);
        $this->assertEquals(2, $collecte[13]['owasp_2021']);
        $this->assertEquals(['referentiel_owasp' => 2017, 'menace' => 'a1', 'security_category' => 'NC', 'rule_key' => 'NC', 'probability' => 'NC', 'status' => 'NC', 'resolution' => '', 'niveau' => -1], $collecte[13]['data']);
        $this->assertEquals('', $collecte[14]['10 - HOTSPOT OWASP A2']);
        $this->assertEquals('enregistrement', $collecte[14]['info']);
        $this->assertEquals(10, $collecte[14]['owasp_2017']);
        $this->assertEquals(2, $collecte[14]['owasp_2021']);
        $this->assertEquals(['referentiel_owasp' => 2017, 'menace' => 'a2', 'security_category' => 'NC', 'rule_key' => 'NC', 'probability' => 'NC', 'status' => 'NC', 'resolution' => '', 'niveau' => -1], $collecte[14]['data']);
        $this->assertEquals('', $collecte[15]['10 - HOTSPOT OWASP A3']);
        $this->assertEquals('enregistrement', $collecte[15]['info']);
        $this->assertEquals(10, $collecte[15]['owasp_2017']);
        $this->assertEquals(2, $collecte[15]['owasp_2021']);
        $this->assertEquals(['referentiel_owasp' => 2017, 'menace' => 'a3', 'security_category' => 'NC', 'rule_key' => 'NC', 'probability' => 'NC', 'status' => 'NC', 'resolution' => '', 'niveau' => -1], $collecte[15]['data']);
        $this->assertEquals('', $collecte[16]['10 - HOTSPOT OWASP A4']);
        $this->assertEquals('enregistrement', $collecte[16]['info']);
        $this->assertEquals(10, $collecte[16]['owasp_2017']);
        $this->assertEquals(2, $collecte[16]['owasp_2021']);
        $this->assertEquals(['referentiel_owasp' => 2017, 'menace' => 'a4', 'security_category' => 'NC', 'rule_key' => 'NC', 'probability' => 'NC', 'status' => 'NC', 'resolution' => '', 'niveau' => -1], $collecte[16]['data']);
        $this->assertEquals('', $collecte[17]['10 - HOTSPOT OWASP A5']);
        $this->assertEquals('enregistrement', $collecte[17]['info']);
        $this->assertEquals(10, $collecte[17]['owasp_2017']);
        $this->assertEquals(2, $collecte[17]['owasp_2021']);
        $this->assertEquals(['referentiel_owasp' => 2017, 'menace' => 'a5', 'security_category' => 'NC', 'rule_key' => 'NC', 'probability' => 'NC', 'status' => 'NC', 'resolution' => '', 'niveau' => -1], $collecte[17]['data']);
        $this->assertEquals('', $collecte[18]['10 - HOTSPOT OWASP A6']);
        $this->assertEquals('enregistrement', $collecte[18]['info']);
        $this->assertEquals(10, $collecte[18]['owasp_2017']);
        $this->assertEquals(2, $collecte[18]['owasp_2021']);
        $this->assertEquals(['referentiel_owasp' => 2017, 'menace' => 'a6', 'security_category' => 'NC', 'rule_key' => 'NC', 'probability' => 'NC', 'status' => 'NC', 'resolution' => '', 'niveau' => -1], $collecte[18]['data']);
        $this->assertEquals('', $collecte[19]['10 - HOTSPOT OWASP A7']);
        $this->assertEquals('enregistrement', $collecte[19]['info']);
        $this->assertEquals(10, $collecte[19]['owasp_2017']);
        $this->assertEquals(2, $collecte[19]['owasp_2021']);
        $this->assertEquals(['referentiel_owasp' => 2017, 'menace' => 'a7', 'security_category' => 'NC', 'rule_key' => 'NC', 'probability' => 'NC', 'status' => 'NC', 'resolution' => '', 'niveau' => -1], $collecte[19]['data']);
        $this->assertEquals('', $collecte[20]['10 - HOTSPOT OWASP A8']);
        $this->assertEquals('enregistrement', $collecte[20]['info']);
        $this->assertEquals(10, $collecte[20]['owasp_2017']);
        $this->assertEquals(2, $collecte[20]['owasp_2021']);
        $this->assertEquals(['referentiel_owasp' => 2017, 'menace' => 'a8', 'security_category' => 'NC', 'rule_key' => 'NC', 'probability' => 'NC', 'status' => 'NC', 'resolution' => '', 'niveau' => -1], $collecte[20]['data']);
        $this->assertEquals('', $collecte[21]['10 - HOTSPOT OWASP A9']);
        $this->assertEquals('enregistrement', $collecte[21]['info']);
        $this->assertEquals(10, $collecte[21]['owasp_2017']);
        $this->assertEquals(2, $collecte[21]['owasp_2021']);
        $this->assertEquals(['referentiel_owasp' => 2017, 'menace' => 'a9', 'security_category' => 'NC', 'rule_key' => 'NC', 'probability' => 'NC', 'status' => 'NC', 'resolution' => '', 'niveau' => -1], $collecte[21]['data']);

        $this->assertEquals('', $collecte[22]['10 - HOTSPOT OWASP A10']);
        $this->assertEquals('enregistrement', $collecte[22]['info']);
        $this->assertEquals(10, $collecte[22]['owasp_2017']);
        $this->assertEquals(2, $collecte[22]['owasp_2021']);
        $this->assertEquals(['referentiel_owasp' => 2017, 'menace' => 'a10', 'security_category' => 'NC', 'rule_key' => 'NC', 'probability' => 'NC', 'status' => 'NC', 'resolution' => '', 'niveau' => -1], $collecte[22]['data']);

        /** NoSonar */
        $this->assertEquals(1, $collecte[23][static::$s11NoSonar]['suppress_warning']);
        $this->assertEquals(1, $collecte[23][static::$s11NoSonar]['no_sonar']);
        $this->assertEquals(1, $data['suppress_warning']);
        $this->assertEquals(1, $data['no_sonar']);

        /** To.do */
        $this->assertEquals('java:007', $collecte[24][static::$s12Todo]['todo']['rule']);
        $this->assertEquals('/src/cool/toto.java', $collecte[24][static::$s12Todo]['todo']['component']);
        $this->assertEquals(10, $collecte[24][static::$s12Todo]['todo']['line']);
        $this->assertEquals(5, $data['todo']);

        /** Logger */
        $this->assertEquals(1, $collecte[26][static::$s14LoggerActuator]['logger_info']);
        $this->assertEquals(1, $collecte[26][static::$s14LoggerActuator]['logger_warn']);
        $this->assertEquals(1, $collecte[26][static::$s14LoggerActuator]['logger_error']);
        $this->assertEquals(1, $collecte[26][static::$s14LoggerActuator]['logger_debug']);

        $this->assertEquals(1, $data['logger_info']['total']);
        $this->assertEquals(1, $data['logger_warn']['total']);
        $this->assertEquals(1, $data['logger_error']['total']);
        $this->assertEquals(1, $data['logger_debug']['total']);

        $this->assertEquals('maven_key', $data['maven_key']);
        $this->assertEquals(false, $data['initial']);
        $this->assertEquals('mode_collecte', $data['mode_collecte']);
        $this->assertEquals('utilisateur_collecte', $data['utilisateur_collecte']);
    }

    public function testCollecteWithInformationProjetError()
    {
        $this->batchCollecteInformation->method('batchCollecteInformation')->willReturn([
            'code' => 500,
            'message' => static::$erreurCollecte
        ]);

        $result = $this->controller->collecte('portefeuille', 'maven_key', 'mode_collecte', 'utilisateur_collecte');

        $this->assertSame(500, $result['code']);
        $this->assertArrayHasKey('Collecte', $result);
        $this->assertContains('**** ERREUR : INFORMATION PROJET 500 ****', $result['Collecte'][1]);

        // Optionnel : vous pouvez également vérifier le message d'erreur complet
        $this->assertSame("Erreur lors de la collecte des informations", $result['Collecte'][1][1]);

        // Vérification complète du contenu de l'élément Collecte[1]
        $expectedErrorArray = [
        '**** ERREUR : INFORMATION PROJET 500 ****',
        static::$erreurCollecte,
        '~************* FIN DU TRAITEMENT ***************~'];
        $this->assertSame($expectedErrorArray, $result['Collecte'][1]);
    }

    public function testCollecteWithMesureError()
    {
        $this->batchCollecteInformation->method('batchCollecteInformation')->willReturn([
            'code' => 200,
            'message' => static::$informationProjet,
            'data' => []
        ]);

        $this->batchCollecteMesure->method('batchCollecteMesure')->willReturn([
            'code' => 500,
            'message' => 'Erreur lors de la collecte des mesures'
        ]);

        $result = $this->controller->collecte('portefeuille', 'maven_key', 'mode_collecte', 'utilisateur_collecte');

        $this->assertSame(500, $result['code']);
        $this->assertArrayHasKey('Collecte', $result);
        $this->assertContains('**** ERREUR : MESURE 500 ****', $result['Collecte'][2]);

        // Vérification complète du tableau imbriqué Collecte[2]
        $expectedErrorArray = [
            '**** ERREUR : MESURE 500 ****',
            'Erreur lors de la collecte des mesures'
        ];
        $this->assertSame($expectedErrorArray, $result['Collecte'][2]);
    }

    public function testCollecteWithReliabilityNoteError()
    {
        $this->batchCollecteInformation->method('batchCollecteInformation')->willReturn([
            'code' => 200,
            'message' => static::$informationProjet,
            'data' => []
        ]);
        $this->batchCollecteMesure->method('batchCollecteMesure')->willReturn([
            'code' => 200,
            'message' => 'Mesure',
            'data' => []
        ]);

        $this->batchCollecteNote->method('batchCollecteNote')
        ->withConsecutive(
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'reliability'],
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'security'],
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'sqale']
        )
        ->willReturnOnConsecutiveCalls(
            ['code' => 500, 'message' => [], 'data' => []],
            ['code' => 200, 'message' => ['value' => 'B'], 'data' => ['note_security' => 'B']],
            ['code' => 200, 'message' => ['value' => 'C'], 'data' => ['note_sqale' => 'C']]
        );

        $result = $this->controller->collecte('portefeuille', 'maven_key', 'mode_collecte', 'utilisateur_collecte');

        $this->assertSame(500, $result['code']);
        $this->assertArrayHasKey('Collecte', $result);

        $collecte = $result['Collecte'];
        $this->assertContains('**** ERREUR : NOTE RELIABILITY 500 ****', $collecte[3]);
    }

    public function testCollecteWithSecurityNoteError()
    {
        $this->batchCollecteInformation->method('batchCollecteInformation')->willReturn([
            'code' => 200,
            'message' => static::$informationProjet,
            'data' => []
        ]);
        $this->batchCollecteMesure->method('batchCollecteMesure')->willReturn([
            'code' => 200,
            'message' => 'Mesure',
            'data' => []
        ]);

        $this->batchCollecteNote->method('batchCollecteNote')->withConsecutive(
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'reliability'],
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'security'],
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'sqale']
        )->willReturnOnConsecutiveCalls(
            ['code' => 200, 'message' => ['value' => 'A'], 'data' => ['note_reliability' => 'A']],
            ['code' => 500, 'message' => [], 'data' => []],
            ['code' => 200, 'message' => ['value' => 'C'], 'data' => ['note_sqale' => 'C']]
        );

        $result = $this->controller->collecte('portefeuille', 'maven_key', 'mode_collecte', 'utilisateur_collecte');

        $this->assertSame(500, $result['code']);
        $this->assertArrayHasKey('Collecte', $result);

        $collecte = $result['Collecte'];
        $this->assertContains('**** ERREUR : NOTE SECURITY 500 ****', $collecte[4]);
    }

    public function testCollecteWithSqaleNoteError()
    {
        $this->batchCollecteInformation->method('batchCollecteInformation')->willReturn([
            'code' => 200,
            'message' => static::$informationProjet,
            'data' => []
        ]);
        $this->batchCollecteMesure->method('batchCollecteMesure')->willReturn([
            'code' => 200,
            'message' => 'Mesure',
            'data' => []
        ]);

        $this->batchCollecteNote->method('batchCollecteNote')->withConsecutive(
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'reliability'],
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'security'],
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'sqale']
        )->willReturnOnConsecutiveCalls(
            ['code' => 200, 'message' => ['value' => 'A'], 'data' => ['note_reliability' => 'A']],
            ['code' => 200, 'message' => ['value' => 'B'], 'data' => ['note_security' => 'B']],
            ['code' => 500, 'message' => [], 'data' => []]
        );

        $result = $this->controller->collecte('portefeuille', 'maven_key', 'mode_collecte', 'utilisateur_collecte');

        $this->assertSame(500, $result['code']);
        $this->assertArrayHasKey('Collecte', $result);

        $collecte = $result['Collecte'];
        $this->assertContains('**** ERREUR : NOTE SQALE 500 ****', $collecte[5]);
    }

    public function testCollecteWithAnomalieError()
    {
        $this->batchCollecteInformation->method('batchCollecteInformation')->willReturn([
            'code' => 200,
            'message' => static::$informationProjet,
            'data' => []
        ]);
        $this->batchCollecteMesure->method('batchCollecteMesure')->willReturn([
            'code' => 200,
            'message' => 'Mesure',
            'data' => []
        ]);

        $this->batchCollecteNote->method('batchCollecteNote')->withConsecutive(
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'reliability'],
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'security'],
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'sqale']
        )->willReturnOnConsecutiveCalls(
            ['code' => 200, 'message' => ['value' => 'A'], 'data' => ['note_reliability' => 'A']],
            ['code' => 200, 'message' => ['value' => 'B'], 'data' => ['note_security' => 'B']],
            ['code' => 200, 'message' => ['value' => 'A'], 'data' => ['note_security' => 'C']]
        );

        $this->batchCollecteAnomalie->method('batchCollecteAnomalie')->willReturn([
            'code' => 500,
            'message' => 'Erreur Anomalie'
        ]);

        $result = $this->controller->collecte('portefeuille', 'maven_key', 'mode_collecte', 'utilisateur_collecte');

        $this->assertSame(500, $result['code']);
        $this->assertArrayHasKey('Collecte', $result);

        $collecte = $result['Collecte'];
        $this->assertContains('**** ERREUR : ANOMALIE 500 ****', $collecte[6]);
    }

    public function testCollecteWithAnomalieDetailsError()
    {
        $this->batchCollecteInformation->method('batchCollecteInformation')->willReturn([
            'code' => 200,
            'message' => static::$informationProjet,
            'data' => []
        ]);
        $this->batchCollecteMesure->method('batchCollecteMesure')->willReturn([
            'code' => 200,
            'message' => 'Mesure',
            'data' => []
        ]);
        $this->batchCollecteNote->method('batchCollecteNote')->withConsecutive(
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'reliability'],
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'security'],
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'sqale']
        )->willReturnOnConsecutiveCalls(
            ['code' => 200, 'message' => ['value' => 'A'], 'data' => ['note_reliability' => 'A']],
            ['code' => 200, 'message' => ['value' => 'B'], 'data' => ['note_security' => 'B']],
            ['code' => 200, 'message' => ['value' => 'A'], 'data' => ['note_security' => 'C']]
        );
        $this->batchCollecteAnomalie->method('batchCollecteAnomalie')->willReturn([
            'code' => 200,
            'message' => 'Anomalie',
            'data' => []
        ]);

        $this->batchCollecteAnomalieDetail->method('batchCollecteAnomalieDetail')->willReturn([
            'code' => 500,
            'message' => 'Erreur Anomalie Detail'
        ]);

        $result = $this->controller->collecte('portefeuille', 'maven_key', 'mode_collecte', 'utilisateur_collecte');

        $this->assertSame(500, $result['code']);
        $this->assertArrayHasKey('Collecte', $result);

        $collecte = $result['Collecte'];
        $this->assertContains('**** ERREUR : ANOMALIE DETAIL 500 ****', $collecte[7]);
    }

    public function testCollecteWithHotspotError()
    {
        $this->batchCollecteInformation->method('batchCollecteInformation')->willReturn([
            'code' => 200,
            'message' => static::$informationProjet,
            'data' => []
        ]);
        $this->batchCollecteMesure->method('batchCollecteMesure')->willReturn([
            'code' => 200,
            'message' => 'Mesure',
            'data' => []
        ]);
        $this->batchCollecteNote->method('batchCollecteNote')->withConsecutive(
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'reliability'],
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'security'],
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'sqale']
        )->willReturnOnConsecutiveCalls(
            ['code' => 200, 'message' => ['value' => 'A'], 'data' => ['note_reliability' => 'A']],
            ['code' => 200, 'message' => ['value' => 'B'], 'data' => ['note_security' => 'B']],
            ['code' => 200, 'message' => ['value' => 'A'], 'data' => ['note_security' => 'C']]
        );
        $this->batchCollecteAnomalie->method('batchCollecteAnomalie')->willReturn([
            'code' => 200,
            'message' => 'Anomalie',
            'data' => []
        ]);
        $this->batchCollecteAnomalieDetail->method('batchCollecteAnomalieDetail')->willReturn([
            'code' => 200,
            'message' => static::$anomalieDetail,
            'data' => []
        ]);

        $this->batchCollecteHotspot->method('batchCollecteHotspot')->willReturn([
            'code' => 500,
            'message' => 'Erreur Hotspot'
        ]);

        $result = $this->controller->collecte('portefeuille', 'maven_key', 'mode_collecte', 'utilisateur_collecte');

        $this->assertSame(500, $result['code']);
        $this->assertArrayHasKey('Collecte', $result);

        $collecte = $result['Collecte'];
        $this->assertContains('**** ERREUR : HOTSPOT 500 ****', $collecte[8]);
    }

    public function testCollecteWithNoteHotspotError()
    {
        $this->batchCollecteInformation->method('batchCollecteInformation')->willReturn([
            'code' => 200,
            'message' => static::$informationProjet,
            'data' => []
        ]);
        $this->batchCollecteMesure->method('batchCollecteMesure')->willReturn([
            'code' => 200,
            'message' => 'Mesure',
            'data' => []
        ]);
        $this->batchCollecteNote->method('batchCollecteNote')->withConsecutive(
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'reliability'],
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'security'],
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'sqale']
        )->willReturnOnConsecutiveCalls(
            ['code' => 200, 'message' => ['value' => 'A'], 'data' => ['note_reliability' => 'A']],
            ['code' => 200, 'message' => ['value' => 'B'], 'data' => ['note_security' => 'B']],
            ['code' => 200, 'message' => ['value' => 'A'], 'data' => ['note_security' => 'C']]
        );
        $this->batchCollecteAnomalie->method('batchCollecteAnomalie')->willReturn([
            'code' => 200,
            'message' => 'Anomalie',
            'data' => []
        ]);
        $this->batchCollecteAnomalieDetail->method('batchCollecteAnomalieDetail')->willReturn([
            'code' => 200,
            'message' => static::$anomalieDetail,
            'data' => []
        ]);
        $this->batchCollecteHotspot->method('batchCollecteHotspot')->willReturn([
            'code' => 200,
            'message' => 'Hotspot',
            'data' => []
        ]);

        $this->batchCollecteNote->method('batchCollecteNoteHotspot')->willReturn([
            'code' => 500,
            'message' => 'Erreur Note Hotspot'
        ]);

        $result = $this->controller->collecte('portefeuille', 'maven_key', 'mode_collecte', 'utilisateur_collecte');
        $this->assertSame(500, $result['code']);
        $this->assertArrayHasKey('Collecte', $result);

        $collecte = $result['Collecte'];
        $this->assertContains('**** ERREUR : NOTE HOTSPOT 500 ****', $collecte[9]);
    }

    public function testCollecteWithHotspotDetailError()
    {
        $this->batchCollecteInformation->method('batchCollecteInformation')->willReturn([
            'code' => 200,
            'message' => static::$informationProjet,
            'data' => []
        ]);
        $this->batchCollecteMesure->method('batchCollecteMesure')->willReturn([
            'code' => 200,
            'message' => 'Mesure',
            'data' => []
        ]);
        $this->batchCollecteNote->method('batchCollecteNote')->withConsecutive(
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'reliability'],
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'security'],
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'sqale']
        )->willReturnOnConsecutiveCalls(
            ['code' => 200, 'message' => ['value' => 'A'], 'data' => ['note_reliability' => 'A']],
            ['code' => 200, 'message' => ['value' => 'B'], 'data' => ['note_security' => 'B']],
            ['code' => 200, 'message' => ['value' => 'A'], 'data' => ['note_security' => 'C']]
        );
        $this->batchCollecteAnomalie->method('batchCollecteAnomalie')->willReturn([
            'code' => 200,
            'message' => 'Anomalie',
            'data' => []
        ]);
        $this->batchCollecteAnomalieDetail->method('batchCollecteAnomalieDetail')->willReturn([
            'code' => 200,
            'message' => static::$anomalieDetail,
            'data' => []
        ]);
        $this->batchCollecteHotspot->method('batchCollecteHotspot')->willReturn([
            'code' => 200,
            'message' => 'Hotspot',
            'data' => []
        ]);
        $this->batchCollecteNote->method('batchCollecteNoteHotspot')->willReturn([
            'code' => 200,
            'message' => static::$noteHotspot,
            'data' => []
        ]);

        $this->batchCollecteHotspotDetail->method('batchCollecteHotspotDetail')->willReturn([
            'code' => 500,
            'message' => 'Erreur Hotspot Detail'
        ]);

        $result = $this->controller->collecte('portefeuille', 'maven_key', 'mode_collecte', 'utilisateur_collecte');

        $this->assertSame(500, $result['code']);
        $this->assertArrayHasKey('Collecte', $result);

        $collecte = $result['Collecte'];
        $this->assertContains('**** ERREUR : HOTSPOT DETAIL 500 ****', $collecte[10]);
    }

    public function testCollecteWithOwaspError()
    {
        $this->batchCollecteInformation->method('batchCollecteInformation')->willReturn([
            'code' => 200,
            'message' => static::$informationProjet,
            'data' => []
        ]);
        $this->batchCollecteMesure->method('batchCollecteMesure')->willReturn([
            'code' => 200,
            'message' => 'Mesure',
            'data' => []
        ]);
        $this->batchCollecteNote->method('batchCollecteNote')->withConsecutive(
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'reliability'],
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'security'],
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'sqale']
        )->willReturnOnConsecutiveCalls(
            ['code' => 200, 'message' => ['value' => 'A'], 'data' => ['note_reliability' => 'A']],
            ['code' => 200, 'message' => ['value' => 'B'], 'data' => ['note_security' => 'B']],
            ['code' => 200, 'message' => ['value' => 'A'], 'data' => ['note_security' => 'C']]
        );
        $this->batchCollecteAnomalie->method('batchCollecteAnomalie')->willReturn([
            'code' => 200,
            'message' => 'Anomalie',
            'data' => []
        ]);
        $this->batchCollecteAnomalieDetail->method('batchCollecteAnomalieDetail')->willReturn([
            'code' => 200,
            'message' => static::$anomalieDetail,
            'data' => []
        ]);
        $this->batchCollecteHotspot->method('batchCollecteHotspot')->willReturn([
            'code' => 200,
            'message' => 'Hotspot',
            'data' => []
        ]);
        $this->batchCollecteNote->method('batchCollecteNoteHotspot')->willReturn([
            'code' => 200,
            'message' => static::$noteHotspot,
            'data' => []
        ]);
        $this->batchCollecteHotspotDetail->method('batchCollecteHotspotDetail')->willReturn([
            'code' => 200,
            'message' => static::$hotspotDetail,
            'data' => []
        ]);

        $this->batchCollecteOwasp->method('batchCollecteOwasp')->willReturn([
            'code' => 500,
            'message' => 'Erreur Owasp'
        ]);

        $result = $this->controller->collecte('portefeuille', 'maven_key', 'mode_collecte', 'utilisateur_collecte');

        $this->assertSame(500, $result['code']);
        $this->assertArrayHasKey('Collecte', $result);

        $collecte = $result['Collecte'];
        $this->assertContains('**** ERREUR : OWASP 500 ****', $collecte[11]);
    }

    public function testCollecteWithHotspotOwaspA0Errors()
    {
        $this->batchCollecteInformation->method('batchCollecteInformation')->willReturn([
            'code' => 200,
            'message' => static::$informationProjet,
            'data' => []
        ]);
        $this->batchCollecteMesure->method('batchCollecteMesure')->willReturn([
            'code' => 200,
            'message' => 'Mesure',
            'data' => []
        ]);
        $this->batchCollecteNote->method('batchCollecteNote')->withConsecutive(
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'reliability'],
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'security'],
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'sqale']
        )->willReturnOnConsecutiveCalls(
            ['code' => 200, 'message' => ['value' => 'A'], 'data' => ['note_reliability' => 'A']],
            ['code' => 200, 'message' => ['value' => 'B'], 'data' => ['note_security' => 'B']],
            ['code' => 200, 'message' => ['value' => 'A'], 'data' => ['note_security' => 'C']]
        );
        $this->batchCollecteAnomalie->method('batchCollecteAnomalie')->willReturn([
            'code' => 200,
            'message' => 'Anomalie',
            'data' => []
        ]);
        $this->batchCollecteAnomalieDetail->method('batchCollecteAnomalieDetail')->willReturn([
            'code' => 200,
            'message' => static::$anomalieDetail,
            'data' => []
        ]);
        $this->batchCollecteHotspot->method('batchCollecteHotspot')->willReturn([
            'code' => 200,
            'message' => 'Hotspot',
            'data' => []
        ]);
        $this->batchCollecteNote->method('batchCollecteNoteHotspot')->willReturn([
            'code' => 200,
            'message' => static::$noteHotspot,
            'data' => []
        ]);
        $this->batchCollecteHotspotDetail->method('batchCollecteHotspotDetail')->willReturn([
            'code' => 200,
            'message' => static::$hotspotDetail,
            'data' => []
        ]);
        $this->batchCollecteOwasp->method('batchCollecteOwasp')->willReturn([
            'code' => 200,
            'message' => 'Owasp',
            'data' => []
        ]);

        $this->batchCollecteHotspotOwasp->method('batchCollecteHotspotOwasp')
            ->withConsecutive(
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a0'],
            )->willReturnOnConsecutiveCalls(
                ['code' => 500, 'message' => 'Erreur Hotspot Owasp a0'],
            );

        // Appel de la méthode que vous voulez tester
        $result = $this->controller->collecte('portefeuille', 'maven_key', 'mode_collecte', 'utilisateur_collecte');

        // Vérifier que le résultat est bien un code 500
        $this->assertSame(500, $result['code']);
        $this->assertArrayHasKey('Collecte', $result);

        $collecte = $result['Collecte'];
        $this->assertContains('**** ERREUR : HOTSPOT OWASP a0 --> 500 ****', $collecte[12]);
        $this->assertSame('Erreur Hotspot Owasp a0', $collecte[12][1]);
    }

    public function testCollecteWithHotspotOwaspA1Errors()
    {
        $this->batchCollecteInformation->method('batchCollecteInformation')->willReturn([
            'code' => 200,
            'message' => static::$informationProjet,
            'data' => []
        ]);
        $this->batchCollecteMesure->method('batchCollecteMesure')->willReturn([
            'code' => 200,
            'message' => 'Mesure',
            'data' => []
        ]);
        $this->batchCollecteNote->method('batchCollecteNote')->withConsecutive(
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'reliability'],
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'security'],
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'sqale']
        )->willReturnOnConsecutiveCalls(
            ['code' => 200, 'message' => ['value' => 'A'], 'data' => ['note_reliability' => 'A']],
            ['code' => 200, 'message' => ['value' => 'B'], 'data' => ['note_security' => 'B']],
            ['code' => 200, 'message' => ['value' => 'A'], 'data' => ['note_security' => 'C']]
        );
        $this->batchCollecteAnomalie->method('batchCollecteAnomalie')->willReturn([
            'code' => 200,
            'message' => 'Anomalie',
            'data' => []
        ]);
        $this->batchCollecteAnomalieDetail->method('batchCollecteAnomalieDetail')->willReturn([
            'code' => 200,
            'message' => static::$anomalieDetail,
            'data' => []
        ]);
        $this->batchCollecteHotspot->method('batchCollecteHotspot')->willReturn([
            'code' => 200,
            'message' => 'Hotspot',
            'data' => []
        ]);
        $this->batchCollecteNote->method('batchCollecteNoteHotspot')->willReturn([
            'code' => 200,
            'message' => static::$noteHotspot,
            'data' => []
        ]);
        $this->batchCollecteHotspotDetail->method('batchCollecteHotspotDetail')->willReturn([
            'code' => 200,
            'message' => static::$hotspotDetail,
            'data' => []
        ]);
        $this->batchCollecteOwasp->method('batchCollecteOwasp')->willReturn([
            'code' => 200,
            'message' => 'Owasp',
            'data' => []
        ]);

        $this->batchCollecteHotspotOwasp->method('batchCollecteHotspotOwasp')
            ->withConsecutive(
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a0'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a1'],
            )->willReturnOnConsecutiveCalls(
                ['code' => 200, 'message' => static::$owaspA0, 'data' => [], 'info'=>'effacement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 500, 'message' => 'Erreur Hotspot Owasp a1'],
            );

        // Appel de la méthode que vous voulez tester
        $result = $this->controller->collecte('portefeuille', 'maven_key', 'mode_collecte', 'utilisateur_collecte');

        // Vérifier que le résultat est bien un code 500
        $this->assertSame(500, $result['code']);
        $this->assertArrayHasKey('Collecte', $result);

        $collecte = $result['Collecte'];
        $this->assertContains('**** ERREUR : HOTSPOT OWASP a1 --> 500 ****', $collecte[13]);
        $this->assertSame('Erreur Hotspot Owasp a1', $collecte[13][1]);
    }

    public function testCollecteWithHotspotOwaspA2Errors()
    {
        $this->batchCollecteInformation->method('batchCollecteInformation')->willReturn([
            'code' => 200,
            'message' => static::$informationProjet,
            'data' => []
        ]);
        $this->batchCollecteMesure->method('batchCollecteMesure')->willReturn([
            'code' => 200,
            'message' => 'Mesure',
            'data' => []
        ]);
        $this->batchCollecteNote->method('batchCollecteNote')->withConsecutive(
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'reliability'],
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'security'],
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'sqale']
        )->willReturnOnConsecutiveCalls(
            ['code' => 200, 'message' => ['value' => 'A'], 'data' => ['note_reliability' => 'A']],
            ['code' => 200, 'message' => ['value' => 'B'], 'data' => ['note_security' => 'B']],
            ['code' => 200, 'message' => ['value' => 'A'], 'data' => ['note_security' => 'C']]
        );
        $this->batchCollecteAnomalie->method('batchCollecteAnomalie')->willReturn([
            'code' => 200,
            'message' => 'Anomalie',
            'data' => []
        ]);
        $this->batchCollecteAnomalieDetail->method('batchCollecteAnomalieDetail')->willReturn([
            'code' => 200,
            'message' => static::$anomalieDetail,
            'data' => []
        ]);
        $this->batchCollecteHotspot->method('batchCollecteHotspot')->willReturn([
            'code' => 200,
            'message' => 'Hotspot',
            'data' => []
        ]);
        $this->batchCollecteNote->method('batchCollecteNoteHotspot')->willReturn([
            'code' => 200,
            'message' => static::$noteHotspot,
            'data' => []
        ]);
        $this->batchCollecteHotspotDetail->method('batchCollecteHotspotDetail')->willReturn([
            'code' => 200,
            'message' => static::$hotspotDetail,
            'data' => []
        ]);
        $this->batchCollecteOwasp->method('batchCollecteOwasp')->willReturn([
            'code' => 200,
            'message' => 'Owasp',
            'data' => []
        ]);

        $this->batchCollecteHotspotOwasp->method('batchCollecteHotspotOwasp')
            ->withConsecutive(
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a0'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a1'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a2'],
                )->willReturnOnConsecutiveCalls(
                ['code' => 200, 'message' => static::$owaspA0, 'data' => [], 'info'=>'effacement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 200, 'message' => static::$owaspA1, 'data' => [], 'info'=>'enregistrement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 500, 'message' => 'Erreur Hotspot Owasp a2'],
            );

        // Appel de la méthode que vous voulez tester
        $result = $this->controller->collecte('portefeuille', 'maven_key', 'mode_collecte', 'utilisateur_collecte');

        // Vérifier que le résultat est bien un code 500
        $this->assertSame(500, $result['code']);
        $this->assertArrayHasKey('Collecte', $result);

        $collecte = $result['Collecte'];
        $this->assertContains('**** ERREUR : HOTSPOT OWASP a2 --> 500 ****', $collecte[14]);
        $this->assertSame('Erreur Hotspot Owasp a2', $collecte[14][1]);
    }

    public function testCollecteWithHotspotOwaspA3Errors()
    {
        $this->batchCollecteInformation->method('batchCollecteInformation')->willReturn([
            'code' => 200,
            'message' => static::$informationProjet,
            'data' => []
        ]);
        $this->batchCollecteMesure->method('batchCollecteMesure')->willReturn([
            'code' => 200,
            'message' => 'Mesure',
            'data' => []
        ]);
        $this->batchCollecteNote->method('batchCollecteNote')->withConsecutive(
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'reliability'],
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'security'],
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'sqale']
        )->willReturnOnConsecutiveCalls(
            ['code' => 200, 'message' => ['value' => 'A'], 'data' => ['note_reliability' => 'A']],
            ['code' => 200, 'message' => ['value' => 'B'], 'data' => ['note_security' => 'B']],
            ['code' => 200, 'message' => ['value' => 'A'], 'data' => ['note_security' => 'C']]
        );
        $this->batchCollecteAnomalie->method('batchCollecteAnomalie')->willReturn([
            'code' => 200,
            'message' => 'Anomalie',
            'data' => []
        ]);
        $this->batchCollecteAnomalieDetail->method('batchCollecteAnomalieDetail')->willReturn([
            'code' => 200,
            'message' => static::$anomalieDetail,
            'data' => []
        ]);
        $this->batchCollecteHotspot->method('batchCollecteHotspot')->willReturn([
            'code' => 200,
            'message' => 'Hotspot',
            'data' => []
        ]);
        $this->batchCollecteNote->method('batchCollecteNoteHotspot')->willReturn([
            'code' => 200,
            'message' => static::$noteHotspot,
            'data' => []
        ]);
        $this->batchCollecteHotspotDetail->method('batchCollecteHotspotDetail')->willReturn([
            'code' => 200,
            'message' => static::$hotspotDetail,
            'data' => []
        ]);
        $this->batchCollecteOwasp->method('batchCollecteOwasp')->willReturn([
            'code' => 200,
            'message' => 'Owasp',
            'data' => []
        ]);

        $this->batchCollecteHotspotOwasp->method('batchCollecteHotspotOwasp')
            ->withConsecutive(
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a0'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a1'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a2'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a3'],
                )->willReturnOnConsecutiveCalls(
                ['code' => 200, 'message' => static::$owaspA0, 'data' => [], 'info'=>'effacement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 200, 'message' => static::$owaspA1, 'data' => [], 'info'=>'enregistrement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 200, 'message' => static::$owaspA2, 'data' => [], 'info'=>'enregistrement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 500, 'message' => 'Erreur Hotspot Owasp a3'],
            );

        // Appel de la méthode que vous voulez tester
        $result = $this->controller->collecte('portefeuille', 'maven_key', 'mode_collecte', 'utilisateur_collecte');

        // Vérifier que le résultat est bien un code 500
        $this->assertSame(500, $result['code']);
        $this->assertArrayHasKey('Collecte', $result);

        $collecte = $result['Collecte'];
        $this->assertContains('**** ERREUR : HOTSPOT OWASP a3 --> 500 ****', $collecte[15]);
        $this->assertSame('Erreur Hotspot Owasp a3', $collecte[15][1]);
    }

    public function testCollecteWithHotspotOwaspA4Errors()
    {
        $this->batchCollecteInformation->method('batchCollecteInformation')->willReturn([
            'code' => 200,
            'message' => static::$informationProjet,
            'data' => []
        ]);
        $this->batchCollecteMesure->method('batchCollecteMesure')->willReturn([
            'code' => 200,
            'message' => 'Mesure',
            'data' => []
        ]);
        $this->batchCollecteNote->method('batchCollecteNote')->withConsecutive(
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'reliability'],
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'security'],
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'sqale']
        )->willReturnOnConsecutiveCalls(
            ['code' => 200, 'message' => ['value' => 'A'], 'data' => ['note_reliability' => 'A']],
            ['code' => 200, 'message' => ['value' => 'B'], 'data' => ['note_security' => 'B']],
            ['code' => 200, 'message' => ['value' => 'A'], 'data' => ['note_security' => 'C']]
        );
        $this->batchCollecteAnomalie->method('batchCollecteAnomalie')->willReturn([
            'code' => 200,
            'message' => 'Anomalie',
            'data' => []
        ]);
        $this->batchCollecteAnomalieDetail->method('batchCollecteAnomalieDetail')->willReturn([
            'code' => 200,
            'message' => static::$anomalieDetail,
            'data' => []
        ]);
        $this->batchCollecteHotspot->method('batchCollecteHotspot')->willReturn([
            'code' => 200,
            'message' => 'Hotspot',
            'data' => []
        ]);
        $this->batchCollecteNote->method('batchCollecteNoteHotspot')->willReturn([
            'code' => 200,
            'message' => static::$noteHotspot,
            'data' => []
        ]);
        $this->batchCollecteHotspotDetail->method('batchCollecteHotspotDetail')->willReturn([
            'code' => 200,
            'message' => static::$hotspotDetail,
            'data' => []
        ]);
        $this->batchCollecteOwasp->method('batchCollecteOwasp')->willReturn([
            'code' => 200,
            'message' => 'Owasp',
            'data' => []
        ]);

        $this->batchCollecteHotspotOwasp->method('batchCollecteHotspotOwasp')
            ->withConsecutive(
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a0'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a1'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a2'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a3'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a4'],
                )->willReturnOnConsecutiveCalls(
                ['code' => 200, 'message' => static::$owaspA0, 'data' => [], 'info'=>'effacement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 200, 'message' => static::$owaspA1, 'data' => [], 'info'=>'enregistrement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 200, 'message' => static::$owaspA2, 'data' => [], 'info'=>'enregistrement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 200, 'message' => static::$owaspA3, 'data' => [], 'info'=>'enregistrement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 500, 'message' => 'Erreur Hotspot Owasp a4'],
            );

        // Appel de la méthode que vous voulez tester
        $result = $this->controller->collecte('portefeuille', 'maven_key', 'mode_collecte', 'utilisateur_collecte');

        // Vérifier que le résultat est bien un code 500
        $this->assertSame(500, $result['code']);
        $this->assertArrayHasKey('Collecte', $result);

        $collecte = $result['Collecte'];
        $this->assertContains('**** ERREUR : HOTSPOT OWASP a4 --> 500 ****', $collecte[16]);
        $this->assertSame('Erreur Hotspot Owasp a4', $collecte[16][1]);
    }

    public function testCollecteWithHotspotOwaspA5Errors()
    {
        $this->batchCollecteInformation->method('batchCollecteInformation')->willReturn([
            'code' => 200,
            'message' => static::$informationProjet,
            'data' => []
        ]);
        $this->batchCollecteMesure->method('batchCollecteMesure')->willReturn([
            'code' => 200,
            'message' => 'Mesure',
            'data' => []
        ]);
        $this->batchCollecteNote->method('batchCollecteNote')->withConsecutive(
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'reliability'],
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'security'],
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'sqale']
        )->willReturnOnConsecutiveCalls(
            ['code' => 200, 'message' => ['value' => 'A'], 'data' => ['note_reliability' => 'A']],
            ['code' => 200, 'message' => ['value' => 'B'], 'data' => ['note_security' => 'B']],
            ['code' => 200, 'message' => ['value' => 'A'], 'data' => ['note_security' => 'C']]
        );
        $this->batchCollecteAnomalie->method('batchCollecteAnomalie')->willReturn([
            'code' => 200,
            'message' => 'Anomalie',
            'data' => []
        ]);
        $this->batchCollecteAnomalieDetail->method('batchCollecteAnomalieDetail')->willReturn([
            'code' => 200,
            'message' => static::$anomalieDetail,
            'data' => []
        ]);
        $this->batchCollecteHotspot->method('batchCollecteHotspot')->willReturn([
            'code' => 200,
            'message' => 'Hotspot',
            'data' => []
        ]);
        $this->batchCollecteNote->method('batchCollecteNoteHotspot')->willReturn([
            'code' => 200,
            'message' => static::$noteHotspot,
            'data' => []
        ]);
        $this->batchCollecteHotspotDetail->method('batchCollecteHotspotDetail')->willReturn([
            'code' => 200,
            'message' => static::$hotspotDetail,
            'data' => []
        ]);
        $this->batchCollecteOwasp->method('batchCollecteOwasp')->willReturn([
            'code' => 200,
            'message' => 'Owasp',
            'data' => []
        ]);

        $this->batchCollecteHotspotOwasp->method('batchCollecteHotspotOwasp')
            ->withConsecutive(
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a0'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a1'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a2'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a3'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a4'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a5'],
                )->willReturnOnConsecutiveCalls(
                ['code' => 200, 'message' => static::$owaspA0, 'data' => [], 'info'=>'effacement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 200, 'message' => static::$owaspA1, 'data' => [], 'info'=>'enregistrement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 200, 'message' => static::$owaspA2, 'data' => [], 'info'=>'enregistrement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 200, 'message' => static::$owaspA3, 'data' => [], 'info'=>'enregistrement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 200, 'message' => static::$owaspA4, 'data' => [], 'info'=>'enregistrement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 500, 'message' => 'Erreur Hotspot Owasp a5'],
            );

        // Appel de la méthode que vous voulez tester
        $result = $this->controller->collecte('portefeuille', 'maven_key', 'mode_collecte', 'utilisateur_collecte');

        // Vérifier que le résultat est bien un code 500
        $this->assertSame(500, $result['code']);
        $this->assertArrayHasKey('Collecte', $result);

        $collecte = $result['Collecte'];
        $this->assertContains('**** ERREUR : HOTSPOT OWASP a5 --> 500 ****', $collecte[17]);
        $this->assertSame('Erreur Hotspot Owasp a5', $collecte[17][1]);
    }

    public function testCollecteWithHotspotOwaspA6Errors()
    {
        $this->batchCollecteInformation->method('batchCollecteInformation')->willReturn([
            'code' => 200,
            'message' => static::$informationProjet,
            'data' => []
        ]);
        $this->batchCollecteMesure->method('batchCollecteMesure')->willReturn([
            'code' => 200,
            'message' => 'Mesure',
            'data' => []
        ]);
        $this->batchCollecteNote->method('batchCollecteNote')->withConsecutive(
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'reliability'],
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'security'],
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'sqale']
        )->willReturnOnConsecutiveCalls(
            ['code' => 200, 'message' => ['value' => 'A'], 'data' => ['note_reliability' => 'A']],
            ['code' => 200, 'message' => ['value' => 'B'], 'data' => ['note_security' => 'B']],
            ['code' => 200, 'message' => ['value' => 'A'], 'data' => ['note_security' => 'C']]
        );
        $this->batchCollecteAnomalie->method('batchCollecteAnomalie')->willReturn([
            'code' => 200,
            'message' => 'Anomalie',
            'data' => []
        ]);
        $this->batchCollecteAnomalieDetail->method('batchCollecteAnomalieDetail')->willReturn([
            'code' => 200,
            'message' => static::$anomalieDetail,
            'data' => []
        ]);
        $this->batchCollecteHotspot->method('batchCollecteHotspot')->willReturn([
            'code' => 200,
            'message' => 'Hotspot',
            'data' => []
        ]);
        $this->batchCollecteNote->method('batchCollecteNoteHotspot')->willReturn([
            'code' => 200,
            'message' => static::$noteHotspot,
            'data' => []
        ]);
        $this->batchCollecteHotspotDetail->method('batchCollecteHotspotDetail')->willReturn([
            'code' => 200,
            'message' => static::$hotspotDetail,
            'data' => []
        ]);
        $this->batchCollecteOwasp->method('batchCollecteOwasp')->willReturn([
            'code' => 200,
            'message' => 'Owasp',
            'data' => []
        ]);

        $this->batchCollecteHotspotOwasp->method('batchCollecteHotspotOwasp')
            ->withConsecutive(
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a0'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a1'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a2'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a3'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a4'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a5'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a6'],
                )->willReturnOnConsecutiveCalls(
                ['code' => 200, 'message' => static::$owaspA0, 'data' => [], 'info'=>'effacement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 200, 'message' => static::$owaspA1, 'data' => [], 'info'=>'enregistrement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 200, 'message' => static::$owaspA2, 'data' => [], 'info'=>'enregistrement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 200, 'message' => static::$owaspA3, 'data' => [], 'info'=>'enregistrement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 200, 'message' => static::$owaspA4, 'data' => [], 'info'=>'enregistrement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 200, 'message' => static::$owaspA5, 'data' => [], 'info'=>'enregistrement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 500, 'message' => 'Erreur Hotspot Owasp a6'],
            );

        // Appel de la méthode que vous voulez tester
        $result = $this->controller->collecte('portefeuille', 'maven_key', 'mode_collecte', 'utilisateur_collecte');

        // Vérifier que le résultat est bien un code 500
        $this->assertSame(500, $result['code']);
        $this->assertArrayHasKey('Collecte', $result);

        $collecte = $result['Collecte'];
        $this->assertContains('**** ERREUR : HOTSPOT OWASP a6 --> 500 ****', $collecte[18]);
        $this->assertSame('Erreur Hotspot Owasp a6', $collecte[18][1]);
    }

    public function testCollecteWithHotspotOwaspA7Errors()
    {
        $this->batchCollecteInformation->method('batchCollecteInformation')->willReturn([
            'code' => 200,
            'message' => static::$informationProjet,
            'data' => []
        ]);
        $this->batchCollecteMesure->method('batchCollecteMesure')->willReturn([
            'code' => 200,
            'message' => 'Mesure',
            'data' => []
        ]);
        $this->batchCollecteNote->method('batchCollecteNote')->withConsecutive(
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'reliability'],
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'security'],
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'sqale']
        )->willReturnOnConsecutiveCalls(
            ['code' => 200, 'message' => ['value' => 'A'], 'data' => ['note_reliability' => 'A']],
            ['code' => 200, 'message' => ['value' => 'B'], 'data' => ['note_security' => 'B']],
            ['code' => 200, 'message' => ['value' => 'A'], 'data' => ['note_security' => 'C']]
        );
        $this->batchCollecteAnomalie->method('batchCollecteAnomalie')->willReturn([
            'code' => 200,
            'message' => 'Anomalie',
            'data' => []
        ]);
        $this->batchCollecteAnomalieDetail->method('batchCollecteAnomalieDetail')->willReturn([
            'code' => 200,
            'message' => static::$anomalieDetail,
            'data' => []
        ]);
        $this->batchCollecteHotspot->method('batchCollecteHotspot')->willReturn([
            'code' => 200,
            'message' => 'Hotspot',
            'data' => []
        ]);
        $this->batchCollecteNote->method('batchCollecteNoteHotspot')->willReturn([
            'code' => 200,
            'message' => static::$noteHotspot,
            'data' => []
        ]);
        $this->batchCollecteHotspotDetail->method('batchCollecteHotspotDetail')->willReturn([
            'code' => 200,
            'message' => static::$hotspotDetail,
            'data' => []
        ]);
        $this->batchCollecteOwasp->method('batchCollecteOwasp')->willReturn([
            'code' => 200,
            'message' => 'Owasp',
            'data' => []
        ]);

        $this->batchCollecteHotspotOwasp->method('batchCollecteHotspotOwasp')
            ->withConsecutive(
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a0'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a1'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a2'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a3'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a4'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a5'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a6'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a7'],
                )->willReturnOnConsecutiveCalls(
                ['code' => 200, 'message' => static::$owaspA0, 'data' => [], 'info'=>'effacement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 200, 'message' => static::$owaspA1, 'data' => [], 'info'=>'enregistrement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 200, 'message' => static::$owaspA2, 'data' => [], 'info'=>'enregistrement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 200, 'message' => static::$owaspA3, 'data' => [], 'info'=>'enregistrement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 200, 'message' => static::$owaspA4, 'data' => [], 'info'=>'enregistrement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 200, 'message' => static::$owaspA5, 'data' => [], 'info'=>'enregistrement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 200, 'message' => static::$owaspA6, 'data' => [], 'info'=>'enregistrement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 500, 'message' => 'Erreur Hotspot Owasp a7'],
            );

        // Appel de la méthode que vous voulez tester
        $result = $this->controller->collecte('portefeuille', 'maven_key', 'mode_collecte', 'utilisateur_collecte');

        // Vérifier que le résultat est bien un code 500
        $this->assertSame(500, $result['code']);
        $this->assertArrayHasKey('Collecte', $result);

        $collecte = $result['Collecte'];
        $this->assertContains('**** ERREUR : HOTSPOT OWASP a7 --> 500 ****', $collecte[19]);
        $this->assertSame('Erreur Hotspot Owasp a7', $collecte[19][1]);
    }

    public function testCollecteWithHotspotOwaspA8Errors()
    {
        $this->batchCollecteInformation->method('batchCollecteInformation')->willReturn([
            'code' => 200,
            'message' => static::$informationProjet,
            'data' => []
        ]);
        $this->batchCollecteMesure->method('batchCollecteMesure')->willReturn([
            'code' => 200,
            'message' => 'Mesure',
            'data' => []
        ]);
        $this->batchCollecteNote->method('batchCollecteNote')->withConsecutive(
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'reliability'],
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'security'],
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'sqale']
        )->willReturnOnConsecutiveCalls(
            ['code' => 200, 'message' => ['value' => 'A'], 'data' => ['note_reliability' => 'A']],
            ['code' => 200, 'message' => ['value' => 'B'], 'data' => ['note_security' => 'B']],
            ['code' => 200, 'message' => ['value' => 'A'], 'data' => ['note_security' => 'C']]
        );
        $this->batchCollecteAnomalie->method('batchCollecteAnomalie')->willReturn([
            'code' => 200,
            'message' => 'Anomalie',
            'data' => []
        ]);
        $this->batchCollecteAnomalieDetail->method('batchCollecteAnomalieDetail')->willReturn([
            'code' => 200,
            'message' => static::$anomalieDetail,
            'data' => []
        ]);
        $this->batchCollecteHotspot->method('batchCollecteHotspot')->willReturn([
            'code' => 200,
            'message' => 'Hotspot',
            'data' => []
        ]);
        $this->batchCollecteNote->method('batchCollecteNoteHotspot')->willReturn([
            'code' => 200,
            'message' => static::$noteHotspot,
            'data' => []
        ]);
        $this->batchCollecteHotspotDetail->method('batchCollecteHotspotDetail')->willReturn([
            'code' => 200,
            'message' => static::$hotspotDetail,
            'data' => []
        ]);
        $this->batchCollecteOwasp->method('batchCollecteOwasp')->willReturn([
            'code' => 200,
            'message' => 'Owasp',
            'data' => []
        ]);

        $this->batchCollecteHotspotOwasp->method('batchCollecteHotspotOwasp')
            ->withConsecutive(
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a0'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a1'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a2'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a3'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a4'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a5'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a6'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a7'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a8'],
                )->willReturnOnConsecutiveCalls(
                ['code' => 200, 'message' => static::$owaspA0, 'data' => [], 'info'=>'effacement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 200, 'message' => static::$owaspA1, 'data' => [], 'info'=>'enregistrement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 200, 'message' => static::$owaspA2, 'data' => [], 'info'=>'enregistrement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 200, 'message' => static::$owaspA3, 'data' => [], 'info'=>'enregistrement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 200, 'message' => static::$owaspA4, 'data' => [], 'info'=>'enregistrement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 200, 'message' => static::$owaspA5, 'data' => [], 'info'=>'enregistrement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 200, 'message' => static::$owaspA6, 'data' => [], 'info'=>'enregistrement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 200, 'message' => static::$owaspA7, 'data' => [], 'info'=>'enregistrement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 500, 'message' => 'Erreur Hotspot Owasp a8'],
            );

        // Appel de la méthode que vous voulez tester
        $result = $this->controller->collecte('portefeuille', 'maven_key', 'mode_collecte', 'utilisateur_collecte');

        // Vérifier que le résultat est bien un code 500
        $this->assertSame(500, $result['code']);
        $this->assertArrayHasKey('Collecte', $result);

        $collecte = $result['Collecte'];
        $this->assertContains('**** ERREUR : HOTSPOT OWASP a8 --> 500 ****', $collecte[20]);
        $this->assertSame('Erreur Hotspot Owasp a8', $collecte[20][1]);
    }

    public function testCollecteWithHotspotOwaspA9Errors()
    {
        $this->batchCollecteInformation->method('batchCollecteInformation')->willReturn([
            'code' => 200,
            'message' => static::$informationProjet,
            'data' => []
        ]);
        $this->batchCollecteMesure->method('batchCollecteMesure')->willReturn([
            'code' => 200,
            'message' => 'Mesure',
            'data' => []
        ]);
        $this->batchCollecteNote->method('batchCollecteNote')->withConsecutive(
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'reliability'],
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'security'],
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'sqale']
        )->willReturnOnConsecutiveCalls(
            ['code' => 200, 'message' => ['value' => 'A'], 'data' => ['note_reliability' => 'A']],
            ['code' => 200, 'message' => ['value' => 'B'], 'data' => ['note_security' => 'B']],
            ['code' => 200, 'message' => ['value' => 'A'], 'data' => ['note_security' => 'C']]
        );
        $this->batchCollecteAnomalie->method('batchCollecteAnomalie')->willReturn([
            'code' => 200,
            'message' => 'Anomalie',
            'data' => []
        ]);
        $this->batchCollecteAnomalieDetail->method('batchCollecteAnomalieDetail')->willReturn([
            'code' => 200,
            'message' => static::$anomalieDetail,
            'data' => []
        ]);
        $this->batchCollecteHotspot->method('batchCollecteHotspot')->willReturn([
            'code' => 200,
            'message' => 'Hotspot',
            'data' => []
        ]);
        $this->batchCollecteNote->method('batchCollecteNoteHotspot')->willReturn([
            'code' => 200,
            'message' => static::$noteHotspot,
            'data' => []
        ]);
        $this->batchCollecteHotspotDetail->method('batchCollecteHotspotDetail')->willReturn([
            'code' => 200,
            'message' => static::$hotspotDetail,
            'data' => []
        ]);
        $this->batchCollecteOwasp->method('batchCollecteOwasp')->willReturn([
            'code' => 200,
            'message' => 'Owasp',
            'data' => []
        ]);

        $this->batchCollecteHotspotOwasp->method('batchCollecteHotspotOwasp')
            ->withConsecutive(
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a0'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a1'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a2'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a3'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a4'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a5'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a6'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a7'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a8'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a9'],
                )->willReturnOnConsecutiveCalls(
                ['code' => 200, 'message' => static::$owaspA0, 'data' => [], 'info'=>'effacement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 200, 'message' => static::$owaspA1, 'data' => [], 'info'=>'enregistrement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 200, 'message' => static::$owaspA2, 'data' => [], 'info'=>'enregistrement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 200, 'message' => static::$owaspA3, 'data' => [], 'info'=>'enregistrement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 200, 'message' => static::$owaspA4, 'data' => [], 'info'=>'enregistrement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 200, 'message' => static::$owaspA5, 'data' => [], 'info'=>'enregistrement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 200, 'message' => static::$owaspA6, 'data' => [], 'info'=>'enregistrement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 200, 'message' => static::$owaspA7, 'data' => [], 'info'=>'enregistrement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 200, 'message' => static::$owaspA8, 'data' => [], 'info'=>'enregistrement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 500, 'message' => 'Erreur Hotspot Owasp a9'],
            );

        // Appel de la méthode que vous voulez tester
        $result = $this->controller->collecte('portefeuille', 'maven_key', 'mode_collecte', 'utilisateur_collecte');

        // Vérifier que le résultat est bien un code 500
        $this->assertSame(500, $result['code']);
        $this->assertArrayHasKey('Collecte', $result);

        $collecte = $result['Collecte'];
        $this->assertContains('**** ERREUR : HOTSPOT OWASP a9 --> 500 ****', $collecte[21]);
        $this->assertSame('Erreur Hotspot Owasp a9', $collecte[21][1]);
    }

    public function testCollecteWithHotspotOwaspA10Errors()
    {
        $this->batchCollecteInformation->method('batchCollecteInformation')->willReturn([
            'code' => 200,
            'message' => static::$informationProjet,
            'data' => []
        ]);
        $this->batchCollecteMesure->method('batchCollecteMesure')->willReturn([
            'code' => 200,
            'message' => 'Mesure',
            'data' => []
        ]);
        $this->batchCollecteNote->method('batchCollecteNote')->withConsecutive(
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'reliability'],
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'security'],
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'sqale']
        )->willReturnOnConsecutiveCalls(
            ['code' => 200, 'message' => ['value' => 'A'], 'data' => ['note_reliability' => 'A']],
            ['code' => 200, 'message' => ['value' => 'B'], 'data' => ['note_security' => 'B']],
            ['code' => 200, 'message' => ['value' => 'A'], 'data' => ['note_security' => 'C']]
        );
        $this->batchCollecteAnomalie->method('batchCollecteAnomalie')->willReturn([
            'code' => 200,
            'message' => 'Anomalie',
            'data' => []
        ]);
        $this->batchCollecteAnomalieDetail->method('batchCollecteAnomalieDetail')->willReturn([
            'code' => 200,
            'message' => static::$anomalieDetail,
            'data' => []
        ]);
        $this->batchCollecteHotspot->method('batchCollecteHotspot')->willReturn([
            'code' => 200,
            'message' => 'Hotspot',
            'data' => []
        ]);
        $this->batchCollecteNote->method('batchCollecteNoteHotspot')->willReturn([
            'code' => 200,
            'message' => static::$noteHotspot,
            'data' => []
        ]);
        $this->batchCollecteHotspotDetail->method('batchCollecteHotspotDetail')->willReturn([
            'code' => 200,
            'message' => static::$hotspotDetail,
            'data' => []
        ]);
        $this->batchCollecteOwasp->method('batchCollecteOwasp')->willReturn([
            'code' => 200,
            'message' => 'Owasp',
            'data' => []
        ]);

        $this->batchCollecteHotspotOwasp->method('batchCollecteHotspotOwasp')
            ->withConsecutive(
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a0'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a1'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a2'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a3'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a4'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a5'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a6'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a7'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a8'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a9'],
                ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'a10'],
                )->willReturnOnConsecutiveCalls(
                ['code' => 200, 'message' => static::$owaspA0, 'data' => [], 'info'=>'effacement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 200, 'message' => static::$owaspA1, 'data' => [], 'info'=>'enregistrement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 200, 'message' => static::$owaspA2, 'data' => [], 'info'=>'enregistrement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 200, 'message' => static::$owaspA3, 'data' => [], 'info'=>'enregistrement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 200, 'message' => static::$owaspA4, 'data' => [], 'info'=>'enregistrement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 200, 'message' => static::$owaspA5, 'data' => [], 'info'=>'enregistrement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 200, 'message' => static::$owaspA6, 'data' => [], 'info'=>'enregistrement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 200, 'message' => static::$owaspA7, 'data' => [], 'info'=>'enregistrement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 200, 'message' => static::$owaspA8, 'data' => [], 'info'=>'enregistrement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 200, 'message' => static::$owaspA9, 'data' => [], 'info'=>'enregistrement', 'owasp_2017'=>'', 'owasp_2021' => ''],
                ['code' => 500, 'message' => 'Erreur Hotspot Owasp a10'],
            );

        // Appel de la méthode que vous voulez tester
        $result = $this->controller->collecte('portefeuille', 'maven_key', 'mode_collecte', 'utilisateur_collecte');

        // Vérifier que le résultat est bien un code 500
        $this->assertSame(500, $result['code']);
        $this->assertArrayHasKey('Collecte', $result);

        $collecte = $result['Collecte'];
        $this->assertContains('**** ERREUR : HOTSPOT OWASP a10 --> 500 ****', $collecte[22]);
        $this->assertSame('Erreur Hotspot Owasp a10', $collecte[22][1]);
    }

    public function testCollecteWithNoSonarError()
    {
        $this->batchCollecteInformation->method('batchCollecteInformation')->willReturn([
            'code' => 200,
            'message' => static::$informationProjet,
            'data' => []
        ]);
        $this->batchCollecteMesure->method('batchCollecteMesure')->willReturn([
            'code' => 200,
            'message' => 'Mesure',
            'data' => []
        ]);
        $this->batchCollecteNote->method('batchCollecteNote')->withConsecutive(
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'reliability'],
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'security'],
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'sqale']
        )->willReturnOnConsecutiveCalls(
            ['code' => 200, 'message' => ['value' => 'A'], 'data' => ['note_reliability' => 'A']],
            ['code' => 200, 'message' => ['value' => 'B'], 'data' => ['note_security' => 'B']],
            ['code' => 200, 'message' => ['value' => 'A'], 'data' => ['note_security' => 'C']]
        );
        $this->batchCollecteAnomalie->method('batchCollecteAnomalie')->willReturn([
            'code' => 200,
            'message' => 'Anomalie',
            'data' => []
        ]);
        $this->batchCollecteAnomalieDetail->method('batchCollecteAnomalieDetail')->willReturn([
            'code' => 200,
            'message' => static::$anomalieDetail,
            'data' => []
        ]);
        $this->batchCollecteHotspot->method('batchCollecteHotspot')->willReturn([
            'code' => 200,
            'message' => 'Hotspot',
            'data' => []
        ]);
        $this->batchCollecteNote->method('batchCollecteNoteHotspot')->willReturn([
            'code' => 200,
            'message' => static::$noteHotspot,
            'data' => []
        ]);
        $this->batchCollecteHotspotDetail->method('batchCollecteHotspotDetail')->willReturn([
            'code' => 200,
            'message' => static::$hotspotDetail,
            'data' => []
        ]);
        $this->batchCollecteOwasp->method('batchCollecteOwasp')->willReturn([
            'code' => 200,
            'message' => 'Owasp',
            'data' => []
        ]);

        $this->batchCollecteHotspotOwasp->method('batchCollecteHotspotOwasp')->willReturn(['code' => 200, 'message' => static::$owaspA0A10, 'data' => [], 'info'=>'enregistrement', 'owasp_2017'=>'', 'owasp_2021' => '']);

        $this->batchCollecteNoSonar->method('batchCollecteNoSonar')->willReturn([
            'code' => 500,
            'message' => 'Erreur NoSonar'
        ]);

        $result = $this->controller->collecte('portefeuille', 'maven_key', 'mode_collecte', 'utilisateur_collecte');

        $this->assertSame(500, $result['code']);
        $this->assertArrayHasKey('Collecte', $result);

        $collecte = $result['Collecte'];
        $this->assertContains('**** ERREUR : NOSONAR 500 ****', $collecte[23]);
    }

    public function testCollecteWithTodoError()
    {
        $this->batchCollecteInformation->method('batchCollecteInformation')->willReturn([
            'code' => 200,
            'message' => static::$informationProjet,
            'data' => []
        ]);
        $this->batchCollecteMesure->method('batchCollecteMesure')->willReturn([
            'code' => 200,
            'message' => 'Mesure',
            'data' => []
        ]);
        $this->batchCollecteNote->method('batchCollecteNote')->withConsecutive(
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'reliability'],
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'security'],
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'sqale']
        )->willReturnOnConsecutiveCalls(
            ['code' => 200, 'message' => ['value' => 'A'], 'data' => ['note_reliability' => 'A']],
            ['code' => 200, 'message' => ['value' => 'B'], 'data' => ['note_security' => 'B']],
            ['code' => 200, 'message' => ['value' => 'A'], 'data' => ['note_security' => 'C']]
        );
        $this->batchCollecteAnomalie->method('batchCollecteAnomalie')->willReturn([
            'code' => 200,
            'message' => 'Anomalie',
            'data' => []
        ]);
        $this->batchCollecteAnomalieDetail->method('batchCollecteAnomalieDetail')->willReturn([
            'code' => 200,
            'message' => static::$anomalieDetail,
            'data' => []
        ]);
        $this->batchCollecteHotspot->method('batchCollecteHotspot')->willReturn([
            'code' => 200,
            'message' => 'Hotspot',
            'data' => []
        ]);
        $this->batchCollecteNote->method('batchCollecteNoteHotspot')->willReturn([
            'code' => 200,
            'message' => static::$noteHotspot,
            'data' => []
        ]);
        $this->batchCollecteHotspotDetail->method('batchCollecteHotspotDetail')->willReturn([
            'code' => 200,
            'message' => static::$hotspotDetail,
            'data' => []
        ]);
        $this->batchCollecteOwasp->method('batchCollecteOwasp')->willReturn([
            'code' => 200,
            'message' => 'Owasp',
            'data' => []
        ]);

        $this->batchCollecteHotspotOwasp->method('batchCollecteHotspotOwasp')->willReturn(['code' => 200, 'message' => static::$owaspA0A10, 'data' => [], 'info'=>'enregistrement', 'owasp_2017'=>'', 'owasp_2021' => '']);
        $this->batchCollecteNoSonar->method('batchCollecteNoSonar')->willReturn([
            'code' => 200,
            'message' => 'NoSonar',
            'data' => []
        ]);

        $this->batchCollecteTodo->method('batchCollecteTodo')->willReturn([
            'code' => 500,
            'message' => 'Error Todo'
        ]);

        $result = $this->controller->collecte('portefeuille', 'maven_key', 'mode_collecte', 'utilisateur_collecte');

        $this->assertSame(500, $result['code']);
        $this->assertArrayHasKey('Collecte', $result);

        $collecte = $result['Collecte'];
        $this->assertContains('**** ERREUR : TODO 500 ****', $collecte[24]);
    }

    public function testCollecteWithActuatorInfoError()
    {
        $this->batchCollecteInformation->method('batchCollecteInformation')->willReturn([
            'code' => 200,
            'message' => static::$informationProjet,
            'data' => []
        ]);
        $this->batchCollecteMesure->method('batchCollecteMesure')->willReturn([
            'code' => 200,
            'message' => 'Mesure',
            'data' => []
        ]);
        $this->batchCollecteNote->method('batchCollecteNote')->withConsecutive(
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'reliability'],
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'security'],
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'sqale']
        )->willReturnOnConsecutiveCalls(
            ['code' => 200, 'message' => ['value' => 'A'], 'data' => ['note_reliability' => 'A']],
            ['code' => 200, 'message' => ['value' => 'B'], 'data' => ['note_security' => 'B']],
            ['code' => 200, 'message' => ['value' => 'A'], 'data' => ['note_security' => 'C']]
        );
        $this->batchCollecteAnomalie->method('batchCollecteAnomalie')->willReturn([
            'code' => 200,
            'message' => 'Anomalie',
            'data' => []
        ]);
        $this->batchCollecteAnomalieDetail->method('batchCollecteAnomalieDetail')->willReturn([
            'code' => 200,
            'message' => static::$anomalieDetail,
            'data' => []
        ]);
        $this->batchCollecteHotspot->method('batchCollecteHotspot')->willReturn([
            'code' => 200,
            'message' => 'Hotspot',
            'data' => []
        ]);
        $this->batchCollecteNote->method('batchCollecteNoteHotspot')->willReturn([
            'code' => 200,
            'message' => static::$noteHotspot,
            'data' => []
        ]);
        $this->batchCollecteHotspotDetail->method('batchCollecteHotspotDetail')->willReturn([
            'code' => 200,
            'message' => static::$hotspotDetail,
            'data' => []
        ]);
        $this->batchCollecteOwasp->method('batchCollecteOwasp')->willReturn([
            'code' => 200,
            'message' => 'Owasp',
            'data' => []
        ]);
        $this->batchCollecteHotspotOwasp->method('batchCollecteHotspotOwasp')->willReturn(['code' => 200, 'message' => static::$owaspA0A10, 'data' => [], 'info'=>'enregistrement', 'owasp_2017'=>'', 'owasp_2021' => '']);
        $this->batchCollecteNoSonar->method('batchCollecteNoSonar')->willReturn([
            'code' => 200,
            'message' => 'NoSonar',
            'data' => []
        ]);
        $this->batchCollecteTodo->method('batchCollecteTodo')->willReturn([
            'code' => 200,
            'message' => 'Todo',
            'data' => []
        ]);

        $this->batchCollecteActuator->method('BatchCollecteActuatorInfo')->willReturn([
            'code' => 500,
            'message' => 'Error ActuatorInfo'
        ]);

        $result = $this->controller->collecte('portefeuille', 'maven_key', 'mode_collecte', 'utilisateur_collecte');

        $this->assertSame(500, $result['code']);
        $this->assertArrayHasKey('Collecte', $result);

        $collecte = $result['Collecte'];
        $this->assertContains('**** ERREUR : ACTUATOR INFO 500 ****', $collecte[25]);
    }

    public function testCollecteWithLoggerError()
    {
        $this->batchCollecteInformation->method('batchCollecteInformation')->willReturn([
            'code' => 200,
            'message' => static::$informationProjet,
            'data' => []
        ]);
        $this->batchCollecteMesure->method('batchCollecteMesure')->willReturn([
            'code' => 200,
            'message' => 'Mesure',
            'data' => []
        ]);
        $this->batchCollecteNote->method('batchCollecteNote')->withConsecutive(
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'reliability'],
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'security'],
            ['maven_key', 'mode_collecte', 'utilisateur_collecte', 'sqale']
        )->willReturnOnConsecutiveCalls(
            ['code' => 200, 'message' => ['value' => 'A'], 'data' => ['note_reliability' => 'A']],
            ['code' => 200, 'message' => ['value' => 'B'], 'data' => ['note_security' => 'B']],
            ['code' => 200, 'message' => ['value' => 'A'], 'data' => ['note_security' => 'C']]
        );
        $this->batchCollecteAnomalie->method('batchCollecteAnomalie')->willReturn([
            'code' => 200,
            'message' => 'Anomalie',
            'data' => []
        ]);
        $this->batchCollecteAnomalieDetail->method('batchCollecteAnomalieDetail')->willReturn([
            'code' => 200,
            'message' => static::$anomalieDetail,
            'data' => []
        ]);
        $this->batchCollecteHotspot->method('batchCollecteHotspot')->willReturn([
            'code' => 200,
            'message' => 'Hotspot',
            'data' => []
        ]);
        $this->batchCollecteNote->method('batchCollecteNoteHotspot')->willReturn([
            'code' => 200,
            'message' => static::$noteHotspot,
            'data' => []
        ]);
        $this->batchCollecteHotspotDetail->method('batchCollecteHotspotDetail')->willReturn([
            'code' => 200,
            'message' => static::$hotspotDetail,
            'data' => []
        ]);
        $this->batchCollecteOwasp->method('batchCollecteOwasp')->willReturn([
            'code' => 200,
            'message' => 'Owasp',
            'data' => []
        ]);
        $this->batchCollecteHotspotOwasp->method('batchCollecteHotspotOwasp')->willReturn(['code' => 200, 'message' => static::$owaspA0A10, 'data' => [], 'info'=>'enregistrement', 'owasp_2017'=>'', 'owasp_2021' => '']);
        $this->batchCollecteNoSonar->method('batchCollecteNoSonar')->willReturn([
            'code' => 200,
            'message' => 'NoSonar',
            'data' => []
        ]);
        $this->batchCollecteTodo->method('batchCollecteTodo')->willReturn([
            'code' => 200,
            'message' => 'Todo',
            'data' => []
        ]);
        $this->batchCollecteActuator->method('BatchCollecteActuatorInfo')->willReturn([
            'code' => 200,
            'message' => 'Actuator Info',
            'data' => []
        ]);

        $this->batchCollecteLogger->method('batchCollecteLogger')->willReturn([
            'code' => 500,
            'message' => 'Error Logger'
        ]);

        $result = $this->controller->collecte('portefeuille', 'maven_key', 'mode_collecte', 'utilisateur_collecte');

        $this->assertSame(500, $result['code']);
        $this->assertArrayHasKey('Collecte', $result);

        $collecte = $result['Collecte'];
        $this->assertContains('**** ERREUR : LOGGER 500 ****', $collecte[26]);
    }
}
