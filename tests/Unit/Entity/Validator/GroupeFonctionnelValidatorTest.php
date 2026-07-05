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

use App\Entity\GroupeFonctionnel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * [Description GroupeFonctionnelValidatorTest]
 */
class GroupeFonctionnelValidatorTest extends KernelTestCase
{
    private function getEntity(): GroupeFonctionnel
    {
        return (new GroupeFonctionnel())
            ->setGroupeFonctionnel('Equipe DEV')
            ->setDescription('Equipe de developpement');
    }

    public function assertHasErrors(GroupeFonctionnel $entity, int $number = 0): void
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
        $this->assertHasErrors($this->getEntity()->setGroupeFonctionnel(''), 1);
        $this->assertHasErrors($this->getEntity()->setDescription(''), 1);
    }

    public function testInvalidLengthEntity(): void
    {
        // groupeFonctionnel : Length(max:32)
        $this->assertHasErrors($this->getEntity()->setGroupeFonctionnel(str_repeat('a', 33)), 1);
        // description : Length(max:128)
        $this->assertHasErrors($this->getEntity()->setDescription(str_repeat('a', 129)), 1);
    }

    public function testCountAttribut(): void
    {
        $reflectionClass = new \ReflectionClass(new GroupeFonctionnel());
        $this->assertEquals(5, count($reflectionClass->getProperties()));
    }
}
