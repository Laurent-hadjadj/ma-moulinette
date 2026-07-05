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

use App\Entity\Activity;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * [Description ActivityValidatorTest]
 */
class ActivityValidatorTest extends KernelTestCase
{

  private static string $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
  private static string $projectName = 'ma-moulinette';
  private static string $analyseId = 'vtrf14lkiutq9mp';
  private static string $status = 'SUCCESS';
  private static string $submitterLogin = 'laurent.hadjadj';
  private static string $submittedAt = '2024-07-31 12:26:58+02';
  private static string $startedAt = '2024-07-31 12:27:05+02';
  private static string $executedAt = '2024-07-31 12:27:47+02';
  private static int $executionTime = 42;

  private function getEntity(): Activity
  {
      return (new activity())
        ->setMavenKey(self::$mavenKey)
        ->setProjectName(self::$projectName)
        ->setAnalyseId(self::$analyseId)
        ->setStatus(self::$status)
        ->setSubmitterLogin(self::$submitterLogin)
        ->setSubmittedAt(new \DateTimeImmutable(self::$submittedAt))
        ->setStartedAt(new \DateTimeImmutable(self::$startedAt))
        ->setExecutedAt(new \DateTimeImmutable(self::$executedAt))
        ->setExecutionTime(self::$executionTime);
  }

  public function assertHasErrors(Activity $entity, int $number = 0): void
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

  public function testInvalidBlankEntity(): void
  {
    $this->assertHasErrors($this->getEntity()->setMavenKey(''), 1);
    $this->assertHasErrors($this->getEntity()->setProjectName(''), 1);
    $this->assertHasErrors($this->getEntity()->setAnalyseId(''), 1);
    $this->assertHasErrors($this->getEntity()->setStatus(''), 1);
    $this->assertHasErrors($this->getEntity()->setSubmitterLogin(''), 1);
  }

  /**
   * v2.0.0 : `executionTime` est contraint par Assert\PositiveOrZero (>= 0).
   * Les valeurs valides : 0 et positives.
   */
  public function testValidIntegerEntity(): void
  {
    $this->assertHasErrors($this->getEntity()->setExecutionTime(0), 0);
    $this->assertHasErrors($this->getEntity()->setExecutionTime(42), 0);
  }

  /**
   * Les valeurs négatives sont rejetées par Assert\PositiveOrZero.
   */
  public function testInvalidIntegerEntity(): void
  {
    $this->assertHasErrors($this->getEntity()->setExecutionTime(-1), 1);
  }

  public function testCountAttribut(): void
  {
      $entity = $this->getEntity();
      $reflectionClass = new \ReflectionClass($entity);
      $nbAttributs = count($reflectionClass->getProperties());
      // 10 attributs : id, mavenKey, projectName, analyseId, status, submitterLogin,
      //                submittedAt, startedAt, executedAt, executionTime
      $this->assertEquals(10, $nbAttributs);
  }
}
