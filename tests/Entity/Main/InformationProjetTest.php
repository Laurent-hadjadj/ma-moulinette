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
use App\Entity\Main\InformationProjet;
use App\Repository\Main\InformationProjetRepository;
use DateTime;

class InformationProjetTest extends TestCase
{
  /**
   * [Description for dataset]
   * Jeu de données
   *
   * @return array
   *
   * Created at: 14/02/2023, 15:50:05 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function dataset(): array
  {
    return [
      ['id'=>1, 'maven_key'=> 'fr.ma-petite-entreprise:ma-moulinette',
      'analyse_key'=> 'AYVyxZcQo0TJpgSeq-ph',
      'date'=> new DateTime(),
      'project_version'=> '1.6.0-RELEASE',
      'type'=>'RELEASE',
      'date_enregistrement'=> new DateTime()]
    ];
  }

  /**
   * [Description for testInformationProjetFindAll]
   * On récupère l'ensemble des données, on fait un getMavenKey().
   * @return void
   *
   * Created at: 14/02/2023, 15:52:43 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testInformationProjetFindAll(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(InformationProjetRepository::class);
    $mockRepo->method('findAll')->willReturn($d);
    $u=$mockRepo->findAll();
    $this->assertEquals('AYVyxZcQo0TJpgSeq-ph', $u[0]['analyse_key']);
  }

  /**
   * [Description for testInformationProjetCountAttribut]
   * On vérifie le nombre d'attribut
   * @return void
   *
   * Created at: 14/02/2023, 15:53:07 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testInformationProjetCountAttribut(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(InformationProjet::class);
    /**
     * On compte le nombre attribut de la classe
     * On enlève :
     * __phpunit_originalObject, __phpunit_returnValueGeneration,__phpunit_invocationMocker
     */
    $nb=count((array)$mockRepo)-3;
    $this->assertEquals(count($d[0]), $nb);
  }

  /**
   * [Description for testInformationProjetCount]
   * On compte le nombre d'enregistrement dans la collection.
   *
   * @return void
   *
   * Created at: 14/02/2023, 15:53:53 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testInformationProjetCount(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(InformationProjetRepository::class);
    $mockRepo->method('findAll')->willReturn($d);
    $u=$mockRepo->findAll();
    $this->assertCount(1, $u);
  }

  /**
   * [Description for testInformationProjetType]
   * On test le type
   * @return void
   *
   * Created at: 14/02/2023, 15:54:08 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testInformationProjetType(): void
  {
    /** On récupère le jeu de données */
    $dd=static::dataset();
    $d=$dd[0];

    $p = new InformationProjet();
    $p->setId($d['id']);
    $p->setMavenKey($d['maven_key']);
    $p->setAnalyseKey($d['analyse_key']);
    $p->setDate($d['date']);
    $p->setProjectVersion($d['project_version']);
    $p->setType($d['type']);
    $p->setDateEnregistrement($d['date_enregistrement']);

    $this->assertIsInt($p->getId());
    $this->assertIsString($p->getMavenKey());
    $this->assertIsString($p->getAnalyseKey());
    $this->assertIsObject($p->getDate());
    $this->assertIsString($p->getProjectVersion());
    $this->assertIsString($p->getType());
    $this->assertIsObject($p->getDateEnregistrement());
  }

  /**
   * [Description for testInformationProjet]
   * test des getter/setter
   *
   * @return void
   *
   * Created at: 14/02/2023, 16:01:57 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testInformationProjet(): void
  {
    /** On récupère le jeu de données */
    $dd=static::dataset();
    $d=$dd[0];

    $p = new InformationProjet();
    $p->setId($d['id']);
    $p->setMavenKey($d['maven_key']);
    $p->setAnalyseKey($d['analyse_key']);
    $p->setDate($d['date']);
    $p->setProjectVersion($d['project_version']);
    $p->setType($d['type']);
    $p->setDateEnregistrement($d['date_enregistrement']);

    $this->assertEquals($d['id'], $p->getId());
    $this->assertSame($d['maven_key'],$p->getMavenKey());
    $this->assertSame($d['analyse_key'],$p->getAnalyseKey());
    $this->assertSame($d['date'],$p->getDate());
    $this->assertSame($d['project_version'],$p->getProjectVersion());
    $this->assertSame($d['type'],$p->getType());
    $this->assertSame($d['date_enregistrement'],$p->getDateEnregistrement());

  }

}
