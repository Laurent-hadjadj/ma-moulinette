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

/* MODIF 2026-05-24 : réécriture complète des tests.
 * sauvegardeHistorique est désormais stats-only (plus de ClientService /
 * MessageBusInterface / lancerBatch). 11 scénarios → 7 scénarios.*/
declare(strict_types=1);

namespace App\Tests\Unit\Controller\Activity;

use App\Controller\Activity\ApiActivityController;
use App\Entity\{Activity, ActivityHistorique};
use App\Repository\{ActivityHistoriqueRepository, ActivityRepository};
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[AllowMockObjectsWithoutExpectations]
class ApiActivityControllerTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */         private MockObject $em;
    /** @var Security&MockObject */                       private MockObject $security;
    /** @var LoggerInterface&MockObject */                private MockObject $logger;
    /** @var ActivityRepository&MockObject */             private MockObject $activityRepo;
    /** @var ActivityHistoriqueRepository&MockObject */   private MockObject $historiqueRepo;
    /** @var AuthorizationCheckerInterface&MockObject */  private MockObject $authChecker;

    private ApiActivityController $controller;

    protected function setUp(): void
    {
        $this->em             = $this->createMock(EntityManagerInterface::class);
        $this->security       = $this->createMock(Security::class);
        $this->logger         = $this->createMock(LoggerInterface::class);
        $this->activityRepo   = $this->createMock(ActivityRepository::class);
        $this->historiqueRepo = $this->createMock(ActivityHistoriqueRepository::class);
        $this->authChecker    = $this->createMock(AuthorizationCheckerInterface::class);

        $this->em->method('getRepository')->willReturnMap([
            [Activity::class,          $this->activityRepo],
            [ActivityHistorique::class, $this->historiqueRepo],
        ]);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnMap([
            ['security.authorization_checker', true],
        ]);
        $container->method('get')->willReturnMap([
            ['security.authorization_checker', 1, $this->authChecker],
        ]);

        $this->controller = new ApiActivityController($this->em, $this->security, $this->logger);
        $this->controller->setContainer($container);
    }

    // ─── sauvegardeHistorique ──────────────────────────────────────────────────

    public function testSauvegardeReturns403WithoutRole(): void
    {
        $this->authChecker->method('isGranted')->willReturn(false);

        $response = $this->controller->sauvegardeHistorique();
        $data = json_decode($response->getContent(), true);

        $this->assertSame(403, $data['code']);
    }

    public function testSauvegardeReturns204WhenNoDatabaseData(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->activityRepo->method('premiereDate')->willReturn(['code' => 200, 'liste' => []]);

        $response = $this->controller->sauvegardeHistorique();
        $data = json_decode($response->getContent(), true);

        $this->assertSame(204, $data['code']);
        $this->assertSame('warning', $data['type']);
    }

    public function testSauvegardeInsertsHistoriqueOnFirstCallOfYear(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);

        $this->activityRepo->method('premiereDate')->willReturn([
            'code' => 200, 'liste' => ['date' => '2026-01-15 00:00:00'],
        ]);
        $this->activityRepo->method('nombreAnalyse')->willReturn([
            'code' => 200, 'request' => ['nb_analyse' => 50],
        ]);
        $this->activityRepo->method('nombreStatus')->willReturn([
            'code' => 200, 'request' => ['nb_status' => 45],
        ]);
        $this->activityRepo->method('tempsExecutionMax')->willReturn([
            'code' => 200, 'request' => ['max_time' => 120],
        ]);

        // selectActivity() renvoie la clé 'liste' : vide au 1er appel → insert.
        $this->historiqueRepo->method('selectActivity')->willReturnOnConsecutiveCalls(
            ['liste' => []],
            ['liste' => [['date_enregistrement' => '2026-01-15 10:00:00']]],
        );

        $captured = null;
        $this->historiqueRepo->expects($this->once())
            ->method('insertHistoriqueActivity')
            ->willReturnCallback(function (array $data) use (&$captured): array {
                $captured = $data;
                return ['code' => 200];
            });

        $response = $this->controller->sauvegardeHistorique();
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);

        // ── Régression sur les 4 bugs de calcul corrigés le 2026-07-15 ──
        $annee = array_key_first($captured);
        // 1) La colonne « Analyse » conserve le TOTAL (50), elle n'est plus écrasée par un ratio.
        $this->assertSame(50, $captured[$annee]['analyse']);
        // 2) Le nombre d'échecs est sous la clé 'failed' (binding repository), plus 'fail'.
        $this->assertSame(45, $captured[$annee]['failed']);
        $this->assertArrayNotHasKey('fail', $captured[$annee]);
        // 3) Taux = succès/total = 45/50 = 90 % (borné à 100 %), plus l'inverse.
        $this->assertSame(90.0, $captured[$annee]['success_rate']);
        // 4) La moyenne d'analyses par jour a sa propre colonne.
        $this->assertArrayHasKey('analyse_average', $captured[$annee]);
    }

    public function testSauvegardeUpdatesHistoriqueOnSubsequentCall(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);

        $this->activityRepo->method('premiereDate')->willReturn([
            'code' => 200, 'liste' => ['date' => '2026-01-15 00:00:00'],
        ]);
        $this->activityRepo->method('nombreAnalyse')->willReturn([
            'code' => 200, 'request' => ['nb_analyse' => 100],
        ]);
        $this->activityRepo->method('nombreStatus')->willReturn([
            'code' => 200, 'request' => ['nb_status' => 90],
        ]);
        $this->activityRepo->method('tempsExecutionMax')->willReturn([
            'code' => 200, 'request' => ['max_time' => 200],
        ]);

        // selectActivity() renvoie la clé 'liste' : non vide au 1er appel → update.
        $this->historiqueRepo->method('selectActivity')->willReturnOnConsecutiveCalls(
            ['liste' => [['year' => '2026']]],
            ['liste' => [['date_enregistrement' => '2026-04-24 11:00:00']]],
        );
        $this->historiqueRepo->expects($this->once())->method('updateHistoriqueActivity');

        $response = $this->controller->sauvegardeHistorique();
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
    }

    // ─── apiDessin ─────────────────────────────────────────────────────────────

    public function testDessinReturns403WithoutRole(): void
    {
        $this->authChecker->method('isGranted')->willReturn(false);

        $response = $this->controller->apiDessin($this->jsonRequest(['source' => 'analyse']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(403, $data['code']);
    }

    public function testDessinReturns400WhenSourceMissing(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);

        $response = $this->controller->apiDessin($this->jsonRequest(['other' => 'x']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(400, $data['code']);
    }

    public function testDessinReturns400OnInvalidJson(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);

        $response = $this->controller->apiDessin($this->jsonRequest('garbage'));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(400, $data['code']);
    }

    public function testDessinAnalyseCallsListeAnalyseJour(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->activityRepo->expects($this->once())
            ->method('listeAnalyseJour')
            ->willReturn(['code' => 200, 'request' => []]);

        $response = $this->controller->apiDessin($this->jsonRequest(['source' => 'analyse']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
    }

    public function testDessinProjetCallsListeProjectAnalyse(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->activityRepo->expects($this->once())
            ->method('listeProjectAnalyse')
            ->willReturn(['code' => 200, 'request' => []]);

        $response = $this->controller->apiDessin($this->jsonRequest(['source' => 'projet']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
    }

    public function testDessinProjetAnalyseCallsListeAnalyseProjet(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->activityRepo->expects($this->once())
            ->method('listeAnalyseProjet')
            ->willReturn(['code' => 200, 'request' => []]);

        $response = $this->controller->apiDessin($this->jsonRequest(['source' => 'projet_analyse']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
    }

    public function testDessinReturns400OnUnknownSource(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->activityRepo->expects($this->never())->method('listeAnalyseJour');
        $this->activityRepo->expects($this->never())->method('listeProjectAnalyse');
        $this->activityRepo->expects($this->never())->method('listeAnalyseProjet');

        $response = $this->controller->apiDessin($this->jsonRequest(['source' => 'bogus']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(400, $data['code']);
        $this->assertStringContainsString('inconnue', $data['message']);
    }

    // ─── helper ────────────────────────────────────────────────────────────────

    private function jsonRequest(array|string $body): Request
    {
        $content = is_string($body) ? $body : json_encode($body, JSON_FORCE_OBJECT);
        return new Request([], [], [], [], [], [], $content);
    }
}
