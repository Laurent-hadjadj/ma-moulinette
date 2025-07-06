<?php

/*
*  Ma-Moulinette
*  --------------
*  Copyright (c) 2021-2025.
*  Laurent HADJADJ <laurent_h@me.com>.
*  Licensed Creative Common  CC-BY-NC-SA 4.0.
*  ---
*  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
*  http://creativecommons.org/licenses/by-nc-sa/4.0/
*/

namespace App\Tests\Unit\Entity\Case;

use App\Entity\Repartition;
use PHPUnit\Framework\TestCase;

/**
 * [Description RepartitionCaseTest]
 */
class RepartitionCaseTest extends TestCase
{
    private $repartition;

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

    protected function setUp(): void
    {
        parent::setUp();
        $this->repartition = $this->getEntity();
    }

    public function testSettingAndGettingId(): void
    {
        $this->repartition->setId(1);
        $this->assertEquals(1, $this->repartition->getId());
    }

    public function testSettingAndGettingMavenKey(): void
    {
        $this->repartition->setMavenKey(static::$mavenKey);
        $this->assertEquals(static::$mavenKey, $this->repartition->getMavenKey());
    }

    public function testSettingAndGettingName(): void
    {
        $this->repartition->setName(static::$name);
        $this->assertEquals(static::$name, $this->repartition->getName());
    }

    public function testSettingAndGettingBugBlocker(): void
    {
        $this->repartition->setBugBlocker(static::$bugBlocker);
        $this->assertEquals(static::$bugBlocker, $this->repartition->getBugBlocker());
    }

    public function testSettingAndGettingBugCritical(): void
    {
        $this->repartition->setBugCritical(static::$bugCritical);
        $this->assertEquals(static::$bugCritical, $this->repartition->getBugCritical());
    }

    public function testSettingAndGettingBugMajor(): void
    {
        $this->repartition->setBugMajor(static::$bugMajor);
        $this->assertEquals(static::$bugMajor, $this->repartition->getBugMajor());
    }

    public function testSettingAndGettingBugMinor(): void
    {
        $this->repartition->setBugMinor(static::$bugMinor);
        $this->assertEquals(static::$bugMinor, $this->repartition->getBugMinor());
    }

    public function testSettingAndGettingBugInfo(): void
    {
        $this->repartition->setBugInfo(static::$bugInfo);
        $this->assertEquals(static::$bugInfo, $this->repartition->getBugInfo());
    }

    public function testSettingAndGettingVulnerabilityBlocker(): void
    {
        $this->repartition->setVulnerabilityBlocker(static::$vulnerabilityBlocker);
        $this->assertEquals(static::$vulnerabilityBlocker, $this->repartition->getVulnerabilityBlocker());
    }

    public function testSettingAndGettingVulnerabilityCritical(): void
    {
        $this->repartition->setVulnerabilityCritical(static::$vulnerabilityCritical);
        $this->assertEquals(static::$vulnerabilityCritical, $this->repartition->getVulnerabilityCritical());
    }

    public function testSettingAndGettingVulnerabilityMajor(): void
    {
        $this->repartition->setVulnerabilityMajor(static::$vulnerabilityMajor);
        $this->assertEquals(static::$vulnerabilityMajor, $this->repartition->getVulnerabilityMajor());
    }

    public function testSettingAndGettingVulnerabilityMinor(): void
    {
        $this->repartition->setVulnerabilityMinor(static::$vulnerabilityMinor);
        $this->assertEquals(static::$vulnerabilityMinor, $this->repartition->getVulnerabilityMinor());
    }

    public function testSettingAndGettingVulnerabilityInfo(): void
    {
        $this->repartition->setVulnerabilityInfo(static::$vulnerabilityInfo);
        $this->assertEquals(static::$vulnerabilityInfo, $this->repartition->getVulnerabilityInfo());
    }

    public function testSettingAndGettingCodeSmellBlocker(): void
    {
        $this->repartition->setCodeSmellBlocker(static::$codeSmellBlocker);
        $this->assertEquals(static::$codeSmellBlocker, $this->repartition->getCodeSmellBlocker());
    }

