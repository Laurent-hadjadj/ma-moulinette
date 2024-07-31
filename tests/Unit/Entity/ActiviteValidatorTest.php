<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2022.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Tests\Unit\Entity;

use App\Entity\Activite;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;

class ActiviteValidatorTest extends KernelTestCase
{

  private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
  private static $projectName = 'ma-moulinette';
  private static $analyseId = 'vtrf14lkiutq9mp';
  private static $status = 'SUCCESS';
  private static $submitterLogin = 'laurent.hadjadj';
  private static $submittedAt = '2024-07-31 12:26:58+02';
  private static $startedAt = '2024-07-31 12:27:05+02';
  private static $executedAt = '2024-07-31 12:27:47+02';
  private static $executionTime = "42";

  private function getEntity(): Activite
  {
      return (new activite())
        ->setMavenKey(static::$mavenKey)
        ->setProjectName(static::$projectName)
        ->setAnalyseId(static::$analyseId)
        ->setStatus(static::$status)
        ->setSubmitterLogin(static::$submitterLogin)
        ->setSubmittedAt(new \DateTimeImmutable(static::$submittedAt))
        ->setStartedAt(new \DateTimeImmutable(static::$startedAt))
        ->setExecutedAt(new \DateTimeImmutable(static::$executedAt))
        ->setExecutionTime(static::$executionTime);
  }

  public function assertHasErrors(Activite $entity, int $number = 0): void
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

  public function testValidIntegerEntity(): void
  {
    $this->assertHasErrors($this->getEntity()->setExecutionTime(-1), 0);
  }

  public function testCountAttribut(): void
  {
      $entity = $this->getEntity();
      $reflectionClass = new \ReflectionClass($entity);
      $nbAttributs = count($reflectionClass->getProperties());
      $this->assertEquals($nbAttributs, 10);
  }
}
