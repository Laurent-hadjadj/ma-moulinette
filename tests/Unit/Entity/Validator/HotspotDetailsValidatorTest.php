<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2026
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Tests\Unit\Entity\Validator;

use App\Entity\HotspotDetails;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * [Description HotspotDetailsValidatorTest]
 */
class HotspotDetailsValidatorTest extends KernelTestCase
{

  private static string $mavenKey = 'fr.ma-moulinette:ma-moulinette';
  private static string $version = '1.2.0-RELEASE';
  private static string $dateVersion = '2024-07-10 15:26:07+02';
  private static string $securityCategory = 'dos';
  private static string $ruleKey = 'typescript:S5852';
  private static string $ruleName = 'Using slow regular expressions is security-sensitive';
  private static string $severity = 'MEDIUM';
  private static string $status = 'TO_REVIEW';
  private string $resolution = 'Todo';
  private static int $niveau = 2;
  private static int $frontend = 1;
  private static int $backend = 1;
  private static int $autre = 0;
  private static string $fileName = 'service-worker-network-first.ts';
  private static string $filePath = 'ma-moulinette/angular/src/service-worker-network-first.ts';
  private static int $line = 60;
  private static string $message = 'Make sure the regex used here, which is vulnerable to super-linear runtime due to backtracking, cannot lead to denial of service.';
  private static string $key = 'AZCc06XbgfifxdiJPzw2';
  private static string $modeCollecte = 'TRAITEMENT AUTOMATIQUE';
  private static string $utilisateurCollecte = 'laurent.hadjadj@ma-moulinette.fr';
  private static string $dateEnregistrement = '2024-03-26 14:46:38+02';

  private function getEntity(): HotspotDetails
  {
    return (new hotspotDetails())
      ->setMavenKey(self::$mavenKey)
      ->setVersion(self::$version)
      ->setDateVersion(new \DateTimeImmutable(self::$dateVersion))
      ->setSecurityCategory(self::$securityCategory)
      ->setRuleKey(self::$ruleKey)
      ->setRuleName(self::$ruleName)
      ->setSeverity(self::$severity)
      ->setStatus(self::$status)
      ->setResolution($this->resolution)
      ->setNiveau(self::$niveau)
      ->setFrontend(self::$frontend)
      ->setBackend(self::$backend)
      ->setAutre(self::$autre)
      ->setFileName(self::$fileName)
      ->setFilePath(self::$filePath)
      ->setLine(self::$line)
      ->setMessage(self::$message)
      ->setHotspotKey(self::$key)
      ->setModeCollecte(self::$modeCollecte)
      ->setUtilisateurCollecte(self::$utilisateurCollecte)
      ->setDateEnregistrement(new \DateTimeImmutable(self::$dateEnregistrement));
  }

  public function assertHasErrors(HotspotDetails $entity, int $number = 0): void
  {
    self::bootKernel();
    $container = static::getContainer();
    $errors = $container->get('validator')->validate($entity);
    $messages = [];
    /** @var ConstraintViolation $error */
    foreach ($errors as $error) {
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
    $this->assertHasErrors($this->getEntity()->setStatus(''), 1);
    $this->assertHasErrors($this->getEntity()->setResolution(''), 0);
    $this->assertHasErrors($this->getEntity()->setFileName(''), 1);
    $this->assertHasErrors($this->getEntity()->setFilePath(''), 1);
    $this->assertHasErrors($this->getEntity()->setMessage(''), 1);
    $this->assertHasErrors($this->getEntity()->setHotspotKey(''), 1);
    $this->assertHasErrors($this->getEntity()->setModeCollecte(''), 0);
    $this->assertHasErrors($this->getEntity()->setUtilisateurCollecte(''), 0);
  }

  public function testValidIntegerEntity(): void
  {
    // v2.0.0 : valeurs limites BASSES acceptees par PositiveOrZero (>= 0)
    $this->assertHasErrors($this->getEntity()->setLine(0), 0);
    $this->assertHasErrors($this->getEntity()->setNiveau(0), 0);
    $this->assertHasErrors($this->getEntity()->setFrontend(0), 0);
    $this->assertHasErrors($this->getEntity()->setBackend(0), 0);
    $this->assertHasErrors($this->getEntity()->setAutre(0), 0);
  }

  /**
   * v2.0.0 : valeurs negatives REJETEES par les contraintes PositiveOrZero / Positive / Range.
   */
  public function testInvalidIntegerEntity(): void
  {
    $this->assertHasErrors($this->getEntity()->setLine(-1), 1);
    $this->assertHasErrors($this->getEntity()->setNiveau(-1), 1);
    $this->assertHasErrors($this->getEntity()->setFrontend(-1), 1);
    $this->assertHasErrors($this->getEntity()->setBackend(-1), 1);
    $this->assertHasErrors($this->getEntity()->setAutre(-1), 1);
  }

  public function testCountAttribut(): void
  {
    $entity = $this->getEntity();
    $reflectionClass = new \ReflectionClass($entity);
    $nbAttributs = count($reflectionClass->getProperties());
    $this->assertEquals($nbAttributs, 22);
  }
}
