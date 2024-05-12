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

use App\Entity\Main\Portefeuille;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;

class PortefeuilleValidatorTest extends KernelTestCase
{

  private static $titre = 'MES PROJETS';
  private static $equipe = 'MA PETITE ENTREPRISE - équipe de développement.';
  private static $liste =  ['fr.ma-petite-entreprise:ma-moulinette'];
  private static $dateModification = '2024-03-26 14:46:38';
  private static $dateEnregistrement = '2024-03-25 12:26:58';

  private function getEntity(): Portefeuille
  {
      return (new portefeuille())
      ->setTitre(static::$titre)
      ->setEquipe(static::$equipe)
      ->setListe(static::$liste)
      ->setDateModification(new \DateTime(static::$dateModification))
      ->setDateEnregistrement(new \DateTime(static::$dateEnregistrement));
}

  public function assertHasErrors(Portefeuille $entity, int $number = 0): void
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

  public function testTitreNotNullEntity(): void
  {
    $portefeuille = new portefeuille();
    $portefeuille->setTitre('mon titre');
    $this->assertNotNull($portefeuille->getTitre());
  }

  public function testEquipeInvalidBlankEntity(): void
  {
    $this->assertHasErrors($this->getEntity()->setEquipe(''), 1);
  }

  public function testEquipeNotNullEntity(): void
  {
    $portefeuille = new portefeuille();
    $portefeuille->setEquipe('mon equipe');
    $this->assertNotNull($portefeuille->getEquipe());
  }

  public function testListeInvalidBlankEntity(): void
  {
    $this->assertHasErrors($this->getEntity()->setListe([]), 1);
  }

  public function testListeNotNullEntity(): void
  {
    $portefeuille = new portefeuille();
    $portefeuille->setListe([]);
    $this->assertNotNull($portefeuille->getListe());
  }

  public function testCountAttribut(): void
  {
      $entity = $this->getEntity();
      $reflectionClass = new \ReflectionClass($entity);
      $nbAttributs = count($reflectionClass->getProperties());
      $this->assertEquals($nbAttributs, 6);
  }
}
