<?php

namespace App\DataFixtures;



use App\Entity\Historique;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * [Description HistoriqueFixtures]
 */
class HistoriqueFixtures extends Fixture
{

  /**
   * [Description for load]
   * Chargement des utilisateurs
   *
   * @param ObjectManager $manager
   *
   * @return void
   *
   * Created at: 05/05/2024 18:43:05 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function load(ObjectManager $manager): void
    {
      $historique = (new Historique())
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
          ->setFiles('180')
          ->setFunctions('226')
          ->setClasses('123')
          ->setFunctions('457')
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
          ->setInitial('true')
          ->setModeCollecte('COLLECTE')
          ->setUtilisateurCollecte('admin@ma-moulinette.fr')
          ->setDateEnregistrement(new \DateTimeImmutable('2024-06-28 17:55:45+02'));
          $manager->persist($historique);

          $historique2 = (new Historique())
          ->setMavenKey('fr.ma-petite-entreprise:ma-moulinette')
          ->setAnalyseKey('AZCc05qWgfifxdiJPzns')
          ->setVersion('1.2.3-RELEASE')
          ->setDateVersion('2024-08-18 15:54:26')
          ->setNomProjet('ma-moulinette')
          ->setVersionRelease('1')
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
          ->setFiles('180')
          ->setFunctions('226')
          ->setClasses('123')
          ->setFunctions('457')
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
          ->setDateEnregistrement(new \DateTimeImmutable('2024-08-28 14:25:15+02'));
          $manager->persist($historique2);

      /** Enregistrement des données dans la base de tests */
        $manager->flush();
    }
  }
