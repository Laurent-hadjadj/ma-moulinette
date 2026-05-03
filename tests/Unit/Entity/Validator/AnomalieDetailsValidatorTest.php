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

use App\Entity\AnomalieDetails;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * [Description AnomalieDetailsValidatorTest]
 */
class AnomalieDetailsValidatorTest extends KernelTestCase
{

  private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
  private static $name = 'ma-moulinette';
  private static $bugBlocker = 7;
  private static $bugCritical = 0;
  private static $bugMajor = 44;
  private static $bugInfo = 37;
  private static $bugMinor = 0;
  private static $vulnerabilityBlocker = 0;
  private static $vulnerabilityCritical = 9;
  private static $vulnerabilityMajor = 0;
  private static $vulnerabilityInfo = 0;
  private static $vulnerabilityMinor = 0;
  private static $codeSmellBlocker = 0;
  private static $codeSmellCritical = 4;
  private static $codeSmellMajor = 109;
  private static $codeSmellInfo = 72;
  private static $codeSmellMinor = 13;
  private static $utilisateurCollecte = 'laurent.hadjadj@ma-petite-entreprise.fr';
  private static $modeCollecte = 'TRAITEMENT MANUEL';
  private static $dateEnregistrement = '2024-07-14 19:36:33+02';


  private function getEntity(): AnomalieDetails
  {
      return (new anomalieDetails())
      ->setMavenKey(self::$mavenKey)
      ->setName(self::$name)
      ->setBugBlocker(self::$bugBlocker)
      ->setBugCritical(self::$bugCritical)
      ->setBugMajor(self::$bugMajor)
      ->setBugInfo(self::$bugInfo)
      ->setBugMinor(self::$bugMinor)
      ->setVulnerabilityBlocker(self::$vulnerabilityBlocker)
      ->setVulnerabilityCritical(self::$vulnerabilityCritical)
      ->setVulnerabilityMajor(self::$vulnerabilityMajor)
      ->setVulnerabilityInfo(self::$vulnerabilityInfo)
      ->setVulnerabilityMinor(self::$vulnerabilityMinor)
      ->setCodeSmellBlocker(self::$codeSmellBlocker)
      ->setCodeSmellCritical(self::$codeSmellCritical)
      ->setCodeSmellMajor(self::$codeSmellMajor)
      ->setCodeSmellInfo(self::$codeSmellInfo)
      ->setCodeSmellMinor(self::$codeSmellMinor)
      ->setUtilisateurCollecte(self::$utilisateurCollecte)
      ->setModeCollecte(self::$modeCollecte)
      ->setDateEnregistrement(new \DateTimeImmutable(self::$dateEnregistrement));
  }

  public function assertHasErrors(AnomalieDetails $entity, int $number = 0): void
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
    $this->assertHasErrors($this->getEntity()->setName(''), 1);
    $this->assertHasErrors($this->getEntity()->setModeCollecte(''), 0);
    $this->assertHasErrors($this->getEntity()->setUtilisateurCollecte(''), 0);
  }

  public function testValidIntegerEntity(): void
  {
    // v2.0.0 : valeurs limites BASSES acceptees par PositiveOrZero (>= 0)
    $this->assertHasErrors($this->getEntity()->setBugBlocker(0), 0);
    $this->assertHasErrors($this->getEntity()->setBugCritical(0), 0);
    $this->assertHasErrors($this->getEntity()->setBugMajor(0), 0);
    $this->assertHasErrors($this->getEntity()->setBugMinor(0), 0);
    $this->assertHasErrors($this->getEntity()->setBugInfo(0), 0);
    $this->assertHasErrors($this->getEntity()->setVulnerabilityBlocker(0), 0);
    $this->assertHasErrors($this->getEntity()->setVulnerabilityCritical(0), 0);
    $this->assertHasErrors($this->getEntity()->setVulnerabilityMajor(0), 0);
    $this->assertHasErrors($this->getEntity()->setVulnerabilityMinor(0), 0);
    $this->assertHasErrors($this->getEntity()->setVulnerabilityInfo(0), 0);
    $this->assertHasErrors($this->getEntity()->setCodeSmellBlocker(0), 0);
    $this->assertHasErrors($this->getEntity()->setCodeSmellCritical(0), 0);
    $this->assertHasErrors($this->getEntity()->setCodeSmellMajor(0), 0);
    $this->assertHasErrors($this->getEntity()->setCodeSmellMinor(0), 0);
    $this->assertHasErrors($this->getEntity()->setCodeSmellInfo(0), 0);
    }

  /**
   * v2.0.0 : valeurs negatives REJETEES par les contraintes PositiveOrZero / Positive / Range.
   */
  public function testInvalidIntegerEntity(): void
  {
    $this->assertHasErrors($this->getEntity()->setBugBlocker(-1), 1);
    $this->assertHasErrors($this->getEntity()->setBugCritical(-1), 1);
    $this->assertHasErrors($this->getEntity()->setBugMajor(-1), 1);
    $this->assertHasErrors($this->getEntity()->setBugMinor(-1), 1);
    $this->assertHasErrors($this->getEntity()->setBugInfo(-1), 1);
    $this->assertHasErrors($this->getEntity()->setVulnerabilityBlocker(-1), 1);
    $this->assertHasErrors($this->getEntity()->setVulnerabilityCritical(-1), 1);
    $this->assertHasErrors($this->getEntity()->setVulnerabilityMajor(-1), 1);
    $this->assertHasErrors($this->getEntity()->setVulnerabilityMinor(-1), 1);
    $this->assertHasErrors($this->getEntity()->setVulnerabilityInfo(-1), 1);
    $this->assertHasErrors($this->getEntity()->setCodeSmellBlocker(-1), 1);
    $this->assertHasErrors($this->getEntity()->setCodeSmellCritical(-1), 1);
    $this->assertHasErrors($this->getEntity()->setCodeSmellMajor(-1), 1);
    $this->assertHasErrors($this->getEntity()->setCodeSmellMinor(-1), 1);
    $this->assertHasErrors($this->getEntity()->setCodeSmellInfo(-1), 1);
    }

  public function testCountAttribut(): void
  {
      $entity = $this->getEntity();
      $reflectionClass = new \ReflectionClass($entity);
      $nbAttributs = count($reflectionClass->getProperties());
      $this->assertEquals($nbAttributs, 21);
  }
}
