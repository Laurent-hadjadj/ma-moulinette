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

namespace App\Tests\Unit\Exception;

use App\Exception\SonarApiException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SonarApiException::class)]
final class SonarApiExceptionTest extends TestCase
{
    public function testStoresAndExposesResponsePayload(): void
    {
        $payload = ['code' => 503, 'json' => null, 'erreur' => 'Service Unavailable'];
        $exception = new SonarApiException('Erreur API Sonar (HTTP 503)', $payload);

        self::assertSame('Erreur API Sonar (HTTP 503)', $exception->getMessage());
        self::assertSame($payload, $exception->getResponse());
        self::assertSame(0, $exception->getCode());
        self::assertNull($exception->getPrevious());
    }

    public function testForwardsCodeAndPreviousToParent(): void
    {
        $previous = new \RuntimeException('upstream');
        $exception = new SonarApiException('msg', ['code' => 500], 42, $previous);

        self::assertSame(42, $exception->getCode());
        self::assertSame($previous, $exception->getPrevious());
    }

    public function testIsThrowable(): void
    {
        $this->expectException(SonarApiException::class);
        $this->expectExceptionMessage('boom');

        throw new SonarApiException('boom', []);
    }

    public function testEmptyResponseIsAllowed(): void
    {
        $exception = new SonarApiException('msg', []);

        self::assertSame([], $exception->getResponse());
    }
}
