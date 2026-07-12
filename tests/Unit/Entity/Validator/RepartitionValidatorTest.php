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

use App\Entity\Repartition;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * [Description RepartitionValidatorTest]
 */
class RepartitionValidatorTest extends KernelTestCase
{
  private static string $mavenKey = 'fr.ma-moulinette:ma-moulinette';
  private static string $name = 'ma-moulinette';
  private static int $bugBlocker = 0;
  private static int $bugCritical = 0;
  private static int $bugMajor = 1843;
  private static int $bugMinor = 29;
  private static int $bugInfo = 0;
  private static int $vulnerabilityBlocker = 0;
  private static int $vulnerabilityCritical = 0;
  private static int $vulnerabilityMajor = 0;
  private static int $vulnerabilityMinor = 1427;
  private static int $vulnerabilityInfo = 3;
  private static int $codeSmellBlocker = 0;
  private static int $codeSmellCritical = 1194;
  private static int $codeSmellMajor = 13272;
  private static int $codeSmellMinor = 8207;
  private static int $codeSmellInfo = 13632;
  private static int $frontend = 0;
  private static int $frontendBugBlocker = 0;
  private static int $frontendBugCritical = 0;
  private static int $frontendBugMajor = 1232;
  private static int $frontendBugMinor = 21;
  private static int $frontendBugInfo = 0;
  private static int $frontendVulnerabilityBlocker = 0;
  private static int $frontendVulnerabilityCritical = 0;
  private static int $frontendVulnerabilityMajor = 0;
  private static int $frontendVulnerabilityMinor = 898;
  private static int $frontendVulnerabilityInfo = 3;
  private static int $frontendCodeSmellBlocker = 0;
  private static int $frontendCodeSmellCritical = 554;
  private static int $frontendCodeSmellMajor = 4441;
  private static int $frontendCodeSmellMinor = 6603;
  private static int $frontendCodeSmellInfo = 4009;
  private static int $backend = 0;
  private static int $backendBugBlocker = 0;
  private static int $backendBugCritical = 0;
  private static int $backendBugMajor = 611;
  private static int $backendBugMinor = 8;
  private static int $backendBugInfo = 0;
  private static int $backendVulnerabilityBlocker = 0;
  private static int $backendVulnerabilityCritical = 0;
  private static int $backendVulnerabilityMajor = 0;
  private static int $backendVulnerabilityMinor = 529;
  private static int $backendVulnerabilityInfo = 0;
  private static int $backendCodeSmellBlocker = 0;
  private static int $backendCodeSmellCritical = 640;
  private static int $backendCodeSmellMajor = 5559;
  private static int $backendCodeSmellMinor = 3396;
  private static int $backendCodeSmellInfo = 4155;
  private static int $autre = 0;
  private static int $autreBugBlocker = 0;
  private static int $autreBugCritical = 0;
  private static int $autreBugMajor = 0;
  private static int $autreBugMinor = 0;
  private static int $autreBugInfo = 0;
  private static int $autreVulnerabilityBlocker = 0;
  private static int $autreVulnerabilityCritical = 0;
  private static int $autreVulnerabilityMajor = 0;
  private static int $autreVulnerabilityMinor = 0;
  private static int $autreVulnerabilityInfo = 0;
  private static int $autreCodeSmellBlocker = 0;
  private static int $autreCodeSmellCritical = 0;
  private static int $autreCodeSmellMajor = 0;
  private static int $autreCodeSmellMinor = 0;
  private static int $autreCodeSmellInfo = 0;
  private static int $inconnu = 0;
  private static int $inconnuBugBlocker = 0;
  private static int $inconnuBugCritical = 0;
  private static int $inconnuBugMajor = 0;
  private static int $inconnuBugMinor = 0;
  private static int $inconnuBugInfo = 0;
  private static int $inconnuVulnerabilityBlocker = 0;
  private static int $inconnuVulnerabilityCritical = 0;
  private static int $inconnuVulnerabilityMajor = 0;
  private static int $inconnuVulnerabilityMinor = 0;
  private static int $inconnuVulnerabilityInfo = 0;
  private static int $inconnuCodeSmellBlocker = 0;
  private static int $inconnuCodeSmellCritical = 0;
  private static int $inconnuCodeSmellMajor = 0;
  private static int $inconnuCodeSmellMinor = 1;
  private static int $inconnuCodeSmellInfo = 43;
  private static string $control = 'complet (100%)';
  private static int $setup = 1739816022572;
  private static string $modeCollecte = 'COLLECTE';
  private static string $utilisateurCollecte = 'laurent.hadjadj@ma-moulinette.fr';
  private static string $dateEnregistrement = '2025-02-17 19:13:59+01';

