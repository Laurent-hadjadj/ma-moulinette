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

use App\Entity\Properties;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * [Description PropertiesValidatorTest]
 */
class PropertiesValidatorTest extends KernelTestCase
{

  private static string $type = 'properties';
    private static int $projetBd = 100;
    private static int $projetSonar = 12;
    private static int $profilBd = 12;
    private static int $profilSonar = 18;
    private static string $dateCreation = '2024-03-26 14:46:38+01';
    private static string $dateModificationProjet = '2024-03-27 10:26:31+01';
    private static string $dateModificationProfil = '2024-04-12 16:23:11+01';

  private function getEntity(): Properties
  {
    return (new properties())
    ->setType(self::$type)
    ->setProjetBd(self::$projetBd)
    ->setProjetSonar(self::$projetSonar)
    ->setProfilBd(self::$profilBd)
    ->setProfilSonar(self::$profilSonar)
    ->setDateCreation(new \DateTimeImmutable(self::$dateCreation))
    ->setDateModificationProjet(new \DateTime(self::$dateModificationProjet))
    ->setDateModificationProfil(new \DateTime(self::$dateModificationProfil));
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
    // v2.0.0 : valeurs limites BASSES acceptees par PositiveOrZero (>= 0)
    $this->assertHasErrors($this->getEntity()->setprojetBd(0), 0);
    $this->assertHasErrors($this->getEntity()->setProjetSonar(0), 0);
    $this->assertHasErrors($this->getEntity()->setprofilBd(0), 0);
    $this->assertHasErrors($this->getEntity()->setProfilSonar(0), 0);
    }

  /**
   * v2.0.0 : valeurs negatives REJETEES par les contraintes PositiveOrZero / Positive / Range.
   */
  public function testInvalidIntegerEntity(): void
  {
    $this->assertHasErrors($this->getEntity()->setprojetBd(-1), 1);
    $this->assertHasErrors($this->getEntity()->setProjetSonar(-1), 1);
    $this->assertHasErrors($this->getEntity()->setprofilBd(-1), 1);
    $this->assertHasErrors($this->getEntity()->setProfilSonar(-1), 1);
    }

  public function testCountAttribut(): void
  {
      $entity = $this->getEntity();
      $reflectionClass = new \ReflectionClass($entity);
      $nbAttributs = count($reflectionClass->getProperties());
      $this->assertEquals($nbAttributs, 9);
  }
}
