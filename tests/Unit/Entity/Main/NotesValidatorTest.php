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

use App\Entity\Main\Notes;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * [Description NotesValidatorTest]
 */
class NotesValidatorTest extends KernelTestCase
{

  private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
  private static $type = 'reliability';
  private static $value = 3;
  private static $dateEnregistrement = '2024-03-26 14:46:38';

  private function getEntity(): Notes
  {
      return (new notes())
      ->setMavenKey(static::$mavenKey)
      ->setType(static::$type)
      ->setValue(static::$value)
      ->setDateEnregistrement(new \DateTime(static::$dateEnregistrement));
  }

  public function assertHasErrors(Notes $entity, int $number = 0): void
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
    $this->assertHasErrors($this->getEntity()->setType(''), 1);
  }

  public function testCountAttribut(): void
  {
      $entity = $this->getEntity();
      $reflectionClass = new \ReflectionClass($entity);
      $nbAttributs = count($reflectionClass->getProperties());
      $this->assertEquals($nbAttributs, 5);
  }
}
