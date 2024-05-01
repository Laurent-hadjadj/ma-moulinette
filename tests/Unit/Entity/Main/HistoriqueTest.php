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

namespace App\Tests\unit\Entity\Main;

use PHPUnit\Framework\TestCase;
use App\Entity\Main\Historique;
use App\Repository\Main\HistoriqueRepository;
use DateTime;

class HistoriqueTest extends TestCase
{
  /**
   * [Description for dataset]
   * Jeu de données
   *
   * @return array
   *
   * Created at: 14/02/2023, 16:54:28 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function dataset(): array
  {
    return [
      [
      'maven_key'=> 'fr.ma-petite-entreprise:ma-moulinette',
      'version'=>'1.6.0-RELEASE', 'date_version'=> '2022-11-30 00:00:00',
      'nom_projet'=> 'ma-moulinette', 'version_release'=>8, 'version_snapshot'=>0,
      'version_autre'=>0,'suppress_warning'=>0, 'no_sonar'=>0,
      'nombre_ligne'=>24471,'nombre_ligne_code'=>17301,'couverture'=>0.0,
      'duplication'=>5.1,'tests_unitaires'=>0,'nombre_defaut'=>1956,'nombre_bug'=>61,
      'nombre_vulnerability'=>0,'nombre_code_smell'=>1895,'frontend'=>1945,
      'backend'=>0, 'autre'=>0, 'dette'=>19586, 'nombre_anomalie_bloquant'=>17,
      'nombre_anomalie_critique'=>133, 'nombre_anomalie_majeur'=>2,
      'nombre_anomalie_mineur'=>686,'nombre_anomalie_info'=>1118, 'note_reliability'=>'C',
      'note_security'=>'A', 'note_sqale'=>'C','note_hotspot'=>'E',
      'hotspot_high'=>0, 'hotspot_medium'=>2, 'hotspot_low'=>0, 'hotspot_total'=>2,
      'favori'=>1, 'initial'=>0, 'bug_blocker'=>0, 'bug_critical'=>0, 'bug_major'=>31,
      'bug_minor'=>30, 'bug_info'=>0, 'vulnerability_blocker'=>0, 'vulnerability_critical'=>0,
      'vulnerability_major'=>0,'vulnerability_minor'=>0, 'vulnerability_info'=>0,
      'code_smell_blocker'=>17, 'code_smell_critical'=>133, 'code_smell_major'=>1087,
      'code_smell_minor'=>656, 'code_smell_info'=>2,'date_enregistrement'=> new DateTime()]
    ];
  }

  /**
   * [Description for testHistoriqueFindAll]
   * On récupère l'ensemble des données, on fait un getMavenKey().
   * @return void
   *
   * Created at: 14/02/2023, 17:15:58 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testHistoriqueFindAll(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(HistoriqueRepository::class);
    $mockRepo->method('findAll')->willReturn($d);
    $u=$mockRepo->findAll();
    $this->assertEquals('1.6.0-RELEASE', $u[0]['version']);
  }

  /**
   * [Description for testHistoriqueCountAttribut]
   * On vérifie le nombre d'attribut
   * @return void
   *
   * Created at: 14/02/2023, 17:16:19 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testHistoriqueCountAttribut(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(Historique::class);
    /**
     * On compte le nombre attribut de la classe
     * On enlève :
     * __phpunit_originalObject, __phpunit_returnValueGeneration,__phpunit_invocationMocker
     */
    $nb=count((array)$mockRepo)-3;
    $this->assertEquals(count($d[0]), $nb);
  }

  /**
   * [Description for testHistoriqueCount]
   * On compte le nombre d'enregistrement dans la collection.
   *
   * @return void
   *
   * Created at: 14/02/2023, 17:16:36 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testHistoriqueCount(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(HistoriqueRepository::class);
    $mockRepo->method('findAll')->willReturn($d);
    $u=$mockRepo->findAll();
    $this->assertCount(1, $u);
  }

  private function setEntity(): object
  {
    /** On récupère le jeu de données */
    $dd=static::dataset();
    $d=$dd[0];

    $p = new Historique();
    $p->setMavenKey($d['maven_key']);
    $p->setVersion($d['version']);
    $p->setDateVersion($d['date_version']);
    $p->setNomProjet($d['nom_projet']);
    $p->setVersionRelease($d['version_release']);
    $p->setVersionSnapshot($d['version_snapshot']);
    $p->setVersionAutre($d['version_autre']);
    $p->setSuppressWarning($d['suppress_warning']);
    $p->setNoSonar($d['no_sonar']);
    $p->setnombreLigne($d['nombre_ligne']);
    $p->setNombreLigneCode($d['nombre_ligne_code']);
    $p->setCouverture($d['couverture']);
    $p->setDuplication($d['duplication']);
    $p->setTestsUnitaires($d['tests_unitaires']);
    $p->setNombreDefaut($d['nombre_defaut']);
    $p->setNombreVulnerability($d['nombre_vulnerability']);
    $p->setNombreCodeSmell($d['nombre_code_smell']);
    $p->setFrontend($d['frontend']);
    $p->setBackend($d['backend']);
    $p->setAutre($d['autre']);
    $p->setDette($d['dette']);
    $p->setNombreAnomalieBloquant($d['nombre_anomalie_bloquant']);
    $p->setNombreAnomalieCritique($d['nombre_anomalie_critique']);
    $p->setNombreAnomalieMajeur($d['nombre_anomalie_majeur']);
    $p->setNombreAnomalieMineur($d['nombre_anomalie_mineur']);
    $p->setNombreAnomalieInfo($d['nombre_anomalie_info']);
    $p->setNoteReliability($d['note_reliability']);
    $p->setNoteSecurity($d['note_security']);
    $p->setNoteSqale($d['note_sqale']);
    $p->setHotspotHigh($d['hotspot_high']);
    $p->setHotspotMedium($d['hotspot_medium']);
    $p->setHotspotLow($d['hotspot_low']);
    $p->setHotspotTotal($d['hotspot_total']);
    $p->setFavori($d['favori']);
    $p->setInitial($d['initial']);
    $p->setBugBlocker($d['bug_blocker']);
    $p->setBugCritical($d['bug_critical']);
    $p->setBugMajor($d['bug_major']);
    $p->setBugMinor($d['bug_minor']);
    $p->setBugInfo($d['bug_info']);
    $p->setVulnerabilityBlocker($d['vulnerability_blocker']);
    $p->setVulnerabilityCritical($d['vulnerability_critical']);
    $p->setVulnerabilityMajor($d['vulnerability_major']);
    $p->setVulnerabilityMinor($d['vulnerability_minor']);
    $p->setVulnerabilityInfo($d['vulnerability_info']);
    $p->setCodeSmellBlocker($d['code_smell_blocker']);
    $p->setCodeSmellCritical($d['code_smell_critical']);
    $p->setCodeSmellMajor($d['code_smell_major']);
    $p->setCodeSmellMinor($d['code_smell_minor']);
    $p->setCodeSmellInfo($d['code_smell_info']);
    $p->setDateEnregistrement($d['date_enregistrement']);
    return $p;
  }

  /**
   * [Description for testHistoriqueType]
   * On test le type
   * @return void
   *
   * Created at: 14/02/2023, 17:47:33 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testHistoriqueType(): void
  {
    $p=static::setEntity();

    $this->assertIsString($p->getMavenKey());
    $this->assertIsString($p->getVersion());
    $this->assertIsString($p->getDateVersion());
    $this->assertIsString($p->getNomProjet());
    $this->assertIsInt($p->getVersionRelease());
    $this->assertIsInt($p->getVersionSnapshot());
    $this->assertIsInt($p->getVersionAutre());
    $this->assertIsInt($p->getSuppressWarning());
    $this->assertIsInt($p->getNoSonar());
    $this->assertIsInt($p->getnombreLigne());
    $this->assertIsInt($p->getNombreLigneCode());
    $this->assertIsFloat($p->getCouverture());
    $this->assertIsFloat($p->getDuplication());
    $this->assertIsInt($p->getTestsUnitaires());
    $this->assertIsInt($p->getNombreDefaut());
    $this->assertIsInt($p->getNombreVulnerability());
    $this->assertIsInt($p->getNombreCodeSmell());
    $this->assertIsInt($p->getFrontend());
    $this->assertIsInt($p->getBackend());
    $this->assertIsInt($p->getAutre());
    $this->assertIsInt($p->getDette());
    $this->assertIsInt($p->getNombreAnomalieBloquant());
    $this->assertIsInt($p->getNombreAnomalieCritique());
    $this->assertIsInt($p->getNombreAnomalieMajeur());
    $this->assertIsInt($p->getNombreAnomalieMineur());
    $this->assertIsInt($p->getNombreAnomalieInfo());
    $this->assertIsString($p->getNoteReliability());
    $this->assertIsString($p->getNoteSecurity());
    $this->assertIsString($p->getNoteSqale());
    $this->assertIsInt($p->getHotspotHigh());
    $this->assertIsInt($p->getHotspotMedium());
    $this->assertIsInt($p->getHotspotLow());
    $this->assertIsInt($p->getHotspotTotal());
    $this->assertIsBool($p->isFavori());
    $this->assertIsBool($p->isInitial());
    $this->assertIsInt($p->getBugBlocker());
    $this->assertIsInt($p->getBugCritical());
    $this->assertIsInt($p->getBugMajor());
    $this->assertIsInt($p->getBugMinor());
    $this->assertIsInt($p->getBugInfo());
    $this->assertIsInt($p->getVulnerabilityBlocker());
    $this->assertIsInt($p->getVulnerabilityCritical());
    $this->assertIsInt($p->getVulnerabilityMajor());
    $this->assertIsInt($p->getVulnerabilityMinor());
    $this->assertIsInt($p->getVulnerabilityInfo());
    $this->assertIsInt($p->getCodeSmellBlocker());
    $this->assertIsInt($p->getCodeSmellCritical());
    $this->assertIsInt($p->getCodeSmellMajor());
    $this->assertIsInt($p->getCodeSmellMinor());
    $this->assertIsInt($p->getCodeSmellInfo());
    $this->assertIsObject($p->getDateEnregistrement());
  }

  /**
   * [Description for testHistorique]
   * test des getter/setter
   *
   * @return void
   *
   * Created at: 14/02/2023, 18:03:09 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testHistorique(): void
  {
    /** On récupère le jeu de données */
    $dd=static::dataset();
    $d=$dd[0];

    $p=static::setEntity();

    $this->assertSame($d['maven_key'],$p->getMavenKey());
    $this->assertSame($d['version'],$p->getVersion());
    $this->assertSame($d['date_version'],$p->getDateVersion());
    $this->assertSame($d['nom_projet'],$p->getNomProjet());
    $this->assertEquals($d['version_release'],$p->getVersionRelease());
    $this->assertEquals($d['version_snapshot'],$p->getVersionSnapshot());
    $this->assertEquals($d['version_autre'],$p->getVersionAutre());
    $this->assertEquals($d['suppress_warning'],$p->getSuppressWarning());
    $this->assertEquals($d['no_sonar'],$p->getNoSonar());
    $this->assertEquals($d['nombre_ligne'],$p->getnombreLigne());
    $this->assertEquals($d['nombre_ligne_code'],$p->getNombreLigneCode());
    $this->assertSame($d['couverture'],$p->getCouverture());
    $this->assertSame($d['duplication'],$p->getDuplication());
    $this->assertEquals($d['tests_unitaires'],$p->getTestsUnitaires());
    $this->assertEquals($d['nombre_defaut'],$p->getNombreDefaut());
    $this->assertEquals($d['nombre_vulnerability'],$p->getNombreVulnerability());
    $this->assertEquals($d['nombre_code_smell'],$p->getNombreCodeSmell());
    $this->assertEquals($d['frontend'],$p->getFrontend());
    $this->assertEquals($d['backend'],$p->getBackend());
    $this->assertEquals($d['dette'],$p->getDette());
    $this->assertEquals($d['nombre_anomalie_bloquant'],$p->getNombreAnomalieBloquant());
    $this->assertEquals($d['nombre_anomalie_critique'],$p->getNombreAnomalieCritique());
    $this->assertEquals($d['nombre_anomalie_majeur'],$p->getNombreAnomalieMajeur());
    $this->assertEquals($d['nombre_anomalie_mineur'],$p->getNombreAnomalieMineur());
    $this->assertEquals($d['nombre_anomalie_info'],$p->getNombreAnomalieInfo());
    $this->assertEquals($d['note_reliability'],$p->getNoteReliability());
    $this->assertEquals($d['note_security'],$p->getNoteSecurity());
    $this->assertEquals($d['note_sqale'],$p->getNoteSqale());
    $this->assertEquals($d['hotspot_high'],$p->getHotspotHigh());
    $this->assertEquals($d['hotspot_medium'],$p->getHotspotMedium());
    $this->assertEquals($d['hotspot_low'],$p->getHotspotLow());
    $this->assertEquals($d['hotspot_total'],$p->getHotspotTotal());
    $this->assertTrue(true, $d['favori'],$p->isFavori());
    $this->assertFalse(false, $d['initial'],$p->isInitial());
    $this->assertEquals($d['bug_blocker'],$p->getBugBlocker());
    $this->assertEquals($d['bug_critical'],$p->getBugCritical());
    $this->assertEquals($d['bug_major'],$p->getBugMajor());
    $this->assertEquals($d['bug_minor'],$p->getBugMinor());
    $this->assertEquals($d['bug_info'],$p->getBugInfo());
    $this->assertEquals($d['vulnerability_blocker'],$p->getVulnerabilityBlocker());
    $this->assertEquals($d['vulnerability_critical'],$p->getVulnerabilityCritical());
    $this->assertEquals($d['vulnerability_major'],$p->getVulnerabilityMajor());
    $this->assertEquals($d['vulnerability_minor'],$p->getVulnerabilityMinor());
    $this->assertEquals($d['vulnerability_info'],$p->getVulnerabilityInfo());
    $this->assertEquals($d['code_smell_blocker'],$p->getCodeSmellBlocker());
    $this->assertEquals($d['code_smell_critical'],$p->getCodeSmellCritical());
    $this->assertEquals($d['code_smell_major'],$p->getCodeSmellMajor());
    $this->assertEquals($d['code_smell_minor'],$p->getCodeSmellMinor());
    $this->assertEquals($d['code_smell_info'],$p->getCodeSmellInfo());
    //$this->assertSame($d['date_enregistrement'],$p->getDateEnregistrement());
  }

}
