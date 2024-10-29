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

use App\Entity\Actuator;
use App\Entity\ActuatorInfo;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;

class ActuatorValidatorTest extends KernelTestCase
{
  private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
  private static $nomApplication = 'Application 04';
  private static $url = 'http://ma-moulinette.fr/app04';
  private static $actuatorUser = 'user4';
  private static $actuatorPassword = 'password4';
  private static $personne = 'Elsa Davis';
  private static $dateModification = '2024-06-23 11:59:51.854783+02';
  private static $actuatorInfoDescription = "Actuator INFO pour l'application 04";
  private static $infoValue = '[SOCLE][ANGULAR]';

  private function getEntityActuator(): Actuator
  {
    $actuator = new Actuator();
    $actuator->setMavenKey(static::$mavenKey)
                ->setNomApplication(static::$nomApplication)
                ->setUrl(static::$url)
                ->setActuatorUser(static::$actuatorUser)
                ->setActuatorPassword(static::$actuatorPassword)
                ->setPersonne(static::$personne)
                ->setDateModification(new \DateTimeImmutable(static::$dateModification))
                ->setDateEnregistrement(new \DateTimeImmutable());
  return $actuator;
}

private function getEntity(): Actuator
{
  $actuator = new Actuator();
  $actuator->setMavenKey(static::$mavenKey)
              ->setNomApplication(static::$nomApplication)
              ->setActuatorUser(static::$actuatorUser)
              ->setActuatorPassword(static::$actuatorPassword)
              ->setUrl(static::$url)
              ->setPersonne(static::$personne)
              ->setDateModification(new \DateTimeImmutable(static::$dateModification));

  $actuatorInfo = new ActuatorInfo();
  $actuatorInfo->setActuator($actuator);
  $actuatorInfo->setActuatorInfoDescription(static::$actuatorInfoDescription);
  $actuatorInfo->setActuatorInfoValue(static::$infoValue);
  $actuator->addActuatorInfo($actuatorInfo);

  return $actuator;
}

  public function assertHasErrors(Actuator $entity, int $number = 0): void
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
    $this->assertHasErrors($this->getEntity()->setNomApplication(''), 1);
    $this->assertHasErrors($this->getEntity()->setUrl(''), 1);
    $this->assertHasErrors($this->getEntity()->setActuatorUser  (''), 0);$this->assertHasErrors($this->getEntity()->setActuatorPassword  (''), 0);
    $this->assertHasErrors($this->getEntity()->setPersonne(''), 1);
  }

  public function testCountAttribut(): void
  {
      $entity = $this->getEntity();
      $reflectionClass = new \ReflectionClass($entity);
      $nbAttributs = count($reflectionClass->getProperties());
      $this->assertEquals($nbAttributs, 10);
  }
}
