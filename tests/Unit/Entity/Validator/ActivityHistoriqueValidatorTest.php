<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2025.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Tests\Unit\Entity\Validator;

use App\Entity\ActivityHistorique;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;

class ActivityHistoriqueValidatorTest extends KernelTestCase
{

  private static $year = 2024;
  private static $day = 326;
  private static $analyse = 1253;
  private static $analyseAverage = 87.5;
  private static $success = 1249;
  private static $failed = 4;
  private static $successRate = 0.99;
  private static $maxTime = 34;
  private static $dateEnregistrement = '2024-07-14 19:36:33+02';

  private function getEntity(): ActivityHistorique
  {
      return (new activityHistorique())
      ->setYear(static::$year)
      ->setDay(static::$day)
      ->setAnalyse(static::$analyse)
      ->setAnalyseAverage(static::$analyseAverage)
      ->setSuccess(static::$success)
      ->setFailed(static::$failed)
      ->setSuccessRate(static::$successRate)
      ->setMaxTime(static::$maxTime)
      ->setDateEnregistrement(new \DateTimeImmutable(static::$dateEnregistrement));
}

  public function assertHasErrors(ActivityHistorique $entity, int $number = 0): void
  {
    self::bootKernel();
    $container = static::getContainer();
    $errors = $container->get('validator')->validate($entity);
    $messages = [];
    /** @var ConstraintViolation $error */
    foreach($errors as $error) {
      $messages[] = $error->getPropertyPath() . ' => ' . $error->getMessage();
    }
    $this->assertCount($number, $errors, implode(', ', $messages));
  }

  public function testValidEntity(): void
  {
    $this->assertHasErrors($this->getEntity(), 0);
  }

  public function testValidIntegerEntity(): void
  {
    $this->assertHasErrors($this->getEntity()->setYear(-1), 0);
    $this->assertHasErrors($this->getEntity()->setDay(-1), 0);
    $this->assertHasErrors($this->getEntity()->setAnalyse(-1), 0);
    $this->assertHasErrors($this->getEntity()->setSuccess(-1), 0);
    $this->assertHasErrors($this->getEntity()->setFailed(-1), 0);
    $this->assertHasErrors($this->getEntity()->setMaxTime(-1), 0);
  }

  public function testValidFloatEntity(): void
  {
    $this->assertHasErrors($this->getEntity()->setAnalyseAverage(0.0), 0);
    $this->assertHasErrors($this->getEntity()->setSuccessRate(0.0), 0);
  }

  public function testCountAttribut(): void
  {
      $entity = $this->getEntity();
      $reflectionClass = new \ReflectionClass($entity);
      $nbAttributs = count($reflectionClass->getProperties());
      $this->assertEquals($nbAttributs, 10);
  }
}
