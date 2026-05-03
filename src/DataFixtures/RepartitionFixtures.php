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
  private static $inconnu = 0;
  private static $inconnuBugBlocker = 0;
  private static $inconnuBugCritical = 0;
  private static $inconnuBugMajor = 0;
  private static $inconnuBugMinor = 0;
  private static $inconnuBugInfo = 0;
  private static $inconnuVulnerabilityBlocker = 0;
  private static $inconnuVulnerabilityCritical = 0;
  private static $inconnuVulnerabilityMajor = 0;
  private static $inconnuVulnerabilityMinor = 0;
  private static $inconnuVulnerabilityInfo = 0;
  private static $inconnuCodeSmellBlocker = 0;
  private static $inconnuCodeSmellCritical = 0;
  private static $inconnuCodeSmellMajor = 0;
  private static $inconnuCodeSmellMinor = 1;
  private static $inconnuCodeSmellInfo = 43;
  private static $control = 'complet (100%)';
  private static $setup = '1739816022572';
  private static $modeCollecte = 'COLLECTE';
  private static $utilisateurCollecte = 'laurent.hadjadj@ma-petite-entreprise.fr';
  private static $dateEnregistrement = '2025-02-17 19:13:59+01';

  public function load(ObjectManager $manager): void
    {
      $repartition=(new Repartition())
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
      $manager->persist($repartition);

      /** Enregistrement des données dans la base de tests */
      $manager->flush();
    }
  }
