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

namespace App\Tests\Unit\Service\CommandRebuildHistorique;

use App\Exception\SonarApiException;
use App\Service\ClientService;
use App\Service\CommandRebuildHistorique\BuildMapHistoryService;
use App\Service\CommandRebuildHistorique\SonarMetricsFetcherService;
use App\Service\UrlBuilderService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SonarMetricsFetcherServiceTest extends TestCase
{
    private const SONAR_URL = 'https://sonar.example.com';
    private const SONAR_VERSION = '10';
    private const METRIC_KEYS = 'bugs,vulnerabilities,code_smells';
    private const BUILT_URL = 'https://sonar.example.com/api/measures/component?component=foo&...';

    /** @var ClientService&MockObject */
    private MockObject $client;

    /** @var UrlBuilderService&MockObject */
    private MockObject $urlBuilder;

    /** @var BuildMapHistoryService&MockObject */
    private MockObject $buildMap;

    /** @var ParameterBagInterface&MockObject */
    private MockObject $params;

    /** @var LoggerInterface&MockObject */
    private MockObject $logger;

    private SonarMetricsFetcherService $service;

    protected function setUp(): void
    {
        $this->client = $this->createMock(ClientService::class);
        $this->urlBuilder = $this->createMock(UrlBuilderService::class);
        $this->buildMap = $this->createMock(BuildMapHistoryService::class);
        $this->params = $this->createMock(ParameterBagInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        // sonar.version et sonar.url sont lus à chaque appel — on prépare des stubs souples
        $this->params->expects($this->atLeastOnce())
            ->method('get')
            ->willReturnMap([
                ['sonar.version', self::SONAR_VERSION],
                ['sonar.url', self::SONAR_URL],
            ]);

        $this->buildMap->expects($this->once())
            ->method('metricsKey')
            ->with(10) // SONAR_VERSION cast en int
            ->willReturn(self::METRIC_KEYS);

        $this->urlBuilder->expects($this->once())
            ->method('build')
            ->with(
                self::SONAR_URL,
                '/api/measures/component',
                [
                    'component' => 'fr.ma-moulinette:ma-moulinette',
                    'metricKeys' => self::METRIC_KEYS,
                    'analysisId' => 'AY123',
                ]
            )
            ->willReturn(self::BUILT_URL);

        $this->service = new SonarMetricsFetcherService(
            $this->client,
            $this->urlBuilder,
            $this->buildMap,
            $this->params,
            $this->logger
        );
    }

    public function testFetchMetricsReturnsMetricMapFromComponentMeasures(): void
    {
        $this->client->expects($this->once())
            ->method('httpSonarQube')
            ->with(self::BUILT_URL)
            ->willReturn([
                'code' => 200,
                'json' => [
                    'components' => [], // clé présente → pas de warning
                    'component' => [
                        'measures' => [
                            ['metric' => 'bugs', 'value' => '3'],
                            ['metric' => 'vulnerabilities', 'value' => '0'],
                            ['metric' => 'code_smells', 'value' => '42'],
                        ],
                    ],
                ],
            ]);

        $this->logger->expects($this->never())->method('error');
        $this->logger->expects($this->never())->method('warning');
        $this->logger->expects($this->never())->method('info');

        $this->assertSame(
            [
                'bugs' => '3',
                'vulnerabilities' => '0',
                'code_smells' => '42',
            ],
            $this->service->fetchMetrics('fr.ma-moulinette:ma-moulinette', 'AY123')
        );
    }

    public function testFetchMetricsThrowsSonarApiExceptionWhenHttpCodeIsNot200(): void
    {
        $this->client->expects($this->once())
            ->method('httpSonarQube')
            ->willReturn(['code' => 503, 'json' => []]);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                '[SonarMetricsFetcher] ❌ Erreur lors de l\'appel API SonarQube',
                $this->callback(fn (array $ctx) => $ctx['status'] === 503)
            );

        try {
            $this->service->fetchMetrics('fr.ma-moulinette:ma-moulinette', 'AY123');
            $this->fail('Expected SonarApiException was not thrown');
        } catch (SonarApiException $e) {
            $this->assertStringContainsString('HTTP 503', $e->getMessage());
            $this->assertSame(['code' => 503, 'json' => []], $e->getResponse());
        }
    }

    public function testFetchMetricsLogsWarningWhenResponseHasNoComponentsKey(): void
    {
        $this->client->expects($this->once())
            ->method('httpSonarQube')
            ->willReturn([
                'code' => 200,
                'json' => [
                    // pas de clé 'components' → déclenche le warning
                    'component' => [
                        'measures' => [
                            ['metric' => 'bugs', 'value' => '1'],
                        ],
                    ],
                ],
            ]);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                $this->stringContains('Aucune metrics trouvée'),
                $this->callback(fn (array $ctx) => $ctx['project_key'] === 'fr.ma-moulinette:ma-moulinette')
            );
        $this->logger->expects($this->never())->method('error');

        $result = $this->service->fetchMetrics('fr.ma-moulinette:ma-moulinette', 'AY123');

        $this->assertSame(['bugs' => '1'], $result);
    }

    public function testFetchMetricsLogsInfoAndReturnsEmptyMapWhenNoMeasures(): void
    {
        $this->client->expects($this->once())
            ->method('httpSonarQube')
            ->willReturn([
                'code' => 200,
                'json' => [
                    'components' => [],
                    'component' => ['measures' => []],
                ],
            ]);

        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                '[SonarMetricsFetcher] Analyse sans métriques',
                $this->callback(fn (array $ctx) => $ctx['project_key'] === 'fr.ma-moulinette:ma-moulinette')
            );

        $this->assertSame([], $this->service->fetchMetrics('fr.ma-moulinette:ma-moulinette', 'AY123'));
    }

    public function testFetchMetricsDefaultsMeasureValueToZeroWhenMissing(): void
    {
        $this->client->expects($this->once())
            ->method('httpSonarQube')
            ->willReturn([
                'code' => 200,
                'json' => [
                    'components' => [],
                    'component' => [
                        'measures' => [
                            ['metric' => 'bugs'], // pas de clé 'value'
                            ['metric' => 'vulnerabilities', 'value' => '7'],
                        ],
                    ],
                ],
            ]);

        $this->logger->expects($this->never())->method('error');
        $this->logger->expects($this->never())->method('warning');
        $this->logger->expects($this->never())->method('info');

        $this->assertSame(
            ['bugs' => 0, 'vulnerabilities' => '7'],
            $this->service->fetchMetrics('fr.ma-moulinette:ma-moulinette', 'AY123')
        );
    }
}
