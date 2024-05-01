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

class ProfilControllerTest extends ApiTestCase
{
  private static $userTest='admin@ma-moulinette.fr';
  private static $applicationJson='application/json';
  public static $strContentType = 'application/json';

  /**
   * [Description for testProfil()]
   * On appel le controller qui génére la vue en mode TEST
   * Retour HTTP 200
   * @return void
   *
   * Created at: 20/02/2023, 15:59:16 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testProfil(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);

    $client->request('GET', '/profil?mode=TEST');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', static::$applicationJson);
    $this->assertIsArray($decode);
    $this->assertEquals('200', $decode[0]);
    $this->assertEquals(5,count($decode));
    $this->assertEquals('TEST', $decode['mode']);
    $this->assertNotEquals('null', $decode['mode']);
    $this->assertArrayHasKey('version', $decode);
    $this->assertArrayHasKey('dateCopyright', $decode);
    // On a 12 profils (A mettre à jour en fonction des profils utilisés sur sonarqube).
    $this->assertEquals(12,count($decode['liste']));
    $this->assertGreaterThanOrEqual(1, $decode['liste']);
  }
}
