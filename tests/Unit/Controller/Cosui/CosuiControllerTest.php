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

namespace App\Tests\Unit\Controller\Cosui;

use App\Repository\HistoriqueRepository;
use App\Repository\UtilisateurRepository;

use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Persistence\ObjectManager;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * [Description CosuiControllerTest]
 */
class CosuiControllerTest extends WebTestCase
{
    private static $josh = 'josh.liberman@ma-moulinette.fr';
    private static $aurelie = 'aurelie.petit-coeur@ma-moulinette.fr';
    private static $titre = '[COSUI]';
    private static $erreur400 = " La requête est incorrecte (Erreur 400).";
    private static $erreur403 = " Vous devez avoir le rôle COLLECTE pour réaliser cette action (Erreur 403).";
    private static $messageErreur500 = " Une erreur inattendue s'est produite lors de la récupération des informations pour la clé fr.ma-petite-entreprise:ma-moulinette.";

    private static $projetCosui = '/projet/cosui';
    private static $token = '?token=BGR2ZQL5ZQLjA3kzpv5gLF1jMKEcqTHgMJ50pzIjpzymMGcgLF1go3IfnJ5yqUEy=';
    private static $filterCalloutMessage = 'div.callout.alert-callout-border.alert p.callout-message';
    private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';

    public function loadFixturesManuellement(ObjectManager $manager): void
    {
        $loader = new Loader();

        // Ajoute ici toutes les fixtures que tu veux charger
        $loader->addFixture(new \App\DataFixtures\UtilisateurFixtures());
        $loader->addFixture(new \App\DataFixtures\RepartitionFixtures());

        $purger = new ORMPurger();
        $executor = new ORMExecutor($manager, $purger);

        // Si tu veux garder les données existantes :
        //$purger->setPurgeMode(ORMPurger::PURGE_MODE_DELETE);

        $executor->execute($loader->getFixtures());
    }

