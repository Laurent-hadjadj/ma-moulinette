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
  private static $bugBlocker = 0;
  private static $bugCritical = 0;
  private static $bugMajor = 1843;
  private static $bugMinor = 29;
  private static $bugInfo = 0;
  private static $vulnerabilityBlocker = 0;
  private static $vulnerabilityCritical = 0;
  private static $vulnerabilityMajor = 0;
  private static $vulnerabilityMinor = 1427;
  private static $vulnerabilityInfo = 3;
  private static $codeSmellBlocker = 0;
  private static $codeSmellCritical = 1194;
  private static $codeSmellMajor = 13272;
  private static $codeSmellMinor = 8207;
  private static $codeSmellInfo = 13632;
  private static $frontend = 0;
  private static $frontendBugBlocker = 0;
  private static $frontendBugCritical = 0;
  private static $frontendBugMajor = 1232;
  private static $frontendBugMinor = 21;
  private static $frontendBugInfo = 0;
  private static $frontendVulnerabilityBlocker = 0;
  private static $frontendVulnerabilityCritical = 0;
  private static $frontendVulnerabilityMajor = 0;
  private static $frontendVulnerabilityMinor = 898;
  private static $frontendVulnerabilityInfo = 3;
  private static $frontendCodeSmellBlocker = 0;
  private static $frontendCodeSmellCritical = 554;
  private static $frontendCodeSmellMajor = 4441;
  private static $frontendCodeSmellMinor = 6603;
  private static $frontendCodeSmellInfo = 4009;
  private static $backend = 0;
  private static $backendBugBlocker = 0;
  private static $backendBugCritical = 0;
  private static $backendBugMajor = 611;
  private static $backendBugMinor = 8;
  private static $backendBugInfo = 0;
  private static $backendVulnerabilityBlocker = 0;
  private static $backendVulnerabilityCritical = 0;
  private static $backendVulnerabilityMajor = 0;
  private static $backendVulnerabilityMinor = 529;
  private static $backendVulnerabilityInfo = 0;
  private static $backendCodeSmellBlocker = 0;
  private static $backendCodeSmellCritical = 640;
  private static $backendCodeSmellMajor = 5559;
  private static $backendCodeSmellMinor = 3396;
  private static $backendCodeSmellInfo = 4155;
  private static $autre = 0;
  private static $autreBugBlocker = 0;
  private static $autreBugCritical = 0;
  private static $autreBugMajor = 0;
  private static $autreBugMinor = 0;
  private static $autreBugInfo = 0;
  private static $autreVulnerabilityBlocker = 0;
  private static $autreVulnerabilityCritical = 0;
  private static $autreVulnerabilityMajor = 0;
  private static $autreVulnerabilityMinor = 0;
  private static $autreVulnerabilityInfo = 0;
  private static $autreCodeSmellBlocker = 0;
  private static $autreCodeSmellCritical = 0;
  private static $autreCodeSmellMajor = 0;
  private static $autreCodeSmellMinor = 0;
  private static $autreCodeSmellInfo = 0;
  private static $inconnue = 0;
  private static $inconnueBugBlocker = 0;
  private static $inconnueBugCritical = 0;
  private static $inconnueBugMajor = 0;
  private static $inconnueBugMinor = 0;
  private static $inconnueBugInfo = 0;
  private static $inconnueVulnerabilityBlocker = 0;
  private static $inconnueVulnerabilityCritical = 0;
  private static $inconnueVulnerabilityMajor = 0;
  private static $inconnueVulnerabilityMinor = 0;
  private static $inconnueVulnerabilityInfo = 0;
  private static $inconnueCodeSmellBlocker = 0;
  private static $inconnueCodeSmellCritical = 0;
  private static $inconnueCodeSmellMajor = 0;
  private static $inconnueCodeSmellMinor = 1;
  private static $inconnueCodeSmellInfo = 43;
  private static $control = 'complet (100%)';
  private static $setup = 1739816022572;
  private static $modeCollecte = 'COLLECTE';
  private static $utilisateurCollecte = 'laurent.hadjadj@ma-petite-entreprise.fr';
  private static $dateEnregistrement = '2025-02-17 19:13:59+01';

  private function getEntity(): Repartition
  {
      return (new repartition())
      ->setMavenKey(static::$mavenKey)
      ->setName(static::$name)
      ->setBugBlocker(static::$bugBlocker)
      ->setBugCritical(static::$bugCritical)
      ->setBugMajor(static::$bugMajor)
      ->setBugMinor(static::$bugMinor)
      ->setBugInfo(static::$bugInfo)
      ->setVulnerabilityBlocker(static::$vulnerabilityBlocker)
      ->setVulnerabilityCritical(static::$vulnerabilityCritical)
      ->setVulnerabilityMajor(static::$vulnerabilityMajor)
      ->setVulnerabilityMinor(static::$vulnerabilityMinor)
      ->setVulnerabilityInfo(static::$vulnerabilityInfo)
      ->setCodeSmellBlocker(static::$codeSmellBlocker)
      ->setCodeSmellCritical(static::$codeSmellCritical)
      ->setCodeSmellMajor(static::$codeSmellMajor)
      ->setCodeSmellMinor(static::$codeSmellMinor)
      ->setCodeSmellInfo(static::$codeSmellInfo)
      ->setFrontend(static::$frontend)
      ->setFrontendBugBlocker(static::$frontendBugBlocker)
      ->setFrontendBugCritical(static::$frontendBugCritical)
      ->setFrontendBugMajor(static::$frontendBugMajor)
      ->setFrontendBugMinor(static::$frontendBugMinor)
      ->setFrontendBugInfo(static::$frontendBugInfo)
      ->setFrontendVulnerabilityBlocker(static::$frontendVulnerabilityBlocker)
      ->setFrontendVulnerabilityCritical(static::$frontendVulnerabilityCritical)
      ->setFrontendVulnerabilityMajor(static::$frontendVulnerabilityMajor)
      ->setFrontendVulnerabilityMinor(static::$frontendVulnerabilityMinor)
      ->setFrontendVulnerabilityInfo(static::$frontendVulnerabilityInfo)
      ->setFrontendCodeSmellBlocker(static::$frontendCodeSmellBlocker)
      ->setFrontendCodeSmellCritical(static::$frontendCodeSmellCritical)
      ->setFrontendCodeSmellMajor(static::$frontendCodeSmellMajor)
      ->setFrontendCodeSmellMinor(static::$frontendCodeSmellMinor)
      ->setFrontendCodeSmellInfo(static::$frontendCodeSmellInfo)
      ->setBackend(static::$backend)
      ->setBackendBugBlocker(static::$backendBugBlocker)
      ->setBackendBugCritical(static::$backendBugCritical)
      ->setBackendBugMajor(static::$backendBugMajor)
      ->setBackendBugMinor(static::$backendBugMinor)
      ->setBackendBugInfo(static::$backendBugInfo)
      ->setBackendVulnerabilityBlocker(static::$backendVulnerabilityBlocker)
      ->setBackendVulnerabilityCritical(static::$backendVulnerabilityCritical)
      ->setBackendVulnerabilityMajor(static::$backendVulnerabilityMajor)
      ->setBackendVulnerabilityMinor(static::$backendVulnerabilityMinor)
      ->setBackendVulnerabilityInfo(static::$backendVulnerabilityInfo)
      ->setBackendCodeSmellBlocker(static::$backendCodeSmellBlocker)
      ->setBackendCodeSmellCritical(static::$backendCodeSmellCritical)
      ->setBackendCodeSmellMajor(static::$backendCodeSmellMajor)
      ->setBackendCodeSmellMinor(static::$backendCodeSmellMinor)
      ->setBackendCodeSmellInfo(static::$backendCodeSmellInfo)
      ->setAutre(static::$autre)
      ->setAutreBugBlocker(static::$autreBugBlocker)
      ->setAutreBugCritical(static::$autreBugCritical)
      ->setAutreBugMajor(static::$autreBugMajor)
      ->setAutreBugMinor(static::$autreBugMinor)
      ->setAutreBugInfo(static::$autreBugInfo)
      ->setAutreVulnerabilityBlocker(static::$autreVulnerabilityBlocker)
      ->setAutreVulnerabilityCritical(static::$autreVulnerabilityCritical)
      ->setAutreVulnerabilityMajor(static::$autreVulnerabilityMajor)
      ->setAutreVulnerabilityMinor(static::$autreVulnerabilityMinor)
      ->setAutreVulnerabilityInfo(static::$autreVulnerabilityInfo)
      ->setAutreCodeSmellBlocker(static::$autreCodeSmellBlocker)
      ->setAutreCodeSmellCritical(static::$autreCodeSmellCritical)
      ->setAutreCodeSmellMajor(static::$autreCodeSmellMajor)
      ->setAutreCodeSmellMinor(static::$autreCodeSmellMinor)
      ->setAutreCodeSmellInfo(static::$autreCodeSmellInfo)
      ->setInconnue(static::$inconnue)
      ->setInconnueBugBlocker(static::$inconnueBugBlocker)
      ->setInconnueBugCritical(static::$inconnueBugCritical)
      ->setInconnueBugMajor(static::$inconnueBugMajor)
      ->setInconnueBugMinor(static::$inconnueBugMinor)
      ->setInconnueBugInfo(static::$inconnueBugInfo)
      ->setInconnueVulnerabilityBlocker(static::$inconnueVulnerabilityBlocker)
      ->setInconnueVulnerabilityCritical(static::$inconnueVulnerabilityCritical)
      ->setInconnueVulnerabilityMajor(static::$inconnueVulnerabilityMajor)
      ->setInconnueVulnerabilityMinor(static::$inconnueVulnerabilityMinor)
      ->setInconnueVulnerabilityInfo(static::$inconnueVulnerabilityInfo)
      ->setInconnueCodeSmellBlocker(static::$inconnueCodeSmellBlocker)
      ->setInconnueCodeSmellCritical(static::$inconnueCodeSmellCritical)
      ->setInconnueCodeSmellMajor(static::$inconnueCodeSmellMajor)
      ->setInconnueCodeSmellMinor(static::$inconnueCodeSmellMinor)
      ->setInconnueCodeSmellInfo(static::$inconnueCodeSmellInfo)
      ->setSetup(static::$setup)
      ->setControl(static::$control)
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
    $this->assertHasErrors($this->getEntity()->setModeCollecte(''), 0);
    $this->assertHasErrors($this->getEntity()->setUtilisateurCollecte(''), 0);
  }

  public function testValidIntegerEntity(): void
  {
    $this->assertHasErrors($this->getEntity()->setSetup(-1), 0);
    $this->assertHasErrors($this->getEntity()->setBugBlocker(-1), 0);
    $this->assertHasErrors($this->getEntity()->setBugCritical(-1), 0);
    $this->assertHasErrors($this->getEntity()->setBugMajor(-1), 0);
    $this->assertHasErrors($this->getEntity()->setBugMinor(-1), 0);
    $this->assertHasErrors($this->getEntity()->setBugInfo(-1), 0);
    $this->assertHasErrors($this->getEntity()->setVulnerabilityBlocker(-1), 0);
    $this->assertHasErrors($this->getEntity()->setVulnerabilityCritical(-1), 0);
    $this->assertHasErrors($this->getEntity()->setVulnerabilityMajor(-1), 0);
    $this->assertHasErrors($this->getEntity()->setVulnerabilityMinor(-1), 0);
    $this->assertHasErrors($this->getEntity()->setVulnerabilityInfo(-1), 0);
    $this->assertHasErrors($this->getEntity()->setCodeSmellBlocker(-1), 0);
    $this->assertHasErrors($this->getEntity()->setCodeSmellCritical(-1), 0);
    $this->assertHasErrors($this->getEntity()->setCodeSmellMajor(-1), 0);
    $this->assertHasErrors($this->getEntity()->setCodeSmellMinor(-1), 0);
    $this->assertHasErrors($this->getEntity()->setCodeSmellInfo(-1), 0);
    $this->assertHasErrors($this->getEntity()->setFrontend(-1), 0);
    $this->assertHasErrors($this->getEntity()->setFrontendBugBlocker(-1), 0);
    $this->assertHasErrors($this->getEntity()->setFrontendBugCritical(-1), 0);
    $this->assertHasErrors($this->getEntity()->setFrontendBugMajor(-1), 0);
    $this->assertHasErrors($this->getEntity()->setFrontendBugMinor(-1), 0);
    $this->assertHasErrors($this->getEntity()->setFrontendBugInfo(-1), 0);
    $this->assertHasErrors($this->getEntity()->setFrontendVulnerabilityBlocker(-1), 0);
    $this->assertHasErrors($this->getEntity()->setFrontendVulnerabilityCritical(-1), 0);
    $this->assertHasErrors($this->getEntity()->setFrontendVulnerabilityMajor(-1), 0);
    $this->assertHasErrors($this->getEntity()->setFrontendVulnerabilityMinor(-1), 0);
    $this->assertHasErrors($this->getEntity()->setFrontendVulnerabilityInfo(-1), 0);
    $this->assertHasErrors($this->getEntity()->setFrontendCodeSmellBlocker(-1), 0);
    $this->assertHasErrors($this->getEntity()->setFrontendCodeSmellCritical(-1), 0);
    $this->assertHasErrors($this->getEntity()->setFrontendCodeSmellMajor(-1), 0);
    $this->assertHasErrors($this->getEntity()->setFrontendCodeSmellMinor(-1), 0);
    $this->assertHasErrors($this->getEntity()->setFrontendCodeSmellInfo(-1), 0);
    $this->assertHasErrors($this->getEntity()->setBackend(-1), 0);
    $this->assertHasErrors($this->getEntity()->setBackendBugBlocker(-1), 0);
    $this->assertHasErrors($this->getEntity()->setBackendBugCritical(-1), 0);
    $this->assertHasErrors($this->getEntity()->setBackendBugMajor(-1), 0);
    $this->assertHasErrors($this->getEntity()->setBackendBugMinor(-1), 0);
    $this->assertHasErrors($this->getEntity()->setBackendBugInfo(-1), 0);
    $this->assertHasErrors($this->getEntity()->setBackendVulnerabilityBlocker(-1), 0);
    $this->assertHasErrors($this->getEntity()->setBackendVulnerabilityCritical(-1), 0);
    $this->assertHasErrors($this->getEntity()->setBackendVulnerabilityMajor(-1), 0);
    $this->assertHasErrors($this->getEntity()->setBackendVulnerabilityMinor(-1), 0);
    $this->assertHasErrors($this->getEntity()->setBackendVulnerabilityInfo(-1), 0);
    $this->assertHasErrors($this->getEntity()->setBackendCodeSmellBlocker(-1), 0);
    $this->assertHasErrors($this->getEntity()->setBackendCodeSmellCritical(-1), 0);
    $this->assertHasErrors($this->getEntity()->setBackendCodeSmellMajor(-1), 0);
    $this->assertHasErrors($this->getEntity()->setBackendCodeSmellMinor(-1), 0);
    $this->assertHasErrors($this->getEntity()->setBackendCodeSmellInfo(-1), 0);
    $this->assertHasErrors($this->getEntity()->setAutre(-1), 0);
    $this->assertHasErrors($this->getEntity()->setAutreBugBlocker(-1), 0);
    $this->assertHasErrors($this->getEntity()->setAutreBugCritical(-1), 0);
    $this->assertHasErrors($this->getEntity()->setAutreBugMajor(-1), 0);
    $this->assertHasErrors($this->getEntity()->setAutreBugMinor(-1), 0);
    $this->assertHasErrors($this->getEntity()->setAutreBugInfo(-1), 0);
    $this->assertHasErrors($this->getEntity()->setAutreVulnerabilityBlocker(-1), 0);
    $this->assertHasErrors($this->getEntity()->setAutreVulnerabilityCritical(-1), 0);
    $this->assertHasErrors($this->getEntity()->setAutreVulnerabilityMajor(-1), 0);
    $this->assertHasErrors($this->getEntity()->setAutreVulnerabilityMinor(-1), 0);
    $this->assertHasErrors($this->getEntity()->setAutreVulnerabilityInfo(-1), 0);
    $this->assertHasErrors($this->getEntity()->setAutreCodeSmellBlocker(-1), 0);
    $this->assertHasErrors($this->getEntity()->setAutreCodeSmellCritical(-1), 0);
    $this->assertHasErrors($this->getEntity()->setAutreCodeSmellMajor(-1), 0);
    $this->assertHasErrors($this->getEntity()->setAutreCodeSmellMinor(-1), 0);
    $this->assertHasErrors($this->getEntity()->setAutreCodeSmellInfo(-1), 0);
    $this->assertHasErrors($this->getEntity()->setInconnue(-1), 0);
    $this->assertHasErrors($this->getEntity()->setInconnueBugBlocker(-1), 0);
    $this->assertHasErrors($this->getEntity()->setInconnueBugCritical(-1), 0);
    $this->assertHasErrors($this->getEntity()->setInconnueBugMajor(-1), 0);
    $this->assertHasErrors($this->getEntity()->setInconnueBugMinor(-1), 0);
    $this->assertHasErrors($this->getEntity()->setInconnueBugInfo(-1), 0);
    $this->assertHasErrors($this->getEntity()->setInconnueVulnerabilityBlocker(-1), 0);
    $this->assertHasErrors($this->getEntity()->setInconnueVulnerabilityCritical(-1), 0);
    $this->assertHasErrors($this->getEntity()->setInconnueVulnerabilityMajor(-1), 0);
    $this->assertHasErrors($this->getEntity()->setInconnueVulnerabilityMinor(-1), 0);
    $this->assertHasErrors($this->getEntity()->setInconnueVulnerabilityInfo(-1), 0);
    $this->assertHasErrors($this->getEntity()->setInconnueCodeSmellBlocker(-1), 0);
    $this->assertHasErrors($this->getEntity()->setInconnueCodeSmellCritical(-1), 0);
    $this->assertHasErrors($this->getEntity()->setInconnueCodeSmellMajor(-1), 0);
    $this->assertHasErrors($this->getEntity()->setInconnueCodeSmellMinor(-1), 0);
    $this->assertHasErrors($this->getEntity()->setInconnueCodeSmellInfo(-1), 0);
  }

  public function testCountAttribut(): void
  {
      $entity = $this->getEntity();
      $reflectionClass = new \ReflectionClass($entity);
      $nbAttributs = count($reflectionClass->getProperties());
      $this->assertEquals($nbAttributs, 87, "Le nombre d'attribut doit être de 87.");
  }
}
