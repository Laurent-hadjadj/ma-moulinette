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

namespace App\Tests\Unit\Service;

use App\Service\{ClientService, GetSonarVersionService, UrlBuilderService};
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[AllowMockObjectsWithoutExpectations]
class GetSonarVersionServiceTest extends TestCase
{
    /** @var ClientService&MockObject */        private MockObject $client;
    /** @var UrlBuilderService&MockObject */    private MockObject $urlBuilder;
    /** @var LoggerInterface&MockObject */       private MockObject $logger;
    /** @var ParameterBagInterface&MockObject */ private MockObject $parameterBag;
    /** @var CacheInterface&MockObject */        private MockObject $cache;

    private GetSonarVersionService $service;

    protected function setUp(): void
    {
        $this->client = $this->createMock(ClientService::class);
        $this->urlBuilder = $this->createMock(UrlBuilderService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);
        $this->cache = $this->createMock(CacheInterface::class);

        $this->urlBuilder->method('build')->willReturn('https://sonar.example.com/api/server/version');
        $this->parameterBag->method('get')->willReturnMap([
            ['sonar.url', 'https://sonar.example.com'],
        ]);

        // Le cache délègue au callback (= on exécute la logique métier sans cacher).
        $this->cache->method('get')->willReturnCallback(function (string $key, callable $callback) {
            $item = $this->createMock(ItemInterface::class);
            return $callback($item);
        });

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnMap([['parameter_bag', true]]);
        $container->method('get')->willReturnMap([['parameter_bag', 1, $this->parameterBag]]);

        $this->service = new GetSonarVersionService($this->client, $this->urlBuilder, $this->logger, $this->cache);
        $this->service->setContainer($container);
    }

    public function testGetSonarQubeVersionReturnsPlainTextVersion(): void
    {
        // L'API /api/server/version de SonarQube renvoie du texte brut, pas du
        // JSON — ClientService::httpSonarQube() l'encapsule dans ['texte' => ...].
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 200,
            'json' => ['texte' => '10.4.1.88267'],
        ]);

        $result = $this->service->getSonarQubeVersion();

        $this->assertSame('10.4.1.88267', $result);
    }

    public function testGetSonarQubeVersionReturnsErrorArrayOnHttpFailure(): void
    {
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 503,
            'erreur' => 'Service indisponible',
        ]);

        $result = $this->service->getSonarQubeVersion();

        $this->assertIsArray($result);
        $this->assertSame(503, $result['code']);
        $this->assertSame('Service indisponible', $result['erreur']);
    }

    public function testGetSonarQubeVersionReturnsErrorArrayOnException(): void
    {
        $this->client->method('httpSonarQube')->willThrowException(new \RuntimeException('boom'));

        $result = $this->service->getSonarQubeVersion();

        $this->assertIsArray($result);
        $this->assertSame(500, $result['code']);
        $this->assertSame('boom', $result['erreur']);
    }

    #[DataProvider('provideVersionsUsingLegacyComponentKeys')]
    public function testComponentKeyParamReturnsComponentKeysForLegacyServers(string $version): void
    {
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 200,
            'json' => ['texte' => $version],
        ]);

        $this->assertSame('componentKeys', $this->service->componentKeyParam());
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function provideVersionsUsingLegacyComponentKeys(): iterable
    {
        yield 'version 8' => ['8.9.9.44547'];
        yield 'version 9' => ['9.9.8.12345'];
    }

    #[DataProvider('provideVersionsUsingComponents')]
    public function testComponentKeyParamReturnsComponentsForRecentServers(string $version): void
    {
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 200,
            'json' => ['texte' => $version],
        ]);

        $this->assertSame('components', $this->service->componentKeyParam());
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function provideVersionsUsingComponents(): iterable
    {
        yield 'version 10' => ['10.4.1.88267'];
        yield 'version 2024' => ['2024.12.0.1234'];
        yield 'version 2026 (format court)' => ['26.9.0.129388'];
    }

    public function testComponentKeyParamDefaultsToComponentsWhenVersionUnknown(): void
    {
        // Cas nominal = components : on ne bascule sur l'ancienne clé que sur
        // confirmation positive d'un serveur 8/9, jamais par défaut sur un échec.
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 503,
            'erreur' => 'Service indisponible',
        ]);

        $this->assertSame('components', $this->service->componentKeyParam());
    }

    #[DataProvider('provideVersionsUsingLegacyComponentKeys')]
    public function testHotspotProjectParamReturnsProjectKeyForLegacyServers(string $version): void
    {
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 200,
            'json' => ['texte' => $version],
        ]);

        $this->assertSame('projectKey', $this->service->hotspotProjectParam());
    }

    #[DataProvider('provideVersionsUsingComponents')]
    public function testHotspotProjectParamReturnsProjectForRecentServers(string $version): void
    {
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 200,
            'json' => ['texte' => $version],
        ]);

        $this->assertSame('project', $this->service->hotspotProjectParam());
    }

    public function testHotspotProjectParamReturnsProjectFor2026Dot10(): void
    {
        // MODIF 2026-10-16 : projectKey supprimé (pas juste déprécié) depuis la
        // 2026.10, confirmé en direct (projectKey => HTTP 400, project => 200).
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 200,
            'json' => ['texte' => '26.10.0.133078'],
        ]);

        $this->assertSame('project', $this->service->hotspotProjectParam());
    }

    public function testHotspotProjectParamDefaultsToProjectWhenVersionUnknown(): void
    {
        // Même politique que componentKeyParam() : cas nominal = paramètre récent.
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 503,
            'erreur' => 'Service indisponible',
        ]);

        $this->assertSame('project', $this->service->hotspotProjectParam());
    }

    #[DataProvider('provideVersionsUsingLegacyStatuses')]
    public function testIssueStatusParamReturnsLegacyStatusesForOldServers(string $version): void
    {
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 200,
            'json' => ['texte' => $version],
        ]);

        $this->assertSame(
            ['param' => 'statuses', 'value' => 'OPEN,CONFIRMED,REOPENED'],
            $this->service->issueStatusParam()
        );
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function provideVersionsUsingLegacyStatuses(): iterable
    {
        yield 'version 8' => ['8.9.9.44547'];
        yield 'version 9' => ['9.9.8.12345'];
        // Contrairement à componentKeyParam(), la version 10 reste sur l'ancien modèle
        // ici : statuses n'est déprécié que depuis la 10.4, pas dès la 10.0.
        yield 'version 10' => ['10.4.1.88267'];
    }

    #[DataProvider('provideVersionsUsingIssueStatuses')]
    public function testIssueStatusParamReturnsIssueStatusesForRecentServers(string $version): void
    {
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 200,
            'json' => ['texte' => $version],
        ]);

        $this->assertSame(
            ['param' => 'issueStatuses', 'value' => 'OPEN,CONFIRMED,ACCEPTED'],
            $this->service->issueStatusParam()
        );
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function provideVersionsUsingIssueStatuses(): iterable
    {
        yield 'version 2024' => ['2024.12.0.1234'];
        yield 'version 2026 (format court)' => ['26.9.0.129388'];
    }

    public function testIssueStatusParamDefaultsToIssueStatusesWhenVersionUnknown(): void
    {
        // Même politique que componentKeyParam() : cas nominal = modèle récent.
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 503,
            'erreur' => 'Service indisponible',
        ]);

        $this->assertSame(
            ['param' => 'issueStatuses', 'value' => 'OPEN,CONFIRMED,ACCEPTED'],
            $this->service->issueStatusParam()
        );
    }
}
