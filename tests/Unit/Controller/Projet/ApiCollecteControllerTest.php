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

use App\Controller\Batch\{BatchCollecteActuatorController, BatchCollecteAnomalieController, BatchCollecteAnomalieDetailController, BatchCollecteHotspotController, BatchCollecteHotspotDetailController,BatchCollecteHotspotOwaspController, BatchCollecteInformationProjetController, BatchCollecteLoggerController,BatchCollecteMesureController, BatchCollecteNoSonarController, BatchCollecteOwaspController, BatchCollecteTodoController, BatchCollecteCleanCodeController};
use App\Controller\Projet\ApiCollecteController;
use App\Entity\Utilisateur;
use PHPUnit\Framework\Attributes\{AllowMockObjectsWithoutExpectations, DataProvider};
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\{JsonResponse, Request};
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[AllowMockObjectsWithoutExpectations]
class ApiCollecteControllerTest extends TestCase
{
    private const MAVEN_KEY = 'fr.ma-moulinette:ma-moulinette';
    private const USER_EMAIL = 'collecte@ma-moulinette.fr';

    /** @var BatchCollecteInformationProjetController&MockObject */  private MockObject $batchInfo;
    /** @var BatchCollecteMesureController&MockObject */             private MockObject $batchMesure;
    /** @var BatchCollecteOwaspController&MockObject */              private MockObject $batchOwasp;
    /** @var BatchCollecteHotspotController&MockObject */            private MockObject $batchHotspot;
    /** @var BatchCollecteAnomalieController&MockObject */           private MockObject $batchAnomalie;
    /** @var BatchCollecteAnomalieDetailController&MockObject */     private MockObject $batchAnomalieDetail;
    /** @var BatchCollecteHotspotOwaspController&MockObject */       private MockObject $batchHotspotOwasp;
    /** @var BatchCollecteHotspotDetailController&MockObject */      private MockObject $batchHotspotDetail;
    /** @var BatchCollecteNoSonarController&MockObject */            private MockObject $batchNoSonar;
    /** @var BatchCollecteTodoController&MockObject */               private MockObject $batchTodo;
    /** @var BatchCollecteActuatorController&MockObject */           private MockObject $batchActuator;
    /** @var BatchCollecteLoggerController&MockObject */             private MockObject $batchLogger;
    /** @var BatchCollecteCleanCodeController&MockObject */          private MockObject $batchCleanCode;

    /** @var Security&MockObject */                                  private MockObject $security;
    /** @var LoggerInterface&MockObject */                           private MockObject $logger;

    /** @var AuthorizationCheckerInterface&MockObject */             private MockObject $authChecker;

    private ApiCollecteController $controller;

    protected function setUp(): void
    {
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
        /* MODIF 2026-05-17 : retour silencieux par défaut (SQ < 10). */
        $this->batchCleanCode = $this->createMock(BatchCollecteCleanCodeController::class);
        $this->batchCleanCode->method('BatchCollecteCleanCode')
            ->willReturn(['code' => 200, 'skipped' => true]);

        $this->security = $this->createMock(Security::class);
        $user = $this->createMock(Utilisateur::class);
        $user->method('getCourriel')->willReturn(self::USER_EMAIL);
        $user->method('getUserIdentifier')->willReturn(self::USER_EMAIL);
        $this->security->method('getUser')->willReturn($user);

        $this->logger = $this->createMock(LoggerInterface::class);

        // AbstractController::isGranted() + json() passent par le container
        $this->authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        // Par défaut, le rôle est accordé — les tests 403 overridet
        $this->authChecker->method('isGranted')->willReturn(true);

        // appUser() (via AbstractController::getUser()) interroge security.token_storage
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnMap([
            ['security.authorization_checker', true],
            ['security.token_storage', true],
            ['parameter_bag', false],
            ['serializer', false],
        ]);
        $container->method('get')->willReturnMap([
            ['security.authorization_checker', 1, $this->authChecker],
            ['security.token_storage', 1, $tokenStorage],
        ]);

        $this->controller = new ApiCollecteController(
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
            $this->batchCleanCode,
            $this->security,
            $this->logger,
        );
        $this->controller->setContainer($container);
    }

    // ═════════════ 400 & 403 parameterized across all endpoints ═══════════

    #[DataProvider('endpointsProvider')]
    public function testEndpointReturns400WhenMavenKeyMissing(string $method): void
    {
        $response = $this->controller->{$method}($this->jsonRequest([]));

        $this->assertJsonStatus($response, 400, 'error');
    }

