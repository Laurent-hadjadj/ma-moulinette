<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Tests\Unit\Entity\Validator;

use App\Entity\BatchExecution;
use App\Entity\BatchExecutionJournal;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * [Description BatchExecutionJournalValidatorTest]
 *
 * v2.0.0 : aucune contrainte Assert sur l'entite, seul testValidEntity + countAttribut.
 */
class BatchExecutionJournalValidatorTest extends KernelTestCase
{
    private function getEntity(): BatchExecutionJournal
    {
        $batch = new BatchExecution(
            nomTraitement: 'job',
            executionId: new Ulid(),
            traitementId: new Ulid(),
            utilisateurCollecte: 'admin@ma-moulinette.fr',
            modeCollecte: 'COLLECTE'
        );

        return new BatchExecutionJournal(
            nomProjet: 'ma-moulinette',
            portefeuille: 'Equipe DEV',
            compteRendu: '<p>OK</p>',
            batchExecution: $batch,
            dateExecution: new \DateTimeImmutable(),
            code: 200
        );
    }

    public function assertHasErrors(BatchExecutionJournal $entity, int $number = 0): void
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

    public function testCountAttribut(): void
    {
        $reflectionClass = new \ReflectionClass($this->getEntity());
        $this->assertEquals(7, count($reflectionClass->getProperties()));
    }
}
