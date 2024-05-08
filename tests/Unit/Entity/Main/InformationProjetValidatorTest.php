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

use App\Entity\Main\InformationProjet;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;

class InformationProjetValidatorTest extends KernelTestCase
{

  private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
  private static $analyseKey = 'AYVyxZcQo0TJpgSeq-ph';
  private static $date = '2024-04-12 16:23:11';
  private static $projectVersion = '2.0.0-RELEASE';
  private static $type = 'RELEASE';
  private static $dateEnregistrement = '2024-04-12 16:23:11';

  private function getEntity(): InformationProjet
  {
      return (new informationProjet())
      ->setMavenKey(static::$mavenKey)
      ->setAnalyseKey(static::$analyseKey)
      ->setDate(new \DateTime(static::$date))
      ->setProjectVersion(static::$projectVersion)
      ->setType(static::$type)
      ->setDateEnregistrement(new \DateTime(static::$dateEnregistrement));
}

  public function assertHasErrors(InformationProjet $entity, int $number = 0): void
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
    $this->assertHasErrors($this->getEntity()->setAnalyseKey(''), 1);
    $this->assertHasErrors($this->getEntity()->setProjectVersion(''), 1);
    $this->assertHasErrors($this->getEntity()->setType(''), 1);
  }

  public function testCountAttribut(): void
  {
      $entity = $this->getEntity();
      $reflectionClass = new \ReflectionClass($entity);
      $nbAttributs = count($reflectionClass->getProperties());
      $this->assertEquals($nbAttributs, 7);
  }
}
