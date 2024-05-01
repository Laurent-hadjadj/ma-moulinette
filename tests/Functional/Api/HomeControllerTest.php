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

class HomeControllerTest extends ApiTestCase
{
  private static $userTest='admin@ma-moulinette.fr';
  private static $mavenKey='mavenKey=fr.ma-moulinette:ma-moulinette';
  private static $applicationJson='application/json';
  public static $strContentType = 'application/json';

  public function testHome(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/home?mode=TEST');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(10,count($decode));
    $this->assertArrayHasKey('projetBD', $decode);
    $this->assertArrayHasKey('projetSonar', $decode);
    $this->assertArrayHasKey('profilBD', $decode);
    $this->assertArrayHasKey('profilSonar', $decode);
    $this->assertArrayHasKey('nombreFavori', $decode);
    $this->assertArrayHasKey('mavenkey', $decode['favori'][0]);
    $this->assertArrayHasKey('nom', $decode['favori'][0]);
    $this->assertArrayHasKey('version', $decode['favori'][0]);
    $this->assertArrayHasKey('date', $decode['favori'][0]);
    $this->assertArrayHasKey('fiabilite', $decode['favori'][0]);
    $this->assertArrayHasKey('securite', $decode['favori'][0]);
    $this->assertArrayHasKey('hotspot', $decode['favori'][0]);
    $this->assertArrayHasKey('sqale', $decode['favori'][0]);
    $this->assertArrayHasKey('vulnerability', $decode['favori'][0]);
    $this->assertArrayHasKey('code_smell', $decode['favori'][0]);
    $this->assertArrayHasKey('hotspots', $decode['favori'][0]);
    $this->assertIsArray($decode['favori']);
    $this->assertGreaterThanOrEqual(1, $decode['projetBD']);
    $this->assertIsInt($decode['projetBD']);
    $this->assertGreaterThanOrEqual(1, $decode['projetSonar']);
    $this->assertIsInt($decode['projetSonar']);
    $this->assertGreaterThanOrEqual(1, $decode['profilBD']);
    $this->assertIsInt($decode['profilBD']);
    $this->assertGreaterThanOrEqual(1, $decode['profilSonar']);
    $this->assertIsInt($decode['profilSonar']);
    $this->assertGreaterThanOrEqual(0, $decode['favori']);
    $this->assertIsArray($decode['favori']);
    $this->assertEquals("fr.ma-moulinette:ma-moulinette", $decode['favori'][0]['mavenkey']);
    $this->assertIsString($decode['favori'][0]['mavenkey']);
    $this->assertEquals("ma-moulinette", $decode['favori'][0]['nom']);
    $this->assertIsString($decode['favori'][0]['nom']);
    $this->assertEquals("1.6.0-RELEASE", $decode['favori'][0]['version']);
    $this->assertIsString($decode['favori'][0]['version']);
    $this->assertEquals("2022-11-30 00:00:00", $decode['favori'][0]['date']);
    $this->assertIsString($decode['favori'][0]['date']);
    $this->assertEquals("C", $decode['favori'][0]['fiabilite']);
    $this->assertIsString($decode['favori'][0]['fiabilite']);
    $this->assertEquals("A", $decode['favori'][0]['securite']);
    $this->assertIsString($decode['favori'][0]['securite']);
    $this->assertEquals("E", $decode['favori'][0]['hotspot']);
    $this->assertIsString($decode['favori'][0]['hotspot']);
    $this->assertEquals("C", $decode['favori'][0]['sqale']);
    $this->assertIsString($decode['favori'][0]['sqale']);
    $this->assertEquals(61, $decode['favori'][0]['bug']);
    $this->assertIsInt($decode['favori'][0]['bug']);
    $this->assertEquals(0, $decode['favori'][0]['vulnerability']);
    $this->assertIsInt($decode['favori'][0]['vulnerability']);
    $this->assertEquals(1895, $decode['favori'][0]['code_smell']);
    $this->assertIsInt($decode['favori'][0]['code_smell']);
    $this->assertEquals(2, $decode['favori'][0]['hotspots']);
    $this->assertIsInt($decode['favori'][0]['hotspots']);
    $this->assertEquals("2.0.0-RC1", $decode['version']);
    $this->assertIsString($decode['version']);
    $this->assertGreaterThanOrEqual(2023, $decode['dateCopyright']);
    $this->assertIsString($decode['dateCopyright']);
    $this->assertEquals("TEST", $decode['mode']);
    $this->assertIsString($decode['mode']);
  }
}
