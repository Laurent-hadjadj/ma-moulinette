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

use App\Exception\SqlRequestException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SqlRequestExceptionTest extends TestCase
{
    public function testExtendsRuntimeException(): void
    {
        $e = new SqlRequestException('selectFoo', 500, 'db down');

        $this->assertInstanceOf(RuntimeException::class, $e);
    }

    public function testFormatsMessageWithRequestCodeAndError(): void
    {
        $e = new SqlRequestException('selectFoo', 500, 'db down');

        $this->assertSame('Échec de la requête selectFoo (Erreur 500): db down', $e->getMessage());
        $this->assertSame(500, $e->getCode());
    }

    public function testExposesAccessors(): void
    {
        $e = new SqlRequestException('insertBar', 23505, 'duplicate key');

        $this->assertSame(23505, $e->getErrorCode());
        $this->assertSame('insertBar', $e->getErrorRequest());
        $this->assertSame('duplicate key', $e->getErrorMessage());
    }

    public function testChainsPreviousException(): void
    {
        $previous = new \RuntimeException('connection refused');
        $e = new SqlRequestException('updateBaz', 503, 'unavailable', $previous);

        $this->assertSame($previous, $e->getPrevious());
    }

    public function testPreviousIsOptional(): void
    {
        $e = new SqlRequestException('deleteQux', 404, 'not found');

        $this->assertNull($e->getPrevious());
    }
}
