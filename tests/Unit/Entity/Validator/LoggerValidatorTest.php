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

use App\Entity\Logger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * [Description LoggerValidatorTest]
 */
class LoggerValidatorTest extends KernelTestCase
{

  private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
  private static $loggerInfo = 14;
  private static $loggerWarn = 0;
  private static $loggerError = 15;
  private static $loggerDebug = 8;
  private static $modeCollecte = 'TRAITEMENT MANUEL';
  private static $utilisateurCollecte = 'laurent.hadjadj@ma-petite-entreprise.fr';
  private static $dateEnregistrement = '2024-04-12 16:23:11+01';

  private function getEntity(): Logger
  {
      return (new logger())
      ->setMavenKey(static::$mavenKey)
      ->setLoggerInfo(static::$loggerInfo)
      ->setLoggerWarn(static::$loggerWarn)
      ->setLoggerError(static::$loggerError)
      ->setLoggerDebug(static::$loggerDebug)
      ->setModeCollecte(static::$modeCollecte)
      ->setUtilisateurCollecte(static::$utilisateurCollecte)
      ->setDateEnregistrement(new \DateTimeImmutable(static::$dateEnregistrement));
  }

  public function assertHasErrors(Logger $entity, int $number = 0): void
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
    $this->assertHasErrors($this->getEntity()->setModeCollecte(''), 0);
    $this->assertHasErrors($this->getEntity()->setUtilisateurCollecte(''), 0);
  }

  public function testValidIntegerEntity(): void
  {
    $this->assertHasErrors($this->getEntity()->setLoggerInfo(-1), 0);
    $this->assertHasErrors($this->getEntity()->setLoggerWarn(-1), 0);
    $this->assertHasErrors($this->getEntity()->setLoggerError(-1), 0);
    $this->assertHasErrors($this->getEntity()->setLoggerDebug(-1), 0);
  }

  public function testCountAttribut(): void
  {
      $entity = $this->getEntity();
      $reflectionClass = new \ReflectionClass($entity);
      $nbAttributs = count($reflectionClass->getProperties());
      $this->assertEquals($nbAttributs, 9);
  }
}
