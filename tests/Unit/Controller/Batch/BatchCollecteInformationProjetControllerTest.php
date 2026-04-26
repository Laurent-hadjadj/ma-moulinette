<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Batch;

use App\Controller\Batch\BatchCollecteInformationProjetController;
use App\Entity\InformationProjet;
use App\Repository\InformationProjetRepository;
use App\Service\ClientService;
use App\Service\IsValideMavenKey;
use App\Service\UrlBuilderService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AllowMockObjectsWithoutExpectations]
class BatchCollecteInformationProjetControllerTest extends TestCase
{
    private const MAVEN_KEY = 'com.acme:app';

    /** @var EntityManagerInterface&MockObject */      private MockObject $em;
    /** @var ClientService&MockObject */               private MockObject $client;
    /** @var UrlBuilderService&MockObject */           private MockObject $urlBuilder;
    /** @var IsValideMavenKey&MockObject */            private MockObject $isValidMavenKey;
    /** @var LoggerInterface&MockObject */             private MockObject $logger;
    /** @var InformationProjetRepository&MockObject */ private MockObject $repo;
    /** @var ParameterBagInterface&MockObject */       private MockObject $parameterBag;

    private BatchCollecteInformationProjetController $controller;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->client = $this->createMock(ClientService::class);
        $this->urlBuilder = $this->createMock(UrlBuilderService::class);
        $this->isValidMavenKey = $this->createMock(IsValideMavenKey::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->repo = $this->createMock(InformationProjetRepository::class);
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);

        $this->em->method('getRepository')
            ->willReturn($this->repo);

        $this->urlBuilder->method('build')->willReturn('https://sonar/api/...');
        $this->parameterBag->method('get')->willReturn('https://sonar.example.com');

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnMap([['parameter_bag', true]]);
        $container->method('get')->willReturnMap([['parameter_bag', 1, $this->parameterBag]]);