    #[DataProvider('endpointsProvider')]
    public function testEndpointReturns403WhenRoleMissing(string $method): void
    {
        $strictAuthChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $strictAuthChecker->method('isGranted')->willReturn(false);

        // Réutilise le token_storage du setUp via un nouveau container limité au strict checker
        $user = $this->createMock(Utilisateur::class);
        $user->method('getCourriel')->willReturn(self::USER_EMAIL);
        $user->method('getUserIdentifier')->willReturn(self::USER_EMAIL);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnMap([
            ['security.authorization_checker', true],
            ['security.token_storage', true],
            ['parameter_bag', false],
            ['serializer', false],
        ]);
        $container->method('get')->willReturnMap([
            ['security.authorization_checker', 1, $strictAuthChecker],
            ['security.token_storage', 1, $tokenStorage],
        ]);
        $this->controller->setContainer($container);

        $body = $method === 'apiCollecteHotspotOwasp'
            ? ['maven_key' => self::MAVEN_KEY, 'menace' => 'a1']
            : ['maven_key' => self::MAVEN_KEY];

        $response = $this->controller->{$method}($this->jsonRequest($body));

        $this->assertJsonStatus($response, 403, 'warning');
    }

    public static function endpointsProvider(): array
    {
        return [
            'information'     => ['apiCollecteInformation'],
            'mesure'          => ['apiCollecteMesure'],
            'owasp'           => ['apiCollecteOwasp'],
            'hotspot'         => ['apiCollecteHotspot'],
            'anomalie'        => ['apiCollecteAnomalie'],
            'anomalieDetail'  => ['apiCollecteAnomalieDetail'],
            'hotspotOwasp'    => ['apiCollecteHotspotOwasp'],
            'hotspotDetail'   => ['apiCollecteHotspotDetail'],
            'noSonar'         => ['apiCollecteNoSonar'],
            'todo'            => ['apiCollecteTodo'],
            'actuator'        => ['apiCollecteActuator'],
            'logger'          => ['apiCollecteLogger'],
        ];
    }

    // ═════════════ Happy path per endpoint ══════════════════════════════════

    public function testApiCollecteInformationReturnsVersionBreakdownOnSuccess(): void
    {
        $this->batchInfo->expects($this->once())
            ->method('batchCollecteInformation')
            ->with(self::MAVEN_KEY, 'COLLECTE', self::USER_EMAIL)
            ->willReturn(['code' => 200, 'historique' => [
                'version' => '1.0.0',
                'version_release' => 3, 'version_snapshot' => 1, 'version_autre' => 0,
                'version_release_sonar' => 5, 'version_snapshot_sonar' => 2, 'version_autre_sonar' => 1,
            ]]);

        $payload = $this->decode($this->controller->apiCollecteInformation(
            $this->jsonRequest(['maven_key' => self::MAVEN_KEY])
        ));

        $this->assertSame(200, $payload['code']);
        $this->assertSame('1.0.0', $payload['message']['projet_version']);
        $this->assertSame(8, $payload['message']['total_sonar']); // 5+2+1
    }

    public function testApiCollecteMesureReturnsProjectDataOnSuccess(): void
    {
        $this->batchMesure->expects($this->once())
            ->method('batchCollecteMesure')
            ->willReturn(['code' => 200, 'data' => [
                'maven_key' => self::MAVEN_KEY, 'project_name' => 'App',
                'lines' => 1000, 'ncloc' => 800, 'classes' => 50,
                'functions' => 200, 'files' => 30,
                'language_distribution' => ['java' => 100],
                'sqale_debt_ratio' => 1.5, 'coverage' => 85,
                'duplicated_lines_density' => 2.0, 'tests' => 150, 'issues' => 10,
            ]]);

        $payload = $this->decode($this->controller->apiCollecteMesure(
            $this->jsonRequest(['maven_key' => self::MAVEN_KEY])
        ));

        $this->assertSame(200, $payload['code']);
        $this->assertSame('App', $payload['message']['project_name']);
        $this->assertSame(800, $payload['message']['ncloc']);
    }