    public function testSettingAndGettingCodeSmellCritical(): void
    {
        $this->repartition->setCodeSmellCritical(static::$codeSmellCritical);
        $this->assertEquals(static::$codeSmellCritical, $this->repartition->getCodeSmellCritical());
    }

    public function testSettingAndGettingCodeSmellMajor(): void
    {
        $this->repartition->setCodeSmellMajor(static::$codeSmellMajor);
        $this->assertEquals(static::$codeSmellMajor, $this->repartition->getCodeSmellMajor());
    }

    public function testSettingAndGettingCodeSmellMinor(): void
    {
        $this->repartition->setCodeSmellMinor(static::$codeSmellMinor);
        $this->assertEquals(static::$codeSmellMinor, $this->repartition->getCodeSmellMinor());
    }

    public function testSettingAndGettingCodeSmellInfo(): void
    {
        $this->repartition->setCodeSmellInfo(static::$codeSmellInfo);
        $this->assertEquals(static::$codeSmellInfo, $this->repartition->getCodeSmellInfo());
    }

    public function testSettingAndGettingFrontend(): void
    {
        $this->repartition->setFrontend(static::$frontend);
        $this->assertEquals(static::$frontend, $this->repartition->getFrontend());
    }

    public function testSettingAndGettingFrontendBugBlocker(): void
    {
        $this->repartition->setFrontendBugBlocker(static::$frontendBugBlocker);
        $this->assertEquals(static::$frontendBugBlocker, $this->repartition->getFrontendBugBlocker());
    }

    public function testSettingAndGettingFrontendBugCritical(): void
    {
        $this->repartition->setFrontendBugCritical(static::$frontendBugCritical);
        $this->assertEquals(static::$frontendBugCritical, $this->repartition->getFrontendBugCritical());
    }

    public function testSettingAndGettingFrontendBugMajor(): void
    {
        $this->repartition->setFrontendBugMajor(static::$frontendBugMajor);
        $this->assertEquals(static::$frontendBugMajor, $this->repartition->getFrontendBugMajor());
    }

    public function testSettingAndGettingFrontendBugMinor(): void
    {
        $this->repartition->setFrontendBugMinor(static::$frontendBugMinor);
        $this->assertEquals(static::$frontendBugMinor, $this->repartition->getFrontendBugMinor());
    }

    public function testSettingAndGettingFrontendBugInfo(): void
    {
        $this->repartition->setFrontendBugInfo(static::$frontendBugInfo);
        $this->assertEquals(static::$frontendBugInfo, $this->repartition->getFrontendBugInfo());
    }

    public function testSettingAndGettingFrontendVulnerabilityBlocker(): void
    {
        $this->repartition->setFrontendVulnerabilityBlocker(static::$frontendVulnerabilityBlocker);
        $this->assertEquals(static::$frontendVulnerabilityBlocker, $this->repartition->getFrontendVulnerabilityBlocker());
    }

    public function testSettingAndGettingFrontendVulnerabilityCritical(): void
    {
        $this->repartition->setFrontendVulnerabilityCritical(static::$frontendVulnerabilityCritical);
        $this->assertEquals(static::$frontendVulnerabilityCritical, $this->repartition->getFrontendVulnerabilityCritical());
    }

    public function testSettingAndGettingFrontendVulnerabilityMajor(): void
    {
        $this->repartition->setFrontendVulnerabilityMajor(static::$frontendVulnerabilityMajor);
        $this->assertEquals(static::$frontendVulnerabilityMajor, $this->repartition->getFrontendVulnerabilityMajor());
    }

    public function testSettingAndGettingFrontendVulnerabilityMinor(): void
    {
        $this->repartition->setFrontendVulnerabilityMinor(static::$frontendVulnerabilityMinor);
        $this->assertEquals(static::$frontendVulnerabilityMinor, $this->repartition->getFrontendVulnerabilityMinor());
    }

    public function testSettingAndGettingFrontendVulnerabilityInfo(): void
    {
        $this->repartition->setFrontendVulnerabilityInfo(static::$frontendVulnerabilityInfo);
        $this->assertEquals(static::$frontendVulnerabilityInfo, $this->repartition->getFrontendVulnerabilityInfo());
    }

