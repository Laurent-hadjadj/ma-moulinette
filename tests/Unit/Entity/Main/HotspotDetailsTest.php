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
use App\Entity\Main\HotspotDetails;
use App\Repository\Main\HotspotDetailsRepository;
use DateTime;

class HotspotDetailsTest extends TestCase
{
  /**
   * [Description for dataset]
   * Jeu de données
   *
   * @return array
   *
   * Created at: 14/02/2023, 16:35:33 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function dataset(): array
  {
    return [
      ['id'=>1, 'maven_key'=> 'fr.ma-petite-entreprise:ma-moulinette',
      'severity'=> 'HIGH',
      'niveau'=> '1',
      'status'=> 'NC',
      'frontend'=> '100',
      'backend'=>'100',
      'autre'=> '0',
      'file'=>'path',
      'line'=>123,
      'message'=>'message sonarqube',
      'rule'=>'description sonarqube',
      'key'=>'xxxxxxxxx',
      'date_enregistrement'=> new DateTime()]
    ];
  }

  /**
   * [Description for testHotspotDetailsFindAll]
   * On récupère l'ensemble des données, on fait un getMavenKey().
   * @return void
   *
   * Created at: 14/02/2023, 16:39:16 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testHotspotDetailsFindAll(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(HotspotDetailsRepository::class);
    $mockRepo->method('findAll')->willReturn($d);
    $u=$mockRepo->findAll();
    $this->assertEquals('100', $u[0]['frontend']);
  }

  /**
   * [Description for testHotspotDetailsCountAttribut]
   * On vérifie le nombre d'attribut
   * @return void
   *
   * Created at: 14/02/2023, 16:40:02 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testHotspotDetailsCountAttribut(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(HotspotDetails::class);
    /**
     * On compte le nombre attribut de la classe
     * On enlève :
     * __phpunit_originalObject, __phpunit_returnValueGeneration,__phpunit_invocationMocker
     */
    $nb=count((array)$mockRepo)-3;
    $this->assertEquals(count($d[0]), $nb);
  }

  /**
   * [Description for testHotspotDetailsCount]
   * On compte le nombre d'enregistrement dans la collection.
   *
   * @return void
   *
   * Created at: 14/02/2023, 16:40:19 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testHotspotDetailsCount(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(HotspotDetailsRepository::class);
    $mockRepo->method('findAll')->willReturn($d);
    $u=$mockRepo->findAll();
    $this->assertCount(1, $u);
  }

  /**
   * [Description for testHotspotDetailsType]
   * On test le type
   * @return void
   *
   * Created at: 14/02/2023, 16:40:40 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testHotspotDetailsType(): void
  {
    /** On récupère le jeu de données */
    $dd=static::dataset();
    $d=$dd[0];

    $p = new HotspotDetails();
    $p->setId($d['id']);
    $p->setMavenKey($d['maven_key']);
    $p->setSeverity($d['severity']);
    $p->setNiveau($d['niveau']);
    $p->setStatus($d['status']);
    $p->setFrontend($d['frontend']);
    $p->setBackend($d['backend']);
    $p->setAutre($d['autre']);
    $p->setFile($d['file']);
    $p->setLine($d['line']);
    $p->setMessage($d['message']);
    $p->setRule($d['rule']);
    $p->setKey($d['key']);
    $p->setDateEnregistrement($d['date_enregistrement']);

    $this->assertIsInt($p->getId());
    $this->assertIsString($p->getMavenKey());
    $this->assertIsString($p->getSeverity());
    $this->assertIsInt($p->getNiveau());
    $this->assertIsString($p->getStatus());
    $this->assertIsInt($p->getFrontend());
    $this->assertIsInt($p->getBackend());
    $this->assertIsInt($p->getAutre());
    $this->assertIsString($p->getFile());
    $this->assertIsInt($p->getLine());
    $this->assertIsString($p->getMessage());
    $this->assertIsString($p->getRule());
    $this->assertIsString($p->getKey());
    $this->assertIsObject($p->getDateEnregistrement());
  }

  /**
   * [Description for testHotspotDetails]
   * test des getter/setter
   *
   * @return void
   *
   * Created at: 14/02/2023, 16:29:39 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testHotspotDetails(): void
  {
    /** On récupère le jeu de données */
    $dd=static::dataset();
    $d=$dd[0];

    $p = new HotspotDetails();
    $p->setId($d['id']);
    $p->setMavenKey($d['maven_key']);
    $p->setSeverity($d['severity']);
    $p->setNiveau($d['niveau']);
    $p->setStatus($d['status']);
    $p->setFrontend($d['frontend']);
    $p->setBackend($d['backend']);
    $p->setAutre($d['autre']);
    $p->setFile($d['file']);
    $p->setRule($d['rule']);
    $p->setKey($d['key']);
    $p->setDateEnregistrement($d['date_enregistrement']);

    $this->assertEquals($d['id'], $p->getId());
    $this->assertSame($d['maven_key'],$p->getMavenKey());
    $this->assertSame($d['severity'],$p->getSeverity());
    $this->assertEquals($d['niveau'],$p->getNiveau());
    $this->assertEquals($d['frontend'],$p->getFrontend());
    $this->assertEquals($d['backend'],$p->getBackend());
    $this->assertEquals($d['autre'],$p->getAutre());
    $this->assertSame($d['file'],$p->getFile());
    $this->assertSame($d['rule'],$p->getRule());
    $this->assertSame($d['key'],$p->getKey());
    $this->assertSame($d['date_enregistrement'],$p->getDateEnregistrement());

  }

}
