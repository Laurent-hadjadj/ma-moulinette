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

namespace App\Tests\Unit\Entity\Main;

use App\Entity\Main\Mesures;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * [Description MesuresValidatorTest]
 */
class MesuresValidatorTest extends KernelTestCase
{

  private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
  private static $projectName = 'Ma-Moulinette';
  private static $lines = 22015;
  private static $ncloc = 10043;
  private static $coverage = 10.3;
  private static $duplicationDensity = 5.1;
  private static $sqaleDebtRatio = 26.0;
  private static $issues = 200;
  private static $tests = 123;
  private static $dateEnregistrement = '2024-04-12 16:23:11';

  private function getEntity(): Mesures
  {
      return (new mesures())
      ->setMavenKey(static::$mavenKey)
      ->setProjectName(static::$projectName)
      ->setLines(static::$lines)
      ->setNcloc(static::$ncloc)
      ->setCoverage(static::$coverage)
      ->setDuplicationDensity(static::$duplicationDensity)
      ->setSqaleDebtRatio(static::$sqaleDebtRatio)
      ->setIssues(static::$issues)
      ->setTests(static::$tests)
      ->setDateEnregistrement(new \DateTime(static::$dateEnregistrement));
  }

  public function assertHasErrors(Mesures $entity, int $number = 0): void
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
  }

  public function testValidIntegerEntity(): void
  {
    $this->assertHasErrors($this->getEntity()->setLines(-1), 0);
    $this->assertHasErrors($this->getEntity()->setNcloc(-1), 0);
    $this->assertHasErrors($this->getEntity()->setIssues(-1), 0);
    $this->assertHasErrors($this->getEntity()->setTests(-1), 0);
  }

  public function testValidFloatEntity(): void
  {
    $this->assertHasErrors($this->getEntity()->setCoverage(-1.0), 0);
    $this->assertHasErrors($this->getEntity()->setDuplicationDensity(-1.0), 0);
    $this->assertHasErrors($this->getEntity()->setSqaleDebtRatio(-1.0), 0);
  }

  public function testCountAttribut(): void
  {
      $entity = $this->getEntity();
      $reflectionClass = new \ReflectionClass($entity);
      $nbAttributs = count($reflectionClass->getProperties());
      $this->assertEquals($nbAttributs, 11);
  }
}
