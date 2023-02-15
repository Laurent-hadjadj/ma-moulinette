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

use PHPUnit\Framework\TestCase;
use App\Entity\Main\HotspotOwasp;
use App\Repository\Main\HotspotOwaspRepository;
use DateTime;

class HotspotOwaspTest extends TestCase
{
  /**
   * [Description for dataset]
   * Jeu de données
   *
   * @return array
   *
   * Created at: 14/02/2023, 16:25:40 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function dataset(): array
  {
    return [
      ['id'=>1, 'maven_key'=> 'fr.ma-petite-entreprise:ma-moulinette',
      'menace'=> 'a1',
      'probability'=> 'NC',
      'status'=> 'NC',
      'niveau'=>'0',
      'date_enregistrement'=> new DateTime()]
    ];
  }

  /**
   * [Description for testHotspotOwaspFindAll]
   * On récupère l'ensemble des données, on fait un getMavenKey().
   * @return void
   *
   * Created at: 14/02/2023, 16:27:26 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testHotspotOwaspFindAll(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(HotspotOwaspRepository::class);
    $mockRepo->method('findAll')->willReturn($d);
    $u=$mockRepo->findAll();
    $this->assertEquals('a1', $u[0]['menace']);
  }

  /**
   * [Description for testHotspotOwaspCountAttribut]
   * On vérifie le nombre d'attribut
   * @return void
   *
   * Created at: 14/02/2023, 16:28:02 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testHotspotOwaspCountAttribut(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(HotspotOwasp::class);
    /**
     * On compte le nombre attribut de la classe
     * On enlève :
     * __phpunit_originalObject, __phpunit_returnValueGeneration,__phpunit_invocationMocker
     */
    $nb=count((array)$mockRepo)-3;
    $this->assertEquals(count($d[0]), $nb);
  }

  /**
   * [Description for testHotspotOwaspCount]
   * On compte le nombre d'enregistrement dans la collection.
   *
   * @return void
   *
   * Created at: 14/02/2023, 16:28:19 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testHotspotOwaspCount(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(HotspotOwaspRepository::class);
    $mockRepo->method('findAll')->willReturn($d);
    $u=$mockRepo->findAll();
    $this->assertCount(1, $u);
  }

  /**
   * [Description for testHotspotOwaspType]
   * On test le type
   * @return void
   *
   * Created at: 14/02/2023, 16:28:47 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testHotspotOwaspType(): void
  {
    /** On récupère le jeu de données */
    $dd=static::dataset();
    $d=$dd[0];

    $p = new HotspotOwasp();
    $p->setId($d['id']);
    $p->setMavenKey($d['maven_key']);
    $p->setMenace($d['menace']);
    $p->setProbability($d['probability']);
    $p->setStatus($d['status']);
    $p->setNiveau($d['niveau']);
    $p->setDateEnregistrement($d['date_enregistrement']);

    $this->assertIsInt($p->getId());
    $this->assertIsString($p->getMavenKey());
    $this->assertIsString($p->getMenace());
    $this->assertIsString($p->getProbability());
    $this->assertIsString($p->getStatus());
    $this->assertIsInt($p->getNiveau());
    $this->assertIsObject($p->getDateEnregistrement());
  }

  /**
   * [Description for testHotspotOwasp]
   * test des getter/setter
   *
   * @return void
   *
   * Created at: 14/02/2023, 16:29:39 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testHotspotOwasp(): void
  {
    /** On récupère le jeu de données */
    $dd=static::dataset();
    $d=$dd[0];

    $p = new HotspotOwasp();
    $p->setId($d['id']);
    $p->setMavenKey($d['maven_key']);
    $p->setMenace($d['menace']);
    $p->setProbability($d['probability']);
    $p->setStatus($d['status']);
    $p->setNiveau($d['niveau']);
    $p->setDateEnregistrement($d['date_enregistrement']);

    $this->assertEquals($d['id'], $p->getId());
    $this->assertSame($d['maven_key'],$p->getMavenKey());
    $this->assertSame($d['menace'],$p->getMenace());
    $this->assertSame($d['probability'],$p->getProbability());
    $this->assertSame($d['status'],$p->getStatus());
    $this->assertEquals($d['niveau'],$p->getNiveau());
    $this->assertSame($d['date_enregistrement'],$p->getDateEnregistrement());

  }

}
