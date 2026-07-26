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
use App\Repository\ActuatorInfoRepository;
use App\Repository\ActuatorRepository;
use App\Service\ClientService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/* MODIF 2026-07-23 : réécriture pour couvrir le nouveau contrat de
 * BatchCollecteActuatorInfo (remise à niveau du module Actuator) :
 *  - toutes les branches renvoient désormais une clé 'json' (array), jamais
 *    absente, destinée à être stockée telle quelle dans historique.actuator_info ;
 *  - seule l'erreur de recherche en base (DB Ma-Moulinette) porte 'fatal' => true ;
 *  - extraction effective des clés déclarées (ActuatorInfoRepository::findActuatorInfoById)
 *    par nœud JSON à points (ex. app.version) ;
 *  - l'URL enregistrée est appelée TELLE QUELLE (plus de UrlBuilderService ici,
 *    qui dupliquait le suffixe /actuator/info + ajoutait un ?project= sans objet
 *    pour un endpoint Actuator — bug réel trouvé en test manuel) ;
 *  - une erreur HTTP se signale par l'ABSENCE de la clé 'json' dans le retour de
 *    ClientService::httpActuator (pas par un 'code' embarqué dans le JSON décodé,
 *    qui était du code mort : ClientService catch déjà ces cas en interne).
 */
#[AllowMockObjectsWithoutExpectations]
class BatchCollecteActuatorControllerTest extends TestCase
{
    private const MAVEN_KEY = 'fr.ma-moulinette:ma-moulinette';
    private const URL = 'http://app.example.com/actuator/info';
    private const ACTUATOR_ID = 7;

    /** @var EntityManagerInterface&MockObject */
    private MockObject $em;

    /** @var ActuatorRepository&MockObject */
    private MockObject $repository;

    /** @var ActuatorInfoRepository&MockObject */
    private MockObject $infoRepository;

    /** @var ClientService&MockObject */
    private MockObject $client;

    /** @var LoggerInterface&MockObject */
    private MockObject $logger;

    private BatchCollecteActuatorController $controller;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(ActuatorRepository::class);
        $this->infoRepository = $this->createMock(ActuatorInfoRepository::class);
        $this->client = $this->createMock(ClientService::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->em->expects($this->atLeastOnce())
            ->method('getRepository')
            ->with(Actuator::class)
            ->willReturn($this->repository);

        $this->controller = new BatchCollecteActuatorController(
            $this->em,
            $this->client,
            $this->infoRepository,
            $this->logger
        );
    }

    /**
     * Erreur de recherche du point d'accès en base (DB Ma-Moulinette elle-même) :
     * seul cas encore marqué 'fatal' => true (stoppe la collecte du projet).
     */
    #[DataProvider('repositoryErrorCodesProvider')]
    public function testBatchCollecteActuatorInfoReturnsFatalErrorWhenEndpointLookupFails(int $code): void
    {
        $this->repository->expects($this->once())
            ->method('findActuatorMavenKey')
            ->with(['maven_key' => self::MAVEN_KEY])
            ->willReturn(['code' => $code, 'erreur' => 'Some DB error']);

        $this->client->expects($this->never())->method('httpActuator');

        $result = $this->controller->BatchCollecteActuatorInfo(self::MAVEN_KEY);

        $this->assertSame($code, $result['code']);
        $this->assertSame('Some DB error', $result['erreur']);
        $this->assertSame([], $result['json']);
        $this->assertTrue($result['fatal']);
    }

    /**
     * @return array<string, array{0: int}>
     */
    public static function repositoryErrorCodesProvider(): array
    {
        return [
            'PG not null violation' => [23502],
            'PG unique violation'   => [23505],
            'HTTP 500'              => [500],
            'HTTP 503'              => [503],
        ];
    }