    public function testSettingAndGettingFrontendCodeSmellBlocker(): void
    {
        $this->repartition->setFrontendCodeSmellBlocker(static::$frontendCodeSmellBlocker);
        $this->assertEquals(static::$frontendCodeSmellBlocker, $this->repartition->getFrontendCodeSmellBlocker());
    }

    public function testSettingAndGettingFrontendCodeSmellCritical(): void
    {
        $this->repartition->setFrontendCodeSmellCritical(static::$frontendCodeSmellCritical);
        $this->assertEquals(static::$frontendCodeSmellCritical, $this->repartition->getFrontendCodeSmellCritical());
    }

    public function testSettingAndGettingFrontendCodeSmellMajor(): void
    {
        $this->repartition->setFrontendCodeSmellMajor(static::$frontendCodeSmellMajor);
        $this->assertEquals(static::$frontendCodeSmellMajor, $this->repartition->getFrontendCodeSmellMajor());
    }

    public function testSettingAndGettingFrontendCodeSmellMinor(): void
    {
        $this->repartition->setFrontendCodeSmellMinor(static::$frontendCodeSmellMinor);
        $this->assertEquals(static::$frontendCodeSmellMinor, $this->repartition->getFrontendCodeSmellMinor());
    }

    public function testSettingAndGettingFrontendCodeSmellInfo(): void
    {
        $this->repartition->setFrontendCodeSmellInfo(static::$frontendCodeSmellInfo);
        $this->assertEquals(static::$frontendCodeSmellInfo, $this->repartition->getFrontendCodeSmellInfo());
    }

    public function testSettingAndGettingBackend(): void
    {
        $this->repartition->setBackend(static::$backend);
        $this->assertEquals(static::$backend, $this->repartition->getBackend());
    }

    public function testSettingAndGettingBackendBugBlocker(): void
    {
        $this->repartition->setBackendBugBlocker(static::$backendBugBlocker);
        $this->assertEquals(static::$backendBugBlocker, $this->repartition->getBackendBugBlocker());
    }

    public function testSettingAndGettingBackendBugCritical(): void
    {
        $this->repartition->setBackendBugCritical(static::$backendBugCritical);
        $this->assertEquals(static::$backendBugCritical, $this->repartition->getBackendBugCritical());
    }

    public function testSettingAndGettingBackendBugMajor(): void
    {
        $this->repartition->setBackendBugMajor(static::$backendBugMajor);
        $this->assertEquals(static::$backendBugMajor, $this->repartition->getBackendBugMajor());
    }

    public function testSettingAndGettingBackendBugMinor(): void
    {
        $this->repartition->setBackendBugMinor(static::$backendBugMinor);
        $this->assertEquals(static::$backendBugMinor, $this->repartition->getBackendBugMinor());
    }

    public function testSettingAndGettingBackendBugInfo(): void
    {
        $this->repartition->setBackendBugInfo(static::$backendBugInfo);
        $this->assertEquals(static::$backendBugInfo, $this->repartition->getBackendBugInfo());
    }

    public function testSettingAndGettingBackendVulnerabilityBlocker(): void
    {
        $this->repartition->setBackendVulnerabilityBlocker(static::$backendVulnerabilityBlocker);
        $this->assertEquals(static::$backendVulnerabilityBlocker, $this->repartition->getBackendVulnerabilityBlocker());
    }

    public function testSettingAndGettingBackendVulnerabilityCritical(): void
    {
        $this->repartition->setBackendVulnerabilityCritical(static::$backendVulnerabilityCritical);
        $this->assertEquals(static::$backendVulnerabilityCritical, $this->repartition->getBackendVulnerabilityCritical());
    }

    public function testSettingAndGettingBackendVulnerabilityMajor(): void
    {
        $this->repartition->setBackendVulnerabilityMajor(static::$backendVulnerabilityMajor);
        $this->assertEquals(static::$backendVulnerabilityMajor, $this->repartition->getBackendVulnerabilityMajor());
    }

    public function testSettingAndGettingBackendVulnerabilityMinor(): void
    {
        $this->repartition->setBackendVulnerabilityMinor(static::$backendVulnerabilityMinor);
        $this->assertEquals(static::$backendVulnerabilityMinor, $this->repartition->getBackendVulnerabilityMinor());
    }

