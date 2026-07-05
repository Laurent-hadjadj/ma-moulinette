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

use App\Controller\Batch\BatchCollecteLoggerController;
use App\Entity\InformationProjet;
use App\Entity\Logger as LoggerEntity;
use App\Entity\LoggerDetail;
use App\Repository\InformationProjetRepository;
use App\Repository\LoggerDetailRepository;
use App\Repository\LoggerRepository;
use App\Service\{ClientService, UrlBuilderService};
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AllowMockObjectsWithoutExpectations]
class BatchCollecteLoggerControllerTest extends TestCase
{
    private const MAVEN_KEY = 'com.acme:app';

    /** @var EntityManagerInterface&MockObject */     private MockObject $em;
    /** @var ClientService&MockObject */               private MockObject $client;
    /** @var UrlBuilderService&MockObject */           private MockObject $urlBuilder;
    /** @var LoggerInterface&MockObject */             private MockObject $logger;
    /** @var LoggerRepository&MockObject */            private MockObject $repo;
    /** @var LoggerDetailRepository&MockObject */      private MockObject $loggerDetailRepo;
    /** @var InformationProjetRepository&MockObject */ private MockObject $informationProjetRepo;
    /** @var ParameterBagInterface&MockObject */       private MockObject $parameterBag;

    private BatchCollecteLoggerController $controller;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->client = $this->createMock(ClientService::class);
        $this->urlBuilder = $this->createMock(UrlBuilderService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->repo = $this->createMock(LoggerRepository::class);
        $this->loggerDetailRepo = $this->createMock(LoggerDetailRepository::class);
        $this->informationProjetRepo = $this->createMock(InformationProjetRepository::class);
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);

        /* MODIF 2026-05-15 [logger-details] : routing par classe pour supporter
         * les nouveaux repos Logger / LoggerDetail / InformationProjet appeles par le controller. */
        $this->em->expects($this->atLeastOnce())
            ->method('getRepository')
            ->willReturnCallback(fn ($class) => match ($class) {
                LoggerEntity::class      => $this->repo,
                LoggerDetail::class      => $this->loggerDetailRepo,
                InformationProjet::class => $this->informationProjetRepo,
                default                  => $this->repo,
            });

        $this->urlBuilder->method('build')->willReturn('https://sonar.example.com/api/...');

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnMap([['parameter_bag', true]]);
        $container->method('get')->willReturnMap([['parameter_bag', 1, $this->parameterBag]]);