  private function getEntity(): Repartition
  {
    return (new repartition())
      ->setMavenKey(self::$mavenKey)
      ->setName(self::$name)
      ->setBugBlocker(self::$bugBlocker)
      ->setBugCritical(self::$bugCritical)
      ->setBugMajor(self::$bugMajor)
      ->setBugMinor(self::$bugMinor)
      ->setBugInfo(self::$bugInfo)
      ->setVulnerabilityBlocker(self::$vulnerabilityBlocker)
      ->setVulnerabilityCritical(self::$vulnerabilityCritical)
      ->setVulnerabilityMajor(self::$vulnerabilityMajor)
      ->setVulnerabilityMinor(self::$vulnerabilityMinor)
      ->setVulnerabilityInfo(self::$vulnerabilityInfo)
      ->setCodeSmellBlocker(self::$codeSmellBlocker)
      ->setCodeSmellCritical(self::$codeSmellCritical)
      ->setCodeSmellMajor(self::$codeSmellMajor)
      ->setCodeSmellMinor(self::$codeSmellMinor)
      ->setCodeSmellInfo(self::$codeSmellInfo)
      ->setFrontend(self::$frontend)
      ->setFrontendBugBlocker(self::$frontendBugBlocker)
      ->setFrontendBugCritical(self::$frontendBugCritical)
      ->setFrontendBugMajor(self::$frontendBugMajor)
      ->setFrontendBugMinor(self::$frontendBugMinor)
      ->setFrontendBugInfo(self::$frontendBugInfo)
      ->setFrontendVulnerabilityBlocker(self::$frontendVulnerabilityBlocker)
      ->setFrontendVulnerabilityCritical(self::$frontendVulnerabilityCritical)
      ->setFrontendVulnerabilityMajor(self::$frontendVulnerabilityMajor)
      ->setFrontendVulnerabilityMinor(self::$frontendVulnerabilityMinor)
      ->setFrontendVulnerabilityInfo(self::$frontendVulnerabilityInfo)
      ->setFrontendCodeSmellBlocker(self::$frontendCodeSmellBlocker)
      ->setFrontendCodeSmellCritical(self::$frontendCodeSmellCritical)
      ->setFrontendCodeSmellMajor(self::$frontendCodeSmellMajor)
      ->setFrontendCodeSmellMinor(self::$frontendCodeSmellMinor)
      ->setFrontendCodeSmellInfo(self::$frontendCodeSmellInfo)
      ->setBackend(self::$backend)
      ->setBackendBugBlocker(self::$backendBugBlocker)
      ->setBackendBugCritical(self::$backendBugCritical)
      ->setBackendBugMajor(self::$backendBugMajor)
      ->setBackendBugMinor(self::$backendBugMinor)
      ->setBackendBugInfo(self::$backendBugInfo)
      ->setBackendVulnerabilityBlocker(self::$backendVulnerabilityBlocker)
      ->setBackendVulnerabilityCritical(self::$backendVulnerabilityCritical)
      ->setBackendVulnerabilityMajor(self::$backendVulnerabilityMajor)
      ->setBackendVulnerabilityMinor(self::$backendVulnerabilityMinor)
      ->setBackendVulnerabilityInfo(self::$backendVulnerabilityInfo)
      ->setBackendCodeSmellBlocker(self::$backendCodeSmellBlocker)
      ->setBackendCodeSmellCritical(self::$backendCodeSmellCritical)
      ->setBackendCodeSmellMajor(self::$backendCodeSmellMajor)
      ->setBackendCodeSmellMinor(self::$backendCodeSmellMinor)
      ->setBackendCodeSmellInfo(self::$backendCodeSmellInfo)
      ->setAutre(self::$autre)
      ->setAutreBugBlocker(self::$autreBugBlocker)
      ->setAutreBugCritical(self::$autreBugCritical)
      ->setAutreBugMajor(self::$autreBugMajor)
      ->setAutreBugMinor(self::$autreBugMinor)
      ->setAutreBugInfo(self::$autreBugInfo)
      ->setAutreVulnerabilityBlocker(self::$autreVulnerabilityBlocker)
      ->setAutreVulnerabilityCritical(self::$autreVulnerabilityCritical)
      ->setAutreVulnerabilityMajor(self::$autreVulnerabilityMajor)
      ->setAutreVulnerabilityMinor(self::$autreVulnerabilityMinor)
      ->setAutreVulnerabilityInfo(self::$autreVulnerabilityInfo)
      ->setAutreCodeSmellBlocker(self::$autreCodeSmellBlocker)
      ->setAutreCodeSmellCritical(self::$autreCodeSmellCritical)
      ->setAutreCodeSmellMajor(self::$autreCodeSmellMajor)
      ->setAutreCodeSmellMinor(self::$autreCodeSmellMinor)
      ->setAutreCodeSmellInfo(self::$autreCodeSmellInfo)
      ->setInconnu(self::$inconnu)
      ->setInconnuBugBlocker(self::$inconnuBugBlocker)
      ->setInconnuBugCritical(self::$inconnuBugCritical)
      ->setInconnuBugMajor(self::$inconnuBugMajor)
      ->setInconnuBugMinor(self::$inconnuBugMinor)
      ->setInconnuBugInfo(self::$inconnuBugInfo)
      ->setInconnuVulnerabilityBlocker(self::$inconnuVulnerabilityBlocker)
      ->setInconnuVulnerabilityCritical(self::$inconnuVulnerabilityCritical)
      ->setInconnuVulnerabilityMajor(self::$inconnuVulnerabilityMajor)
      ->setInconnuVulnerabilityMinor(self::$inconnuVulnerabilityMinor)
      ->setInconnuVulnerabilityInfo(self::$inconnuVulnerabilityInfo)
      ->setInconnuCodeSmellBlocker(self::$inconnuCodeSmellBlocker)
      ->setInconnuCodeSmellCritical(self::$inconnuCodeSmellCritical)
      ->setInconnuCodeSmellMajor(self::$inconnuCodeSmellMajor)
      ->setInconnuCodeSmellMinor(self::$inconnuCodeSmellMinor)
      ->setInconnuCodeSmellInfo(self::$inconnuCodeSmellInfo)
      ->setSetup(self::$setup)
      ->setControl(self::$control)
      ->setModeCollecte(self::$modeCollecte)
      ->setUtilisateurCollecte(self::$utilisateurCollecte)
      ->setDateEnregistrement(new \DateTimeImmutable(self::$dateEnregistrement));
  }

