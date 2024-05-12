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

use App\Entity\Main\ProfilesHistorique;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * [Description ProfilesHistoriqueValidatorTest]
 */
class ProfilesHistoriqueValidatorTest extends KernelTestCase
{

  private static $dateCourte = '2022-04-14';
  private static $language = 'java';
  private static $date  = '2022-08-30T18:42:41+0200';
  private static $action = 'ACTIVATED';
  private static $auteur = 'HADJADJ Laurent';
  private static $regle = 'java:S5679';
  private static $description = 'OpenSAML2 should be configured to prevent authentication bypass';
  private static $detail = '{"severity":"MAJOR"}';
  private static $dateEnregistrement = '2024-04-12 16:23:11';

  private function getEntity(): ProfilesHistorique
  {
      return (new profilesHistorique())
      ->setDateCourte(new \DateTime(static::$dateCourte))
      ->setLanguage(static::$language)
      ->setDate(new \DateTime(static::$date))
      ->setAction(static::$action)
      ->setAuteur(static::$auteur)
      ->setRegle(static::$regle)
      ->setDescription(static::$description)
      ->setDetail(static::$detail)
      ->setDateEnregistrement(new \DateTime(static::$dateEnregistrement));
  }

  public function assertHasErrors(ProfilesHistorique $entity, int $number = 0): void
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
    $this->assertHasErrors($this->getEntity()->setLanguage(''), 1);
    $this->assertHasErrors($this->getEntity()->setAction(''), 1);
    $this->assertHasErrors($this->getEntity()->setAuteur(''), 1);
    $this->assertHasErrors($this->getEntity()->setRegle(''), 1);
    $this->assertHasErrors($this->getEntity()->setDescription(''), 1);
    $this->assertHasErrors($this->getEntity()->setDetail(''), 1);
  }

  public function testCountAttribut(): void
  {
      $entity = $this->getEntity();
      $reflectionClass = new \ReflectionClass($entity);
      $nbAttributs = count($reflectionClass->getProperties());
      $this->assertEquals($nbAttributs, 10);
  }
}
