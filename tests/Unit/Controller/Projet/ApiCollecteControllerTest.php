<?php

namespace App\Tests\Unit\Controller\Projet;


use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Controller\Batch\BatchCollecteNoteController;
use App\Controller\Batch\BatchCollecteTodoController;
use App\Controller\Batch\BatchCollecteOwaspController;
use App\Controller\Batch\BatchCollecteLoggerController;
use App\Controller\Batch\BatchCollecteMesureController;
use App\Controller\Batch\BatchCollecteHotspotController;
use App\Controller\Batch\BatchCollecteNoSonarController;
use App\Controller\Batch\BatchCollecteActuatorController;
use App\Controller\Batch\BatchCollecteAnomalieController;
use App\Controller\Batch\BatchCollecteHotspotOwaspController;
use App\Controller\Batch\BatchCollecteHotspotDetailController;
use App\Controller\Batch\BatchCollecteAnomalieDetailController;
use App\Controller\Batch\BatchCollecteInformationProjetController;

/**
 * [Description ApiProjetControllerTest]
 */
class ApiCollecteControllerTest extends WebTestCase
{
    private static $contentType = 'application/json';
    private static $aurelie = 'aurelie.petit-coeur@ma-moulinette.fr';
    private static $josh = 'josh.liberman@ma-moulinette.fr';
    private static $leChat = 'fr.ma-petite-entreprise:le-chat';

    private static $apiCollecteInformation = '/api/collecte/information';
    private static $apiCollecteMesure = '/api/collecte/mesure';
    private static $apiCollecteNote = '/api/collecte/note';
    private static $apiCollecteOwasp = '/api/collecte/owasp';
    private static $apiCollecteHotspot = '/api/collecte/hotspot';
    private static $apiCollecteAnomalie = '/api/collecte/anomalie';
    private static $apiCollecteAnomalieDetail = '/api/collecte/anomalie/detail';
    private static $apiCollecteHotspotOwasp = '/api/collecte/hotspot/owasp';
    private static $apiCollecteHotspotDetail = '/api/collecte/hotspot/detail';
    private static $apiCollecteNoSonar = '/api/collecte/nosonar';
    private static $apiCollecteTodo = '/api/collecte/todo';
    private static $apiCollecteActuatorInfo = '/api/collecte/actuator/info';
    private static $apiCollecteLogger = '/api/collecte/logger';

    private static $appControllerBatchBatchCollecteNoteController = 'App\Controller\Batch\BatchCollecteNoteController';

    private static $message400 = '<strong>[Collecte]</strong> La requête est incorrecte (Erreur 400).';
    private static $message403 = "<strong>[Collecte]</strong> Vous devez avoir le rôle COLLECTE pour réaliser cette action (Erreur 403).";
    private static $message404 = "<strong>[Collecte]</strong> Le projet n'existe pas sur le serveur SonarQube (Erreur 404).";
    private static $http404 = "<strong>[Collecte]</strong> Erreur 404 - Le service n'a pas trouvé les éléments.";
    private static $messageLight404 = "<strong>[Collecte]</strong> Aucune information n'a été trouvée (Erreur 404).";
    private static $message500 = "<strong>[Collecte]</strong> Je n'ai pas trouvé d'analyse (Erreur 500).";
    private static $messageLight500 = "Je n'ai pas trouvé d'analyse (Erreur 500).";

    public function testApiCollecteInformationDataNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Envoyer une requête POST avec un body null
        $client->request('POST', static::$apiCollecteInformation, [], [], [static::$contentType], null);

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('data', $jsonResponse);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEmpty($jsonResponse['data']);
        $this->assertEquals(400, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message400, $jsonResponse['message']);
    }

    public function testApiCollecteInformationMavenKeyNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $data = ['some_key' => null ];

        // Envoyer une requête POST avec une clé différente de maven_key
        $client->request('POST', static::$apiCollecteInformation, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('data', $jsonResponse);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertArrayNotHasKey('maven_key', $jsonResponse['data']);
        $this->assertEquals(400, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message400, $jsonResponse['message']);
    }

    public function testApiCollecteInformationSansRoleCollecte(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$josh);
        $client->loginUser($testUser);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiCollecteInformation, [], [], ['CONTENT_TYPE' => static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(403, $jsonResponse['code']);
        $this->assertEquals('warning', $jsonResponse['type']);
        $this->assertEquals(static::$message403, $jsonResponse['message']);
    }

    public function testApiCollecteInformationBatchCollecte404Error(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiCollecteInformation, [], [], ['CONTENT_TYPE' => static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(404, $jsonResponse['code']);
        $this->assertEquals('warning', $jsonResponse['type']);
        $this->assertEquals(static::$message404, $jsonResponse['message']);
    }

    public function testApiCollecteInformationBatchCollectSuccess(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Créer un mock de l'objet batchCollecteInformation
        $batchCollecteInformationMock = $this->createMock(BatchCollecteInformationProjetController::class);

        // Définir le comportement attendu de la méthode batchCollecteInformation
        $batchCollecteInformationMock->method('batchCollecteInformation')
            ->with(static::$leChat, 'COLLECTE', static::$aurelie)
            ->willReturn(['code' => 200, 'message' =>
                [
                'projet' => '2.7.0-RELEASE',
                'release' => 1,
                'snapshot' => 0,
                'autre' => 0,
                'version_sonar' => 59,
                'version_release_sonar' => 52,
                'version_snapshot_sonar' => 4,
                'version_autre_sonar' => 3
                ]]);
        $client->getContainer()->set('App\Controller\Batch\BatchCollecteInformationProjetController', $batchCollecteInformationMock);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiCollecteInformation, [], [], ['CONTENT_TYPE' => static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);
        $this->assertArrayHasKey('projet', $jsonResponse['message']);
        $this->assertArrayHasKey('release', $jsonResponse['message']);
        $this->assertArrayHasKey('snapshot', $jsonResponse['message']);
        $this->assertArrayHasKey('autre', $jsonResponse['message']);

        $this->assertEquals(200, $jsonResponse['code']);
        $this->assertEquals('2.7.0-RELEASE', $jsonResponse['message']['projet']);
        $this->assertEquals('1', $jsonResponse['message']['release']);
        $this->assertEquals('0', $jsonResponse['message']['snapshot']);
        $this->assertEquals('0', $jsonResponse['message']['autre']);
        $this->assertEquals('59', $jsonResponse['message']['total_sonar']);
        $this->assertEquals('52', $jsonResponse['message']['release_sonar']);
        $this->assertEquals('4', $jsonResponse['message']['snapshot_sonar']);
        $this->assertEquals('3', $jsonResponse['message']['autre_sonar']);
    }

    public function testApiCollecteMesureDataNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Envoyer une requête POST avec un body null
        $client->request('POST', static::$apiCollecteMesure, [], [], [static::$contentType], null);

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('data', $jsonResponse);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEmpty($jsonResponse['data']);
        $this->assertEquals(400, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message400, $jsonResponse['message']);
    }

    public function testApiCollecteMesureMavenKeyNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $data = ['some_key' => null ];

        // Envoyer une requête POST avec une clé différente de maven_key
        $client->request('POST', static::$apiCollecteMesure, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('data', $jsonResponse);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertArrayNotHasKey('maven_key', $jsonResponse['data']);
        $this->assertEquals(400, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message400, $jsonResponse['message']);
    }

    public function testApiCollecteMesureSansRoleCollecte(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$josh);
        $client->loginUser($testUser);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiCollecteMesure, [], [], ['CONTENT_TYPE' => static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(403, $jsonResponse['code']);
        $this->assertEquals('warning', $jsonResponse['type']);
        $this->assertEquals(static::$message403, $jsonResponse['message']);
    }

    public function testApiCollecteMesureBatchCollecte404Error(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiCollecteMesure, [], [], ['CONTENT_TYPE' => static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(404, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$http404, $jsonResponse['message']);
    }

    public function testApiCollectMesureBatchCollectSuccess(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Créer un mock de l'objet batchCollecteMesure
        $batchCollecteMesureMock = $this->createMock(BatchCollecteMesureController::class);

        // Définir le comportement attendu de la méthode batchCollecteMesure
        $batchCollecteMesureMock->method('batchCollecteMesure')
            ->with(static::$leChat, 'COLLECTE', static::$aurelie)
            ->willReturn(['code' => 200, 'message' =>
                [
                    'maven_key' => static::$leChat,
                    'project_name' => 'le-chat',
                    'lines' => 132661,
                    'ncloc' => 54690,
                    'classes' => 664,
                    'functions' => 1888,
                    'files' => 1456,
                    'language_distribution' =>
                        [
                            'java' => 41177,
                            'web' => 7813,
                            'css' => 4268,
                            'xml' => 1329,
                            'json' => 103,
                        ],
                    'sqale_debt_ratio' => 8.7,
                    'coverage' => 48.1,
                    'duplicated_lines_density' => 1.3,
                    'tests' => 763,
                    'issues' => 8637,
                ]]);
        $client->getContainer()->set('App\Controller\Batch\BatchCollecteMesureController', $batchCollecteMesureMock);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiCollecteMesure, [], [], ['CONTENT_TYPE' => static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertEquals(200, $jsonResponse['code']);
        $this->assertEquals(static::$leChat, $jsonResponse['message']['maven_key']);
        $this->assertEquals('le-chat', $jsonResponse['message']['project_name']);
        $this->assertEquals(132661, $jsonResponse['message']['lines']);
        $this->assertEquals(54690, $jsonResponse['message']['ncloc']);
        $this->assertEquals(664, $jsonResponse['message']['classes']);
        $this->assertEquals(1888, $jsonResponse['message']['functions']);
        $this->assertEquals(1456, $jsonResponse['message']['files']);
        $this->assertEquals([
            'java' => '41177',
            'web' => '7813',
            'css' => '4268',
            'xml' => '1329',
            'json' => '103',
        ], $jsonResponse['message']['language_distribution']);
        $this->assertEquals(8.7, $jsonResponse['message']['sqale_debt_ratio']);
        $this->assertEquals('48.1', $jsonResponse['message']['coverage']);
        $this->assertEquals('1.3', $jsonResponse['message']['duplicated_lines_density']);
        $this->assertEquals(763, $jsonResponse['message']['tests']);
        $this->assertEquals(8637, $jsonResponse['message']['issues']);
    }

    public function testApiCollecteNoteDataNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Envoyer une requête POST avec un body null
        $client->request('POST', static::$apiCollecteNote, [], [], [static::$contentType], null);

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('data', $jsonResponse);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEmpty($jsonResponse['data']);
        $this->assertEquals(400, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message400, $jsonResponse['message']);
    }

    public function testApiCollecteNoteMavenKeyNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $data = ['some_key' => null ];

        // Envoyer une requête POST avec une clé différente de maven_key
        $client->request('POST', static::$apiCollecteNote, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('data', $jsonResponse);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertArrayNotHasKey('maven_key', $jsonResponse['data']);
        $this->assertEquals(400, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message400, $jsonResponse['message']);
    }

    public function testApiCollecteNoteTypeNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $data = ['maven_key' => static::$leChat ];

        // Envoyer une requête POST avec une clé type null
        $client->request('POST', static::$apiCollecteNote, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('data', $jsonResponse);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);
        $this->assertArrayHasKey('maven_key', $jsonResponse['data']);
        $this->assertArrayNotHasKey('type', $jsonResponse['data']);

        $this->assertEquals(400, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message400, $jsonResponse['message']);
    }

    public function testApiCollecteNoteTypeNoteFound(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $data = ['maven_key' => static::$leChat, 'type' => 'some-type' ];

        // Envoyer une requête POST avec une clé type null
        $client->request('POST', static::$apiCollecteNote, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('data', $jsonResponse);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);
        $this->assertArrayHasKey('maven_key', $jsonResponse['data']);
        $this->assertArrayHasKey('type', $jsonResponse['data']);

        $this->assertEquals(400, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message400, $jsonResponse['message']);
    }

    public function testApiCollecteNoteSansRoleCollecte(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$josh);
        $client->loginUser($testUser);

        $data = ['maven_key' => static::$leChat, 'type' => 'security'];
        $client->request('POST', static::$apiCollecteNote, [], [], ['CONTENT_TYPE' => static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(403, $jsonResponse['code']);
        $this->assertEquals('warning', $jsonResponse['type']);
        $this->assertEquals(static::$message403, $jsonResponse['message']);
    }

    public function testApiCollecteNoteBatchCollecte404Error(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $data = ['maven_key' => static::$leChat, 'type' => 'reliability' ];
        // Envoyer une requête POST avec un projet qui n'existe pas
        $client->request('POST', static::$apiCollecteNote, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(404, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$http404, $jsonResponse['message']);
    }

    public function testApiCollectNoteBatchCollectReliabilitySuccess(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Créer un mock de l'objet batchCollecteNote
        $batchCollecteNoteMock = $this->createMock(BatchCollecteNoteController::class);

        // Définir le comportement attendu de la méthode batchCollecteNote
        $batchCollecteNoteMock->method('batchCollecteNote')
            ->with(static::$leChat, 'COLLECTE', static::$aurelie)
            ->willReturn(['code' => 200, 'type' => 'reliability', 'message' =>
                [
                    'value' => 'C',
                ]]);
        $client->getContainer()->set(static::$appControllerBatchBatchCollecteNoteController, $batchCollecteNoteMock);

        $data = ['maven_key' => static::$leChat, 'type' => 'reliability'];
        $client->request('POST', static::$apiCollecteNote, [], [], ['CONTENT_TYPE' => static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);
        $this->assertEquals(200, $jsonResponse['code']);
        $this->assertEquals('reliability', $jsonResponse['type']);
        $this->assertEquals('C', $jsonResponse['message']['note']);
    }

    public function testApiCollectNoteBatchCollectSecuritySuccess(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Créer un mock de l'objet batchCollecteNote
        $batchCollecteNoteMock = $this->createMock(BatchCollecteNoteController::class);

        // Définir le comportement attendu de la méthode batchCollecteNote
        $batchCollecteNoteMock->method('batchCollecteNote')
            ->with(static::$leChat, 'COLLECTE', static::$aurelie)
            ->willReturn(['code' => 200, 'type' => 'security', 'message' =>
                [
                    'value' => 'A',
                ]]);
        $client->getContainer()->set(static::$appControllerBatchBatchCollecteNoteController, $batchCollecteNoteMock);

        $data = ['maven_key' => static::$leChat, 'type' => 'security'];
        $client->request('POST', static::$apiCollecteNote, [], [], ['CONTENT_TYPE' => static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(200, $jsonResponse['code']);
        $this->assertEquals('security', $jsonResponse['type']);
        $this->assertEquals('A', $jsonResponse['message']['note']);
    }

    public function testApiCollectNoteBatchCollectSqaleSuccess(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Créer un mock de l'objet batchCollecteNote
        $batchCollecteNoteMock = $this->createMock(BatchCollecteNoteController::class);

        // Définir le comportement attendu de la méthode batchCollecteNote
        $batchCollecteNoteMock->method('batchCollecteNote')
            ->with(static::$leChat, 'COLLECTE', static::$aurelie)
            ->willReturn(['code' => 200, 'type' => 'sqale', 'message' =>
                [
                    'value' => 'B',
                ]]);
        $client->getContainer()->set(static::$appControllerBatchBatchCollecteNoteController, $batchCollecteNoteMock);

        $data = ['maven_key' => static::$leChat, 'type' => 'sqale'];
        $client->request('POST', static::$apiCollecteNote, [], [], ['CONTENT_TYPE' => static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(200, $jsonResponse['code']);
        $this->assertEquals('sqale', $jsonResponse['type']);
        $this->assertEquals('B', $jsonResponse['message']['note']);
    }

    public function testApiCollecteOwaspDataNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Envoyer une requête POST avec un body null
        $client->request('POST', static::$apiCollecteOwasp, [], [], [static::$contentType], null);

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('data', $jsonResponse);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEmpty($jsonResponse['data']);
        $this->assertEquals(400, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message400, $jsonResponse['message']);
    }

    public function testApiCollecteOwaspMavenKeyNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $data = ['some_key' => null ];

        // Envoyer une requête POST avec une clé différente de maven_key
        $client->request('POST', static::$apiCollecteOwasp, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('data', $jsonResponse);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertArrayNotHasKey('maven_key', $jsonResponse['data']);
        $this->assertEquals(400, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message400, $jsonResponse['message']);
    }

    public function testApiCollecteOwaspSansRoleCollecte(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$josh);
        $client->loginUser($testUser);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiCollecteOwasp, [], [], ['CONTENT_TYPE' => static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(403, $jsonResponse['code']);
        $this->assertEquals('warning', $jsonResponse['type']);
        $this->assertEquals(static::$message403, $jsonResponse['message']);
    }

    public function testApiCollectOwaspBatchCollectError(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $batchCollecteOwaspMock = $this->createMock(BatchCollecteOwaspController::class);
        $batchCollecteOwaspMock->method('batchCollecteOwasp')
            ->willReturn(['code' => 500, 'type' => 'alert', 'message' => static::$messageLight500]);

        // Remplace le service dans le conteneur de Symfony
        static::getContainer()->set(BatchCollecteOwaspController::class, $batchCollecteOwaspMock);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiCollecteOwasp, [], [], ['CONTENT_TYPE' => static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(500, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message500, $jsonResponse['message']);
    }

    public function testApiCollectOwaspBatchCollectSuccess(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $batchCollecteOwaspMock = $this->createMock(BatchCollecteOwaspController::class);
        $batchCollecteOwaspMock->method('batchCollecteOwasp')
            ->willReturn(['code' => 200,
                            'owasp2017' => 2,
                            'owasp2021' => 5,
                            'message' => [
                                'Nombre de faille OWASP 2017 : ' => 2,
                                'Nombre de faille OWASP 2021 : ' => 5
                                ]
                            ]);

        // Remplace le service dans le conteneur de Symfony
        static::getContainer()->set(BatchCollecteOwaspController::class, $batchCollecteOwaspMock);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiCollecteOwasp, [], [], ['CONTENT_TYPE' => static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('owasp2017', $jsonResponse);
        $this->assertArrayHasKey('owasp2021', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(200, $jsonResponse['code']);
        $this->assertEquals('2', $jsonResponse['owasp2017']);
        $this->assertEquals('5', $jsonResponse['owasp2021']);
        $this->assertEquals('2', $jsonResponse['message']['Nombre de faille OWASP 2017 : ']);
        $this->assertEquals('5', $jsonResponse['message']['Nombre de faille OWASP 2021 : ']);
    }

    public function testApiCollecteHotspotDataNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Envoyer une requête POST avec un body null
        $client->request('POST', static::$apiCollecteHotspot, [], [], [static::$contentType], null);

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('data', $jsonResponse);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEmpty($jsonResponse['data']);
        $this->assertEquals(400, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message400, $jsonResponse['message']);
    }

    public function testApiCollecteHotspotMavenKeyNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $data = ['some_key' => null ];

        // Envoyer une requête POST avec une clé différente de maven_key
        $client->request('POST', static::$apiCollecteHotspot, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('data', $jsonResponse);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertArrayNotHasKey('maven_key', $jsonResponse['data']);
        $this->assertEquals(400, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message400, $jsonResponse['message']);
    }

    public function testApiCollecteHotspotSansRoleCollecte(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$josh);
        $client->loginUser($testUser);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiCollecteHotspot, [], [], ['CONTENT_TYPE' => static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(403, $jsonResponse['code']);
        $this->assertEquals('warning', $jsonResponse['type']);
        $this->assertEquals(static::$message403, $jsonResponse['message']);
    }

    public function testApiCollectHotspotCollectError(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $batchCollecteHotspotMock = $this->createMock(BatchCollecteHotspotController::class);
        $batchCollecteHotspotMock->method('batchCollecteHotspot')
            ->willReturn(['code' => 500, 'type' => 'alert', 'message' => static::$messageLight500]);

        // Remplace le service dans le conteneur de Symfony
        static::getContainer()->set(BatchCollecteHotspotController::class, $batchCollecteHotspotMock);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiCollecteHotspot, [], [], ['CONTENT_TYPE' => static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(500, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message500, $jsonResponse['message']);
    }

    public function testApiCollectHotspotCollectSuccess(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $batchCollecteHotspotMock = $this->createMock(BatchCollecteHotspotController::class);
        $batchCollecteHotspotMock->method('batchCollecteHotspot')
            ->willReturn(['code' => 200, 'nombre' => 3, 'data' =>
                [
                    'hotspot_high' => 2,
                    'hotspot_medium' => 0,
                    'hotspot_low' => 1,
                    'nombre_hotspot' => 3,
                ]]);
        static::getContainer()->set(BatchCollecteHotspotController::class, $batchCollecteHotspotMock);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiCollecteHotspot, [], [], ['CONTENT_TYPE' => static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        // Vérifier le code de réponse
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertEquals(200, $jsonResponse['code']);

        // Vérifier le nombre total
        $this->assertArrayHasKey('nombre', $jsonResponse);
        $this->assertEquals(3, $jsonResponse['nombre']);

        // Vérifier la présence de la clé "message"
        $this->assertArrayHasKey('message', $jsonResponse);
        $this->assertIsArray($jsonResponse['message']);

        // Vérifier les valeurs spécifiques du message
        $this->assertArrayHasKey('hotspot_high', $jsonResponse['message']);
        $this->assertArrayHasKey('hotspot_medium', $jsonResponse['message']);
        $this->assertArrayHasKey('hotspot_low', $jsonResponse['message']);
        $this->assertArrayHasKey('nombre_hotspot', $jsonResponse['message']);

        $this->assertEquals(2, $jsonResponse['message']['hotspot_high']);
        $this->assertEquals(0, $jsonResponse['message']['hotspot_medium']);
        $this->assertEquals(1, $jsonResponse['message']['hotspot_low']);
        $this->assertEquals(3, $jsonResponse['message']['nombre_hotspot']);
    }

    public function testApiCollecteAnomalieDataNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Envoyer une requête POST avec un body null
        $client->request('POST', static::$apiCollecteAnomalie, [], [], [static::$contentType], null);

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('data', $jsonResponse);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEmpty($jsonResponse['data']);
        $this->assertEquals(400, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message400, $jsonResponse['message']);
    }

    public function testApiCollecteAnomalieMavenKeyNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $data = ['some_key' => null ];

        // Envoyer une requête POST avec une clé différente de maven_key
        $client->request('POST', static::$apiCollecteAnomalie, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('data', $jsonResponse);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertArrayNotHasKey('maven_key', $jsonResponse['data']);
        $this->assertEquals(400, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message400, $jsonResponse['message']);
    }

    public function testApiCollecteAnomalieSansRoleCollecte(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$josh);
        $client->loginUser($testUser);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiCollecteAnomalie, [], [], ['CONTENT_TYPE' => static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(403, $jsonResponse['code']);
        $this->assertEquals('warning', $jsonResponse['type']);
        $this->assertEquals(static::$message403, $jsonResponse['message']);
    }

    public function testApiCollectAnomalieCollectError(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $batchCollecteAnomalieMock = $this->createMock(BatchCollecteAnomalieController::class);
        $batchCollecteAnomalieMock->method('batchCollecteAnomalie')
            ->willReturn(['code' => 500, 'type' => 'alert', 'message' => static::$messageLight500]);

        // Remplace le service dans le conteneur de Symfony
        static::getContainer()->set(BatchCollecteAnomalieController::class, $batchCollecteAnomalieMock);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiCollecteAnomalie, [], [], ['CONTENT_TYPE' => static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(500, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message500, $jsonResponse['message']);
    }

    public function testApiCollectAnomalieCollectSuccess(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $batchCollecteAnomalieMock = $this->createMock(BatchCollecteAnomalieController::class);
        $batchCollecteAnomalieMock->method('batchCollecteAnomalie')
            ->willReturn(['code' => 200, 'info' => "Nombre d'anomalie : 8637",
                'data' => [
                    'violations' => 8637,
                    'nombre_bug' => 631,
                    'nombre_vulnerability' => 8,
                    'nombre_code_smell' => 7998]]);

        static::getContainer()->set(BatchCollecteAnomalieController::class, $batchCollecteAnomalieMock);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiCollecteAnomalie, [], [], ['CONTENT_TYPE' => static::$contentType], json_encode($data));

        $response = $client->getResponse();
        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        // Vérifier le code de réponse
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertEquals(200, $jsonResponse['code']);

        // Vérifier le champ "info"
        $this->assertArrayHasKey('info', $jsonResponse);
        $this->assertEquals("Nombre d'anomalie : 8637", $jsonResponse['info']);

        // Vérifier la présence de la clé "message"
        $this->assertArrayHasKey('message', $jsonResponse);
        $this->assertIsArray($jsonResponse['message']);

        // Vérifier les valeurs spécifiques du message
        $this->assertArrayHasKey('violations', $jsonResponse['message']);
        $this->assertArrayHasKey('nombre_bug', $jsonResponse['message']);
        $this->assertArrayHasKey('nombre_vulnerability', $jsonResponse['message']);
        $this->assertArrayHasKey('nombre_code_smell', $jsonResponse['message']);

        $this->assertEquals(8637, $jsonResponse['message']['violations']);
        $this->assertEquals(631, $jsonResponse['message']['nombre_bug']);
        $this->assertEquals(8, $jsonResponse['message']['nombre_vulnerability']);
        $this->assertEquals(7998, $jsonResponse['message']['nombre_code_smell']);
    }

    public function testApiCollecteAnomalieDetailDataNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Envoyer une requête POST avec un body null
        $client->request('POST', static::$apiCollecteAnomalieDetail, [], [], [static::$contentType], null);

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('data', $jsonResponse);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEmpty($jsonResponse['data']);
        $this->assertEquals(400, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message400, $jsonResponse['message']);
    }

    public function testApiCollecteAnomalieDetailMavenKeyNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $data = ['some_key' => null ];

        // Envoyer une requête POST avec une clé différente de maven_key
        $client->request('POST', static::$apiCollecteAnomalieDetail, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('data', $jsonResponse);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertArrayNotHasKey('maven_key', $jsonResponse['data']);
        $this->assertEquals(400, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message400, $jsonResponse['message']);
    }

    public function testApiCollecteAnomalieDetailSansRoleCollecte(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$josh);
        $client->loginUser($testUser);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiCollecteAnomalieDetail, [], [], ['CONTENT_TYPE' => static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(403, $jsonResponse['code']);
        $this->assertEquals('warning', $jsonResponse['type']);
        $this->assertEquals(static::$message403, $jsonResponse['message']);
    }

    public function testApiCollectAnomalieDetailCollectError(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $batchCollecteAnomalieDetailMock = $this->createMock(BatchCollecteAnomalieDetailController::class);
        $batchCollecteAnomalieDetailMock->method('batchCollecteAnomalieDetail')
            ->willReturn(['code' => 500, 'type' => 'alert', 'message' => static::$messageLight500, 'debug' => 'CODE_SMELL']);

        // Remplace le service dans le conteneur de Symfony
        static::getContainer()->set(BatchCollecteAnomalieDetailController::class, $batchCollecteAnomalieDetailMock);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiCollecteAnomalieDetail, [], [], ['CONTENT_TYPE' => static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(500, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message500, $jsonResponse['message']);
    }

    public function testApiCollectAnomalieDetailCollectSuccess(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $batchCollecteAnomalieDetailMock = $this->createMock(BatchCollecteAnomalieDetailController::class);
        $batchCollecteAnomalieDetailMock->method('batchCollecteAnomalieDetail')
            ->willReturn(['code' => 200, 'message' => ['chargement des anomalies détallées'], 'data' =>
                [
                    'bug_blocker' => 0,
                    'bug_critical' => 0,
                    'bug_major' => 573,
                    'bug_minor' => 36,
                    'bug_info' => 22,
                    'vulnerability_blocker' => 0,
                    'vulnerability_critical' => 0,
                    'vulnerability_major' => 0,
                    'vulnerability_minor' => 0,
                    'vulnerability_info' => 8,
                    'code_smell_blocker' => 1,
                    'code_smell_critical' => 178,
                    'code_smell_major' => 1222,
                    'code_smell_minor' => 1215,
                    'code_smell_info' => 5382
            ]]);

        static::getContainer()->set(BatchCollecteAnomalieDetailController::class, $batchCollecteAnomalieDetailMock);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiCollecteAnomalieDetail, [], [], ['CONTENT_TYPE' => static::$contentType], json_encode($data));

        $response = $client->getResponse();
        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        // Vérification du code de réponse
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertEquals(200, $jsonResponse['code']);

        // Vérification du message
        $this->assertArrayHasKey('message', $jsonResponse);
        $this->assertIsArray($jsonResponse['message']);
        $this->assertCount(1, $jsonResponse['message']);
        $this->assertEquals("chargement des anomalies détallées", $jsonResponse['message'][0]);

        // Vérification de la présence des clés dans "data"
        $this->assertArrayHasKey('data', $jsonResponse);
        $expectedKeys = [
            'bug_blocker', 'bug_critical', 'bug_major', 'bug_minor', 'bug_info',
            'vulnerability_blocker', 'vulnerability_critical', 'vulnerability_major',
            'vulnerability_minor', 'vulnerability_info',
            'code_smell_blocker', 'code_smell_critical', 'code_smell_major',
            'code_smell_minor', 'code_smell_info'
        ];
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $jsonResponse['data']);
        }

        // Vérification des valeurs des anomalies
        $this->assertEquals(0, $jsonResponse['data']['bug_blocker']);
        $this->assertEquals(0, $jsonResponse['data']['bug_critical']);
        $this->assertEquals(573, $jsonResponse['data']['bug_major']);
        $this->assertEquals(36, $jsonResponse['data']['bug_minor']);
        $this->assertEquals(22, $jsonResponse['data']['bug_info']);

        $this->assertEquals(0, $jsonResponse['data']['vulnerability_blocker']);
        $this->assertEquals(0, $jsonResponse['data']['vulnerability_critical']);
        $this->assertEquals(0, $jsonResponse['data']['vulnerability_major']);
        $this->assertEquals(0, $jsonResponse['data']['vulnerability_minor']);
        $this->assertEquals(8, $jsonResponse['data']['vulnerability_info']);

        $this->assertEquals(1, $jsonResponse['data']['code_smell_blocker']);
        $this->assertEquals(178, $jsonResponse['data']['code_smell_critical']);
        $this->assertEquals(1222, $jsonResponse['data']['code_smell_major']);
        $this->assertEquals(1215, $jsonResponse['data']['code_smell_minor']);
        $this->assertEquals(5382, $jsonResponse['data']['code_smell_info']);
    }

    public function testApiCollecteHotspotOwaspDetailDataNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Envoyer une requête POST avec un body null
        $client->request('POST', static::$apiCollecteHotspotOwasp, [], [], [static::$contentType], null);

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('data', $jsonResponse);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEmpty($jsonResponse['data']);
        $this->assertEquals(400, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message400, $jsonResponse['message']);
    }

    public function testApiCollecteHotspotOwaspMavenKeyNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $data = ['some_key' => null ];

        // Envoyer une requête POST avec une clé différente de maven_key
        $client->request('POST', static::$apiCollecteHotspotOwasp, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('data', $jsonResponse);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertArrayNotHasKey('maven_key', $jsonResponse['data']);
        $this->assertEquals(400, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message400, $jsonResponse['message']);
    }

    public function testApiCollecteHotspotOwaspMenaceNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $data = ['maven_key' => 'some-maven_key' ];

        // Envoyer une requête POST avec une clé différente de maven_key
        $client->request('POST', static::$apiCollecteHotspotOwasp, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('data', $jsonResponse);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertArrayNotHasKey('menace', $jsonResponse['data']);
        $this->assertEquals(400, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message400, $jsonResponse['message']);
    }

    public function testApiCollecteHotspotOwaspSansRoleCollecte(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$josh);
        $client->loginUser($testUser);

        $data = ['maven_key' => static::$leChat, 'menace' => 'a0'];
        $client->request('POST', static::$apiCollecteHotspotOwasp, [], [], ['CONTENT_TYPE' => static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(403, $jsonResponse['code']);
        $this->assertEquals('warning', $jsonResponse['type']);
        $this->assertEquals(static::$message403, $jsonResponse['message']);
    }

    public function testApiCollectHotspotOwaspCollectError(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $batchCollecteHotspotOwaspMock = $this->createMock(BatchCollecteHotspotOwaspController::class);
        $batchCollecteHotspotOwaspMock->method('batchCollecteHotspotOwasp')
            ->willReturn(['code' => 500, 'type' => 'alert', 'message' => static::$messageLight500]);

        // Remplace le service dans le conteneur de Symfony
        static::getContainer()->set(BatchCollecteHotspotOwaspController::class, $batchCollecteHotspotOwaspMock);

        $data = ['maven_key' => static::$leChat, 'menace' => 'a1'];
        $client->request('POST', static::$apiCollecteHotspotOwasp, [], [], ['CONTENT_TYPE' => static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(500, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message500, $jsonResponse['message']);
    }

    public function testApiCollectHotspotOwaspCollectA0Success(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);
        $batchCollecteHotspotOwaspMock = $this->createMock(BatchCollecteHotspotOwaspController::class);
        $batchCollecteHotspotOwaspMock->method('batchCollecteHotspotOwasp')
            ->willReturn([
                        'code' => 200,
                        'info' => 'effacement',
                        'owasp_2017' => 'NC',
                        'owasp_2021' => 'NC',
                        'message' => 'A0 : Effacement des données de la table hotspotOwasp pour le projet.',
                        'data' => [] ]);

        static::getContainer()->set(BatchCollecteHotspotOwaspController::class, $batchCollecteHotspotOwaspMock);

        $data = ['maven_key' => static::$leChat, 'menace' => 'a0'];
        $client->request('POST', static::$apiCollecteHotspotOwasp, [], [], ['CONTENT_TYPE' => static::$contentType], json_encode($data));

        $response = $client->getResponse();
        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        // Vérification du code de réponse
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertEquals(200, $jsonResponse['code']);

        // Vérification des autres clés
        $this->assertArrayHasKey('info', $jsonResponse);
        $this->assertEquals("effacement", $jsonResponse['info']);

        $this->assertArrayHasKey('owasp2017', $jsonResponse);
        $this->assertEquals("NC", $jsonResponse['owasp2017']);

        $this->assertArrayHasKey('owasp2021', $jsonResponse);
        $this->assertEquals("NC", $jsonResponse['owasp2021']);

        $this->assertArrayHasKey('message', $jsonResponse);
        $this->assertEquals("A0 : Effacement des données de la table hotspotOwasp pour le projet.", $jsonResponse['message']);
    }

    public function testApiCollectHotspotOwaspCollectA1Success(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $batchCollecteHotspotOwaspMock = $this->createMock(BatchCollecteHotspotOwaspController::class);
        $batchCollecteHotspotOwaspMock->method('batchCollecteHotspotOwasp')
            ->willReturn(
                [
                    'code' => 200,
                    'info' => 'enregistrement',
                    'owasp_2017' => 1,
                    'owasp_2021' => 'NC',
                    'message' => '',
                    'data' => [
                        [
                            'referential_owasp' => 2017,
                            'maven_key' => static::$leChat,
                            'menace' => 'a6',
                            'security_category' => 'others',
                            'rule_key' => 'NC',
                            'probability' => 'LOW',
                            'status' => 'TO_REVIEW',
                            'resolution' => '',
                            'niveau' => 3,
                            'mode_collecte' => 'COLLECTE',
                            'utilisateur_collecte' => static::$aurelie,
                            'date_enregistrement' => new \DateTimeImmutable('2025-02-09 11:00:32')
                        ]
                    ]]);

        static::getContainer()->set(BatchCollecteHotspotOwaspController::class, $batchCollecteHotspotOwaspMock);

        $data = ['maven_key' => static::$leChat, 'menace' => 'a6'];
        $client->request('POST', static::$apiCollecteHotspotOwasp, [], [], ['CONTENT_TYPE' => static::$contentType], json_encode($data));

        $response = $client->getResponse();
        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        // Vérification du code de réponse
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertEquals(200, $jsonResponse['code']);

        // Vérification des autres clés
        $this->assertArrayHasKey('info', $jsonResponse);
        $this->assertArrayHasKey('owasp2017', $jsonResponse);
        $this->assertArrayHasKey('owasp2021', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals('enregistrement', $jsonResponse['info']);
        $this->assertEquals(1, $jsonResponse['owasp2017']);
        $this->assertEquals('NC', $jsonResponse['owasp2021']);
        $this->assertEquals('', $jsonResponse['message']);

        // Vérification de la présence de data et de son contenu
        $this->assertArrayHasKey('data', $jsonResponse);
        $this->assertIsArray($jsonResponse['data']);
        $this->assertCount(1, $jsonResponse['data']);

        $entry = $jsonResponse['data'][0];

        // Vérification des clés de l'entrée dans data
        $expectedKeys = [
            'referential_owasp', 'maven_key', 'menace', 'security_category', 'rule_key',
            'probability', 'status', 'resolution', 'niveau', 'mode_collecte',
            'utilisateur_collecte', 'date_enregistrement'
        ];
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $entry);
        }

        // Vérification des valeurs
        $this->assertEquals(2017, $entry['referential_owasp']);
        $this->assertEquals(static::$leChat, $entry['maven_key']);
        $this->assertEquals('a6', $entry['menace']);
        $this->assertEquals('others', $entry['security_category']);
        $this->assertEquals('NC', $entry['rule_key']);
        $this->assertEquals('LOW', $entry['probability']);
        $this->assertEquals('TO_REVIEW', $entry['status']);
        $this->assertEquals('', $entry['resolution']);
        $this->assertEquals(3, $entry['niveau']);
        $this->assertEquals('COLLECTE', $entry['mode_collecte']);
        $this->assertEquals(static::$aurelie, $entry['utilisateur_collecte']);

        // Vérification de la date d'enregistrement
        $this->assertEquals('2025-02-09 11:00:32', (new \DateTimeImmutable($entry['date_enregistrement']['date']))->format('Y-m-d H:i:s'));
    }

    public function testApiCollecteHotspotDetailDataNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Envoyer une requête POST avec un body null
        $client->request('POST', static::$apiCollecteHotspotDetail, [], [], [static::$contentType], null);

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('data', $jsonResponse);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEmpty($jsonResponse['data']);
        $this->assertEquals(400, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message400, $jsonResponse['message']);
    }

    public function testApiCollecteHotspotDetailMavenKeyNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $data = ['some_key' => null ];

        // Envoyer une requête POST avec une clé différente de maven_key
        $client->request('POST', static::$apiCollecteHotspotDetail, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('data', $jsonResponse);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertArrayNotHasKey('maven_key', $jsonResponse['data']);
        $this->assertEquals(400, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message400, $jsonResponse['message']);
    }

    public function testApiCollecteHotspotDetailSansRoleCollecte(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$josh);
        $client->loginUser($testUser);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiCollecteHotspotDetail, [], [], ['CONTENT_TYPE' => static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(403, $jsonResponse['code']);
        $this->assertEquals('warning', $jsonResponse['type']);
        $this->assertEquals(static::$message403, $jsonResponse['message']);
    }

    public function testApiCollectHotspotDetailCollectNotFound(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiCollecteHotspotDetail, [], [], ['CONTENT_TYPE' => static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(404, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$messageLight404, $jsonResponse['message']);
    }

    public function testApiCollectHotspotDetailCollectError(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $batchCollecteHotspotDetailMock = $this->createMock(BatchCollecteHotspotDetailController::class);
        $batchCollecteHotspotDetailMock->method('batchCollecteHotspotDetail')
            ->willReturn(['code' => 500, 'type' => 'alert', 'message' => static::$messageLight500]);

        // Remplace le service dans le conteneur de Symfony
        static::getContainer()->set(BatchCollecteHotspotDetailController::class, $batchCollecteHotspotDetailMock);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiCollecteHotspotDetail, [], [], ['CONTENT_TYPE' => static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(500, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message500, $jsonResponse['message']);
    }

    public function testApiCollectHotspotDetailCollectSuccess(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $batchCollecteHotspotDetailMock = $this->createMock(BatchCollecteHotspotDetailController::class);
        $batchCollecteHotspotDetailMock->method('batchCollecteHotspotDetail')
            ->willReturn(['code' => 200, 'nombre' => 2, 'message' =>
            'Données enregistrée dans la table hotspotDetail.']);

        // Remplace le service dans le conteneur de Symfony
        static::getContainer()->set(BatchCollecteHotspotDetailController::class, $batchCollecteHotspotDetailMock);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiCollecteHotspotDetail, [], [], ['CONTENT_TYPE' => static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('nombre', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(200, $jsonResponse['code']);
        $this->assertEquals(2, $jsonResponse['nombre']);
        $this->assertEquals('Données enregistrées dans la table hotspotDetail.', $jsonResponse['message']);
    }

    public function testApiCollecteNoSonarDataNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Envoyer une requête POST avec un body null
        $client->request('POST', static::$apiCollecteNoSonar, [], [], [static::$contentType], null);

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('data', $jsonResponse);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEmpty($jsonResponse['data']);
        $this->assertEquals(400, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message400, $jsonResponse['message']);
    }

    public function testApiCollecteNoSonarMavenKeyNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $data = ['some_key' => null ];

        // Envoyer une requête POST avec une clé différente de maven_key
        $client->request('POST', static::$apiCollecteNoSonar, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('data', $jsonResponse);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertArrayNotHasKey('maven_key', $jsonResponse['data']);
        $this->assertEquals(400, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message400, $jsonResponse['message']);
    }

    public function testApiCollecteNoSonarSansRoleCollecte(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$josh);
        $client->loginUser($testUser);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiCollecteNoSonar, [], [], ['CONTENT_TYPE' => static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(403, $jsonResponse['code']);
        $this->assertEquals('warning', $jsonResponse['type']);
        $this->assertEquals(static::$message403, $jsonResponse['message']);
    }

    public function testApiCollectNoSonarCollectError(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $batchCollecteNoSonarMock = $this->createMock(BatchCollecteNoSonarController::class);
        $batchCollecteNoSonarMock->method('batchCollecteNoSonar')
            ->willReturn(['code' => 500, 'type' => 'alert', 'message' => static::$messageLight500]);

        // Remplace le service dans le conteneur de Symfony
        static::getContainer()->set(BatchCollecteNoSonarController::class, $batchCollecteNoSonarMock);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiCollecteNoSonar, [], [], ['CONTENT_TYPE' => static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(500, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message500, $jsonResponse['message']);
    }

    public function testApiCollectNoSonarCollectSuccess(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $batchCollecteNoSonarMock = $this->createMock(BatchCollecteNoSonarController::class);
        $batchCollecteNoSonarMock->method('batchCollecteNoSonar')
            ->willReturn(['code' => 200, 'nombre' => 51, 'message' =>
                            [
                                'suppress_warning' => 51,
                                'no_sonar' => 0
                            ]]);

        // Remplace le service dans le conteneur de Symfony
        static::getContainer()->set(BatchCollecteNoSonarController::class, $batchCollecteNoSonarMock);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiCollecteNoSonar, [], [], ['CONTENT_TYPE' => static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('nombre', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(200, $jsonResponse['code']);
        $this->assertEquals(51, $jsonResponse['nombre']);
        $this->assertEquals(51, $jsonResponse['message']['suppress_warning']);
        $this->assertEquals(0, $jsonResponse['message']['no_sonar']);
    }

    public function testApiCollecteTodoDataNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Envoyer une requête POST avec un body null
        $client->request('POST', static::$apiCollecteTodo, [], [], [static::$contentType], null);

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('data', $jsonResponse);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEmpty($jsonResponse['data']);
        $this->assertEquals(400, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message400, $jsonResponse['message']);
    }

    public function testApiCollecteTodoMavenKeyNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $data = ['some_key' => null ];

        // Envoyer une requête POST avec une clé différente de maven_key
        $client->request('POST', static::$apiCollecteTodo, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('data', $jsonResponse);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertArrayNotHasKey('maven_key', $jsonResponse['data']);
        $this->assertEquals(400, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message400, $jsonResponse['message']);
    }

    public function testApiCollecteTodoSansRoleCollecte(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$josh);
        $client->loginUser($testUser);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiCollecteTodo, [], [], ['CONTENT_TYPE' => static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(403, $jsonResponse['code']);
        $this->assertEquals('warning', $jsonResponse['type']);
        $this->assertEquals(static::$message403, $jsonResponse['message']);
    }

    public function testApiCollectTodoCollectError(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $batchCollecteTodoMock = $this->createMock(BatchCollecteTodoController::class);
        $batchCollecteTodoMock->method('batchCollecteTodo')
            ->willReturn(['code' => 500, 'message' => static::$messageLight500]);

        // Remplace le service dans le conteneur de Symfony
        static::getContainer()->set(BatchCollecteTodoController::class, $batchCollecteTodoMock);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiCollecteTodo, [], [], ['CONTENT_TYPE' => static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(500, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message500, $jsonResponse['message']);
    }

    public function testApiCollectTodoCollectSuccess(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $batchCollecteTodoMock = $this->createMock(BatchCollecteTodoController::class);
        $batchCollecteTodoMock->method('batchCollecteTodo')
            ->willReturn(['code' => 200, 'nombre' => 12, 'message' => 'Données enregistrées dans la table Todo.']);

        // Remplace le service dans le conteneur de Symfony
        static::getContainer()->set(BatchCollecteTodoController::class, $batchCollecteTodoMock);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiCollecteTodo, [], [], ['CONTENT_TYPE' => static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('nombre', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(200, $jsonResponse['code']);
        $this->assertEquals(12, $jsonResponse['nombre']);
        $this->assertEquals('Données enregistrées dans la table Todo.', $jsonResponse['message']);
    }

    public function testApiCollecteActuatorInfoDataNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Envoyer une requête POST avec un body null
        $client->request('POST', static::$apiCollecteActuatorInfo, [], [], [static::$contentType], null);

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('data', $jsonResponse);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEmpty($jsonResponse['data']);
        $this->assertEquals(400, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message400, $jsonResponse['message']);
    }

    public function testApiCollecteActuatorInfoMavenKeyNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $data = ['some_key' => null ];

        // Envoyer une requête POST avec une clé différente de maven_key
        $client->request('POST', static::$apiCollecteActuatorInfo, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('data', $jsonResponse);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertArrayNotHasKey('maven_key', $jsonResponse['data']);
        $this->assertEquals(400, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message400, $jsonResponse['message']);
    }

    public function testApiCollecteActuatorInfoSansRoleCollecte(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$josh);
        $client->loginUser($testUser);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiCollecteActuatorInfo, [], [], ['CONTENT_TYPE' => static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(403, $jsonResponse['code']);
        $this->assertEquals('warning', $jsonResponse['type']);
        $this->assertEquals(static::$message403, $jsonResponse['message']);
    }

    public function testApiCollectActuatorInfoCollectError(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $batchCollecteActuatorMock = $this->createMock(BatchCollecteActuatorController::class);
        $batchCollecteActuatorMock->method('batchCollecteActuatorInfo')
            ->willReturn(['code' => 500, 'message' => static::$messageLight500]);

        // Remplace le service dans le conteneur de Symfony
        static::getContainer()->set(BatchCollecteActuatorController::class, $batchCollecteActuatorMock);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiCollecteActuatorInfo, [], [], ['CONTENT_TYPE' => static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(500, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message500, $jsonResponse['message']);
    }

    public function testApiCollectActuatorInfoCollectSuccess(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $batchCollecteActuatorMock = $this->createMock(BatchCollecteActuatorController::class);
        $batchCollecteActuatorMock->method('batchCollecteActuatorInfo')
            ->willReturn(['code' => 200, 'message' => 'Extraction des données Actuator',
                        'json' => [
                            'nom' => 'Le-Chat',
                            'description' => 'AI Gen prompt',
                            'version' => '2.1.1-RELEASE',
                            'socle' => [ 'php' => '8.3.0 [NT]']
                            ]
                    ]);

        // Remplace le service dans le conteneur de Symfony
        static::getContainer()->set(BatchCollecteActuatorController::class, $batchCollecteActuatorMock);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiCollecteActuatorInfo, [], [], ['CONTENT_TYPE' => static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        // Vérification du code de réponse
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertEquals(200, $jsonResponse['code']);

        // Vérification du message
        $this->assertArrayHasKey('message', $jsonResponse);
        $this->assertEquals("Extraction des données Actuator.", $jsonResponse['message']);

        // Vérification de la présence de la clé 'json'
        $this->assertArrayHasKey('json', $jsonResponse);
        $this->assertIsArray($jsonResponse['json']);

        // Vérification des clés dans 'json'
        $this->assertArrayHasKey('nom', $jsonResponse['json']);
        $this->assertEquals("Le-Chat", $jsonResponse['json']['nom']);

        $this->assertArrayHasKey('description', $jsonResponse['json']);
        $this->assertEquals("AI Gen prompt", $jsonResponse['json']['description']);

        $this->assertArrayHasKey('version', $jsonResponse['json']);
        $this->assertEquals("2.1.1-RELEASE", $jsonResponse['json']['version']);

        // Vérification de la présence de 'socle' et de 'php'
        $this->assertArrayHasKey('socle', $jsonResponse['json']);
        $this->assertIsArray($jsonResponse['json']['socle']);

        $this->assertArrayHasKey('php', $jsonResponse['json']['socle']);
        $this->assertEquals("8.3.0 [NT]", $jsonResponse['json']['socle']['php']);
    }

    public function testApiCollecteLoggerDataNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Envoyer une requête POST avec un body null
        $client->request('POST', static::$apiCollecteLogger, [], [], [static::$contentType], null);

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('data', $jsonResponse);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEmpty($jsonResponse['data']);
        $this->assertEquals(400, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message400, $jsonResponse['message']);
    }

    public function testApiCollecteLoggerMavenKeyNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $data = ['some_key' => null ];

        // Envoyer une requête POST avec une clé différente de maven_key
        $client->request('POST', static::$apiCollecteLogger, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('data', $jsonResponse);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertArrayNotHasKey('maven_key', $jsonResponse['data']);
        $this->assertEquals(400, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message400, $jsonResponse['message']);
    }

    public function testApiCollecteLoggerRoleCollecte(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$josh);
        $client->loginUser($testUser);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiCollecteLogger, [], [], ['CONTENT_TYPE' => static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(403, $jsonResponse['code']);
        $this->assertEquals('warning', $jsonResponse['type']);
        $this->assertEquals(static::$message403, $jsonResponse['message']);
    }

    public function testApiCollectLoggerCollectError(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $batchCollecteLoggerMock = $this->createMock(BatchCollecteLoggerController::class);
        $batchCollecteLoggerMock->method('batchCollecteLogger')
            ->willReturn(['code' => 500, 'message' => static::$messageLight500]);

        // Remplace le service dans le conteneur de Symfony
        static::getContainer()->set(BatchCollecteLoggerController::class, $batchCollecteLoggerMock);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiCollecteLogger, [], [], ['CONTENT_TYPE' => static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(500, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message500, $jsonResponse['message']);
    }

    public function testApiCollectLoggerCollectSuccess(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $batchCollecteLoggerMock = $this->createMock(BatchCollecteLoggerController::class);
        $batchCollecteLoggerMock->method('batchCollecteLogger')
            ->willReturn(['code' => 200, 'message' => '', 'data' =>
        [
            'maven_key' => static::$leChat,
            'logger_info' => 10,
            'logger_warn' => 2,
            'logger_error' => 18,
            'logger_debug' => 26,
        ]]);

        // Remplace le service dans le conteneur de Symfony
        static::getContainer()->set(BatchCollecteLoggerController::class, $batchCollecteLoggerMock);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiCollecteLogger, [], [], ['CONTENT_TYPE' => static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);

        // Vérification du code de réponse
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertEquals(200, $jsonResponse['code']);

        // Vérification du message
        $this->assertArrayHasKey('message', $jsonResponse);
        $this->assertEquals("Données enregistrées dans la table logger.", $jsonResponse['message']);

        // Vérification de la présence de la clé 'data'
        $this->assertArrayHasKey('data', $jsonResponse);
        $this->assertIsArray($jsonResponse['data']);

        // Vérification des clés dans 'data'
        $this->assertArrayHasKey('maven_key', $jsonResponse['data']);
        $this->assertEquals("fr.ma-petite-entreprise:le-chat", $jsonResponse['data']['maven_key']);

        $this->assertArrayHasKey('logger_info', $jsonResponse['data']);
        $this->assertEquals(10, $jsonResponse['data']['logger_info']);

        $this->assertArrayHasKey('logger_warn', $jsonResponse['data']);
        $this->assertEquals(2, $jsonResponse['data']['logger_warn']);

        $this->assertArrayHasKey('logger_error', $jsonResponse['data']);
        $this->assertEquals(18, $jsonResponse['data']['logger_error']);

        $this->assertArrayHasKey('logger_debug', $jsonResponse['data']);
        $this->assertEquals(26, $jsonResponse['data']['logger_debug']);
    }

}
