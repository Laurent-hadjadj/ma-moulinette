<?php

namespace App\DataFixtures;

use App\Entity\Repartition;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * [Description RepartitionFixtures]
 */
class RepartitionFixtures extends Fixture
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
  private static $setup = '1739816022572';
  private static $modeCollecte = 'COLLECTE';
  private static $utilisateurCollecte = 'laurent.hadjadj@ma-petite-entreprise.fr';
  private static $dateEnregistrement = '2025-02-17 19:13:59+01';

  public function load(ObjectManager $manager): void
    {
      $repartition=(new Repartition())
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
      $manager->persist($repartition);

      /** Enregistrement des données dans la base de tests */
      $manager->flush();
    }
  }
