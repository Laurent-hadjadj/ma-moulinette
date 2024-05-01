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
use App\Entity\Main\BatchTraitement;
use App\Repository\Main\BatchTraitementRepository;
use PHPUnit\Framework\TestCase;

class BatchTraitementTest extends TestCase
{
  /**
   * [Description for dataset]
   * Jeu de données
   *
   * @return array
   *
   * Created at: 15/02/2023, 13:45:24 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function dataset(): array
  {
    return [
      ['id'=>1,
      'demarrage'=> 'Auto',
      'resultat'=>"1",
      'titre'=>"RECETTE 2.0.0-RC1",
      'portefeuille'=>"TEST MULTI PROJETS",
      'nombre_projet'=>1,
      'responsable'=>'admin @ma-moulinette',
      'debut_traitement'=> new DateTime(),
      'fin_traitement'=> new DateTime(),
      'date_enregistrement'=> new DateTime()]
    ];
  }

  /**
   * [Description for testBatchTraitementFindAll]
   * On récupère l'ensemble des données, on fait un getMavenKey().
   * @return void
   *
   * Created at: 15/02/2023, 13:48:39 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testBatchTraitementFindAll(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(BatchTraitementRepository::class);
    $mockRepo->method('findAll')->willReturn($d);
    $u=$mockRepo->findAll();
    $this->assertEquals('Auto', $u[0]['demarrage']);
  }

  /**
   * [Description for testBatchTraitementCountAttribut]
   * On vérifie le nombre d'attribut
   * @return void
   *
   * Created at: 15/02/2023, 13:49:23 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testBatchTraitementCountAttribut(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(BatchTraitement::class);
    /**
     * On compte le nombre attribut de la classe
     * On enlève :
     * __phpunit_originalObject, __phpunit_returnValueGeneration,__phpunit_invocationMocker
     */
    $nb=count((array)$mockRepo)-3;
    $this->assertEquals(count($d[0]), $nb);
  }

  /**
   * [Description for testBatchTraitementCount]
   * On compte le nombre d'enregistrement dans la collection.
   *
   * @return void
   *
   * Created at: 15/02/2023, 13:49:49 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testBatchTraitementCount(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(BatchTraitementRepository::class);
    $mockRepo->method('findAll')->willReturn($d);
    $u=$mockRepo->findAll();
    $this->assertCount(1, $u);
  }

    /**
   * [Description for testBatchTraitementTitreUnique]
   * On test si le titre dans l'entité est le même que dans le set !
   * @return void
   *
   * Created at: 15/02/2023, 13:50:13 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   *
   */
  public function testBatchTraitementTitreUnique(): void
  {
    /** On récupère le jeu de données */
    $dd=static::dataset();
    $d=$dd[0];

    $p = new BatchTraitement();
    $p->setTitre('RECETTE 2.0.0-RC1');
    $this->assertEquals($d['titre'], $p->getTitre());
  }

  /**
   * [Description for testBatchTraitementType]
   * On test le type
   * @return void
   *
   * Created at: 15/02/2023, 13:51:21 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testBatchTraitementType(): void
  {
    /** On récupère le jeu de données */
    $dd=static::dataset();
    $d=$dd[0];

    $p = new BatchTraitement();
    $p->setId($d['id']);
    $p->setDemarrage($d['demarrage']);
    $p->setResultat($d['resultat']);
    $p->setTitre($d['titre']);
    $p->setPortefeuille($d['portefeuille']);
    $p->setNombreProjet($d['nombre_projet']);
    $p->setResponsable($d['responsable']);
    $p->setDebutTraitement($d['debut_traitement']);
    $p->setFinTraitement($d['fin_traitement']);
    $p->setDateEnregistrement($d['date_enregistrement']);

    $this->assertIsInt($p->getId());
    $this->assertIsString($p->getDemarrage());
    $this->assertIsBool($p->isResultat());
    $this->assertIsString($p->getTitre());
    $this->assertIsString($p->getPortefeuille());
    $this->assertIsInt($p->getNombreProjet());
    $this->assertIsString($p->getResponsable());
    $this->assertIsObject($p->getDebutTraitement());
    $this->assertIsObject($p->getFinTraitement());
    $this->assertIsObject($p->getDateEnregistrement());
  }

  /**
   * [Description for testBatchTraitement]
   * test des getter/setter
   *
   * @return void
   *
   * Created at: 15/02/2023, 13:58:31 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testBatchTraitement(): void
  {
    /** On récupère le jeu de données */
    $dd=static::dataset();
    $d=$dd[0];

    $p = new BatchTraitement();
    $p->setId($d['id']);
    $p->setDemarrage($d['demarrage']);
    $p->setResultat($d['resultat']);
    $p->setTitre($d['titre']);
    $p->setPortefeuille($d['portefeuille']);
    $p->setNombreProjet($d['nombre_projet']);
    $p->setResponsable($d['responsable']);
    $p->setDebutTraitement($d['debut_traitement']);
    $p->setFinTraitement($d['fin_traitement']);
    $p->setDateEnregistrement($d['date_enregistrement']);

    $this->assertEquals($d['id'], $p->getId());
    $this->assertSame($d['demarrage'],$p->getDemarrage());
    $this->assertEquals($d['resultat'],$p->isResultat());
    $this->assertSame($d['titre'],$p->getTitre());
    $this->assertSame($d['portefeuille'],$p->getPortefeuille());
    $this->assertEquals($d['nombre_projet'],$p->getNombreProjet());
    $this->assertSame($d['responsable'],$p->getResponsable());
    $this->assertSame($d['debut_traitement'],$p->getDebutTraitement());
    $this->assertSame($d['fin_traitement'],$p->getFinTraitement());
    $this->assertSame($d['date_enregistrement'],$p->getDateEnregistrement());
  }

}
