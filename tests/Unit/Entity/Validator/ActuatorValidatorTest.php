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

use App\Entity\Actuator;
use App\Entity\ActuatorInfo;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * [Description ActuatorValidatorTest]
 */
class ActuatorValidatorTest extends KernelTestCase
{
  private static string $mavenKey = 'fr.ma-moulinette:ma-moulinette';
  private static string $nomApplication = 'Application 04';
  private static string $url = 'http://ma-moulinette.fr/app04';
  private static string $actuatorUser = 'user4';
  private static string $actuatorPassword = 'password4';
  private static string $personne = 'Elsa Davis';
  private static string $dateModification = '2024-06-23 11:59:51.854783+02';
  private static string $actuatorInfoDescription = "Actuator INFO pour l'application 04";
  private static string $infoValue = '[SOCLE][ANGULAR]';

  private function getEntity(): Actuator
  {
    $actuator = new Actuator();
    $actuator->setMavenKey(self::$mavenKey)
      ->setNomApplication(self::$nomApplication)
      ->setActuatorUser(self::$actuatorUser)
      ->setActuatorPassword(self::$actuatorPassword)
      ->setUrl(self::$url)
      ->setPersonne(self::$personne)
      ->setDateModification(new \DateTimeImmutable(self::$dateModification));

    $actuatorInfo = new ActuatorInfo();
    $actuatorInfo->setActuator($actuator);
    $actuatorInfo->setActuatorInfoDescription(self::$actuatorInfoDescription);
    $actuatorInfo->setActuatorInfoCle(self::$infoValue);
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
    $this->assertHasErrors($this->getEntity()->setNomApplication(''), 1);
    $this->assertHasErrors($this->getEntity()->setUrl(''), 1);
    $this->assertHasErrors($this->getEntity()->setActuatorUser(''), 0);
    $this->assertHasErrors($this->getEntity()->setActuatorPassword(''), 0);
    $this->assertHasErrors($this->getEntity()->setPersonne(''), 1);
  }

  public function testCountAttribut(): void
  {
    /* MODIF 2026-05-07 : 11 → 10 (alignement Entity↔DDL session 2026-05-04). */
    $entity = $this->getEntity();
    $reflectionClass = new \ReflectionClass($entity);
    $nbAttributs = count($reflectionClass->getProperties());
    $this->assertEquals($nbAttributs, 10);
  }
}
