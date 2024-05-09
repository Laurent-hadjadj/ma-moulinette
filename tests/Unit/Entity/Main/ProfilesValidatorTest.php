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

use App\Entity\Main\Profiles;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * [Description ProfilesValidatorTest]
 */
class ProfilesValidatorTest extends KernelTestCase
{

  private static $key = 'AXyXMubJRtAGLwAs7Zcv';
  private static $name = 'Ma-Petite-Entreprise v1.0.0 (2024)';
  private static $languageName = 'CSS';
  private static $activeRuleCount = 31;
  private static $rulesUpdateAt = '2024-04-13 12:10:51';
  private static $referentielDefault = true;
  private static $dateEnregistrement = '2024-04-12 16:23:11';

  private function getEntity(): Profiles
  {
      return (new profiles())
      ->setKey(static::$key)
      ->setName(static::$name)
      ->setLanguageName(static::$languageName)
      ->setActiveRuleCount(static::$activeRuleCount)
      ->setRulesUpdateAt(new \DateTime(static::$rulesUpdateAt))
      ->setReferentielDefault(static::$referentielDefault)
      ->setDateEnregistrement(new \DateTime(static::$dateEnregistrement));
  }

  public function assertHasErrors(Profiles $entity, int $number = 0): void
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
    $this->assertHasErrors($this->getEntity()->setKey(''), 1);
    $this->assertHasErrors($this->getEntity()->setName(''), 1);
    $this->assertHasErrors($this->getEntity()->setLanguageName(''), 1);
  }

  public function testValidIntegerEntity(): void
  {
    $this->assertHasErrors($this->getEntity()->setActiveRuleCount(-1), 0);
  }

  public function testValidBooleanEntity(): void
  {
    $this->assertHasErrors($this->getEntity()->setReferentielDefault(true), 0);
  }

  public function testCountAttribut(): void
  {
      $entity = $this->getEntity();
      $reflectionClass = new \ReflectionClass($entity);
      $nbAttributs = count($reflectionClass->getProperties());
      $this->assertEquals($nbAttributs, 8);
  }
}
