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

namespace App\Tests\Unit\Entity\Validator;

use App\Entity\Hotspots;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * [Description HotspotsValidatorTest]
 */
class HotspotsValidatorTest extends KernelTestCase
{

  private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
  private static $version = '1.2.0-RELEASE';
  private static $dateVersion = '2024-07-10 15:26:07+02';
  private static $hotspotKey = 'AZCc06XbgfifxdiJPzw6';
  private static $securityCategory = 'dos';
  private static $ruleKey = 'typescript:S5852';
  private static $probability = 'MEDIUM';
  private static $status = 'TO_REVIEW';
  private static $resolution = 'Todo';
  private static $niveau = 2;
  private static $modeCollecte = 'TRAITEMENT MANUEL';
  private static $utilisateurCollecte = 'laurent.hadjadj@ma-petite-entreprise.fr';
  private static $dateEnregistrement = '2024-04-12 16:23:11+01';

  private function getEntity(): Hotspots
  {
      return (new hotspots())
      ->setMavenKey(static::$mavenKey)
      ->setVersion(static::$version)
      ->setDateVersion(new \DateTimeImmutable(static::$dateVersion))
      ->setHotspotKey(static::$hotspotKey)
      ->setSecurityCategory(static::$securityCategory)
      ->setRuleKey(static::$ruleKey)
      ->setProbability(static::$probability)
      ->setStatus(static::$status)
      ->setResolution(static::$resolution)
      ->setNiveau(static::$niveau)
      ->setModeCollecte(static::$modeCollecte)
      ->setUtilisateurCollecte(static::$utilisateurCollecte)
      ->setDateEnregistrement(new \DateTimeImmutable(static::$dateEnregistrement));
  }

  public function assertHasErrors(Hotspots $entity, int $number = 0): void
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
    $this->assertHasErrors($this->getEntity()->setVersion(''), 1);
    $this->assertHasErrors($this->getEntity()->setHotspotKey(''), 1);
    $this->assertHasErrors($this->getEntity()->setSecurityCategory(''), 1);
    $this->assertHasErrors($this->getEntity()->setRuleKey(''), 1);
    $this->assertHasErrors($this->getEntity()->setProbability(''), 1);
    $this->assertHasErrors($this->getEntity()->setStatus(''), 1);
    $this->assertHasErrors($this->getEntity()->setResolution(''), 0);
    $this->assertHasErrors($this->getEntity()->setModeCollecte(''), 0);
    $this->assertHasErrors($this->getEntity()->setUtilisateurCollecte(''), 0);
  }

  public function testValidIntegerEntity(): void
  {
    $this->assertHasErrors($this->getEntity()->setNiveau(-1), 0);
  }

  public function testCountAttribut(): void
  {
      $entity = $this->getEntity();
      $reflectionClass = new \ReflectionClass($entity);
      $nbAttributs = count($reflectionClass->getProperties());
      $this->assertEquals($nbAttributs, 14);
  }
}
