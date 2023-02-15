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

use DateTime;
use App\Entity\Main\Equipe;
use App\Repository\Main\EquipeRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class EquipeTest extends TestCase
{
  /**
   * [Description for dataset]
   * Jeu de données
   *
   * @return array
   *
   * Created at: 15/02/2023, 07:08:27 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function dataset(): array
  {
    return [
      ['id'=>1,
      'titre'=> 'MA PETITE ENTREPRISE',
      'description'=>"Développement de l'application Ma-Moulinette",
      'date_modification'=> new DateTime(),
      'date_enregistrement'=> new DateTime()]
    ];
  }

  /**
   * [Description for testEquipeFindAll]
   * On récupère l'ensemble des données, on fait un getMavenKey().
   * @return void
   *
   * Created at: 15/02/2023, 07:10:24 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testEquipeFindAll(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(EquipeRepository::class);
    $mockRepo->method('findAll')->willReturn($d);
    $u=$mockRepo->findAll();
    $this->assertEquals('MA PETITE ENTREPRISE', $u[0]['titre']);
  }

  /**
   * [Description for testEquipeCountAttribut]
   * On vérifie le nombre d'attribut
   * @return void
   *
   * Created at: 15/02/2023, 07:11:17 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testEquipeCountAttribut(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(Equipe::class);
    /**
     * On compte le nombre attribut de la classe
     * On enlève :
     * __phpunit_originalObject, __phpunit_returnValueGeneration,__phpunit_invocationMocker
     */
    $nb=count((array)$mockRepo)-3;
    $this->assertEquals(count($d[0]), $nb);
  }

  /**
   * [Description for testEquipeCount]
   * On compte le nombre d'enregistrement dans la collection.
   *
   * @return void
   *
   * Created at: 14/02/2023, 19:04:34 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testEquipeCount(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(EquipeRepository::class);
    $mockRepo->method('findAll')->willReturn($d);
    $u=$mockRepo->findAll();
    $this->assertCount(1, $u);
  }

    /**
   * [Description for testEquipeTitreUnique]
   * On test si le titre dans l'entité est le même que dans le set !
   * @return void
   *
   * Created at: 15/02/2023, 07:18:54 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   *
   */
  public function testEquipeTitreUnique(): void
  {
    /** On récupère le jeu de données */
    $dd=static::dataset();
    $d=$dd[0];

    $p = new Equipe();
    $p->setTitre('MA PETITE ENTREPRISE');
    $this->assertEquals($d['titre'], $p->getTitre());
  }

  /**
   * [Description for testEquipeType]
   * On test le type
   * @return void
   *
   * Created at: 14/02/2023, 19:04:51 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testEquipeType(): void
  {
    /** On récupère le jeu de données */
    $dd=static::dataset();
    $d=$dd[0];

    $p = new Equipe();
    $p->setId($d['id']);
    $p->setTitre($d['titre']);
    $p->setDescription($d['description']);
    $p->setDateModification($d['date_modification']);
    $p->setDateEnregistrement($d['date_enregistrement']);

    $this->assertIsInt($p->getId());
    $this->assertIsString($p->getTitre());
    $this->assertIsString($p->getDescription());
    $this->assertIsObject($p->getDateModification());
    $this->assertIsObject($p->getDateEnregistrement());
  }

  /**
   * [Description for testEquipe]
   * test des getter/setter
   *
   * @return void
   *
   * Created at: 15/02/2023, 07:15:39 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testEquipe(): void
  {
    /** On récupère le jeu de données */
    $dd=static::dataset();
    $d=$dd[0];

    $p = new Equipe();
    $p->setId($d['id']);
    $p->setTitre($d['titre']);
    $p->setDescription($d['description']);
    $p->setDateModification($d['date_modification']);
    $p->setDateEnregistrement($d['date_enregistrement']);

    $this->assertEquals($d['id'], $p->getId());
    $this->assertSame($d['titre'],$p->getTitre());
    $this->assertSame($d['description'],$p->getDescription());
    $this->assertSame($d['date_modification'],$p->getDateModification());
    $this->assertSame($d['date_enregistrement'],$p->getDateEnregistrement());

  }

}
