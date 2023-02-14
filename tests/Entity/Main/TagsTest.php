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
use App\Entity\Main\Tags;
use App\Repository\Main\TagsRepository;
use DateTime;

class TagsTest extends TestCase
{

  /**
   * [Description for dataset]
   * Jeu de données
   *
   * @return array
   *
   * Created at: 14/02/2023, 09:15:18 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function dataset(): array
  {
    return [
      ['id' =>1, 'maven_key'=> 'fr.ma-petite-entreprise:ma-moulinette', 'name'=>'Ma-Moulinette',
      'tags'=>['ma-petite-entreprise','php', 'sqligt'],
      'visibility'=>'private', 'dateEnregistrement'=> new DateTime()],
      ['id' =>2, 'maven_key'=> 'fr.ma-petite-entreprise:ma-moulinette-docker', 'name'=>'Ma-Moulinette',
      'tags'=>['ma-petite-entreprise','php', 'postgresql'],
      'visibility'=>'public', 'dateEnregistrement'=> new DateTime()]
    ];
  }

  /**
   * [Description for testTagsFindAll]
   * On récupère l'ensemble des données, on fait un getMavenKey().
   * @return void
   *
   * Created at: 14/02/2023, 07:55:49 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testTagsFindAll(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(TagsRepository::class);
    $mockRepo->method('findAll')->willReturn($d);
    $u=$mockRepo->findAll();
    $this->assertEquals('fr.ma-petite-entreprise:ma-moulinette', $u[0]['maven_key']);
  }

  /**
   * [Description for testTagsCount]
   * On compte le nombre d'enregistrement dans la collection.
   *
   * @return void
   *
   * Created at: 14/02/2023, 07:56:11 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testTagsCount(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(TagsRepository::class);
    $mockRepo->method('findAll')->willReturn($d);
    $u=$mockRepo->findAll();
    $this->assertCount(2, $u);
  }

  public function testTagsType(): void
  {
    /** On récupère le jeu de données */
    $dd=static::dataset();
    $d=$dd[0];

    $p = new Tags();
    $p->setId($d['id']);
    $p->setMavenKey($d['maven_key']);
    $p->setName($d['name']);
    $p->setTags($d['tags']);
    $p->setVisibility($d['visibility']);
    $p->setDateEnregistrement($d['dateEnregistrement']);

    $this->assertIsInt($p->getId());
    $this->assertIsString($p->getMavenKey());
    $this->assertIsString($p->getName());
    $this->assertIsArray($p->getTags());
    $this->assertIsString($p->getVisibility());
    $this->assertIsObject($p->getDateEnregistrement());
  }

  /**
   * [Description for testTags]
   * test des getter/setter
   *
   * @return void
   *
   * Created at: 14/02/2023, 07:56:27 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testTags(): void
  {
    /** On récupère le jeu de données */
    $dd=static::dataset();
    $d=$dd[0];

    $p = new Tags();
    $p->setId($d['id']);
    $p->setMavenKey($d['maven_key']);
    $p->setName($d['name']);
    $p->setTags($d['tags']);
    $p->setVisibility($d['visibility']);
    $p->setDateEnregistrement($d['dateEnregistrement']);

    $this->assertEquals(1, $p->getId());
    $this->assertSame($d['maven_key'], $p->getMavenKey());
    $this->assertSame($d['name'], $p->getName());
    $this->assertSame($d['tags'], $p->getTags());
    $this->assertSame($d['visibility'], $p->getVisibility());
    $this->assertSame($d['dateEnregistrement'], $p->getDateEnregistrement());
  }

}
