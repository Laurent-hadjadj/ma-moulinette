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

class ApiProjetControllerTest extends ApiTestCase
{
  private static $userTest='admin@ma-moulinette.fr';
  private static $mavenKey='mavenKey=fr.ma-moulinette:ma-moulinette';
  private static $applicationJson='application/json';
  public static $strContentType = 'application/json';

  /**
   * [Description for testApiFavori400()]
   * On appel le controller qui génére la vue en mode TEST
   * Retour HTTP 200
   * @return void
   *
   * Created at: 20/02/2023, 16:08:21 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiFavori400(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/favori?mode=TEST&mavenKey=null&statut=0');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('400', $decode[0]);
    $this->assertEquals(5,count($decode));
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertEquals('null', $decode['mavenKey']);
    $this->assertEquals(0, $decode['statut']);
    $this->assertEquals("La clé maven est vide!", $decode['message']);
    $this->assertArrayHasKey('message', $decode);
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('statut', $decode);
    $this->assertArrayHasKey('mavenKey', $decode);
  }

  /**
   * [Description for testApiFavori200]
   * Tests de l'APIProjet
   * @return void
   *
   * Created at: 20/02/2023, 16:30:28 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiFavori200(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/favori?mode=TEST&'.static::$mavenKey.'&statut=0');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(3,count($decode));
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertEquals(0, $decode['statut']);
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('statut', $decode);
  }

  /**
   * [Description for testApiFavoriCheck]
   * Tests le retour de favori et de statut
   * @return void
   *
   * Created at: 20/02/2023, 16:47:58 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiFavoriCheck(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/favori/check?'.static::$mavenKey);
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(3,count($decode));
    $this->assertArrayHasKey('favori', $decode);
    $this->assertArrayHasKey('statut', $decode);
  }

  /**
   * [Description for testApiListeProjet]
   *
   * @return void
   *
   * Created at: 20/02/2023, 16:49:55 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiListeProjet(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/liste/projet?'.static::$mavenKey);
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(2, count($decode));
    $this->assertArrayHasKey('liste', $decode);
    $this->assertArrayHasKey('id', $decode['liste'][0]);
    $this->assertArrayHasKey('text', $decode['liste'][0]);
  }

  /**
   * [Description for testApiProjetAnalyses]
   *
   * @return void
   *
   * Created at: 20/02/2023, 17:08:21 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetAnalyses(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/projet/analyses?'.static::$mavenKey.'&mode=TEST');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(3, count($decode));
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('nombreVersion', $decode);
    $this->assertGreaterThanOrEqual(1, $decode['nombreVersion']);
  }

  /**
   * [Description for testApiProjetMesures]
   *
   * @return void
   *
   * Created at: 24/02/2023, 13:40:35 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetMesures(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/projet/mesures?'.static::$mavenKey.'&mode=TEST');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(3, count($decode));
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('mesures', $decode);
    $this->assertEquals(5, count($decode['mesures']));
    $this->assertGreaterThanOrEqual(0, $decode['mesures']['coverage']);
    $this->assertGreaterThanOrEqual(0, $decode['mesures']['duplicationDensity']);
    $this->assertGreaterThanOrEqual(0, $decode['mesures']['tests']);
    $this->assertGreaterThanOrEqual(0, $decode['mesures']['issues']);
    $this->assertGreaterThanOrEqual(0, $decode['mesures']['ncloc']);
  }

  /**
   * [Description for testApiProjetAnomalie400]
   *
   * @return void
   *
   * Created at: 24/02/2023, 13:50:51 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetAnomalie400(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/projet/anomalie?mavenKey=null&mode=TEST');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('400', $decode[0]);
    $this->assertEquals(4,count($decode));
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertEquals('null', $decode['mavenKey']);
    $this->assertEquals("La clé maven est vide!", $decode['message']);
    $this->assertArrayHasKey('message', $decode);
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('mavenKey', $decode);
  }

  /**
   * [Description for testApiProjetAnomalie200]
   *
   * @return void
   *
   * Created at: 24/02/2023, 13:57:26 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetAnomalie200(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/projet/anomalie?'.static::$mavenKey.'&mode=TEST');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(3,count($decode));
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('info', $decode);
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertStringStartsWith("Enregistrement des défauts", $decode['info']);
  }

  /**
   * [Description for testApiProjetAnomalieDetails400]
   *
   * @return void
   *
   * Created at: 24/02/2023, 18:42:08 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetAnomalieDetails400(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/projet/anomalie/details?mavenKey=null&mode=TEST');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('400', $decode[0]);
    $this->assertEquals(4,count($decode));
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertEquals('null', $decode['mavenKey']);
    $this->assertEquals("La clé maven est vide!", $decode['message']);
    $this->assertArrayHasKey('message', $decode);
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('mavenKey', $decode);
  }

  /**
   * [Description for testApiProjetAnomalieDetails200]
   *
   * @return void
   *
   * Created at: 25/02/2023, 23:32:40 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetAnomalieDetails200(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/projet/anomalie/details?'.static::$mavenKey.'&mode=TEST');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(3,count($decode));
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('code', $decode);
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertEquals('OK', $decode['code']);
  }

  /**
   * [Description for testApiProjetHistoriqueNote400]
   *
   * @return void
   *
   * Created at: 25/02/2023, 23:32:28 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetHistoriqueNote400(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/projet/historique/note?mavenKey=null&mode=TEST');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('400', $decode[0]);
    $this->assertEquals(4,count($decode));
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertEquals('null', $decode['mavenKey']);
    $this->assertEquals("La clé maven est vide!", $decode['message']);
    $this->assertArrayHasKey('message', $decode);
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('mavenKey', $decode);
  }

  /**
   * [Description for testApiProjetHistoriqueNoteReliabilityRating200]
   *
   * @return void
   *
   * Created at: 25/02/2023, 23:33:01 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetHistoriqueNoteReliabilityRating200(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/projet/historique/note?'.static::$mavenKey.'&type=reliability&mode=TEST');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(4,count($decode));
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('nombre', $decode);
    $this->assertArrayHasKey('type', $decode);
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertEquals('Fiabilité', $decode['type']);
    $this->assertEquals(8, $decode['nombre']);
  }

  /**
   * [Description for testApiProjetHistoriqueNoteReliabilityRating200]
   *
   * @return void
   *
   * Created at: 25/02/2023, 23:37:32 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetHistoriqueNoteSecurityRating200(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/projet/historique/note?'.static::$mavenKey.'&type=security&mode=TEST');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(4,count($decode));
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('nombre', $decode);
    $this->assertArrayHasKey('type', $decode);
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertEquals('Sécurité', $decode['type']);
    $this->assertEquals(8, $decode['nombre']);
  }

  /**
   * [Description for testApiProjetHistoriqueNoteSqaleRating200]
   *
   * @return void
   *
   * Created at: 25/02/2023, 23:41:19 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetHistoriqueNoteSqaleRating200(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/projet/historique/note?'.static::$mavenKey.'&type=sqale&mode=TEST');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(4,count($decode));
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('nombre', $decode);
    $this->assertArrayHasKey('type', $decode);
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertEquals('Mauvaises Pratiques', $decode['type']);
    $this->assertEquals(8, $decode['nombre']);
  }

  /**
   * [Description for testApiProjetIssuesOwasp400]
   *
   * @return void
   *
   * Created at: 25/02/2023, 23:52:20 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetIssuesOwasp400(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/projet/issues/owasp?mavenKey=null&mode=TEST');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('400', $decode[0]);
    $this->assertEquals(4,count($decode));
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertEquals('null', $decode['mavenKey']);
    $this->assertEquals("La clé maven est vide!", $decode['message']);
    $this->assertArrayHasKey('message', $decode);
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('mavenKey', $decode);
  }

  /**
   * [Description for testApiProjetIssuesOwasp200]
   *
   * @return void
   *
   * Created at: 26/02/2023, 00:06:06 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetIssuesOwasp200(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/projet/issues/owasp?'.static::$mavenKey.'&mode=TEST');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(3,count($decode));
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('owasp', $decode);
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertEquals(0, $decode['owasp']);
  }

  /**
   * [Description for testApiProjetHotspot400]
   *
   * @return void
   *
   * Created at: 26/02/2023, 00:06:44 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetHotspot400(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/projet/hotspot?mavenKey=null&mode=TEST');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('400', $decode[0]);
    $this->assertEquals(4,count($decode));
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertEquals('null', $decode['mavenKey']);
    $this->assertEquals("La clé maven est vide!", $decode['message']);
    $this->assertArrayHasKey('message', $decode);
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('mavenKey', $decode);
  }

  /**
   * [Description for testApiProjetHotspot200]
   *
   * @return void
   *
   * Created at: 26/02/2023, 00:10:03 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetHotspot200(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/projet/hotspot?'.static::$mavenKey.'&mode=TEST');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(3,count($decode));
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('hotspots', $decode);
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertEquals(2, $decode['hotspots']);
  }

  /**
   * [Description for testApiProjetHotspotOwasp400]
   *
   * @return void
   *
   * Created at: 26/02/2023, 00:11:36 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetHotspotOwasp400(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/projet/hotspot/owasp?mavenKey=null&owasp=null&mode=TEST');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('400', $decode[0]);
    $this->assertEquals(5,count($decode));
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertEquals('null', $decode['mavenKey']);
    $this->assertEquals('null', $decode['owasp']);
    $this->assertEquals("La clé maven est vide!", $decode['message']);
    $this->assertArrayHasKey('message', $decode);
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('mavenKey', $decode);
    $this->assertArrayHasKey('owasp', $decode);
  }

  /**
   * [Description for testApiProjetHotspotOwaspA1]
   * A1
   * @return void
   *
   * Created at: 26/02/2023, 00:22:58 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetHotspotOwaspA1(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/projet/hotspot/owasp?'.static::$mavenKey.'&owasp=a1&mode=TEST');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(4,count($decode));
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('info', $decode);
    $this->assertArrayHasKey('hotspots', $decode);
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertEquals(0, $decode['hotspots']);
    $this->assertEquals("enregistrement", $decode['info']);
  }

    /**
   * [Description for testApiProjetHotspotOwaspA2]
   * A2
   * @return void
   *
   * Created at: 26/02/2023, 00:22:58 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetHotspotOwaspA2(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/projet/hotspot/owasp?'.static::$mavenKey.'&owasp=a2&mode=TEST');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(4,count($decode));
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('info', $decode);
    $this->assertArrayHasKey('hotspots', $decode);
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertEquals(0, $decode['hotspots']);
    $this->assertEquals("enregistrement", $decode['info']);
  }

    /**
   * [Description for testApiProjetHotspotOwaspA3]
   * A3
   * @return void
   *
   * Created at: 26/02/2023, 00:22:58 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetHotspotOwaspA3(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/projet/hotspot/owasp?'.static::$mavenKey.'&owasp=a3&mode=TEST');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(4,count($decode));
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('info', $decode);
    $this->assertArrayHasKey('hotspots', $decode);
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertEquals(2, $decode['hotspots']);
    $this->assertEquals("enregistrement", $decode['info']);
  }

  /**
   * [Description for testApiProjetHotspotOwaspA4]
   * A4
   * @return void
   *
   * Created at: 26/02/2023, 00:24:53 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetHotspotOwaspA4(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/projet/hotspot/owasp?'.static::$mavenKey.'&owasp=a4&mode=TEST');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(4,count($decode));
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('info', $decode);
    $this->assertArrayHasKey('hotspots', $decode);
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertEquals(0, $decode['hotspots']);
    $this->assertEquals("enregistrement", $decode['info']);
  }

  /**
   * [Description for testApiProjetHotspotOwaspA5]
   * A5
   * @return void
   *
   * Created at: 26/02/2023, 00:25:26 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetHotspotOwaspA5(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/projet/hotspot/owasp?'.static::$mavenKey.'&owasp=a5&mode=TEST');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(4,count($decode));
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('info', $decode);
    $this->assertArrayHasKey('hotspots', $decode);
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertEquals(0, $decode['hotspots']);
    $this->assertEquals("enregistrement", $decode['info']);
  }

  /**
   * [Description for testApiProjetHotspotOwaspA6]
   * A6
   * @return void
   *
   * Created at: 26/02/2023, 00:26:21 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetHotspotOwaspA6(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/projet/hotspot/owasp?'.static::$mavenKey.'&owasp=a6&mode=TEST');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(4,count($decode));
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('info', $decode);
    $this->assertArrayHasKey('hotspots', $decode);
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertEquals(0, $decode['hotspots']);
    $this->assertEquals("enregistrement", $decode['info']);
  }

  /**
   * [Description for testApiProjetHotspotOwaspA7]
   * A7
   * @return void
   *
   * Created at: 26/02/2023, 00:26:51 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetHotspotOwaspA7(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/projet/hotspot/owasp?'.static::$mavenKey.'&owasp=a7&mode=TEST');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(4,count($decode));
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('info', $decode);
    $this->assertArrayHasKey('hotspots', $decode);
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertEquals(0, $decode['hotspots']);
    $this->assertEquals("enregistrement", $decode['info']);
  }

  /**
   * [Description for testApiProjetHotspotOwaspA8]
   * A8
   * @return void
   *
   * Created at: 26/02/2023, 00:27:20 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetHotspotOwaspA8(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/projet/hotspot/owasp?'.static::$mavenKey.'&owasp=a8&mode=TEST');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(4,count($decode));
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('info', $decode);
    $this->assertArrayHasKey('hotspots', $decode);
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertEquals(0, $decode['hotspots']);
    $this->assertEquals("enregistrement", $decode['info']);
  }

  /**
   * [Description for testApiProjetHotspotOwaspA9]
   * A9
   * @return void
   *
   * Created at: 26/02/2023, 00:27:51 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetHotspotOwaspA9(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/projet/hotspot/owasp?'.static::$mavenKey.'&owasp=a9&mode=TEST');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(4,count($decode));
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('info', $decode);
    $this->assertArrayHasKey('hotspots', $decode);
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertEquals(0, $decode['hotspots']);
    $this->assertEquals("enregistrement", $decode['info']);
  }

  /**
   * [Description for testApiProjetHotspotOwaspA10]
   * A10
   * @return void
   *
   * Created at: 26/02/2023, 00:28:18 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetHotspotOwaspA10(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/projet/hotspot/owasp?'.static::$mavenKey.'&owasp=a10&mode=TEST');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(4,count($decode));
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('info', $decode);
    $this->assertArrayHasKey('hotspots', $decode);
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertEquals(0, $decode['hotspots']);
    $this->assertEquals("enregistrement", $decode['info']);
  }

  /**
   * [Description for testApiProjetHotspotDetails400]
   *
   * @return void
   * 
   * Created at: 26/02/2023, 00:41:49 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com> 
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0. 
   */
  public function testApiProjetHotspotDetails400(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/projet/hotspot/details?mavenKey=null&mode=TEST');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('400', $decode[0]);
    $this->assertEquals(4,count($decode));
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertEquals('null', $decode['mavenKey']);
    $this->assertEquals("La clé maven est vide!", $decode['message']);
    $this->assertArrayHasKey('message', $decode);
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('mavenKey', $decode);
  }

