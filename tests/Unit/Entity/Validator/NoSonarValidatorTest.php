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

use App\Entity\NoSonar;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * [Description NoSonarValidatorTest]
 */
class NoSonarValidatorTest extends KernelTestCase
{

  private static string $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
  private static string $rule = 'java:S1309';
  private static string $component = 'fr.ma-petite-entreprise:mo-moulinette:
  ma-moulinette-service/src/main/java/fr/ma-petite-entreprise/ma-moulinette/service/ClamAvService.java';
  private static int $line = 118;
  private static string $modeCollecte = 'TRAITEMENT MANUEL';
  private static string $utilisateurCollecte = 'laurent.hadjadj@ma-petite-entreprise.fr';
  private static string $dateEnregistrement = '2024-03-26 14:46:38+01';

  private function getEntity(): NoSonar
  {
      return (new nosonar())
      ->setMavenKey(self::$mavenKey)
      ->setRule(self::$rule)
      ->setComponent(self::$component)
      ->setLine(self::$line)
      ->setModeCollecte(self::$modeCollecte)
      ->setUtilisateurCollecte(self::$utilisateurCollecte)
      ->setDateEnregistrement(new \DateTimeImmutable(self::$dateEnregistrement));
  }

  public function assertHasErrors(NoSonar $entity, int $number = 0): void
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
    $this->assertHasErrors($this->getEntity()->setRule(''), 1);
    $this->assertHasErrors($this->getEntity()->setComponent(''), 1);
    $this->assertHasErrors($this->getEntity()->setModeCollecte(''), 0);
    $this->assertHasErrors($this->getEntity()->setUtilisateurCollecte(''), 0);
  }

  public function testValidIntegerEntity(): void
  {
    // v2.0.0 : valeurs limites BASSES acceptees par PositiveOrZero (>= 0)
    $this->assertHasErrors($this->getEntity()->setLine(0), 0);
    }

  /**
   * v2.0.0 : valeurs negatives REJETEES par les contraintes PositiveOrZero / Positive / Range.
   */
  public function testInvalidIntegerEntity(): void
  {
    $this->assertHasErrors($this->getEntity()->setLine(-1), 1);
    }

  public function testCountAttribut(): void
  {
      $entity = $this->getEntity();
      $reflectionClass = new \ReflectionClass($entity);
      $nbAttributs = count($reflectionClass->getProperties());
      $this->assertEquals($nbAttributs, 8);
  }
}
