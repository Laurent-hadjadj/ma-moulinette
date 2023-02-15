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
use App\Entity\Main\Anomalie;
use App\Repository\Main\AnomalieRepository;
use PHPUnit\Framework\TestCase;

class AnomalieTest extends TestCase
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
      'project_name'=>'ma-moulinette', 'anomalie_total'=>1956, 'dette_minute'=>19586,
      'dette_reliability_minute'=>107, 'dette_vulnerability_minute'=>0,'dette_code_smell_minute'=>7369,
      'dette_reliability'=>'0h:5min', 'dette_vulnerability'=>'0h:0min',
      'dette'=>'4d, 19h:32min', 'dette_code_smell'=>'5d, 2h:49min',
      'frontend'=>806, 'backend'=>0, 'autre'=>0,
      'blocker'=>0, 'critical'=>0, 'major'=>475, 'info'=>0, 'minor'=>222,
      'bug'=>0, 'vulnerability'=>0, 'code_smell'=>801, 'liste'=>1,
      'date_enregistrement'=> new DateTime()]
    ];
  }

  /**
   * [Description for testAnomalieFindAll]
   * On récupère l'ensemble des données, on fait un getMavenKey().
   * @return void
   *
   * Created at: 15/02/2023, 15:20:46 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testAnomalieFindAll(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(AnomalieRepository::class);
    $mockRepo->method('findAll')->willReturn($d);
    $u=$mockRepo->findAll();
    $this->assertEquals('ma-moulinette', $u[0]['project_name']);
  }

  /**
   * [Description for testAnomalieCountAttribut]
   * On vérifie le nombre d'attribut
   * @return void
   *
   * Created at: 15/02/2023, 15:21:06 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testAnomalieCountAttribut(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(Anomalie::class);
    /**
     * On compte le nombre attribut de la classe
     * On enlève :
     * __phpunit_originalObject, __phpunit_returnValueGeneration,__phpunit_invocationMocker
     */
    $nb=count((array)$mockRepo)-3;
    $this->assertEquals(count($d[0]), $nb);
  }

  /**
   * [Description for testAnomalieCount]
   * On compte le nombre d'enregistrement dans la collection.
   *
   * @return void
   *
   * Created at: 15/02/2023, 15:21:19 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testAnomalieCount(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(AnomalieRepository::class);
    $mockRepo->method('findAll')->willReturn($d);
    $u=$mockRepo->findAll();
    $this->assertCount(1, $u);
  }

  /**
   * [Description for testAnomalieType]
   * On test le type
   * @return void
   *
   * Created at: 15/02/2023, 15:21:42 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testAnomalieType(): void
  {
    /** On récupère le jeu de données */
    $dd=static::dataset();
    $d=$dd[0];

    $p = new Anomalie();
    $p->setId($d['id']);
    $p->setMavenKey($d['maven_key']);
    $p->setProjectName($d['project_name']);
    $p->setAnomalieTotal($d['anomalie_total']);
    $p->setDetteMinute($d['dette_minute']);
    $p->setDetteReliabilityMinute($d['dette_reliability_minute']);
    $p->setDetteVulnerabilityMinute($d['dette_vulnerability_minute']);
    $p->setDetteCodeSmellMinute($d['dette_code_smell_minute']);
    $p->setDetteReliability($d['dette_reliability']);
    $p->setDetteVulnerability($d['dette_vulnerability']);
    $p->setDette($d['dette']);
    $p->setDetteCodeSmell($d['dette_code_smell']);
    $p->setFrontend($d['frontend']);
    $p->setBackend($d['backend']);
    $p->setAutre($d['autre']);
    $p->setBlocker($d['blocker']);
    $p->setCritical($d['critical']);
    $p->setMajor($d['major']);
    $p->setInfo($d['info']);
    $p->setMinor($d['minor']);
    $p->setBug($d['bug']);
    $p->setVulnerability($d['vulnerability']);
    $p->setCodeSmell($d['code_smell']);
    $p->setListe($d['liste']);
    $p->setDateEnregistrement($d['date_enregistrement']);

    $this->assertIsInt($p->getId());
    $this->assertIsString($p->getMavenKey());
    $this->assertIsString($p->getProjectName());
    $this->assertIsInt($p->getAnomalieTotal());
    $this->assertIsInt($p->getDetteReliabilityMinute());
    $this->assertIsInt($p->getDetteVulnerabilityMinute());
    $this->assertIsInt($p->getDetteCodeSmellMinute());
    $this->assertIsString($p->getDette());
    $this->assertIsString($p->getDetteReliability());
    $this->assertIsString($p->getDetteVulnerability());
    $this->assertIsString($p->getDetteCodeSmell());
    $this->assertIsInt($p->getFrontend());
    $this->assertIsInt($p->getBackend());
    $this->assertIsInt($p->getAutre());
    $this->assertIsInt($p->getBlocker());
    $this->assertIsInt($p->getCritical());
    $this->assertIsInt($p->getMajor());
    $this->assertIsInt($p->getMinor());
    $this->assertIsInt($p->getInfo());
    $this->assertIsInt($p->getVulnerability());
    $this->assertIsInt($p->getCodeSmell());
    $this->assertIsBool($p->isListe());
    $this->assertIsObject($p->getDateEnregistrement());
  }

  /**
   * [Description for testAnomalie]
   * test des getter/setter
   *
   * @return void
   *
   * Created at: 15/02/2023, 15:33:38 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testAnomalie(): void
  {
    /** On récupère le jeu de données */
    $dd=static::dataset();
    $d=$dd[0];

    $p = new Anomalie();
    $p->setId($d['id']);
    $p->setMavenKey($d['maven_key']);
    $p->setProjectName($d['project_name']);
    $p->setAnomalieTotal($d['anomalie_total']);
    $p->setDetteMinute($d['dette_minute']);
    $p->setDetteReliabilityMinute($d['dette_reliability_minute']);
    $p->setDetteVulnerabilityMinute($d['dette_vulnerability_minute']);
    $p->setDetteCodeSmellMinute($d['dette_code_smell_minute']);
    $p->setDetteReliability($d['dette_reliability']);
    $p->setDetteVulnerability($d['dette_vulnerability']);
    $p->setDette($d['dette']);
    $p->setDetteCodeSmell($d['dette_code_smell']);
    $p->setFrontend($d['frontend']);
    $p->setBackend($d['backend']);
    $p->setAutre($d['autre']);
    $p->setBlocker($d['blocker']);
    $p->setCritical($d['critical']);
    $p->setMajor($d['major']);
    $p->setInfo($d['info']);
    $p->setMinor($d['minor']);
    $p->setBug($d['bug']);
    $p->setVulnerability($d['vulnerability']);
    $p->setCodeSmell($d['code_smell']);
    $p->setListe($d['liste']);
    $p->setDateEnregistrement($d['date_enregistrement']);

    $this->assertEquals($d['id'], $p->getId());
    $this->assertSame($d['maven_key'],$p->getMavenKey());
    $this->assertSame($d['project_name'],$p->getProjectName());
    $this->assertSame($d['anomalie_total'],$p->getAnomalieTotal());
    $this->assertEquals($d['dette_reliability'],$p->getDetteReliability());
    $this->assertEquals($d['dette_vulnerability'],$p->getDetteVulnerability());
    $this->assertEquals($d['dette_code_smell'],$p->getDetteCodeSmell());
    $this->assertEquals($d['dette'],$p->getDette());
    $this->assertEquals($d['dette_reliability_minute'],$p->getDetteReliabilityMinute());
    $this->assertEquals($d['dette_vulnerability_minute'],$p->getDetteVulnerabilityMinute());
    $this->assertEquals($d['dette_code_smell_minute'],$p->getDetteCodeSmellMinute());
    $this->assertEquals($d['frontend'],$p->getFrontend());
    $this->assertEquals($d['backend'],$p->getBackend());
    $this->assertEquals($d['autre'],$p->getAutre());
    $this->assertEquals($d['blocker'],$p->getBlocker());
    $this->assertEquals($d['critical'],$p->getCritical());
    $this->assertEquals($d['major'],$p->getMajor());
    $this->assertEquals($d['minor'],$p->getMinor());
    $this->assertEquals($d['info'],$p->getInfo());
    $this->assertEquals($d['bug'],$p->getBug());
    $this->assertEquals($d['vulnerability'],$p->getVulnerability());
    $this->assertEquals($d['code_smell'],$p->getCodeSmell());
    $this->assertTrue(true,$p->getCodeSmell());
    $this->assertSame($d['date_enregistrement'],$p->getDateEnregistrement());
  }

}
