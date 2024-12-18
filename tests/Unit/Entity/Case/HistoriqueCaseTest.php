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

namespace App\Tests\Unit\Entity\Case;

use App\Entity\Historique;
use PHPUnit\Framework\TestCase;

/**
 * [Description HistoriqueCaseTest]
 */
class HistoriqueCaseTest extends TestCase
{
    private $historique;


    private function getEntity(): Historique
    {
        return (new historique())
        ->setMavenKey('fr.ma-petite-entreprise:ma-moulinette')
        ->setAnalyseKey('AZCc05qWgfifxdiJPzns')
        ->setVersion('1.2.0-RELEASE')
        ->setDateVersion('2024-07-12 16:34:46')
        ->setNomProjet('ma-moulinette')
        ->setVersionRelease('0')
        ->setVersionSnapshot('0')
        ->setVersionAutre('1')
        ->setSuppressWarning('8')
        ->setNoSonar('0')
        ->setTodo('17')
        ->setLoggerInfo('14')
        ->setLoggerWarn('0')
        ->setLoggerError('15')
        ->setLoggerDebug('8')
        ->setNombreLigne('17049')
        ->setNombreLigneCode('8928')
        ->setCoverage('50.1')
        ->setDuplicatedLinesDensity('0.2')
        ->setSqaleDebtRatio('1')
        ->setTests('55')
        ->setViolations('295')
        ->setDette('3054')
        ->setNombreBug('88')
        ->setNombreVulnerability('9')
        ->setNombreCodeSmell('198')
        ->setBugBlocker('7')
        ->setBugCritical('0')
        ->setBugMajor('44')
        ->setBugMinor('0')
        ->setBugInfo('37')
        ->setVulnerabilityBlocker('0')
        ->setVulnerabilityCritical('9')
        ->setVulnerabilityMajor('0')
        ->setVulnerabilityMinor('0')
        ->setVulnerabilityInfo('0')
        ->setCodeSmellBlocker('0')
        ->setCodeSmellCritical('4')
        ->setCodeSmellMajor('109')
        ->setCodeSmellMinor('13')
        ->setCodeSmellInfo('72')
        ->setFrontend('21')
        ->setBackend('136')
        ->setAutre('0')
        ->setNombreAnomalieBloquant('7')
        ->setNombreAnomalieCritique('13')
        ->setNombreAnomalieMajeur('153')
        ->setNombreAnomalieMineur('13')
        ->setNombreAnomalieInfo('109')
        ->setNoteReliability('E')
        ->setNoteSecurity('D')
        ->setNoteSqale('A')
        ->setNoteHotspot('A')
        ->setNombreHotspot('0')
        ->setHotspotHigh('0')
        ->setHotspotMedium('0')
        ->setHotspotLow('0')
        ->setInitial('false')
        ->setModeCollecte('COLLECTE')
        ->setUtilisateurCollecte('admin@ma-moulinette.fr')
        ->setDateEnregistrement(new \DateTimeImmutable('2024-06-28 17:55:45+02'));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->historique = $this->getEntity();
    }

    public function testSettingAndGettingMavenKey(): void
    {
        $this->historique->setMavenKey('fr.ma-petite-entreprise:ma-moulinette');
        $this->assertEquals('fr.ma-petite-entreprise:ma-moulinette', $this->historique->getMavenKey());
    }

    public function testSettingAndGettingAnalyseKey(): void
    {
        $this->historique->setAnalyseKey('AZCc05qWgfifxdiJPzns');
        $this->assertEquals('AZCc05qWgfifxdiJPzns', $this->historique->getAnalyseKey());
    }

    public function testSettingAndGettingVersion(): void
    {
        $this->historique->setVersion('1.2.0-RELEASE');
        $this->assertEquals('1.2.0-RELEASE', $this->historique->getVersion());
    }

    public function testSettingAndGettingDateVersion(): void
    {
        $this->historique->setDateVersion('2024-07-12 16:34:46');
        $this->assertEquals('2024-07-12 16:34:46', $this->historique->getDateVersion());
    }

    public function testSettingAndGettingNomProjet(): void
    {
        $this->historique->setNomProjet('ma-moulinette');
        $this->assertEquals('ma-moulinette', $this->historique->getNomProjet());
    }

    public function testSettingAndGettingVersionRelease(): void
    {
        $this->historique->setVersionRelease('0');
        $this->assertEquals('0', $this->historique->getVersionRelease());
    }

    public function testSettingAndGettingVersionSnapshot(): void
    {
        $this->historique->setVersionSnapshot('0');
        $this->assertEquals('0', $this->historique->getVersionSnapshot());
    }

    public function testSettingAndGettingVersionAutre(): void
    {
        $this->historique->setVersionAutre('1');
        $this->assertEquals('1', $this->historique->getVersionAutre());
    }

    public function testSettingAndGettingSuppressWarning(): void
    {
        $this->historique->setSuppressWarning('8');
        $this->assertEquals('8', $this->historique->getSuppressWarning());
    }

