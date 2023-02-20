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

use App\Controller\ApiProfilController;
use App\Repository\Main\UtilisateurRepository;

use PHPUnit\Framework\TestCase;
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SuiviControllerTest extends ApiTestCase
{
  private static $userTest='admin@ma-moulinette.fr';
  private static $mavenKey='mavenKey=fr.franceagrimer:ma-moulinette';
  private static $mavenkey='fr.franceagrimer:ma-moulinette';
  private static $applicationJson='application/json';
  public static $strContentType = 'application/json';

  /**
   * [Description for testSuivi400()]
   * On appel le controller qui génére la vue en mode TEST
   * Retour HTTP 400
   * @return void
   *
   * Created at: 18/02/2023, 20:18:34 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testSuiviController400(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/suivi?mode=TEST');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('400', $decode[0]);
    $this->assertEquals('La clé maven est vide!', $decode['message']);
    $this->assertEquals('TEST', $decode['mode']);
  }

  /**
   * [Description for testMockApiListeVersion200]
   * Mock HTTP 200
   * @return void
   *
   * Created at: 19/02/2023, 13:37:02 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testMockApiListeVersion200(): void
  {
    $mockResponseJson=[];
    $mockResponse = new MockResponse($mockResponseJson, [
        'http_code' => 200,
        'response_headers' => ['Content-Type: application/json'],
    ]);
    $uriBase='http://localhost:8000/api/liste/version?'.static::$mavenKey.'&mode=TEST&exception=404';
    $httpClient = new MockHttpClient($mockResponse, $uriBase);
    $user='laurent';
    $password='password';

    $httpClient->request('GET', $uriBase,
      [ 'headers' => [ 'Accept' => '*/*',
        'Content-Type' => static::$strContentType,
        'auth_basic' => [$user, $password] ],
      ]);

    $this->assertEquals('GET', $mockResponse->getRequestMethod());
    $this->assertEquals($uriBase, $mockResponse->getRequestUrl());
    $header=$mockResponse->getRequestOptions()['headers'];
    $this->assertEquals('Accept: */*', $header[0]);
    $this->assertEquals('Content-Type: application/json', $header[1]);
    $this->assertEquals('auth_basic: laurent', $header[2]);
    $this->assertEquals('auth_basic: password', $header[3]);
  }

  /**
   * [Description for testSuivi200]
   * On appel le controller qui génére la vue en mode TEST
   * Retour HTTP 200
   * @return void
   *
   * Created at: 18/02/2023, 21:12:51 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */

  public function testSuivi200(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/suivi?'.static::$mavenKey.'&mode=TEST');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals(12,count($decode));
    $this->assertEquals('200', $decode[0]);
    $this->assertArrayHasKey('nom', $decode);
    $this->assertArrayHasKey('mavenKey', $decode);
    $this->assertArrayHasKey('data1', $decode);
    $this->assertArrayHasKey('data2', $decode);
    $this->assertArrayHasKey('data3', $decode);
    $this->assertArrayHasKey('labels', $decode);
    $this->assertEquals(17,count($decode['suivi'][0]));
    $this->assertEquals(5,count($decode['severite'][0]));
    $this->assertEquals(18,count($decode['details'][0]));

    $this->assertArrayHasKey('nom', $decode['suivi'][0]);
    $this->assertArrayHasKey('date', $decode['suivi'][0]);
    $this->assertArrayHasKey('version', $decode['suivi'][0]);
    $this->assertArrayHasKey('suppress_warning', $decode['suivi'][0]);
    $this->assertArrayHasKey('no_sonar', $decode['suivi'][0]);
    $this->assertArrayHasKey('bug', $decode['suivi'][0]);
    $this->assertArrayHasKey('faille', $decode['suivi'][0]);
    $this->assertArrayHasKey('mauvaise_pratique', $decode['suivi'][0]);
    $this->assertArrayHasKey('nombre_hotspot', $decode['suivi'][0]);
    $this->assertArrayHasKey('presentation', $decode['suivi'][0]);
    $this->assertArrayHasKey('metier', $decode['suivi'][0]);
    $this->assertArrayHasKey('autre', $decode['suivi'][0]);
    $this->assertArrayHasKey('fiabilite', $decode['suivi'][0]);
    $this->assertArrayHasKey('securite', $decode['suivi'][0]);
    $this->assertArrayHasKey('note_hotspot', $decode['suivi'][0]);
    $this->assertArrayHasKey('maintenabilite', $decode['suivi'][0]);
    $this->assertArrayHasKey('initial', $decode['suivi'][0]);

    $this->assertArrayHasKey('date', $decode['severite'][0]);
    $this->assertArrayHasKey('bloquant', $decode['severite'][0]);
    $this->assertArrayHasKey('critique', $decode['severite'][0]);
    $this->assertArrayHasKey('majeur', $decode['severite'][0]);
    $this->assertArrayHasKey('mineur', $decode['severite'][0]);

    $this->assertArrayHasKey('date', $decode['details'][0]);
    $this->assertArrayHasKey('version', $decode['details'][0]);
    $this->assertArrayHasKey('bug_blocker', $decode['details'][0]);
    $this->assertArrayHasKey('bug_critical', $decode['details'][0]);
    $this->assertArrayHasKey('bug_major', $decode['details'][0]);
    $this->assertArrayHasKey('bug_minor', $decode['details'][0]);
    $this->assertArrayHasKey('bug_info', $decode['details'][0]);
    $this->assertArrayHasKey('vulnerability_blocker', $decode['details'][0]);
    $this->assertArrayHasKey('vulnerability_critical', $decode['details'][0]);
    $this->assertArrayHasKey('vulnerability_major', $decode['details'][0]);
    $this->assertArrayHasKey('vulnerability_minor', $decode['details'][0]);
    $this->assertArrayHasKey('vulnerability_info', $decode['details'][0]);
    $this->assertArrayHasKey('code_smell_blocker', $decode['details'][0]);
    $this->assertArrayHasKey('code_smell_critical', $decode['details'][0]);
    $this->assertArrayHasKey('code_smell_major', $decode['details'][0]);
    $this->assertArrayHasKey('code_smell_minor', $decode['details'][0]);
    $this->assertArrayHasKey('code_smell_info', $decode['details'][0]);
    $this->assertArrayHasKey('initial', $decode['details'][0]);
  }

  /**
   * [Description for testApiListeVersion400()]
   * On appel le controller qui génére la vue en mode TEST
   * Retour HTTP 400
   * @return void
   *
   * Created at: 18/02/2023, 20:18:34 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiListeVersion400(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/liste/version?mode=TEST');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('400', $decode[0]);
    $this->assertEquals('TEST', $decode['mode']);
        $this->assertEquals('La clé maven est vide!', $decode['message']);
  }


  /**
   * [Description for testApiListeVersionSuivi200]
   * On appel le controller qui génére la vue en mode TEST
   * Retour HTTP 200
   * @return void
   *
   * Created at: 18/02/2023, 21:23:54 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiListeVersion200(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/liste/version?'.static::$mavenKey.'&mode=TEST');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals(4,count($decode));
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals('TEST', $decode['mode']);

    $this->assertArrayHasKey('liste', $decode);
    $this->assertArrayHasKey('versions', $decode);
    if (!$decode['versions']) {
      $this->assertNull($versions);
    }
  }


  /**
   * [Description for testApiGetVersion400]
   * On appel le controller qui génére la vue en mode TEST
   * Retour HTTP 400
   * @return void
   *
   * Created at: 18/02/2023, 21:51:42 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiGetVersion400(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $data=['mavenKey'=>'TEST', 'mode'=>'TEST'];
    $client->request('POST', '/api/get/version', ['json'=>$data] );
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('400', $decode[0]);
  }

  /**
   * [Description for testApiGtVersion200]
   * On appel le controller qui génére la vue en mode TEST
   * Retour HTTP 200
   * @return void
   *
    * Created at: 18/02/2023, 21:43:21 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiGetVersion200(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);
    $data=['mavenKey'=>static::$mavenKey, 'mode'=>'TEST'];
    $client->request('POST', '/api/get/version', ['json'=>$data] );

    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals(200, $decode[0]);
    $this->assertEquals(200,$decode['message']);
    $this->assertNotEquals(404,$decode['message']);
    $this->assertEquals(3, $decode['noteReliability']);
    $this->assertEquals(1,$decode['noteSecurity']);
    $this->assertEquals(3,$decode['noteSqale']);
    $this->assertEquals(5,$decode['noteHotspotsReview']);
    $this->assertNotEquals(6,$decode['noteHotspotsReview']);
    $this->assertEquals(43,$decode['bug']);
    $this->assertEquals(0,$decode['vulnerabilities']);
    $this->assertEquals(3080,$decode['codeSmell']);
    $this->assertEquals(2,$decode['hotspotsReview']);
    $this->assertNotEquals(-1,$decode['hotspotsReview']);
    $this->assertEquals(20984,$decode['lines']);
    $this->assertEquals(17312,$decode['ncloc']);
    $this->assertEquals(2.6,$decode['duplication']);
    $this->assertEquals(50,$decode['coverage']);
    $this->assertNotEquals(0,$decode['coverage']);
    $this->assertEquals(134,$decode['tests']);
    $this->assertNotEquals(0,$decode['tests']);
    $this->assertEquals(15596,$decode['dette']);
  }

  /**
   * [Description for testMockApiSuiviVersionListe200]
   *  Mock qui sert a rien !!!! en tout cas pour le coverage.
   * @return void
   *
   * Created at: 19/02/2023, 15:07:21 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testMockApiSuiviVersionListe200(): void
  {
    $mockResponseJson=[];
    $mockResponse = new MockResponse($mockResponseJson, [
        'http_code' => 200,
        'code'=>'OK',
        'mode'=>'TEST',
        'mavenKey'=>static::$mavenKey,
        'response_headers' => ['Content-Type: application/json'],
    ]);
    $uriBase='http://localhost:8000/api/suivi/version/liste';
    $httpClient = new MockHttpClient($mockResponse, $uriBase);
    $user='laurent';
    $password='password';
    $requestData=['mavenKey'=>static::$mavenkey, 'mode'=>"TEST"];
    $requestJson = json_encode($requestData, JSON_THROW_ON_ERROR);
    $response=$httpClient->request('POST', $uriBase,
      [ 'headers' => [ 'Accept' => '*/*',
        'Content-Type' => static::$strContentType,
        'auth_basic' => [$user, $password]],'body'=> $requestJson
      ]);

    $this->assertEquals('POST', $mockResponse->getRequestMethod());
    $this->assertEquals($uriBase, $mockResponse->getRequestUrl());
    $header=$mockResponse->getRequestOptions()['headers'];
    $this->assertEquals('Accept: */*', $header[0]);
    $this->assertEquals('Content-Type: application/json', $header[1]);
    $this->assertEquals('auth_basic: laurent', $header[2]);
    $this->assertEquals('auth_basic: password', $header[3]);
    $info=$mockResponse->getInfo();
    $this->assertEquals('OK', $info['code']);
    $this->assertEquals('TEST', $info['mode']);
    $this->assertEquals(static::$mavenKey, $info['mavenKey']);
    $this->assertEquals('200', $info['http_code']);
    $this->assertNull( $info['error']);
  }

  /**
   * [Description for testApiSuiviVersionListe400]
   *  On test une erreur 400 en mode Test avec une clé maven bidon.
   * @return void
   *
   * Created at: 19/02/2023, 15:18:52 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiSuiviVersionListe400(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $data=['mavenKey'=>'null', 'mode'=>'TEST'];
    $client->request('POST', '/api/suivi/version/liste', ['json'=>$data] );
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('400', $decode[0]);
    $info=$client->getResponse()->getInfo();
    $this->assertNull($info['error']);
    $this->assertEquals('POST', $info['http_method']);
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertEquals('null', $decode['mavenKey']);
  }

  /**
   * [Description for testApiSuiviVersionListe200]
   * On récupère la liste des versions
   * @return void
   *
   * Created at: 19/02/2023, 15:36:29 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiSuiviVersionListe200(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $data=['mavenKey'=>static::$mavenkey, 'mode'=>'TEST'];
    $client->request('POST', '/api/suivi/version/liste', ['json'=>$data] );
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $info=$client->getResponse()->getInfo();
    $this->assertNull($info['error']);
    $this->assertEquals('POST', $info['http_method']);
    $this->assertIsArray($decode);
    $this->assertEquals(3,count($decode));
    $this->assertEquals('OK', $decode['code']);
    $this->assertEquals(5,count($decode['versions'][0]));
    $this->assertArrayHasKey('maven_key', $decode['versions'][0]);
    $this->assertArrayHasKey('version', $decode['versions'][0]);
    $this->assertArrayHasKey('date', $decode['versions'][0]);
    $this->assertArrayHasKey('favori', $decode['versions'][0]);
    $this->assertArrayHasKey('initial', $decode['versions'][0]);
  }

  /**
   * [Description for testApiSuiviVersionListe400]
   * On fait PUT pour un DELETE. (i.e on bloque la methode DELETE)
   * @return void
   *
   * Created at: 19/02/2023, 15:38:03 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiSuiviVersionPoubelle400(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $data=['mavenKey'=>'null', 'mode'=>'TEST', 'version'=>'1.6.0-RELEASE', 'date'=>'2022-11-30 00:00:00'];
    $client->request('PUT', '/api/suivi/version/poubelle', ['json'=>$data] );
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('400', $decode[0]);
    $info=$client->getResponse()->getInfo();
    $this->assertNull($info['error']);
    $this->assertEquals('PUT', $info['http_method']);
    $this->assertNotEquals('DELETE', $info['http_method']);
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertEquals('null', $decode['mavenKey']);
  }

  public function testApiSuiviVersionPoubelle200(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $data=['mavenKey'=>'TEST', 'mode'=>'TEST', 'version'=>'1.6.0-RELEASE', 'date'=>'2022-11-30 00:00:00'];
    $client->request('PUT', '/api/suivi/version/poubelle', ['json'=>$data] );
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $info=$client->getResponse()->getInfo();
    $this->assertNull($info['error']);
    $this->assertEquals('PUT', $info['http_method']);
    $this->assertNotEquals('DELETE', $info['http_method']);
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertEquals('OK', $decode['code']);
  }
}
