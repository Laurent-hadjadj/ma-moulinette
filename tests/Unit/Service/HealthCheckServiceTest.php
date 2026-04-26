<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\HealthCheckService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class HealthCheckServiceTest extends TestCase
{
    /** @var Connection&MockObject */
    private MockObject $connection;

    /** @var LoggerInterface&MockObject */
    private MockObject $logger;

    /** @var CacheInterface&MockObject */
    private MockObject $cache;

    private HealthCheckService $service;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->cache = $this->createMock(CacheInterface::class);

        // Le cache délègue au callback (= on exécute la logique métier sans cacher)
        $this->cache->expects($this->once())
            ->method('get')
            ->willReturnCallback(function (string $key, callable $callback) {
                $item = $this->createMock(ItemInterface::class);
                $item->expects($this->once())
                    ->method('expiresAfter')
                    ->with(5);
                return $callback($item);
            });

        $this->service = new HealthCheckService(
            $this->connection,
            $this->logger,
            $this->cache
        );
    }

    public function testCheckReturnsEmptyArrayWhenAllOk(): void
    {
        $this->connection->expects($this->once())
            ->method('fetchOne')
            ->with('SELECT 1')
            ->willReturn(1);

        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->expects($this->once())
            ->method('tablesExist')
            ->with(['ma_moulinette'])
            ->willReturn(true);

        $this->connection->expects($this->once())
            ->method('createSchemaManager')
            ->willReturn($schemaManager);

        $this->logger->expects($this->never())->method('error');
        $this->logger->expects($this->never())->method('warning');

        $result = $this->service->check();

        $this->assertSame([], $result);
    }

    public function testCheckReturnsErrorWhenDatabaseIsUnreachable(): void
    {
        $this->connection->expects($this->once())
            ->method('fetchOne')
            ->willThrowException(new \RuntimeException('Connection refused'));

        // Si la DB est down, on court-circuite avant la vérification du schéma
        $this->connection->expects($this->never())->method('createSchemaManager');

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                '[HealthCheck] ❌ La base de données est indisponible',
                $this->arrayHasKey('exception')
            );

        $result = $this->service->check();

        $this->assertSame(
            ['[HealthCheck] ❌ La base de données est indisponible'],
            $result
        );
    }

    public function testCheckReturnsWarningWhenTableIsMissing(): void
    {
        $this->connection->expects($this->once())
            ->method('fetchOne')
            ->willReturn(1);

        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->expects($this->once())
            ->method('tablesExist')
            ->with(['ma_moulinette'])
            ->willReturn(false);

        $this->connection->expects($this->once())
            ->method('createSchemaManager')
            ->willReturn($schemaManager);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('[HealthCheck] ⚠️ La table \'ma_moulinette\' n\'existe pas');

        $this->logger->expects($this->never())->method('error');

        $result = $this->service->check();

        $this->assertSame(
            ['[HealthCheck] ⚠️ La table \'ma_moulinette\' n\'existe pas'],
            $result
        );
    }

    public function testCheckReturnsErrorWhenSchemaInspectionThrows(): void
    {
        $this->connection->expects($this->once())
            ->method('fetchOne')
            ->willReturn(1);

        $this->connection->expects($this->once())
            ->method('createSchemaManager')
            ->willThrowException(new \RuntimeException('schema driver missing'));

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                '[HealthCheck] ❌ La vérification du schéma a échoué',
                $this->arrayHasKey('exception')
            );

        $result = $this->service->check();

        $this->assertSame(
            ['[HealthCheck] ❌ La vérification du schéma a échoué'],
            $result
        );
    }
}
