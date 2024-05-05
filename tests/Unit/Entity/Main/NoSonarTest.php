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
use App\Entity\Main\NoSonar;
use App\Repository\Main\NoSonarRepository;
use DateTime;

/**
 * [Description NoSonarTest]
 */
class NoSonarTest extends TestCase
{
  /**
   * [Description for dataset]
   * Jeu de données
   *
   * @return array
   *
   * Created at: 14/02/2023, 12:26:32 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function dataset(): array
  {
    return [
      ['id'=>1, 'maven_key'=> 'fr.ma-petite-entreprise:ma-moulinette',
      'rule'=>'java:S1309',
      'component'=> 'fr.ma-petite-entreprise:mo-moulinette:
      ma-moulinette-service/src/main/java/fr/ma-petite-entreprise/ma-moulinette/service/ClamAvService.java',
      'line'=> 123,
      'date_enregistrement'=> new DateTime()],
    ];
  }

  /**
   * [Description for testNoSonarFindAll]
   * On récupère l'ensemble des données, on fait un getMavenKey().
   * @return void
   *
   * Created at: 14/02/2023, 13:20:56 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testNoSonarFindAll(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(NoSonarRepository::class);
    $mockRepo->method('findAll')->willReturn($d);
    $u=$mockRepo->findAll();
    $this->assertEquals('java:S1309', $u[0]['rule']);
  }

/**
   * [Description for testNoSonarType]
   * On test le type
   * @return void
   *
   *  Created at: 14/02/2023, 11:03:09 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testNoSonarType(): void
  {
    /** On récupère le jeu de données */
    $dd=static::dataset();
    $d=$dd[0];

    $p = new NoSonar();
    $p->setId($d['id']);
    $p->setMavenKey($d['maven_key']);
    $p->setRule($d['rule']);
    $p->setComponent($d['component']);
    $p->setLine($d['line']);
    $p->setDateEnregistrement($d['date_enregistrement']);

    $this->assertIsInt($p->getId());
    $this->assertIsString($p->getMavenKey());
    $this->assertIsString($p->getRule());
    $this->assertIsString($p->getComponent());
    $this->assertIsInt($p->getLine());
    $this->assertIsObject($p->getDateEnregistrement());
  }

  /**
   * [Description for testNoSonar]
   * test des getter/setter
   *
   * @return void
   *
   * Created at: 14/02/2023, 13:12:49 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testNoSonar(): void
  {
    /** On récupère le jeu de données */
    $dd=static::dataset();
    $d=$dd[0];

    $p = new NoSonar();
    $p->setId($d['id']);
    $p->setMavenKey($d['maven_key']);
    $p->setRule($d['rule']);
    $p->setComponent($d['component']);
    $p->setLine($d['line']);
    $p->setDateEnregistrement($d['date_enregistrement']);

    $this->assertEquals($d['id'],$p->getId());
    $this->assertSame($d['maven_key'],$p->getMavenKey());
    $this->assertSame($d['rule'],$p->getRule());
    $this->assertSame($d['component'],$p->getComponent());
    $this->assertSame($d['line'],$p->getLine());
    $this->assertSame($d['date_enregistrement'],$p->getDateEnregistrement());
  }

}
