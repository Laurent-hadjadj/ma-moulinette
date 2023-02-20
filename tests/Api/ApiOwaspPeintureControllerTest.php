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

class ApiOwaspPeintureControllerTest extends ApiTestCase
{
  private static $userTest='admin@ma-moulinette.fr';
  private static $mavenKey='mavenKey=fr.franceagrimer:ma-moulinette';

  /**
   * [Description for testApiPeintureOwaspListe400]
   * On test l'API /api/peinture/owasp/liste sans argument
   * Retour : 400 - HTTP_BAD_REQUEST
   *
   * @return void
   *
   * Created at: 15/02/2023, 19:19:42 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiPeintureOwaspListe400(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);
    $client->request('GET', '/api/peinture/owasp/liste');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', 'application/json');
    $this->assertIsArray($decode);
    $this->assertEquals(2,count($decode));
    $this->assertEquals('400', $decode[0]);
  }

  /**
   * [Description for testApiPeintureProjetAnomalieDetails200]
   * On test l'API api/peinture/projet/anomalie/details avec une clé maven
   * Retour : 200 | 406
   *
   * @return void
   *
   * Created at: 15/02/2023, 19:21:18 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiPeintureOwaspListe200(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);
    $client->request('GET', '/api/peinture/owasp/liste?'.static::$mavenKey);
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', 'application/json');
    $this->assertIsArray($decode);

    if ($decode[0]===406) {
      $this->assertEquals(1,count($decode));
      $this->assertEquals('406', $decode[0]);
    }
    if ($decode[0]===200) {
      $this->assertEquals(57,count($decode));
      $this->assertEquals('200', $decode[0]);
      $this->assertArrayHasKey('code', $decode);
      $this->assertArrayHasKey('total', $decode);
      $this->assertArrayHasKey('bloquant', $decode);
      $this->assertArrayHasKey('critique', $decode);
      $this->assertArrayHasKey('majeur', $decode);
      $this->assertArrayHasKey('mineur', $decode);
      $this->assertArrayHasKey('a1', $decode);
      $this->assertArrayHasKey('a2', $decode);
      $this->assertArrayHasKey('a3', $decode);
      $this->assertArrayHasKey('a4', $decode);
      $this->assertArrayHasKey('a5', $decode);
      $this->assertArrayHasKey('a6', $decode);
      $this->assertArrayHasKey('a7', $decode);
      $this->assertArrayHasKey('a8', $decode);
      $this->assertArrayHasKey('a9', $decode);
      $this->assertArrayHasKey('a10', $decode);
      $this->assertArrayHasKey('a1Blocker', $decode);
      $this->assertArrayHasKey('a2Blocker', $decode);
      $this->assertArrayHasKey('a3Blocker', $decode);
      $this->assertArrayHasKey('a4Blocker', $decode);
      $this->assertArrayHasKey('a5Blocker', $decode);
      $this->assertArrayHasKey('a6Blocker', $decode);
      $this->assertArrayHasKey('a7Blocker', $decode);
      $this->assertArrayHasKey('a8Blocker', $decode);
      $this->assertArrayHasKey('a9Blocker', $decode);
      $this->assertArrayHasKey('a10Blocker', $decode);
      $this->assertArrayHasKey('a1Critical', $decode);
      $this->assertArrayHasKey('a2Critical', $decode);
      $this->assertArrayHasKey('a3Critical', $decode);
      $this->assertArrayHasKey('a4Critical', $decode);
      $this->assertArrayHasKey('a5Critical', $decode);
      $this->assertArrayHasKey('a6Critical', $decode);
      $this->assertArrayHasKey('a7Critical', $decode);
      $this->assertArrayHasKey('a8Critical', $decode);
      $this->assertArrayHasKey('a9Critical', $decode);
      $this->assertArrayHasKey('a10Critical', $decode);
      $this->assertArrayHasKey('a1Major', $decode);
      $this->assertArrayHasKey('a2Major', $decode);
      $this->assertArrayHasKey('a3Major', $decode);
      $this->assertArrayHasKey('a4Major', $decode);
      $this->assertArrayHasKey('a5Major', $decode);
      $this->assertArrayHasKey('a6Major', $decode);
      $this->assertArrayHasKey('a7Major', $decode);
      $this->assertArrayHasKey('a8Major', $decode);
      $this->assertArrayHasKey('a9Major', $decode);
      $this->assertArrayHasKey('a10Major', $decode);
      $this->assertArrayHasKey('a1Minor', $decode);
      $this->assertArrayHasKey('a2Minor', $decode);
      $this->assertArrayHasKey('a3Minor', $decode);
      $this->assertArrayHasKey('a4Minor', $decode);
      $this->assertArrayHasKey('a5Minor', $decode);
      $this->assertArrayHasKey('a6Minor', $decode);
      $this->assertArrayHasKey('a7Minor', $decode);
      $this->assertArrayHasKey('a8Minor', $decode);
      $this->assertArrayHasKey('a9Minor', $decode);
      $this->assertArrayHasKey('a10Minor', $decode);
    }
  }

/**
   * [Description for testApiPeintureOwaspHotspotInfo400]
   * On test l'API /api/peinture/owasp/liste sans argument
   * Retour : 400 - HTTP_BAD_REQUEST
   *
   * @return void
   *
   * Created at: 15/02/2023, 19:47:14 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiPeintureOwaspHotspotInfo400(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);
    $client->request('GET', '/api/peinture/owasp/hotspot/info');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', 'application/json');
    $this->assertIsArray($decode);
    $this->assertEquals(2,count($decode));
    $this->assertEquals('400', $decode[0]);
  }

  /**
   * [Description for testApiPeintureProjetAnomalieDetails200]
   * On test l'API api/peinture/projet/anomalie/details avec une clé maven
   * Retour : 200 | 406
   *
   * @return void
   *
   * Created at: 15/02/2023, 19:54:11 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiPeintureOwaspHotspotInfo200(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);
    $client->request('GET', '/api/peinture/owasp/hotspot/info?'.static::$mavenKey);
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', 'application/json');
    $this->assertIsArray($decode);
    $this->assertEquals(7,count($decode));
    $this->assertEquals('200', $decode[0]);
    $this->assertArrayHasKey('reviewed', $decode);
    $this->assertArrayHasKey('toReview', $decode);
    $this->assertArrayHasKey('total', $decode);
    $this->assertArrayHasKey('high', $decode);
    $this->assertArrayHasKey('medium', $decode);
    $this->assertArrayHasKey('low', $decode);
  }

  /**
   * [Description for testApiPeintureOwaspHotspotListe400]
   * On test l'API /api/peinture/owasp/hotspot/liste sans argument
   * Retour : 400 - HTTP_BAD_REQUEST
   *
   * @return void
   *
   * Created at: 15/02/2023, 20:18:22 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiPeintureOwaspHotspotListe400(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);
    $client->request('GET', '/api/peinture/owasp/hotspot/liste');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', 'application/json');
    $this->assertIsArray($decode);
    $this->assertEquals(2,count($decode));
    $this->assertEquals('400', $decode[0]);
  }

  /**
   * [Description for testApiPeintureOwaspHotspotListe200]
   * On test l'API /api/peinture/owasp/hotspot/liste avec une clé maven
   * Retour : 200
   *
   * @return void
   *
   * Created at: 15/02/2023, 20:27:26 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiPeintureOwaspHotspotListe200(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);
    $client->request('GET', '/api/peinture/owasp/hotspot/liste?'.static::$mavenKey);
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', 'application/json');
    $this->assertIsArray($decode);
    $this->assertEquals(11,count($decode));
    $this->assertEquals('200', $decode[0]);
    $this->assertArrayHasKey('menaceA1', $decode);
    $this->assertArrayHasKey('menaceA2', $decode);
    $this->assertArrayHasKey('menaceA3', $decode);
    $this->assertArrayHasKey('menaceA4', $decode);
    $this->assertArrayHasKey('menaceA5', $decode);
    $this->assertArrayHasKey('menaceA6', $decode);
    $this->assertArrayHasKey('menaceA7', $decode);
    $this->assertArrayHasKey('menaceA8', $decode);
    $this->assertArrayHasKey('menaceA9', $decode);
    $this->assertArrayHasKey('menaceA10', $decode);
  }

  /**
   * [Description for testApiPeintureOwaspHotspotListe400]
   * On test l'API  /api/peinture/owasp/hotspot/details sans clé maven
   * Retour : 400
   *
   * @return void
   *
   * Created at: 15/02/2023, 20:31:51 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiPeintureOwaspHotspotDetails400(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);
    $client->request('GET', '/api/peinture/owasp/hotspot/details');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', 'application/json');
    $this->assertIsArray($decode);
    $this->assertEquals(2,count($decode));
    $this->assertEquals('400', $decode[0]);
  }

  /**
   * [Description for testApiPeintureOwaspHotspotDetails400]
   * On test l'API  /api/peinture/owasp/hotspot/details avec une clé maven
   * Retour : 200
   *
   * @return void
   *
   * Created at: 15/02/2023, 20:34:02 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiPeintureOwaspHotspotDetails200(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);
    $client->request('GET', '/api/peinture/owasp/hotspot/details?'.static::$mavenKey);
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', 'application/json');
    $this->assertIsArray($decode);
    $this->assertEquals(14,count($decode['details'][0]));
    $this->assertEquals('200', $decode[0]);
    $this->assertArrayHasKey('id', $decode['details'][0]);
    $this->assertArrayHasKey('maven_key', $decode['details'][0]);
    $this->assertArrayHasKey('severity', $decode['details'][0]);
    $this->assertArrayHasKey('niveau', $decode['details'][0]);
    $this->assertArrayHasKey('status', $decode['details'][0]);
    $this->assertArrayHasKey('frontend', $decode['details'][0]);
    $this->assertArrayHasKey('backend', $decode['details'][0]);
    $this->assertArrayHasKey('autre', $decode['details'][0]);
    $this->assertArrayHasKey('file', $decode['details'][0]);
    $this->assertArrayHasKey('line', $decode['details'][0]);
    $this->assertArrayHasKey('rule', $decode['details'][0]);
    $this->assertArrayHasKey('message', $decode['details'][0]);
    $this->assertArrayHasKey('key', $decode['details'][0]);
    $this->assertArrayHasKey('date_enregistrement', $decode['details'][0]);
  }

  /**
   * [Description for testApiPeintureOwaspHotspotDetails400]
   * On test l'API /api/peinture/owasp/hotspot/severity sans clé maven
   * Retour : 400
   *
   * @return void
   *
   * Created at: 15/02/2023, 20:45:04 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiPeintureOwaspHotspotSeverity400(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);
    $client->request('GET', '/api/peinture/owasp/hotspot/severity');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', 'application/json');
    $this->assertIsArray($decode);
    $this->assertEquals(2,count($decode));
    $this->assertEquals('400', $decode[0]);
  }

  /**
   * [Description for testApiPeintureOwaspHotspotSeverity200]
   * On test l'API /api/peinture/owasp/hotspot/severity avec clé maven
   * Retour : 400
   *
   * @return void
   *
   * Created at: 15/02/2023, 20:46:51 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiPeintureOwaspHotspotSeverity200(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);
    $client->request('GET', '/api/peinture/owasp/hotspot/severity?'.static::$mavenKey);
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', 'application/json');
    $this->assertIsArray($decode);
    $this->assertEquals(4,count($decode));
    $this->assertEquals('200', $decode[0]);
    $this->assertArrayHasKey('high', $decode);
    $this->assertArrayHasKey('medium', $decode);
    $this->assertArrayHasKey('low', $decode);
  }

}
