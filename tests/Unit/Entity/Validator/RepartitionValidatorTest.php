<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2024.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Tests\Unit\Entity\Validator;

use App\Entity\Repartition;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * [Description RepartitionValidatorTest]
 */
class RepartitionValidatorTest extends KernelTestCase
{
    private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
    private static $name = 'ma-moulinette';
    private static $component = '/controller/auth/reset-password.php';
    private static $type = 'bug';
    private static $severity = 'medium';
    private static $setup = '1707664293645';
    private static $modeCollecte = 'TRAITEMENT MANUEL';
    private static $utilisateurCollecte = 'laurent.hadjadj@ma-petite-entreprise.fr';
    private static $dateEnregistrement = '2024-04-12 16:23:11+01';

  private function getEntity(): Repartition
  {
      return (new repartition())
      ->setMavenKey(static::$mavenKey)
      ->setName(static::$name)
      ->setComponent(static::$component)
      ->setType(static::$type)
      ->setSeverity(static::$severity)
      ->setSetup(static::$setup)
      ->setModeCollecte(static::$modeCollecte)
      ->setUtilisateurCollecte(static::$utilisateurCollecte)
      ->setDateEnregistrement(new \DateTimeImmutable(static::$dateEnregistrement));
  }

  public function assertHasErrors(Repartition $entity, int $number = 0): void
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
    $this->assertHasErrors($this->getEntity()->setName(''), 1);
    $this->assertHasErrors($this->getEntity()->setComponent(''), 1);
    $this->assertHasErrors($this->getEntity()->setType(''), 1);
    $this->assertHasErrors($this->getEntity()->setSeverity(''), 1);
    $this->assertHasErrors($this->getEntity()->setSetup(''), 1);
    $this->assertHasErrors($this->getEntity()->setModeCollecte(''), 0);
    $this->assertHasErrors($this->getEntity()->setUtilisateurCollecte(''), 0);
  }

  public function testCountAttribut(): void
  {
      $entity = $this->getEntity();
      $reflectionClass = new \ReflectionClass($entity);
      $nbAttributs = count($reflectionClass->getProperties());
      $this->assertEquals($nbAttributs, 10);
  }
}
