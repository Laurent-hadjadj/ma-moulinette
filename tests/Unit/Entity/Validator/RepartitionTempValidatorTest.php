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

use App\Entity\RepartitionTemp;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * [Description RepartitionTempValidatorTest]
 */
class RepartitionTempValidatorTest extends KernelTestCase
{
  private static string $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
  private static int $setup = 1739816022572;
  private static string $component = '/src/Controller/Accueil/AccueilController.php';
  private static string $type = 'CODE_SMELL';
  private static string $severity = 'INFO';

  private function getEntity(): RepartitionTemp
  {
      return (new RepartitionTemp())
      ->setMavenKey(self::$mavenKey)
      ->setSetup(self::$setup)
      ->setType(self::$type)
      ->setComponent(self::$component)
      ->setSeverity(self::$severity);
  }

  public function assertHasErrors(RepartitionTemp $entity, int $number = 0): void
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
    $this->assertHasErrors($this->getEntity()->setMavenKey(''), 0);
    $this->assertHasErrors($this->getEntity()->setComponent(''), 0);
    $this->assertHasErrors($this->getEntity()->setType(''), 0);
    $this->assertHasErrors($this->getEntity()->setSeverity(''), 0);
  }

  public function testValidIntegerEntity(): void
  {
    // v2.0.0 : valeurs limites BASSES acceptees par PositiveOrZero (>= 0)
    $this->assertHasErrors($this->getEntity()->setSetup(0), 0);
    }

  /**
   * v2.0.0 : valeurs negatives REJETEES par les contraintes PositiveOrZero / Positive / Range.
   */
  public function testInvalidIntegerEntity(): void
  {
    $this->assertHasErrors($this->getEntity()->setSetup(-1), 1);
    }

  public function testCountAttribut(): void
  {
      $entity = $this->getEntity();
      $reflectionClass = new \ReflectionClass($entity);
      $nbAttributs = count($reflectionClass->getProperties());
      $this->assertEquals($nbAttributs, 6, "Le nombre d'attribut doit être de 6.");
  }
}
