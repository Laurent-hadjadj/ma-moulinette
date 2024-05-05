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

use DateTime;
use PHPUnit\Framework\TestCase;
use App\Entity\Main\Portefeuille;
use App\Repository\Main\PortefeuilleRepository;

class PortefeuilleTest extends TestCase
{
  /**
   * [Description for dataset]
   * Jeu de données
   *
   * @return array
   *
   * Created at: 14/02/2023, 10:52:26 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function dataset(): array
  {
    return [
      ['id' =>1, 'titre'=> 'TEST MULTI PROJETS',
      'equipe'=>'MA PETITE ENTREPRISE',
      'liste'=>["fr.ma-petite-entreprise:ma-moulinette"],
      'date_modification'=> new DateTime(),
      'date_enregistrement'=> new DateTime()],
    ];
  }

  /**
   * [Description for testPortefeuilleFindAll]
   * On récupère l'ensemble des données, on fait un getMavenKey().
   * @return void
   *
   * Created at: 14/02/2023, 10:22:09 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testPortefeuilleFindAll(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(PortefeuilleRepository::class);
    $mockRepo->method('findAll')->willReturn($d);
    $u=$mockRepo->findAll();
    $this->assertEquals('MA PETITE ENTREPRISE', $u[0]['equipe']);
  }

  /**
   * [Description for testPortefeuilleCount]
   * On compte le nombre d'enregistrement dans la collection.
   *
   * @return void
   *
   * Created at: 14/02/2023, 10:23:36 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testPortefeuilleCount(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(PortefeuilleRepository::class);
    $mockRepo->method('findAll')->willReturn($d);
    $u=$mockRepo->findAll();
    $this->assertCount(1, $u);
  }

  /**
   * [Description for testPortefeuilleType]
   * On test le type
   * @return void
   *
   *  Created at: 14/02/2023, 11:03:09 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testPortefeuilleType(): void
  {
    /** On récupère le jeu de données */
    $dd=static::dataset();
    $d=$dd[0];

    $p = new Portefeuille();
    $p->setId($d['id']);
    $p->setTitre($d['titre']);
    $p->setEquipe($d['equipe']);
    $p->setListe($d['liste']);
    $p->setDateModification($d['date_modification']);
    $p->setDateEnregistrement($d['date_enregistrement']);

    $this->assertIsInt($p->getId());
    $this->assertIsString($p->getTitre());
    $this->assertIsString($p->getEquipe());
    $this->assertIsArray($p->getListe());
    $this->assertIsObject($p->getDateModification());
    $this->assertIsObject($p->getDateEnregistrement());
  }

  /**
   * [Description for testPortefeuilleTitreUnique]
   * On test si le titre dans l'entité est le même que dans le set !
   * @return void
   *
   * Created at: 14/02/2023, 12:11:54 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testPortefeuilleTitreUnique(): void
  {
    /** On récupère le jeu de données */
    $dd=static::dataset();
    $d=$dd[0];

    $p = new Portefeuille();
    $p->setTitre('TEST MULTI PROJETS');
    $this->assertEquals($d['titre'], $p->getTitre());
  }

  /**
   * [Description for testPortefeuille]
   * test des getter/setter
   *
   * @return void
   *
   * Created at: 14/02/2023, 11:09:46 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testPortefeuille(): void
  {
    /** On récupère le jeu de données */
    $dd=static::dataset();
    $d=$dd[0];

    $p = new Portefeuille();
    $p->setId($d['id']);
    $p->setTitre($d['titre']);
    $p->setEquipe($d['equipe']);
    $p->setListe($d['liste']);
    $p->setDateModification($d['date_modification']);
    $p->setDateEnregistrement($d['date_enregistrement']);

    $this->assertEquals(1, $p->getId());
    $this->assertSame($d['titre'],$p->getTitre());
    $this->assertSame($d['equipe'],$p->getEquipe());
    $this->assertSame($d['liste'],$p->getListe());
    $this->assertSame($d['date_modification'],$p->getDateModification());
    $this->assertSame($d['date_enregistrement'],$p->getDateEnregistrement());
  }

}
