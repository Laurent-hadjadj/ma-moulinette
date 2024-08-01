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

use App\Entity\HotspotDetails;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * [Description HotspotDetailsValidatorTest]
 */
class HotspotDetailsValidatorTest extends KernelTestCase
{

  private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
  private static $version = '1.2.0-RELEASE';
  private static $dateVersion = '2024-07-10 15:26:07+02';
  private static $securityCategory = 'dos';
  private static $ruleKey = 'typescript:S5852';
  private static $ruleName = 'Using slow regular expressions is security-sensitive';
  private static $severity = 'MEDIUM';
  private static $status = 'TO_REVIEW';
  private string $resolution = 'Todo';
  private static $niveau = 2;
  private static $frontend = 1;
  private static $backend = 1;
  private static $autre= 0;
  private static $fileName = 'service-worker-network-first.ts';
  private static $filePath = 'ma-moulinette/angular/src/service-worker-network-first.ts';
  private static $line = 60;
  private static $message = 'Make sure the regex used here, which is vulnerable to super-linear runtime due to backtracking, cannot lead to denial of service.';
  private static $key = 'AZCc06XbgfifxdiJPzw2';
  private static $modeCollecte = 'TRAITEMENT AUTOMATIQUE';
  private static $utilisateurCollecte = 'laurent.hadjadj@ma-petite-entreprise.fr';
  private static $dateEnregistrement = '2024-03-26 14:46:38+02';

  private function getEntity(): HotspotDetails
  {
      return (new hotspotDetails())
        ->setMavenKey(static::$mavenKey)
        ->setVersion(static::$version)
        ->setDateVersion(new \DateTimeImmutable(static::$dateVersion))
        ->setSecurityCategory(static::$securityCategory)
        ->setRuleKey(static::$ruleKey)
        ->setRuleName(static::$ruleName)
        ->setSeverity(static::$severity)
        ->setStatus(static::$status)
        ->setResolution($this->resolution)
        ->setNiveau(static::$niveau)
        ->setFrontend(static::$frontend)
        ->setBackend(static::$backend)
        ->setAutre(static::$autre)
        ->setFileName(static::$fileName)
        ->setFilePath(static::$filePath)
        ->setLine(static::$line)
        ->setMessage(static::$message)
        ->setKey(static::$key)
        ->setModeCollecte(static::$modeCollecte)
        ->setUtilisateurCollecte(static::$utilisateurCollecte)
        ->setDateEnregistrement(new \DateTimeImmutable(static::$dateEnregistrement));
  }

  public function assertHasErrors(HotspotDetails $entity, int $number = 0): void
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
    $this->assertHasErrors($this->getEntity()->setSecurityCategory(''), 1);
    $this->assertHasErrors($this->getEntity()->setRuleKey(''), 1);
    $this->assertHasErrors($this->getEntity()->setRuleName(''), 1);
    $this->assertHasErrors($this->getEntity()->setSeverity(''), 1);
    $this->assertHasErrors($this->getEntity()->setStatus(''), 1);$this->assertHasErrors($this->getEntity()->setResolution(''), 0);$this->assertHasErrors($this->getEntity()->setFileName(''), 1);
    $this->assertHasErrors($this->getEntity()->setFilePath(''), 1);
    $this->assertHasErrors($this->getEntity()->setMessage(''), 1);
    $this->assertHasErrors($this->getEntity()->setKey(''), 1);
    $this->assertHasErrors($this->getEntity()->setModeCollecte(''), 0);
    $this->assertHasErrors($this->getEntity()->setUtilisateurCollecte(''), 0);
  }

  public function testValidIntegerEntity(): void
  {
    $this->assertHasErrors($this->getEntity()->setLine(-1), 0);
    $this->assertHasErrors($this->getEntity()->setNiveau(-1), 0);
    $this->assertHasErrors($this->getEntity()->setFrontend(-1), 0);
    $this->assertHasErrors($this->getEntity()->setBackend(-1), 0);
    $this->assertHasErrors($this->getEntity()->setAutre(-1), 0);
  }

  public function testCountAttribut(): void
  {
      $entity = $this->getEntity();
      $reflectionClass = new \ReflectionClass($entity);
      $nbAttributs = count($reflectionClass->getProperties());
      $this->assertEquals($nbAttributs, 22);
  }
}