    public function testSettingAndGettingNoSonar(): void
    {
        $this->historique->setNoSonar('0');
        $this->assertEquals('0', $this->historique->getNoSonar());
    }

    public function testSettingAndGettingTodo(): void
    {
        $this->historique->setTodo('17');
        $this->assertEquals('17', $this->historique->getTodo());
    }

    public function testSettingAndGettingLoggerInfo(): void
    {
        $this->historique->setLoggerInfo('14');
        $this->assertEquals('14', $this->historique->getLoggerInfo());
    }

    public function testSettingAndGettingLoggerWarn(): void
    {
        $this->historique->setLoggerWarn('0');
        $this->assertEquals('0', $this->historique->getLoggerWarn());
    }

    public function testSettingAndGettingLoggerError(): void
    {
        $this->historique->setLoggerError('15');
        $this->assertEquals('15', $this->historique->getLoggerError());
    }

    public function testSettingAndGettingLoggerDebug(): void
    {
        $this->historique->setLoggerDebug('8');
        $this->assertEquals('8', $this->historique->getLoggerDebug());
    }

    public function testSettingAndGettingNombreLigne(): void
    {
        $this->historique->setNombreLigne('17049');
        $this->assertEquals('17049', $this->historique->getNombreLigne());
    }

    public function testSettingAndGettingNombreLigneCode(): void
    {
        $this->historique->setNombreLigneCode('8928');
        $this->assertEquals('8928', $this->historique->getNombreLigneCode());
    }

    public function testSettingAndGettingCoverage(): void
    {
        $this->historique->setCoverage('50.1');
        $this->assertEquals('50.1', $this->historique->getCoverage());
    }

    public function testSettingAndGettingDuplicatedLinesDensity(): void
    {
        $this->historique->setDuplicatedLinesDensity('0.2');
        $this->assertEquals('0.2', $this->historique->getDuplicatedLinesDensity());
    }

    public function testSettingAndGettingSqaleDebtRatio(): void
    {
        $this->historique->setSqaleDebtRatio('1');
        $this->assertEquals('1', $this->historique->getSqaleDebtRatio());
    }

    public function testSettingAndGettingTests(): void
    {
        $this->historique->setTests('55');
        $this->assertEquals('55', $this->historique->getTests());
    }

    public function testSettingAndGettingViolations(): void
    {
        $this->historique->setViolations('295');
        $this->assertEquals('295', $this->historique->getViolations());
    }

    public function testSettingAndGettingDette(): void
    {
        $this->historique->setDette('3054');
        $this->assertEquals('3054', $this->historique->getDette());
    }

    public function testSettingAndGettingNombreBug(): void
    {
        $this->historique->setNombreBug('88');
        $this->assertEquals('88', $this->historique->getNombreBug());
    }

    public function testSettingAndGettingNombreVulnerability(): void
    {
        $this->historique->setNombreVulnerability('9');
        $this->assertEquals('9', $this->historique->getNombreVulnerability());
    }

    public function testSettingAndGettingNombreCodeSmell(): void
    {
        $this->historique->setNombreCodeSmell('198');
        $this->assertEquals('198', $this->historique->getNombreCodeSmell());
    }

    public function testSettingAndGettingBugBlocker(): void
    {
        $this->historique->setBugBlocker('7');
        $this->assertEquals('7', $this->historique->getBugBlocker());
    }

    public function testSettingAndGettingBugCritical(): void
    {
        $this->historique->setBugCritical('0');
        $this->assertEquals('0', $this->historique->getBugCritical());
    }

    public function testSettingAndGettingBugMajor(): void
    {
        $this->historique->setBugMajor('44');
        $this->assertEquals('44', $this->historique->getBugMajor());
    }

    public function testSettingAndGettingBugMinor(): void
    {
        $this->historique->setBugMinor('0');
        $this->assertEquals('0', $this->historique->getBugMinor());
    }

    public function testSettingAndGettingBugInfo(): void
    {
        $this->historique->setBugInfo('37');
        $this->assertEquals('37', $this->historique->getBugInfo());
    }

    public function testSettingAndGettingVulnerabilityBlocker(): void
    {
        $this->historique->setVulnerabilityBlocker('0');
        $this->assertEquals('0', $this->historique->getVulnerabilityBlocker());
    }

    public function testSettingAndGettingVulnerabilityCritical(): void
    {
        $this->historique->setVulnerabilityCritical('9');
        $this->assertEquals('9', $this->historique->getVulnerabilityCritical());
    }

    public function testSettingAndGettingVulnerabilityMajor(): void
    {
        $this->historique->setVulnerabilityMajor('0');
        $this->assertEquals('0', $this->historique->getVulnerabilityMajor());
    }

    public function testSettingAndGettingVulnerabilityMinor(): void
    {
        $this->historique->setVulnerabilityMinor('0');
        $this->assertEquals('0', $this->historique->getVulnerabilityMinor());
    }

    public function testSettingAndGettingVulnerabilityInfo(): void
    {
        $this->historique->setVulnerabilityInfo('0');
        $this->assertEquals('0', $this->historique->getVulnerabilityInfo());
    }