    public function testSettingAndGettingBackendVulnerabilityInfo(): void
    {
        $this->repartition->setBackendVulnerabilityInfo(static::$backendVulnerabilityInfo);
        $this->assertEquals(static::$backendVulnerabilityInfo, $this->repartition->getBackendVulnerabilityInfo());
    }

    public function testSettingAndGettingBackendCodeSmellBlocker(): void
    {
        $this->repartition->setBackendCodeSmellBlocker(static::$backendCodeSmellBlocker);
        $this->assertEquals(static::$backendCodeSmellBlocker, $this->repartition->getBackendCodeSmellBlocker());
    }

    public function testSettingAndGettingBackendCodeSmellCritical(): void
    {
        $this->repartition->setBackendCodeSmellCritical(static::$backendCodeSmellCritical);
        $this->assertEquals(static::$backendCodeSmellCritical, $this->repartition->getBackendCodeSmellCritical());
    }

    public function testSettingAndGettingBackendCodeSmellMajor(): void
    {
        $this->repartition->setBackendCodeSmellMajor(static::$backendCodeSmellMajor);
        $this->assertEquals(static::$backendCodeSmellMajor, $this->repartition->getBackendCodeSmellMajor());
    }

    public function testSettingAndGettingBackendCodeSmellMinor(): void
    {
        $this->repartition->setBackendCodeSmellMinor(static::$backendCodeSmellMinor);
        $this->assertEquals(static::$backendCodeSmellMinor, $this->repartition->getBackendCodeSmellMinor());
    }

    public function testSettingAndGettingBackendCodeSmellInfo(): void
    {
        $this->repartition->setBackendCodeSmellInfo(static::$backendCodeSmellInfo);
        $this->assertEquals(static::$backendCodeSmellInfo, $this->repartition->getBackendCodeSmellInfo());
    }

    public function testSettingAndGettingAutre(): void
    {
        $this->repartition->setAutre(static::$autre);
        $this->assertEquals(static::$autre, $this->repartition->getAutre());
    }

    public function testSettingAndGettingAutreBugBlocker(): void
    {
        $this->repartition->setAutreBugBlocker(static::$autreBugBlocker);
        $this->assertEquals(static::$autreBugBlocker, $this->repartition->getAutreBugBlocker());
    }

    public function testSettingAndGettingAutreBugCritical(): void
    {
        $this->repartition->setAutreBugCritical(static::$autreBugCritical);
        $this->assertEquals(static::$autreBugCritical, $this->repartition->getAutreBugCritical());
    }

    public function testSettingAndGettingAutreBugMajor(): void
    {
        $this->repartition->setAutreBugMajor(static::$autreBugMajor);
        $this->assertEquals(static::$autreBugMajor, $this->repartition->getAutreBugMajor());
    }

    public function testSettingAndGettingAutreBugMinor(): void
    {
        $this->repartition->setAutreBugMinor(static::$autreBugMinor);
        $this->assertEquals(static::$autreBugMinor, $this->repartition->getAutreBugMinor());
    }

    public function testSettingAndGettingAutreBugInfo(): void
    {
        $this->repartition->setAutreBugInfo(static::$autreBugInfo);
        $this->assertEquals(static::$autreBugInfo, $this->repartition->getAutreBugInfo());
    }

    public function testSettingAndGettingAutreVulnerabilityBlocker(): void
    {
        $this->repartition->setAutreVulnerabilityBlocker(static::$autreVulnerabilityBlocker);
        $this->assertEquals(static::$autreVulnerabilityBlocker, $this->repartition->getAutreVulnerabilityBlocker());
    }

    public function testSettingAndGettingAutreVulnerabilityCritical(): void
    {
        $this->repartition->setAutreVulnerabilityCritical(static::$autreVulnerabilityCritical);
        $this->assertEquals(static::$autreVulnerabilityCritical, $this->repartition->getAutreVulnerabilityCritical());
    }

    public function testSettingAndGettingAutreVulnerabilityMajor(): void
    {
        $this->repartition->setAutreVulnerabilityMajor(static::$autreVulnerabilityMajor);
        $this->assertEquals(static::$autreVulnerabilityMajor, $this->repartition->getAutreVulnerabilityMajor());
    }

