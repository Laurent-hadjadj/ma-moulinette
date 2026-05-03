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
  private static string $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
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
  private static string $setup = '1739816022572';
  private static string $modeCollecte = 'COLLECTE';
  private static string $utilisateurCollecte = 'laurent.hadjadj@ma-petite-entreprise.fr';
  private static string $dateEnregistrement = '2025-02-17 19:13:59+01';

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
