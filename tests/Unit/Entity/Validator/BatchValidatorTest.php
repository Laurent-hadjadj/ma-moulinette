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

use App\Entity\Batch;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * [Description BatchValidatorTest]
 *
 * v2.0.0 : tests de validation pour les contraintes de l'entite Batch.
 */
class BatchValidatorTest extends KernelTestCase
{
    private function getEntity(): Batch
    {
        return (new Batch())
            ->setActivated(true)
            ->setAutomatique(false)
            ->setTitre('Daily collecte')
            ->setDescription('Collecte quotidienne des indicateurs')
            ->setResponsable('Laurent HADJADJ')
            ->setResponsableShort('L.HADJADJ')
            ->setPortefeuille('Equipe DEV')
            ->setNombreProjet(15);
    }

    public function assertHasErrors(Batch $entity, int $number = 0): void
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
        $this->assertHasErrors($this->getEntity()->setDescription(''), 1);
        $this->assertHasErrors($this->getEntity()->setResponsable(''), 1);
        $this->assertHasErrors($this->getEntity()->setResponsableShort(''), 1);
        $this->assertHasErrors($this->getEntity()->setPortefeuille(''), 1);
    }

    public function testInvalidLengthEntity(): void
    {
        $this->assertHasErrors($this->getEntity()->setTitre(str_repeat('a', 33)), 1);
        $this->assertHasErrors($this->getEntity()->setDescription(str_repeat('a', 129)), 1);
        $this->assertHasErrors($this->getEntity()->setResponsable(str_repeat('a', 129)), 1);
        $this->assertHasErrors($this->getEntity()->setResponsableShort(str_repeat('a', 65)), 1);
        $this->assertHasErrors($this->getEntity()->setPortefeuille(str_repeat('a', 33)), 1);
    }

    public function testValidNombreProjet(): void
    {
        $this->assertHasErrors($this->getEntity()->setNombreProjet(0), 0);
        $this->assertHasErrors($this->getEntity()->setNombreProjet(100), 0);
    }

    public function testInvalidNombreProjet(): void
    {
        $this->assertHasErrors($this->getEntity()->setNombreProjet(-1), 1);
    }

    public function testCountAttribut(): void
    {
        $reflectionClass = new \ReflectionClass(new Batch());
        $this->assertEquals(13, count($reflectionClass->getProperties()));
    }
}