    public function testSettingAndGettingAutreVulnerabilityMinor(): void
    {
        $this->repartition->setAutreVulnerabilityMinor(static::$autreVulnerabilityMinor);
        $this->assertEquals(static::$autreVulnerabilityMinor, $this->repartition->getAutreVulnerabilityMinor());
    }

    public function testSettingAndGettingAutreVulnerabilityInfo(): void
    {
        $this->repartition->setAutreVulnerabilityInfo(static::$autreVulnerabilityInfo);
        $this->assertEquals(static::$autreVulnerabilityInfo, $this->repartition->getAutreVulnerabilityInfo());
    }

    public function testSettingAndGettingAutreCodeSmellBlocker(): void
    {
        $this->repartition->setAutreCodeSmellBlocker(static::$autreCodeSmellBlocker);
        $this->assertEquals(static::$autreCodeSmellBlocker, $this->repartition->getAutreCodeSmellBlocker());
    }

    public function testSettingAndGettingAutreCodeSmellCritical(): void
    {
        $this->repartition->setAutreCodeSmellCritical(static::$autreCodeSmellCritical);
        $this->assertEquals(static::$autreCodeSmellCritical, $this->repartition->getAutreCodeSmellCritical());
    }

    public function testSettingAndGettingAutreCodeSmellMajor(): void
    {
        $this->repartition->setAutreCodeSmellMajor(static::$autreCodeSmellMajor);
        $this->assertEquals(static::$autreCodeSmellMajor, $this->repartition->getAutreCodeSmellMajor());
    }

    public function testSettingAndGettingAutreCodeSmellMinor(): void
    {
        $this->repartition->setAutreCodeSmellMinor(static::$autreCodeSmellMinor);
        $this->assertEquals(static::$autreCodeSmellMinor, $this->repartition->getAutreCodeSmellMinor());
    }

    public function testSettingAndGettingAutreCodeSmellInfo(): void
    {
        $this->repartition->setAutreCodeSmellInfo(static::$autreCodeSmellInfo);
        $this->assertEquals(static::$autreCodeSmellInfo, $this->repartition->getAutreCodeSmellInfo());
    }

    public function testSettingAndGettingInconnue(): void
    {
        $this->repartition->setInconnue(static::$inconnue);
        $this->assertEquals(static::$inconnue, $this->repartition->getInconnue());
    }

    public function testSettingAndGettingInconnueBugBlocker(): void
    {
        $this->repartition->setInconnueBugBlocker(static::$inconnueBugBlocker);
        $this->assertEquals(static::$inconnueBugBlocker, $this->repartition->getInconnueBugBlocker());
    }

    public function testSettingAndGettingInconnueBugCritical(): void
    {
        $this->repartition->setInconnueBugCritical(static::$inconnueBugCritical);
        $this->assertEquals(static::$inconnueBugCritical, $this->repartition->getInconnueBugCritical());
    }

    public function testSettingAndGettingInconnueBugMajor(): void
    {
        $this->repartition->setInconnueBugMajor(static::$inconnueBugMajor);
        $this->assertEquals(static::$inconnueBugMajor, $this->repartition->getInconnueBugMajor());
    }

    public function testSettingAndGettingInconnueBugMinor(): void
    {
        $this->repartition->setInconnueBugMinor(static::$inconnueBugMinor);
        $this->assertEquals(static::$inconnueBugMinor, $this->repartition->getInconnueBugMinor());
    }

    public function testSettingAndGettingInconnueBugInfo(): void
    {
        $this->repartition->setInconnueBugInfo(static::$inconnueBugInfo);
        $this->assertEquals(static::$inconnueBugInfo, $this->repartition->getInconnueBugInfo());
    }

    public function testSettingAndGettingInconnueVulnerabilityBlocker(): void
    {
        $this->repartition->setInconnueVulnerabilityBlocker(static::$inconnueVulnerabilityBlocker);
        $this->assertEquals(static::$inconnueVulnerabilityBlocker, $this->repartition->getInconnueVulnerabilityBlocker());
    }

