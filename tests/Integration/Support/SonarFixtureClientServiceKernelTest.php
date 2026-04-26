<?php

declare(strict_types=1);

namespace App\Tests\Integration\Support;

use App\Service\ClientService;
use App\Tests\Support\SonarFixtureClientService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Verifie le wiring de `SonarFixtureClientService` (fake ClientService) dans
 * l'env test via `config/services_test.yaml`, et que chaque endpoint SonarQube
 * documente est bien mappe sur une fixture.
 */
class SonarFixtureClientServiceKernelTest extends KernelTestCase
{
    private const SONAR_BASE = 'http://localhost:9000';

    private ClientService $client;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->client = static::getContainer()->get(ClientService::class);
    }

    public function testContainerResolvesClientServiceToTestDouble(): void
    {
        // Dans l'env test, services_test.yaml substitue ClientService par le fake.
        $this->assertInstanceOf(SonarFixtureClientService::class, $this->client);
    }

    public function testIssuesSearchReturnsFixturePayload(): void
    {
        $result = $this->client->httpSonarQube(
            self::SONAR_BASE . '/api/issues/search?componentKeys=tetris:TetrisGame&ps=100'
        );

        $this->assertSame(200, $result['code']);
        $this->assertIsArray($result['json']);
        $this->assertArrayHasKey('issues', $result['json']);
        $this->assertGreaterThan(0, count($result['json']['issues']));
    }

    public function testHotspotsSearchReturnsFixturePayload(): void
    {
        $result = $this->client->httpSonarQube(
            self::SONAR_BASE . '/api/hotspots/search?projectKey=tetris:TetrisGame'
        );

        $this->assertSame(200, $result['code']);
        $this->assertArrayHasKey('hotspots', $result['json']);
    }

    public function testHotspotsShowReturnsFixturePayload(): void
    {
        $result = $this->client->httpSonarQube(
            self::SONAR_BASE . '/api/hotspots/show?hotspot=abc-123'
        );

        $this->assertSame(200, $result['code']);
        $this->assertArrayHasKey('rule', $result['json']);
    }

    public function testProjectAnalysesSearchReturnsFixturePayload(): void
    {
        $result = $this->client->httpSonarQube(
            self::SONAR_BASE . '/api/project_analyses/search?project=tetris:TetrisGame&p=1&ps=500'
        );

        $this->assertSame(200, $result['code']);
        $this->assertArrayHasKey('analyses', $result['json']);
    }

    public function testComponentsAppReturnsFixturePayload(): void
    {
        $result = $this->client->httpSonarQube(
            self::SONAR_BASE . '/api/components/app?component=tetris:TetrisGame'
        );

        $this->assertSame(200, $result['code']);
        $this->assertSame('tetris:TetrisGame', $result['json']['key']);
    }

    public function testMeasuresComponentReturnsFixturePayload(): void
    {
        $result = $this->client->httpSonarQube(
            self::SONAR_BASE . '/api/measures/component?component=tetris:TetrisGame&metricKeys=bugs,coverage'
        );

        $this->assertSame(200, $result['code']);
        $this->assertArrayHasKey('component', $result['json']);
        $this->assertArrayHasKey('measures', $result['json']['component']);
    }

    public function testUnmappedUrlReturns404(): void
    {
        $result = $this->client->httpSonarQube(
            self::SONAR_BASE . '/api/qualitygates/list'
        );

        $this->assertSame(404, $result['code']);
        $this->assertNull($result['json']);
        $this->assertStringContainsString('non mappee', $result['erreur']);
    }
}
