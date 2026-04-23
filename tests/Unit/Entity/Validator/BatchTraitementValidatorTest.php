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

use App\Entity\BatchTraitement;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * [Description BatchTraitementValidatorTest]
 *
 * v2.0.0 : tests de validation pour les contraintes de l'entite BatchTraitement.
 */
class BatchTraitementValidatorTest extends KernelTestCase
{
    private function getEntity(): BatchTraitement
    {
        return new BatchTraitement(
            titre: 'Daily Job',
            portefeuille: 'Equipe DEV',
            responsable: 'Laurent HADJADJ',
            responsableShort: 'L.HADJADJ',
            modeCollecte: 'COLLECTE',
            nombreProjet: 5
        );
    }

    public function assertHasErrors(BatchTraitement $entity, int $number = 0): void
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

    public function testInvalidBlankEntity(): void
    {
        $this->assertHasErrors($this->getEntity()->setTitre(''), 1);
        $this->assertHasErrors($this->getEntity()->setPortefeuille(''), 1);
        $this->assertHasErrors($this->getEntity()->setResponsable(''), 1);
        $this->assertHasErrors($this->getEntity()->setResponsableShort(''), 1);
        // Vide => 2 violations : NotBlank + Choice (la chaine vide n'est pas un choix valide)
        $this->assertHasErrors($this->getEntity()->setModeCollecte(''), 2);
    }

    public function testInvalidLengthEntity(): void
    {
        $this->assertHasErrors($this->getEntity()->setTitre(str_repeat('a', 33)), 1);
        $this->assertHasErrors($this->getEntity()->setPortefeuille(str_repeat('a', 33)), 1);
        $this->assertHasErrors($this->getEntity()->setResponsable(str_repeat('a', 129)), 1);
        $this->assertHasErrors($this->getEntity()->setResponsableShort(str_repeat('a', 65)), 1);
    }

    public function testValidModeCollecteChoice(): void
    {
        foreach (['COLLECTE', 'TRAITEMENT MANUEL', 'TRAITEMENT AUTOMATIQUE'] as $mode) {
            $entity = $this->getEntity();
            $entity->setModeCollecte($mode);
            $this->assertHasErrors($entity, 0);
        }
    }

    public function testInvalidModeCollecteChoice(): void
    {
        $this->assertHasErrors($this->getEntity()->setModeCollecte('UNKNOWN'), 1);
    }

    public function testCountAttribut(): void
    {
        $reflectionClass = new \ReflectionClass($this->getEntity());
        $this->assertEquals(15, count($reflectionClass->getProperties()));
    }
}
