<?php

namespace App\Tests\Api;

use App\Controller\ApiProfilController;
use App\Repository\Main\UtilisateurRepository;

use PHPUnit\Framework\TestCase;
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class ProjetControllerTest extends ApiTestCase
{
  private static $userTest='admin@ma-moulinette.fr';
  private static $mavenKey='mavenKey=fr.franceagrimer:ma-moulinette';
  private static $applicationJson='application/json';
  public static $strContentType = 'application/json';

  /**
   * [Description for testProjet200()]
   * On appel le controller qui génére la vue en mode TEST
   * Retour HTTP 200
   * @return void
   *
   * Created at: 19/02/2023, 160:21:14 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testProjet(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/projet?mode=TEST');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(4,count($decode));
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertArrayHasKey('version', $decode);
    $this->assertArrayHasKey('dateCopyright', $decode);
    $this->assertIsString($decode['dateCopyright']);
    $this->assertIsString($decode['version']);
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
    $this->assertArrayHasKey('version', $decode);
    $this->assertArrayHasKey('dateCopyright', $decode);

    $this->assertEquals('NaN', $decode['setup']);
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
}
