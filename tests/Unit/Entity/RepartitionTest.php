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

namespace App\Tests\Entity;

use DateTime;
use App\Entity\Repartition;
use App\Repository\RepartitionRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RepartitionTest extends TestCase
{
  /**
   * [Description for dataset]
   * Jeu de données
   *
   * @return array
   *
   * Created at: 15/02/2023, 15:50:21 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function dataset(): array
  {
    return [
      ['id'=>1,
      'maven_key'=> 'fr.ma-petite-entreprise:ma-moulinette',
      'name'=>'ma-moulinette',
      'component'=>'fr.ma-petite-entreprise:ma-moulinette:ma-moulinette-metier/
      ma-moulinette-metier-service/src/main/java/fr/ma-petite-entreprise/mamoulinettemetier/
      service/sonar/dvd/impl/DvdServiceImpl.java',
      'type'=>'BUG',
      'severity'=>'MAJOR',
      'setup'=> '1665657415440',
      'date_enregistrement'=> new DateTime()]
    ];
  }

  /**
   * [Description for testRepartitionFindAll]
   * On récupère l'ensemble des données, on fait un getMavenKey().
   * @return void
   *
   * Created at: 15/02/2023, 15:59:13 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testRepartitionFindAll(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(RepartitionRepository::class);
    $mockRepo->method('findAll')->willReturn($d);
    $u=$mockRepo->findAll();
    $this->assertEquals('ma-moulinette', $u[0]['name']);
  }

  /**
   * [Description for testRepartitionCountAttribut]
   * On vérifie le nombre d'attribut
   * @return void
   *
   * Created at: 15/02/2023, 16:01:43 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testRepartitionCountAttribut(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(Repartition::class);
    /**
     * On compte le nombre attribut de la classe
     * On enlève :
     * __phpunit_originalObject, __phpunit_returnValueGeneration,__phpunit_invocationMocker
     */
    $nb=count((array)$mockRepo)-3;
    $this->assertEquals(count($d[0]), $nb);
  }

  /**
   * [Description for testRepartitionCount]
   * On compte le nombre d'enregistrement dans la collection.
   *
   * @return void
   *
   * Created at: 15/02/2023, 16:01:58 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testRepartitionCount(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(RepartitionRepository::class);
    $mockRepo->method('findAll')->willReturn($d);
    $u=$mockRepo->findAll();
    $this->assertCount(1, $u);
  }

  /**
   * [Description for testRepartitionType]
   * On test le type
   * @return void
   *
   * Created at: 15/02/2023, 16:03:11 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testRepartitionType(): void
  {
    /** On récupère le jeu de données */
    $dd=static::dataset();
    $d=$dd[0];

    $p = new Repartition();
    $p->setId($d['id']);
    $p->setMavenKey($d['maven_key']);
    $p->setName($d['name']);
    $p->setComponent($d['component']);
    $p->setType($d['type']);
    $p->setSeverity($d['severity']);
    $p->setSetup($d['setup']);
    $p->setDateEnregistrement($d['date_enregistrement']);

    $this->assertIsInt($p->getId());
    $this->assertIsString($p->getName());
    $this->assertIsString($p->getMavenKey());
    $this->assertIsString($p->getComponent());
    $this->assertIsString($p->getType());
    $this->assertIsString($p->getSeverity());
    $this->assertIsNumeric($p->getSetup());
    $this->assertIsObject($p->getDateEnregistrement());
  }

  /**
   * [Description for testRepartition]
   * test des getter/setter
   *
   * @return void
   *
   * Created at: 15/02/2023, 16:12:02 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testRepartition(): void
  {
    /** On récupère le jeu de données */
    $dd=static::dataset();
    $d=$dd[0];

    $p = new Repartition();
    $p->setId($d['id']);
    $p->setMavenKey($d['maven_key']);
    $p->setName($d['name']);
    $p->setComponent($d['component']);
    $p->setType($d['type']);
    $p->setSeverity($d['severity']);
    $p->setSetup($d['setup']);
    $p->setDateEnregistrement($d['date_enregistrement']);

    $this->assertEquals($d['id'], $p->getId());
    $this->assertSame($d['maven_key'],$p->getMavenKey());
    $this->assertSame($d['name'],$p->getName());
    $this->assertSame($d['component'],$p->getComponent());
    $this->assertSame($d['type'],$p->getType());
    $this->assertSame($d['severity'],$p->getSeverity());
    $this->assertEquals($d['setup'],$p->getSetup());
    $this->assertSame($d['date_enregistrement'],$p->getDateEnregistrement());

  }

}
