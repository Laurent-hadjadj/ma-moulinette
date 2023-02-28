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

class ApiHomeControllerTest extends ApiTestCase
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
   * Created at: 20/02/2023, 11:53:43 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiProjetListe(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/projet/liste?mode=TEST');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(3,count($decode));
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertNotEquals('null', $decode['mode']);
    $this->assertArrayHasKey('nombre', $decode);
    $this->assertGreaterThanOrEqual(1, $decode['nombre']);
    $this->assertIsString($decode['mode']);
  }

  /**
   * [Description for testProjetCosuiNoMavenKey]
   * On test l'API /projet/cosui sans clé maven
   * @return void
   *
   * Created at: 20/02/2023, 09:56:10 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testProjetCosuiNoMavenKey(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/projet/cosui?mode=TEST');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(77,count($decode));
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertNotEquals('null', $decode['mode']);
    $this->assertArrayHasKey('version', $decode);
    $this->assertArrayHasKey('dateCopyright', $decode);
    $this->assertEquals('NaN', $decode['setup']);
    $this->assertEquals('NaN', $decode['monApplication']);
    $this->assertEquals('NaN', $decode['version_application']);
    $this->assertEquals('NaN', $decode['type_application']);
    $this->assertEquals('01/01/1980', $decode['date_application']);
    $this->assertEquals('F', $decode['note_code_smell']);
    $this->assertEquals('F', $decode['note_reliability']);
    $this->assertEquals('F', $decode['note_security']);
    $this->assertEquals('F', $decode['note_hotspot']);
    $this->assertEquals(0, $decode['bug_blocker']);
    $this->assertEquals(0, $decode['bug_critical']);
    $this->assertEquals(0, $decode['bug_major']);
    $this->assertEquals(0, $decode['vulnerability_blocker']);
    $this->assertEquals(0, $decode['vulnerability_critical']);
    $this->assertEquals(0, $decode['vulnerability_major']);
    $this->assertEquals(0, $decode['code_smell_blocker']);
    $this->assertEquals(0, $decode['code_smell_critical']);
    $this->assertEquals(0, $decode['code_smell_major']);
    $this->assertEquals(0, $decode['hotspot']);
    $this->assertEquals('NaN', $decode['initial_version_application']);
    $this->assertEquals('01/01/1980', $decode['initial_date_application']);
    $this->assertEquals('F', $decode['initial_note_code_smell']);
    $this->assertEquals('F', $decode['initial_note_reliability']);
    $this->assertEquals('F', $decode['initial_note_security']);
    $this->assertEquals('F', $decode['initial_note_hotspot']);
    $this->assertEquals(0, $decode['initial_bug_blocker']);
    $this->assertEquals(0, $decode['initial_bug_critical']);
    $this->assertEquals(0, $decode['initial_bug_major']);
    $this->assertEquals(0, $decode['initial_vulnerability_blocker']);
    $this->assertEquals(0, $decode['initial_vulnerability_critical']);
    $this->assertEquals(0, $decode['initial_vulnerability_major']);
    $this->assertEquals(0, $decode['initial_code_smell_blocker']);
    $this->assertEquals(0, $decode['initial_code_smell_critical']);
    $this->assertEquals(0, $decode['initial_code_smell_major']);
    $this->assertEquals('equal', $decode['evolution_bug_blocker']);
    $this->assertEquals('equal', $decode['evolution_bug_critical']);
    $this->assertEquals('equal', $decode['evolution_bug_major']);
    $this->assertEquals('equal', $decode['evolution_vulnerability_blocker']);
    $this->assertEquals('equal', $decode['evolution_vulnerability_critical']);
    $this->assertEquals('equal', $decode['evolution_vulnerability_major']);
    $this->assertEquals('equal', $decode['evolution_code_smell_blocker']);
    $this->assertEquals('equal', $decode['evolution_code_smell_critical']);
    $this->assertEquals('equal', $decode['evolution_code_smell_major']);
    $this->assertEquals('equal', $decode['evolution_hotspot']);
    $this->assertEquals(0, $decode['modal_initial_bug_blocker']);
    $this->assertEquals(0, $decode['modal_initial_bug_critical']);
    $this->assertEquals(0, $decode['modal_initial_bug_major']);
    $this->assertEquals(0, $decode['modal_initial_vulnerability_blocker']);
    $this->assertEquals(0, $decode['modal_initial_vulnerability_critical']);
    $this->assertEquals(0, $decode['modal_initial_vulnerability_major']);
    $this->assertEquals(0, $decode['modal_initial_code_smell_blocker']);
    $this->assertEquals(0, $decode['modal_initial_code_smell_critical']);
    $this->assertEquals(0, $decode['modal_initial_code_smell_major']);
    $this->assertEquals(0, $decode['modal_initial_hotspot']);
    $this->assertEquals(0, $decode['nombre_metier_code_smell_blocker']);
    $this->assertEquals(0, $decode['nombre_metier_code_smell_critical']);
    $this->assertEquals(0, $decode['nombre_metier_code_smell_major']);
    $this->assertEquals(0, $decode['nombre_presentation_code_smell_blocker']);
    $this->assertEquals(0, $decode['nombre_presentation_code_smell_critical']);
    $this->assertEquals(0, $decode['nombre_presentation_code_smell_major']);
    $this->assertEquals(0, $decode['nombre_metier_reliability_blocker']);
    $this->assertEquals(0, $decode['nombre_metier_reliability_critical']);
    $this->assertEquals(0, $decode['nombre_metier_reliability_major']);
    $this->assertEquals(0, $decode['nombre_presentation_reliability_blocker']);
    $this->assertEquals(0, $decode['nombre_presentation_reliability_critical']);
    $this->assertEquals(0, $decode['nombre_presentation_reliability_major']);
    $this->assertEquals(0, $decode['nombre_metier_vulnerability_blocker']);
    $this->assertEquals(0, $decode['nombre_metier_vulnerability_critical']);
    $this->assertEquals(0, $decode['nombre_metier_vulnerability_major']);
    $this->assertEquals(0, $decode['nombre_presentation_vulnerability_blocker']);
    $this->assertEquals(0, $decode['nombre_presentation_vulnerability_critical']);
    $this->assertEquals(0, $decode['nombre_presentation_vulnerability_major']);
  }

  /**
   * [Description for testProjetCosuiNoMavenKey]
   * On test l'API /projet/cosui avec clé maven*
   * @return void
   *
   * Created at: 20/02/2023, 10:46:40 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testProjetCosuiMavenKey(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/projet/cosui?'.static::$mavenKey.'&mode=TEST');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(77,count($decode));
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertNotEquals('null', $decode['mode']);
    $this->assertArrayHasKey('version', $decode);
    $this->assertArrayHasKey('dateCopyright', $decode);

    $this->assertEquals('1677324427466', $decode['setup']);
    $this->assertEquals('ma-moulinette', $decode['monApplication']);
    $this->assertEquals('1.6.0', $decode['version_application']);
    $this->assertEquals('RELEASE', $decode['type_application']);
    $this->assertEquals('2022-11-30 00:00:00', $decode['date_application']);
    $this->assertEquals('C', $decode['note_code_smell']);
    $this->assertEquals('C', $decode['note_reliability']);
    $this->assertEquals('A', $decode['note_security']);
    $this->assertEquals('E', $decode['note_hotspot']);
    $this->assertEquals(0, $decode['bug_blocker']);
    $this->assertEquals(0, $decode['bug_critical']);
    $this->assertEquals(31, $decode['bug_major']);
    $this->assertEquals(0, $decode['vulnerability_blocker']);
    $this->assertEquals(0, $decode['vulnerability_critical']);
    $this->assertEquals(0, $decode['vulnerability_major']);
    $this->assertEquals(17, $decode['code_smell_blocker']);
    $this->assertEquals(133, $decode['code_smell_critical']);
    $this->assertEquals(1087, $decode['code_smell_major']);
    $this->assertEquals(2, $decode['hotspot']);
    $this->assertEquals('1.6.0', $decode['initial_version_application']);
    $this->assertEquals('2022-11-30 00:00:00', $decode['initial_date_application']);
    $this->assertEquals('C', $decode['initial_note_code_smell']);
    $this->assertEquals('C', $decode['initial_note_reliability']);
    $this->assertEquals('A', $decode['initial_note_security']);
    $this->assertEquals('E', $decode['initial_note_hotspot']);
    $this->assertEquals(0, $decode['initial_bug_blocker']);
    $this->assertEquals(0, $decode['initial_bug_critical']);
    $this->assertEquals(31, $decode['initial_bug_major']);
    $this->assertEquals(0, $decode['initial_vulnerability_blocker']);
    $this->assertEquals(0, $decode['initial_vulnerability_critical']);
    $this->assertEquals(0, $decode['initial_vulnerability_major']);
    $this->assertEquals(17, $decode['initial_code_smell_blocker']);
    $this->assertEquals(133, $decode['initial_code_smell_critical']);
    $this->assertEquals(1087, $decode['initial_code_smell_major']);
    $this->assertEquals('equal', $decode['evolution_bug_blocker']);
    $this->assertEquals('equal', $decode['evolution_bug_critical']);
    $this->assertEquals('down', $decode['evolution_bug_major']);
    $this->assertEquals('equal', $decode['evolution_vulnerability_blocker']);
    $this->assertEquals('equal', $decode['evolution_vulnerability_critical']);
    $this->assertEquals('equal', $decode['evolution_vulnerability_major']);
    $this->assertEquals('down', $decode['evolution_code_smell_blocker']);
    $this->assertEquals('down', $decode['evolution_code_smell_critical']);
    $this->assertEquals('down', $decode['evolution_code_smell_major']);
    $this->assertEquals('down', $decode['evolution_hotspot']);
    $this->assertEquals(0, $decode['modal_initial_bug_blocker']);
    $this->assertEquals(0, $decode['modal_initial_bug_critical']);
    $this->assertEquals(31, $decode['modal_initial_bug_major']);
    $this->assertEquals(0, $decode['modal_initial_vulnerability_blocker']);
    $this->assertEquals(0, $decode['modal_initial_vulnerability_critical']);
    $this->assertEquals(0, $decode['modal_initial_vulnerability_major']);
    $this->assertEquals(17, $decode['modal_initial_code_smell_blocker']);
    $this->assertEquals(133, $decode['modal_initial_code_smell_critical']);
    $this->assertEquals(1087, $decode['modal_initial_code_smell_major']);
    $this->assertEquals(2, $decode['modal_initial_hotspot']);
    $this->assertEquals(0, $decode['nombre_metier_code_smell_blocker']);
    $this->assertEquals(0, $decode['nombre_metier_code_smell_critical']);
    $this->assertEquals(1087, $decode['nombre_metier_code_smell_major']);
    $this->assertEquals(17, $decode['nombre_presentation_code_smell_blocker']);
    $this->assertEquals(133, $decode['nombre_presentation_code_smell_critical']);
    $this->assertEquals(1087, $decode['nombre_presentation_code_smell_major']);
    $this->assertEquals(0, $decode['nombre_metier_reliability_blocker']);
    $this->assertEquals(0, $decode['nombre_metier_reliability_critical']);
    $this->assertEquals(0, $decode['nombre_metier_reliability_major']);
    $this->assertEquals(0, $decode['nombre_presentation_reliability_blocker']);
    $this->assertEquals(0, $decode['nombre_presentation_reliability_critical']);
    $this->assertEquals(31, $decode['nombre_presentation_reliability_major']);
    $this->assertEquals(0, $decode['nombre_metier_vulnerability_blocker']);
    $this->assertEquals(0, $decode['nombre_metier_vulnerability_critical']);
    $this->assertEquals(0, $decode['nombre_metier_vulnerability_major']);
    $this->assertEquals(0, $decode['nombre_presentation_vulnerability_blocker']);
    $this->assertEquals(0, $decode['nombre_presentation_vulnerability_critical']);
    $this->assertEquals(0, $decode['nombre_presentation_vulnerability_major']);
  }

  /**
   * [Description for testApiStatus]
   * On vérifie que le serveur sonarqube est UP
   * @return void
   *
   * Created at: 20/02/2023, 13:34:20 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiStatus(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/status');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertEquals(3,count($decode));
    $this->assertEquals("8.9.9.56886", $decode['version']);
    $this->assertEquals("UP", $decode['status']);
    $this->assertArrayHasKey("id", $decode);
    $this->assertArrayHasKey("version", $decode);
  }

  /**
   * [Description for testApiStatus]
   *  Revoie une erreur 403 --> 500. Il faut une habilittaion admin sur sonarqube
   * @return void
   *
   * Created at: 20/02/2023, 13:34:58 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiHealth(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/health');
    $this->assertEquals(500, $client->getResponse()->getStatusCode());
  }

  /**
   * [Description for testApiSystemInfo]
   * Revoie une erreur 403 --> 500. Il faut une habilittaion admin sur sonarqube
   *
   * @return void
   *
   * Created at: 20/02/2023, 14:02:33 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiSystemInfo(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/system/info');
    $this->assertEquals(500, $client->getResponse()->getStatusCode());
  }

  /**
   * [Description for testApiQualityProfiles]
   * Tests chargement des profiles sonarqube
   * @return void
   *
   * Created at: 20/02/2023, 14:04:41 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiQualityProfiles(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/quality/profiles?mode=TEST');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertEquals('200', $decode[0]);
    $this->assertIsArray($decode);
    $this->assertEquals(3,count($decode));
    $this->assertEquals("TEST", $decode['mode']);
    $this->assertArrayHasKey('listeProfil', $decode);
    $this->assertEquals(12, count($decode['listeProfil']));
  }

  /**
   * [Description for testApiTags]
   * Test la récupération des tags
   * @return void
   *
   * Created at: 20/02/2023, 14:16:54 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiTags(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/tags?mode=TEST');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertEquals('200', $decode[0]);
    $this->assertIsArray($decode);
    $this->assertEquals(6,count($decode));
    $this->assertEquals("TEST", $decode['mode']);
    $this->assertNotEquals("null", $decode['mode']);
    $this->assertArrayHasKey('message', $decode);
    $this->assertArrayHasKey('message', $decode);
    $this->assertArrayHasKey('public', $decode);
    $this->assertArrayHasKey('private', $decode);
    $this->assertArrayHasKey('empty_tags', $decode);
  }

  /**
   * [Description for testApiVisibility]
   * Tests de la récupération de la visibilité des projets. Il faut être en Admin Sonarqube.
   * @return void
   *
   * Created at: 20/02/2023, 15:21:49 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiVisibility(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/api/visibility');
    $this->assertEquals(500, $client->getResponse()->getStatusCode());
  }

}
