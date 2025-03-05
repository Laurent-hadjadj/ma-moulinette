<?php

namespace App\Tests\Unit\Controller\Accueil;

use App\Repository\PropertiesRepository;
use App\Repository\ListeProjetRepository;
use App\Repository\UtilisateurRepository;
use App\Controller\Accueil\ApiAccueilController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * [Description ApiAccueilControllerTest]
 */
class ApiAccueilControllerTest extends WebTestCase
{
    private static $contentType = 'application/json';
    private static $http400 = 'Requête invalide (Erreur 400).';
    private static $http401 = 'Non autorisé (Erreur 401).';
    private static $http403 = 'Accès interdit (Erreur 403).';
    private static $http404 = 'Non trouvé (Erreur 404).';
    private static $http500 = 'Une erreur inattendue du serveur s\'est produite (Erreur 500).';
    private static $http503 = 'Service indisponible (Erreur 503).';
    private static $apiStatus = '/api/status';
    private static $apiAccueilProjet = '/api/accueil/projet';
    private static $apiAccueilTags = '/api/accueil/tags';
    private static $josh = 'josh.liberman@ma-moulinette.fr';
    private static $aurelie = 'aurelie.petit-coeur@ma-moulinette.fr';


    public function testApiSonarStatusNominal(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$josh);
        $client->loginUser($testUser);

        // Effectuer la requête
        $client->request('POST', static::$apiStatus);
        $response = $client->getResponse();

        // Vérifier que la réponse est bien un tableau
        $responseData = json_decode($response->getContent(), true);
        $this->assertIsArray($responseData);

        // Si le 'code' dans la réponse est 503, on ignore le test
        if (($responseData['code'] ?? null) === 503) {
            $this->markTestSkipped('SonarQube est indisponible, test ignoré.');
        }

        // Vérifier que le code est 200
        $this->assertEquals(200, $responseData['code'] ?? null, 'Le code devrait être 200 dans le cas nominal.');

