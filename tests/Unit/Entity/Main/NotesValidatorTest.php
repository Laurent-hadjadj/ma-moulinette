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


  /**
   * [Description for getEntity]
   *
   * @return Notes
   *
   * Created at: 06/05/2024 19:41:29 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  private function getEntity(): Notes
  {
      return (new notes())
      ->setMavenKey(static::$mavenKey)
      ->setType(static::$type)
      ->setValue(static::$value)
      ->setDateEnregistrement(new \DateTime(static::$dateEnregistrement));
  }


  /**
   * [Description for assertHasErrors]
   *
   * @param Notes $entity
   * @param int $number
   *
   * @return void
   *
   * Created at: 06/05/2024 19:41:50 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
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

  /**
   * [Description for testValidEntity]
   *
   * @return void
   *
   * Created at: 06/05/2024 19:41:59 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testValidEntity(): void
  {
    $this->assertHasErrors($this->getEntity(), 0);
  }

  /**
   * [Description for testInvalidBlankEntity]
   *
   * @return void
   *
   * Created at: 06/05/2024 19:42:02 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testInvalidBlankEntity(): void
  {
    $this->assertHasErrors($this->getEntity()->setMavenKey(''), 1);
    $this->assertHasErrors($this->getEntity()->setType(''), 1);
  }

  /**
   * [Description for testCountAttribut]
   *
   * @return void
   *
   * Created at: 06/05/2024 19:42:06 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testCountAttribut(): void
  {
      $entity = $this->getEntity();
      $reflectionClass = new \ReflectionClass($entity);
      $nbAttributs = count($reflectionClass->getProperties());
      $this->assertEquals($nbAttributs, 5);
  }
}
