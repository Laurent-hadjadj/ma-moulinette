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

use App\Entity\OwaspTop10;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * [Description OwaspTop10ValidatorTest]
 *
 * v2.0.0 : tests de validation pour les contraintes de l'entite OwaspTop10.
 */
class OwaspTop10ValidatorTest extends KernelTestCase
{
    private function getEntity(): OwaspTop10
    {
        return (new OwaspTop10())
            ->setYear(2021)
            ->setCategory('A01:2021 - Broken Access Control')
            ->setDescription('Restrictions on what authenticated users are allowed to do are often not properly enforced.')
            ->setLien('https://owasp.org/Top10/A01_2021-Broken_Access_Control/');
    }

    public function assertHasErrors(OwaspTop10 $entity, int $number = 0): void
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
        $this->assertHasErrors($this->getEntity()->setCategory(''), 1);
        $this->assertHasErrors($this->getEntity()->setDescription(''), 1);
        $this->assertHasErrors($this->getEntity()->setLien(''), 1);
    }

    public function testInvalidLengthEntity(): void
    {
        $this->assertHasErrors($this->getEntity()->setCategory(str_repeat('a', 256)), 1);
        $this->assertHasErrors($this->getEntity()->setLien(str_repeat('a', 129)), 1);
    }

    public function testValidYear(): void
    {
        $this->assertHasErrors($this->getEntity()->setYear(0), 0);
        $this->assertHasErrors($this->getEntity()->setYear(2017), 0);
        $this->assertHasErrors($this->getEntity()->setYear(2025), 0);
    }

    public function testInvalidYear(): void
    {
        $this->assertHasErrors($this->getEntity()->setYear(-1), 1);
    }

    public function testCountAttribut(): void
    {
        $reflectionClass = new \ReflectionClass(new OwaspTop10());
        $this->assertEquals(6, count($reflectionClass->getProperties()));
    }
}
