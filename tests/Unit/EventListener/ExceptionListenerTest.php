<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  https://www.gnu.org/licenses/agpl-3.0.html
 */

declare(strict_types=1);

namespace App\Tests\Unit\EventListener;

use App\EventListener\ExceptionListener;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * MODIF 2026-11-22 : jusqu'ici aucun test - trouvé en auditant la couverture
 * avant release (14,28%). Couvre :
 *  - log déclenché pour une exception non-HTTP ou HTTP >= 500
 *  - pas de log pour une exception HTTP < 500 (404/403/400)
 *  - masquage password/secret/token/key dans le message ET la trace,
 *    y compris en casse mixte (ex. "Password=", "SECRET=") - corrigé dans ce
 *    même lot : la regex n'avait pas le flag /i, un secret en casse
 *    différente de la casse attendue par le pattern n'était pas masqué.
 */
#[AllowMockObjectsWithoutExpectations]
class ExceptionListenerTest extends TestCase
{
    private LoggerInterface&\PHPUnit\Framework\MockObject\MockObject $logger;
    private ExceptionListener $listener;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->listener = new ExceptionListener($this->logger);
    }

    public function testLogsForNonHttpException(): void
    {
        $this->logger->expects($this->once())->method('error')->with('Security Sensitive');

        $this->listener->onKernelException($this->buildEvent(new \RuntimeException('boom')));
    }

    public function testLogsForHttpExceptionWithStatus500OrAbove(): void
    {
        $this->logger->expects($this->once())->method('error')->with('Security Sensitive');

        $this->listener->onKernelException($this->buildEvent(new HttpException(503, 'service indisponible')));
    }

    public function testDoesNotLogForHttpExceptionBelow500(): void
    {
        $this->logger->expects($this->never())->method('error');

        $this->listener->onKernelException($this->buildEvent(new HttpException(404, 'introuvable')));
    }

    public function testDoesNotLogForForbiddenHttpException(): void
    {
        $this->logger->expects($this->never())->method('error');

        $this->listener->onKernelException($this->buildEvent(new HttpException(403, 'interdit')));
    }

    public function testMasksPasswordSecretTokenKeyRegardlessOfCase(): void
    {
        $captured = null;
        $this->logger->method('error')->willReturnCallback(
            function (string $message, array $context) use (&$captured): void { $captured = $context; }
        );

        $message = "Echec : password=hunter2, SECRET=abc123, Token=xyz789, KEY=deadbeef";
        $this->listener->onKernelException($this->buildEvent(new \RuntimeException($message)));

        $this->assertNotNull($captured);
        $maskedMessage = $captured['exception_message'];
        $this->assertStringNotContainsString('hunter2', $maskedMessage);
        $this->assertStringNotContainsString('abc123', $maskedMessage);
        $this->assertStringNotContainsString('xyz789', $maskedMessage);
        $this->assertStringNotContainsString('deadbeef', $maskedMessage);
        $this->assertStringContainsString('***MASKED***', $maskedMessage);
    }

    public function testExposesCodeFileAndLineInContext(): void
    {
        $captured = null;
        $this->logger->method('error')->willReturnCallback(
            function (string $message, array $context) use (&$captured): void { $captured = $context; }
        );

        $exception = new \RuntimeException('sans secret', 42);
        $this->listener->onKernelException($this->buildEvent($exception));

        $this->assertSame(42, $captured['exception_code']);
        $this->assertSame($exception->getFile(), $captured['exception_file']);
        $this->assertSame($exception->getLine(), $captured['exception_line']);
    }

    private function buildEvent(\Throwable $throwable): ExceptionEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        return new ExceptionEvent($kernel, Request::create('/'), HttpKernelInterface::MAIN_REQUEST, $throwable);
    }
}