    public function testApiCollecteOwaspReturnsBothReferentials(): void
    {
        $this->batchOwasp->expects($this->once())
            ->method('batchCollecteOwasp')
            ->willReturn(['code' => 200, 'owasp2017' => 3, 'owasp2021' => 5]);

        $payload = $this->decode($this->controller->apiCollecteOwasp(
            $this->jsonRequest(['maven_key' => self::MAVEN_KEY])
        ));

        $this->assertSame(200, $payload['code']);
        $this->assertSame(3, $payload['owasp2017']);
        $this->assertSame(5, $payload['owasp2021']);
    }

    public function testApiCollecteHotspotReturnsCountBreakdown(): void
    {
        $this->batchHotspot->expects($this->once())
            ->method('batchCollecteHotspot')
            ->willReturn(['code' => 200, 'historique' => [
                'hotspot_high' => 2, 'hotspot_medium' => 4,
                'hotspot_low' => 1, 'nombre_hotspot' => 7,
            ]]);

        $payload = $this->decode($this->controller->apiCollecteHotspot(
            $this->jsonRequest(['maven_key' => self::MAVEN_KEY])
        ));

        $this->assertSame(200, $payload['code']);
        $this->assertSame(7, $payload['nombre']);
        $this->assertSame(2, $payload['message']['hotspot_high']);
    }

    public function testApiCollecteAnomalieReturnsHistoriqueViolations(): void
    {
        $this->batchAnomalie->expects($this->once())
            ->method('batchCollecteAnomalie')
            ->willReturn(['code' => 200,
                'info' => 'info text',
                'historique' => [
                    'violations' => 10, 'nombre_bug' => 2,
                    'nombre_vulnerability' => 3, 'nombre_code_smell' => 5,
                ],
            ]);

        $payload = $this->decode($this->controller->apiCollecteAnomalie(
            $this->jsonRequest(['maven_key' => self::MAVEN_KEY])
        ));

        $this->assertSame(200, $payload['code']);
        $this->assertSame('info text', $payload['info']);
        $this->assertSame(10, $payload['message']['violations']);
    }

    public function testApiCollecteAnomalieDetailReturnsHistoriqueAsData(): void
    {
        $historique = ['bug_blocker' => 1, 'code_smell_info' => 2];
        $this->batchAnomalieDetail->expects($this->once())
            ->method('BatchCollecteAnomalieDetail')
            ->willReturn(['code' => 200, 'historique' => $historique]);

        $payload = $this->decode($this->controller->apiCollecteAnomalieDetail(
            $this->jsonRequest(['maven_key' => self::MAVEN_KEY, 'type' => 'BUG'])
        ));

        $this->assertSame(200, $payload['code']);
        $this->assertSame($historique, $payload['data']);
    }

    public function testApiCollecteHotspotOwaspRequiresMenaceField(): void
    {
        // Missing 'menace' → 400 (different from other endpoints which only need maven_key)
        $response = $this->controller->apiCollecteHotspotOwasp(
            $this->jsonRequest(['maven_key' => self::MAVEN_KEY])
        );

        $this->assertJsonStatus($response, 400, 'error');
    }

    public function testApiCollecteHotspotOwaspReturnsOwaspCountsOnSuccess(): void
    {
        $this->batchHotspotOwasp->expects($this->once())
            ->method('BatchCollecteHotspotOwasp')
            ->with(self::MAVEN_KEY, 'COLLECTE', self::USER_EMAIL, 'a1')
            ->willReturn(['code' => 200,
                'info' => 'collect mode',
                'owasp_2017' => 2, 'owasp_2021' => 4,
                'message' => 'ok', 'data' => [['x' => 1]],
            ]);

        $payload = $this->decode($this->controller->apiCollecteHotspotOwasp(
            $this->jsonRequest(['maven_key' => self::MAVEN_KEY, 'menace' => 'a1'])
        ));

        $this->assertSame(200, $payload['code']);
        $this->assertSame(2, $payload['owasp2017']);
        $this->assertSame(4, $payload['owasp2021']);
        $this->assertSame([['x' => 1]], $payload['data']);
    }

    public function testApiCollecteHotspotDetailReturnsNombreOnSuccess(): void
    {
        $this->batchHotspotDetail->expects($this->once())
            ->method('BatchCollecteHotspotDetail')
            ->willReturn(['code' => 200, 'nombre' => 42]);

        $payload = $this->decode($this->controller->apiCollecteHotspotDetail(
            $this->jsonRequest(['maven_key' => self::MAVEN_KEY])
        ));

        $this->assertSame(200, $payload['code']);
        $this->assertSame(42, $payload['nombre']);
    }