    public function testCosuiWithoutToken()
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$josh);
        $client->loginUser($testUser);

        $crawler = $client->request('GET', static::$projetCosui);
        $response = $client->getResponse();

        // Vérifie que la réponse est un succès
        $this->assertEquals(200, $response->getStatusCode());

        // Vérifie que la réponse contient le message d'erreur attendu
        $this->assertStringContainsString(static::$erreur400, $response->getContent());

        // Vérifie que le message flash est correctement rendu dans la page
        $this->assertCount(1, $crawler->filter('div.callout.alert-callout-border.alert'));
        $this->assertStringContainsString(static::$titre . static::$erreur400, $crawler->filter(static::$filterCalloutMessage)->text());
    }

    public function testCosuiWithoutRoleCollecte()
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$josh);
        $client->loginUser($testUser);

        $crawler = $client->request('GET', static::$projetCosui.static::$token);
        $response = $client->getResponse();

        // Vérifie que la réponse est un succès
        $this->assertEquals(200, $response->getStatusCode());

        // Vérifie que la réponse contient le message d'erreur attendu
        $this->assertStringContainsString(static::$erreur403, $response->getContent());
        // Vérifie que le message flash est correctement rendu dans la page
        $this->assertCount(1, $crawler->filter('div.callout.alert-callout-border.warning'));
        $this->assertStringContainsString(static::$titre . static::$erreur403, $crawler->filter('div.callout.alert-callout-border.warning p.callout-message')->text());
    }

    public function testCosuiWithInValidToken()
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $parameter = '?token=some-bad-token';
        $crawler = $client->request('GET', static::$projetCosui.$parameter);
        $response = $client->getResponse();

        // Vérifie que la réponse est un succès
        $this->assertEquals(200, $response->getStatusCode());

        // Vérifie que la réponse contient le message d'erreur attendu
        $this->assertStringContainsString(static::$erreur400, $response->getContent());

        // Vérifie que le message flash est correctement rendu dans la page
        $this->assertCount(1, $crawler->filter('div.callout.alert-callout-border.alert'));
        $this->assertStringContainsString(static::$titre . static::$erreur400, $crawler->filter(static::$filterCalloutMessage)->text());
    }

    public function testCosuiWithNotesError()
    {
        $client = static::createClient();

        // Mock du repository
        $historiqueRepositoryMock = $this->createMock(HistoriqueRepository::class);

        $historiqueRepositoryMock
            ->method('selectHistoriqueProjetLast')
            ->willReturn([
                'maven_key' => static::$mavenKey,
                'code' => 500,
                'erreur' => 'erreur 500'
            ]);

        // Injecte le mock dans le conteneur
        static::getContainer()->set(HistoriqueRepository::class, $historiqueRepositoryMock);

        // Authentifie l'utilisateur
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $parameter = static::$token;
        $crawler = $client->request('GET', static::$projetCosui . $parameter);
        $response = $client->getResponse();

        // Vérifie la réponse HTTP
        $this->assertEquals(200, $response->getStatusCode());

        // Vérifie que le message flash est affiché
        $this->assertCount(1, $crawler->filter('div.callout.alert-callout-border.alert'));
        $this->assertStringContainsString(
            static::$titre . static::$messageErreur500,
            $crawler->filter(static::$filterCalloutMessage)->text()
        );
    }

    public function testCosuiWithNotesNotFound()
    {
        $client = static::createClient();

        // Mock du repository
        $historiqueRepositoryMock = $this->createMock(HistoriqueRepository::class);

        $historiqueRepositoryMock
            ->method('selectHistoriqueProjetLast')
            ->willReturn([
                'maven_key' => static::$mavenKey,
                'code' => 200,
                'infos' => [] ]);

        // Injecte le mock dans le conteneur
        static::getContainer()->set(HistoriqueRepository::class, $historiqueRepositoryMock);

        // Authentifie l'utilisateur
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $parameter = static::$token;
        $crawler = $client->request('GET', static::$projetCosui . $parameter);
        $response = $client->getResponse();

        // Vérifie la réponse HTTP
        $this->assertEquals(200, $response->getStatusCode());

        // Vérifie que le message flash est affiché
        $this->assertCount(1, $crawler->filter('div.callout.alert-callout-border.alert'));
        $this->assertStringContainsString(
            "[COSUI-001] Il n'y a pas de données dans la babase !",
            $crawler->filter(static::$filterCalloutMessage)->text()
        );
    }

    public function testCosuiWithReferenceError()
    {
        $client = static::createClient();

        // Mock du repository
        $historiqueRepositoryMock = $this->createMock(HistoriqueRepository::class);

        $historiqueRepositoryMock
        ->method('selectHistoriqueProjetLast')
        ->willReturn([
            'maven_key' => static::$mavenKey,
            'code' => 200,
            'infos' => [
                [
                    'name' => 'ma-moulinette',
                    'version' => '1.2.3-RELEASE',
                    'date_version' => '2024-08-18 15:54:26',
                    'bug_blocker' => 7,
                    'bug_critical' => 0,
                    'bug_major' => 44,
                    'vulnerability_blocker' => 0,
                    'vulnerability_critical' => 9,
                    'vulnerability_major' => 0,
                    'code_smell_blocker' => 0,
                    'code_smell_critical' => 4,
                    'code_smell_major' => 109,
                    'nombre_hotspot' => 0,
                    'note_reliability' => 'E',
                    'note_security' => 'D',
                    'note_sqale' => 'A',
                    'note_hotspot' => 'A',
                    'coverage' => 50.1,
                    'sqale_debt_ratio' => 1
                ]
            ]
        ]);

        $historiqueRepositoryMock
            ->method('selectHistoriqueProjetReference')
            ->willReturn([
                'maven_key' => static::$mavenKey,
                'code' => 500,
                'erreur' => 'erreur 500'
            ]);

        // Injecte le mock dans le conteneur
        static::getContainer()->set(HistoriqueRepository::class, $historiqueRepositoryMock);

        // Authentifie l'utilisateur
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $parameter = static::$token;
        $crawler = $client->request('GET', static::$projetCosui . $parameter);
        $response = $client->getResponse();

        // Vérifie la réponse HTTP
        $this->assertEquals(200, $response->getStatusCode());

        // Vérifie que le message flash est affiché
        $this->assertCount(1, $crawler->filter('div.callout.alert-callout-border.alert'));
        $this->assertStringContainsString(
            static::$titre . static::$messageErreur500,
            $crawler->filter(static::$filterCalloutMessage)->text()
        );
    }

    public function testCosuiWithReferenceNotFound()
    {
        $client = static::createClient();

        // Mock du repository
        $historiqueRepositoryMock = $this->createMock(HistoriqueRepository::class);

        $historiqueRepositoryMock
            ->method('selectHistoriqueProjetLast')
            ->willReturn([
                'maven_key' => static::$mavenKey,
                'code' => 200,
                'infos' => [
                    [
                        'name' => 'ma-moulinette',
                        'version' => '1.2.3-RELEASE',
                        'date_version' => '2024-08-18 15:54:26',
                        'bug_blocker' => 7,
                        'bug_critical' => 0,
                        'bug_major' => 44,
                        'vulnerability_blocker' => 0,
                        'vulnerability_critical' => 9,
                        'vulnerability_major' => 0,
                        'code_smell_blocker' => 0,
                        'code_smell_critical' => 4,
                        'code_smell_major' => 109,
                        'nombre_hotspot' => 0,
                        'note_reliability' => 'E',
                        'note_security' => 'D',
                        'note_sqale' => 'A',
                        'note_hotspot' => 'A',
                        'coverage' => 50.1,
                        'sqale_debt_ratio' => 1
                    ]
                ]
        ]);

        $historiqueRepositoryMock
        ->method('selectHistoriqueProjetReference')
        ->willReturn([
            'maven_key' => static::$mavenKey,
            'code' => 200,
            'reference' => [] ]);

        // Injecte le mock dans le conteneur
        static::getContainer()->set(HistoriqueRepository::class, $historiqueRepositoryMock);

        // Authentifie l'utilisateur
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $parameter = static::$token;
        $crawler = $client->request('GET', static::$projetCosui . $parameter);
        $response = $client->getResponse();

        // Vérifie la réponse HTTP
        $this->assertEquals(200, $response->getStatusCode());

        // Vérifie que le message flash est affiché
        $this->assertCount(1, $crawler->filter('div.callout.alert-callout-border.alert'));
        $this->assertStringContainsString(
            "[COSUI-002] Vous devez choisir un projet comme référence !",
            $crawler->filter(static::$filterCalloutMessage)->text()
        );
    }

    public function testCosuiWithValidSetup()
    {
        /** on boot le client et on charge le container */
        static::ensureKernelShutdown();
        $client = static::createClient();
        $container = $client->getContainer();

        $manager = $container->get('doctrine')->getManager();

        $this->loadFixturesManuellement($manager);

        $this->assertNotNull(
            $container->get(\App\Repository\UtilisateurRepository::class)
                ->findOneByCourriel(static::$aurelie),
            'Utilisateur "aurelie" non trouvé après le chargement des fixtures.'
        );

        // Mock du repository
        $historiqueRepositoryMock = $this->createMock(HistoriqueRepository::class);
        $historiqueRepositoryMock->method('selectHistoriqueProjetLast')
            ->willReturn([
                'maven_key' => static::$mavenKey,
                'code' => 200,
                'infos' => [
                    [
                        'name' => 'ma-moulinette',
                        'version' => '1.2.3-RELEASE',
                        'date_version' => '2024-08-18 15:54:26',
                        'bug_blocker' => 7,
                        'bug_critical' => 0,
                        'bug_major' => 44,
                        'vulnerability_blocker' => 0,
                        'vulnerability_critical' => 9,
                        'vulnerability_major' => 0,
                        'code_smell_blocker' => 0,
                        'code_smell_critical' => 4,
                        'code_smell_major' => 109,
                        'nombre_hotspot' => 0,
                        'note_reliability' => 'E',
                        'note_security' => 'D',
                        'note_sqale' => 'A',
                        'note_hotspot' => 'A',
                        'coverage' => 50.1,
                        'sqale_debt_ratio' => 1
                    ]
            ]
        ]);

        $historiqueRepositoryMock->method('selectHistoriqueProjetReference')
            ->willReturn([
            'maven_key' => static::$mavenKey,
            'code' => 200,
            'reference' => [
                [
                    'name' => 'ma-moulinette',
                    'version' => '1.2.0-RELEASE',
                    'date_version' => '2024-07-12 16:34:46',
                    'bug_blocker' => 0,
                    'bug_critical' => 1,
                    'bug_major' => 5,
                    'vulnerability_blocker' => 1,
                    'vulnerability_critical' => 14,
                    'vulnerability_major' => 23,
                    'code_smell_blocker' => 56,
                    'code_smell_critical' => 4,
                    'code_smell_major' => 176,
                    'nombre_hotspot' => 4,
                    'note_reliability' => 'B',
                    'note_security' => 'D',
                    'note_hotspot' => 'E',
                    'note_sqale' => 'E',
                    'coverage' => 42.1,
                    'sqale_debt_ratio' => 1
                ]
            ]
        ]);

        // Injecte les mocks dans le conteneur
        $container->set(HistoriqueRepository::class, $historiqueRepositoryMock);

        // Authentifie l'utilisateur
        $userRepository = $container->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $parameter = static::$token;
        $client->request('GET', static::$projetCosui . $parameter);
        $response = $client->getResponse();

        // Vérifie la réponse HTTP
        $this->assertEquals(200, $response->getStatusCode());
        // Vérifier que la valeur de 'setup' est bien injectée
        $render = $client->getRequest()->attributes->get('render');
        $this->assertEquals('1739816022572', $render['setup']);
        }

    /*public function testCosuiFailsWithCollecteCosuiModule()
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Création du mock du service BatchCollecteCosui
        $mockService = $this->createMock(BatchCollecteCosuiController::class);
        $mockService->method('CollecteCosuiModule')->willReturnCallback(
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
        static::getContainer()->set(BatchCollecteCosuiController::class, $mockService);

        $crawler = $client->request('GET', static::$projetCosui.static::$token);
        $response = $client->getResponse();

        // Vérifie que la réponse est un succès
        $this->assertEquals(200, $response->getStatusCode());

        // Vérifie que la réponse contient le message d'erreur attendu
        $this->assertStringContainsString(static::$erreur500, $response->getContent());

        // Vérifie que le message flash est correctement rendu dans la page
        $this->assertCount(1, $crawler->filter('div.callout.alert-callout-border.alert'));
        $this->assertStringContainsString(static::$erreur500, $crawler->filter(static::$filterCalloutMessage)->text());

    }

    public function testCosuiFailSelectOrUpdateCosuiInitial()
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Création du mock du service BatchCollecteCosui
        $mockService = $this->createMock(BatchCollecteCosuiController::class);

        // Mock du repository CosuiRepository
        $mockCosuiRepository = $this->createMock(CosuiRepository::class);
        $mockCosuiRepository->method('selectOrUpdateCosuiInitial')->willReturn(['code' => 500, 'erreur' => 'Debug : Erreur 500.']);

        // Définition des valeurs attendues par type
        $mockService->method('CollecteCosuiModule')->willReturnCallback(
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
        static::getContainer()->set(BatchCollecteCosuiController::class, $mockService);
        static::getContainer()->set(CosuiRepository::class, $mockCosuiRepository);

        $crawler = $client->request('GET', static::$projetCosui.static::$token);
        $response = $client->getResponse();

        // Vérifie que la réponse est un succès
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertResponseIsSuccessful();

        // Vérifie que le message flash est correctement rendu dans la page
        $this->assertCount(1, $crawler->filter('div.callout.alert-callout-border.alert'));
        $this->assertStringContainsString(static::$titre . static::$erreur50x.'Debug : Erreur 500.', $crawler->filter(static::$filterCalloutMessage)->text());
    }

    public function testCosuiSuccess()
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Création du mock du service BatchCollecteCosui
        $mockService = $this->createMock(BatchCollecteCosuiController::class);

        // Mock du repository CosuiRepository
        $mockCosuiRepository = $this->createMock(CosuiRepository::class);
        $mockCosuiRepository->method('selectOrUpdateCosuiInitial')->willReturn(['code' => 200]);

        // Définition des valeurs attendues par type
        $mockService->method('CollecteCosuiModule')->willReturnCallback(
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
        static::getContainer()->set(BatchCollecteCosuiController::class, $mockService);
        static::getContainer()->set(CosuiRepository::class, $mockCosuiRepository);

        $crawler = $client->request('GET', static::$projetCosui.static::$token);
        $response = $client->getResponse();

        // Vérifie que la réponse est un succès
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertResponseIsSuccessful();

        // Vérifie que la page contient les informations nécessaires
        $this->assertStringContainsString(static::$mavenKey, $crawler->filter('h1#js-app')->attr('data-application'));
        $value = $crawler->filter('span#js-setup')->text();
        $this->assertIsNumeric($value);
        $this->assertStringContainsString('1872', $crawler->filter('#nombre-bug')->attr('data-nombre-bug'));
        $this->assertStringContainsString('1430', $crawler->filter('#nombre-vulnerability')->attr('data-nombre-vulnerability'));
        $this->assertStringContainsString('36305', $crawler->filter('#nombre-code-smell')->attr('data-nombre-code-smell'));

    }*/

}
