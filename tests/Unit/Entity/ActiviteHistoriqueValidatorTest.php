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

use App\Entity\ActiviteHistorique;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;

class ActiviteHistoriqueValidatorTest extends KernelTestCase
{

  private static $annee = 2024;
  private static $nbJour = 326;
  private static $nbAnalyse = 1253;
  private static $moyenneAnalyse = 87.5;
  private static $nbReussi = 1249;
  private static $nbEchec = 4;
  private static $tauxReussite = 0.99;
  private static $maxTemps = 34;
  private static $dateEnregistrement = '2024-07-14 19:36:33+02';

  private function getEntity(): ActiviteHistorique
  {
      return (new activiteHistorique())
      ->setAnnee(static::$annee)
      ->setNbJour(static::$nbJour)
      ->setNbAnalyse(static::$nbAnalyse)
      ->setMoyenneAnalyse(static::$moyenneAnalyse)
      ->setNbReussi(static::$nbReussi)
      ->setNbEchec(static::$nbEchec)
      ->setTauxReussite(static::$tauxReussite)
      ->setmaxTemps(static::$maxTemps)
      ->setDateEnregistrement(new \DateTimeImmutable(static::$dateEnregistrement));
}

  public function assertHasErrors(ActiviteHistorique $entity, int $number = 0): void
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

  public function testValidIntegerEntity(): void
  {
    $this->assertHasErrors($this->getEntity()->setAnnee(-1), 0);
    $this->assertHasErrors($this->getEntity()->setNbJour(-1), 0);
    $this->assertHasErrors($this->getEntity()->setNbAnalyse(-1), 0);
    $this->assertHasErrors($this->getEntity()->setNbReussi(-1), 0);
    $this->assertHasErrors($this->getEntity()->setNbEchec(-1), 0);
    $this->assertHasErrors($this->getEntity()->setMaxTemps(-1), 0);
  }

  public function testValidFloatEntity(): void
  {
    $this->assertHasErrors($this->getEntity()->setMoyenneAnalyse(0.0), 0);
    $this->assertHasErrors($this->getEntity()->setTauxReussite(0.0), 0);
  }

  public function testCountAttribut(): void
  {
      $entity = $this->getEntity();
      $reflectionClass = new \ReflectionClass($entity);
      $nbAttributs = count($reflectionClass->getProperties());
      $this->assertEquals($nbAttributs, 10);
  }
}