        // Vérifications des autres champs
        $this->assertArrayHasKey('code', $responseData);
        $this->assertArrayHasKey('result', $responseData, 'Un résultat est toujours présent.');
        $this->assertArrayHasKey('json', $responseData['result'], 'Un tableau ["json"] devrait toujours être présent.');
        $this->assertEquals('UP', $responseData['result']['json']['status']);
    }

    public function testApiSonarStatusWithFullMock(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);

        // Récupérer l'utilisateur de test
        $testUser = $userRepository->findOneByCourriel(static::$josh);

        // Simuler que l'utilisateur est connecté
        $client->loginUser($testUser);

        // Liste des codes d'erreur et leurs réponses simulées
        $errorCases = [
            400 => static::$http400,
            401 => static::$http401,
            403 => static::$http403,
            500 => static::$http500,
            503 => static::$http503,
        ];

        foreach ($errorCases as $errorCode => $expectedErrorMessage) {
            // Mock de la réponse
            $mockResponse = new JsonResponse([
                'code' => $errorCode,
                'erreur' => $expectedErrorMessage,
            ], 200);

            // Mocker le service ou la méthode utilisée par le contrôleur
            $mockController = $this->createMock(ApiAccueilController::class);
            $mockController->method('apiSonarStatus')->willReturn($mockResponse);

            // Appeler la méthode simulée
            $response = $mockController->apiSonarStatus();

            // Vérifier le contenu de la réponse simulée
            $this->assertEquals(200, $response->getStatusCode());
            $responseData = json_decode($response->getContent(), true);
            $this->assertEquals($errorCode, $responseData['code']);
            $this->assertEquals($expectedErrorMessage, $responseData['erreur']);
        }
    }

    public function testApiSonarStatusWithError400(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);

        // Récupérer l'utilisateur de test
        $testUser = $userRepository->findOneByCourriel(static::$josh);

        // Simuler que l'utilisateur est connecté
        $client->loginUser($testUser);

        // Mock de la réponse pour l'erreur 400
        $mockHttpClient = $this->createMock(\App\Service\Client::class);
        $mockHttpClient->method('httpSonarQube')->willReturn([
            'code' => 400,
            'erreur' => static::$http400,
        ]);

        // Remplacement du service Client dans le conteneur
        static::getContainer()->set(\App\Service\Client::class, $mockHttpClient);

        // Simuler une requête POST
        $client->request('POST', static::$apiStatus);

        // Vérifier la réponse
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(400, $responseData['code']);
        $this->assertEquals(static::$http400, $responseData['erreur']);
    }

    public function testApiSonarStatusWithError401(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);

        // Récupérer l'utilisateur de test
        $testUser = $userRepository->findOneByCourriel(static::$josh);

        // Simuler que l'utilisateur est connecté
        $client->loginUser($testUser);

        // Mock de la réponse pour l'erreur 401
        $mockHttpClient = $this->createMock(\App\Service\Client::class);
        $mockHttpClient->method('httpSonarQube')->willReturn([
            'code' => 401,
            'erreur' => static::$http401,
        ]);

        // Remplacement du service Client dans le conteneur
        static::getContainer()->set(\App\Service\Client::class, $mockHttpClient);

        // Simuler une requête POST
        $client->request('POST', static::$apiStatus);

        // Vérifier la réponse
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(401, $responseData['code']);
        $this->assertEquals(static::$http401, $responseData['erreur']);
    }

    public function testApiSonarStatusWithError404(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);

        // Récupérer l'utilisateur de test
        $testUser = $userRepository->findOneByCourriel(static::$josh);

        // Simuler que l'utilisateur est connecté
        $client->loginUser($testUser);

        // Mock de la réponse pour l'erreur 404
        $mockHttpClient = $this->createMock(\App\Service\Client::class);
        $mockHttpClient->method('httpSonarQube')->willReturn([
            'code' => 404,
            'erreur' => static::$http404,
        ]);

        // Remplacement du service Client dans le conteneur
        static::getContainer()->set(\App\Service\Client::class, $mockHttpClient);

        // Simuler une requête POST
        $client->request('POST', static::$apiStatus);

        // Vérifier la réponse
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(404, $responseData['code']);
        $this->assertEquals(static::$http404, $responseData['erreur']);
    }

    public function testApiSonarStatusWithError500(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);

        // Récupérer l'utilisateur de test
        $testUser = $userRepository->findOneByCourriel(static::$josh);

        // Simuler que l'utilisateur est connecté
        $client->loginUser($testUser);

        // Mock de la réponse pour l'erreur 404
        $mockHttpClient = $this->createMock(\App\Service\Client::class);
        $mockHttpClient->method('httpSonarQube')->willReturn([
            'code' => 500,
            'erreur' => static::$http500,
        ]);

        // Remplacement du service Client dans le conteneur
        static::getContainer()->set(\App\Service\Client::class, $mockHttpClient);

        // Simuler une requête POST
        $client->request('POST', static::$apiStatus);

        // Vérifier la réponse
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(500, $responseData['code']);
        $this->assertEquals(static::$http500, $responseData['erreur']);
    }

    public function testApiSonarStatusWithError503(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);

        // Récupérer l'utilisateur de test
        $testUser = $userRepository->findOneByCourriel(static::$josh);

        // Simuler que l'utilisateur est connecté
        $client->loginUser($testUser);

        // Mock de la réponse pour l'erreur 404
        $mockHttpClient = $this->createMock(\App\Service\Client::class);
        $mockHttpClient->method('httpSonarQube')->willReturn([
            'code' => 503,
            'erreur' => static::$http503,
        ]);

        // Remplacement du service Client dans le conteneur
        static::getContainer()->set(\App\Service\Client::class, $mockHttpClient);

        // Simuler une requête POST
        $client->request('POST', static::$apiStatus);

        // Vérifier la réponse
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(503, $responseData['code']);
        $this->assertEquals(static::$http503, $responseData['erreur']);
    }

    public function testAccueilProjetListeNominal(): void
    {
        // Création du client
        $client = static::createClient();

        // Simuler un utilisateur avec le rôle ROLE_COLLECTE
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Simuler une requête POST vers l'API
        $client->request('POST', static::$apiAccueilProjet, [], [], [
            'CONTENT_TYPE' => static::$contentType,
        ]);

        // Vérification que la réponse est un JSON valide avec un code 200
        $response = $client->getResponse();
        $this->assertResponseIsSuccessful();
        $this->assertJson($response->getContent());

        // Décoder la réponse
        $responseData = json_decode($response->getContent(), true);

        // Si le 'code' dans la réponse est 503, on ignore le test
        if (($responseData['code'] ?? null) === 503) {
            $this->markTestSkipped('SonarQube est indisponible, test ignoré.');
        }

        $this->assertEquals(200, $responseData['code'], 'Le code de réponse devrait être 200.');
        $this->assertEquals('success', $responseData['type']);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertArrayHasKey('nombre', $responseData);
        $this->assertArrayHasKey('public', $responseData);
        $this->assertArrayHasKey('private', $responseData);
        $this->assertArrayHasKey('empty_tags', $responseData);

        // Vérification des données spécifiques
        $this->assertGreaterThan(0, $responseData['nombre'], 'Le nombre de projets devrait être supérieur à 0.');
        $this->assertGreaterThanOrEqual(0, $responseData['public'], 'Le nombre de projets publics devrait être >= 0.');
        $this->assertGreaterThanOrEqual(0, $responseData['private'], 'Le nombre de projets privés devrait être >= 0.');
        $this->assertGreaterThanOrEqual(0, $responseData['empty_tags'], 'Le nombre de projets sans tags devrait être >= 0.');
    }

    public function testApiAccueilProjetPublic(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Mock du ListeProjetRepository
        $mockListeProjetRepository = $this->createMock(ListeProjetRepository::class);
        $mockListeProjetRepository->method('deleteListeProjet')
            ->willReturn(['code' => 200 ]);

        // Remplacer le service ListeProjetRepository dans le conteneur
        static::getContainer()->set(ListeProjetRepository::class, $mockListeProjetRepository);

        // Mock du client HTTP pour simuler une réponse SonarQube valide
        $mockHttpClient = $this->createMock(\App\Service\Client::class);
        $mockHttpClient->method('httpSonarQube')->willReturn([
            'code' => 200,
            'json' => [
                'components' => [
                    ['key' =>  'fr.ma-petite-entreprise:ma-moulinette',
                    'name' => 'ma-moulinette',
                    'tags' => [],
                    'visibility' => 'public']
                ]
            ],
        ]);
        static::getContainer()->set(\App\Service\Client::class, $mockHttpClient);

        // Simuler une requête POST vers l'API
        $client->request('POST', static::$apiAccueilProjet);

        // Vérifier que la réponse est un succès HTTP
        $this->assertResponseIsSuccessful();

        // Décoder la réponse JSON
        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(200, $responseData['code'], 'Le code de réponse devrait être 200.');
        $this->assertEquals('success', $responseData['type']);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertArrayHasKey('nombre', $responseData);
        $this->assertArrayHasKey('public', $responseData);
        $this->assertArrayHasKey('private', $responseData);
        $this->assertArrayHasKey('empty_tags', $responseData);

        // Vérification des données spécifiques
        $this->assertGreaterThanOrEqual(1, $responseData['nombre']);
        $this->assertGreaterThanOrEqual(1, $responseData['public'],);
        $this->assertGreaterThanOrEqual(0, $responseData['private']);
        $this->assertGreaterThanOrEqual(0, $responseData['empty_tags']);
    }

    public function testAccueilProjetListeNominalForbiddenError(): void
    {
        // Création du client
        $client = static::createClient();

        // Simuler un utilisateur avec le rôle ROLE_COLLECTE
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$josh);
        $client->loginUser($testUser);

        // Simuler une requête POST vers l'API
        $client->request('POST', static::$apiAccueilProjet, [], [], [
            'CONTENT_TYPE' => static::$contentType,
        ]);

        // Vérification que la réponse est un JSON valide avec un code 200
        $response = $client->getResponse();
        $this->assertResponseIsSuccessful();
        $this->assertJson($response->getContent());

        // Décoder la réponse
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals(403, $responseData['code'], 'Le code de réponse devrait être 403.');
        $this->assertEquals('warning', $responseData['type']);
        $this->assertEquals('[Accueil]', $responseData['reference']);
        $this->assertEquals('Vous devez avoir le rôle COLLECTE pour réaliser cette action (Erreur 403).', $responseData['message']);
    }

    public function testAccueilProjetListeWithError400(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);

        // Récupérer l'utilisateur de test
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);

        // Simuler que l'utilisateur est connecté
        $client->loginUser($testUser);

        // Mock de la réponse pour l'erreur 400
        $mockHttpClient = $this->createMock(\App\Service\Client::class);
        $mockHttpClient->method('httpSonarQube')->willReturn([
            'code' => 400,
            'erreur' => static::$http400,
        ]);

        // Remplacement du service Client dans le conteneur
        static::getContainer()->set(\App\Service\Client::class, $mockHttpClient);

        // Simuler une requête POST
        $client->request('POST', static::$apiAccueilProjet);

        // Vérifier la réponse
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('alert', $responseData['type']);
        $this->assertEquals(400, $responseData['code']);
        $this->assertEquals(static::$http400, $responseData['message']);
        $this->assertEquals(ApiAccueilController::$reference, $responseData['reference']);
    }

    public function testAccueilProjetListeWithError401(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);

        // Récupérer l'utilisateur de test
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);

        // Simuler que l'utilisateur est connecté
        $client->loginUser($testUser);

        // Mock de la réponse pour l'erreur 401
        $mockHttpClient = $this->createMock(\App\Service\Client::class);
        $mockHttpClient->method('httpSonarQube')->willReturn([
            'code' => 401,
            'erreur' => static::$http401,
        ]);

        // Remplacement du service Client dans le conteneur
        static::getContainer()->set(\App\Service\Client::class, $mockHttpClient);

        // Simuler une requête POST
        $client->request('POST', static::$apiAccueilProjet);

        // Vérifier la réponse
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(401, $responseData['code']);
        $this->assertEquals(static::$http401, $responseData['message']);
        $this->assertEquals('alert', $responseData['type']);
        $this->assertEquals(ApiAccueilController::$reference, $responseData['reference']);
    }

    public function testAccueilProjetListeWithError404(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);

        // Récupérer l'utilisateur de test
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);

        // Simuler que l'utilisateur est connecté
        $client->loginUser($testUser);

        // Mock de la réponse pour l'erreur 404
        $mockHttpClient = $this->createMock(\App\Service\Client::class);
        $mockHttpClient->method('httpSonarQube')->willReturn([
            'code' => 404,
            'erreur' => static::$http404,
        ]);

        // Remplacement du service Client dans le conteneur
        static::getContainer()->set(\App\Service\Client::class, $mockHttpClient);

        // Simuler une requête POST
        $client->request('POST', static::$apiAccueilProjet);

        // Vérifier la réponse
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(404, $responseData['code']);
        $this->assertEquals(static::$http404, $responseData['message']);
        $this->assertEquals('alert', $responseData['type']);
        $this->assertEquals(ApiAccueilController::$reference, $responseData['reference']);
    }

    public function testAccueilProjetListeWithError500(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);

        // Récupérer l'utilisateur de test
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);

        // Simuler que l'utilisateur est connecté
        $client->loginUser($testUser);

        // Mock de la réponse pour l'erreur 404
        $mockHttpClient = $this->createMock(\App\Service\Client::class);
        $mockHttpClient->method('httpSonarQube')->willReturn([
            'code' => 500,
            'erreur' => static::$http500,
        ]);

        // Remplacement du service Client dans le conteneur
        static::getContainer()->set(\App\Service\Client::class, $mockHttpClient);

        // Simuler une requête POST
        $client->request('POST', static::$apiAccueilProjet);

        // Vérifier la réponse
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(500, $responseData['code']);
        $this->assertEquals(static::$http500, $responseData['message']);
        $this->assertEquals('alert', $responseData['type']);
        $this->assertEquals(ApiAccueilController::$reference, $responseData['reference']);
    }

    public function testAccueilProjetListeWithError503(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);

        // Récupérer l'utilisateur de test
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);

        // Simuler que l'utilisateur est connecté
        $client->loginUser($testUser);

        // Mock de la réponse pour l'erreur 404
        $mockHttpClient = $this->createMock(\App\Service\Client::class);
        $mockHttpClient->method('httpSonarQube')->willReturn([
            'code' => 503,
            'erreur' => static::$http503,
        ]);

        // Remplacement du service Client dans le conteneur
        static::getContainer()->set(\App\Service\Client::class, $mockHttpClient);

        // Simuler une requête POST
        $client->request('POST', static::$apiAccueilProjet);

        // Vérifier la réponse
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(503, $responseData['code']);
        $this->assertEquals(static::$http503, $responseData['message']);
        $this->assertEquals('alert', $responseData['type']);
        $this->assertEquals(ApiAccueilController::$reference, $responseData['reference']);
    }

    public function testApiAccueilProjetWithSonarNoProjects(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Mock de la réponse HTTP pour le service Client
        $mockHttpClient = $this->createMock(\App\Service\Client::class);
        $mockHttpClient->method('httpSonarQube')->willReturn([
            'code' => 200,
            'json' => ['components' => []]
        ]);

        // Remplacement du service Client dans le conteneur
        static::getContainer()->set(\App\Service\Client::class, $mockHttpClient);

        // Simuler une requête POST vers l'API
        $client->request('POST', static::$apiAccueilProjet);

        // Vérifier que la réponse est un succès HTTP (car Response::HTTP_OK est utilisé)
        $this->assertResponseIsSuccessful();

        // Décoder la réponse JSON
        $responseData = json_decode($client->getResponse()->getContent(), true);

        // Vérifier le contenu de la réponse
        $this->assertEquals('warning', $responseData['type']);
        $this->assertEquals(ApiAccueilController::$reference, $responseData['reference']);
        $this->assertEquals(404, $responseData['code']);
        $this->assertEquals(ApiAccueilController::$erreur404, $responseData['message']);
    }

    public function testApiAccueilProjetWithDeleteListeProjetError(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Mock du ListeProjetRepository
        $mockListeProjetRepository = $this->createMock(ListeProjetRepository::class);
        $mockListeProjetRepository->method('deleteListeProjet')->willReturn([
            'code' => 500,
            'erreur' => 'Impossible de supprimer les données existantes.',
        ]);

        // Remplacer le service ListeProjetRepository dans le conteneur
        static::getContainer()->set(ListeProjetRepository::class, $mockListeProjetRepository);

        // Mock du client HTTP pour simuler une réponse SonarQube valide
        $mockHttpClient = $this->createMock(\App\Service\Client::class);
        $mockHttpClient->method('httpSonarQube')->willReturn([
            'code' => 200,
            'json' => [
                'components' => [
                    ['key' =>  'fr.xoxo:tetris',
                    'name' => 'tetris',
                    'tags' => [],
                    'visibility' => 'private']
                ]
            ],
        ]);
        static::getContainer()->set(\App\Service\Client::class, $mockHttpClient);

        // Simuler une requête POST vers l'API
        $client->request('POST', static::$apiAccueilProjet);

        // Vérifier que la réponse est un succès HTTP
        $this->assertResponseIsSuccessful();

        // Décoder la réponse JSON
        $responseData = json_decode($client->getResponse()->getContent(), true);

        // Vérifier le contenu de la réponse
        $this->assertEquals('alert', $responseData['type']);
        $this->assertEquals(ApiAccueilController::$reference, $responseData['reference']);
        $this->assertEquals(500, $responseData['code']);
        $this->assertEquals('Impossible de supprimer les données existantes.', $responseData['message']);
    }

    public function testApiAccueilProjetWithUpdatePropertiesError(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);

        // Récupérer l'utilisateur de test avec le rôle adéquat
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);

        // Simuler que l'utilisateur est connecté
        $client->loginUser($testUser);

        // Mock du PropertiesRepository
        $mockPropertiesRepository = $this->createMock(PropertiesRepository::class);
        $mockPropertiesRepository->method('updatePropertiesProjet')->willReturn([
            'code' => 500,
            'erreur' => 'Une erreur est survenue lors de la mise à jour des propriétés.',
        ]);

        // Remplacer le service PropertiesRepository dans le conteneur
        static::getContainer()->set(PropertiesRepository::class, $mockPropertiesRepository);

        // Mock de la réponse HTTP pour le client SonarQube
        $mockHttpClient = $this->createMock(\App\Service\Client::class);
        $mockHttpClient->method('httpSonarQube')->willReturn([
            'code' => 200,
            'json' => [
                'components' => [
                    ['key' =>  'fr.xoxo:tetris',
                    'name' => 'tetris',
                    'tags' => [],
                    'visibility' => 'private']
                ]
            ]]);
        static::getContainer()->set(\App\Service\Client::class, $mockHttpClient);

        // Simuler une requête POST vers l'API
        $client->request('POST', static::$apiAccueilProjet);

        // Vérifier que la réponse est un succès HTTP
        $this->assertResponseIsSuccessful();

        // Décoder la réponse JSON
        $responseData = json_decode($client->getResponse()->getContent(), true);

        // Vérifier le contenu de la réponse
        $this->assertEquals('alert', $responseData['type']);
        $this->assertEquals(ApiAccueilController::$reference, $responseData['reference']);
        $this->assertEquals(500, $responseData['code']);
        $this->assertEquals('Une erreur est survenue lors de la mise à jour des propriétés.', $responseData['message']);
    }

    public function testApiAccueilProjetTags(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Mock du ListeProjetRepository
        $mockListeProjetRepository = $this->createMock(ListeProjetRepository::class);
        $mockListeProjetRepository->method('countListeProjetTags')->willReturn([
            'code' => 200,
            'nombre' => [['tag' => 42]],
        ]);

        // Remplacer le service ListeProjetRepository dans le conteneur
        static::getContainer()->set(ListeProjetRepository::class, $mockListeProjetRepository);

        // Simuler une requête POST vers l'API
        $client->request('POST', static::$apiAccueilTags);

        // Vérifier que la réponse est un succès HTTP
        $this->assertResponseIsSuccessful();

        // Décoder la réponse JSON
        $responseData = json_decode($client->getResponse()->getContent(), true);
        // Vérifier le contenu de la réponse
        $this->assertEquals(200, $responseData['code']);
        $this->assertEquals(42, $responseData['nombre_tag']);
    }

    public function testApiAccueilProjetTagsError(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Mock du ListeProjetRepository
        $mockListeProjetRepository = $this->createMock(ListeProjetRepository::class);
        $mockListeProjetRepository->method('countListeProjetTags')->willReturn([
            'code' => 500,
            'erreur' => 'Une erreur inattendue est survenue, Lol !']);

        // Remplacer le service ListeProjetRepository dans le conteneur
        static::getContainer()->set(ListeProjetRepository::class, $mockListeProjetRepository);

        // Simuler une requête POST vers l'API
        $client->request('POST', static::$apiAccueilTags);

        // Vérifier que la réponse est un succès HTTP
        $this->assertResponseIsSuccessful();

        // Décoder la réponse JSON
        $responseData = json_decode($client->getResponse()->getContent(), true);
        // Vérifier le contenu de la réponse
        $this->assertEquals(500, $responseData['code']);
        $this->assertEquals('alert', $responseData['type']);
        $this->assertEquals(ApiAccueilController::$reference, $responseData['reference']);
        $this->assertEquals('Une erreur inattendue est survenue, Lol !', $responseData['message']);
    }

    public function testApiAccueilProjetTagsForbidden(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        // Récupérer un utilisateur sans le rôle `ROLE_COLLECTE`
        $testUser = $userRepository->findOneByCourriel(static::$josh);
        $client->loginUser($testUser);

        // Simuler une requête POST vers l'API
        $client->request('POST', static::$apiAccueilTags);

        // Vérifier que la réponse est un succès HTTP
        $this->assertResponseIsSuccessful();

        // Décoder la réponse JSON
        $responseData = json_decode($client->getResponse()->getContent(), true);

        // Vérifier le contenu de la réponse
        $this->assertEquals('warning', $responseData['type']);
        $this->assertEquals(403, $responseData['code']);
        $this->assertEquals(ApiAccueilController::$reference, $responseData['reference']);
        $this->assertEquals(ApiAccueilController::$erreur403, $responseData['message']);
    }
}
