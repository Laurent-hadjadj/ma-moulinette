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
use App\Entity\Main\Hotspots;
use App\Repository\Main\HotspotsRepository;
use DateTime;

class HotspotsTest extends TestCase
{
  /**
   * [Description for dataset]
   * Jeu de données
   *
   * @return array
   *
   * Created at: 14/02/2023, 16:14:55 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function dataset(): array
  {
    return [
      ['id'=>1, 'maven_key'=> 'fr.ma-petite-entreprise:ma-moulinette',
      'key'=> 'AYVymv5uo0TJpgSeq1g8',
      'probability'=> 'MEDIUM',
      'status'=> 'TO_REVIEW',
      'niveau'=>'2',
      'date_enregistrement'=> new DateTime()]
    ];
  }

  /**
   * [Description for testHotspotsFindAll]
   * On récupère l'ensemble des données, on fait un getMavenKey().
   * @return void
   *
   * Created at: 14/02/2023, 16:16:58 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testHotspotsFindAll(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(HotspotsRepository::class);
    $mockRepo->method('findAll')->willReturn($d);
    $u=$mockRepo->findAll();
    $this->assertEquals('MEDIUM', $u[0]['probability']);
  }

  /**
   * [Description for testHotspotsCountAttribut]
   * On vérifie le nombre d'attribut
   * @return void
   *
   * Created at: 14/02/2023, 16:17:38 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testHotspotsCountAttribut(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(Hotspots::class);
    /**
     * On compte le nombre attribut de la classe
     * On enlève :
     * __phpunit_originalObject, __phpunit_returnValueGeneration,__phpunit_invocationMocker
     */
    $nb=count((array)$mockRepo)-3;
    $this->assertEquals(count($d[0]), $nb);
  }

  /**
   * [Description for testHotspotsCount]
   * On compte le nombre d'enregistrement dans la collection.
   *
   * @return void
   *
   * Created at: 14/02/2023, 16:18:14 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testHotspotsCount(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(HotspotsRepository::class);
    $mockRepo->method('findAll')->willReturn($d);
    $u=$mockRepo->findAll();
    $this->assertCount(1, $u);
  }

  /**
   * [Description for testHotspotsType]
   * On test le type
   * @return void
   *
   * Created at: 14/02/2023, 16:18:45 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testHotspotsType(): void
  {
    /** On récupère le jeu de données */
    $dd=static::dataset();
    $d=$dd[0];

    $p = new Hotspots();
    $p->setId($d['id']);
    $p->setMavenKey($d['maven_key']);
    $p->setKey($d['key']);
    $p->setProbability($d['probability']);
    $p->setStatus($d['status']);
    $p->setNiveau($d['niveau']);
    $p->setDateEnregistrement($d['date_enregistrement']);

    $this->assertIsInt($p->getId());
    $this->assertIsString($p->getMavenKey());
    $this->assertIsString($p->getKey());
    $this->assertIsString($p->getProbability());
    $this->assertIsString($p->getStatus());
    $this->assertIsInt($p->getNiveau());
    $this->assertIsObject($p->getDateEnregistrement());
  }

  /**
   * [Description for testHotspots]
   * test des getter/setter
   *
   * @return void
   *
   * Created at: 14/02/2023, 16:21:22 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testHotspots(): void
  {
    /** On récupère le jeu de données */
    $dd=static::dataset();
    $d=$dd[0];

    $p = new Hotspots();
    $p->setId($d['id']);
    $p->setMavenKey($d['maven_key']);
    $p->setKey($d['key']);
    $p->setProbability($d['probability']);
    $p->setStatus($d['status']);
    $p->setNiveau($d['niveau']);
    $p->setDateEnregistrement($d['date_enregistrement']);

    $this->assertEquals($d['id'], $p->getId());
    $this->assertSame($d['maven_key'],$p->getMavenKey());
    $this->assertSame($d['key'],$p->getKey());
    $this->assertSame($d['probability'],$p->getProbability());
    $this->assertSame($d['status'],$p->getStatus());
    $this->assertEquals($d['niveau'],$p->getNiveau());
    $this->assertSame($d['date_enregistrement'],$p->getDateEnregistrement());

  }

}
