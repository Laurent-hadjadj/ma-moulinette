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

use App\Entity\Anomalie;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;

class AnomalieValidatorTest extends KernelTestCase
{

  private static $mavenKey = 'fr.map-petite-entreprise:ma-moulinette';
  private static $projectName = 'ma-moulinette';
  private static $anomalieTotal = 1956;
  private static $detteMinute = 19586;
  private static $detteReliabilityMinute = 107;
  private static $detteVulnerabilityMinute = 0;
  private static $detteCodeSmellMinute = 7369;
  private static $dette = '4d, 19h:32min';
  private static $detteReliability = '0h:5min';
  private static $detteVulnerability = '0h:0min';
  private static $detteCodeSmell = '5d, 2h:49min';
  private static $frontend = 806;
  private static $backend = 0;
  private static $autre = 0;
  private static $blocker = 0;
  private static $critical = 0;
  private static $major = 4750;
  private static $info = 0;
  private static $minor = 222;
  private static $bug = 0;
  private static $vulnerability = 0;
  private static $codeSmell = 801;
  private static $dateEnregistrement = '2024-03-25 12:26:58';

  private function getEntity(): Anomalie
  {
      return (new anomalie())
      ->setMavenKey(static::$mavenKey)
      ->setProjectName(static::$projectName)
      ->setAnomalieTotal(static::$anomalieTotal)
      ->setDetteMinute(static::$detteMinute)
      ->setDetteReliabilityMinute(static::$detteReliabilityMinute)
      ->setDetteVulnerabilityMinute(static::$detteVulnerabilityMinute)
      ->setDetteCodeSmellMinute(static::$detteCodeSmellMinute)
      ->setDette(static::$dette)
      ->setDetteReliability(static::$detteReliability)
      ->setDetteVulnerability(static::$detteVulnerability)
      ->setDetteCodeSmell(static::$detteCodeSmell)
      ->setFrontend(static::$frontend)
      ->setBackend(static::$backend)
      ->setAutre(static::$autre)
      ->setBlocker(static::$blocker)
      ->setCritical(static::$critical)
      ->setMajor(static::$major)
      ->setInfo(static::$info)
      ->setMinor(static::$minor)
      ->setBug(static::$bug)
      ->setVulnerability(static::$vulnerability)
      ->setCodeSmell(static::$codeSmell)
      ->setDateEnregistrement(new \DateTime(static::$dateEnregistrement));
}

  public function assertHasErrors(Anomalie $entity, int $number = 0): void
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
    $this->assertHasErrors($this->getEntity()->setDette(''), 1);
    $this->assertHasErrors($this->getEntity()->setDetteReliability(''), 1);
    $this->assertHasErrors($this->getEntity()->setDetteVulnerability(''), 1);
    $this->assertHasErrors($this->getEntity()->setDetteCodeSmell(''), 1);
  }

  public function testValidIntegerEntity(): void
  {
    $this->assertHasErrors($this->getEntity()->setAnomalieTotal(-1), 0);
    $this->assertHasErrors($this->getEntity()->setDetteMinute(-1), 0);
    $this->assertHasErrors($this->getEntity()->setDetteReliabilityMinute(-1), 0);
    $this->assertHasErrors($this->getEntity()->setDetteVulnerabilityMinute(-1), 0);
    $this->assertHasErrors($this->getEntity()->setDetteCodeSmellMinute(-1), 0);
    $this->assertHasErrors($this->getEntity()->setFrontend(-1), 0);
    $this->assertHasErrors($this->getEntity()->setBackend(-1), 0);
    $this->assertHasErrors($this->getEntity()->setAutre(-1), 0);
    $this->assertHasErrors($this->getEntity()->setBlocker(-1), 0);
    $this->assertHasErrors($this->getEntity()->setCritical(-1), 0);
    $this->assertHasErrors($this->getEntity()->setMajor(-1), 0);
    $this->assertHasErrors($this->getEntity()->setMinor(-1), 0);
    $this->assertHasErrors($this->getEntity()->setInfo(-1), 0);
    $this->assertHasErrors($this->getEntity()->setBug(-1), 0);
  }

  public function testCountAttribut(): void
  {
      $entity = $this->getEntity();
      $reflectionClass = new \ReflectionClass($entity);
      $nbAttributs = count($reflectionClass->getProperties());
      $this->assertEquals($nbAttributs, 24);
  }
}
