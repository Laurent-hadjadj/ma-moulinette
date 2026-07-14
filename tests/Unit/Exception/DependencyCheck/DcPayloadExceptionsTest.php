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

namespace App\Tests\Unit\Exception\DependencyCheck;

use App\Exception\DependencyCheck\{
    DcEmptyPayloadException,
    DcGzipDecodeException,
    DcInvalidJsonException,
    DcInvalidStructureException,
    DcPayloadException
};
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * MODIF 2026-05-15 : tests Unit des 5 exceptions
 * d'ingestion DC. Couvre :
 *  - hiérarchie (chaque concrete extends DcPayloadException extends RuntimeException)
 *  - DcPayloadException est abstract (ne peut pas être instanciée directement)
 *  - message + code + previous propages correctement
 */
class DcPayloadExceptionsTest extends TestCase
{
    public function testDcPayloadExceptionIsAbstract(): void
    {
        $ref = new \ReflectionClass(DcPayloadException::class);
        $this->assertTrue($ref->isAbstract(), 'DcPayloadException doit etre abstract');
    }

    public function testDcPayloadExceptionExtendsRuntimeException(): void
    {
        // @phpstan-ignore-next-line method.alreadyNarrowedType (test documentaire de la hiérarchie)
        $this->assertTrue(
            // @phpstan-ignore-next-line function.alreadyNarrowedType (statiquement connu par PHPStan)
            is_subclass_of(DcPayloadException::class, \RuntimeException::class),
            'DcPayloadException doit heriter de \\RuntimeException pour rester compatible avec les catch historiques'
        );
    }

    /** @return array<string, array{class-string<DcPayloadException>}> */
    public static function concreteExceptionsProvider(): array
    {
        return [
            'empty payload'     => [DcEmptyPayloadException::class],
            'gzip decode'       => [DcGzipDecodeException::class],
            'invalid json'      => [DcInvalidJsonException::class],
            'invalid structure' => [DcInvalidStructureException::class],
        ];
    }

    /**
     * @param class-string<DcPayloadException> $exceptionClass
     */
    #[DataProvider('concreteExceptionsProvider')]
    public function testConcreteExceptionExtendsDcPayloadException(string $exceptionClass): void
    {
        $this->assertTrue(
            is_subclass_of($exceptionClass, DcPayloadException::class),
            "$exceptionClass doit heriter de DcPayloadException"
        );
    }

    /**
     * @param class-string<DcPayloadException> $exceptionClass
     */
    #[DataProvider('concreteExceptionsProvider')]
    public function testConcreteExceptionPropagatesMessageCodePrevious(string $exceptionClass): void
    {
        $previous = new \LogicException('cause initiale');
        /** @var DcPayloadException $e */
        $e = new $exceptionClass('payload defectueux', 42, $previous);

        $this->assertSame('payload defectueux', $e->getMessage());
        $this->assertSame(42, $e->getCode());
        $this->assertSame($previous, $e->getPrevious());
    }

    /**
     * Catcher la famille parent DcPayloadException attrape bien chaque
     * exception concrete (use-case worker : un seul catch pour la branche
     * "rapport impossible a traiter").
     */
    public function testParentCatchAttrappeLesConcretes(): void
    {
        $caught = 0;
        foreach (self::concreteExceptionsProvider() as $row) {
            $exceptionClass = $row[0];
            try {
                throw new $exceptionClass('test');
            } catch (DcPayloadException $e) {
                $caught++;
            }
        }
        $this->assertSame(4, $caught, 'Les 4 exceptions concretes doivent etre catchees par DcPayloadException');
    }

    /**
     * Idem catch \RuntimeException : doit aussi attraper toute la famille
     * (compat avec les blocs catch (\RuntimeException) historiques).
     */
    public function testRuntimeCatchAttrappeLesConcretes(): void
    {
        $caught = 0;
        foreach (self::concreteExceptionsProvider() as $row) {
            $exceptionClass = $row[0];
            try {
                throw new $exceptionClass('test');
            } catch (\RuntimeException $e) {
                $caught++;
            }
        }
        $this->assertSame(4, $caught);
    }
}
