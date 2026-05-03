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

use App\Entity\Anomalie;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * [Description AnomalieValidatorTest]
 */
class AnomalieValidatorTest extends KernelTestCase
{

  private static string $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
  private static string $projectName = 'ma-moulinette';
  private static int $anomalieTotal = 1956;
  private static int $detteMinute = 19586;
  private static int $detteReliabilityMinute = 107;
  private static int $detteVulnerabilityMinute = 0;
  private static int $detteCodeSmellMinute = 7369;
  private static string $dette = '4d, 19h:32min';
  private static string $detteReliability = '0h:5min';
  private static string $detteVulnerability = '0h:0min';
  private static string $detteCodeSmell = '5d, 2h:49min';
  private static int $frontend = 806;
  private static int $backend = 0;
  private static int $autre = 0;
  private static int $inconnu = 1;
  private static int $blocker = 0;
  private static int $critical = 0;
  private static int $major = 4750;
  private static int $info = 0;
  private static int $minor = 222;
  private static int $bug = 0;
  private static int $vulnerability = 0;
  private static int $codeSmell = 801;
  private static string $modeCollecte = "TRAITEMENT AUTOMATIQUE";
  private static string $utilisateurCollecte = 'laurent.hadjadj@ma-petite-entreprise.fr';
  private static string $dateEnregistrement = '2024-03-25 12:26:58+02';

  private function getEntity(): Anomalie
  {
      return (new anomalie())
      ->setMavenKey(self::$mavenKey)
      ->setProjectName(self::$projectName)
      ->setAnomalieTotal(self::$anomalieTotal)
      ->setDetteMinute(self::$detteMinute)
      ->setDetteReliabilityMinute(self::$detteReliabilityMinute)
      ->setDetteVulnerabilityMinute(self::$detteVulnerabilityMinute)
      ->setDetteCodeSmellMinute(self::$detteCodeSmellMinute)
      ->setDette(self::$dette)
      ->setDetteReliability(self::$detteReliability)
      ->setDetteVulnerability(self::$detteVulnerability)
      ->setDetteCodeSmell(self::$detteCodeSmell)
      ->setFrontend(self::$frontend)
      ->setBackend(self::$backend)
      ->setAutre(self::$autre)
      ->setInconnu(self::$inconnu)
      ->setBlocker(self::$blocker)
      ->setCritical(self::$critical)
      ->setMajor(self::$major)
      ->setInfo(self::$info)
      ->setMinor(self::$minor)
      ->setBug(self::$bug)
      ->setVulnerability(self::$vulnerability)
      ->setCodeSmell(self::$codeSmell)
      ->setModeCollecte(self::$modeCollecte)
      ->setUtilisateurCollecte(self::$utilisateurCollecte)
      ->setDateEnregistrement(new \DateTimeImmutable(self::$dateEnregistrement));
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
    $this->assertHasErrors($this->getEntity()->setModeCollecte(''), 0);
    $this->assertHasErrors($this->getEntity()->setUtilisateurCollecte(''), 0);
  }

  public function testValidIntegerEntity(): void
  {
    // v2.0.0 : valeurs limites BASSES acceptees par PositiveOrZero (>= 0)
    $this->assertHasErrors($this->getEntity()->setAnomalieTotal(0), 0);
    $this->assertHasErrors($this->getEntity()->setDetteMinute(0), 0);
    $this->assertHasErrors($this->getEntity()->setDetteReliabilityMinute(0), 0);
    $this->assertHasErrors($this->getEntity()->setDetteVulnerabilityMinute(0), 0);
    $this->assertHasErrors($this->getEntity()->setDetteCodeSmellMinute(0), 0);
    $this->assertHasErrors($this->getEntity()->setFrontend(0), 0);
    $this->assertHasErrors($this->getEntity()->setBackend(0), 0);
    $this->assertHasErrors($this->getEntity()->setAutre(0), 0);
    $this->assertHasErrors($this->getEntity()->setInconnu(0), 0);
    $this->assertHasErrors($this->getEntity()->setBlocker(0), 0);
    $this->assertHasErrors($this->getEntity()->setCritical(0), 0);
    $this->assertHasErrors($this->getEntity()->setMajor(0), 0);
    $this->assertHasErrors($this->getEntity()->setMinor(0), 0);
    $this->assertHasErrors($this->getEntity()->setInfo(0), 0);
    $this->assertHasErrors($this->getEntity()->setBug(0), 0);
    }

  /**
   * v2.0.0 : valeurs negatives REJETEES par les contraintes PositiveOrZero / Positive / Range.
   */
  public function testInvalidIntegerEntity(): void
  {
    $this->assertHasErrors($this->getEntity()->setAnomalieTotal(-1), 1);
    $this->assertHasErrors($this->getEntity()->setDetteMinute(-1), 1);
    $this->assertHasErrors($this->getEntity()->setDetteReliabilityMinute(-1), 1);
    $this->assertHasErrors($this->getEntity()->setDetteVulnerabilityMinute(-1), 1);
    $this->assertHasErrors($this->getEntity()->setDetteCodeSmellMinute(-1), 1);
    $this->assertHasErrors($this->getEntity()->setFrontend(-1), 1);
    $this->assertHasErrors($this->getEntity()->setBackend(-1), 1);
    $this->assertHasErrors($this->getEntity()->setAutre(-1), 1);
    $this->assertHasErrors($this->getEntity()->setInconnu(-1), 1);
    $this->assertHasErrors($this->getEntity()->setBlocker(-1), 1);
    $this->assertHasErrors($this->getEntity()->setCritical(-1), 1);
    $this->assertHasErrors($this->getEntity()->setMajor(-1), 1);
    $this->assertHasErrors($this->getEntity()->setMinor(-1), 1);
    $this->assertHasErrors($this->getEntity()->setInfo(-1), 1);
    $this->assertHasErrors($this->getEntity()->setBug(-1), 1);
    }

  public function testCountAttribut(): void
  {
      $entity = $this->getEntity();
      $reflectionClass = new \ReflectionClass($entity);
      $nbAttributs = count($reflectionClass->getProperties());
      $this->assertEquals($nbAttributs, 27);
  }
}
