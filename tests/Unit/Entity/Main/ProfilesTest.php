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
use App\Entity\Main\Profiles;
use App\Repository\Main\ProfilesRepository;
use DateTime;

class ProfilesTest extends TestCase
{

  /**
   * [Description for dataset]
   * Jeu de données
   *
   * @return array
   *
   * Created at: 14/02/2023, 10:15:44 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function dataset(): array
  {
    return [
      ['id' =>1, 'key'=> 'AXyXMubJRtAGLwAs7Zcv',
      'name'=>'MaPetiteEntrteprise v1.0.0 (2023)','language_name'=>'CSS', 'active_rule_count'=>31,
      'rules_update_at'=> new DateTime(),
      'is_default'=> 1,
      'date_enregistrement'=> new DateTime()],
      ['id' =>2, 'key'=> 'AXyXMubJRtAGLwAs7Zcv',
      'name'=>'MaPetiteEntrteprise v1.0.0 (2023)','language_name'=>'PHP', 'active_rule_count'=>203,
      'rules_update_at'=> new DateTime(),
      'is_default'=> 1,
      'date_enregistrement'=> new DateTime()],
    ];
  }

  /**
   * [Description for testProfilesFindAll]
   * On récupère l'ensemble des données, on fait un getMavenKey().
   * @return void
   *
   * Created at: 14/02/2023, 10:22:09 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testProfilesFindAll(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(ProfilesRepository::class);
    $mockRepo->method('findAll')->willReturn($d);
    $u=$mockRepo->findAll();
    $this->assertEquals('AXyXMubJRtAGLwAs7Zcv', $u[0]['key']);
  }

  /**
   * [Description for testProfilesCount]
   * On compte le nombre d'enregistrement dans la collection.
   *
   * @return void
   *
   * Created at: 14/02/2023, 10:23:36 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testProfilesCount(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(ProfilesRepository::class);
    $mockRepo->method('findAll')->willReturn($d);
    $u=$mockRepo->findAll();
    $this->assertCount(2, $u);
  }

  /**
   * [Description for testProfilesType]
   * On test le type
   * @return void
   *
   * Created at: 14/02/2023, 10:26:41 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testProfilesType(): void
  {
    /** On récupère le jeu de données */
    $dd=static::dataset();
    $d=$dd[0];

    $p = new Profiles();
    $p->setId($d['id']);
    $p->setKey($d['key']);
    $p->setName($d['name']);
    $p->setLanguageName($d['language_name']);
    $p->setActiveRuleCount($d['active_rule_count']);
    $p->setRulesUpdateAt($d['rules_update_at']);
    $p->setIsDefault($d['is_default']);
    $p->setDateEnregistrement($d['date_enregistrement']);

    $this->assertIsInt($p->getId());
    $this->assertIsString($p->getKey());
    $this->assertIsString($p->getName());
    $this->assertIsString($p->getLanguageName());
    $this->assertIsInt($p->getActiveRuleCount());
    $this->assertIsObject($p->getRulesUpdateAt());
    $this->assertIsBool($p->IsIsDefault());
    $this->assertIsObject($p->getDateEnregistrement());
  }

    /**
   * [Description for testProfilesCountAttribut]
   * On vérifie le nombre d'attribut
   * @return void
   *
   * Created at: 14/02/2023, 15:19:00 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testProfilesCountAttribut(): void
  {
    /** On récupère le jeu de données */
    $d=static::dataset();

    $mockRepo = $this->createMock(Profiles::class);
    /**
     * On compte le nombre attribut de la classe
     * On enlève :
     * __phpunit_originalObject, __phpunit_returnValueGeneration,__phpunit_invocationMocker
     */
    $nb=count((array)$mockRepo)-3;
    $this->assertEquals(count($d[0]), $nb);
  }

  /**
   * [Description for testProfiles]
   * test des getter/setter
   *
   * @return void
   *
   * Created at: 14/02/2023, 10:32:14 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testProfiles(): void
  {
    /** On récupère le jeu de données */
    $dd=static::dataset();
    $d=$dd[0];
    $p = new Profiles();
    $p->setId($d['id']);
    $p->setKey($d['key']);
    $p->setName($d['name']);
    $p->setLanguageName($d['language_name']);
    $p->setActiveRuleCount($d['active_rule_count']);
    $p->setRulesUpdateAt($d['rules_update_at']);
    $p->setIsDefault($d['is_default']);
    $p->setDateEnregistrement($d['date_enregistrement']);

    $this->assertEquals(1, $p->getId());
    $this->assertSame($d['key'],$p->getKey());
    $this->assertSame($d['name'],$p->getName());
    $this->assertSame($d['language_name'],$p->getLanguageName());
    $this->assertSame($d['active_rule_count'],$p->getActiveRuleCount());
    $this->assertSame($d['rules_update_at'],$p->getRulesUpdateAt());
    $this->assertTrue($p->IsIsDefault());
    $this->assertSame($d['date_enregistrement'],$p->getDateEnregistrement());
  }

}
