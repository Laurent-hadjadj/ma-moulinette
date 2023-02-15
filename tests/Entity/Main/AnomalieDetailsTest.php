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

namespace App\Tests\Entity\Main;

use DateTime;
use App\Entity\Main\AnomalieDetails;
use App\Repository\Main\AnomalieDetailsRepository;
use PHPUnit\Framework\TestCase;

class AnomalieDetailsTest extends TestCase
{
  /**
   * [Description for dataset]
   * Jeu de données
   *
   * @return array
   *
   * Created at: 15/02/2023, 14:51:51 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function dataset(): array
  {
    return [
      ['id'=>1,
      'maven_key'=>'fr.franceagrimer:ma-moulinette',
      'name'=>'ma-moulinette',
      'bug_blocker'=>0, 'bug_critical'=>0, 'bug_info'=>0, 'bug_major'=>31,  'bug_minor'=>30,
      'vulnerability_blocker'=>0,'vulnerability_critical'=>0,
      'vulnerability_info'=>0,  'vulnerability_major'=>0, 'vulnerability_minor'=>0,
      'code_smell_blocker'=>17,'code_smell_critical'=>133,'code_smell_info'=>2,
      'code_smell_major'=>1087,'code_smell_minor'=>656,
      'date_enregistrement'=> new DateTime()]
    ];
  }

  /**
   * [Description for testAnomalieDetailsFindAll]
   * On récupère l'ensemble des données, on fait un getMavenKey().
   * @return void
   *
   * Created at: 15/02/2023, 14:52:54 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testAnomalieDetailsFindAll(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(AnomalieDetailsRepository::class);
    $mockRepo->method('findAll')->willReturn($d);
    $u=$mockRepo->findAll();
    $this->assertEquals('ma-moulinette', $u[0]['name']);
  }

  /**
   * [Description for testAnomalieDetailsCountAttribut]
   * On vérifie le nombre d'attribut
   * @return void
   *
   * Created at: 15/02/2023, 14:53:07 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testAnomalieDetailsCountAttribut(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(AnomalieDetails::class);
    /**
     * On compte le nombre attribut de la classe
     * On enlève :
     * __phpunit_originalObject, __phpunit_returnValueGeneration,__phpunit_invocationMocker
     */
    $nb=count((array)$mockRepo)-3;
    $this->assertEquals(count($d[0]), $nb);
  }

  /**
   * [Description for testAnomalieDetailsCount]
   * On compte le nombre d'enregistrement dans la collection.
   *
   * @return void
   *
   * Created at: 15/02/2023, 14:53:21 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testAnomalieDetailsCount(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(AnomalieDetailsRepository::class);
    $mockRepo->method('findAll')->willReturn($d);
    $u=$mockRepo->findAll();
    $this->assertCount(1, $u);
  }

  /**
   * [Description for testAnomalieDetailsType]
   * On test le type
   * @return void
   *
   * Created at: 15/02/2023, 13:51:21 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testAnomalieDetailsType(): void
  {
    /** On récupère le jeu de données */
    $dd=static::dataset();
    $d=$dd[0];

    $p = new AnomalieDetails();
    $p->setId($d['id']);
    $p->setMavenKey($d['maven_key']);
    $p->setName($d['name']);
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

    $this->assertIsInt($p->getId());
    $this->assertIsString($p->getMavenKey());
    $this->assertIsString($p->getName());
    $this->assertIsInt($p->getBugBlocker());
    $this->assertIsInt($p->getBugCritical());
    $this->assertIsInt($p->getBugInfo());
    $this->assertIsInt($p->getBugMajor());
    $this->assertIsInt($p->getBugMinor());
    $this->assertIsInt($p->getVulnerabilityBlocker());
    $this->assertIsInt($p->getVulnerabilityCritical());
    $this->assertIsInt($p->getVulnerabilityInfo());
    $this->assertIsInt($p->getVulnerabilityMajor());
    $this->assertIsInt($p->getVulnerabilityMinor());
    $this->assertIsInt($p->getCodeSmellBlocker());
    $this->assertIsInt($p->getCodeSmellCritical());
    $this->assertIsInt($p->getCodeSmellMajor());
    $this->assertIsInt($p->getCodeSmellMinor());
    $this->assertIsInt($p->getCodeSmellInfo());
    $this->assertIsObject($p->getDateEnregistrement());
  }

  /**
   * [Description for testAnomalieDetails]
   * test des getter/setter
   *
   * @return void
   *
   * Created at: 15/02/2023, 15:03:43 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testAnomalieDetails(): void
  {
    /** On récupère le jeu de données */
    $dd=static::dataset();
    $d=$dd[0];

    $p = new AnomalieDetails();
    $p->setId($d['id']);
    $p->setMavenKey($d['maven_key']);
    $p->setName($d['name']);
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

    $this->assertEquals($d['id'], $p->getId());
    $this->assertSame($d['maven_key'],$p->getMavenKey());
    $this->assertSame($d['name'],$p->getName());
    $this->assertSame($d['bug_blocker'],$p->getBugBlocker());
    $this->assertEquals($d['bug_critical'],$p->getBugCritical());
    $this->assertEquals($d['bug_major'],$p->getBugMajor());
    $this->assertEquals($d['bug_minor'],$p->getBugMinor());
    $this->assertEquals($d['bug_info'],$p->getBugInfo());
    $this->assertEquals($d['vulnerability_blocker'],$p->getVulnerabilityBlocker());
    $this->assertEquals($d['vulnerability_critical'],$p->getVulnerabilityCritical());
    $this->assertEquals($d['vulnerability_info'],$p->getVulnerabilityInfo());
    $this->assertEquals($d['vulnerability_major'],$p->getVulnerabilityMajor());
    $this->assertEquals($d['vulnerability_minor'],$p->getVulnerabilityMinor());
    $this->assertEquals($d['code_smell_blocker'],$p->getCodeSmellBlocker());
    $this->assertEquals($d['code_smell_critical'],$p->getCodeSmellCritical());
    $this->assertEquals($d['code_smell_info'],$p->getCodeSmellInfo());
    $this->assertEquals($d['code_smell_major'],$p->getCodeSmellMajor());
    $this->assertEquals($d['code_smell_minor'],$p->getCodeSmellMinor());
    $this->assertSame($d['date_enregistrement'],$p->getDateEnregistrement());
  }

}
