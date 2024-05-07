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

  public static $id = 1;
  public static $init = 1;
  public static $avatar = 'chiffre/01.png';
  public static $prenom = 'Laurent';
  public static $nom = 'HADJADJ';
  public static $courriel = 'laurent.hadjadj@ma-petite-entreprise.fr';
  public static $password = '$2y$13$6n72QhYwz.iufebkV.XaAOO4IOm3zOYcfzPUmal.jDTs8/QFq1p4K';
  public static $actif = 1;
  public static $roles = ["ROLE_GESTIONNAIRE"];
  public static $equipe = [];
  public static $preference = ['{
    "statut":{"projet":false,"favori":false,"version":false,"bookmark":false},
    "projet":[],"favori":[],"version":[],"bookmark":[]}'];
  public static $dateModification = '1981-01-01 00:00:00';
  public static $dateEnregistrement = '1980-01-01 00:00:00';

  /**
   * [Description for getEntity]
   * Prépare le jeu de données
   *
   * @return Utilisateur
   *
   * Created at: 02/05/2024 20:44:25 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function getEntity(): Utilisateur
  {
    return (new utilisateur())
      ->setId(static::$id)
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

  /**
   * [Description for assertHasErrors]
   *
   * @param Utilisateur $entity
   * @param int $number
   *
   * @return [type]
   *
   * Created at: 02/05/2024 22:24:59 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
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

  /**
   * [Description for testValidEntity]
   * Vérification de l'entity
   *
   * @return void
   *
   * Created at: 03/05/2024 16:38:53 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testValidEntity(): void
  {
    $this->assertHasErrors($this->getEntity(), 0);
  }


  /**
   * [Description for testInvalidBlankEntity]
   * Vérification des attributs qui ne peuvent pas être null/vide
   * @return void
   *
   * Created at: 03/05/2024 16:38:43 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testInvalidBlankEntity(): void
  {
    $this->assertHasErrors($this->getEntity()->setId(''), 1);
    $this->assertHasErrors($this->getEntity()->setPrenom(''), 1);
    $this->assertHasErrors($this->getEntity()->setNom(''), 1);
    $this->assertHasErrors($this->getEntity()->setCourriel(''), 1);
    $this->assertHasErrors($this->getEntity()->setPassword(''), 1);
    $this->assertHasErrors($this->getEntity()->setPreference([]), 1);
  }

  /**
   * [Description for testValidBlankEntity]
   * Vérification des attributs qui peuvent être null/vide
   *
   * @return void
   *
   * Created at: 03/05/2024 16:40:24 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testValidBlankEntity(): void
  {
    $this->assertHasErrors($this->getEntity()->setAvatar(''), 0);
    $this->assertHasErrors($this->getEntity()->setRoles([]), 0);
    $this->assertHasErrors($this->getEntity()->setEquipe([]), 0);
    $this->assertHasErrors($this->getEntity()->setActif(''), 0);
  }
  /**
   * [Description for testUtilisateurCountAttribut]
   * On vérifie le nombre d'attribut
   *
   * @return void
   *
   * Created at: 14/02/2023, 15:19:00 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
    public function testCountAttribut(): void
    {
        $entity = $this->getEntity();
        $reflectionClass = new \ReflectionClass($entity);
        $nbAttributs = count($reflectionClass->getProperties());
        $this->assertEquals($nbAttributs, 14);
    }
}
