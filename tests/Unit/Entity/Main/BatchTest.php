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
use App\Entity\Main\Batch;
use App\Repository\Main\BatchRepository;
use PHPUnit\Framework\TestCase;

class BatchTest extends TestCase
{
  /**
   * [Description for dataset]
   * Jeu de données
   *
   * @return array
   *
   * Created at: 15/02/2023, 14:03:48 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function dataset(): array
  {
    return [
      ['id'=>1,
      'statut'=> 1,
      'titre'=>'RECETTE 2.0.0-RC1',
      'description'=>'Tests de traitement multi-projets.',
      'portefeuille'=>'TEST MULTI PROJETS',
      'responsable'=>'admin @ma-moulinette',
      'nombre_projet'=>1,
      'execution'=>'x',
      'date_modification'=> new DateTime(),
      'date_enregistrement'=> new DateTime()]
    ];
  }

  /**
   * [Description for testBatchFindAll]
   * On récupère l'ensemble des données, on fait un getMavenKey().
   * @return void
   *
   * Created at: 15/02/2023, 14:08:20 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testBatchFindAll(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(BatchRepository::class);
    $mockRepo->method('findAll')->willReturn($d);
    $u=$mockRepo->findAll();
    $this->assertEquals('TEST MULTI PROJETS', $u[0]['portefeuille']);
  }

  /**
   * [Description for testBatchCountAttribut]
   * On vérifie le nombre d'attribut
   * @return void
   *
   * Created at: 15/02/2023, 14:09:37 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testBatchCountAttribut(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(Batch::class);
    /**
     * On compte le nombre attribut de la classe
     * On enlève :
     * __phpunit_originalObject, __phpunit_returnValueGeneration,__phpunit_invocationMocker
     */
    $nb=count((array)$mockRepo)-3;
    $this->assertEquals(count($d[0]), $nb);
  }

  /**
   * [Description for testBatchCount]
   * On compte le nombre d'enregistrement dans la collection.
   *
   * @return void
   *
   * Created at: 15/02/2023, 14:09:54 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testBatchCount(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(BatchRepository::class);
    $mockRepo->method('findAll')->willReturn($d);
    $u=$mockRepo->findAll();
    $this->assertCount(1, $u);
  }

    /**
   * [Description for testBatchTitreUnique]
   * On test si le titre dans l'entité est le même que dans le set !
   * @return void
   *
   * Created at: 15/02/2023, 14:10:08 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   *
   */
  public function testBatchTitreUnique(): void
  {
    /** On récupère le jeu de données */
    $dd=static::dataset();
    $d=$dd[0];

    $p = new Batch();
    $p->setTitre('RECETTE 2.0.0-RC1');
    $this->assertEquals($d['titre'], $p->getTitre());
  }

  /**
   * [Description for testBatchType]
   * On test le type
   * @return void
   *
   * Created at: 15/02/2023, 13:51:21 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testBatchType(): void
  {
    /** On récupère le jeu de données */
    $dd=static::dataset();
    $d=$dd[0];

    $p = new Batch();
    $p->setId($d['id']);
    $p->setStatut($d['statut']);
    $p->setTitre($d['titre']);
    $p->setDescription($d['description']);
    $p->setPortefeuille($d['portefeuille']);
    $p->setResponsable($d['responsable']);
    $p->setNombreProjet($d['nombre_projet']);
    $p->setExecution($d['execution']);
    $p->setDateModification($d['date_modification']);
    $p->setDateEnregistrement($d['date_enregistrement']);

    $this->assertIsInt($p->getId());
    $this->assertIsBool($p->isStatut());
    $this->assertIsString($p->getTitre());
    $this->assertIsString($p->getDescription());
    $this->assertIsString($p->getPortefeuille());
    $this->assertIsString($p->getResponsable());
    $this->assertIsInt($p->getNombreProjet());
    //$this->assertIsString($p->getExecution());
    $this->assertIsObject($p->getDateModification());
    $this->assertIsObject($p->getDateEnregistrement());
  }

  /**
   * [Description for testBatch]
   * test des getter/setter
   *
   * @return void
   *
   * Created at: 15/02/2023, 14:14:39 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testBatch(): void
  {
    /** On récupère le jeu de données */
    $dd=static::dataset();
    $d=$dd[0];

    $p = new Batch();
    $p->setId($d['id']);
    $p->setStatut($d['statut']);
    $p->setTitre($d['titre']);
    $p->setDescription($d['description']);
    $p->setPortefeuille($d['portefeuille']);
    $p->setResponsable($d['responsable']);
    $p->setNombreProjet($d['nombre_projet']);
    $p->setExecution($d['execution']);
    $p->setDateModification($d['date_modification']);
    $p->setDateEnregistrement($d['date_enregistrement']);

    $this->assertEquals($d['id'], $p->getId());
    $this->assertSame($d['titre'],$p->getTitre());
    $this->assertSame($d['description'],$p->getDescription());
    //$this->assertSame($d['portefeuille'],$p->getPortefeuille());
    $this->assertSame($d['responsable'],$p->getResponsable());
    $this->assertEquals($d['nombre_projet'],$p->getNombreProjet());
    //$this->assertSame($d['execution'],$p->getExecution());
    $this->assertSame($d['date_modification'],$p->getDateModification());
    $this->assertSame($d['date_enregistrement'],$p->getDateEnregistrement());
  }

}
