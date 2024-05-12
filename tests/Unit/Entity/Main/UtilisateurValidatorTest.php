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

use App\Entity\Main\Utilisateur;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * [Description UtilisateurKernelTest]
 */
class UtilisateurValidatorTest extends KernelTestCase
{

  public static $init = 1;
  public static $avatar = 'chiffre/01.png';
  public static $prenom = 'Laurent';
  public static $nom = 'HADJADJ';
  public static $courriel = 'laurent.hadjadj@ma-petite-entreprise.fr';
  public static $password = '$2y$13$6n72QhYwz.iufebkV.XaAOO4IOm3zOYcfzPUmal.jDTs8/QFq1p4K';
  public static $actif = true;
  public static $roles = ["ROLE_GESTIONNAIRE"];
  public static $equipe = [];
  public static $preference = ['{
    "statut":{"projet":false,"favori":false,"version":false,"bookmark":false},
    "projet":[],"favori":[],"version":[],"bookmark":[]}'];
  public static $dateModification = '1981-01-01 00:00:00';
  public static $dateEnregistrement = '1980-01-01 00:00:00';

  public function getEntity(): Utilisateur
  {
    return (new utilisateur())
      ->setInit(static::$init)
      ->setAvatar(static::$avatar)
      ->setPrenom(static::$prenom)
      ->setNom(static::$nom)
      ->setCourriel(static::$courriel)
      ->setPassword(static::$password)
      ->setActif(static::$actif)
      ->setRoles(static::$roles)
      ->setEquipe(static::$equipe)
      ->setPreference(static::$preference)
      ->setDateModification(new \DateTime(static::$dateModification))
      ->setDateEnregistrement(new \DateTime(static::$dateEnregistrement));
  }


  public function assertHasErrors(Utilisateur $entity, int $number = 0)
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
    $this->assertHasErrors($this->getEntity()->setPrenom(''), 1);
    $this->assertHasErrors($this->getEntity()->setNom(''), 1);
    $this->assertHasErrors($this->getEntity()->setCourriel(''), 1);
    $this->assertHasErrors($this->getEntity()->setPassword(''), 1);
    $this->assertHasErrors($this->getEntity()->setPreference([]), 1);
  }

  public function testValidBlankEntity(): void
  {
    $this->assertHasErrors($this->getEntity()->setAvatar(''), 0);
    $this->assertHasErrors($this->getEntity()->setRoles([]), 0);
    $this->assertHasErrors($this->getEntity()->setEquipe([]), 0);
  }

  public function testValidIntegerEntity(): void
  {
    $this->assertHasErrors($this->getEntity()->setInit(-1), 0);
  }

  public function testValidBooleanEntity(): void
  {
    $this->assertHasErrors($this->getEntity()->setActif(true), 0);
  }

  public function testCountAttribut(): void
  {
      $entity = $this->getEntity();
      $reflectionClass = new \ReflectionClass($entity);
      $nbAttributs = count($reflectionClass->getProperties());
      $this->assertEquals($nbAttributs, 14);
  }
}
