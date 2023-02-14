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
use App\Entity\Main\Notes;
use App\Repository\Main\NotesRepository;
use DateTime;

class NotesTest extends TestCase
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
      ['maven_key'=> 'fr.ma-petite-entreprise:ma-moulinette',
      'type'=>'reliability',
      'date'=> new DateTime(),
      'value'=> 1,
      'date_enregistrement'=> new DateTime()],
    ];
  }

  /**
   * [Description for testNotesFindAll]
   * On récupère l'ensemble des données, on fait un getMavenKey().
   * @return void
   *
   * Created at: 14/02/2023, 10:22:09 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testNotesFindAll(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(NotesRepository::class);
    $mockRepo->method('findAll')->willReturn($d);
    $u=$mockRepo->findAll();
    $this->assertEquals('fr.ma-petite-entreprise:ma-moulinette', $u[0]['maven_key']);
  }

  /**
   * [Description for testNotesCount]
   * On compte le nombre d'enregistrement dans la collection.
   *
   * @return void
   *
   * Created at: 14/02/2023, 10:23:36 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testNotesCount(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(NotesRepository::class);
    $mockRepo->method('findAll')->willReturn($d);
    $u=$mockRepo->findAll();
    $this->assertCount(1, $u);
  }

  /**
   * [Description for testNotesType]
   * On test le type
   * @return void
   *
   *  Created at: 14/02/2023, 11:03:09 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testNotesType(): void
  {
    /** On récupère le jeu de données */
    $dd=static::dataset();
    $d=$dd[0];

    $p = new Notes();
    $p->setMavenKey($d['maven_key']);
    $p->setType($d['type']);
    $p->setDate($d['date']);
    $p->setValue($d['value']);
    $p->setDateEnregistrement($d['date_enregistrement']);

    $this->assertIsString($p->getMavenKey());
    $this->assertIsString($p->getType());
    $this->assertIsObject($p->getDate());
    $this->assertIsInt($p->getValue());
    $this->assertIsObject($p->getDateEnregistrement());
  }


  /**
   * [Description for testNotes]
   *  Pas d'attribut id
   * @return void
   *
   * Created at: 14/02/2023, 13:14:10 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testNotesNotHasKeyId(): void
  {
    /** On récupère le jeu de données */
    $dd=static::dataset();
    $d=$dd[0];
    $this->assertArrayNotHasKey('id', $d);
  }

  /**
   * [Description for testNotes]
   * test des getter/setter
   *
   * @return void
   *
   * Created at: 14/02/2023, 13:12:49 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testNotes(): void
  {
    /** On récupère le jeu de données */
    $dd=static::dataset();
    $d=$dd[0];

    $p = new Notes();
    $p->setMavenKey($d['maven_key']);
    $p->setType($d['type']);
    $p->setDate($d['date']);
    $p->setValue($d['value']);
    $p->setDateEnregistrement($d['date_enregistrement']);

    $this->assertEquals($d['maven_key'],$p->getMavenKey());
    $this->assertSame($d['type'],$p->getType());
    $this->assertSame($d['date'],$p->getDate());
    $this->assertSame($d['value'],$p->getValue());
    $this->assertSame($d['date_enregistrement'],$p->getDateEnregistrement());
  }

}
