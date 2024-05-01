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
use App\Entity\Main\Mesures;
use App\Repository\Main\MesuresRepository;
use DateTime;

class MesuresTest extends TestCase
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
      'project_name'=>'Ma-Moulinette',
      'lines'=> '22015', 'ncloc'=>'10043', 'coverage'=>10.3, 'duplication_density'=>5.1,
      'tests'=>123, 'issues'=>200,
      'date_enregistrement'=> new DateTime()],
    ];
  }

  /**
   * [Description for testMesuresFindAll]
   * On récupère l'ensemble des données, on fait un getMavenKey().
   * @return void
   *
   * Created at: 14/02/2023, 13:20:56 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testMesuresFindAll(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(MesuresRepository::class);
    $mockRepo->method('findAll')->willReturn($d);
    $u=$mockRepo->findAll();
    $this->assertEquals('Ma-Moulinette', $u[0]['project_name']);
  }

  /**
   * [Description for testMesuresCount]
   * On compte le nombre d'enregistrement dans la collection.
   *
   * @return void
   *
   * Created at: 14/02/2023, 13:21:20 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testMesuresCount(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(MesuresRepository::class);
    $mockRepo->method('findAll')->willReturn($d);
    $u=$mockRepo->findAll();
    $this->assertCount(1, $u);
  }

  /**
   * [Description for testMesuresType]
   * On test le type
   * @return void
   *
   * Created at: 14/02/2023, 14:19:39 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testMesuresType(): void
  {
    /** On récupère le jeu de données */
    $dd=static::dataset();
    $d=$dd[0];

    $p = new Mesures();
    $p->setId($d['id']);
    $p->setMavenKey($d['maven_key']);
    $p->setProjectName($d['project_name']);
    $p->setLines($d['lines']);
    $p->setNcloc($d['ncloc']);
    $p->setCoverage($d['coverage']);
    $p->setDuplicationDensity($d['duplication_density']);
    $p->setTests($d['tests']);
    $p->setIssues($d['issues']);
    $p->setDateEnregistrement($d['date_enregistrement']);

    $this->assertIsInt($p->getId());
    $this->assertIsString($p->getMavenKey());
    $this->assertIsInt($p->getLines());
    $this->assertIsInt($p->getNcloc());
    $this->assertIsFloat($p->getCoverage());
    $this->assertIsFloat($p->getDuplicationDensity());
    $this->assertIsInt($p->getTests());
    $this->assertIsInt($p->getIssues());
    $this->assertIsObject($p->getDateEnregistrement());
  }

  /**
   * [Description for testMesures]
   * test des getter/setter
   *
   * @return void
   *
   * Created at: 14/02/2023, 14:20:03 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testMesures(): void
  {
    /** On récupère le jeu de données */
    $dd=static::dataset();
    $d=$dd[0];

    $p = new Mesures();
    $p->setId($d['id']);
    $p->setMavenKey($d['maven_key']);
    $p->setProjectName($d['project_name']);
    $p->setLines($d['lines']);
    $p->setNcloc($d['ncloc']);
    $p->setCoverage($d['coverage']);
    $p->setDuplicationDensity($d['duplication_density']);
    $p->setTests($d['tests']);
    $p->setIssues($d['issues']);
    $p->setDateEnregistrement($d['date_enregistrement']);

    $this->assertEquals($d['id'], $p->getId());
    $this->assertSame($d['maven_key'],$p->getMavenKey());
    $this->assertEquals($d['lines'],$p->getLines());
    $this->assertEquals($d['ncloc'],$p->getNcloc());
    $this->assertSame($d['coverage'],$p->getCoverage());
    $this->assertSame($d['duplication_density'],$p->getDuplicationDensity());
    $this->assertEquals($d['tests'],$p->getTests());
    $this->assertEquals($d['issues'],$p->getIssues());
    $this->assertSame($d['date_enregistrement'],$p->getDateEnregistrement());

  }

}
