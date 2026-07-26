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

namespace App\Tests\Unit\Controller\HealthCheck;

use App\Controller\HealthCheck\HealthCheckController;
use App\Service\HealthCheckService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimit;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

class HealthCheckControllerTest extends TestCase
{
    // Ip de Tests, faux positifs (sonarqube(php:S1313).
    private const TEST_IP_ERROR = '10.0.0.1';
    private const TEST_IP_DNS = '8.8.8.8';

    /** @var RateLimiterFactoryInterface&MockObject */
    private MockObject $limiterFactory;

    /** @var LimiterInterface&MockObject */
    private MockObject $limiter;

    /** @var HealthCheckService&MockObject */
    private MockObject $healthCheckService;

    /** @var LoggerInterface&MockObject */
    private MockObject $logger;

    /** @var ContainerInterface&MockObject */
    private MockObject $container;

    private HealthCheckController $controller;

    protected function setUp(): void
    {
        $this->limiterFactory = $this->createMock(RateLimiterFactoryInterface::class);
        $this->limiter = $this->createMock(LimiterInterface::class);
        $this->healthCheckService = $this->createMock(HealthCheckService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->container = $this->createMock(ContainerInterface::class);
        // AbstractController::json() consulte le service 'serializer' ; en l'absence
        // on retombe sur JsonResponse + json_encode, ce qui convient aux tests.
        $this->container->expects($this->once())
            ->method('has')
            ->with('serializer')
            ->willReturn(false);

        $this->controller = new HealthCheckController(
            $this->limiterFactory,
            $this->healthCheckService,
            $this->logger
        );
        $this->controller->setContainer($this->container);
    }

    public function testStatusReturns200AndCodeRetourOkWhenNoErrors(): void
    {
        $this->expectRateLimiterAccepts('127.0.0.1');

        $this->healthCheckService->expects($this->once())
            ->method('check')
            ->willReturn([]);

        $this->logger->expects($this->never())->method('warning');

        $response = $this->controller->status($this->buildRequest('127.0.0.1'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(
            ['codeRetour' => 'OK', 'listMessage' => []],
            json_decode($response->getContent(), true)
        );
    }

    public function testStatusReturns503AndCodeRetourKoWhenHealthCheckReportsErrors(): void
    {
        $this->expectRateLimiterAccepts(self::TEST_IP_ERROR);

        $errors = ['[HealthCheck] ❌ La base de données est indisponible'];
        $this->healthCheckService->expects($this->once())
            ->method('check')
            ->willReturn($errors);

        $this->logger->expects($this->never())->method('warning');

        $response = $this->controller->status($this->buildRequest(self::TEST_IP_ERROR));

        $this->assertSame(503, $response->getStatusCode());
        $this->assertSame(
            ['codeRetour' => 'KO', 'listMessage' => $errors],
            json_decode($response->getContent(), true)
        );
    }

    public function testStatusReturns429WhenRateLimiterRejects(): void
    {
        $this->expectRateLimiterRejects(self::TEST_IP_DNS);

        // Si on dépasse le rate limit on NE doit PAS exécuter le healthcheck métier
        $this->healthCheckService->expects($this->never())->method('check');

        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                '[HealthCheck] ⚠️ nombre de tentatives dépassé',
                ['client_ip' => self::TEST_IP_DNS]
            );

        $response = $this->controller->status($this->buildRequest(self::TEST_IP_DNS));

        $this->assertSame(429, $response->getStatusCode());
        $payload = json_decode($response->getContent(), true);
        $this->assertSame('KO', $payload['codeRetour']);
        $this->assertContains(
            'nombre de tentatives dépassé, veuillez réessayer plus tard.',
            $payload['listMessage']
        );
    }

    public function testStatusUsesAnonymousKeyWhenRequestHasNoClientIp(): void
    {
        // Pas d'IP → fallback sur la clé 'anonymous'
        $this->limiterFactory->expects($this->once())
            ->method('create')
            ->with('anonymous')
            ->willReturn($this->limiter);

        $this->limiter->expects($this->once())
            ->method('consume')
            ->willReturn($this->buildRateLimit(true));

        $this->healthCheckService->expects($this->once())
            ->method('check')
            ->willReturn([]);

        $this->logger->expects($this->never())->method('warning');

        $request = new Request();

        $response = $this->controller->status($request);

        $this->assertSame(200, $response->getStatusCode());
    }

    private function expectRateLimiterAccepts(string $expectedKey): void
    {
        $this->limiterFactory->expects($this->once())
            ->method('create')
            ->with($expectedKey)
            ->willReturn($this->limiter);

        $this->limiter->expects($this->once())
            ->method('consume')
            ->willReturn($this->buildRateLimit(true));
    }

    private function expectRateLimiterRejects(string $expectedKey): void
    {
        $this->limiterFactory->expects($this->once())
            ->method('create')
            ->with($expectedKey)
            ->willReturn($this->limiter);

        $this->limiter->expects($this->once())
            ->method('consume')
            ->willReturn($this->buildRateLimit(false));
    }

    private function buildRateLimit(bool $accepted): RateLimit
    {
        return new RateLimit(
            $accepted ? 10 : 0,
            new \DateTimeImmutable(),
            $accepted,
            10
        );
    }

    private function buildRequest(string $clientIp): Request
    {
        $request = new Request();
        $request->server->set('REMOTE_ADDR', $clientIp);

        return $request;
    }
}
