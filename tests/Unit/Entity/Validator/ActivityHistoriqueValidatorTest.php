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

use App\Entity\ActivityHistorique;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * [Description ActivityHistoriqueValidatorTest]
 */
class ActivityHistoriqueValidatorTest extends KernelTestCase
{

  private static int $year = 2024;
  private static int $day = 326;
  private static int $analyse = 1253;
  private static float $analyseAverage = 87.5;
  private static int $success = 1249;
  private static int $failed = 4;
  private static float $successRate = 0.99;
  private static int $maxTime = 34;
  private static string $dateEnregistrement = '2024-07-14 19:36:33+02';

  private function getEntity(): ActivityHistorique
  {
      return (new activityHistorique())
      ->setYear(self::$year)
      ->setDay(self::$day)
      ->setAnalyse(self::$analyse)
      ->setAnalyseAverage(self::$analyseAverage)
      ->setSuccess(self::$success)
      ->setFailed(self::$failed)
      ->setSuccessRate(self::$successRate)
      ->setMaxTime(self::$maxTime)
      ->setDateEnregistrement(new \DateTimeImmutable(self::$dateEnregistrement));
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

  /**
   * Valeurs entières acceptées par les contraintes v2.0.0 :
   *  - year         : Positive (> 0)
   *  - day          : Range(1..366)
   *  - analyse/success/failed/maxTime : PositiveOrZero (>= 0)
   */
  public function testValidIntegerEntity(): void
  {
    $this->assertHasErrors($this->getEntity()->setYear(2024), 0);
    $this->assertHasErrors($this->getEntity()->setDay(1), 0);
    $this->assertHasErrors($this->getEntity()->setDay(366), 0);
    $this->assertHasErrors($this->getEntity()->setAnalyse(0), 0);
    $this->assertHasErrors($this->getEntity()->setSuccess(0), 0);
    $this->assertHasErrors($this->getEntity()->setFailed(0), 0);
    $this->assertHasErrors($this->getEntity()->setMaxTime(0), 0);
  }

  /**
   * Valeurs entières rejetées par les contraintes v2.0.0.
   * Chaque cas attend exactement 1 violation (la contrainte du champ concerné).
   */
  public function testInvalidIntegerEntity(): void
  {
    // year : strictement positif
    $this->assertHasErrors($this->getEntity()->setYear(0), 1);
    $this->assertHasErrors($this->getEntity()->setYear(-1), 1);

    // day : entre 1 et 366 inclus
    $this->assertHasErrors($this->getEntity()->setDay(0), 1);
    $this->assertHasErrors($this->getEntity()->setDay(367), 1);
    $this->assertHasErrors($this->getEntity()->setDay(-1), 1);

    // PositiveOrZero
    $this->assertHasErrors($this->getEntity()->setAnalyse(-1), 1);
    $this->assertHasErrors($this->getEntity()->setSuccess(-1), 1);
    $this->assertHasErrors($this->getEntity()->setFailed(-1), 1);
    $this->assertHasErrors($this->getEntity()->setMaxTime(-1), 1);
  }

  /**
   * Valeurs flottantes acceptées :
   *  - analyseAverage : PositiveOrZero (>= 0.0)
   *  - successRate    : Range(0..100)
   */
  public function testValidFloatEntity(): void
  {
    $this->assertHasErrors($this->getEntity()->setAnalyseAverage(0.0), 0);
    $this->assertHasErrors($this->getEntity()->setSuccessRate(0.0), 0);
    $this->assertHasErrors($this->getEntity()->setSuccessRate(100.0), 0);
  }

  /**
   * Valeurs flottantes rejetées.
   */
  public function testInvalidFloatEntity(): void
  {
    $this->assertHasErrors($this->getEntity()->setAnalyseAverage(-0.1), 1);
    $this->assertHasErrors($this->getEntity()->setSuccessRate(-1.0), 1);
    $this->assertHasErrors($this->getEntity()->setSuccessRate(101.0), 1);
  }

  public function testCountAttribut(): void
  {
      $entity = $this->getEntity();
      $reflectionClass = new \ReflectionClass($entity);
      $nbAttributs = count($reflectionClass->getProperties());
      // 10 attributs : id, year, day, analyse, analyseAverage, success, failed, successRate, maxTime, dateEnregistrement
      $this->assertEquals(10, $nbAttributs);
  }
}
