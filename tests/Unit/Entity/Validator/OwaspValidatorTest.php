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

use App\Entity\Owasp;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * [Description OwaspValidatorTest]
 */
class OwaspValidatorTest extends KernelTestCase
{

  private static string $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
  private static int $referentialOwasp = 2017;
  private static string $version = '1.2.0-RELEASE';
  private static string $dateVersion = '2024-07-10 15:26:07+02';
  private static int $effortTotal = 0;
  private static array $a = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
  private static array $aBlocker = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
  private static array $aCritical = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
  private static array $aMajor = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
  private static array $aInfo = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
  private static array $aMinor = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
  private static string $modeCollecte = 'TRAITEMENT MANUEL';
  private static string $utilisateurCollecte = 'laurent.hadjadj@ma-petite-entreprise.fr';
  private static string $dateEnregistrement = '2024-03-26 14:46:38+02';

  private function getEntity(): Owasp
  {
    $owasp = new Owasp();
    $owasp->setMavenKey(self::$mavenKey);
    $owasp->setReferentialOwasp(self::$referentialOwasp);
    $owasp->setVersion(self::$version);
    $owasp->setDateVersion(new \DateTimeImmutable(self::$dateVersion));
    $owasp->setEffortTotal(self::$effortTotal);

    for ($i = 0; $i < 10; $i++) {
        $owasp->{"setA" . ($i + 1)}(self::$a[$i]);
        $owasp->{"setA" . ($i + 1) . "Blocker"}(self::$aBlocker[$i]);
        $owasp->{"setA" . ($i + 1) . "Critical"}(self::$aCritical[$i]);
        $owasp->{"setA" . ($i + 1) . "Major"}(self::$aMajor[$i]);
        $owasp->{"setA" . ($i + 1) . "Info"}(self::$aInfo[$i]);
        $owasp->{"setA" . ($i + 1) . "Minor"}(self::$aMinor[$i]);
    }

    $owasp->setModeCollecte(self::$modeCollecte);
    $owasp->setUtilisateurCollecte(self::$utilisateurCollecte);
    $owasp->setDateEnregistrement(new \DateTimeImmutable(self::$dateEnregistrement));
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

  // v2.0.0 : `effortTotal`, `aN`, `aNBlocker`, `aNCritical`, `aNMajor`, `aNMinor`, `aNInfo`
  // sont contraints par Assert\PositiveOrZero (>= 0) — 0 valide, -1 rejeté.

  public function testValidIntegerAEntity(): void
  {
    $this->assertHasErrors($this->getEntity()->setEffortTotal(0), 0);
    for ($i = 0; $i < 10; $i++) {
      $this->assertHasErrors($this->getEntity()->{"setA" . ($i + 1)}(0), 0);
    }
  }

  public function testInvalidIntegerAEntity(): void
  {
    $this->assertHasErrors($this->getEntity()->setEffortTotal(-1), 1);
    for ($i = 0; $i < 10; $i++) {
      $this->assertHasErrors($this->getEntity()->{"setA" . ($i + 1)}(-1), 1);
    }
  }

  public function testValidIntegerABlockerEntity(): void
  {
    for ($i = 0; $i < 10; $i++) {
      $this->assertHasErrors($this->getEntity()->{"setA" . ($i + 1) . "Blocker"}(0), 0);
    }
  }

  public function testInvalidIntegerABlockerEntity(): void
  {
    for ($i = 0; $i < 10; $i++) {
      $this->assertHasErrors($this->getEntity()->{"setA" . ($i + 1) . "Blocker"}(-1), 1);
    }
  }

  public function testValidIntegerACriticalEntity(): void
  {
    for ($i = 0; $i < 10; $i++) {
      $this->assertHasErrors($this->getEntity()->{"setA" . ($i + 1) . "Critical"}(0), 0);
    }
  }

  public function testInvalidIntegerACriticalEntity(): void
  {
    for ($i = 0; $i < 10; $i++) {
      $this->assertHasErrors($this->getEntity()->{"setA" . ($i + 1) . "Critical"}(-1), 1);
    }
  }

  public function testValidIntegerAMajorEntity(): void
  {
    for ($i = 0; $i < 10; $i++) {
      $this->assertHasErrors($this->getEntity()->{"setA" . ($i + 1) . "Major"}(0), 0);
    }
  }

  public function testInvalidIntegerAMajorEntity(): void
  {
    for ($i = 0; $i < 10; $i++) {
      $this->assertHasErrors($this->getEntity()->{"setA" . ($i + 1) . "Major"}(-1), 1);
    }
  }

  public function testValidIntegerAMinorEntity(): void
  {
    for ($i = 0; $i < 10; $i++) {
      $this->assertHasErrors($this->getEntity()->{"setA" . ($i + 1) . "Minor"}(0), 0);
    }
  }

  public function testInvalidIntegerAMinorEntity(): void
  {
    for ($i = 0; $i < 10; $i++) {
      $this->assertHasErrors($this->getEntity()->{"setA" . ($i + 1) . "Minor"}(-1), 1);
    }
  }

  public function testValidIntegerAInfoEntity(): void
  {
    for ($i = 0; $i < 10; $i++) {
      $this->assertHasErrors($this->getEntity()->{"setA" . ($i + 1) . "Info"}(0), 0);
    }
  }

  public function testInvalidIntegerAInfoEntity(): void
  {
    for ($i = 0; $i < 10; $i++) {
      $this->assertHasErrors($this->getEntity()->{"setA" . ($i + 1) . "Info"}(-1), 1);
    }
  }

  public function testCountAttribut(): void
  {
      $entity = $this->getEntity();
      $reflectionClass = new \ReflectionClass($entity);
      $nbAttributs = count($reflectionClass->getProperties());
      $this->assertEquals($nbAttributs, 69);
  }
}
