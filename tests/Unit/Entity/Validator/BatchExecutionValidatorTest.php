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

namespace App\Tests\Unit\Entity\Validator;

use App\Entity\BatchExecution;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * [Description BatchExecutionValidatorTest]
 */
class BatchExecutionValidatorTest extends KernelTestCase
{
    private function getEntity(
        string $nomTraitement = 'collecte_quotidienne',
        string $modeCollecte = 'COLLECTE',
        ?string $utilisateurCollecte = 'admin@ma-moulinette.fr'
    ): BatchExecution {
        return new BatchExecution(
            nomTraitement: $nomTraitement,
            executionId: new Ulid(),
            traitementId: new Ulid(),
            utilisateurCollecte: $utilisateurCollecte,
            modeCollecte: $modeCollecte
        );
    }

    public function assertHasErrors(BatchExecution $entity, int $number = 0): void
    {
        self::bootKernel();
        $errors = static::getContainer()->get('validator')->validate($entity);
        $messages = [];
        /** @var ConstraintViolation $error */
        foreach ($errors as $error) {
            $messages[] = $error->getPropertyPath() . ' => ' . $error->getMessage();
        }
        $this->assertCount($number, $errors, implode(', ', $messages));
    }

    public function testValidEntity(): void
    {
        $this->assertHasErrors($this->getEntity(), 0);
    }

    public function testInvalidBlankNomTraitement(): void
    {
        $this->assertHasErrors($this->getEntity(nomTraitement: ''), 1);
    }

    public function testInvalidLengthNomTraitement(): void
    {
        $this->assertHasErrors($this->getEntity(nomTraitement: str_repeat('a', 33)), 1);
    }

    public function testInvalidBlankModeCollecte(): void
    {
        $this->assertHasErrors($this->getEntity(modeCollecte: ''), 1);
    }

    public function testInvalidLengthModeCollecte(): void
    {
        $this->assertHasErrors($this->getEntity(modeCollecte: str_repeat('a', 33)), 1);
    }

    public function testInvalidEmailUtilisateurCollecte(): void
    {
        $this->assertHasErrors($this->getEntity(utilisateurCollecte: 'not-an-email'), 1);
    }

    public function testNullUtilisateurCollecteIsValid(): void
    {
        $this->assertHasErrors($this->getEntity(utilisateurCollecte: null), 0);
    }

    public function testCountAttribut(): void
    {
        $reflectionClass = new \ReflectionClass($this->getEntity());
        $this->assertEquals(8, count($reflectionClass->getProperties()));
    }
}
