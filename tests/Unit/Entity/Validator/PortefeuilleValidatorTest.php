<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Tests\Unit\Entity\Validator;

use App\Entity\Portefeuille;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;

class PortefeuilleValidatorTest extends KernelTestCase
{

  private static string $portefeuille = 'MES PROJETS';
  private static string $groupeFonctionnel = 'MA PETITE ENTREPRISE';
  private static $liste =  ['fr.ma-petite-entreprise:ma-moulinette'];
  private static string $dateModification = '2024-03-26 14:46:38+01';
  private static string $dateEnregistrement = '2024-03-25 12:26:58+01';

  private function getEntity(): Portefeuille
  {
      return (new portefeuille())
      ->setPortefeuille(self::$portefeuille)
      ->setGroupeFonctionnel(self::$groupeFonctionnel)
      ->setListe(self::$liste)
      ->setDateModification(new \DateTime(self::$dateModification))
      ->setDateEnregistrement(new \DateTimeImmutable(self::$dateEnregistrement));
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

  public function testPortefeuilleInvalidBlankEntity(): void
  {
    $this->assertHasErrors($this->getEntity()->setPortefeuille(''), 1);
  }

  public function testPortefeuilleNotNullEntity(): void
  {
    $portefeuille = new portefeuille();
    $portefeuille->setPortefeuille('mon portefeuille');
    $this->assertNotNull($portefeuille->getPortefeuille());
  }

  public function testGroupeFonctionnelInvalidBlankEntity(): void
  {
    $this->assertHasErrors($this->getEntity()->setGroupeFonctionnel(''), 1);
  }

  public function testGroupeFonctionnelNotNullEntity(): void
  {
    $portefeuille = new portefeuille();
    $portefeuille->setGroupeFonctionnel('mon groupe fonctionnel');
    $this->assertNotNull($portefeuille->getGroupeFonctionnel());
  }

  public function testListeValidNullEntity(): void
  {
    $this->assertHasErrors($this->getEntity()->setListe([]), 0);
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
