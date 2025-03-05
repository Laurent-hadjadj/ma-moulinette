<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2024.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Tests\Unit\Controller\Repartition;

use App\Repository\RepartitionRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Controller\Batch\BatchCollecteRepartitionController;


/**
 * [Description RepartitionControllerTest]
 */
class RepartitionControllerTest extends WebTestCase
{

    private static $josh = 'josh.liberman@ma-moulinette.fr';
    private static $aurelie = 'aurelie.petit-coeur@ma-moulinette.fr';
    private static $titre = '[Répartition-Module]';
    private static $erreur400 = " La requête est incorrecte (Erreur 400).";
    private static $erreur403 = " Vous devez avoir le rôle COLLECTE pour réaliser cette action (Erreur 403).";
    private static $erreur500 = "La collecte des données SonarQube à échouée.";
    private static $erreur50x = " L'enregistrement des données initiales a échouées.";

    private static $repartition = '/repartition';
    private static $token = '?token=BGR2ZQL5ZQLjA3kzpv5gLF1go3IfnJ5yqUEyBzkyYJAbLKD=';

    public function testRepartitionWithoutToken()
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$josh);
        $client->loginUser($testUser);

        $crawler = $client->request('GET', static::$repartition);
        $response = $client->getResponse();

        // Vérifie que la réponse est un succès
        $this->assertEquals(200, $response->getStatusCode());

        // Vérifie que la réponse contient le message d'erreur attendu
        $this->assertStringContainsString(static::$erreur400, $response->getContent());

        // Vérifie que le message flash est correctement rendu dans la page
        $this->assertCount(1, $crawler->filter('div.callout.alert-callout-border.alert'));
        $this->assertStringContainsString(static::$titre . static::$erreur400, $crawler->filter('div.callout.alert-callout-border.alert p.callout-message')->text());
    }

    public function testRepartitionWithoutRoleCollecte()
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$josh);
        $client->loginUser($testUser);

        $crawler = $client->request('GET', static::$repartition.static::$token);
        $response = $client->getResponse();

        // Vérifie que la réponse est un succès
        $this->assertEquals(200, $response->getStatusCode());

        // Vérifie que la réponse contient le message d'erreur attendu
        $this->assertStringContainsString(static::$erreur403, $response->getContent());
        // Vérifie que le message flash est correctement rendu dans la page
        $this->assertCount(1, $crawler->filter('div.callout.alert-callout-border.warning'));
        $this->assertStringContainsString(static::$titre . static::$erreur403, $crawler->filter('div.callout.alert-callout-border.warning p.callout-message')->text());
    }

    public function testRepartitionWithInValidToken()
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $parameter = '?token=some-bad-token';
        $crawler = $client->request('GET', static::$repartition.$parameter);
        $response = $client->getResponse();

        // Vérifie que la réponse est un succès
        $this->assertEquals(200, $response->getStatusCode());

        // Vérifie que la réponse contient le message d'erreur attendu
        $this->assertStringContainsString(static::$erreur400, $response->getContent());

        // Vérifie que le message flash est correctement rendu dans la page
        $this->assertCount(1, $crawler->filter('div.callout.alert-callout-border.alert'));
        $this->assertStringContainsString(static::$titre . static::$erreur400, $crawler->filter('div.callout.alert-callout-border.alert p.callout-message')->text());
    }

    public function testRepartitionFailsWithCollecteRepartitionModule()
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Création du mock du service BatchCollecteRepartition
        $mockService = $this->createMock(BatchCollecteRepartitionController::class);
        $mockService->method('CollecteRepartitionModule')->willReturnCallback(
            function ($mavenKey, $category) {
                $mockData = [
                    'BUG' => [
                        'code' => 500,
                        'total' => 0,
                        'category' => 'BUG',
                        'blocker' => 0,
                        'critical' => 0,
                        'major' => 0,
                        'minor' => 0,
                        'info' => 0,
                        'erreur' => ''
                    ],
                ];
                return $mockData[$category];
            }
        );

        // Injection du mock dans le conteneur Symfony
        static::getContainer()->set(BatchCollecteRepartitionController::class, $mockService);

        $crawler = $client->request('GET', static::$repartition.static::$token);
        $response = $client->getResponse();

        // Vérifie que la réponse est un succès
        $this->assertEquals(200, $response->getStatusCode());

        // Vérifie que la réponse contient le message d'erreur attendu
        $this->assertStringContainsString(static::$erreur500, $response->getContent());

        // Vérifie que le message flash est correctement rendu dans la page
        $this->assertCount(1, $crawler->filter('div.callout.alert-callout-border.alert'));
        $this->assertStringContainsString(static::$erreur500, $crawler->filter('div.callout.alert-callout-border.alert p.callout-message')->text());

    }

    public function testRepartitionFailSelectOrUpdateRepartitionInitial()
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Création du mock du service BatchCollecteRepartition
        $mockService = $this->createMock(BatchCollecteRepartitionController::class);

        // Mock du repository RepartitionRepository
        $mockRepartitionRepository = $this->createMock(RepartitionRepository::class);
        $mockRepartitionRepository->method('selectOrUpdateRepartitionInitial')->willReturn(['code' => 500, 'erreur' => 'Debug : Erreur 500.']);

        // Définition des valeurs attendues par type
        $mockService->method('CollecteRepartitionModule')->willReturnCallback(
            function ($mavenKey, $type) {
                $mockData = [
                    'BUG' => [
                        'code' => 200,
                        'total' => 1843+29,
                        'blocker' => 0,
                        'critical' => 0,
                        'major' => 1843,
                        'minor' => 29,
                        'info' => 0
                    ],
                    'VULNERABILITY' => [
                        'code' => 200,
                        'total' => 1430,
                        'blocker' => 0,
                        'critical' => 0,
                        'major' => 0,
                        'minor' => 1427,
                        'info' => 3
                    ],
                    'CODE_SMELL' => [
                        'code' => 200,
                        'total' => 1194+13272+8207+13632,
                        'blocker' => 0,
                        'critical' => 1194,
                        'major' => 13272,
                        'minor' => 8207,
                        'info' => 13632
                    ]
                ];
                return $mockData[$type] ?? ['code' => 500, 'erreur' => 'Type inconnu'];
            }
        );

        // Injection du mock dans le conteneur Symfony
        static::getContainer()->set(BatchCollecteRepartitionController::class, $mockService);
        static::getContainer()->set(RepartitionRepository::class, $mockRepartitionRepository);

        $crawler = $client->request('GET', static::$repartition.static::$token);
        $response = $client->getResponse();

        // Vérifie que la réponse est un succès
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertResponseIsSuccessful();

        // Vérifie que le message flash est correctement rendu dans la page
        $this->assertCount(1, $crawler->filter('div.callout.alert-callout-border.alert'));
        $this->assertStringContainsString(static::$titre . static::$erreur50x.'Debug : Erreur 500.', $crawler->filter('div.callout.alert-callout-border.alert p.callout-message')->text());
    }

    public function testRepartitionSuccess()
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Création du mock du service BatchCollecteRepartition
        $mockService = $this->createMock(BatchCollecteRepartitionController::class);

        // Mock du repository RepartitionRepository
        $mockRepartitionRepository = $this->createMock(RepartitionRepository::class);
        $mockRepartitionRepository->method('selectOrUpdateRepartitionInitial')->willReturn(['code' => 200]);

        // Définition des valeurs attendues par type
        $mockService->method('CollecteRepartitionModule')->willReturnCallback(
            function ($mavenKey, $type) {
                $mockData = [
                    'BUG' => [
                        'code' => 200,
                        'total' => 1843+29,
                        'blocker' => 0,
                        'critical' => 0,
                        'major' => 1843,
                        'minor' => 29,
                        'info' => 0
                    ],
                    'VULNERABILITY' => [
                        'code' => 200,
                        'total' => 1430,
                        'blocker' => 0,
                        'critical' => 0,
                        'major' => 0,
                        'minor' => 1427,
                        'info' => 3
                    ],
                    'CODE_SMELL' => [
                        'code' => 200,
                        'total' => 1194+13272+8207+13632,
                        'blocker' => 0,
                        'critical' => 1194,
                        'major' => 13272,
                        'minor' => 8207,
                        'info' => 13632
                    ]
                ];
                return $mockData[$type] ?? ['code' => 500, 'erreur' => 'Type inconnu'];
            }
        );

        // Injection du mock dans le conteneur Symfony
        static::getContainer()->set(BatchCollecteRepartitionController::class, $mockService);
        static::getContainer()->set(RepartitionRepository::class, $mockRepartitionRepository);

        $crawler = $client->request('GET', static::$repartition.static::$token);
        $response = $client->getResponse();

        // Vérifie que la réponse est un succès
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertResponseIsSuccessful();

        // Vérifie que la page contient les informations nécessaires
        $this->assertStringContainsString('fr.ma-moulinette:le-chat', $crawler->filter('h1#js-app')->attr('data-application'));
        $value = $crawler->filter('span#js-setup')->text();
        $this->assertIsNumeric($value);
        $this->assertStringContainsString('1872', $crawler->filter('#nombre-bug')->attr('data-nombre-bug'));
        $this->assertStringContainsString('1430', $crawler->filter('#nombre-vulnerability')->attr('data-nombre-vulnerability'));
        $this->assertStringContainsString('36305', $crawler->filter('#nombre-code-smell')->attr('data-nombre-code-smell'));

    }

}
