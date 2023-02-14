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
use App\Entity\Main\Properties;
use App\Repository\Main\PropertiesRepository;
use DateTime;

class PropertiesTest extends TestCase
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
      ['id' =>1, 'type'=> 'properties', 'projet_bd'=>100,'projet_sonar'=>12, 'profil_bd'=>12, 'profil_sonar'=>12,
      'date_creation'=> new DateTime(),
      'date_modification_projet'=> new DateTime(),
      'date_modification_profil'=> new DateTime()],
    ];
  }

  /**
   * [Description for testPropertiesFindAll]
   * On récupère l'ensemble des données, on fait un getMavenKey().
   * @return void
   *
   * Created at: 14/02/2023, 07:55:49 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testPropertiesFindAll(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(PropertiesRepository::class);
    $mockRepo->method('findAll')->willReturn($d);
    $u=$mockRepo->findAll();
    $this->assertEquals('properties', $u[0]['type']);
  }

  /**
   * [Description for testPropertiesCount]
   * On compte le nombre d'enregistrement dans la collection.
   *
   * @return void
   *
   * Created at: 14/02/2023, 07:56:11 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testPropertiesCount(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(PropertiesRepository::class);
    $mockRepo->method('findAll')->willReturn($d);
    $u=$mockRepo->findAll();
    $this->assertCount(1, $u);
  }

  public function testPropertiesType(): void
  {
    /** On récupère le jeu de données */
    $dd=static::dataset();
    $d=$dd[0];

    $p = new Properties();
    $p->setId($d['id']);
    $p->setType($d['type']);
    $p->setProjetBd($d['projet_bd']);
    $p->setProjetSonar($d['projet_sonar']);
    $p->setProfilBd($d['profil_bd']);
    $p->setProfilSonar($d['profil_sonar']);
    $p->setDateCreation($d['date_creation']);
    $p->setDateModificationProjet($d['date_modification_projet']);
    $p->setDateModificationProfil($d['date_modification_profil']);

    $this->assertIsInt($p->getId());
    $this->assertIsString($p->getType());
    $this->assertIsInt($p->getProjetBd());
    $this->assertIsInt($p->getProjetSonar());
    $this->assertIsInt($p->getProfilBd());
    $this->assertIsInt($p->getProfilSonar());
    $this->assertIsObject($p->getDateCreation());
    $this->assertIsObject($p->getDateModificationProjet());
    $this->assertIsObject($p->getDateModificationProfil());
  }

  /**
   * [Description for testProperties]
   * test des getter/setter
   *
   * @return void
   *
   * Created at: 14/02/2023, 07:56:27 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testProperties(): void
  {
    /** On récupère le jeu de données */
    $dd=static::dataset();
    $d=$dd[0];

    $p = new Properties();
    $p->setId($d['id']);
    $p->setType($d['type']);
    $p->setProjetBd($d['projet_bd']);
    $p->setProjetSonar($d['projet_sonar']);
    $p->setProfilBd($d['profil_bd']);
    $p->setProfilSonar($d['profil_sonar']);
    $p->setDateCreation($d['date_creation']);
    $p->setDateModificationProjet($d['date_modification_projet']);
    $p->setDateModificationProfil($d['date_modification_profil']);

    $this->assertEquals(1, $p->getId());
    $this->assertSame($d['type'],$p->getType());
    $this->assertSame($d['projet_bd'],$p->getProjetBd());
    $this->assertSame($d['projet_sonar'],$p->getProjetSonar());
    $this->assertSame($d['profil_bd'],$p->getProfilBd());
    $this->assertSame($d['profil_sonar'],$p->getProfilSonar());
    $this->assertSame($d['date_creation'],$p->getDateCreation());
    $this->assertSame($d['date_modification_projet'],$p->getDateModificationProjet());
    $this->assertSame($d['date_modification_profil'],$p->getDateModificationProfil());
  }

}