        $this->controller = new BatchCollecteInformationProjetController(
            $this->em, $this->isValidMavenKey, $this->client, $this->urlBuilder, $this->logger
        );
        $this->controller->setContainer($container);
    }

    /* ---------------- calculRepartitionProjet ---------------- */

    public function testCalculRepartitionWithEmptyArray(): void
    {
        $r = $this->controller->calculRepartitionProjet([]);
        $this->assertSame(['total' => 0, 'release' => 0, 'snapshot' => 0, 'autre' => 0], $r);
    }

    public function testCalculRepartitionWithNonArrayInput(): void
    {
        $r = $this->controller->calculRepartitionProjet('oops');
        $this->assertSame(['total' => 0, 'release' => 0, 'snapshot' => 0, 'autre' => 0], $r);
    }

    public function testCalculRepartitionCountsReleaseSnapshotAndAutre(): void
    {
        $analyses = [
            ['projectVersion' => '1.0-RELEASE'],
            ['projectVersion' => '1.1-SNAPSHOT'],
            ['projectVersion' => '1.2'],
            ['projectVersion' => '2.0-release'],   // case-insensitive
            ['nodate' => 'skip'],                  // no projectVersion → ignored
        ];

        $r = $this->controller->calculRepartitionProjet($analyses);

        $this->assertSame(4, $r['total']);
        $this->assertSame(2, $r['release']);
        $this->assertSame(1, $r['snapshot']);
        $this->assertSame(1, $r['autre']);
    }

    /* ---------------- controlVersionProjet ---------------- */

    public function testControlVersionProjetFoundOnSonarAndInBase(): void
    {
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 200, 'json' => ['analyses' => [['key' => 'k1', 'projectVersion' => '1.0', 'date' => '2026-01-01']]]
        ]);
        $this->isValidMavenKey->method('isValideInformation')->willReturn(['code' => 200, 'request' => ['x' => 1]]);
        $this->isValidMavenKey->method('isValideHistorique')->willReturn(['code' => 200, 'request' => ['y' => 2]]);

        $r = $this->controller->controlVersionProjet(self::MAVEN_KEY);

        $this->assertSame(200, $r['code']);
        $this->assertSame(['x' => 1], $r['data-baseInformation']);
    }

    public function testControlVersionProjetFoundOnSonarButNotInBase(): void
    {
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 200, 'json' => ['analyses' => [['key' => 'k1']]]
        ]);
        $this->isValidMavenKey->method('isValideInformation')->willReturn(['code' => 404]);
        $this->isValidMavenKey->method('isValideHistorique')->willReturn(['code' => 404]);

        $r = $this->controller->controlVersionProjet(self::MAVEN_KEY);

        $this->assertSame(202, $r['code']);
        $this->assertStringContainsString('sur le serveur', $r['message']);
        $this->assertStringContainsString('pas en base', $r['message']);
    }

    public function testControlVersionProjetReturns401WhenUnauthorized(): void
    {
        $this->client->method('httpSonarQube')->willReturn(['code' => 401, 'erreur' => 'unauthorized']);
        $this->isValidMavenKey->method('isValideInformation')->willReturn(['code' => 404]);
        $this->isValidMavenKey->method('isValideHistorique')->willReturn(['code' => 404]);

        $r = $this->controller->controlVersionProjet(self::MAVEN_KEY);

        $this->assertSame(401, $r['code']);
    }

    public function testControlVersionProjetReturns404WhenProjectNotFound(): void
    {
        $this->client->method('httpSonarQube')->willReturn(['code' => 404, 'erreur' => 'not found']);
        $this->isValidMavenKey->method('isValideInformation')->willReturn(['code' => 404]);
        $this->isValidMavenKey->method('isValideHistorique')->willReturn(['code' => 404]);

        $r = $this->controller->controlVersionProjet(self::MAVEN_KEY);

        $this->assertSame(404, $r['code']);
    }

    public function testControlVersionProjetReturns503WhenServerUnavailable(): void
    {
        $this->client->method('httpSonarQube')->willReturn(['code' => 503, 'erreur' => 'down']);
        $this->isValidMavenKey->method('isValideInformation')->willReturn(['code' => 404]);
        $this->isValidMavenKey->method('isValideHistorique')->willReturn(['code' => 404]);

        $r = $this->controller->controlVersionProjet(self::MAVEN_KEY);

        $this->assertSame(503, $r['code']);
    }

    public function testControlVersionProjetReturns500WhenUnexpected(): void
    {
        // No json (isFound = false) but no matching error code (500, for example)
        $this->client->method('httpSonarQube')->willReturn(['code' => 418, 'erreur' => 'teapot']);
        $this->isValidMavenKey->method('isValideInformation')->willReturn(['code' => 500]);
        $this->isValidMavenKey->method('isValideHistorique')->willReturn(['code' => 500]);

        $r = $this->controller->controlVersionProjet(self::MAVEN_KEY);

        $this->assertSame(500, $r['code']);
    }

    /* ---------------- batchInformationVersion ---------------- */

    public function testBatchInformationVersionReturnsErrorOnRepoFailure(): void
    {
        $this->repo->expects($this->once())
            ->method('selectInformationProjetVersion')
            ->willReturn(['code' => 500, 'erreur' => 'db down']);

        $r = $this->controller->batchInformationVersion(self::MAVEN_KEY);

        $this->assertSame(500, $r['code']);
        $this->assertSame('db down', $r['erreur']);
    }

    public function testBatchInformationVersionReturnsInfoOnSuccess(): void
    {
        $this->repo->expects($this->once())
            ->method('selectInformationProjetVersion')
            ->willReturn([
                'code' => 200,
                'info' => [[
                    'analyse_key' => 'K1',
                    'version_release_sonar' => 3,
                    'version_snapshot_sonar' => 2,
                    'version_autre_sonar' => 1,
                    'version' => '1.0.0',
                    'date' => '2026-04-01 10:00:00',
                ]]
            ]);

        $r = $this->controller->batchInformationVersion(self::MAVEN_KEY);

        $this->assertSame('K1', $r['analyse_key']);
        $this->assertSame(3, $r['release']);
        $this->assertSame('1.0.0', $r['projet_version']);
    }

    public function testBatchInformationVersionUsesDefaultsOnEmptyInfo(): void
    {
        $this->repo->expects($this->once())
            ->method('selectInformationProjetVersion')
            ->willReturn(['code' => 200, 'info' => []]);

        $r = $this->controller->batchInformationVersion(self::MAVEN_KEY);

        $this->assertSame('inconnu', $r['analyse_key']);
        $this->assertSame(0, $r['release']);
        $this->assertSame('inconnu', $r['projet_version']);
    }

    /* ---------------- batchCollecteInformation ---------------- */

    public function testBatchCollecteReturnsEarlyWhenControlFailsWith401(): void
    {
        $this->client->method('httpSonarQube')->willReturn(['code' => 401, 'erreur' => 'auth']);
        $this->isValidMavenKey->method('isValideInformation')->willReturn(['code' => 404]);
        $this->isValidMavenKey->method('isValideHistorique')->willReturn(['code' => 404]);

        $this->repo->expects($this->never())->method('deleteInformationProjetMavenKey');
        $this->repo->expects($this->never())->method('insertInformationProjet');

        $r = $this->controller->batchCollecteInformation(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(401, $r['code']);
    }

    public function testBatchCollecteSkipsWhenKeyMatchesAndNotCollecteMode(): void
    {
        // Project is on Sonar AND in base, mode != COLLECTE → comparison branch
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 200,
            'json' => ['analyses' => [['key' => 'SAME-KEY', 'projectVersion' => '1.0', 'date' => '2026-01-01']]]
        ]);
        $this->isValidMavenKey->method('isValideInformation')->willReturn(['code' => 200, 'request' => []]);
        $this->isValidMavenKey->method('isValideHistorique')->willReturn([
            'code' => 200,
            'request' => [
                'analyse_key' => 'SAME-KEY',
                'version' => '1.0',
                'date_version' => '2026-01-01',
                'name' => 'App',
            ],
        ]);

        $this->repo->expects($this->never())->method('deleteInformationProjetMavenKey');
        $this->repo->expects($this->never())->method('insertInformationProjet');

        $r = $this->controller->batchCollecteInformation(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(100, $r['code']);
        $this->assertSame('SAME-KEY', $r['historique']['SonarQube']['key-analyse']);
        $this->assertSame('SAME-KEY', $r['historique']['Locale']['key-analyse']);
    }

    public function testBatchCollecteReturnsErrorWhenDeleteFails(): void
    {
        // Sonar key differs from local → collecte continues → delete fails
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 200,
            'json' => ['analyses' => [
                ['key' => 'NEW-KEY', 'projectVersion' => '2.0-SNAPSHOT', 'date' => '2026-04-10'],
            ]]
        ]);
        $this->isValidMavenKey->method('isValideInformation')->willReturn(['code' => 200, 'request' => []]);
        $this->isValidMavenKey->method('isValideHistorique')->willReturn([
            'code' => 200,
            'request' => ['analyse_key' => 'OLD-KEY'],
        ]);

        $this->repo->expects($this->once())
            ->method('deleteInformationProjetMavenKey')
            ->willReturn(['code' => 500, 'erreur' => 'delete fail']);

        $this->repo->expects($this->never())->method('insertInformationProjet');

        $r = $this->controller->batchCollecteInformation(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(500, $r['code']);
        $this->assertSame('delete fail', $r['erreur']);
    }

    public function testBatchCollecteReturnsErrorWhenInsertFails(): void
    {
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 200,
            'json' => ['analyses' => [
                ['key' => 'NEW-KEY', 'projectVersion' => '2.0-RELEASE', 'date' => '2026-04-10'],
            ]]
        ]);
        $this->isValidMavenKey->method('isValideInformation')->willReturn(['code' => 200, 'request' => []]);
        $this->isValidMavenKey->method('isValideHistorique')->willReturn([
            'code' => 200,
            'request' => ['analyse_key' => 'OLD-KEY'],
        ]);

        $this->repo->method('deleteInformationProjetMavenKey')->willReturn(['code' => 200]);
        $this->repo->expects($this->once())
            ->method('insertInformationProjet')
            ->willReturn(['code' => 500, 'erreur' => 'insert fail']);

        $r = $this->controller->batchCollecteInformation(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(500, $r['code']);
        $this->assertSame('insert fail', $r['erreur']);
    }

    public function testBatchCollecteHappyPathReturnsHistorique(): void
    {
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 200,
            'json' => ['analyses' => [
                ['key' => 'A1', 'projectVersion' => '1.0-RELEASE', 'date' => '2026-04-10'],
                ['key' => 'A2', 'projectVersion' => '1.1-SNAPSHOT', 'date' => '2026-04-11'],
                ['key' => 'A3', 'projectVersion' => '1.2', 'date' => '2026-04-12'],
            ]]
        ]);
        $this->isValidMavenKey->method('isValideInformation')->willReturn(['code' => 200, 'request' => []]);
        $this->isValidMavenKey->method('isValideHistorique')->willReturn([
            'code' => 200,
            'request' => ['analyse_key' => 'OLD-KEY'],
        ]);

        $this->repo->method('deleteInformationProjetMavenKey')->willReturn(['code' => 200]);

        $capturedInsert = null;
        $this->repo->expects($this->once())
            ->method('insertInformationProjet')
            ->with($this->callback(function (array $map) use (&$capturedInsert) {
                $capturedInsert = $map;
                return true;
            }))
            ->willReturn(['code' => 200]);

        $this->repo->method('selectInformationProjetVersion')->willReturn([
            'code' => 200,
            'info' => [[
                'analyse_key' => 'A1',
                'version_release_sonar' => 1,
                'version_snapshot_sonar' => 1,
                'version_autre_sonar' => 1,
                'version' => '1.0-RELEASE',
                'date' => '2026-04-10',
            ]],
        ]);

        $r = $this->controller->batchCollecteInformation(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(200, $r['code']);
        // Verify map sent to insert
        $this->assertSame(self::MAVEN_KEY, $capturedInsert['maven_key']);
        $this->assertSame('A1', $capturedInsert['analyse_key']);
        $this->assertSame('RELEASE', $capturedInsert['type']);
        $this->assertSame(3, $capturedInsert['version_sonar']);
        $this->assertSame(1, $capturedInsert['version_release_sonar']);
        $this->assertSame(1, $capturedInsert['version_snapshot_sonar']);
        $this->assertSame(1, $capturedInsert['version_autre_sonar']);
        // Historique returned
        $this->assertSame('A1', $r['historique']['analyse_key']);
        $this->assertSame(3, $r['historique']['version_sonar']);
    }

    public function testBatchCollecteUsesNCTypeWhenNoDashInVersion(): void
    {
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 200,
            'json' => ['analyses' => [
                ['key' => 'NEW-KEY', 'projectVersion' => '1.0', 'date' => '2026-04-10'],
            ]]
        ]);
        $this->isValidMavenKey->method('isValideInformation')->willReturn(['code' => 200, 'request' => []]);
        $this->isValidMavenKey->method('isValideHistorique')->willReturn([
            'code' => 200,
            'request' => ['analyse_key' => 'OLD-KEY'],
        ]);

        $this->repo->method('deleteInformationProjetMavenKey')->willReturn(['code' => 200]);

        $capturedInsert = null;
        $this->repo->expects($this->once())
            ->method('insertInformationProjet')
            ->with($this->callback(function (array $map) use (&$capturedInsert) {
                $capturedInsert = $map;
                return true;
            }))
            ->willReturn(['code' => 200]);

        $this->repo->method('selectInformationProjetVersion')->willReturn([
            'code' => 200, 'info' => [],
        ]);

        $r = $this->controller->batchCollecteInformation(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(200, $r['code']);
        $this->assertSame('N.C', $capturedInsert['type']);
    }
}
