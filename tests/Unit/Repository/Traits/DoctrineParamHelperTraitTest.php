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

namespace App\Tests\Unit\Repository\Traits;

use App\Repository\Traits\DoctrineParamHelperTrait;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Statement;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;

/**
 * MODIF 2026-05-15 : tests Unit du trait
 * DoctrineParamHelperTrait. Le trait fournit 3 méthodes utilitaires
 * `protected` ; on les expose via une classe anonyme de test pour
 * pouvoir les appeler depuis l'extérieur.
 *
 * Couvre :
 *   - bindNullableBool : valeur true/false/null → bindValue avec type correct
 *   - bindUlidAsString : Ulid → string RFC4122, string → string, null → NULL
 *   - bindNullableValue : null → ParameterType::NULL, sinon defaultType
 */
#[AllowMockObjectsWithoutExpectations]
class DoctrineParamHelperTraitTest extends TestCase
{
    /**
     * Helper qui expose les méthodes protected du trait.
     * Classe anonyme évite de polluer le namespace avec un dummy.
     */
    private function makeHelper(): object
    {
        return new class {
            use DoctrineParamHelperTrait;

            public function callBindNullableBool(Statement $stmt, string $param, ?bool $value): void
            {
                $this->bindNullableBool($stmt, $param, $value);
            }

            public function callBindUlidAsString(Statement $stmt, string $param, Ulid|string|null $value): void
            {
                $this->bindUlidAsString($stmt, $param, $value);
            }

            public function callBindNullableValue(
                Statement $stmt,
                string $param,
                mixed $value,
                ParameterType|ArrayParameterType $defaultType = ParameterType::STRING
            ): void {
                $this->bindNullableValue($stmt, $param, $value, $defaultType);
            }
        };
    }

    /* ============ bindNullableBool ============ */

    public function testBindNullableBoolWithTrueUsesBooleanType(): void
    {
        $stmt = $this->createMock(Statement::class);
        $stmt->expects($this->once())
            ->method('bindValue')
            ->with(':actif', true, ParameterType::BOOLEAN);

        $this->makeHelper()->callBindNullableBool($stmt, ':actif', true);
    }

    public function testBindNullableBoolWithFalseUsesBooleanType(): void
    {
        $stmt = $this->createMock(Statement::class);
        $stmt->expects($this->once())
            ->method('bindValue')
            ->with(':actif', false, ParameterType::BOOLEAN);

        $this->makeHelper()->callBindNullableBool($stmt, ':actif', false);
    }

    public function testBindNullableBoolWithNullUsesNullType(): void
    {
        $stmt = $this->createMock(Statement::class);
        $stmt->expects($this->once())
            ->method('bindValue')
            ->with(':actif', null, ParameterType::NULL);

        $this->makeHelper()->callBindNullableBool($stmt, ':actif', null);
    }

    /* ============ bindUlidAsString ============ */

    public function testBindUlidAsStringConvertsUlidToRfc4122(): void
    {
        $ulid = new Ulid('01ARZ3NDEKTSV4RRFFQ69G5FAV');
        $expected = $ulid->toRfc4122();

        $stmt = $this->createMock(Statement::class);
        $stmt->expects($this->once())
            ->method('bindValue')
            ->with(':uid', $expected, ParameterType::STRING);

        $this->makeHelper()->callBindUlidAsString($stmt, ':uid', $ulid);
    }

    public function testBindUlidAsStringPassesStringThrough(): void
    {
        $raw = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

        $stmt = $this->createMock(Statement::class);
        $stmt->expects($this->once())
            ->method('bindValue')
            ->with(':uid', $raw, ParameterType::STRING);

        $this->makeHelper()->callBindUlidAsString($stmt, ':uid', $raw);
    }

    public function testBindUlidAsStringWithNullUsesNullType(): void
    {
        $stmt = $this->createMock(Statement::class);
        $stmt->expects($this->once())
            ->method('bindValue')
            ->with(':uid', null, ParameterType::NULL);

        $this->makeHelper()->callBindUlidAsString($stmt, ':uid', null);
    }

    /* ============ bindNullableValue ============ */

    public function testBindNullableValueWithNullUsesNullType(): void
    {
        $stmt = $this->createMock(Statement::class);
        $stmt->expects($this->once())
            ->method('bindValue')
            ->with(':v', null, ParameterType::NULL);

        $this->makeHelper()->callBindNullableValue($stmt, ':v', null);
    }

    public function testBindNullableValueWithStringUsesDefaultStringType(): void
    {
        $stmt = $this->createMock(Statement::class);
        $stmt->expects($this->once())
            ->method('bindValue')
            ->with(':v', 'foo', ParameterType::STRING);

        $this->makeHelper()->callBindNullableValue($stmt, ':v', 'foo');
    }

    public function testBindNullableValueWithIntegerOverridesDefaultType(): void
    {
        $stmt = $this->createMock(Statement::class);
        $stmt->expects($this->once())
            ->method('bindValue')
            ->with(':v', 42, ParameterType::INTEGER);

        $this->makeHelper()->callBindNullableValue($stmt, ':v', 42, ParameterType::INTEGER);
    }

    /* NB : la signature du trait accepte `ParameterType|ArrayParameterType`
     * mais `Statement::bindValue` n'accepte PAS `ArrayParameterType` (TypeError
     * runtime). Cette branche est de facto inutilisable. Vérifié 2026-05-15 :
     * `bindNullableValue` n'est appelée NULLE PART en prod (dead code).
     * À simplifier (retrait de `ArrayParameterType` de la signature) ou supprimer
     * la méthode entièrement lors d'un futur nettoyage. Pas testé ici pour ne
     * pas figer un comportement buggé. */
}
