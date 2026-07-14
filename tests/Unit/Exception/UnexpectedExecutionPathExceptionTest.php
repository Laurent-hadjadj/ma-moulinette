<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

declare(strict_types=1);

namespace App\Tests\Unit\Exception;

use App\Exception\UnexpectedExecutionPathException;
use PHPUnit\Framework\TestCase;

/**
 * MODIF 2026-05-15 : tests Unit triviaux pour
 * marquer UnexpectedExecutionPathException couverte. Cette exception est
 * volontairement minimaliste (cf commentaire "Pour faire plaisir à SonarQube"
 * dans le fichier source).
 */
class UnexpectedExecutionPathExceptionTest extends TestCase
{
    public function testExtendsRuntimeException(): void
    {
        // @phpstan-ignore-next-line method.alreadyNarrowedType (test documentaire de la hiérarchie)
        $this->assertTrue(
            // @phpstan-ignore-next-line function.alreadyNarrowedType (statiquement connu par PHPStan)
            is_subclass_of(UnexpectedExecutionPathException::class, \RuntimeException::class)
        );
    }

    public function testCanBeThrownAndCaughtAsRuntime(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('chemin inattendu');
        throw new UnexpectedExecutionPathException('chemin inattendu');
    }

    public function testPropagatesMessageCodePrevious(): void
    {
        $previous = new \LogicException('cause');
        $e = new UnexpectedExecutionPathException('msg', 99, $previous);

        $this->assertSame('msg',     $e->getMessage());
        $this->assertSame(99,        $e->getCode());
        $this->assertSame($previous, $e->getPrevious());
    }
}
