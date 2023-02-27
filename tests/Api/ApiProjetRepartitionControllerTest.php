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
   * [Description for testApiProjetrepartition200()]
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
  public function testApiProjetrepartitionBug200(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);
    $client->request('GET', '/api/projet/repartition/details?'.static::$mavenKey.'&ype=BUG');
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
  public function testApiProjetrepartitionCodeSmell200(): void
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
  }


}
