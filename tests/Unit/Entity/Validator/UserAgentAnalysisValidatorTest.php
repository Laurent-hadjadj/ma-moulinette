<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Tests\Unit\Entity\Validator;

use App\Entity\UserAgentAnalysis;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * [Description UserAgentAnalysisValidatorTest]
 *
 * v2.0.0 : aucune contrainte Assert sur l'entite, seul testValidEntity + countAttribut.
 */
class UserAgentAnalysisValidatorTest extends KernelTestCase
{
    public function assertHasErrors(UserAgentAnalysis $entity, int $number = 0): void
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
        $this->assertHasErrors(new UserAgentAnalysis(), 0);
    }

    public function testCountAttribut(): void
    {
        $reflectionClass = new \ReflectionClass(new UserAgentAnalysis());
        $this->assertEquals(14, count($reflectionClass->getProperties()));
    }
}
