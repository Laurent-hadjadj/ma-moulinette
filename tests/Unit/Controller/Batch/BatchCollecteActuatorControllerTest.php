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

use App\Controller\Batch\BatchCollecteActuatorController;
use App\Entity\Actuator;
use App\Repository\ActuatorRepository;
use App\Service\ClientService;
use App\Service\UrlBuilderService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[AllowMockObjectsWithoutExpectations]
class BatchCollecteActuatorControllerTest extends TestCase
{
    private const MAVEN_KEY = 'fr.ma-moulinette:ma-moulinette';
    private const BUILT_URL = 'http://app.example.com/actuator/info?project=fr.ma-moulinette:ma-moulinette';

    /** @var EntityManagerInterface&MockObject */
    private MockObject $em;

    /** @var ActuatorRepository&MockObject */
    private MockObject $repository;

    /** @var ClientService&MockObject */
    private MockObject $client;

    /** @var UrlBuilderService&MockObject */
    private MockObject $urlBuilder;

    /** @var LoggerInterface&MockObject */
    private MockObject $logger;

    private BatchCollecteActuatorController $controller;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(ActuatorRepository::class);
        $this->client = $this->createMock(ClientService::class);
        $this->urlBuilder = $this->createMock(UrlBuilderService::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->em->expects($this->atLeastOnce())
            ->method('getRepository')
            ->with(Actuator::class)
            ->willReturn($this->repository);

        $this->controller = new BatchCollecteActuatorController(
            $this->em,
            $this->client,
            $this->urlBuilder,
            $this->logger
        );
    }

    /**
     * Quand la recherche de l'endpoint Actuator renvoie un code en erreur DB/HTTP,
     * on propage l'erreur telle quelle (pas d'appel httpActuator).
     */
    #[DataProvider('repositoryErrorCodesProvider')]
    public function testBatchCollecteActuatorInfoReturnsErrorWhenEndpointLookupFails(int $code): void
    {
        $this->repository->expects($this->once())
            ->method('findActuatorMavenKey')
            ->with(['maven_key' => self::MAVEN_KEY])
            ->willReturn(['code' => $code, 'erreur' => 'Some DB error']);

        $this->client->expects($this->never())->method('httpActuator');
        $this->urlBuilder->expects($this->never())->method('build');

        $this->assertSame(
            ['code' => $code, 'erreur' => 'Some DB error'],
            $this->controller->BatchCollecteActuatorInfo(self::MAVEN_KEY)
        );
    }

    public static function repositoryErrorCodesProvider(): array
    {
        return [
            'PG not null violation' => [23502],
            'PG unique violation'   => [23505],
            'HTTP 500'              => [500],
            'HTTP 503'              => [503],
        ];
    }

    public function testBatchCollecteActuatorInfoReturns404WhenNoEndpointIsDefined(): void
    {
        $this->repository->expects($this->once())
            ->method('findActuatorMavenKey')
            ->willReturn(['code' => 404]);

        $this->client->expects($this->never())->method('httpActuator');

        $this->logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('Aucun endpoint'));

        $result = $this->controller->BatchCollecteActuatorInfo(self::MAVEN_KEY);

        $this->assertSame(404, $result['code']);
        $this->assertSame('warning', $result['type']);
        $this->assertStringContainsString("point-d'accès", $result['message']);
    }

    public function testBatchCollecteActuatorInfoReturnsDataJsonOnSuccess(): void
    {
        /* MODIF 2026-05-08 : ajout de la cle 'code' (happy path).
         * Why: BatchCollecteActuatorInfo:74 fait `$actuatorEndpoint['code'] === 404` sans coalesce.
         * Sans cette cle, warning "Undefined array key 'code'".
         */
        $this->repository->expects($this->once())
            ->method('findActuatorMavenKey')
            ->willReturn([
                'code' => 200,
                'user' => 'actuator-user',
                'password' => 'actuator-pass',
                'url' => 'http://app.example.com',
            ]);

        $this->urlBuilder->expects($this->once())
            ->method('build')
            ->with(
                'http://app.example.com',
                'actuator/info',
                ['project' => self::MAVEN_KEY]
            )
            ->willReturn(self::BUILT_URL);

        $dataJson = ['build' => ['version' => '2.0.0'], 'git' => ['branch' => 'main']];
        $this->client->expects($this->once())
            ->method('httpActuator')
            ->with(self::BUILT_URL, 'actuator-user', 'actuator-pass')
            ->willReturn(['code' => 200, 'json' => $dataJson]);

        $result = $this->controller->BatchCollecteActuatorInfo(self::MAVEN_KEY);

        $this->assertSame(200, $result['code']);
        $this->assertSame($dataJson, $result['dataJson']);
        $this->assertStringContainsString('collecte', $result['message']);
    }

    #[DataProvider('httpErrorCodesProvider')]
    public function testBatchCollecteActuatorInfoPropagatesHttpErrorWhenJsonPayloadCarriesErrorCode(int $code): void
    {
        $this->repository->expects($this->once())
            ->method('findActuatorMavenKey')
            ->willReturn([
                'code' => 200, 'user' => 'u', 'password' => 'p', 'url' => 'http://app.example.com',
            ]);

        $this->urlBuilder->method('build')->willReturn(self::BUILT_URL);

        $this->client->expects($this->once())
            ->method('httpActuator')
            ->willReturn([
                'code' => 200,
                'json' => ['code' => $code, 'erreur' => "HTTP $code error"],
            ]);

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Erreur HTTP'));

        $this->assertSame(
            ['code' => $code, 'erreur' => "HTTP $code error"],
            $this->controller->BatchCollecteActuatorInfo(self::MAVEN_KEY)
        );
    }

    public static function httpErrorCodesProvider(): array
    {
        return [
            [400], [401], [403], [404], [407], [414], [418], [422], [429],
            [500], [502], [503], [504], [505],
        ];
    }

    public function testBatchCollecteActuatorInfoCatchesExceptionFromHttpClient(): void
    {
        $this->repository->expects($this->once())
            ->method('findActuatorMavenKey')
            ->willReturn([
                'code' => 200, 'user' => 'u', 'password' => 'p', 'url' => 'http://app.example.com',
            ]);

        $this->urlBuilder->method('build')->willReturn(self::BUILT_URL);

        $this->client->expects($this->once())
            ->method('httpActuator')
            ->willThrowException(new \RuntimeException('Connection refused'));

        $this->logger->expects($this->once())
            ->method('critical')
            ->with($this->stringContains('Exception lors de l'));

        $result = $this->controller->BatchCollecteActuatorInfo(self::MAVEN_KEY);

        $this->assertSame(500, $result['code']);
        $this->assertSame('error', $result['type']);
        $this->assertStringContainsString('Connection refused', $result['erreur'][0]);
    }

    public function testBatchCollecteActuatorInfoSanitizesMavenKey(): void
    {
        // maven_key avec balise HTML → htmlspecialchars avec ENT_QUOTES
        $rawKey = 'fr.ma-moulinette:<script>';
        $escapedKey = 'fr.ma-moulinette:&lt;script&gt;';

        $this->repository->expects($this->once())
            ->method('findActuatorMavenKey')
            ->with(['maven_key' => $escapedKey])
            ->willReturn(['code' => 404]);

        $this->controller->BatchCollecteActuatorInfo($rawKey);
    }
}
