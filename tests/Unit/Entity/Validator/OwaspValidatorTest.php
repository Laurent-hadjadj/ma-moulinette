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

namespace App\Tests\Unit\Entity\Validator;

use App\Entity\Owasp;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * [Description OwaspValidatorTest]
 */
class OwaspValidatorTest extends KernelTestCase
{

  private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
  private static $version = '1.2.0-RELEASE';
  private static $dateVersion = '2024-07-10 15:26:07+02';
  private static $effortTotal = 0;
  private static $a = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
  private static $aBlocker = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
  private static $aCritical = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
  private static $aMajor = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
  private static $aInfo = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
  private static $aMinor = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
  private static $modeCollecte = 'TRAITEMENT MANUEL';
  private static $utilisateurCollecte = 'laurent.hadjadj@ma-petite-entreprise.fr';
  private static $dateEnregistrement = '2024-03-26 14:46:38+02';

  private function getEntity(): Owasp
  {
    $owasp = new Owasp();
    $owasp->setMavenKey(static::$mavenKey);
    $owasp->setVersion(static::$version);
    $owasp->setDateVersion(new \DateTimeImmutable(static::$dateVersion));
    $owasp->setEffortTotal(static::$effortTotal);

    for ($i = 0; $i < 10; $i++) {
        $owasp->{"setA" . ($i + 1)}(static::$a[$i]);
        $owasp->{"setA" . ($i + 1) . "Blocker"}(static::$aBlocker[$i]);
        $owasp->{"setA" . ($i + 1) . "Critical"}(static::$aCritical[$i]);
        $owasp->{"setA" . ($i + 1) . "Major"}(static::$aMajor[$i]);
        $owasp->{"setA" . ($i + 1) . "Info"}(static::$aInfo[$i]);
        $owasp->{"setA" . ($i + 1) . "Minor"}(static::$aMinor[$i]);
    }

    $owasp->setModeCollecte(static::$modeCollecte);
    $owasp->setUtilisateurCollecte(static::$utilisateurCollecte);
    $owasp->setDateEnregistrement(new \DateTimeImmutable(static::$dateEnregistrement));
    return $owasp;
  }

  public function assertHasErrors(Owasp $entity, int $number = 0): void
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
    $this->assertHasErrors($this->getEntity()->setVersion(''), 1);
    $this->assertHasErrors($this->getEntity()->setModeCollecte(''), 0);
    $this->assertHasErrors($this->getEntity()->setUtilisateurCollecte(''), 0);
  }

  public function testValidIntegerAEntity(): void
  {
    $this->assertHasErrors($this->getEntity()->setEffortTotal(-1), 0);
    for ($i = 0; $i < 10; $i++) {
      $this->assertHasErrors($this->getEntity()->{"setA" . ($i + 1)}(-1), 0);
    }

  }

  public function testValidIntegerABlockerEntity(): void
  {
    for ($i = 0; $i < 10; $i++) {
      $this->assertHasErrors($this->getEntity()->{"setA" . ($i + 1) . "Blocker"}(-1), 0);
    }
  }

  public function testValidIntegerACriticalEntity(): void
  {
    for ($i = 0; $i < 10; $i++) {
      $this->assertHasErrors($this->getEntity()->{"setA" . ($i + 1) . "Critical"}(-1), 0);
    }
  }

  public function testValidIntegerAMajorEntity(): void
  {
    for ($i = 0; $i < 10; $i++) {
      $this->assertHasErrors($this->getEntity()->{"setA" . ($i + 1) . "Major"}(-1), 0);
    }
  }

  public function testValidIntegerAMinorEntity(): void
  {
    for ($i = 0; $i < 10; $i++) {
      $this->assertHasErrors($this->getEntity()->{"setA" . ($i + 1) . "Minor"}(-1), 0);
    }
  }

  public function testValidIntegerAInfoEntity(): void
  {
    for ($i = 0; $i < 10; $i++) {
      $this->assertHasErrors($this->getEntity()->{"setA" . ($i + 1) . "Info"}(-1), 0);
    }
  }

  public function testCountAttribut(): void
  {
      $entity = $this->getEntity();
      $reflectionClass = new \ReflectionClass($entity);
      $nbAttributs = count($reflectionClass->getProperties());
      $this->assertEquals($nbAttributs, 68);
  }
}
