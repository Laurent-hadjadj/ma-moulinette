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

use App\Entity\Equipe;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;

class EquipeValidatorTest extends KernelTestCase
{

  private static $titre = 'MA PETITE ENTREPRISE';
  private static $description = "Equipe de Développement de l'application Ma-Moulinette";
  private static $dateModification = '2024-03-26 14:46:38';
  private static $dateEnregistrement = '2024-03-25 12:26:58';

  private function getEntity(): Equipe
  {
      return (new equipe())
      ->setTitre(static::$titre)
      ->setDescription(static::$description)
      ->setDateModification(new \DateTime(static::$dateModification))
      ->setDateEnregistrement(new \DateTime(static::$dateEnregistrement));
  }

  public function assertHasErrors(Equipe $entity, int $number = 0): void
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

  public function testTitreInvalidBlankEntity(): void
  {
    $this->assertHasErrors($this->getEntity()->setTitre(''), 1);
  }

  public function testDescriptionInvalidBlankEntity(): void
  {
    $this->assertHasErrors($this->getEntity()->setDescription(''), 1);
  }

  public function testCountAttribut(): void
  {
      $entity = $this->getEntity();
      $reflectionClass = new \ReflectionClass($entity);
      $nbAttributs = count($reflectionClass->getProperties());
      $this->assertEquals($nbAttributs, 5);
  }
}
