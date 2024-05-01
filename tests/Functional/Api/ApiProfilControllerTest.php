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

class ApiProfilControllerTest extends ApiTestCase
{
  private static $userTest='admin@ma-moulinette.fr';

  /**
   * [Description for testApiQualityLangage]
   * Test de l'API  /api/quality/language
   * @return void
   *
   * Created at: 15/02/2023, 19:03:55 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiQualityLangage(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);
    $client->request('GET', '/api/quality/langage');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', 'application/json');
    $this->assertIsArray($decode['label']);
    $this->assertEquals(12,count($decode['label']));
    $this->assertContains('CSS', $decode['label']);
  }

}
