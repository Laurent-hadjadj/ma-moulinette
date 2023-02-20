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

class ApiDetailsPeintureControllerTest extends ApiTestCase
{
  private static $userTest='admin@ma-moulinette.fr';
  private static $mavenKey='mavenKey=fr.franceagrimer:ma-moulinette';

  /**
   * [Description for testApiPeintureProjetAnomalieDetails406]
   * On test l'API api/peinture/projet/anomalie/details sans argument
   * Retour : 406
   *
   * @return void
   *
   * Created at: 15/02/2023, 19:06:42 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiPeintureProjetAnomalieDetails406(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);
    $client->request('GET', '/api/peinture/projet/anomalie/details');
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', 'application/json');
    $this->assertIsArray($decode);
    $this->assertEquals(2,count($decode));
    $this->assertEquals('406', $decode[0]);
  }

  /**
   * [Description for testApiPeintureProjetAnomalieDetails200]
   * On test l'API api/peinture/projet/anomalie/details avec une clé maven
   * Retour : 200
   *
   * @return void
   *
   * Created at: 15/02/2023, 19:07:58 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function testApiPeintureProjetAnomalieDetails200(): void
  {
    $client = static::createClient();
    $userRepository = static::getContainer()->get(UtilisateurRepository::class);
    $testUser = $userRepository->findOneByCourriel(static::$userTest);
    $client->loginUser($testUser);
    $client->request('GET', '/api/peinture/projet/anomalie/details?'.static::$mavenKey);
    $response = $client->getResponse()->getContent();
    $decode=json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $this->assertResponseIsSuccessful();
    $this->assertResponseHeaderSame('content-type', 'application/json');
    $this->assertIsArray($decode);
    $this->assertEquals(17,count($decode));
    $this->assertEquals('200', $decode[0]);
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
  }

}
