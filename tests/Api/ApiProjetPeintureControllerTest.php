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

class ApiProjetPeintureControllerTest extends ApiTestCase
{
  private static $userTest='admin@ma-moulinette.fr';
  private static $mavenKey='mavenKey=fr.ma-moulinette:ma-moulinette';
  private static $applicationJson='application/json';
  public static $strContentType = 'application/json';

  /**
   * [Description for testProjet200()]
   * On appel le controller qui génére la vue en mode TEST
   * Retour HTTP 200
   * @return void
   *
   * Created at: 26/02/2023, 21:17:47 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetMesApplicationsListe(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/projet/mes-applications/liste');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(4,count($decode));
    $this->assertArrayHasKey('code', $decode);
    $this->assertArrayHasKey('liste', $decode);
    $this->assertArrayHasKey('favori', $decode);
    $this->assertNotEquals(406, $decode['code']);
    $this->assertEquals(200, $decode['code']);
    $this->assertIsArray($decode['liste']);
    $this->assertIsArray($decode['favori']);
    $this->assertEquals("ma-moulinette", $decode['liste'][0]['name']);
    $this->assertIsString($decode['liste'][0]['name']);
    $this->assertEquals("fr.ma-moulinette:ma-moulinette", $decode['liste'][0]['key']);
    $this->assertIsString($decode['liste'][0]['key']);
    $this->assertEquals("fr.ma-moulinette:ma-moulinette", $decode['favori'][0]['key']);
    $this->assertIsString($decode['favori'][0]['key']);
  }

  public function testApiProjetMesApplicationsDelete400(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/projet/mes-applications/delete?mavenKey=null&mode=TEST');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('400', $decode[0]);
    $this->assertEquals(4,count($decode));
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('message', $decode);
    $this->assertEquals('La clé maven est vide!', $decode['message']);
  }

  /**
   * [Description for testApiProjetMesApplicationsDelete]
   *
   * @return void
   *
   * Created at: 26/02/2023, 21:57:02 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetMesApplicationsDelete(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/projet/mes-applications/delete?mode=TEST');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(3,count($decode));
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('code', $decode);
    $this->assertEquals(200, $decode['code']);
    $this->assertEquals("TEST", $decode['mode']);
  }

  /**
   * [Description for testApiPeintureProjetMesversion406]
   *
   * @return void
   *
   * Created at: 26/02/2023, 22:02:27 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiPeintureProjetVersion406(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/peinture/projet/version?mavenKey=null');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('406', $decode[0]);
    $this->assertEquals(2,count($decode));
    $this->assertArrayHasKey('message', $decode);
    $this->assertStringEndsWith('Vous devez lancer une analyse pour ce projet !!!', $decode['message']);
  }

  /**
   * [Description for testApiPeintureProjetVersion]
   *
   * @return void
   *
   * Created at: 26/02/2023, 22:18:31 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiPeintureProjetVersion(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/peinture/projet/version?'.static::$mavenKey);
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(8,count($decode));
    $this->assertArrayHasKey('release', $decode);
    $this->assertArrayHasKey('snapshot', $decode);
    $this->assertArrayHasKey('autre', $decode);
    $this->assertArrayHasKey('label', $decode);
    $this->assertArrayHasKey('dataset', $decode);
    $this->assertArrayHasKey('projet', $decode);
    $this->assertArrayHasKey('date', $decode);
    $this->assertEquals(8, $decode['release']);
    $this->assertIsInt($decode['release']);
    $this->assertEquals(0, $decode['snapshot']);
    $this->assertIsInt($decode['snapshot']);
    $this->assertEquals(0, $decode['autre']);
    $this->assertIsInt($decode['autre']);
    $this->assertEquals('RELEASE', $decode['label'][0]);
    $this->assertIsString($decode['label'][0]);
    $this->assertEquals(8, $decode['dataset'][0]);
    $this->assertIsInt($decode['dataset'][0]);
    $this->assertEquals("1.6.0-RELEASE", $decode['projet']);
    $this->assertIsString($decode['projet']);
    $this->assertEquals("2022-11-30 00:00:00", $decode['date']);
    $this->assertIsString($decode['date']);
  }

  /**
   * [Description for testApiPeintureProjetInformation406]
   *
   * @return void
   *
   * Created at: 26/02/2023, 22:19:58 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiPeintureProjetInformation406(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/peinture/projet/information?mavenKey=null');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('406', $decode[0]);
    $this->assertEquals(2,count($decode));
    $this->assertArrayHasKey('message', $decode);
    $this->assertStringEndsWith('Vous devez lancer une analyse pour ce projet !!!', $decode['message']);
  }

  /**
   * [Description for testApiPeintureProjetInformation]
   *
   * @return void
   *
   * Created at: 26/02/2023, 22:20:37 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiPeintureProjetInformation(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/peinture/projet/information?'.static::$mavenKey);
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(8,count($decode));
    $this->assertArrayHasKey('name', $decode);
    $this->assertArrayHasKey('ncloc', $decode);
    $this->assertArrayHasKey('lines', $decode);
    $this->assertArrayHasKey('coverage', $decode);
    $this->assertArrayHasKey('duplication', $decode);
    $this->assertArrayHasKey('tests', $decode);
    $this->assertArrayHasKey('issues', $decode);
    $this->assertEquals("Ma-Moulinette", $decode['name']);
    $this->assertIsString($decode['name']);
    $this->assertEquals(17301, $decode['ncloc']);
    $this->assertIsInt($decode['ncloc']);
    $this->assertEquals(24471, $decode['lines']);
    $this->assertIsInt($decode['lines']);
    $this->assertEquals(0, $decode['coverage']);
    $this->assertIsInt($decode['coverage']);
    $this->assertEquals(5.1, $decode['duplication']);
    $this->assertIsFloat($decode['duplication']);
    $this->assertEquals(0, $decode['tests']);
    $this->assertIsInt($decode['tests']);
    $this->assertEquals(1956, $decode['issues']);
    $this->assertIsInt($decode['issues']);
  }

  /**
   * [Description for testApiPeintureProjetAnomalie406]
   *
   * @return void
   *
   * Created at: 26/02/2023, 22:29:36 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiPeintureProjetAnomalie406(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/peinture/projet/anomalie?mavenKey=null');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('406', $decode[0]);
    $this->assertEquals(2,count($decode));
    $this->assertArrayHasKey('message', $decode);
    $this->assertStringEndsWith('Vous devez lancer une analyse pour ce projet !!!', $decode['message']);
  }

  /**
   * [Description for testApiPeintureProjetAnomalie]
   *
   * @return void
   *
   * Created at: 26/02/2023, 22:30:51 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiPeintureProjetAnomalie(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/peinture/projet/anomalie?'.static::$mavenKey);
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(23,count($decode));
    $this->assertArrayHasKey('dette', $decode);
    $this->assertArrayHasKey('detteReliability', $decode);
    $this->assertArrayHasKey('detteVulnerability', $decode);
    $this->assertArrayHasKey('detteCodeSmell', $decode);
    $this->assertArrayHasKey('detteReliabilityMinute', $decode);
    $this->assertArrayHasKey('detteVulnerabilityMinute', $decode);
    $this->assertArrayHasKey('detteCodeSmellMinute', $decode);
    $this->assertArrayHasKey('bug', $decode);
    $this->assertArrayHasKey('vulnerability', $decode);
    $this->assertArrayHasKey('codeSmell', $decode);
    $this->assertArrayHasKey('blocker', $decode);
    $this->assertArrayHasKey('critical', $decode);
    $this->assertArrayHasKey('info', $decode);
    $this->assertArrayHasKey('major', $decode);
    $this->assertArrayHasKey('minor', $decode);
    $this->assertArrayHasKey('frontend', $decode);
    $this->assertArrayHasKey('backend', $decode);
    $this->assertArrayHasKey('autre', $decode);
    $this->assertArrayHasKey('noteReliability', $decode);
    $this->assertArrayHasKey('noteSecurity', $decode);
    $this->assertArrayHasKey('noteSqale', $decode);
    $this->assertEquals("13d, 14h:26min", $decode['dette']);
    $this->assertIsString($decode['dette']);
    $this->assertEquals("1h:25min", $decode['detteReliability']);
    $this->assertIsString($decode['detteReliability']);
    $this->assertEquals("0h:0min", $decode['detteVulnerability']);
    $this->assertIsString($decode['detteVulnerability']);
    $this->assertEquals("13d, 13h:1min", $decode['detteCodeSmell']);
    $this->assertIsString($decode['detteCodeSmell']);
    $this->assertEquals(19586, $decode['detteMinute']);
    $this->assertIsInt($decode['detteMinute']);
    $this->assertEquals(85, $decode['detteReliabilityMinute']);
    $this->assertIsInt($decode['detteReliabilityMinute']);
    $this->assertEquals(0, $decode['detteVulnerabilityMinute']);
    $this->assertIsInt($decode['detteVulnerabilityMinute']);
    $this->assertEquals(19501, $decode['detteCodeSmellMinute']);
    $this->assertIsInt($decode['detteCodeSmellMinute']);
    $this->assertEquals(61, $decode['bug']);
    $this->assertIsInt($decode['bug']);
    $this->assertEquals(0, $decode['vulnerability']);
    $this->assertIsInt($decode['vulnerability']);
    $this->assertEquals(1895, $decode['codeSmell']);
    $this->assertIsInt($decode['codeSmell']);
    $this->assertEquals(17, $decode['blocker']);
    $this->assertIsInt($decode['blocker']);
    $this->assertEquals(133, $decode['critical']);
    $this->assertIsInt($decode['critical']);
    $this->assertEquals(2, $decode['info']);
    $this->assertIsInt($decode['info']);
    $this->assertEquals(1118, $decode['major']);
    $this->assertIsInt($decode['major']);
    $this->assertEquals(686, $decode['minor']);
    $this->assertIsInt($decode['minor']);
    $this->assertEquals(1945, $decode['frontend']);
    $this->assertIsInt($decode['frontend']);
    $this->assertEquals(0, $decode['backend']);
    $this->assertIsInt($decode['backend']);
    $this->assertEquals(0, $decode['autre']);
    $this->assertIsInt($decode['autre']);
    $this->assertEquals(3, $decode['noteReliability']);
    $this->assertIsInt($decode['noteReliability']);
    $this->assertEquals(1, $decode['noteSecurity']);
    $this->assertIsInt($decode['noteSecurity']);
    $this->assertEquals(3, $decode['noteSqale']);
    $this->assertIsInt($decode['noteSqale']);
  }

  /**
   * [Description for testApiPeintureProjetAnomalieDetails406]
   *
   * @return void
   *
   * Created at: 26/02/2023, 22:52:56 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiPeintureProjetAnomalieDetails406(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/peinture/projet/anomalie/details?mavenKey=null');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('406', $decode[0]);
    $this->assertEquals(2,count($decode));
    $this->assertArrayHasKey('message', $decode);
    $this->assertStringEndsWith('Vous devez lancer une analyse pour ce projet !!!', $decode['message']);
  }

  /**
   * [Description for testApiPeintureProjetAnomalieDetails]
   *
   * @return void
   *
   * Created at: 26/02/2023, 23:18:18 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiPeintureProjetAnomalieDetails(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/peinture/projet/anomalie/details?'.static::$mavenKey);
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(17,count($decode));
    $this->assertArrayHasKey('message', $decode);
    $this->assertArrayHasKey('bugBlocker', $decode);
    $this->assertArrayHasKey('bugCritical', $decode);
    $this->assertArrayHasKey('bugMajor', $decode);
    $this->assertArrayHasKey('bugMinor', $decode);
    $this->assertArrayHasKey('bugInfo', $decode);
    $this->assertArrayHasKey('vulnerabilityBlocker', $decode);
    $this->assertArrayHasKey('vulnerabilityCritical', $decode);
    $this->assertArrayHasKey('vulnerabilityMajor', $decode);
    $this->assertArrayHasKey('vulnerabilityMinor', $decode);
    $this->assertArrayHasKey('vulnerabilityInfo', $decode);
    $this->assertArrayHasKey('codeSmellBlocker', $decode);
    $this->assertArrayHasKey('codeSmellCritical', $decode);
    $this->assertArrayHasKey('codeSmellMajor', $decode);
    $this->assertArrayHasKey('codeSmellMinor', $decode);
    $this->assertArrayHasKey('codeSmellInfo', $decode);
    $this->assertEquals(200, $decode['message']);
    $this->assertIsInt($decode['message']);
    $this->assertEquals(0, $decode['bugBlocker']);
    $this->assertIsInt($decode['bugBlocker']);
    $this->assertEquals(0, $decode['bugCritical']);
    $this->assertIsInt($decode['bugCritical']);
    $this->assertEquals(31, $decode['bugMajor']);
    $this->assertIsInt($decode['bugMajor']);
    $this->assertEquals(30, $decode['bugMinor']);
    $this->assertIsInt($decode['bugMinor']);
    $this->assertEquals(0, $decode['bugInfo']);
    $this->assertIsInt($decode['bugInfo']);
    $this->assertIsInt($decode['vulnerabilityBlocker']);
    $this->assertEquals(0, $decode['bugCritical']);
    $this->assertIsInt($decode['vulnerabilityCritical']);
    $this->assertEquals(0, $decode['vulnerabilityMajor']);
    $this->assertIsInt($decode['vulnerabilityMajor']);
    $this->assertEquals(30, $decode['bugMinor']);
    $this->assertIsInt($decode['vulnerabilityMinor']);
    $this->assertEquals(0, $decode['vulnerabilityInfo']);
    $this->assertIsInt($decode['vulnerabilityInfo']);
    $this->assertEquals(17, $decode['codeSmellBlocker']);
    $this->assertIsInt($decode['codeSmellBlocker']);
    $this->assertEquals(133, $decode['codeSmellCritical']);
    $this->assertIsInt($decode['codeSmellCritical']);
    $this->assertEquals(1087, $decode['codeSmellMajor']);
    $this->assertIsInt($decode['codeSmellMajor']);
    $this->assertEquals(656, $decode['codeSmellMinor']);
    $this->assertIsInt($decode['codeSmellMinor']);
    $this->assertEquals(2, $decode['codeSmellInfo']);
    $this->assertIsInt($decode['codeSmellInfo']);
  }

  /**
   * [Description for testApiPeintureProjetHotspot406]
   *
   * @return void
   *
   * Created at: 26/02/2023, 23:18:59 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiPeintureProjetHotspot406(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/peinture/projet/hotspots?mavenKey=null');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('406', $decode[0]);
    $this->assertEquals(2,count($decode));
    $this->assertArrayHasKey('message', $decode);
    $this->assertStringEndsWith('Vous devez lancer une analyse pour ce projet !!!', $decode['message']);
  }

  /**
   * [Description for testApiPeintureProjetHotspot]
   *
   * @return void
   *
   * Created at: 26/02/2023, 23:24:03 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiPeintureProjetHotspot(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/peinture/projet/hotspots?'.static::$mavenKey);
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(2,count($decode));
    $this->assertArrayHasKey('note', $decode);
    $this->assertEquals("E", $decode['note']);
    $this->assertIsString($decode['note']);
  }

  /**
   * [Description for testApiPeintureProjetHotspotDetails406]
   *
   * @return void
   *
   * Created at: 26/02/2023, 23:24:40 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiPeintureProjetHotspotDetails406(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/peinture/projet/hotspot/details?mavenKey=null');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('406', $decode[0]);
    $this->assertEquals(2,count($decode));
    $this->assertArrayHasKey('message', $decode);
    $this->assertStringEndsWith('Vous devez lancer une analyse pour ce projet !!!', $decode['message']);
  }

  /**
   * [Description for testApiPeintureProjetHotspotDetails]
   *
   * @return void
   *
   * Created at: 26/02/2023, 23:29:47 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiPeintureProjetHotspotDetails(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/peinture/projet/hotspot/details?'.static::$mavenKey);
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(6,count($decode));
    $this->assertArrayHasKey('code', $decode);
    $this->assertEquals(200, $decode['code']);
    $this->assertIsInt($decode['code']);
    $this->assertArrayHasKey('total', $decode);
    $this->assertEquals(2, $decode['total']);
    $this->assertIsInt($decode['total']);
    $this->assertArrayHasKey('high', $decode);
    $this->assertEquals(0, $decode['high']);
    $this->assertIsInt($decode['high']);
    $this->assertArrayHasKey('medium', $decode);
    $this->assertEquals(2, $decode['medium']);
    $this->assertIsInt($decode['medium']);
    $this->assertArrayHasKey('low', $decode);
    $this->assertEquals(0, $decode['low']);
    $this->assertIsInt($decode['low']);
  }

  /**
   * [Description for testApiPeintureProjetNosonarDetails400]
   *
   * @return void
   *
   * Created at: 26/02/2023, 23:30:35 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiPeintureProjetNosonarDetails400(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/peinture/projet/nosonar/details?mavenKey=null&mode=TEST');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('400', $decode[0]);
    $this->assertEquals(4,count($decode));
    $this->assertArrayHasKey('message', $decode);
    $this->assertEquals('La clé maven est vide!', $decode['message']);
    $this->assertArrayHasKey('mode', $decode);
    $this->assertEquals('TEST', $decode['mode']);
  }

  public function testApiPeintureProjetNosonarDetails(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/peinture/projet/nosonar/details?'.static::$mavenKey);
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(4,count($decode));
    $this->assertArrayHasKey('total', $decode);
    $this->assertEquals(0, $decode['total']);
    $this->assertIsInt($decode['total']);
    $this->assertArrayHasKey('s1309', $decode);
    $this->assertEquals(0, $decode['s1309']);
    $this->assertIsInt($decode['s1309']);
    $this->assertArrayHasKey('nosonar', $decode);
    $this->assertEquals(0, $decode['nosonar']);
    $this->assertIsInt($decode['nosonar']);
  }
}