        $this->controller = new BatchCollecteLoggerController(
            $this->em, $this->client, $this->urlBuilder, $this->logger
        );
        $this->controller->setContainer($container);
    }

    public function testReturns404WhenTrackLoggerPluginDisabled(): void
    {
        $this->parameterBag->method('get')->willReturnMap([
            ['sonar.url', 'https://sonar.example.com'],
            ['track.logger.method', 'false'],
        ]);

        // Aucun appel HTTP ne doit partir
        $this->client->expects($this->never())->method('httpSonarQube');
        $this->repo->expects($this->never())->method('insertLogger');

        $result = $this->controller->BatchCollecteLogger(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(404, $result['code']);
        $this->assertSame(0, $result['historique']['logger_info']);
    }

    public function testReturnsErrorWhenFirstHttpCallFails(): void
    {
        $this->parameterBag->method('get')->willReturnMap([
            ['sonar.url', 'https://sonar.example.com'],
            ['track.logger.method', 'true'],
        ]);

        $this->client->expects($this->once())
            ->method('httpSonarQube')
            ->willReturn(['code' => 500, 'erreur' => 'sonar down']);

        $this->repo->expects($this->never())->method('deleteLoggerMavenKey');

        $result = $this->controller->BatchCollecteLogger(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(500, $result['code']);
        $this->assertSame('track-info-method', $result['tracker']);
    }

    public function testReturnsErrorWhenDeleteFails(): void
    {
        $this->parameterBag->method('get')->willReturnMap([
            ['sonar.url', 'https://sonar.example.com'],
            ['track.logger.method', 'true'],
        ]);

        // 4 appels Sonar OK (1 par tracker)
        $this->client->expects($this->exactly(4))
            ->method('httpSonarQube')
            ->willReturn(['code' => 200, 'json' => ['paging' => ['total' => 1]]]);

        $this->repo->expects($this->once())
            ->method('deleteLoggerMavenKey')
            ->willReturn(['code' => 500, 'erreur' => 'delete failed']);

        $this->repo->expects($this->never())->method('insertLogger');

        $result = $this->controller->BatchCollecteLogger(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(500, $result['code']);
    }

    public function testHappyPathPersistsAndReturnsHistorique(): void
    {
        $this->parameterBag->method('get')->willReturnMap([
            ['sonar.url', 'https://sonar.example.com'],
            ['track.logger.method', '1'],
        ]);

        // 4 appels distincts → INFO=10, WARN=5, ERROR=2, DEBUG=20
        $totals = [10, 5, 2, 20];
        $call = 0;
        $this->client->expects($this->exactly(4))
            ->method('httpSonarQube')
            ->willReturnCallback(function () use (&$call, $totals) {
                return ['code' => 200, 'json' => ['paging' => ['total' => $totals[$call++]], 'issues' => []]];
            });

        $this->repo->method('deleteLoggerMavenKey')->willReturn(['code' => 200]);

        /* MODIF 2026-05-15 : stubs minimaux pour le flow detail.
         * Sans issues retournées, detailRows est vide ; le controller appelle
         * tout de même delete + insert (batch vide) + countByLevelAndFramework. */
        $this->informationProjetRepo->method('selectInformationProjetVersion')
            ->willReturn(['code' => 200, 'info' => [['version' => '4.2.0']]]);
        $this->loggerDetailRepo->method('deleteLoggerDetailMavenKey')->willReturn(['code' => 200]);
        $this->loggerDetailRepo->method('insertLoggerDetailBatch')->willReturn(['code' => 200, 'nombre' => 0]);
        $this->loggerDetailRepo->method('countByMavenKeyGroupedByLevelAndFramework')
            ->willReturn(['code' => 200, 'liste' => []]);

        /* MODIF 2026-05-07 [tests-validators] : init [] (intelephense by-ref). */
        $capturedInsert = [];
        $this->repo->expects($this->once())
            ->method('insertLogger')
            ->with($this->callback(function (array $data) use (&$capturedInsert) {
                $capturedInsert = $data;
                return true;
            }))
            ->willReturn(['code' => 200]);

        $result = $this->controller->BatchCollecteLogger(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(200, $result['code']);
        $this->assertSame(10, $result['historique']['logger_info']);
        $this->assertSame(5, $result['historique']['logger_warn']);
        $this->assertSame(2, $result['historique']['logger_error']);
        $this->assertSame(20, $result['historique']['logger_debug']);

        // Insert map contient les totaux + metadata
        $this->assertSame(10, $capturedInsert['logger_info']);
        $this->assertSame('auto', $capturedInsert['mode_collecte']);
        $this->assertSame('u', $capturedInsert['utilisateur_collecte']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $capturedInsert['date_enregistrement']);
    }

    public function testReturnsErrorWhenInsertFails(): void
    {
        $this->parameterBag->method('get')->willReturnMap([
            ['sonar.url', 'https://sonar.example.com'],
            ['track.logger.method', 'true'],
        ]);

        $this->client->method('httpSonarQube')->willReturn([
            'code' => 200, 'json' => ['paging' => ['total' => 1]],
        ]);
        $this->repo->method('deleteLoggerMavenKey')->willReturn(['code' => 200]);
        $this->repo->expects($this->once())
            ->method('insertLogger')
            ->willReturn(['code' => 500, 'erreur' => 'insert fail']);

        $result = $this->controller->BatchCollecteLogger(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(500, $result['code']);
    }
}