    public function testApiCollecteNoSonarReturnsSumAndHistorique(): void
    {
        $this->batchNoSonar->expects($this->once())
            ->method('BatchCollecteNoSonar')
            ->willReturn(['code' => 200, 'historique' => [
                'suppress_warning' => 3, 'total_no_sonar' => 5,
            ]]);

        $payload = $this->decode($this->controller->apiCollecteNoSonar(
            $this->jsonRequest(['maven_key' => self::MAVEN_KEY])
        ));

        $this->assertSame(200, $payload['code']);
        $this->assertSame(8, $payload['nombre']);
        $this->assertSame(3, $payload['historique']['suppress_warning']);
        $this->assertSame(5, $payload['historique']['total_no_sonar']);
    }

    public function testApiCollecteTodoReturnsNombreOnSuccess(): void
    {
        /* MODIF 2026-05-08 : ajout de la cle 'historique'.
         * Why: apiCollecteTodo lit $todo['historique'] sans coalesce.
         * Sans cette cle, warning "Undefined array key 'historique'".
         */
        $this->batchTodo->expects($this->once())
            ->method('BatchCollecteTodo')
            ->willReturn(['code' => 200, 'nombre' => 17, 'historique' => []]);

        $payload = $this->decode($this->controller->apiCollecteTodo(
            $this->jsonRequest(['maven_key' => self::MAVEN_KEY])
        ));

        $this->assertSame(200, $payload['code']);
        $this->assertSame(17, $payload['nombre']);
    }

    public function testApiCollecteActuatorReturnsJsonPayload(): void
    {
        $this->batchActuator->expects($this->once())
            ->method('BatchCollecteActuatorInfo')
            ->willReturn(['code' => 200, 'json' => ['build' => ['version' => '2.0']]]);

        $payload = $this->decode($this->controller->apiCollecteActuator(
            $this->jsonRequest(['maven_key' => self::MAVEN_KEY])
        ));

        $this->assertSame(200, $payload['code']);
        $this->assertSame(['build' => ['version' => '2.0']], $payload['json']);
    }

    public function testApiCollecteLoggerReturnsCountsOnSuccess(): void
    {
        /* MODIF 2026-05-08 : ajout de la cle 'data'.
         * Why: apiCollecteLogger lit $logger['data'] sans coalesce.
         */
        $this->batchLogger->expects($this->once())
            ->method('BatchCollecteLogger')
            ->willReturn(['code' => 200, 'historique' => [
                'logger_info' => 10, 'logger_warn' => 5,
                'logger_error' => 2, 'logger_debug' => 20,
            ], 'data' => []]);

        $payload = $this->decode($this->controller->apiCollecteLogger(
            $this->jsonRequest(['maven_key' => self::MAVEN_KEY])
        ));

        $this->assertSame(200, $payload['code']);
    }

    // ═════════════ Error propagation from batch ════════════════════════════

    public function testApiCollecteInformationPropagatesBatchErrorAsAlert(): void
    {
        $this->batchInfo->expects($this->once())
            ->method('batchCollecteInformation')
            ->willReturn(['code' => 503, 'message' => 'timeout', 'erreur' => 'e']);

        $payload = $this->decode($this->controller->apiCollecteInformation(
            $this->jsonRequest(['maven_key' => self::MAVEN_KEY])
        ));

        $this->assertSame(503, $payload['code']);
        $this->assertSame('warning', $payload['type']);
        $this->assertSame('timeout', $payload['message']);
    }

    public function testApiCollecteOwaspPropagatesBatchError(): void
    {
        $this->batchOwasp->expects($this->once())
            ->method('batchCollecteOwasp')
            ->willReturn(['code' => 500, 'message' => 'owasp fail', 'erreur' => 'e']);

        $payload = $this->decode($this->controller->apiCollecteOwasp(
            $this->jsonRequest(['maven_key' => self::MAVEN_KEY])
        ));

        $this->assertSame(500, $payload['code']);
        $this->assertSame('error', $payload['type']);
    }

    public function testApiCollecteMesurePropagatesBatchError(): void
    {
        $this->batchMesure->method('batchCollecteMesure')
            ->willReturn(['code' => 500, 'message' => 'mesure fail', 'erreur' => 'e']);

        $payload = $this->decode($this->controller->apiCollecteMesure(
            $this->jsonRequest(['maven_key' => self::MAVEN_KEY])
        ));

        $this->assertSame(500, $payload['code']);
    }