  /**
   * [Description for testApiProjetHotspotDetails]
   *
   * @return void
   *
   * Created at: 26/02/2023, 00:38:10 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetHotspotDetails(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/projet/hotspot/details?'.static::$mavenKey.'&mode=TEST');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(3,count($decode));
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('ligne', $decode);
    $this->assertArrayNotHasKey('code', $decode);
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertEquals(2, $decode['ligne']);
  }

  public function testApiProjetNosonarDetails400(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/projet/nosonar/details?mavenKey=null&mode=TEST');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('400', $decode[0]);
    $this->assertEquals(4,count($decode));
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertEquals('null', $decode['mavenKey']);
    $this->assertEquals("La clé maven est vide!", $decode['message']);
    $this->assertArrayHasKey('message', $decode);
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('mavenKey', $decode);
  }

  /**
   * [Description for testApiProjetNosonarDetails]
   *
   * @return void
   * 
   * Created at: 26/02/2023, 00:49:51 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com> 
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0. 
   */
  public function testApiProjetNosonarDetails(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/projet/nosonar/details?'.static::$mavenKey.'&mode=TEST');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(3,count($decode));
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('nosonar', $decode);
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertEquals(0, $decode['nosonar']);
  }

  /**
   * [Description for testApiEnregistrement]
   * On test un code 19 (violation d'unicité)
   * @return void
   *
   * Created at: 26/02/2023, 20:10:45 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiEnregistrement(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);
    $data=
      ['autre'=>"0",  'backend'=>"0", 'bugBlocker'=>"0", 'bugCritical'=> "0",
      'bugInfo'=>"0", 'bugMajor'=>"31", 'bugMinor'=>"30", 'codeSmellBlocker'=>"17",
      'codeSmellCritical'=>"133", 'codeSmellInfo'=>"2", 'codeSmellMajor'=>"1087",
      'codeSmellMinor'=>"656", 'couverture'=>"0", 'dateVersion'=>"2022-11-30 00:00:00",
      'dette'=>"19586",'duplication'=>"5.1",'favori'=>1, 'frontend'=>"1945",
      'hotspotHigh'=>"0", 'hotspotLow'=>"0", 'hotspotMedium'=>"2",'hotspotTotal'=>"2",
      'initial'=>0, 'mavenKey'=>"fr.ma-moulinette:ma-moulinette", 'noSonar'=>"0",
      'nomProjet'=>"ma-moulinette", 'nombreAnomalieBloquant'=>"17",   'nombreAnomalieCritique'=>"133", 'nombreAnomalieInfo'=>"2",
      'nombreAnomalieMajeur'=>"1118", 'nombreAnomalieMineur'=>"686",
      'nombreBug'=>"61", 'nombreCodeSmell'=>"1895", 'nombreDefaut'=>"1956",
      'nombreLigne'=>"24471", 'nombreLigneDeCode'=>"17301", 'nombreVulnerability'=>"0",
      'noteHotspot'=>"E", 'noteReliability'=>"C", 'noteSecurity'=>"A",
      'noteSqale'=>"C", 'suppressWarning'=>"0", 'testsUnitaires'=>"0", 'version'=>"1.6.0-RELEASE", 'versionAutre'=>"0", 'versionRelease'=>"8", 'versionSnapshot'=>"0",
      'vulnerabilityBlocker'=>"0", 'vulnerabilityCritical'=>"0", 'vulnerabilityInfo'=>"0",'vulnerabilityMajor'=>"0", 'vulnerabilityMinor'=>"0"];

    $client->request('PUT', '/api/enregistrement', ['json'=>$data]);
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(2,count($decode));
    // L'enregistrement existe déjà
    $this->assertEquals('19', $decode['code']);
  }
}
