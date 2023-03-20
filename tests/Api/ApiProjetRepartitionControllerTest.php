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

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Repository\Main\UtilisateurRepository;
use App\Controller\ApiProfilController;

class ApiProjetRepartitionControllerTest extends ApiTestCase
{
  private static $userTest='admin@ma-moulinette.fr';
  private static $mavenKey='mavenKey=fr.ma-moulinette:ma-moulinette';
  private static $mavenkey='fr.ma-moulinette:ma-moulinette';
  private static $applicationJson='application/json';
  public static $strContentType = 'application/json';

  /**
   * [Description for testApiProjetrepartition400()]
   * On test l'API api/peinture/projet/anomalie/details sans argument
   * Retour : 400
   *
   * @return void
   *
   * Created at: 15/02/2023, 21:02:54 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetrepartition400(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);
    $client->request('GET', '/api/projet/repartition/details');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', 'application/json');
    $this->assertIsArray($decode);
    $this->assertEquals(2,count($decode));
    $this->assertEquals('400', $decode[0]);
    $this->assertArrayHasKey('message', $decode);
    $this->assertEquals('La clé maven est vide!', $decode['message']);
  }

  /**
   * [Description for testApiProjetrepartition()]
   * On test l'API api/peinture/projet/anomalie/details avec une clé maven
   * Type : BUG
   * Retour : 200
   *
   * @return void
   *
   * Created at: 15/02/2023, 21:03:46 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetRepartitionBug(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);
    $client->request('GET', '/api/projet/repartition/details?'.static::$mavenKey.'&type=BUG');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', 'application/json');
    $this->assertIsArray($decode);
    $this->assertEquals(8,count($decode));
    $this->assertEquals('200', $decode[0]);
    $this->assertArrayHasKey('total', $decode);
    $this->assertArrayHasKey('type', $decode);
    $this->assertArrayHasKey('blocker', $decode);
    $this->assertArrayHasKey('critical', $decode);
    $this->assertArrayHasKey('major', $decode);
    $this->assertArrayHasKey('minor', $decode);
    $this->assertArrayHasKey('info', $decode);
    $this->assertEquals(61, $decode['total']);
    $this->assertEquals("BUG", $decode['type']);
    $this->assertEquals(0, $decode['blocker']);
    $this->assertEquals(0, $decode['critical']);
    $this->assertEquals(31, $decode['major']);
    $this->assertEquals(30, $decode['minor']);
    $this->assertEquals(0, $decode['info']);
    $this->assertIsInt($decode['total']);
    $this->assertIsString($decode['type']);
    $this->assertIsInt($decode['blocker']);
    $this->assertIsInt($decode['critical']);
    $this->assertIsInt($decode['major']);
    $this->assertIsInt($decode['minor']);
    $this->assertIsInt($decode['info']);
  }

  /**
   * [Description for testApiProjetrepartitionVulnerability200]
   * On test l'API api/peinture/projet/anomalie/details avec une clé maven
   * Type : VULNERABILITY
   * Retour : 200
   *
   * @return void
   *
   * Created at: 15/02/2023, 21:19:53 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetrepartitionVulnerability200(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);
    $client->request('GET', '/api/projet/repartition/details?'.static::$mavenKey.'&type=VULNERABILITY');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', 'application/json');
    $this->assertIsArray($decode);
    $this->assertEquals(8,count($decode));
    $this->assertEquals('200', $decode[0]);
    $this->assertArrayHasKey('total', $decode);
    $this->assertArrayHasKey('type', $decode);
    $this->assertArrayHasKey('blocker', $decode);
    $this->assertArrayHasKey('critical', $decode);
    $this->assertArrayHasKey('major', $decode);
    $this->assertArrayHasKey('minor', $decode);
    $this->assertArrayHasKey('info', $decode);
    $this->assertEquals(0, $decode['total']);
    $this->assertEquals("VULNERABILITY", $decode['type']);
    $this->assertEquals(0, $decode['blocker']);
    $this->assertEquals(0, $decode['critical']);
    $this->assertEquals(0, $decode['major']);
    $this->assertEquals(0, $decode['minor']);
    $this->assertEquals(0, $decode['info']);
    $this->assertIsInt($decode['total']);
    $this->assertIsString($decode['type']);
    $this->assertIsInt($decode['blocker']);
    $this->assertIsInt($decode['critical']);
    $this->assertIsInt($decode['major']);
    $this->assertIsInt($decode['minor']);
    $this->assertIsInt($decode['info']);
  }

    /**
   * [Description for testApiProjetrepartitionVulnerability200]
   * On test l'API api/peinture/projet/anomalie/details avec une clé maven
   * Type : CODE_SMELL
   * Retour : 200
   *
   * @return void
   *
   * Created at: 15/02/2023, 21:21:44 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetRepartitionCodeSmell200(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);
    $client->request('GET', '/api/projet/repartition/details?'.static::$mavenKey.'&type=CODE_SMELL');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', 'application/json');
    $this->assertIsArray($decode);
    $this->assertEquals(8,count($decode));
    $this->assertEquals('200', $decode[0]);
    $this->assertArrayHasKey('total', $decode);
    $this->assertArrayHasKey('type', $decode);
    $this->assertArrayHasKey('blocker', $decode);
    $this->assertArrayHasKey('critical', $decode);
    $this->assertArrayHasKey('major', $decode);
    $this->assertArrayHasKey('minor', $decode);
    $this->assertArrayHasKey('info', $decode);
    $this->assertEquals(1895, $decode['total']);
    $this->assertEquals("CODE_SMELL", $decode['type']);
    $this->assertEquals(17, $decode['blocker']);
    $this->assertEquals(133, $decode['critical']);
    $this->assertEquals(1087, $decode['major']);
    $this->assertEquals(656, $decode['minor']);
    $this->assertEquals(2, $decode['info']);
    $this->assertIsInt($decode['total']);
    $this->assertIsString($decode['type']);
    $this->assertIsInt($decode['blocker']);
    $this->assertIsInt($decode['critical']);
    $this->assertIsInt($decode['major']);
    $this->assertIsInt($decode['minor']);
    $this->assertIsInt($decode['info']);
  }

  /**
   * [Description for testApiProjetRepartitionCollectBugBloker]
   * BUG (VULNERABILITY,CODE_SMELL) - BLOCKER (CRITICAL, MAJOR, INFO, MINOR)
   * @return void
   *
   * Created at: 27/02/2023, 18:55:01 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetRepartitionCollectBugBlocker(): void
  {
    $data=['mavenKey'=>static::$mavenkey, 'type'=>"BUG", 'severity'=>"BLOCKER", 'setup'=>1677324427466, 'mode'=> "TEST"];

    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('PUT', '/api/projet/repartition/collecte', ['json'=>$data]);
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(7,count($decode));
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('total', $decode);
    $this->assertArrayHasKey('type', $decode);
    $this->assertArrayHasKey('severity', $decode);
    $this->assertArrayHasKey('setup', $decode);
    $this->assertArrayHasKey('temps', $decode);
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertNotEquals('null', $decode['mode']);
    $this->assertEquals(0, $decode['total']);
    $this->assertEquals('BUG', $decode['type']);
    $this->assertEquals('BLOCKER', $decode['severity']);
    $this->assertEquals(1677324427466, $decode['setup']);
    $this->assertGreaterThanOrEqual(1, $decode['temps']);
    $this->assertIsInt($decode['total']);
    $this->assertIsInt($decode['setup']);
    $this->assertIsInt($decode['temps']);
    $this->assertIsString($decode['mode']);
    $this->assertIsString($decode['type']);
    $this->assertIsString($decode['severity']);
  }

  /**
   * [Description for testApiProjetRepartitionCollectBugCritical]
   * BUG (VULNERABILITY,CODE_SMELL) - CRITICAL (BLOCKER, MAJOR, INFO, MINOR)
   * @return void
   *
   * Created at: 27/02/2023, 19:11:16 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetRepartitionCollectBugCritical(): void
  {
    $data=['mavenKey'=>static::$mavenkey, 'type'=>"BUG", 'severity'=>"CRITICAL", 'setup'=>1677324427466, 'mode'=> "TEST"];

    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('PUT', '/api/projet/repartition/collecte', ['json'=>$data]);
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(7,count($decode));
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('total', $decode);
    $this->assertArrayHasKey('type', $decode);
    $this->assertArrayHasKey('severity', $decode);
    $this->assertArrayHasKey('setup', $decode);
    $this->assertArrayHasKey('temps', $decode);
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertNotEquals('null', $decode['mode']);
    $this->assertEquals(0, $decode['total']);
    $this->assertEquals('BUG', $decode['type']);
    $this->assertEquals('CRITICAL', $decode['severity']);
    $this->assertEquals(1677324427466, $decode['setup']);
    $this->assertGreaterThanOrEqual(1, $decode['temps']);
    $this->assertIsInt($decode['total']);
    $this->assertIsInt($decode['setup']);
    $this->assertIsInt($decode['temps']);
    $this->assertIsString($decode['mode']);
    $this->assertIsString($decode['type']);
    $this->assertIsString($decode['severity']);
  }

  /**
   * [Description for testApiProjetRepartitionCollectBugMajor]
   * BUG (VULNERABILITY,CODE_SMELL) -  MAJOR (BLOCKER, CRITICAL, INFO, MINOR)
   * @return void
   *
   * Created at: 27/02/2023, 19:13:53 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetRepartitionCollectBugMajor(): void
  {
    $data=['mavenKey'=>static::$mavenkey, 'type'=>"BUG", 'severity'=>"MAJOR", 'setup'=>1677324427466, 'mode'=> "TEST"];

    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('PUT', '/api/projet/repartition/collecte', ['json'=>$data]);
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(7,count($decode));
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('total', $decode);
    $this->assertArrayHasKey('type', $decode);
    $this->assertArrayHasKey('severity', $decode);
    $this->assertArrayHasKey('setup', $decode);
    $this->assertArrayHasKey('temps', $decode);
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertNotEquals('null', $decode['mode']);
    $this->assertEquals(31, $decode['total']);
    $this->assertEquals('BUG', $decode['type']);
    $this->assertEquals('MAJOR', $decode['severity']);
    $this->assertEquals(1677324427466, $decode['setup']);
    $this->assertGreaterThanOrEqual(1, $decode['temps']);
    $this->assertIsInt($decode['total']);
    $this->assertIsInt($decode['setup']);
    $this->assertIsInt($decode['temps']);
    $this->assertIsString($decode['mode']);
    $this->assertIsString($decode['type']);
    $this->assertIsString($decode['severity']);
  }

  /**
   * [Description for testApiProjetRepartitionCollectBugInfo]
   * BUG (VULNERABILITY,CODE_SMELL) - INFO (BLOCKER, CRITICAL,  MAJOR, INFO, MINOR)
   * @return void
   *
   * Created at: 27/02/2023, 19:16:19 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetRepartitionCollectBugInfo(): void
  {
    $data=['mavenKey'=>static::$mavenkey, 'type'=>"BUG", 'severity'=>"INFO", 'setup'=>1677324427466, 'mode'=> "TEST"];

    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('PUT', '/api/projet/repartition/collecte', ['json'=>$data]);
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(7,count($decode));
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('total', $decode);
    $this->assertArrayHasKey('type', $decode);
    $this->assertArrayHasKey('severity', $decode);
    $this->assertArrayHasKey('setup', $decode);
    $this->assertArrayHasKey('temps', $decode);
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertNotEquals('null', $decode['mode']);
    $this->assertEquals(0, $decode['total']);
    $this->assertEquals('BUG', $decode['type']);
    $this->assertEquals('INFO', $decode['severity']);
    $this->assertEquals(1677324427466, $decode['setup']);
    $this->assertGreaterThanOrEqual(1, $decode['temps']);
    $this->assertIsInt($decode['total']);
    $this->assertIsInt($decode['setup']);
    $this->assertIsInt($decode['temps']);
    $this->assertIsString($decode['mode']);
    $this->assertIsString($decode['type']);
    $this->assertIsString($decode['severity']);
  }

    /**
   * [Description for testApiProjetRepartitionCollectBugMinor]
   * BUG (VULNERABILITY,CODE_SMELL) - INFO (BLOCKER, CRITICAL,  MAJOR, INFO, MINOR)
   * @return void
   *
   * Created at: 27/02/2023, 19:18:15 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetRepartitionCollectBugMinor(): void
  {
    $data=['mavenKey'=>static::$mavenkey, 'type'=>"BUG", 'severity'=>"MINOR", 'setup'=>1677324427466, 'mode'=> "TEST"];

    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('PUT', '/api/projet/repartition/collecte', ['json'=>$data]);
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(7,count($decode));
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('total', $decode);
    $this->assertArrayHasKey('type', $decode);
    $this->assertArrayHasKey('severity', $decode);
    $this->assertArrayHasKey('setup', $decode);
    $this->assertArrayHasKey('temps', $decode);
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertNotEquals('null', $decode['mode']);
    $this->assertEquals(30, $decode['total']);
    $this->assertEquals('BUG', $decode['type']);
    $this->assertEquals('MINOR', $decode['severity']);
    $this->assertEquals(1677324427466, $decode['setup']);
    $this->assertGreaterThanOrEqual(1, $decode['temps']);
    $this->assertIsInt($decode['total']);
    $this->assertIsInt($decode['setup']);
    $this->assertIsInt($decode['temps']);
    $this->assertIsString($decode['mode']);
    $this->assertIsString($decode['type']);
    $this->assertIsString($decode['severity']);
  }

  /**
   * [Description for testApiProjetRepartitionCollectVulnerabilityBlocker]
   * VULNERABILITY (BUG,CODE_SMELL) - BLOCKER (CRITICAL, MAJOR, INFO, MINOR)
   * @return void
   *
   * Created at: 27/02/2023, 19:42:27 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetRepartitionCollectVulnerabilityBlocker(): void
  {
    $data=['mavenKey'=>static::$mavenkey, 'type'=>"VULNERABILITY", 'severity'=>"BLOCKER", 'setup'=>1677324427466, 'mode'=> "TEST"];

    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('PUT', '/api/projet/repartition/collecte', ['json'=>$data]);
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(7,count($decode));
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('total', $decode);
    $this->assertArrayHasKey('type', $decode);
    $this->assertArrayHasKey('severity', $decode);
    $this->assertArrayHasKey('setup', $decode);
    $this->assertArrayHasKey('temps', $decode);
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertNotEquals('null', $decode['mode']);
    $this->assertEquals(0, $decode['total']);
    $this->assertEquals('VULNERABILITY', $decode['type']);
    $this->assertEquals('BLOCKER', $decode['severity']);
    $this->assertEquals(1677324427466, $decode['setup']);
    $this->assertGreaterThanOrEqual(1, $decode['temps']);
    $this->assertIsInt($decode['total']);
    $this->assertIsInt($decode['setup']);
    $this->assertIsInt($decode['temps']);
    $this->assertIsString($decode['mode']);
    $this->assertIsString($decode['type']);
    $this->assertIsString($decode['severity']);
  }

  /**
   * [Description for testApiProjetRepartitionCollectVulnerabilityCritical]
   * VULNERABILITY (BUG,CODE_SMELL) - CRITICAL (BLOCKER, MAJOR, INFO, MINOR)
   * @return void
   *
   * Created at: 27/02/2023, 19:43:07 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetRepartitionCollectVulnerabilityCritical(): void
  {
    $data=['mavenKey'=>static::$mavenkey, 'type'=>"VULNERABILITY", 'severity'=>"CRITICAL", 'setup'=>1677324427466, 'mode'=> "TEST"];

    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('PUT', '/api/projet/repartition/collecte', ['json'=>$data]);
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(7,count($decode));
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('total', $decode);
    $this->assertArrayHasKey('type', $decode);
    $this->assertArrayHasKey('severity', $decode);
    $this->assertArrayHasKey('setup', $decode);
    $this->assertArrayHasKey('temps', $decode);
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertNotEquals('null', $decode['mode']);
    $this->assertEquals(0, $decode['total']);
    $this->assertEquals('VULNERABILITY', $decode['type']);
    $this->assertEquals('CRITICAL', $decode['severity']);
    $this->assertEquals(1677324427466, $decode['setup']);
    $this->assertGreaterThanOrEqual(1, $decode['temps']);
    $this->assertIsInt($decode['total']);
    $this->assertIsInt($decode['setup']);
    $this->assertIsInt($decode['temps']);
    $this->assertIsString($decode['mode']);
    $this->assertIsString($decode['type']);
    $this->assertIsString($decode['severity']);
  }

  /**
   * [Description for testApiProjetRepartitionCollectVulnerabilityMajor]
   * VULNERABILITY (BUG,CODE_SMELL) - MAJOR (BLOCKER,CRITICAL, INFO, MINOR)
   * @return void
   *
   * Created at: 27/02/2023, 19:30:53 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetRepartitionCollectVulnerabilityMajor(): void
  {
    $data=['mavenKey'=>static::$mavenkey, 'type'=>"VULNERABILITY", 'severity'=>"MAJOR", 'setup'=>1677324427466, 'mode'=> "TEST"];

    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('PUT', '/api/projet/repartition/collecte', ['json'=>$data]);
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(7,count($decode));
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('total', $decode);
    $this->assertArrayHasKey('type', $decode);
    $this->assertArrayHasKey('severity', $decode);
    $this->assertArrayHasKey('setup', $decode);
    $this->assertArrayHasKey('temps', $decode);
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertNotEquals('null', $decode['mode']);
    $this->assertEquals(0, $decode['total']);
    $this->assertEquals('VULNERABILITY', $decode['type']);
    $this->assertEquals('MAJOR', $decode['severity']);
    $this->assertEquals(1677324427466, $decode['setup']);
    $this->assertGreaterThanOrEqual(1, $decode['temps']);
    $this->assertIsInt($decode['total']);
    $this->assertIsInt($decode['setup']);
    $this->assertIsInt($decode['temps']);
    $this->assertIsString($decode['mode']);
    $this->assertIsString($decode['type']);
    $this->assertIsString($decode['severity']);
  }

  /**
   * [Description for testApiProjetRepartitionCollectVulnerabilityInfo]
   * VULNERABILITY (BUG,CODE_SMELL) - INFO (CRITICAL, MAJOR, BLOCKER, MINOR)
   * @return void
   *
   * Created at: 27/02/2023, 19:45:19 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetRepartitionCollectVulnerabilityInfo(): void
  {
    $data=['mavenKey'=>static::$mavenkey, 'type'=>"VULNERABILITY", 'severity'=>"INFO", 'setup'=>1677324427466, 'mode'=> "TEST"];

    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('PUT', '/api/projet/repartition/collecte', ['json'=>$data]);
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(7,count($decode));
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('total', $decode);
    $this->assertArrayHasKey('type', $decode);
    $this->assertArrayHasKey('severity', $decode);
    $this->assertArrayHasKey('setup', $decode);
    $this->assertArrayHasKey('temps', $decode);
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertNotEquals('null', $decode['mode']);
    $this->assertEquals(0, $decode['total']);
    $this->assertEquals('VULNERABILITY', $decode['type']);
    $this->assertEquals('INFO', $decode['severity']);
    $this->assertEquals(1677324427466, $decode['setup']);
    $this->assertGreaterThanOrEqual(1, $decode['temps']);
    $this->assertIsInt($decode['total']);
    $this->assertIsInt($decode['setup']);
    $this->assertIsInt($decode['temps']);
    $this->assertIsString($decode['mode']);
    $this->assertIsString($decode['type']);
    $this->assertIsString($decode['severity']);
  }

    /**
   * [Description for testApiProjetRepartitionCollectVulnerabilityMinor]
   * VULNERABILITY (BUG,CODE_SMELL) - , MINOR (BLOCKER, CRITICAL, MAJOR, INFO)
   * @return void
   *
   * Created at: 27/02/2023, 19:46:16 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetRepartitionCollectVulnerabilityMinor(): void
  {
    $data=['mavenKey'=>static::$mavenkey, 'type'=>"VULNERABILITY", 'severity'=>"MINOR", 'setup'=>1677324427466, 'mode'=> "TEST"];

    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('PUT', '/api/projet/repartition/collecte', ['json'=>$data]);
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(7,count($decode));
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('total', $decode);
    $this->assertArrayHasKey('type', $decode);
    $this->assertArrayHasKey('severity', $decode);
    $this->assertArrayHasKey('setup', $decode);
    $this->assertArrayHasKey('temps', $decode);
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertNotEquals('null', $decode['mode']);
    $this->assertEquals(0, $decode['total']);
    $this->assertEquals('VULNERABILITY', $decode['type']);
    $this->assertEquals('MINOR', $decode['severity']);
    $this->assertEquals(1677324427466, $decode['setup']);
    $this->assertGreaterThanOrEqual(1, $decode['temps']);
    $this->assertIsInt($decode['total']);
    $this->assertIsInt($decode['setup']);
    $this->assertIsInt($decode['temps']);
    $this->assertIsString($decode['mode']);
    $this->assertIsString($decode['type']);
    $this->assertIsString($decode['severity']);
  }

    /**
   * [Description for testApiProjetRepartitionCollectCodeSmellBlocker]
   * CODE_SMELL (BUG,VULNERABILITY) - BLOCKER (CRITICAL, MAJOR, INFO, MINOR)
   * @return void
   *
   * Created at: 27/02/2023, 19:57:09 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetRepartitionCollectCodeSmellBlocker(): void
  {
    $data=['mavenKey'=>static::$mavenkey, 'type'=>"CODE_SMELL", 'severity'=>"BLOCKER", 'setup'=>1677324427466, 'mode'=> "TEST"];

    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('PUT', '/api/projet/repartition/collecte', ['json'=>$data]);
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(7,count($decode));
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('total', $decode);
    $this->assertArrayHasKey('type', $decode);
    $this->assertArrayHasKey('severity', $decode);
    $this->assertArrayHasKey('setup', $decode);
    $this->assertArrayHasKey('temps', $decode);
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertNotEquals('null', $decode['mode']);
    $this->assertEquals(17, $decode['total']);
    $this->assertEquals('CODE_SMELL', $decode['type']);
    $this->assertEquals('BLOCKER', $decode['severity']);
    $this->assertEquals(1677324427466, $decode['setup']);
    $this->assertGreaterThanOrEqual(1, $decode['temps']);
    $this->assertIsInt($decode['total']);
    $this->assertIsInt($decode['setup']);
    $this->assertIsInt($decode['temps']);
    $this->assertIsString($decode['mode']);
    $this->assertIsString($decode['type']);
    $this->assertIsString($decode['severity']);
  }

  /**
   * [Description for testApiProjetRepartitionCollectCodeSmellCritical]
   * VULNERABILITY (BUG,CODE_SMELL) - CRITICAL (BLOCKER, MAJOR, INFO, MINOR)
   * @return void
   *
   * Created at: 27/02/2023, 19:57:24 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetRepartitionCollectCodeSmellCritical(): void
  {
    $data=['mavenKey'=>static::$mavenkey, 'type'=>"CODE_SMELL", 'severity'=>"CRITICAL", 'setup'=>1677324427466, 'mode'=> "TEST"];

    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('PUT', '/api/projet/repartition/collecte', ['json'=>$data]);
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(7,count($decode));
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('total', $decode);
    $this->assertArrayHasKey('type', $decode);
    $this->assertArrayHasKey('severity', $decode);
    $this->assertArrayHasKey('setup', $decode);
    $this->assertArrayHasKey('temps', $decode);
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertNotEquals('null', $decode['mode']);
    $this->assertEquals(133, $decode['total']);
    $this->assertEquals('CODE_SMELL', $decode['type']);
    $this->assertEquals('CRITICAL', $decode['severity']);
    $this->assertEquals(1677324427466, $decode['setup']);
    $this->assertGreaterThanOrEqual(1, $decode['temps']);
    $this->assertIsInt($decode['total']);
    $this->assertIsInt($decode['setup']);
    $this->assertIsInt($decode['temps']);
    $this->assertIsString($decode['mode']);
    $this->assertIsString($decode['type']);
    $this->assertIsString($decode['severity']);
  }

  /**
   * [Description for testApiProjetRepartitionCollectCodeSmellMajor]
   * VULNERABILITY (BUG,CODE_SMELL) - MAJOR (BLOCKER,CRITICAL, INFO, MINOR)
   * @return void
   *
   * Created at: 27/02/2023, 19:57:39 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetRepartitionCollectCodeSmellMajor(): void
  {
    $data=['mavenKey'=>static::$mavenkey, 'type'=>"CODE_SMELL", 'severity'=>"MAJOR", 'setup'=>1677324427466, 'mode'=> "TEST"];

    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('PUT', '/api/projet/repartition/collecte', ['json'=>$data]);
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(7,count($decode));
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('total', $decode);
    $this->assertArrayHasKey('type', $decode);
    $this->assertArrayHasKey('severity', $decode);
    $this->assertArrayHasKey('setup', $decode);
    $this->assertArrayHasKey('temps', $decode);
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertNotEquals('null', $decode['mode']);
    $this->assertEquals(1087, $decode['total']);
    $this->assertEquals('CODE_SMELL', $decode['type']);
    $this->assertEquals('MAJOR', $decode['severity']);
    $this->assertEquals(1677324427466, $decode['setup']);
    $this->assertGreaterThanOrEqual(1, $decode['temps']);
    $this->assertIsInt($decode['total']);
    $this->assertIsInt($decode['setup']);
    $this->assertIsInt($decode['temps']);
    $this->assertIsString($decode['mode']);
    $this->assertIsString($decode['type']);
    $this->assertIsString($decode['severity']);
  }

  /**
   * [Description for testApiProjetRepartitionCollectCodeSmellInfo]
   * VULNERABILITY (BUG,CODE_SMELL) - INFO (CRITICAL, MAJOR, BLOCKER, MINOR)
   * @return void
   *
   * Created at: 27/02/2023, 19:57:51 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetRepartitionCollectCodeSmellInfo(): void
  {
    $data=['mavenKey'=>static::$mavenkey, 'type'=>"CODE_SMELL", 'severity'=>"INFO", 'setup'=>1677324427466, 'mode'=> "TEST"];

    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('PUT', '/api/projet/repartition/collecte', ['json'=>$data]);
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(7,count($decode));
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('total', $decode);
    $this->assertArrayHasKey('type', $decode);
    $this->assertArrayHasKey('severity', $decode);
    $this->assertArrayHasKey('setup', $decode);
    $this->assertArrayHasKey('temps', $decode);
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertNotEquals('null', $decode['mode']);
    $this->assertEquals(2, $decode['total']);
    $this->assertEquals('CODE_SMELL', $decode['type']);
    $this->assertEquals('INFO', $decode['severity']);
    $this->assertEquals(1677324427466, $decode['setup']);
    $this->assertGreaterThanOrEqual(1, $decode['temps']);
    $this->assertIsInt($decode['total']);
    $this->assertIsInt($decode['setup']);
    $this->assertIsInt($decode['temps']);
    $this->assertIsString($decode['mode']);
    $this->assertIsString($decode['type']);
    $this->assertIsString($decode['severity']);
  }

    /**
   * [Description for testApiProjetRepartitionCollectCodeSmellMinor]
   * VULNERABILITY (BUG,CODE_SMELL) - , MINOR (BLOCKER, CRITICAL, MAJOR, INFO)
   * @return void
   *
   * Created at: 27/02/2023, 19:46:16 (Europe/Paris)
   * Created at: 27/02/2023, 19:58:08 (Europe/Paris)
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetRepartitionCollectCodeSmellMinor(): void
  {
    $data=['mavenKey'=>static::$mavenkey, 'type'=>"CODE_SMELL", 'severity'=>"MINOR", 'setup'=>1677324427466, 'mode'=> "TEST"];

    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('PUT', '/api/projet/repartition/collecte', ['json'=>$data]);
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(7,count($decode));
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('total', $decode);
    $this->assertArrayHasKey('type', $decode);
    $this->assertArrayHasKey('severity', $decode);
    $this->assertArrayHasKey('setup', $decode);
    $this->assertArrayHasKey('temps', $decode);
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertNotEquals('null', $decode['mode']);
    $this->assertEquals(656, $decode['total']);
    $this->assertEquals('CODE_SMELL', $decode['type']);
    $this->assertEquals('MINOR', $decode['severity']);
    $this->assertEquals(1677324427466, $decode['setup']);
    $this->assertGreaterThanOrEqual(1, $decode['temps']);
    $this->assertIsInt($decode['total']);
    $this->assertIsInt($decode['setup']);
    $this->assertIsInt($decode['temps']);
    $this->assertIsString($decode['mode']);
    $this->assertIsString($decode['type']);
    $this->assertIsString($decode['severity']);
  }

  /**
   * [Description for testApiProjetRepartitionClear]
   *
   * @return void
   *
   * Created at: 27/02/2023, 20:41:32 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetRepartitionClear(): void
  {
    $data=['mavenKey'=>static::$mavenkey, 'mode'=> "TEST"];

    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('PUT', '/api/projet/repartition/clear', ['json'=>$data]);
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', 'application/json');
    $this->assertIsArray($decode);
    $this->assertEquals(3,count($decode));
    $this->assertEquals('200', $decode[0]);
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('code', $decode);
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertNotEquals('null', $decode['mode']);
    $this->assertEquals('OK', $decode['code']);
    $this->assertIsString($decode['mode']);
    $this->assertIsString($decode['code']);
  }

  /**
   * [Description for testApiProjetRepartitionAnalyseBugBlocker]
   * BUG - BLOCKER
   * @return void
   *
   * Created at: 27/02/2023, 20:49:14 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetRepartitionAnalyseBugBlocker(): void
  {
    $data=['mavenKey'=>static::$mavenkey, 'type'=>"BUG", 'severity'=>"BLOCKER", 'setup'=>1677324427466, 'mode'=> "TEST"];

    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('PUT', '/api/projet/repartition/analyse', ['json'=>$data]);
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', 'application/json');
    $this->assertIsArray($decode);
    $this->assertEquals(4,count($decode));
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertNotEquals('null', $decode['mode']);
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('code', $decode);
    $this->assertArrayHasKey('repartition', $decode);
    $this->assertEquals('OK', $decode['code']);
    $this->assertEquals(0, $decode['repartition']['erreur']);
    $this->assertEquals(0, $decode['repartition']['frontend']);
    $this->assertEquals(0, $decode['repartition']['backend']);
    $this->assertEquals(0, $decode['repartition']['autre']);
    $this->assertIsString($decode['mode']);
    $this->assertIsString($decode['code']);
    $this->assertIsArray($decode['repartition']);
  }

  /**
   * [Description for testApiProjetRepartitionAnalyseBugCritical]
   * BUG - CRITICAL
   * @return void
   *
   * Created at: 27/02/2023, 21:05:28 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetRepartitionAnalyseBugCrtical(): void
  {
    $data=['mavenKey'=>static::$mavenkey, 'type'=>"BUG", 'severity'=>"CRITICAL", 'setup'=>1677324427466, 'mode'=> "TEST"];

    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('PUT', '/api/projet/repartition/analyse', ['json'=>$data]);
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', 'application/json');
    $this->assertIsArray($decode);
    $this->assertEquals(4,count($decode));
    $this->assertEquals('200', $decode[0]);
    $this->assertArrayHasKey('code', $decode);
    $this->assertArrayHasKey('repartition', $decode);
    $this->assertEquals('OK', $decode['code']);
    $this->assertEquals(0, $decode['repartition']['erreur']);
    $this->assertEquals(0, $decode['repartition']['frontend']);
    $this->assertEquals(0, $decode['repartition']['backend']);
    $this->assertEquals(0, $decode['repartition']['autre']);
    $this->assertIsString($decode['code']);
    $this->assertIsArray($decode['repartition']);
  }

  /**
   * [Description for testApiProjetRepartitionAnalyseBugCritical]
   * BUG - MAJOR
   * @return void
   *
   * Created at: 27/02/2023, 21:05:28 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetRepartitionAnalyseBugMajor(): void
  {
    $data=['mavenKey'=>static::$mavenkey, 'type'=>"BUG", 'severity'=>"MAJOR", 'setup'=>1677324427466, 'mode'=> "TEST"];

    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('PUT', '/api/projet/repartition/analyse', ['json'=>$data]);
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', 'application/json');
    $this->assertIsArray($decode);
    $this->assertEquals(4,count($decode));
    $this->assertEquals('200', $decode[0]);
    $this->assertArrayHasKey('code', $decode);
    $this->assertArrayHasKey('repartition', $decode);
    $this->assertEquals('OK', $decode['code']);
    $this->assertEquals(0, $decode['repartition']['erreur']);
    $this->assertEquals(31, $decode['repartition']['frontend']);
    $this->assertEquals(0, $decode['repartition']['backend']);
    $this->assertEquals(0, $decode['repartition']['autre']);
    $this->assertIsString($decode['code']);
    $this->assertIsArray($decode['repartition']);
  }

  /**
   * [Description for testApiProjetRepartitionAnalyseBugInfo]
   * BUG - INFO
   * @return void
   *
   * Created at: 27/02/2023, 21:05:28 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetRepartitionAnalyseBugInfo(): void
  {
    $data=['mavenKey'=>static::$mavenkey, 'type'=>"BUG", 'severity'=>"INFO", 'setup'=>1677324427466, 'mode'=> "TEST"];

    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('PUT', '/api/projet/repartition/analyse', ['json'=>$data]);
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', 'application/json');
    $this->assertIsArray($decode);
    $this->assertEquals(4,count($decode));
    $this->assertEquals('200', $decode[0]);
    $this->assertArrayHasKey('code', $decode);
    $this->assertArrayHasKey('repartition', $decode);
    $this->assertEquals('OK', $decode['code']);
    $this->assertEquals(0, $decode['repartition']['erreur']);
    $this->assertEquals(0, $decode['repartition']['frontend']);
    $this->assertEquals(0, $decode['repartition']['backend']);
    $this->assertEquals(0, $decode['repartition']['autre']);
    $this->assertIsString($decode['code']);
    $this->assertIsArray($decode['repartition']);
  }

    /**
   * [Description for testApiProjetRepartitionAnalyseBugMinor]
   * BUG - Minor
   * @return void
   *
   * Created at: 27/02/2023, 21:05:28 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetRepartitionAnalyseBugMinor(): void
  {
    $data=['mavenKey'=>static::$mavenkey, 'type'=>"BUG", 'severity'=>"Minor", 'setup'=>1677324427466, 'mode'=> "TEST"];

    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('PUT', '/api/projet/repartition/analyse', ['json'=>$data]);
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', 'application/json');
    $this->assertIsArray($decode);
    $this->assertEquals(4,count($decode));
    $this->assertEquals('200', $decode[0]);
    $this->assertArrayHasKey('code', $decode);
    $this->assertArrayHasKey('repartition', $decode);
    $this->assertEquals('OK', $decode['code']);
    $this->assertEquals(0, $decode['repartition']['erreur']);
    $this->assertEquals(0, $decode['repartition']['frontend']);
    $this->assertEquals(0, $decode['repartition']['backend']);
    $this->assertEquals(0, $decode['repartition']['autre']);
    $this->assertIsString($decode['code']);
    $this->assertIsArray($decode['repartition']);
  }

  /**
   * [Description for testApiProjetRepartitionAnalyseBugBlocker]
   * VULNERABILITY - BLOCKER
   * @return void
   *
   * Created at: 27/02/2023, 21:16:10 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetRepartitionAnalyseVulnerabilityBlocker(): void
  {
    $data=['mavenKey'=>static::$mavenkey, 'type'=>"VULNERABILITY", 'severity'=>"BLOCKER", 'setup'=>1677324427466, 'mode'=> "TEST"];

    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('PUT', '/api/projet/repartition/analyse', ['json'=>$data]);
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', 'application/json');
    $this->assertIsArray($decode);
    $this->assertEquals(4,count($decode));
    $this->assertEquals('200', $decode[0]);
    $this->assertArrayHasKey('code', $decode);
    $this->assertArrayHasKey('repartition', $decode);
    $this->assertEquals('OK', $decode['code']);
    $this->assertEquals(0, $decode['repartition']['erreur']);
    $this->assertEquals(0, $decode['repartition']['frontend']);
    $this->assertEquals(0, $decode['repartition']['backend']);
    $this->assertEquals(0, $decode['repartition']['autre']);
    $this->assertIsString($decode['code']);
    $this->assertIsArray($decode['repartition']);
  }

  /**
   * [Description for testApiProjetRepartitionAnalyseBugCritical]
   * VULNERABILITY - CRITICAL
   * @return void
   *
   * Created at: 27/02/2023, 21:16:45 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetRepartitionAnalyseVulnerabilityCritical(): void
  {
    $data=['mavenKey'=>static::$mavenkey, 'type'=>"VULNERABILITY", 'severity'=>"CRITICAL", 'setup'=>1677324427466, 'mode'=> "TEST"];

    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('PUT', '/api/projet/repartition/analyse', ['json'=>$data]);
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', 'application/json');
    $this->assertIsArray($decode);
    $this->assertEquals(4,count($decode));
    $this->assertEquals('200', $decode[0]);
    $this->assertArrayHasKey('code', $decode);
    $this->assertArrayHasKey('repartition', $decode);
    $this->assertEquals('OK', $decode['code']);
    $this->assertEquals(0, $decode['repartition']['erreur']);
    $this->assertEquals(0, $decode['repartition']['frontend']);
    $this->assertEquals(0, $decode['repartition']['backend']);
    $this->assertEquals(0, $decode['repartition']['autre']);
    $this->assertIsString($decode['code']);
    $this->assertIsArray($decode['repartition']);
  }

  /**
   * [Description for testApiProjetRepartitionAnalyseBugCritical]
   * VULNERABILITY - MAJOR
   * @return void
   *
   * Created at: 27/02/2023, 21:17:10 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetRepartitionAnalyseVulnerabilityMajor(): void
  {
    $data=['mavenKey'=>static::$mavenkey, 'type'=>"VULNERABILITY", 'severity'=>"MAJOR", 'setup'=>1677324427466, 'mode'=> "TEST"];

    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('PUT', '/api/projet/repartition/analyse', ['json'=>$data]);
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', 'application/json');
    $this->assertIsArray($decode);
    $this->assertEquals(4,count($decode));
    $this->assertEquals('200', $decode[0]);
    $this->assertArrayHasKey('code', $decode);
    $this->assertArrayHasKey('repartition', $decode);
    $this->assertEquals('OK', $decode['code']);
    $this->assertEquals(0, $decode['repartition']['erreur']);
    $this->assertEquals(0, $decode['repartition']['frontend']);
    $this->assertEquals(0, $decode['repartition']['backend']);
    $this->assertEquals(0, $decode['repartition']['autre']);
    $this->assertIsString($decode['code']);
    $this->assertIsArray($decode['repartition']);
  }

  /**
   * [Description for testApiProjetRepartitionAnalyseBugInfo]
   * VULNERABILITY - INFO
   * @return void
   *
   * Created at: 27/02/2023, 21:17:46 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetRepartitionAnalyseVulnerabilityInfo(): void
  {
    $data=['mavenKey'=>static::$mavenkey, 'type'=>"VULNERABILITY", 'severity'=>"INFO", 'setup'=>1677324427466, 'mode'=> "TEST"];

    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('PUT', '/api/projet/repartition/analyse', ['json'=>$data]);
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', 'application/json');
    $this->assertIsArray($decode);
    $this->assertEquals(4,count($decode));
    $this->assertEquals('200', $decode[0]);
    $this->assertArrayHasKey('code', $decode);
    $this->assertArrayHasKey('repartition', $decode);
    $this->assertEquals('OK', $decode['code']);
    $this->assertEquals(0, $decode['repartition']['erreur']);
    $this->assertEquals(0, $decode['repartition']['frontend']);
    $this->assertEquals(0, $decode['repartition']['backend']);
    $this->assertEquals(0, $decode['repartition']['autre']);
    $this->assertIsString($decode['code']);
    $this->assertIsArray($decode['repartition']);
  }

    /**
   * [Description for testApiProjetRepartitionAnalyseBugMinor]
   * VULNERABILITY - Minor
   * @return void
   *
   * Created at: 27/02/2023, 21:18:12 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetRepartitionAnalyseVulnerabilityMinor(): void
  {
    $data=['mavenKey'=>static::$mavenkey, 'type'=>"VULNERABILITY", 'severity'=>"Minor", 'setup'=>1677324427466, 'mode'=> "TEST"];

    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('PUT', '/api/projet/repartition/analyse', ['json'=>$data]);
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', 'application/json');
    $this->assertIsArray($decode);
    $this->assertEquals(4,count($decode));
    $this->assertEquals('200', $decode[0]);
    $this->assertArrayHasKey('code', $decode);
    $this->assertArrayHasKey('repartition', $decode);
    $this->assertEquals('OK', $decode['code']);
    $this->assertEquals(0, $decode['repartition']['erreur']);
    $this->assertEquals(0, $decode['repartition']['frontend']);
    $this->assertEquals(0, $decode['repartition']['backend']);
    $this->assertEquals(0, $decode['repartition']['autre']);
    $this->assertIsString($decode['code']);
    $this->assertIsArray($decode['repartition']);
  }

    /**
   * [Description for testApiProjetRepartitionAnalyseCodeSmellBlocker]
   * CODE_SMELL - BLOCKER
   * @return void
   *
   * Created at: 27/02/2023, 21:23:46 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetRepartitionAnalyseCodeSmellBlocker(): void
  {
    $data=['mavenKey'=>static::$mavenkey, 'type'=>"CODE_SMELL", 'severity'=>"BLOCKER", 'setup'=>1677324427466, 'mode'=> "TEST"];

    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('PUT', '/api/projet/repartition/analyse', ['json'=>$data]);
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', 'application/json');
    $this->assertIsArray($decode);
    $this->assertEquals(4,count($decode));
    $this->assertEquals('200', $decode[0]);
    $this->assertArrayHasKey('code', $decode);
    $this->assertArrayHasKey('repartition', $decode);
    $this->assertEquals('OK', $decode['code']);
    $this->assertEquals(0, $decode['repartition']['erreur']);
    $this->assertEquals(17, $decode['repartition']['frontend']);
    $this->assertEquals(0, $decode['repartition']['backend']);
    $this->assertEquals(0, $decode['repartition']['autre']);
    $this->assertIsString($decode['code']);
    $this->assertIsArray($decode['repartition']);
  }

  /**
   * [Description for testApiProjetRepartitionAnalyseCodeSmellCritical]
   * CODE_SMELL - CRITICAL
   * @return void
   *
   * Created at: 27/02/2023, 21:23:28 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetRepartitionAnalyseCodeSmellCritical(): void
  {
    $data=['mavenKey'=>static::$mavenkey, 'type'=>"CODE_SMELL", 'severity'=>"CRITICAL", 'setup'=>1677324427466, 'mode'=> "TEST"];

    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('PUT', '/api/projet/repartition/analyse', ['json'=>$data]);
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', 'application/json');
    $this->assertIsArray($decode);
    $this->assertEquals(4,count($decode));
    $this->assertEquals('200', $decode[0]);
    $this->assertArrayHasKey('code', $decode);
    $this->assertArrayHasKey('repartition', $decode);
    $this->assertEquals('OK', $decode['code']);
    $this->assertEquals(0, $decode['repartition']['erreur']);
    $this->assertEquals(133, $decode['repartition']['frontend']);
    $this->assertEquals(0, $decode['repartition']['backend']);
    $this->assertEquals(0, $decode['repartition']['autre']);
    $this->assertIsString($decode['code']);
    $this->assertIsArray($decode['repartition']);
  }

  /**
   * [Description for testApiProjetRepartitionAnalyseBugCritical]
   * CODE_SMELL - MAJOR
   * @return void
   *
   * Created at: 27/02/2023, 21:23:15 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetRepartitionAnalyseCodeSmellMajor(): void
  {
    $data=['mavenKey'=>static::$mavenkey, 'type'=>"CODE_SMELL", 'severity'=>"MAJOR", 'setup'=>1677324427466, 'mode'=> "TEST"];

    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('PUT', '/api/projet/repartition/analyse', ['json'=>$data]);
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', 'application/json');
    $this->assertIsArray($decode);
    $this->assertEquals(4,count($decode));
    $this->assertEquals('200', $decode[0]);
    $this->assertArrayHasKey('code', $decode);
    $this->assertArrayHasKey('repartition', $decode);
    $this->assertEquals('OK', $decode['code']);
    $this->assertEquals(0, $decode['repartition']['erreur']);
    $this->assertEquals(1087, $decode['repartition']['frontend']);
    $this->assertEquals(0, $decode['repartition']['backend']);
    $this->assertEquals(0, $decode['repartition']['autre']);
    $this->assertIsString($decode['code']);
    $this->assertIsArray($decode['repartition']);
  }

  /**
   * [Description for testApiProjetRepartitionAnalyseCodeSmellInfo]
   * CODE_SMELL - INFO
   * @return void
   *
   * Created at: 27/02/2023, 21:23:00 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetRepartitionAnalyseCodeSmellInfo(): void
  {
    $data=['mavenKey'=>static::$mavenkey, 'type'=>"CODE_SMELL", 'severity'=>"INFO", 'setup'=>1677324427466, 'mode'=> "TEST"];

    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('PUT', '/api/projet/repartition/analyse', ['json'=>$data]);
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', 'application/json');
    $this->assertIsArray($decode);
    $this->assertEquals(4,count($decode));
    $this->assertEquals('200', $decode[0]);
    $this->assertArrayHasKey('code', $decode);
    $this->assertArrayHasKey('repartition', $decode);
    $this->assertEquals('OK', $decode['code']);
    $this->assertEquals(0, $decode['repartition']['erreur']);
    $this->assertEquals(2, $decode['repartition']['frontend']);
    $this->assertEquals(0, $decode['repartition']['backend']);
    $this->assertEquals(0, $decode['repartition']['autre']);
    $this->assertIsString($decode['code']);
    $this->assertIsArray($decode['repartition']);
  }

    /**
   * [Description for testApiProjetRepartitionAnalyseCodeSmellMinor]
   * CODE_SMELL - Minor
   * @return void
   *
   * Created at: 27/02/2023, 21:22:28 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetRepartitionAnalyseCodeSmellMinor(): void
  {
    $data=['mavenKey'=>static::$mavenkey, 'type'=>"CODE_SMELL", 'severity'=>"Minor", 'setup'=>1677324427466, 'mode'=> "TEST"];

    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('PUT', '/api/projet/repartition/analyse', ['json'=>$data]);
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', 'application/json');
    $this->assertIsArray($decode);
    $this->assertEquals(4,count($decode));
    $this->assertEquals('200', $decode[0]);
    $this->assertArrayHasKey('code', $decode);
    $this->assertArrayHasKey('repartition', $decode);
    $this->assertEquals('OK', $decode['code']);
    $this->assertEquals(0, $decode['repartition']['erreur']);
    $this->assertEquals(0, $decode['repartition']['frontend']);
    $this->assertEquals(0, $decode['repartition']['backend']);
    $this->assertEquals(0, $decode['repartition']['autre']);
    $this->assertIsString($decode['code']);
    $this->assertIsArray($decode['repartition']);
  }

}