    public function testApiCollecteHotspotPropagatesBatchError(): void
    {
        $this->batchHotspot->method('batchCollecteHotspot')
            ->willReturn(['code' => 500, 'message' => 'hotspot fail', 'erreur' => 'e']);

        $payload = $this->decode($this->controller->apiCollecteHotspot(
            $this->jsonRequest(['maven_key' => self::MAVEN_KEY])
        ));

        $this->assertSame(500, $payload['code']);
    }

    public function testApiCollecteAnomaliePropagatesBatchError(): void
    {
        $this->batchAnomalie->method('batchCollecteAnomalie')
            ->willReturn(['code' => 500, 'message' => 'anomalie fail', 'erreur' => 'e']);

        $payload = $this->decode($this->controller->apiCollecteAnomalie(
            $this->jsonRequest(['maven_key' => self::MAVEN_KEY])
        ));

        $this->assertSame(500, $payload['code']);
    }

    public function testApiCollecteAnomalieDetailPropagatesBatchError(): void
    {
        $this->batchAnomalieDetail->method('BatchCollecteAnomalieDetail')
            ->willReturn(['code' => 500, 'message' => 'detail fail', 'erreur' => 'e']);

        $payload = $this->decode($this->controller->apiCollecteAnomalieDetail(
            $this->jsonRequest(['maven_key' => self::MAVEN_KEY])
        ));

        $this->assertSame(500, $payload['code']);
    }

    public function testApiCollecteHotspotOwaspPropagatesBatchError(): void
    {
        $this->batchHotspotOwasp->method('batchCollecteHotspotOwasp')
            ->willReturn(['code' => 500, 'message' => 'owasp hotspot fail', 'erreur' => 'e']);

        $payload = $this->decode($this->controller->apiCollecteHotspotOwasp(
            $this->jsonRequest(['maven_key' => self::MAVEN_KEY, 'menace' => 'a1'])
        ));

        $this->assertSame(500, $payload['code']);
    }

    public function testApiCollecteHotspotDetailPropagatesBatchError(): void
    {
        $this->batchHotspotDetail->method('batchCollecteHotspotDetail')
            ->willReturn(['code' => 500, 'message' => 'detail fail', 'erreur' => 'e']);

        $payload = $this->decode($this->controller->apiCollecteHotspotDetail(
            $this->jsonRequest(['maven_key' => self::MAVEN_KEY])
        ));

        $this->assertSame(500, $payload['code']);
    }

    public function testApiCollecteNoSonarPropagatesBatchError(): void
    {
        $this->batchNoSonar->method('batchCollecteNoSonar')
            ->willReturn(['code' => 500, 'message' => 'nosonar fail', 'erreur' => 'e']);

        $payload = $this->decode($this->controller->apiCollecteNoSonar(
            $this->jsonRequest(['maven_key' => self::MAVEN_KEY])
        ));

        $this->assertSame(500, $payload['code']);
    }

    public function testApiCollecteTodoPropagatesBatchError(): void
    {
        $this->batchTodo->method('batchCollecteTodo')
            ->willReturn(['code' => 500, 'message' => 'todo fail', 'erreur' => 'e']);

        $payload = $this->decode($this->controller->apiCollecteTodo(
            $this->jsonRequest(['maven_key' => self::MAVEN_KEY])
        ));

        $this->assertSame(500, $payload['code']);
    }

    public function testApiCollecteActuatorPropagatesBatchError(): void
    {
        $this->batchActuator->method('BatchCollecteActuatorInfo')
            ->willReturn(['code' => 500, 'message' => 'actuator fail', 'erreur' => 'e']);

        $payload = $this->decode($this->controller->apiCollecteActuator(
            $this->jsonRequest(['maven_key' => self::MAVEN_KEY])
        ));

        $this->assertSame(500, $payload['code']);
    }

    public function testApiCollecteLoggerPropagatesBatchError(): void
    {
        $this->batchLogger->method('BatchCollecteLogger')
            ->willReturn(['code' => 500, 'message' => 'logger fail', 'erreur' => 'e']);

        $payload = $this->decode($this->controller->apiCollecteLogger(
            $this->jsonRequest(['maven_key' => self::MAVEN_KEY])
        ));

        $this->assertSame(500, $payload['code']);
    }

    // ═════════════ helpers ═════════════════════════════════════════════════

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
