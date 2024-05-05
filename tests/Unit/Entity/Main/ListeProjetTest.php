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
use App\Entity\Main\ListeProjet;
use App\Repository\Main\ListeProjetRepository;
use DateTime;

class ListeProjetTest extends TestCase
{
  /**
   * [Description for dataset]
   * Jeu de données
   *
   * @return array
   *
   * Created at: 14/02/2023, 15:40:43 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function dataset(): array
  {
    return [
      ['id'=>1, 'maven_key'=> 'fr.ma-petite-entreprise:ma-moulinette',
      'name'=> 'Ma-Moulinette',
      'date_enregistrement'=> new DateTime()]
    ];
  }

  /**
   * [Description for testListeProjetFindAll]
   * On récupère l'ensemble des données, on fait un getMavenKey().
   * @return void
   *
   * Created at: 14/02/2023, 15:43:43 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testListeProjetFindAll(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(ListeProjetRepository::class);
    $mockRepo->method('findAll')->willReturn($d);
    $u=$mockRepo->findAll();
    $this->assertEquals('Ma-Moulinette', $u[0]['name']);
  }

  /**
   * [Description for testListeProjetCountAttribut]
   * On vérifie le nombre d'attribut
   * @return void
   *
   * Created at: 14/02/2023, 15:43:57 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testListeProjetCountAttribut(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(ListeProjet::class);
    /**
     * On compte le nombre attribut de la classe
     * On enlève :
     * __phpunit_originalObject, __phpunit_returnValueGeneration,__phpunit_invocationMocker
     */
    $nb=count((array)$mockRepo)-3;
    $this->assertEquals(count($d[0]), $nb);
  }

  /**
   * [Description for testListeProjetCount]
   * On compte le nombre d'enregistrement dans la collection.
   *
   * @return void
   *
   * Created at: 14/02/2023, 15:44:11 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testListeProjetCount(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(ListeProjetRepository::class);
    $mockRepo->method('findAll')->willReturn($d);
    $u=$mockRepo->findAll();
    $this->assertCount(1, $u);
  }

  /**
   * [Description for testListeProjetType]
   * On test le type
   * @return void
   *
   * Created at: 14/02/2023, 15:44:34 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testListeProjetType(): void
  {
    /** On récupère le jeu de données */
    $dd=static::dataset();
    $d=$dd[0];

    $p = new ListeProjet();
    $p->setId($d['id']);
    $p->setMavenKey($d['maven_key']);
    $p->setName($d['name']);
    $p->setDateEnregistrement($d['date_enregistrement']);

    $this->assertIsInt($p->getId());
    $this->assertIsString($p->getMavenKey());
    $this->assertIsString($p->getName());
    $this->assertIsObject($p->getDateEnregistrement());
  }

  /**
   * [Description for testListeProjet]
   * test des getter/setter
   *
   * @return void
   *
   * Created at: 14/02/2023, 15:46:44 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testListeProjet(): void
  {
    /** On récupère le jeu de données */
    $dd=static::dataset();
    $d=$dd[0];

    $p = new ListeProjet();
    $p->setId($d['id']);
    $p->setMavenKey($d['maven_key']);
    $p->setName($d['name']);
    $p->setDateEnregistrement($d['date_enregistrement']);

    $this->assertEquals($d['id'], $p->getId());
    $this->assertSame($d['maven_key'],$p->getMavenKey());
    $this->assertSame($d['name'],$p->getName());
    $this->assertSame($d['date_enregistrement'],$p->getDateEnregistrement());

  }

}
