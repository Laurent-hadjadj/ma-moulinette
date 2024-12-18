<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2024.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Tests\Unit\Entity\Validator;

use App\Entity\Actuator;
use App\Entity\ActuatorInfo;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;

class ActuatorInfoValidatorTest extends KernelTestCase
{
  private static $actuatorId = 1;
  private static $actuatorInfoDescription = '[SOCLE][ARCHETYPE]';
  private static $actuatorInfoValue = 'socle.archetype';

private function getEntity(): ActuatorInfo
{
  $actuator = new Actuator();
  $actuator->setId(static::$actuatorId);

  $actuatorInfo = new ActuatorInfo();
  $actuatorInfo->setActuator($actuator)
              ->setActuatorInfoDescription(static::$actuatorInfoDescription)
              ->setActuatorInfoValue(static::$actuatorInfoValue);
  return $actuatorInfo;
}

  public function assertHasErrors(ActuatorInfo $entity, int $number = 0): void
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
    $this->assertHasErrors($this->getEntity()->setActuatorInfoDescription(''), 1);
    $this->assertHasErrors($this->getEntity()->setActuatorInfoValue(''), 1);
  }

  public function testCountAttribut(): void
  {
      $entity = $this->getEntity();
      $reflectionClass = new \ReflectionClass($entity);
      $nbAttributs = count($reflectionClass->getProperties());
      $this->assertEquals($nbAttributs, 4);
  }
}
