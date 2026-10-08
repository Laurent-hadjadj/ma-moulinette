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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AllowMockObjectsWithoutExpectations]
class GetSonarVersionServiceTest extends TestCase
{
    /** @var ClientService&MockObject */        private MockObject $client;
    /** @var UrlBuilderService&MockObject */    private MockObject $urlBuilder;
    /** @var LoggerInterface&MockObject */       private MockObject $logger;
    /** @var ParameterBagInterface&MockObject */ private MockObject $parameterBag;

    private GetSonarVersionService $service;

    protected function setUp(): void
    {
        $this->client = $this->createMock(ClientService::class);
        $this->urlBuilder = $this->createMock(UrlBuilderService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);

        $this->urlBuilder->method('build')->willReturn('https://sonar.example.com/api/server/version');
        $this->parameterBag->method('get')->willReturnMap([
            ['sonar.url', 'https://sonar.example.com'],
        ]);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnMap([['parameter_bag', true]]);
        $container->method('get')->willReturnMap([['parameter_bag', 1, $this->parameterBag]]);

        $this->service = new GetSonarVersionService($this->client, $this->urlBuilder, $this->logger);
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
}
