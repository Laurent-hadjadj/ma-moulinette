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

class BatchControllerTest extends ApiTestCase
{
  private static $userTest='admin@ma-moulinette.fr';
  private static $mavenKey='mavenKey=fr.ma-moulinette:ma-moulinette';
  private static $applicationJson='application/json';
  public static $strContentType = 'application/json';

  /**
   * [Description for testTraitementPendingKO]
   * Le job n'existe pas.
   * @return void
   *
   * Created at: 28/02/2023, 16:57:06 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testTraitementPendingError(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/traitement/pending?job=Test&mode=TEST');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(5,count($decode));
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('job', $decode);
    $this->assertArrayHasKey('code', $decode);
    $this->assertArrayHasKey('execution', $decode);
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertNotEquals('null', $decode['mode']);
    $this->assertEquals('KO', $decode['code']);
    $this->assertEquals('Test', $decode['job']);
    $this->assertEquals('error', $decode['execution']);
    $this->assertNotEquals('end', $decode['execution']);
    $this->assertNotEquals('start', $decode['execution']);
    $this->assertIsString($decode['mode']);
    $this->assertIsString($decode['job']);
    $this->assertIsString($decode['code']);
    $this->assertIsString($decode['execution']);
  }

  /**
   * [Description for testTraitementPendingError]
   * Le job existe mais il y a déjà un job en cours
   * @return void
   *
   * Created at: 28/02/2023, 19:52:02 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testTraitementPending(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/traitement/pending?job=MA MOULINETTE&mode=TEST');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);

    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(4,count($decode));
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('code', $decode);
    $this->assertArrayHasKey('execution', $decode);
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertNotEquals('null', $decode['mode']);
    $this->assertEquals('OK', $decode['code']);
    $this->assertEquals('pending', $decode['execution']);
    $this->assertNotEquals('end', $decode['execution']);
    $this->assertNotEquals('start', $decode['execution']);
    $this->assertIsString($decode['mode']);
    $this->assertIsString($decode['code']);
    $this->assertIsString($decode['execution']);
  }

  /**
   * [Description for testTraitementManuelKo]
   *
   * @return void
   *
   * Created at: 04/03/2023, 20:39:33 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testTraitementManuelKo(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    //MA MOULINETTE
    $data=['job'=>'null'];
    $client->request('POST', '/traitement/manuel', ['json'=>$data]);
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);

    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('406', $decode[0]);
    $this->assertEquals(2,count($decode));
    $this->assertArrayHasKey('job', $decode);
    $this->assertEquals('null', $decode['job']);
    $this->assertIsString($decode['job']);
  }

  public function testTraitementManuel(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $data=['job'=>'MA MOULINETTE'];
    $client->request('POST', '/traitement/manuel', ['json'=>$data]);
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);

    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    dd($decode);
    $this->assertEquals(3,count($decode));
    $this->assertArrayHasKey('mode', $decode);
    $this->assertArrayHasKey('job', $decode);
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertNotEquals('null', $decode['mode']);
    $this->assertEquals('null', $decode['job']);
    $this->assertIsString($decode['mode']);
    $this->assertIsString($decode['job']);
  }


}
