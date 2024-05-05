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
use App\Entity\Main\Owasp;
use App\Repository\Main\OwaspRepository;
use DateTime;

class OwaspTest extends TestCase
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
      ['id' =>1, 'maven_key'=> 'fr.ma-petite-entreprise:ma-moulinette',
      'effort_total'=>100,
      'a1'=>1, 'a2'=>2, 'a3'=>3, 'a4'=>4, 'a5'=>5, 'a6'=>6, 'a7'=>7, 'a8'=>8, 'a9'=>9, 'a10'=>10,
      'a1_blocker'=>1, 'a1_critical'=>2, 'a1_major'=>3, 'a1_minor'=>4, 'a1_info'=>5,
      'a2_blocker'=>1, 'a2_critical'=>2, 'a2_major'=>3, 'a2_minor'=>4, 'a2_info'=>5,
      'a3_blocker'=>1, 'a3_critical'=>2, 'a3_major'=>3, 'a3_minor'=>4, 'a3_info'=>5,
      'a4_blocker'=>1, 'a4_critical'=>2, 'a4_major'=>3, 'a4_minor'=>4, 'a4_info'=>5,
      'a5_blocker'=>1, 'a5_critical'=>2, 'a5_major'=>3, 'a5_minor'=>4, 'a5_info'=>5,
      'a6_blocker'=>1, 'a6_critical'=>2, 'a6_major'=>3, 'a6_minor'=>4, 'a6_info'=>5,
      'a7_blocker'=>1, 'a7_critical'=>2, 'a7_major'=>3, 'a7_minor'=>4, 'a7_info'=>5,
      'a8_blocker'=>1, 'a8_critical'=>2, 'a8_major'=>3, 'a8_minor'=>4, 'a8_info'=>5,
      'a9_blocker'=>1, 'a9_critical'=>2, 'a9_major'=>3, 'a9_minor'=>4, 'a9_info'=>5,
      'a10_blocker'=>1, 'a10_critical'=>2, 'a10_major'=>3, 'a10_minor'=>4, 'a10_info'=>5,
      'date_enregistrement'=> new DateTime()],
    ];
  }

  /**
   * [Description for testOwaspFindAll]
   * On récupère l'ensemble des données, on fait un getMavenKey().
   * @return void
   *
   * Created at: 14/02/2023, 10:22:09 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testOwaspFindAll(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(OwaspRepository::class);
    $mockRepo->method('findAll')->willReturn($d);
    $u=$mockRepo->findAll();
    $this->assertEquals('fr.ma-petite-entreprise:ma-moulinette', $u[0]['maven_key']);
  }

  /**
   * [Description for testOwaspCount]
   * On compte le nombre d'enregistrement dans la collection.
   *
   * @return void
   *
   * Created at: 14/02/2023, 10:23:36 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testOwaspCount(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(OwaspRepository::class);
    $mockRepo->method('findAll')->willReturn($d);
    $u=$mockRepo->findAll();
    $this->assertCount(1, $u);
  }

  /**
   * [Description for testOwaspType]
   * On test le type
   * @return void
   *
   *  Created at: 14/02/2023, 11:03:09 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testOwaspType(): void
  {
    /** On récupère le jeu de données */
    $dd=static::dataset();
    $d=$dd[0];

    $p = new Owasp();
    $p->setId($d['id']);
    $p->setMavenKey($d['maven_key']);
    $p->setEffortTotal($d['effort_total']);
    $p->setA1($d['a1']);
    $p->setA2($d['a2']);
    $p->setA3($d['a3']);
    $p->setA4($d['a4']);
    $p->setA5($d['a5']);
    $p->setA6($d['a6']);
    $p->setA7($d['a7']);
    $p->setA8($d['a8']);
    $p->setA9($d['a9']);
    $p->setA10($d['a10']);

    $p->setA1Blocker($d['a1_blocker']);
    $p->setA1Critical($d['a1_critical']);
    $p->setA1Major($d['a1_major']);
    $p->setA1Minor($d['a1_minor']);
    $p->setA1Info($d['a1_info']);

    $p->setA2Blocker($d['a2_blocker']);
    $p->setA2Critical($d['a2_critical']);
    $p->setA2Major($d['a2_major']);
    $p->setA2Minor($d['a2_minor']);
    $p->setA2Info($d['a2_info']);

    $p->setA3Blocker($d['a3_blocker']);
    $p->setA3Critical($d['a3_critical']);
    $p->setA3Major($d['a3_major']);
    $p->setA3Minor($d['a3_minor']);
    $p->setA3Info($d['a3_info']);

    $p->setA4Blocker($d['a4_blocker']);
    $p->setA4Critical($d['a4_critical']);
    $p->setA4Major($d['a4_major']);
    $p->setA4Minor($d['a4_minor']);
    $p->setA4Info($d['a4_info']);

    $p->setA5Blocker($d['a5_blocker']);
    $p->setA5Critical($d['a5_critical']);
    $p->setA5Major($d['a5_major']);
    $p->setA5Minor($d['a5_minor']);
    $p->setA5Info($d['a5_info']);

    $p->setA6Blocker($d['a6_blocker']);
    $p->setA6Critical($d['a6_critical']);
    $p->setA6Major($d['a6_major']);
    $p->setA6Minor($d['a6_minor']);
    $p->setA6Info($d['a6_info']);

    $p->setA7Blocker($d['a7_blocker']);
    $p->setA7Critical($d['a7_critical']);
    $p->setA7Major($d['a7_major']);
    $p->setA7Minor($d['a7_minor']);
    $p->setA7Info($d['a7_info']);

    $p->setA8Blocker($d['a8_blocker']);
    $p->setA8Critical($d['a8_critical']);
    $p->setA8Major($d['a8_major']);
    $p->setA8Minor($d['a8_minor']);
    $p->setA8Info($d['a8_info']);

    $p->setA9Blocker($d['a9_blocker']);
    $p->setA9Critical($d['a9_critical']);
    $p->setA9Major($d['a9_major']);
    $p->setA9Minor($d['a9_minor']);
    $p->setA9Info($d['a9_info']);

    $p->setA10Blocker($d['a10_blocker']);
    $p->setA10Critical($d['a10_critical']);
    $p->setA10Major($d['a10_major']);
    $p->setA10Minor($d['a10_minor']);
    $p->setA10Info($d['a10_info']);

    $p->setDateEnregistrement($d['date_enregistrement']);

    $this->assertIsInt($p->getId());
    $this->assertIsString($p->getMavenKey());
    $this->assertIsInt($p->getEffortTotal());
    $this->assertIsInt($p->getA1());
    $this->assertIsInt($p->getA2());
    $this->assertIsInt($p->getA3());
    $this->assertIsInt($p->getA4());
    $this->assertIsInt($p->getA5());
    $this->assertIsInt($p->getA6());
    $this->assertIsInt($p->getA7());
    $this->assertIsInt($p->getA8());
    $this->assertIsInt($p->getA9());
    $this->assertIsInt($p->getA10());

    $this->assertIsInt($p->getA1Blocker());
    $this->assertIsInt($p->getA1Critical());
    $this->assertIsInt($p->getA1Major());
    $this->assertIsInt($p->getA1Minor());
    $this->assertIsInt($p->getA1Info());
    $this->assertIsInt($p->getA2Blocker());
    $this->assertIsInt($p->getA2Critical());
    $this->assertIsInt($p->getA2Major());
    $this->assertIsInt($p->getA2Minor());
    $this->assertIsInt($p->getA2Info());
    $this->assertIsInt($p->getA3Blocker());
    $this->assertIsInt($p->getA3Critical());
    $this->assertIsInt($p->getA3Major());
    $this->assertIsInt($p->getA3Minor());
    $this->assertIsInt($p->getA3Info());
    $this->assertIsInt($p->getA4Blocker());
    $this->assertIsInt($p->getA4Critical());
    $this->assertIsInt($p->getA4Major());
    $this->assertIsInt($p->getA4Minor());
    $this->assertIsInt($p->getA4Info());
    $this->assertIsInt($p->getA5Blocker());
    $this->assertIsInt($p->getA5Critical());
    $this->assertIsInt($p->getA5Major());
    $this->assertIsInt($p->getA5Minor());
    $this->assertIsInt($p->getA5Info());
    $this->assertIsInt($p->getA6Blocker());
    $this->assertIsInt($p->getA6Critical());
    $this->assertIsInt($p->getA6Major());
    $this->assertIsInt($p->getA6Minor());
    $this->assertIsInt($p->getA6Info());
    $this->assertIsInt($p->getA7Blocker());
    $this->assertIsInt($p->getA7Critical());
    $this->assertIsInt($p->getA7Major());
    $this->assertIsInt($p->getA7Minor());
    $this->assertIsInt($p->getA7Info());
    $this->assertIsInt($p->getA8Blocker());
    $this->assertIsInt($p->getA8Critical());
    $this->assertIsInt($p->getA8Major());
    $this->assertIsInt($p->getA8Minor());
    $this->assertIsInt($p->getA8Info());
    $this->assertIsInt($p->getA9Blocker());
    $this->assertIsInt($p->getA9Critical());
    $this->assertIsInt($p->getA9Major());
    $this->assertIsInt($p->getA9Minor());
    $this->assertIsInt($p->getA9Info());
    $this->assertIsInt($p->getA10Blocker());
    $this->assertIsInt($p->getA10Critical());
    $this->assertIsInt($p->getA10Major());
    $this->assertIsInt($p->getA10Minor());
    $this->assertIsInt($p->getA10Info());
    $this->assertIsObject($p->getDateEnregistrement());
  }

  /**
   * [Description for testOwasp]
   * test des getter/setter
   *
   * @return void
   *
   * Created at: 14/02/2023, 12:55:25 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testOwasp(): void
  {
    /** On récupère le jeu de données */
    $dd=static::dataset();
    $d=$dd[0];

    $p = new Owasp();
    $p->setId($d['id']);
    $p->setMavenKey($d['maven_key']);
    $p->setEffortTotal($d['effort_total']);
    $p->setA1($d['a1']);
    $p->setA2($d['a2']);
    $p->setA3($d['a3']);
    $p->setA4($d['a4']);
    $p->setA5($d['a5']);
    $p->setA6($d['a6']);
    $p->setA7($d['a7']);
    $p->setA8($d['a8']);
    $p->setA9($d['a9']);
    $p->setA10($d['a10']);

    $p->setA1Blocker($d['a1_blocker']);
    $p->setA1Critical($d['a1_critical']);
    $p->setA1Major($d['a1_major']);
    $p->setA1Minor($d['a1_minor']);
    $p->setA1Info($d['a1_info']);

    $p->setA2Blocker($d['a2_blocker']);
    $p->setA2Critical($d['a2_critical']);
    $p->setA2Major($d['a2_major']);
    $p->setA2Minor($d['a2_minor']);
    $p->setA2Info($d['a2_info']);

    $p->setA3Blocker($d['a3_blocker']);
    $p->setA3Critical($d['a3_critical']);
    $p->setA3Major($d['a3_major']);
    $p->setA3Minor($d['a3_minor']);
    $p->setA3Info($d['a3_info']);

    $p->setA4Blocker($d['a4_blocker']);
    $p->setA4Critical($d['a4_critical']);
    $p->setA4Major($d['a4_major']);
    $p->setA4Minor($d['a4_minor']);
    $p->setA4Info($d['a4_info']);

    $p->setA5Blocker($d['a5_blocker']);
    $p->setA5Critical($d['a5_critical']);
    $p->setA5Major($d['a5_major']);
    $p->setA5Minor($d['a5_minor']);
    $p->setA5Info($d['a5_info']);

    $p->setA6Blocker($d['a6_blocker']);
    $p->setA6Critical($d['a6_critical']);
    $p->setA6Major($d['a6_major']);
    $p->setA6Minor($d['a6_minor']);
    $p->setA6Info($d['a6_info']);

    $p->setA7Blocker($d['a7_blocker']);
    $p->setA7Critical($d['a7_critical']);
    $p->setA7Major($d['a7_major']);
    $p->setA7Minor($d['a7_minor']);
    $p->setA7Info($d['a7_info']);

    $p->setA8Blocker($d['a8_blocker']);
    $p->setA8Critical($d['a8_critical']);
    $p->setA8Major($d['a8_major']);
    $p->setA8Minor($d['a8_minor']);
    $p->setA8Info($d['a8_info']);

    $p->setA9Blocker($d['a9_blocker']);
    $p->setA9Critical($d['a9_critical']);
    $p->setA9Major($d['a9_major']);
    $p->setA9Minor($d['a9_minor']);
    $p->setA9Info($d['a9_info']);

    $p->setA10Blocker($d['a10_blocker']);
    $p->setA10Critical($d['a10_critical']);
    $p->setA10Major($d['a10_major']);
    $p->setA10Minor($d['a10_minor']);
    $p->setA10Info($d['a10_info']);

    $p->setDateEnregistrement($d['date_enregistrement']);

    $this->assertEquals($d['id'],$p->getId());
    $this->assertSame($d['maven_key'],$p->getMavenKey());
    $this->assertSame($d['effort_total'],$p->getEffortTotal());
    $this->assertSame($d['a1'],$p->getA1());
    $this->assertSame($d['a2'],$p->getA2());
    $this->assertSame($d['a3'],$p->getA3());
    $this->assertSame($d['a4'],$p->getA4());
    $this->assertSame($d['a5'],$p->getA5());
    $this->assertSame($d['a6'],$p->getA6());
    $this->assertSame($d['a7'],$p->getA7());
    $this->assertSame($d['a8'],$p->getA8());
    $this->assertSame($d['a9'],$p->getA9());
    $this->assertSame($d['a10'],$p->getA10());

    $this->assertSame($d['a1_blocker'],$p->getA1Blocker());
    $this->assertSame($d['a1_critical'],$p->getA1Critical());
    $this->assertSame($d['a1_major'],$p->getA1Major());
    $this->assertSame($d['a1_minor'],$p->getA1Minor());
    $this->assertSame($d['a1_info'],$p->getA1Info());
    $this->assertSame($d['a2_blocker'],$p->getA2Blocker());
    $this->assertSame($d['a2_critical'],$p->getA2Critical());
    $this->assertSame($d['a2_major'],$p->getA2Major());
    $this->assertSame($d['a2_minor'],$p->getA2Minor());
    $this->assertSame($d['a2_info'],$p->getA2Info());
    $this->assertSame($d['a3_blocker'],$p->getA3Blocker());
    $this->assertSame($d['a3_critical'],$p->getA3Critical());
    $this->assertSame($d['a3_major'],$p->getA3Major());
    $this->assertSame($d['a3_minor'],$p->getA3Minor());
    $this->assertSame($d['a3_info'],$p->getA3Info());
    $this->assertSame($d['a4_blocker'],$p->getA4Blocker());
    $this->assertSame($d['a4_critical'],$p->getA4Critical());
    $this->assertSame($d['a4_major'],$p->getA4Major());
    $this->assertSame($d['a4_minor'],$p->getA4Minor());
    $this->assertSame($d['a4_info'],$p->getA4Info());
    $this->assertSame($d['a5_blocker'],$p->getA5Blocker());
    $this->assertSame($d['a5_critical'],$p->getA5Critical());
    $this->assertSame($d['a5_major'],$p->getA5Major());
    $this->assertSame($d['a5_minor'],$p->getA5Minor());
    $this->assertSame($d['a5_info'],$p->getA5Info());
    $this->assertSame($d['a6_blocker'],$p->getA6Blocker());
    $this->assertSame($d['a6_critical'],$p->getA6Critical());
    $this->assertSame($d['a6_major'],$p->getA6Major());
    $this->assertSame($d['a6_minor'],$p->getA6Minor());
    $this->assertSame($d['a6_info'],$p->getA6Info());
    $this->assertSame($d['a7_blocker'],$p->getA7Blocker());
    $this->assertSame($d['a7_critical'],$p->getA7Critical());
    $this->assertSame($d['a7_major'],$p->getA7Major());
    $this->assertSame($d['a7_minor'],$p->getA7Minor());
    $this->assertSame($d['a7_info'],$p->getA7Info());
    $this->assertSame($d['a8_blocker'],$p->getA8Blocker());
    $this->assertSame($d['a8_critical'],$p->getA8Critical());
    $this->assertSame($d['a8_major'],$p->getA8Major());
    $this->assertSame($d['a8_minor'],$p->getA8Minor());
    $this->assertSame($d['a8_info'],$p->getA8Info());
    $this->assertSame($d['a9_blocker'],$p->getA9Blocker());
    $this->assertSame($d['a9_critical'],$p->getA9Critical());
    $this->assertSame($d['a9_major'],$p->getA9Major());
    $this->assertSame($d['a9_minor'],$p->getA9Minor());
    $this->assertSame($d['a9_info'],$p->getA9Info());
    $this->assertSame($d['a10_blocker'],$p->getA10Blocker());
    $this->assertSame($d['a10_critical'],$p->getA10Critical());
    $this->assertSame($d['a10_major'],$p->getA10Major());
    $this->assertSame($d['a10_minor'],$p->getA10Minor());
    $this->assertSame($d['a10_info'],$p->getA10Info());
    $this->assertSame($d['date_enregistrement'],$p->getDateEnregistrement());
  }

}