    public function testSettingAndGettingInconnueVulnerabilityCritical(): void
    {
        $this->repartition->setInconnueVulnerabilityCritical(static::$inconnueVulnerabilityCritical);
        $this->assertEquals(static::$inconnueVulnerabilityCritical, $this->repartition->getInconnueVulnerabilityCritical());
    }

    public function testSettingAndGettingInconnueVulnerabilityMajor(): void
    {
        $this->repartition->setInconnueVulnerabilityMajor(static::$inconnueVulnerabilityMajor);
        $this->assertEquals(static::$inconnueVulnerabilityMajor, $this->repartition->getInconnueVulnerabilityMajor());
    }

    public function testSettingAndGettingInconnueVulnerabilityMinor(): void
    {
        $this->repartition->setInconnueVulnerabilityMinor(static::$inconnueVulnerabilityMinor);
        $this->assertEquals(static::$inconnueVulnerabilityMinor, $this->repartition->getInconnueVulnerabilityMinor());
    }

    public function testSettingAndGettingInconnueVulnerabilityInfo(): void
    {
        $this->repartition->setInconnueVulnerabilityInfo(static::$inconnueVulnerabilityInfo);
        $this->assertEquals(static::$inconnueVulnerabilityInfo, $this->repartition->getInconnueVulnerabilityInfo());
    }

    public function testSettingAndGettingInconnueCodeSmellBlocker(): void
    {
        $this->repartition->setInconnueCodeSmellBlocker(static::$inconnueCodeSmellBlocker);
        $this->assertEquals(static::$inconnueCodeSmellBlocker, $this->repartition->getInconnueCodeSmellBlocker());
    }

    public function testSettingAndGettingInconnueCodeSmellCritical(): void
    {
        $this->repartition->setInconnueCodeSmellCritical(static::$inconnueCodeSmellCritical);
        $this->assertEquals(static::$inconnueCodeSmellCritical, $this->repartition->getInconnueCodeSmellCritical());
    }

    public function testSettingAndGettingInconnueCodeSmellMajor(): void
    {
        $this->repartition->setInconnueCodeSmellMajor(static::$inconnueCodeSmellMajor);
        $this->assertEquals(static::$inconnueCodeSmellMajor, $this->repartition->getInconnueCodeSmellMajor());
    }

    public function testSettingAndGettingInconnueCodeSmellMinor(): void
    {
        $this->repartition->setInconnueCodeSmellMinor(static::$inconnueCodeSmellMinor);
        $this->assertEquals(static::$inconnueCodeSmellMinor, $this->repartition->getInconnueCodeSmellMinor());
    }

    public function testSettingAndGettingInconnueCodeSmellInfo(): void
    {
        $this->repartition->setInconnueCodeSmellInfo(static::$inconnueCodeSmellInfo);
        $this->assertEquals(static::$inconnueCodeSmellInfo, $this->repartition->getInconnueCodeSmellInfo());
    }

    public function testSettingAndGettingSetup(): void
    {
        $this->repartition->setSetup(static::$setup);
        $this->assertEquals(static::$setup, $this->repartition->getSetup());
    }

    public function testSettingAndGettingModeCollecte(): void
    {
        $this->repartition->setModeCollecte(static::$modeCollecte);
        $this->assertEquals(static::$modeCollecte, $this->repartition->getModeCollecte());
    }

    public function testSettingAndGettingUtilisateurCollecte(): void
    {
        $this->repartition->setUtilisateurCollecte(static::$utilisateurCollecte);
        $this->assertEquals(static::$utilisateurCollecte, $this->repartition->getUtilisateurCollecte());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDate = new \DateTimeImmutable('2025-01-01 12:00:00+01');
        $this->repartition->setDateEnregistrement($newDate);
        $this->assertEquals($newDate, $this->repartition->getDateEnregistrement());
    }