    public function testBatchCollecteActuatorInfoReturns404WithEmptyJsonWhenNoEndpointIsDefined(): void
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
        $this->assertSame([], $result['json']);
        $this->assertArrayNotHasKey('fatal', $result);
    }

    public function testBatchCollecteActuatorInfoCallsStoredUrlAsIs(): void
    {
        $this->repository->expects($this->once())
            ->method('findActuatorMavenKey')
            ->willReturn([
                'code' => 200,
                'user' => 'actuator-user',
                'password' => 'actuator-pass',
                'url' => self::URL,
                'id' => self::ACTUATOR_ID,
            ]);

        $dataJson = ['app' => ['version' => '2.0.0'], 'git' => ['branch' => 'main']];
        $this->client->expects($this->once())
            ->method('httpActuator')
            // MODIF 2026-07-23 : plus de suffixe/paramètre rajouté, l'URL stockée
            // (déjà complète) est appelée telle quelle.
            ->with(self::URL, 'actuator-user', 'actuator-pass')
            ->willReturn(['code' => 200, 'json' => $dataJson]);

        $this->infoRepository->method('findActuatorInfoById')->willReturn(['code' => 200, 'liste' => []]);

        $result = $this->controller->BatchCollecteActuatorInfo(self::MAVEN_KEY);

        $this->assertSame(200, $result['code']);
    }

    public function testBatchCollecteActuatorInfoExtractsRequestedKeysOnSuccess(): void
    {
        $this->repository->expects($this->once())
            ->method('findActuatorMavenKey')
            ->willReturn([
                'code' => 200,
                'user' => 'actuator-user',
                'password' => 'actuator-pass',
                'url' => self::URL,
                'id' => self::ACTUATOR_ID,
            ]);

        $dataJson = ['app' => ['version' => '2.0.0'], 'git' => ['branch' => 'main']];
        $this->client->expects($this->once())
            ->method('httpActuator')
            ->with(self::URL, 'actuator-user', 'actuator-pass')
            ->willReturn(['code' => 200, 'json' => $dataJson]);

        $this->infoRepository->expects($this->once())
            ->method('findActuatorInfoById')
            ->with(['actuator_id' => self::ACTUATOR_ID])
            ->willReturn([
                'code' => 200,
                'liste' => [
                    ['nom' => 'Version', 'cle' => 'app.version'],
                    ['nom' => 'Branche', 'cle' => 'git.branch'],
                    ['nom' => 'Absente', 'cle' => 'app.inconnu'],
                ],
            ]);

        $result = $this->controller->BatchCollecteActuatorInfo(self::MAVEN_KEY);

        $this->assertSame(200, $result['code']);
        $this->assertSame($dataJson, $result['dataJson']);

        $json = $result['json'];
        $this->assertSame(200, $json['code']);
        $this->assertArrayHasKey('date_extraction', $json);
        $this->assertSame('2.0.0', $json['app.version']);
        $this->assertSame('main', $json['git.branch']);
        $this->assertNull($json['app.inconnu']);
    }

    /**
     * ClientService::httpActuator() catch déjà les erreurs HTTP en interne et
     * renvoie {code, erreur} SANS clé 'json' — c'est ce signal (pas un 'code'
     * dans un JSON décodé, qui n'arrive jamais dans ce cas) qui doit déclencher
     * la construction du JSON d'échec.
     */
    #[DataProvider('httpErrorCodesProvider')]
    public function testBatchCollecteActuatorInfoBuildsFailureJsonWhenClientReturnsNoJson(int $code): void
    {
        $this->repository->expects($this->once())
            ->method('findActuatorMavenKey')
            ->willReturn([
                'code' => 200, 'user' => 'u', 'password' => 'p', 'url' => self::URL, 'id' => self::ACTUATOR_ID,
            ]);

        $this->client->expects($this->once())
            ->method('httpActuator')
            ->willReturn(['code' => $code, 'erreur' => "HTTP $code error"]);

        $this->infoRepository->expects($this->never())->method('findActuatorInfoById');

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Erreur HTTP'));

        $result = $this->controller->BatchCollecteActuatorInfo(self::MAVEN_KEY);

        $this->assertSame($code, $result['code']);
        $this->assertSame("HTTP $code error", $result['erreur']);
        $this->assertArrayNotHasKey('fatal', $result);
        $this->assertSame($code, $result['json']['code']);
        $this->assertStringContainsString((string) $code, $result['json']['message']);
        $this->assertArrayHasKey('date_extraction', $result['json']);
    }

    /**
     * @return array<int, array{0: int}>
     */
    public static function httpErrorCodesProvider(): array
    {
        return [
            [400], [401], [403], [404], [407], [414], [418], [422], [429],
            [500], [502], [503], [504], [505],
        ];
    }

    public function testBatchCollecteActuatorInfoBuildsFailureJsonWhenHttpClientThrows(): void
    {
        $this->repository->expects($this->once())
            ->method('findActuatorMavenKey')
            ->willReturn([
                'code' => 200, 'user' => 'u', 'password' => 'p', 'url' => self::URL, 'id' => self::ACTUATOR_ID,
            ]);

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
        $this->assertArrayNotHasKey('fatal', $result);
        $this->assertSame(500, $result['json']['code']);
        $this->assertStringContainsString('Connection refused', $result['json']['message']);
    }

    public function testBatchCollecteActuatorInfoIgnoresKeysWithBlankCle(): void
    {
        $this->repository->expects($this->once())
            ->method('findActuatorMavenKey')
            ->willReturn([
                'code' => 200, 'user' => 'u', 'password' => 'p', 'url' => self::URL, 'id' => self::ACTUATOR_ID,
            ]);

        $dataJson = ['app' => ['version' => '2.0.0']];
        $this->client->expects($this->once())
            ->method('httpActuator')
            ->willReturn(['code' => 200, 'json' => $dataJson]);

        $this->infoRepository->method('findActuatorInfoById')->willReturn([
            'code' => 200,
            'liste' => [['nom' => 'Sans clé', 'cle' => null]],
        ]);

        $result = $this->controller->BatchCollecteActuatorInfo(self::MAVEN_KEY);

        $this->assertSame(200, $result['code']);
        $this->assertSame(
            ['date_extraction', 'code', 'message'],
            array_keys($result['json'])
        );
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