  public function assertHasErrors(Repartition $entity, int $number = 0): void
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
    $this->assertHasErrors($this->getEntity()->setName(''), 1);
    $this->assertHasErrors($this->getEntity()->setModeCollecte(''), 0);
    $this->assertHasErrors($this->getEntity()->setUtilisateurCollecte(''), 0);
  }

  public function testValidIntegerEntity(): void
  {
    // v2.0.0 : valeurs limites BASSES acceptees par PositiveOrZero (>= 0)
    $this->assertHasErrors($this->getEntity()->setSetup(0), 0);
    $this->assertHasErrors($this->getEntity()->setBugBlocker(0), 0);
    $this->assertHasErrors($this->getEntity()->setBugCritical(0), 0);
    $this->assertHasErrors($this->getEntity()->setBugMajor(0), 0);
    $this->assertHasErrors($this->getEntity()->setBugMinor(0), 0);
    $this->assertHasErrors($this->getEntity()->setBugInfo(0), 0);
    $this->assertHasErrors($this->getEntity()->setVulnerabilityBlocker(0), 0);
    $this->assertHasErrors($this->getEntity()->setVulnerabilityCritical(0), 0);
    $this->assertHasErrors($this->getEntity()->setVulnerabilityMajor(0), 0);
    $this->assertHasErrors($this->getEntity()->setVulnerabilityMinor(0), 0);
    $this->assertHasErrors($this->getEntity()->setVulnerabilityInfo(0), 0);
    $this->assertHasErrors($this->getEntity()->setCodeSmellBlocker(0), 0);
    $this->assertHasErrors($this->getEntity()->setCodeSmellCritical(0), 0);
    $this->assertHasErrors($this->getEntity()->setCodeSmellMajor(0), 0);
    $this->assertHasErrors($this->getEntity()->setCodeSmellMinor(0), 0);
    $this->assertHasErrors($this->getEntity()->setCodeSmellInfo(0), 0);
    $this->assertHasErrors($this->getEntity()->setFrontend(0), 0);
    $this->assertHasErrors($this->getEntity()->setFrontendBugBlocker(0), 0);
    $this->assertHasErrors($this->getEntity()->setFrontendBugCritical(0), 0);
    $this->assertHasErrors($this->getEntity()->setFrontendBugMajor(0), 0);
    $this->assertHasErrors($this->getEntity()->setFrontendBugMinor(0), 0);
    $this->assertHasErrors($this->getEntity()->setFrontendBugInfo(0), 0);
    $this->assertHasErrors($this->getEntity()->setFrontendVulnerabilityBlocker(0), 0);
    $this->assertHasErrors($this->getEntity()->setFrontendVulnerabilityCritical(0), 0);
    $this->assertHasErrors($this->getEntity()->setFrontendVulnerabilityMajor(0), 0);
    $this->assertHasErrors($this->getEntity()->setFrontendVulnerabilityMinor(0), 0);
    $this->assertHasErrors($this->getEntity()->setFrontendVulnerabilityInfo(0), 0);
    $this->assertHasErrors($this->getEntity()->setFrontendCodeSmellBlocker(0), 0);
    $this->assertHasErrors($this->getEntity()->setFrontendCodeSmellCritical(0), 0);
    $this->assertHasErrors($this->getEntity()->setFrontendCodeSmellMajor(0), 0);
    $this->assertHasErrors($this->getEntity()->setFrontendCodeSmellMinor(0), 0);
    $this->assertHasErrors($this->getEntity()->setFrontendCodeSmellInfo(0), 0);
    $this->assertHasErrors($this->getEntity()->setBackend(0), 0);
    $this->assertHasErrors($this->getEntity()->setBackendBugBlocker(0), 0);
    $this->assertHasErrors($this->getEntity()->setBackendBugCritical(0), 0);
    $this->assertHasErrors($this->getEntity()->setBackendBugMajor(0), 0);
    $this->assertHasErrors($this->getEntity()->setBackendBugMinor(0), 0);
    $this->assertHasErrors($this->getEntity()->setBackendBugInfo(0), 0);
    $this->assertHasErrors($this->getEntity()->setBackendVulnerabilityBlocker(0), 0);
    $this->assertHasErrors($this->getEntity()->setBackendVulnerabilityCritical(0), 0);
    $this->assertHasErrors($this->getEntity()->setBackendVulnerabilityMajor(0), 0);
    $this->assertHasErrors($this->getEntity()->setBackendVulnerabilityMinor(0), 0);
    $this->assertHasErrors($this->getEntity()->setBackendVulnerabilityInfo(0), 0);
    $this->assertHasErrors($this->getEntity()->setBackendCodeSmellBlocker(0), 0);
    $this->assertHasErrors($this->getEntity()->setBackendCodeSmellCritical(0), 0);
    $this->assertHasErrors($this->getEntity()->setBackendCodeSmellMajor(0), 0);
    $this->assertHasErrors($this->getEntity()->setBackendCodeSmellMinor(0), 0);
    $this->assertHasErrors($this->getEntity()->setBackendCodeSmellInfo(0), 0);
    $this->assertHasErrors($this->getEntity()->setAutre(0), 0);
    $this->assertHasErrors($this->getEntity()->setAutreBugBlocker(0), 0);
    $this->assertHasErrors($this->getEntity()->setAutreBugCritical(0), 0);
    $this->assertHasErrors($this->getEntity()->setAutreBugMajor(0), 0);
    $this->assertHasErrors($this->getEntity()->setAutreBugMinor(0), 0);
    $this->assertHasErrors($this->getEntity()->setAutreBugInfo(0), 0);
    $this->assertHasErrors($this->getEntity()->setAutreVulnerabilityBlocker(0), 0);
    $this->assertHasErrors($this->getEntity()->setAutreVulnerabilityCritical(0), 0);
    $this->assertHasErrors($this->getEntity()->setAutreVulnerabilityMajor(0), 0);
    $this->assertHasErrors($this->getEntity()->setAutreVulnerabilityMinor(0), 0);
    $this->assertHasErrors($this->getEntity()->setAutreVulnerabilityInfo(0), 0);
    $this->assertHasErrors($this->getEntity()->setAutreCodeSmellBlocker(0), 0);
    $this->assertHasErrors($this->getEntity()->setAutreCodeSmellCritical(0), 0);
    $this->assertHasErrors($this->getEntity()->setAutreCodeSmellMajor(0), 0);
    $this->assertHasErrors($this->getEntity()->setAutreCodeSmellMinor(0), 0);
    $this->assertHasErrors($this->getEntity()->setAutreCodeSmellInfo(0), 0);
    $this->assertHasErrors($this->getEntity()->setInconnu(0), 0);
    $this->assertHasErrors($this->getEntity()->setInconnuBugBlocker(0), 0);
    $this->assertHasErrors($this->getEntity()->setInconnuBugCritical(0), 0);
    $this->assertHasErrors($this->getEntity()->setInconnuBugMajor(0), 0);
    $this->assertHasErrors($this->getEntity()->setInconnuBugMinor(0), 0);
    $this->assertHasErrors($this->getEntity()->setInconnuBugInfo(0), 0);
    $this->assertHasErrors($this->getEntity()->setInconnuVulnerabilityBlocker(0), 0);
    $this->assertHasErrors($this->getEntity()->setInconnuVulnerabilityCritical(0), 0);
    $this->assertHasErrors($this->getEntity()->setInconnuVulnerabilityMajor(0), 0);
    $this->assertHasErrors($this->getEntity()->setInconnuVulnerabilityMinor(0), 0);
    $this->assertHasErrors($this->getEntity()->setInconnuVulnerabilityInfo(0), 0);
    $this->assertHasErrors($this->getEntity()->setInconnuCodeSmellBlocker(0), 0);
    $this->assertHasErrors($this->getEntity()->setInconnuCodeSmellCritical(0), 0);
    $this->assertHasErrors($this->getEntity()->setInconnuCodeSmellMajor(0), 0);
    $this->assertHasErrors($this->getEntity()->setInconnuCodeSmellMinor(0), 0);
    $this->assertHasErrors($this->getEntity()->setInconnuCodeSmellInfo(0), 0);
  }

  /**
   * v2.0.0 : valeurs negatives REJETEES par les contraintes PositiveOrZero / Positive / Range.
   */
  public function testInvalidIntegerEntity(): void
  {
    $this->assertHasErrors($this->getEntity()->setSetup(-1), 1);
    $this->assertHasErrors($this->getEntity()->setBugBlocker(-1), 1);
    $this->assertHasErrors($this->getEntity()->setBugCritical(-1), 1);
    $this->assertHasErrors($this->getEntity()->setBugMajor(-1), 1);
    $this->assertHasErrors($this->getEntity()->setBugMinor(-1), 1);
    $this->assertHasErrors($this->getEntity()->setBugInfo(-1), 1);
    $this->assertHasErrors($this->getEntity()->setVulnerabilityBlocker(-1), 1);
    $this->assertHasErrors($this->getEntity()->setVulnerabilityCritical(-1), 1);
    $this->assertHasErrors($this->getEntity()->setVulnerabilityMajor(-1), 1);
    $this->assertHasErrors($this->getEntity()->setVulnerabilityMinor(-1), 1);
    $this->assertHasErrors($this->getEntity()->setVulnerabilityInfo(-1), 1);
    $this->assertHasErrors($this->getEntity()->setCodeSmellBlocker(-1), 1);
    $this->assertHasErrors($this->getEntity()->setCodeSmellCritical(-1), 1);
    $this->assertHasErrors($this->getEntity()->setCodeSmellMajor(-1), 1);
    $this->assertHasErrors($this->getEntity()->setCodeSmellMinor(-1), 1);
    $this->assertHasErrors($this->getEntity()->setCodeSmellInfo(-1), 1);
    $this->assertHasErrors($this->getEntity()->setFrontend(-1), 1);
    $this->assertHasErrors($this->getEntity()->setFrontendBugBlocker(-1), 1);
    $this->assertHasErrors($this->getEntity()->setFrontendBugCritical(-1), 1);
    $this->assertHasErrors($this->getEntity()->setFrontendBugMajor(-1), 1);
    $this->assertHasErrors($this->getEntity()->setFrontendBugMinor(-1), 1);
    $this->assertHasErrors($this->getEntity()->setFrontendBugInfo(-1), 1);
    $this->assertHasErrors($this->getEntity()->setFrontendVulnerabilityBlocker(-1), 1);
    $this->assertHasErrors($this->getEntity()->setFrontendVulnerabilityCritical(-1), 1);
    $this->assertHasErrors($this->getEntity()->setFrontendVulnerabilityMajor(-1), 1);
    $this->assertHasErrors($this->getEntity()->setFrontendVulnerabilityMinor(-1), 1);
    $this->assertHasErrors($this->getEntity()->setFrontendVulnerabilityInfo(-1), 1);
    $this->assertHasErrors($this->getEntity()->setFrontendCodeSmellBlocker(-1), 1);
    $this->assertHasErrors($this->getEntity()->setFrontendCodeSmellCritical(-1), 1);
    $this->assertHasErrors($this->getEntity()->setFrontendCodeSmellMajor(-1), 1);
    $this->assertHasErrors($this->getEntity()->setFrontendCodeSmellMinor(-1), 1);
    $this->assertHasErrors($this->getEntity()->setFrontendCodeSmellInfo(-1), 1);
    $this->assertHasErrors($this->getEntity()->setBackend(-1), 1);
    $this->assertHasErrors($this->getEntity()->setBackendBugBlocker(-1), 1);
    $this->assertHasErrors($this->getEntity()->setBackendBugCritical(-1), 1);
    $this->assertHasErrors($this->getEntity()->setBackendBugMajor(-1), 1);
    $this->assertHasErrors($this->getEntity()->setBackendBugMinor(-1), 1);
    $this->assertHasErrors($this->getEntity()->setBackendBugInfo(-1), 1);
    $this->assertHasErrors($this->getEntity()->setBackendVulnerabilityBlocker(-1), 1);
    $this->assertHasErrors($this->getEntity()->setBackendVulnerabilityCritical(-1), 1);
    $this->assertHasErrors($this->getEntity()->setBackendVulnerabilityMajor(-1), 1);
    $this->assertHasErrors($this->getEntity()->setBackendVulnerabilityMinor(-1), 1);
    $this->assertHasErrors($this->getEntity()->setBackendVulnerabilityInfo(-1), 1);
    $this->assertHasErrors($this->getEntity()->setBackendCodeSmellBlocker(-1), 1);
    $this->assertHasErrors($this->getEntity()->setBackendCodeSmellCritical(-1), 1);
    $this->assertHasErrors($this->getEntity()->setBackendCodeSmellMajor(-1), 1);
    $this->assertHasErrors($this->getEntity()->setBackendCodeSmellMinor(-1), 1);
    $this->assertHasErrors($this->getEntity()->setBackendCodeSmellInfo(-1), 1);
    $this->assertHasErrors($this->getEntity()->setAutre(-1), 1);
    $this->assertHasErrors($this->getEntity()->setAutreBugBlocker(-1), 1);
    $this->assertHasErrors($this->getEntity()->setAutreBugCritical(-1), 1);
    $this->assertHasErrors($this->getEntity()->setAutreBugMajor(-1), 1);
    $this->assertHasErrors($this->getEntity()->setAutreBugMinor(-1), 1);
    $this->assertHasErrors($this->getEntity()->setAutreBugInfo(-1), 1);
    $this->assertHasErrors($this->getEntity()->setAutreVulnerabilityBlocker(-1), 1);
    $this->assertHasErrors($this->getEntity()->setAutreVulnerabilityCritical(-1), 1);
    $this->assertHasErrors($this->getEntity()->setAutreVulnerabilityMajor(-1), 1);
    $this->assertHasErrors($this->getEntity()->setAutreVulnerabilityMinor(-1), 1);
    $this->assertHasErrors($this->getEntity()->setAutreVulnerabilityInfo(-1), 1);
    $this->assertHasErrors($this->getEntity()->setAutreCodeSmellBlocker(-1), 1);
    $this->assertHasErrors($this->getEntity()->setAutreCodeSmellCritical(-1), 1);
    $this->assertHasErrors($this->getEntity()->setAutreCodeSmellMajor(-1), 1);
    $this->assertHasErrors($this->getEntity()->setAutreCodeSmellMinor(-1), 1);
    $this->assertHasErrors($this->getEntity()->setAutreCodeSmellInfo(-1), 1);
    $this->assertHasErrors($this->getEntity()->setInconnu(-1), 1);
    $this->assertHasErrors($this->getEntity()->setInconnuBugBlocker(-1), 1);
    $this->assertHasErrors($this->getEntity()->setInconnuBugCritical(-1), 1);
    $this->assertHasErrors($this->getEntity()->setInconnuBugMajor(-1), 1);
    $this->assertHasErrors($this->getEntity()->setInconnuBugMinor(-1), 1);
    $this->assertHasErrors($this->getEntity()->setInconnuBugInfo(-1), 1);
    $this->assertHasErrors($this->getEntity()->setInconnuVulnerabilityBlocker(-1), 1);
    $this->assertHasErrors($this->getEntity()->setInconnuVulnerabilityCritical(-1), 1);
    $this->assertHasErrors($this->getEntity()->setInconnuVulnerabilityMajor(-1), 1);
    $this->assertHasErrors($this->getEntity()->setInconnuVulnerabilityMinor(-1), 1);
    $this->assertHasErrors($this->getEntity()->setInconnuVulnerabilityInfo(-1), 1);
    $this->assertHasErrors($this->getEntity()->setInconnuCodeSmellBlocker(-1), 1);
    $this->assertHasErrors($this->getEntity()->setInconnuCodeSmellCritical(-1), 1);
    $this->assertHasErrors($this->getEntity()->setInconnuCodeSmellMajor(-1), 1);
    $this->assertHasErrors($this->getEntity()->setInconnuCodeSmellMinor(-1), 1);
    $this->assertHasErrors($this->getEntity()->setInconnuCodeSmellInfo(-1), 1);
  }

  public function testCountAttribut(): void
  {
    $entity = $this->getEntity();
    $reflectionClass = new \ReflectionClass($entity);
    $nbAttributs = count($reflectionClass->getProperties());
    $this->assertEquals($nbAttributs, 87, "Le nombre d'attribut doit être de 87.");
  }
}