    public function testSettingAndGettingCodeSmellBlocker(): void
    {
        $this->historique->setCodeSmellBlocker('0');
        $this->assertEquals('0', $this->historique->getCodeSmellBlocker());
    }

    public function testSettingAndGettingCodeSmellCritical(): void
    {
        $this->historique->setCodeSmellCritical('4');
        $this->assertEquals('4', $this->historique->getCodeSmellCritical());
    }

    public function testSettingAndGettingCodeSmellMajor(): void
    {
        $this->historique->setCodeSmellMajor('109');
        $this->assertEquals('109', $this->historique->getCodeSmellMajor());
    }

    public function testSettingAndGettingCodeSmellMinor(): void
    {
        $this->historique->setCodeSmellMinor('13');
        $this->assertEquals('13', $this->historique->getCodeSmellMinor());
    }

    public function testSettingAndGettingCodeSmellInfo(): void
    {
        $this->historique->setCodeSmellInfo('72');
        $this->assertEquals('72', $this->historique->getCodeSmellInfo());
    }

    public function testSettingAndGettingFrontend(): void
    {
        $this->historique->setFrontend('21');
        $this->assertEquals('21', $this->historique->getFrontend());
    }

    public function testSettingAndGettingBackend(): void
    {
        $this->historique->setBackend('136');
        $this->assertEquals('136', $this->historique->getBackend());
    }

    public function testSettingAndGettingAutre(): void
    {
        $this->historique->setAutre('0');
        $this->assertEquals('0', $this->historique->getAutre());
    }

    public function testSettingAndGettingNombreAnomalieBloquant(): void
    {
        $this->historique->setNombreAnomalieBloquant('7');
        $this->assertEquals('7', $this->historique->getNombreAnomalieBloquant());
    }

    public function testSettingAndGettingNombreAnomalieCritique(): void
    {
        $this->historique->setNombreAnomalieCritique('13');
        $this->assertEquals('13', $this->historique->getNombreAnomalieCritique());
    }

    public function testSettingAndGettingNombreAnomalieMajeur(): void
    {
        $this->historique->setNombreAnomalieMajeur('153');
        $this->assertEquals('153', $this->historique->getNombreAnomalieMajeur());
    }

    public function testSettingAndGettingNombreAnomalieMineur(): void
    {
        $this->historique->setNombreAnomalieMineur('13');
        $this->assertEquals('13', $this->historique->getNombreAnomalieMineur());
    }

    public function testSettingAndGettingNombreAnomalieInfo(): void
    {
        $this->historique->setNombreAnomalieInfo('109');
        $this->assertEquals('109', $this->historique->getNombreAnomalieInfo());
    }

    public function testSettingAndGettingNoteReliability(): void
    {
        $this->historique->setNoteReliability('E');
        $this->assertEquals('E', $this->historique->getNoteReliability());
    }

    public function testSettingAndGettingNoteSecurity(): void
    {
        $this->historique->setNoteSecurity('D');
        $this->assertEquals('D', $this->historique->getNoteSecurity());
    }

    public function testSettingAndGettingNoteSqale(): void
    {
        $this->historique->setNoteSqale('A');
        $this->assertEquals('A', $this->historique->getNoteSqale());
    }

    public function testSettingAndGettingNoteHotspot(): void
    {
        $this->historique->setNoteHotspot('A');
        $this->assertEquals('A', $this->historique->getNoteHotspot());
    }

    public function testSettingAndGettingNombreHotspot(): void
    {
        $this->historique->setNombreHotspot('0');
        $this->assertEquals('0', $this->historique->getNombreHotspot());
    }

    public function testSettingAndGettingHotspotHigh(): void
    {
        $this->historique->setHotspotHigh('0');
        $this->assertEquals('0', $this->historique->getHotspotHigh());
    }

    public function testSettingAndGettingHotspotMedium(): void
    {
        $this->historique->setHotspotMedium('0');
        $this->assertEquals('0', $this->historique->getHotspotMedium());
    }

    public function testSettingAndGettingHotspotLow(): void
    {
        $this->historique->setHotspotLow('0');
        $this->assertEquals('0', $this->historique->getHotspotLow());
    }

    public function testSettingAndGettingInitial(): void
    {
        $this->historique->setInitial('false');
        $this->assertEquals('false', $this->historique->isInitial());
    }

    public function testSettingAndGettingModeCollecte(): void
    {
        $this->historique->setModeCollecte('COLLECTE');
        $this->assertEquals('COLLECTE', $this->historique->getModeCollecte());
    }

    public function testSettingAndGettingUtilisateurCollecte(): void
    {
        $this->historique->setUtilisateurCollecte('admin@ma-moulinette.fr');
        $this->assertEquals('admin@ma-moulinette.fr', $this->historique->getUtilisateurCollecte());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDate = new \DateTimeImmutable('2024-07-12 16:34:46+2000');
        $this->historique->setDateEnregistrement($newDate);
        $this->assertEquals($newDate, $this->historique->getDateEnregistrement());
    }

}