    public function testInitialValues(): void
    {
        $this->assertEquals(static::$mavenKey, $this->repartition->getMavenKey());
        $this->assertEquals(static::$name, $this->repartition->getName());

        $this->assertEquals(static::$bugBlocker, $this->repartition->getBugBlocker());
        $this->assertEquals(static::$bugCritical, $this->repartition->getBugCritical());
        $this->assertEquals(static::$bugMajor, $this->repartition->getBugMajor());
        $this->assertEquals(static::$bugMinor, $this->repartition->getBugMinor());
        $this->assertEquals(static::$bugInfo, $this->repartition->getBugInfo());

        $this->assertEquals(static::$vulnerabilityBlocker, $this->repartition->getVulnerabilityBlocker());
        $this->assertEquals(static::$vulnerabilityCritical, $this->repartition->getVulnerabilityCritical());
        $this->assertEquals(static::$vulnerabilityMajor, $this->repartition->getVulnerabilityMajor());
        $this->assertEquals(static::$vulnerabilityMinor, $this->repartition->getVulnerabilityMinor());
        $this->assertEquals(static::$vulnerabilityInfo, $this->repartition->getVulnerabilityInfo());

        $this->assertEquals(static::$codeSmellBlocker, $this->repartition->getCodeSmellBlocker());
        $this->assertEquals(static::$codeSmellCritical, $this->repartition->getCodeSmellCritical());
        $this->assertEquals(static::$codeSmellMajor, $this->repartition->getCodeSmellMajor());
        $this->assertEquals(static::$codeSmellMinor, $this->repartition->getCodeSmellMinor());
        $this->assertEquals(static::$codeSmellInfo, $this->repartition->getCodeSmellInfo());

        $this->assertEquals(static::$frontend, $this->repartition->getFrontend());
        $this->assertEquals(static::$frontendBugBlocker, $this->repartition->getFrontendBugBlocker());
        $this->assertEquals(static::$frontendBugCritical, $this->repartition->getFrontendBugCritical());
        $this->assertEquals(static::$frontendBugMajor, $this->repartition->getFrontendBugMajor());
        $this->assertEquals(static::$frontendBugMinor, $this->repartition->getFrontendBugMinor());
        $this->assertEquals(static::$frontendBugInfo, $this->repartition->getFrontendBugInfo());

        $this->assertEquals(static::$backend, $this->repartition->getBackend());
        $this->assertEquals(static::$backendBugBlocker, $this->repartition->getBackendBugBlocker());
        $this->assertEquals(static::$backendBugCritical, $this->repartition->getBackendBugCritical());
        $this->assertEquals(static::$backendBugMajor, $this->repartition->getBackendBugMajor());
        $this->assertEquals(static::$backendBugMinor, $this->repartition->getBackendBugMinor());
        $this->assertEquals(static::$backendBugInfo, $this->repartition->getBackendBugInfo());

        $this->assertEquals(static::$autre, $this->repartition->getAutre());
        $this->assertEquals(static::$autreBugBlocker, $this->repartition->getAutreBugBlocker());
        $this->assertEquals(static::$autreBugCritical, $this->repartition->getAutreBugCritical());
        $this->assertEquals(static::$autreBugMajor, $this->repartition->getAutreBugMajor());
        $this->assertEquals(static::$autreBugMinor, $this->repartition->getAutreBugMinor());
        $this->assertEquals(static::$autreBugInfo, $this->repartition->getAutreBugInfo());

        $this->assertEquals(static::$inconnue, $this->repartition->getInconnue());
        $this->assertEquals(static::$inconnueBugBlocker, $this->repartition->getInconnueBugBlocker());
        $this->assertEquals(static::$inconnueBugCritical, $this->repartition->getInconnueBugCritical());
        $this->assertEquals(static::$inconnueBugMajor, $this->repartition->getInconnueBugMajor());
        $this->assertEquals(static::$inconnueBugMinor, $this->repartition->getInconnueBugMinor());
        $this->assertEquals(static::$inconnueBugInfo, $this->repartition->getInconnueBugInfo());

        $this->assertEquals(static::$control, $this->repartition->getControl());

        $this->assertEquals(static::$setup, $this->repartition->getSetup());
        $this->assertEquals(static::$modeCollecte, $this->repartition->getModeCollecte());
        $this->assertEquals(static::$utilisateurCollecte, $this->repartition->getUtilisateurCollecte());
        $this->assertEquals(new \DateTimeImmutable(static::$dateEnregistrement), $this->repartition->getDateEnregistrement());
    }

}
