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

use App\Entity\ActivityBatchReport;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * [Description ActivityBatchReportValidatorTest]
 *
 * v2.0.0 : l’entité ActivityBatchReport ne porte aucune contrainte Assert.
 * Le test verify uniquement la validité par default + le nombre d'attributs.
 */
class ActivityBatchReportValidatorTest extends KernelTestCase
{
    private function getEntity(): ActivityBatchReport
    {
        return (new ActivityBatchReport())
            ->setDateStart(new \DateTimeImmutable('2024-04-12 16:00:00+02:00'))
            ->setDateEnd(new \DateTimeImmutable('2024-04-12 16:30:00+02:00'))
            ->setTaskCount(10)
            ->setTaskDone(10)
            ->setPage(1);
    }

    public function assertHasErrors(ActivityBatchReport $entity, int $number = 0): void
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
        $reflectionClass = new \ReflectionClass(new ActivityBatchReport());
        $this->assertEquals(8, count($reflectionClass->getProperties()));
    }
}
