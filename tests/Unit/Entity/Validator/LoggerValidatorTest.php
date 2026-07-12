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

use App\Entity\Logger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * [Description LoggerValidatorTest]
 */
class LoggerValidatorTest extends KernelTestCase
{

  private static string $mavenKey = 'fr.ma-moulinette:ma-moulinette';
  private static int $loggerInfo = 14;
  private static int $loggerWarn = 0;
  private static int $loggerError = 15;
  private static int $loggerDebug = 8;
  private static string $modeCollecte = 'TRAITEMENT MANUEL';
  private static string $utilisateurCollecte = 'laurent.hadjadj@ma-moulinette.fr';
  private static string $dateEnregistrement = '2024-04-12 16:23:11+01';

  private function getEntity(): Logger
  {
    return (new logger(
      self::$mavenKey,
      self::$loggerInfo,
      self::$loggerWarn,
      self::$loggerError,
      self::$loggerDebug,
      self::$modeCollecte,
      self::$utilisateurCollecte
    ))
      ->setDateEnregistrement(new \DateTimeImmutable(self::$dateEnregistrement));
  }

  public function assertHasErrors(Logger $entity, int $number = 0): void
  {
    self::bootKernel();
    $container = static::getContainer();
    $errors = $container->get('validator')->validate($entity);
    $messages = [];
    /** @var ConstraintViolation $error */
    foreach ($errors as $error) {
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
    $this->assertHasErrors($this->getEntity()->setModeCollecte(''), 0);
    $this->assertHasErrors($this->getEntity()->setUtilisateurCollecte(''), 0);
  }

  public function testValidIntegerEntity(): void
  {
    // v2.0.0 : valeurs limites BASSES acceptees par PositiveOrZero (>= 0)
    $this->assertHasErrors($this->getEntity()->setLoggerInfo(0), 0);
    $this->assertHasErrors($this->getEntity()->setLoggerWarn(0), 0);
    $this->assertHasErrors($this->getEntity()->setLoggerError(0), 0);
    $this->assertHasErrors($this->getEntity()->setLoggerDebug(0), 0);
  }

  /**
   * v2.0.0 : valeurs negatives REJETEES par les contraintes PositiveOrZero / Positive / Range.
   */
  public function testInvalidIntegerEntity(): void
  {
    $this->assertHasErrors($this->getEntity()->setLoggerInfo(-1), 1);
    $this->assertHasErrors($this->getEntity()->setLoggerWarn(-1), 1);
    $this->assertHasErrors($this->getEntity()->setLoggerError(-1), 1);
    $this->assertHasErrors($this->getEntity()->setLoggerDebug(-1), 1);
  }

  public function testCountAttribut(): void
  {
    $entity = $this->getEntity();
    $reflectionClass = new \ReflectionClass($entity);
    $nbAttributs = count($reflectionClass->getProperties());
    $this->assertEquals($nbAttributs, 9);
  }
}
