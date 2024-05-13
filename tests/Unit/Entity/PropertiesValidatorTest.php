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

use App\Entity\Properties;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * [Description PropertiesValidatorTest]
 */
class PropertiesValidatorTest extends KernelTestCase
{

  private static $type = 'properties';
    private static $projetBd = 100;
    private static $projetSonar = 12;
    private static $profilBd = 12;
    private static $profilSonar = 18;
    private static $dateCreation = '2024-03-26 14:46:38';
    private static $dateModificationProjet = '2024-03-27 10:26:31';
    private static $dateModificationProfil = '2024-04-12 16:23:11';

  private function getEntity(): Properties
  {
    return (new properties())
    ->setType(static::$type)
    ->setProjetBd(static::$projetBd)
    ->setProjetSonar(static::$projetSonar)
    ->setProfilBd(static::$profilBd)
    ->setProfilSonar(static::$profilSonar)
    ->setDateCreation(new \DateTime(static::$dateCreation))
    ->setDateModificationProjet(new \DateTime(static::$dateModificationProjet))
    ->setDateModificationProfil(new \DateTime(static::$dateModificationProfil));
  }

  public function assertHasErrors(Properties $entity, int $number = 0): void
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
    $this->assertHasErrors($this->getEntity()->setType(''), 1);
  }

  public function testValidIntegerEntity(): void
  {
    $this->assertHasErrors($this->getEntity()->setprojetBd(-1), 0);
    $this->assertHasErrors($this->getEntity()->setProjetSonar(-1), 0);
    $this->assertHasErrors($this->getEntity()->setprofilBd(-1), 0);
    $this->assertHasErrors($this->getEntity()->setProfilSonar(-1), 0);
  }

  public function testCountAttribut(): void
  {
      $entity = $this->getEntity();
      $reflectionClass = new \ReflectionClass($entity);
      $nbAttributs = count($reflectionClass->getProperties());
      $this->assertEquals($nbAttributs, 9);
  }
}
