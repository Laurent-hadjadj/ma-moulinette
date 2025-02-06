<?php

namespace App\Tests\Unit\Controller\Projet;

use App\Service\IsValideMavenKey;
use App\Repository\NotesRepository;
use App\Repository\MesuresRepository;
use App\Repository\AnomalieRepository;
use App\Repository\UtilisateurRepository;
use App\Repository\AnomalieDetailsRepository;
use App\Repository\InformationProjetRepository;
use App\Repository\HotspotsRepository;
use App\Repository\NoSonarRepository;
use App\Repository\TodoRepository;
use App\Repository\LoggerRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * [Description ApiPeintureControllerTest]
 */
class ApiPeintureControllerTest extends WebTestCase
{
    private static $contentType = 'application/json';
    private static $aurelie = 'aurelie.petit-coeur@ma-moulinette.fr';
    private static $maMoulinette = 'fr.ma-petite-entreprise:ma-moulinette';
    private static $leChat = 'fr.ma-petite-entreprise:le-chat';
    private static $trackerLogger = 'fr.ma-petite-entreprise:tracker-logger';
    private static $apiProjetMesApplicationsListe = '/api/projet/mes-applications/liste';
    private static $apiPeintureProjetVersion = '/api/peinture/projet/version';
    private static $apiPeintureProjetAnomalie = '/api/peinture/projet/anomalie';
    private static $apiPeintureProjetAnomalieDetails = '/api/peinture/projet/anomalie/details';
    private static $apiPeintureProjetMesures = '/api/peinture/projet/mesures';
    private static $apiPeintureProjetHotspots = '/api/peinture/projet/hotspots';
    private static $apiPeintureProjetHotspotsDetails = '/api/peinture/projet/hotspots/details';
    private static $apiPeintureProjetNoSonar = '/api/peinture/projet/nosonar';
    private static $apiPeintureProjetTodo = '/api/peinture/projet/todo';
    private static $apiPeintureProjetLogger = '/api/peinture/projet/logger';

    private static $appServiceIsValidMavenKey = 'App\Service\IsValideMavenKey';
    private static $appRepositoryInformationProjectRepository = 'App\Repository\InformationProjetRepository';
    private static $appRepositoryMesuresRepository = 'App\Repository\MesuresRepository';
    private static $appRepositoryAnomalieRepository = 'App\Repository\AnomalieRepository';
    private static $appRepositoryAnomalieDetailsRepository = 'App\Repository\AnomalieDetailsRepository';
    private static $appRepositoryHotspotsRepository = 'App\Repository\HotspotsRepository';
    private static $appRepositoryNotesRepository = 'App\Repository\NotesRepository';
    private static $appRepositoryNoSonarRepository = 'App\Repository\NoSonarRepository';
    private static $appRepositoryTodoRepository = 'App\Repository\TodoRepository';
    private static $appRepositoryLoggerRepository = 'App\Repository\LoggerRepository';

    private static $message400 = '<strong>[Peinture]</strong> La requête est incorrecte (Erreur 400).';
    private static $message404 = "<strong>[Peinture]</strong> Je n'ai pas trouvé les données. Vous devez lancer une collecte (Erreur 404).";
    private static $message500 = "<strong>[Peinture]</strong> Je n'ai pas trouvé d'analyse (Erreur 500).";
    private static $messageLight500 = "Je n'ai pas trouvé d'analyse (Erreur 500).";

    private static $dette = '1h:7min';
    private static $detteReliability = '0h:0min';
    private static $detteVulnerability = '2d, 2h:54min';
    private static $detteCodeSmell = '2d, 1h:47min';

    private static $webS1135 = 'Web:S1135';
    private static $tsS1135 = 'typescript:S1135';
    private static $javaS1135 = 'java:S1135';
    private static $xmlS1135 = 'xml:S1135';
    private static $jsS1135 = 'javascript:S1135';

    public function testProjetMesApplicationsListeDataNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Envoyer une requête POST avec un body null
        $client->request('POST', static::$apiProjetMesApplicationsListe, [], [], [static::$contentType], null);

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
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

    public function testProjetMesApplicationsListeError(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Simuler le comportement du repository
        $anomalieRepository = $this->createMock(AnomalieRepository::class);
        $anomalieRepository->method('selectAnomalieByProjectName')
                            ->willReturn(['code' => 500, 'erreur' => 'Erreur serveur']);

        static::getContainer()->set(AnomalieRepository::class, $anomalieRepository);

        // Envoyer une requête POST
        $client->request('POST', static::$apiProjetMesApplicationsListe, [], [], [static::$contentType], json_encode(['dummy' => 'data']));

        $response = $client->getResponse();

        // Vérifie que la réponse est bien un code 500 (erreur serveur)
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);
        $this->assertEquals(500, $jsonResponse['code']);
        $this->assertEquals('warning', $jsonResponse['type']);
        $this->assertEquals('Erreur serveur', $jsonResponse['message']);
    }

    public function testProjetMesApplicationsListeEmptyProjectList(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Simuler la réponse du repository avec une liste vide
        $anomalieRepository = $this->createMock(AnomalieRepository::class);
        $anomalieRepository->method('selectAnomalieByProjectName')
                            ->willReturn(['code' => 200, 'liste' => []]);

        static::getContainer()->set(AnomalieRepository::class, $anomalieRepository);

        $client->request('POST', static::$apiProjetMesApplicationsListe, [], [], [static::$contentType], json_encode(['dummy' => 'data']));

        $response = $client->getResponse();

        // Vérifie que la réponse est un code 406 (pas de projet trouvé)
        $jsonResponse = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);
        $this->assertEquals(406, $jsonResponse['code']);
        $this->assertEquals('primary', $jsonResponse['type']);
        $this->assertEquals(static::$message404, $jsonResponse['message']);
    }

    public function testProjetMesApplicationsListeValidResponse(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        // Mock du repository AnomalieRepository
        $anomalieRepositoryMock = $this->createMock(AnomalieRepository::class);
        $anomalieRepositoryMock->method('selectAnomalieByProjectName')->willReturn([
            'code' => 200,
            'liste' => [
                ['key' => 'de.merv:2048'],
                ['key' => static::$maMoulinette],
                ['key' => static::$leChat],
                ['key' => 'tetris:tetrisGame'],
                ['key' => static::$trackerLogger]
            ]
        ]);

        // Injection du mock dans le conteneur de services Symfony
        $container->set(AnomalieRepository::class, $anomalieRepositoryMock);
        $entityManager = $container->get('doctrine')->getManager();

        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);

        // Sauvegarde des préférences actuelles
        $preferencesOriginales = $testUser->getPreference();

        // Préférence avec un projet favori
        $preferencesTest = [
            'statut' => [
                'bookmark' => true,
                'suivi_projet' => false,
                'favori_projet' => true,
                'favori_version' => true
            ],
            'suivi_projet' => [],
            'favori_projet' => [
                static::$maMoulinette,
                static::$leChat,
                static::$trackerLogger
            ],
            'favori_version' => [
                static::$maMoulinette => ['1.0.0-RELEASE', '1.5.0-RELEASE', '2.0.0-RELEASE'],
                static::$leChat => ['2.1.1-RELEASE']
            ],
            'bookmark' => [static::$leChat]
        ];

        /** On met à jour les préférences */
        $testUser->setPreference($preferencesTest);
        $entityManager->flush();

        /** On se connecte */
        $client->loginUser($testUser);


        // Simuler la préférence de l'utilisateur
        $client->request('POST', static::$apiProjetMesApplicationsListe, [], [], [static::$contentType], json_encode([]));

        // 🔄 **Restaurer les préférences originales après le test **
        $testUser->setPreference($preferencesOriginales);
        $entityManager->flush();

        // Obtenir la réponse
        $response = $client->getResponse();
        $jsonResponse = json_decode($response->getContent(), true);

        // Vérifier les projets retournés
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertEquals(200, $jsonResponse['code']);
        $this->assertArrayHasKey('projets', $jsonResponse);

        // Vérifier que les projets sont corrects et que le favori est bien détecté
        $projets = $jsonResponse['projets'];
        $projetKeys = array_column($projets, 'key');

        // Vérification du premier projet (qui est favori)
        $this->assertContains(static::$maMoulinette, $projetKeys);
        $this->assertEquals('ma-moulinette', $projets[0]['name']);
        $this->assertEquals(true, $projets[0]['favori']);

        // Vérification du deuxième projet (qui est favori)
        $this->assertContains(static::$leChat, $projetKeys);
        $this->assertEquals('le-chat', $projets[1]['name']);
        $this->assertEquals(true, $projets[1]['favori']);

        // Vérification du troisième projet (qui n'est pas favori)
        $this->assertContains(static::$trackerLogger, $projetKeys);
        $this->assertEquals('tracker-logger', $projets[2]['name']);
        $this->assertEquals(false, $projets[2]['favori']);
    }

    public function testPeintureProjetVersionDataNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Envoyer une requête POST avec un body null
        $client->request('POST', static::$apiPeintureProjetVersion, [], [], [static::$contentType], null);

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
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

    public function testPeintureProjetVersionMavenKeyNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $data = ['some_key' => null ];

        // Envoyer une requête POST avec une clé différente de maven_key
        $client->request('POST', static::$apiPeintureProjetVersion, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
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

    public function testPeintureProjetVersionMavenKeyNotFound(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Créer un mock pour isValidMavenKey
        $isValidMavenKeyMock = $this->getMockBuilder(isValideMavenKey::class)
        ->disableOriginalConstructor()
        ->getMock();

        // Configurer le mock pour retourner une réponse avec un code 404
        $isValidMavenKeyMock->method('isValideInformation')
            ->willReturn(['code' => 404]);
        $client->getContainer()->set(static::$appServiceIsValidMavenKey, $isValidMavenKeyMock);

        $data = ['maven_key' => 'non-existent-key' ];
        $client->request('POST', static::$apiPeintureProjetVersion, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $jsonResponse = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);
        $this->assertEquals(404, $jsonResponse['code']);
        $this->assertEquals('secondary', $jsonResponse['type']);
        $this->assertEquals(static::$message404, $jsonResponse['message']);
    }

    public function testPeintureProjetVersionCountAllProjetError(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Créer un mock pour isValidMavenKey
        $isValidMavenKeyMock = $this->getMockBuilder(isValideMavenKey::class)
        ->disableOriginalConstructor()
        ->getMock();

        // Configurer le mock pour retourner une réponse avec un code 404
        $isValidMavenKeyMock->method('isValideInformation')
            ->willReturn(['code' => 200]);
        $client->getContainer()->set(static::$appServiceIsValidMavenKey, $isValidMavenKeyMock);

        // Créer un mock pour InformationProjetEntity
        $informationProjetRepositoryMock = $this->getMockBuilder(InformationProjetRepository::class)
        ->disableOriginalConstructor()
        ->getMock();
        // Configurer le mock pour retourner une réponse avec un code 500
        $informationProjetRepositoryMock->method('countInformationProjetAllType')
            ->willReturn(['code' => 500]);
        $client->getContainer()->set(static::$appRepositoryInformationProjectRepository, $informationProjetRepositoryMock);

        $data = ['maven_key' => 1 ];
        $client->request('POST', static::$apiPeintureProjetVersion, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $jsonResponse = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);
        $this->assertArrayHasKey('debug', $jsonResponse);
        $this->assertEquals(500, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message500, $jsonResponse['message']);
        $this->assertEquals('Tous les projets.', $jsonResponse['debug']);
    }

    public function testPeintureProjetVersionCountReleaseError(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $isValidMavenKeyMock = $this->getMockBuilder(isValideMavenKey::class)
        ->disableOriginalConstructor()
        ->getMock();

        $isValidMavenKeyMock->method('isValideInformation')
            ->willReturn(['code' => 200]);
        $client->getContainer()->set(static::$appServiceIsValidMavenKey, $isValidMavenKeyMock);

        $informationProjetRepositoryMock = $this->getMockBuilder(InformationProjetRepository::class)
        ->disableOriginalConstructor()
        ->getMock();

        $informationProjetRepositoryMock->method('countInformationProjetAllType')
            ->willReturn(['code' => 200]);
        $informationProjetRepositoryMock->method('countInformationProjetType')
            ->willReturn(['code' => 500]);

        $client->getContainer()->set(static::$appRepositoryInformationProjectRepository, $informationProjetRepositoryMock);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiPeintureProjetVersion, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $jsonResponse = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);
        $this->assertArrayHasKey('debug', $jsonResponse);
        $this->assertEquals(500, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message500, $jsonResponse['message']);
        $this->assertEquals('Seul les Releases.', $jsonResponse['debug']);
    }

    public function testPeintureProjetVersionCountSnapshotError(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $isValidMavenKeyMock = $this->getMockBuilder(isValideMavenKey::class)
        ->disableOriginalConstructor()
        ->getMock();

        $isValidMavenKeyMock->method('isValideInformation')
            ->willReturn(['code' => 200]);
        $client->getContainer()->set(static::$appServiceIsValidMavenKey, $isValidMavenKeyMock);

        $informationProjetRepositoryMock = $this->getMockBuilder(InformationProjetRepository::class)
        ->disableOriginalConstructor()
        ->getMock();

        $informationProjetRepositoryMock->method('countInformationProjetAllType')
            ->willReturn(['code' => 200, 'nombre' => [['total' => 8]]]);

        // Configurer le mock pour retourner une réponse avec un code 200 et un total de 5 pour les releases
        $informationProjetRepositoryMock->method('countInformationProjetType')
        ->with($this->anything())
        ->will($this->returnCallback(function ($args) {
            if (isset($args['maven_key']) && $args['maven_key'] === static::$leChat && isset($args['type']) && $args['type'] === 'RELEASE') {
                return ['code' => 200, 'nombre' => [['total' => 5]]];
            } elseif (isset($args['maven_key']) && $args['maven_key'] === static::$leChat && isset($args['type']) && $args['type'] === 'SNAPSHOT') {
                return ['code' => 500];
            }
            return null;
        }));

        $client->getContainer()->set(static::$appRepositoryInformationProjectRepository, $informationProjetRepositoryMock);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiPeintureProjetVersion, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $jsonResponse = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);
        $this->assertArrayHasKey('debug', $jsonResponse);
        $this->assertEquals(500, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message500, $jsonResponse['message']);
        $this->assertEquals('Seul les Snapshots.', $jsonResponse['debug']);
    }

    public function testPeintureProjetVersionTypeIndexedError(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $isValidMavenKeyMock = $this->getMockBuilder(isValideMavenKey::class)
        ->disableOriginalConstructor()
        ->getMock();

        $isValidMavenKeyMock->method('isValideInformation')
            ->willReturn(['code' => 200]);
        $client->getContainer()->set(static::$appServiceIsValidMavenKey, $isValidMavenKeyMock);

        $informationProjetRepositoryMock = $this->getMockBuilder(InformationProjetRepository::class)
        ->disableOriginalConstructor()
        ->getMock();

        $informationProjetRepositoryMock->method('countInformationProjetAllType')
            ->willReturn(['code' => 200, 'nombre' => [['total' => 8]]]);

        // Configurer le mock pour retourner une réponse avec un code 200 et un total de 5 pour les releases
        $informationProjetRepositoryMock->method('countInformationProjetType')
        ->with($this->anything())
        ->will($this->returnCallback(function ($args) {
            if (isset($args['maven_key']) && $args['maven_key'] === static::$leChat && isset($args['type']) && $args['type'] === 'RELEASE') {
                return ['code' => 200, 'nombre' => [['total' => 5]]];
            } elseif (isset($args['maven_key']) && $args['maven_key'] === static::$leChat && isset($args['type']) && $args['type'] === 'SNAPSHOT') {
                return ['code' => 200, 'nombre' => [['total' => 3]]];
            }
            return null;
        }));

        $informationProjetRepositoryMock->method('selectInformationProjetTypeIndexed')
            ->willReturn(['code' => 500, 'type' => 'alert', 'erreur' => static::$messageLight500]);

        $client->getContainer()->set(static::$appRepositoryInformationProjectRepository, $informationProjetRepositoryMock);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiPeintureProjetVersion, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(500, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message500, $jsonResponse['message']);
    }

    public function testPeintureProjetVersionLastVersionError(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $isValidMavenKeyMock = $this->getMockBuilder(isValideMavenKey::class)
        ->disableOriginalConstructor()
        ->getMock();

        $isValidMavenKeyMock->method('isValideInformation')
            ->willReturn(['code' => 200]);
        $client->getContainer()->set(static::$appServiceIsValidMavenKey, $isValidMavenKeyMock);

        $informationProjetRepositoryMock = $this->getMockBuilder(InformationProjetRepository::class)
        ->disableOriginalConstructor()
        ->getMock();

        $informationProjetRepositoryMock->method('countInformationProjetAllType')
            ->willReturn(['code' => 200, 'nombre' => [['total' => 8]]]);

        // Configurer le mock pour retourner une réponse avec un code 200 et un total de 5 pour les releases
        $informationProjetRepositoryMock->method('countInformationProjetType')
        ->with($this->anything())
        ->will($this->returnCallback(function ($args) {
            if (isset($args['maven_key']) && $args['maven_key'] === static::$leChat && isset($args['type']) && $args['type'] === 'RELEASE') {
                return ['code' => 200, 'nombre' => [['total' => 5]]];
            } elseif (isset($args['maven_key']) && $args['maven_key'] === static::$leChat && isset($args['type']) && $args['type'] === 'SNAPSHOT') {
                return ['code' => 200, 'nombre' => [['total' => 3]]];
            }
            return null;
        }));

        // Configurer le mock pour retourner une réponse avec un code 200 et une liste de types de versions
        $informationProjetRepositoryMock->method('selectInformationProjetTypeIndexed')
        ->with($this->equalTo(['maven_key' => static::$leChat]))
        ->willReturn([
            'code' => 200,
            'liste' => [
                ['type' => 'RELEASE', 'total' => 5],
                ['type' => 'SNAPSHOT', 'total' => 3]
            ]
        ]);

        $informationProjetRepositoryMock->method('selectInformationProjetVersionLast')
            ->willReturn(['code' => 500, 'type' => 'alert', 'erreur' => static::$messageLight500]);

        $client->getContainer()->set(static::$appRepositoryInformationProjectRepository, $informationProjetRepositoryMock);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiPeintureProjetVersion, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(500, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message500, $jsonResponse['message']);
    }

    public function testPeintureProjetVersionSuccess(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $isValidMavenKeyMock = $this->getMockBuilder(isValideMavenKey::class)
        ->disableOriginalConstructor()
        ->getMock();

        $isValidMavenKeyMock->method('isValideInformation')
            ->willReturn(['code' => 200]);
        $client->getContainer()->set(static::$appServiceIsValidMavenKey, $isValidMavenKeyMock);

        $informationProjetRepositoryMock = $this->getMockBuilder(InformationProjetRepository::class)
        ->disableOriginalConstructor()
        ->getMock();

        $informationProjetRepositoryMock->method('countInformationProjetAllType')
            ->willReturn(['code' => 200, 'nombre' => [['total' => 8]]]);

        // Configurer le mock pour retourner une réponse avec un code 200 et un total de 5 pour les releases
        $informationProjetRepositoryMock->method('countInformationProjetType')
        ->with($this->anything())
        ->will($this->returnCallback(function ($args) {
            if (isset($args['maven_key']) && $args['maven_key'] === static::$leChat && isset($args['type']) && $args['type'] === 'RELEASE') {
                return ['code' => 200, 'nombre' => [['total' => 5]]];
            } elseif (isset($args['maven_key']) && $args['maven_key'] === static::$leChat && isset($args['type']) && $args['type'] === 'SNAPSHOT') {
                return ['code' => 200, 'nombre' => [['total' => 3]]];
            }
            return null;
        }));

        // Configurer le mock pour retourner une réponse avec un code 200 et une liste de types de versions
        $informationProjetRepositoryMock->method('selectInformationProjetTypeIndexed')
        ->with($this->equalTo(['maven_key' => static::$leChat]))
        ->willReturn([
            'code' => 200,
            'liste' => [
                ['type' => 'RELEASE', 'total' => 5],
                ['type' => 'SNAPSHOT', 'total' => 3]
            ]
        ]);
        /** On récupère un tableau ['projet' => '1.0-SNAPSHOT', 'date' => '2024-07-03 08:01:48+02', 'analyse_key' => 'AZB3L6XAHU0SAaF5lc_0'] */
        $informationProjetRepositoryMock->method('selectInformationProjetVersionLast')
            ->willReturn([
                'code' => 200,
                'version' => [
                    [
                        'projet' => '1.0-SNAPSHOT',
                        'date' => new \DateTimeImmutable('2024-07-03 08:01:48'),
                        'analyse_key' => 'AZB3L6XAHU0SAaF5lc_0'
                    ]
                ]]);

        $client->getContainer()->set(static::$appRepositoryInformationProjectRepository, $informationProjetRepositoryMock);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiPeintureProjetVersion, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('release', $jsonResponse);
        $this->assertArrayHasKey('snapshot', $jsonResponse);
        $this->assertArrayHasKey('autre', $jsonResponse);
        $this->assertArrayHasKey('label', $jsonResponse);
        $this->assertArrayHasKey('projet', $jsonResponse);
        $this->assertArrayHasKey('date', $jsonResponse);
        $this->assertArrayHasKey('analyse_key', $jsonResponse);

        $this->assertEquals(200, $jsonResponse['code']);
        $this->assertEquals(5, $jsonResponse['release']);
        $this->assertEquals(3, $jsonResponse['snapshot']);
        $this->assertEquals(0, $jsonResponse['autre']);
        $this->assertEquals(['RELEASE', 'SNAPSHOT'], $jsonResponse['label']);
        $this->assertEquals([5, 3], $jsonResponse['dataset']);
        $this->assertEquals('1.0-SNAPSHOT', $jsonResponse['projet']);
        $this->assertEquals(new \DateTimeImmutable('2024-07-03 08:01:48'), new \DateTimeImmutable($jsonResponse['date']['date']));
        $this->assertEquals('AZB3L6XAHU0SAaF5lc_0', $jsonResponse['analyse_key']);
    }

    public function testPeintureProjetMesuresDataNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Envoyer une requête POST avec un body null
        $client->request('POST', static::$apiPeintureProjetMesures, [], [], [static::$contentType], null);

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
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

    public function testPeintureProjetMesuresMavenKeyNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $data = ['some_key' => null ];

        // Envoyer une requête POST avec une clé différente de maven_key
        $client->request('POST', static::$apiPeintureProjetMesures, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $jsonResponse = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $jsonResponse);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);
        $this->assertArrayNotHasKey('maven_key', $jsonResponse['data'], 'Pas de clé maven_key');
        $this->assertEquals(400, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message400, $jsonResponse['message']);
    }

    public function testPeintureProjetMesuresMavenKeyNotFound(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Créer un mock pour isValidMavenKey
        $isValidMavenKeyMock = $this->getMockBuilder(isValideMavenKey::class)
        ->disableOriginalConstructor()
        ->getMock();

        // Configurer le mock pour retourner une réponse avec un code 404
        $isValidMavenKeyMock->method('isValideInformation')
            ->willReturn(['code' => 404]);
        $client->getContainer()->set(static::$appServiceIsValidMavenKey, $isValidMavenKeyMock);

        $data = ['maven_key' => 'non-existent-key2' ];
        $client->request('POST', static::$apiPeintureProjetMesures, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $jsonResponse = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(404, $jsonResponse['code']);
        $this->assertEquals('secondary', $jsonResponse['type']);
        $this->assertEquals(static::$message404, $jsonResponse['message']);
    }

    public function testPeintureProjetMesuresVersionLastError(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $isValidMavenKeyMock = $this->getMockBuilder(isValideMavenKey::class)
        ->disableOriginalConstructor()
        ->getMock();
        $mesuresRepositoryMock = $this->getMockBuilder(MesuresRepository::class)
        ->disableOriginalConstructor()
        ->getMock();

        $isValidMavenKeyMock->method('isValideInformation')
            ->willReturn(['code' => 200]);
        $client->getContainer()->set(static::$appServiceIsValidMavenKey, $isValidMavenKeyMock);

        $mesuresRepositoryMock->method('selectMesuresVersionLast')
            ->willReturn(['code' => 500, 'type' => 'alert', 'erreur' => static::$messageLight500]);
        $client->getContainer()->set(static::$appRepositoryMesuresRepository, $mesuresRepositoryMock);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiPeintureProjetMesures, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $jsonResponse = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(500, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message500, $jsonResponse['message']);
    }

    public function testPeintureProjetMesuresSuccess(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $isValidMavenKeyMock = $this->getMockBuilder(isValideMavenKey::class)
        ->disableOriginalConstructor()
        ->getMock();
        $mesuresRepositoryMock = $this->getMockBuilder(MesuresRepository::class)
        ->disableOriginalConstructor()
        ->getMock();

        $isValidMavenKeyMock->method('isValideInformation')
            ->willReturn(['code' => 200]);
        $client->getContainer()->set(static::$appServiceIsValidMavenKey, $isValidMavenKeyMock);

        $mesuresRepositoryMock->method('selectMesuresVersionLast')
            ->willReturn(['code' => 200, 'mesures' => [
                [
                    'name' => 'le-chat',
                    'ncloc' => 355,
                    'language_distribution' => '[{"java":"275","xml":"80"}]',
                    'lines' => 438,
                    'files' => 18,
                    'classes' => 26,
                    'functions' => 52,
                    'coverage' => 10.1,
                    'sqale_debt_ratio' => 5.6,
                    'duplicated_lines_density' => 0,
                    'tests' => 4,
                    'issues' => 108
                ]
            ] ]);
        $client->getContainer()->set(static::$appRepositoryMesuresRepository, $mesuresRepositoryMock);

        $data = ['maven_key' => static::$leChat ];
        $client->request('POST', static::$apiPeintureProjetMesures, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('name', $jsonResponse);
        $this->assertArrayHasKey('ncloc', $jsonResponse);
        $this->assertArrayHasKey('languages', $jsonResponse);
        $this->assertArrayHasKey('lines', $jsonResponse);
        $this->assertArrayHasKey('files', $jsonResponse);
        $this->assertArrayHasKey('classes', $jsonResponse);
        $this->assertArrayHasKey('functions', $jsonResponse);
        $this->assertArrayHasKey('coverage', $jsonResponse);
        $this->assertArrayHasKey('sqale_debt_ratio', $jsonResponse);
        $this->assertArrayHasKey('duplicated_lines_density', $jsonResponse);
        $this->assertArrayHasKey('tests', $jsonResponse);
        $this->assertArrayHasKey('issues', $jsonResponse);

        $this->assertEquals(200, $jsonResponse['code']);
        $this->assertEquals('le-chat', $jsonResponse['name']);
        $this->assertEquals(355, $jsonResponse['ncloc']);
        $this->assertEquals(["java" => "275", "xml" => "80"], $jsonResponse['languages']);
        $this->assertEquals(438, $jsonResponse['lines']);
        $this->assertEquals(18, $jsonResponse['files']);
        $this->assertEquals(26, $jsonResponse['classes']);
        $this->assertEquals(52, $jsonResponse['functions']);
        $this->assertEquals(10.1, $jsonResponse['coverage']);
        $this->assertEquals(5.6, $jsonResponse['sqale_debt_ratio']);
        $this->assertEquals(0, $jsonResponse['duplicated_lines_density']);
        $this->assertEquals(4, $jsonResponse['tests']);
        $this->assertEquals(108, $jsonResponse['issues']);
    }

    public function testPeintureProjetAnomalieDataNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Envoyer une requête POST avec un body null
        $client->request('POST', static::$apiPeintureProjetAnomalie, [], [], [static::$contentType], null);

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
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

    public function testPeintureProjetAnomalieMavenKeyNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $data = ['some_key' => null ];

        // Envoyer une requête POST avec une clé différente de maven_key
        $client->request('POST', static::$apiPeintureProjetAnomalie, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $jsonResponse = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('data', $jsonResponse);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);
        $this->assertArrayNotHasKey('maven_key', $jsonResponse['data'], 'Pas de clé maven_key');
        $this->assertEquals(400, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message400, $jsonResponse['message']);
    }

    public function testPeintureProjetAnomalieMavenKeyNotFound(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Créer un mock pour isValidMavenKey
        $isValidMavenKeyMock = $this->getMockBuilder(isValideMavenKey::class)
        ->disableOriginalConstructor()
        ->getMock();

        // Configurer le mock pour retourner une réponse avec un code 404
        $isValidMavenKeyMock->method('isValideInformation')
            ->willReturn(['code' => 404]);
        $client->getContainer()->set(static::$appServiceIsValidMavenKey, $isValidMavenKeyMock);

        $data = ['maven_key' => 'non-existent-key3' ];
        $client->request('POST', static::$apiPeintureProjetAnomalie, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $jsonResponse = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(404, $jsonResponse['code']);
        $this->assertEquals('secondary', $jsonResponse['type']);
        $this->assertEquals(static::$message404, $jsonResponse['message']);
    }

    public function testPeintureProjetAnomalieError(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $isValidMavenKeyMock = $this->getMockBuilder(isValideMavenKey::class)
        ->disableOriginalConstructor()
        ->getMock();
        $anomalieRepositoryMock = $this->getMockBuilder(AnomalieRepository::class)
        ->disableOriginalConstructor()
        ->getMock();

        $isValidMavenKeyMock->method('isValideInformation')
            ->willReturn(['code' => 200]);
        $client->getContainer()->set(static::$appServiceIsValidMavenKey, $isValidMavenKeyMock);

        $anomalieRepositoryMock->method('selectAnomalie')
            ->willReturn(['code' => 500, 'type' => 'alert', 'erreur' => static::$messageLight500]);
        $client->getContainer()->set(static::$appRepositoryAnomalieRepository, $anomalieRepositoryMock);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiPeintureProjetAnomalie, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(500, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message500, $jsonResponse['message']);
    }

    public function testPeintureProjetNotesMavenTypeError(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $isValidMavenKeyMock = $this->getMockBuilder(isValideMavenKey::class)
        ->disableOriginalConstructor()
        ->getMock();
        $anomalieRepositoryMock = $this->getMockBuilder(AnomalieRepository::class)
        ->disableOriginalConstructor()
        ->getMock();
        $notesRepositoryMock = $this->getMockBuilder(NotesRepository::class)
        ->disableOriginalConstructor()
        ->getMock();

        $isValidMavenKeyMock->method('isValideInformation')
            ->willReturn(['code' => 200]);
        $client->getContainer()->set(static::$appServiceIsValidMavenKey, $isValidMavenKeyMock);

        $anomalieRepositoryMock->method('selectAnomalie')
            ->willReturn(['code' => 200, 'liste' => [
                [
                    "maven_key" => static::$leChat,
                    "project_name" => "le-chat",
                    "anomalie_total" => 295,
                    "dette_minute" => 3054,
                    "dette_reliability_minute" => 67,
                    "dette_vulnerability_minute" => 0,
                    "dette_code_smell_minute" => 2987,
                    "dette" => static::$dette,
                    "dette_reliability" => static::$detteReliability,
                    "dette_vulnerability" => static::$detteVulnerability,
                    "dette_code_smell" => static::$detteCodeSmell,
                    "frontend" => 21,
                    "backend" => 136,
                    "autre" => 0,
                    "blocker" => 7,
                    "critical" => 13,
                    "major" => 153,
                    "info" => 109,
                    "minor" => 13,
                    "bug" => 88,
                    "vulnerability" => 9,
                    "code_smell" => 198,
                    "mode_collecte" => "COLLECTE",
                    "utilisateur_collecte" => "laurent.hadjadj@ma-petite-entreprise.fr",
                    "date_enregistrement" => new \DateTimeImmutable('2024-11-20 16:53:29')
                ]
            ]
        ]);
        $client->getContainer()->set(static::$appRepositoryAnomalieRepository, $anomalieRepositoryMock);

        $notesRepositoryMock->method('selectNotesMavenType')
            ->willReturn(['code' => 500, 'type' => 'alert', 'erreur' => static::$messageLight500]);
        $client->getContainer()->set(static::$appRepositoryNotesRepository, $notesRepositoryMock);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiPeintureProjetAnomalie, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $jsonResponse = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(500, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message500, $jsonResponse['message']);
    }

    public function testPeintureProjetAnomalieSuccess(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $isValidMavenKeyMock = $this->getMockBuilder(isValideMavenKey::class)
        ->disableOriginalConstructor()
        ->getMock();
        $anomalieRepositoryMock = $this->getMockBuilder(AnomalieRepository::class)
        ->disableOriginalConstructor()
        ->getMock();
        $notesRepositoryMock = $this->getMockBuilder(NotesRepository::class)
        ->disableOriginalConstructor()
        ->getMock();

        $isValidMavenKeyMock->method('isValideInformation')
            ->willReturn(['code' => 200]);
        $client->getContainer()->set(static::$appServiceIsValidMavenKey, $isValidMavenKeyMock);

        $anomalieRepositoryMock->method('selectAnomalie')
            ->willReturn(['code' => 200, 'liste' => [
                [
                    "maven_key" => static::$leChat,
                    "project_name" => "le-chat",
                    "anomalie_total" => 295,
                    "dette_minute" => 3054,
                    "dette_reliability_minute" => 67,
                    "dette_vulnerability_minute" => 0,
                    "dette_code_smell_minute" => 2987,
                    "dette" => static::$dette,
                    "dette_reliability" => static::$detteReliability,
                    "dette_vulnerability" => static::$detteVulnerability,
                    "dette_code_smell" => static::$detteCodeSmell,
                    "frontend" => 21,
                    "backend" => 136,
                    "autre" => 0,
                    "blocker" => 7,
                    "critical" => 13,
                    "major" => 153,
                    "info" => 109,
                    "minor" => 13,
                    "bug" => 88,
                    "vulnerability" => 9,
                    "code_smell" => 198,
                    "mode_collecte" => "COLLECTE",
                    "utilisateur_collecte" => "laurent.hadjadj@ma-petite-entreprise.fr",
                    "date_enregistrement" => new \DateTimeImmutable('2024-11-20 16:53:29')
                ]
            ]
        ]);
        $client->getContainer()->set(static::$appRepositoryAnomalieRepository, $anomalieRepositoryMock);

        $notesRepositoryMock->method('selectNotesMavenType')
            ->with($this->anything())
            ->will($this->returnCallback(function ($args) {
                if (isset($args['maven_key']) && $args['maven_key'] === static::$leChat && isset($args['type']) && $args['type'] === 'reliability') {
                    return ['code' => 200, 'liste' => [['value' => 1]]];
                } elseif (isset($args['maven_key']) && $args['maven_key'] === static::$leChat && isset($args['type']) && $args['type'] === 'security') {
                    return ['code' => 200, 'liste' => [['value' => 2]]];
                } elseif (isset($args['maven_key']) && $args['maven_key'] === static::$leChat && isset($args['type']) && $args['type'] === 'sqale') {
                    return ['code' => 200, 'liste' => [['value' => 3]]];
                }
                return null;
            }));
        $client->getContainer()->set(static::$appRepositoryNotesRepository, $notesRepositoryMock);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiPeintureProjetAnomalie, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $jsonResponse = json_decode($response->getContent(), true);

        // Vérifie que la réponse contient bien les clés attendues
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('dette', $jsonResponse);
        $this->assertArrayHasKey('detteReliability', $jsonResponse);
        $this->assertArrayHasKey('detteVulnerability', $jsonResponse);
        $this->assertArrayHasKey('detteCodeSmell', $jsonResponse);
        $this->assertArrayHasKey('detteMinute', $jsonResponse);
        $this->assertArrayHasKey('detteReliabilityMinute', $jsonResponse);
        $this->assertArrayHasKey('detteVulnerabilityMinute', $jsonResponse);
        $this->assertArrayHasKey('detteCodeSmellMinute', $jsonResponse);
        $this->assertArrayHasKey('bug', $jsonResponse);
        $this->assertArrayHasKey('vulnerability', $jsonResponse);
        $this->assertArrayHasKey('codeSmell', $jsonResponse);
        $this->assertArrayHasKey('blocker', $jsonResponse);
        $this->assertArrayHasKey('critical', $jsonResponse);
        $this->assertArrayHasKey('info', $jsonResponse);
        $this->assertArrayHasKey('major', $jsonResponse);
        $this->assertArrayHasKey('minor', $jsonResponse);
        $this->assertArrayHasKey('frontend', $jsonResponse);
        $this->assertArrayHasKey('backend', $jsonResponse);
        $this->assertArrayHasKey('autre', $jsonResponse);
        $this->assertArrayHasKey('inconnue', $jsonResponse);
        $this->assertArrayHasKey('noteReliability', $jsonResponse);
        $this->assertArrayHasKey('noteSecurity', $jsonResponse);
        $this->assertArrayHasKey('noteSqale', $jsonResponse);

        // Vérifie que les valeurs sont correctes
        $this->assertEquals(200, $jsonResponse['code']);
        $this->assertEquals(static::$dette, $jsonResponse['dette']);
        $this->assertEquals(static::$detteReliability, $jsonResponse['detteReliability']);
        $this->assertEquals(static::$detteVulnerability, $jsonResponse['detteVulnerability']);
        $this->assertEquals(static::$detteCodeSmell, $jsonResponse['detteCodeSmell']);
        $this->assertEquals(3054, $jsonResponse['detteMinute']);
        $this->assertEquals(67, $jsonResponse['detteReliabilityMinute']);
        $this->assertEquals(0, $jsonResponse['detteVulnerabilityMinute']);
        $this->assertEquals(2987, $jsonResponse['detteCodeSmellMinute']);
        $this->assertEquals(88, $jsonResponse['bug']);
        $this->assertEquals(9, $jsonResponse['vulnerability']);
        $this->assertEquals(198, $jsonResponse['codeSmell']);
        $this->assertEquals(7, $jsonResponse['blocker']);
        $this->assertEquals(13, $jsonResponse['critical']);
        $this->assertEquals(109, $jsonResponse['info']);
        $this->assertEquals(153, $jsonResponse['major']);
        $this->assertEquals(13, $jsonResponse['minor']);
        $this->assertEquals(21, $jsonResponse['frontend']);
        $this->assertEquals(136, $jsonResponse['backend']);
        $this->assertEquals(0, $jsonResponse['autre']);
        $this->assertEquals(0, $jsonResponse['inconnue']);
        $this->assertEquals(1, $jsonResponse['noteReliability']);
        $this->assertEquals(2, $jsonResponse['noteSecurity']);
        $this->assertEquals(3, $jsonResponse['noteSqale']);
    }

    public function testPeintureProjetAnomalieDetailsDataNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Envoyer une requête POST avec un body null
        $client->request('POST', static::$apiPeintureProjetAnomalieDetails, [], [], [static::$contentType], null);

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
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

    public function testPeintureProjetAnomalieDetailsMavenKeyNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $data = ['some_key' => null ];

        // Envoyer une requête POST avec une clé différente de maven_key
        $client->request('POST', static::$apiPeintureProjetAnomalieDetails, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
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

    public function testPeintureProjetAnomalieDetailsMavenKeyNotFound(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Créer un mock pour isValidMavenKey
        $isValidMavenKeyMock = $this->getMockBuilder(isValideMavenKey::class)
        ->disableOriginalConstructor()
        ->getMock();

        // Configurer le mock pour retourner une réponse avec un code 404
        $isValidMavenKeyMock->method('isValideInformation')
            ->willReturn(['code' => 404]);
        $client->getContainer()->set(static::$appServiceIsValidMavenKey, $isValidMavenKeyMock);

        $data = ['maven_key' => 'non-existent-key4' ];
        $client->request('POST', static::$apiPeintureProjetAnomalieDetails, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $jsonResponse = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(404, $jsonResponse['code']);
        $this->assertEquals('secondary', $jsonResponse['type']);
        $this->assertEquals(static::$message404, $jsonResponse['message']);
    }

    public function testPeintureProjetAnomalieDetailsError(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $isValidMavenKeyMock = $this->getMockBuilder(isValideMavenKey::class)
        ->disableOriginalConstructor()
        ->getMock();
        $anomalieDetailsRepositoryMock = $this->getMockBuilder(AnomalieDetailsRepository::class)
        ->disableOriginalConstructor()
        ->getMock();

        $isValidMavenKeyMock->method('isValideInformation')
            ->willReturn(['code' => 200]);
        $client->getContainer()->set(static::$appServiceIsValidMavenKey, $isValidMavenKeyMock);

        $anomalieDetailsRepositoryMock->method('selectAnomalieDetailsMavenKey')
            ->willReturn(['code' => 500, 'type' => 'alert', 'erreur' => static::$messageLight500]);
        $client->getContainer()->set(static::$appRepositoryAnomalieDetailsRepository, $anomalieDetailsRepositoryMock);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiPeintureProjetAnomalieDetails, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(500, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message500, $jsonResponse['message']);
    }

    public function testPeintureProjetAnomalieDetailsSuccess(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $isValidMavenKeyMock = $this->getMockBuilder(isValideMavenKey::class)
        ->disableOriginalConstructor()
        ->getMock();
        $anomalieDetailsRepositoryMock = $this->getMockBuilder(AnomalieDetailsRepository::class)
        ->disableOriginalConstructor()
        ->getMock();

        $isValidMavenKeyMock->method('isValideInformation')
            ->willReturn(['code' => 200]);
        $client->getContainer()->set(static::$appServiceIsValidMavenKey, $isValidMavenKeyMock);

        $anomalieDetailsRepositoryMock->method('selectAnomalieDetailsMavenKey')
            ->willReturn(['code' => 200, 'liste' => [
                [
                    "maven_key" => static::$leChat,
                    "name" => "le-chat",
                    "bug_blocker" => 0,
                    "bug_critical" => 0,
                    "bug_major" => 0,
                    "bug_minor" => 1870,
                    "bug_info" => 29,
                    "vulnerability_blocker" => 0,
                    "vulnerability_critical" => 0,
                    "vulnerability_major" => 3,
                    "vulnerability_minor" => 0,
                    "vulnerability_info" => 1437,
                    "code_smell_blocker" => 0,
                    "code_smell_critical" => 1192,
                    "code_smell_major" => 13619,
                    "code_smell_minor" => 13342,
                    "code_smell_info" => 8229,
                    "mode_collecte" => "COLLECTE",
                    "utilisateur_collecte" => "admin@ma-moulinette.fr",
                    "date_enregistrement" => new \DateTimeImmutable('2025-01-19 16:51:17')
                ]
            ]
        ]);
        $client->getContainer()->set(static::$appRepositoryAnomalieDetailsRepository, $anomalieDetailsRepositoryMock);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiPeintureProjetAnomalieDetails, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $jsonResponse = json_decode($response->getContent(), true);

        // Vérifie que la réponse contient bien les clés attendues
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('bugBlocker', $jsonResponse);
        $this->assertArrayHasKey('bugCritical', $jsonResponse);
        $this->assertArrayHasKey('bugMajor', $jsonResponse);
        $this->assertArrayHasKey('bugMinor', $jsonResponse);
        $this->assertArrayHasKey('bugInfo', $jsonResponse);
        $this->assertArrayHasKey('vulnerabilityBlocker', $jsonResponse);
        $this->assertArrayHasKey('vulnerabilityCritical', $jsonResponse);
        $this->assertArrayHasKey('vulnerabilityMajor', $jsonResponse);
        $this->assertArrayHasKey('vulnerabilityMinor', $jsonResponse);
        $this->assertArrayHasKey('vulnerabilityInfo', $jsonResponse);
        $this->assertArrayHasKey('codeSmellBlocker', $jsonResponse);
        $this->assertArrayHasKey('codeSmellCritical', $jsonResponse);
        $this->assertArrayHasKey('codeSmellMajor', $jsonResponse);
        $this->assertArrayHasKey('codeSmellMinor', $jsonResponse);
        $this->assertArrayHasKey('codeSmellInfo', $jsonResponse);

        // Vérifie que les valeurs sont correctes
        $this->assertEquals(200, $jsonResponse['code']);
        $this->assertEquals(0, $jsonResponse['bugBlocker']);
        $this->assertEquals(0, $jsonResponse['bugCritical']);
        $this->assertEquals(0, $jsonResponse['bugMajor']);
        $this->assertEquals(1870, $jsonResponse['bugMinor']);
        $this->assertEquals(29, $jsonResponse['bugInfo']);
        $this->assertEquals(0, $jsonResponse['vulnerabilityBlocker']);
        $this->assertEquals(0, $jsonResponse['vulnerabilityCritical']);
        $this->assertEquals(3, $jsonResponse['vulnerabilityMajor']);
        $this->assertEquals(0, $jsonResponse['vulnerabilityMinor']);
        $this->assertEquals(1437, $jsonResponse['vulnerabilityInfo']);
        $this->assertEquals(0, $jsonResponse['codeSmellBlocker']);
        $this->assertEquals(1192, $jsonResponse['codeSmellCritical']);
        $this->assertEquals(13619, $jsonResponse['codeSmellMajor']);
        $this->assertEquals(13342, $jsonResponse['codeSmellMinor']);
        $this->assertEquals(8229, $jsonResponse['codeSmellInfo']);
    }

    public function testPeintureProjetHotspotsDataNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Envoyer une requête POST avec un body null
        $client->request('POST', static::$apiPeintureProjetHotspots, [], [], [static::$contentType], null);

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
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

    public function testPeintureProjetHotspotsMavenKeyNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $data = ['some_key' => null ];

        // Envoyer une requête POST avec une clé différente de maven_key
        $client->request('POST', static::$apiPeintureProjetHotspots, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
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

    public function testPeintureProjetHotspotsMavenKeyNotFound(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Créer un mock pour isValidMavenKey
        $isValidMavenKeyMock = $this->getMockBuilder(isValideMavenKey::class)
        ->disableOriginalConstructor()
        ->getMock();

        // Configurer le mock pour retourner une réponse avec un code 404
        $isValidMavenKeyMock->method('isValideInformation')
            ->willReturn(['code' => 404]);
        $client->getContainer()->set(static::$appServiceIsValidMavenKey, $isValidMavenKeyMock);

        $data = ['maven_key' => 'non-existent-key5'];
        $client->request('POST', static::$apiPeintureProjetHotspots, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $jsonResponse = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(404, $jsonResponse['code']);
        $this->assertEquals('secondary', $jsonResponse['type']);
        $this->assertEquals(static::$message404, $jsonResponse['message']);
    }

    public function testPeintureProjetHotspotsCountToReviewError(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $isValidMavenKeyMock = $this->getMockBuilder(isValideMavenKey::class)
        ->disableOriginalConstructor()
        ->getMock();
        $hotspotsRepositoryMock = $this->getMockBuilder(HotspotsRepository::class)
        ->disableOriginalConstructor()
        ->getMock();

        $isValidMavenKeyMock->method('isValideInformation')
            ->willReturn(['code' => 200]);
        $client->getContainer()->set(static::$appServiceIsValidMavenKey, $isValidMavenKeyMock);

        $hotspotsRepositoryMock->method('countHotspotsStatus')
            ->with($this->anything())
            ->will($this->returnCallback(function ($args) {
                if (isset($args['maven_key']) && $args['maven_key'] === static::$leChat && isset($args['status']) && $args['status'] === 'TO_REVIEW') {
                return ['code' => 500, 'erreur' => static::$messageLight500, 'debug' => 'TO_REVIEW' ];
                } elseif (isset($args['maven_key']) && $args['maven_key'] === static::$leChat && isset($args['status']) && $args['status'] === 'REVIEWED') {
                return ['code' => 200, 'nombre' => [
                        [
                            'reviewed' => 5
                        ]]
                    ];
                }
                return null;
            }));
        $client->getContainer()->set(static::$appRepositoryHotspotsRepository, $hotspotsRepositoryMock);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiPeintureProjetHotspots, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);
        $this->assertArrayHasKey('debug', $jsonResponse);

        $this->assertEquals(500, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message500, $jsonResponse['message']);
        $this->assertEquals('TO_REVIEW', $jsonResponse['debug']);
    }

    public function testPeintureProjetHotspotsCountReviewedError(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $isValidMavenKeyMock = $this->getMockBuilder(isValideMavenKey::class)
        ->disableOriginalConstructor()
        ->getMock();
        $hotspotsRepositoryMock = $this->getMockBuilder(HotspotsRepository::class)
        ->disableOriginalConstructor()
        ->getMock();

        $isValidMavenKeyMock->method('isValideInformation')
            ->willReturn(['code' => 200]);
        $client->getContainer()->set(static::$appServiceIsValidMavenKey, $isValidMavenKeyMock);

        $hotspotsRepositoryMock->method('countHotspotsStatus')
            ->with($this->anything())
            ->will($this->returnCallback(function ($args) {
                if (isset($args['maven_key']) && $args['maven_key'] === static::$leChat && isset($args['status']) && $args['status'] === 'TO_REVIEW') {
                    return ['code' => 200, 'nombre' => [
                        [
                            'to_review' => 3
                        ]]
                    ];
                } elseif (isset($args['maven_key']) && $args['maven_key'] === static::$leChat && isset($args['status']) && $args['status'] === 'REVIEWED') {
                    return ['code' => 500, 'erreur' => static::$messageLight500, 'debug' => 'REVIEWED' ];
                }
                return null;
            }));
        $client->getContainer()->set(static::$appRepositoryHotspotsRepository, $hotspotsRepositoryMock);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiPeintureProjetHotspots, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);
        $this->assertArrayHasKey('debug', $jsonResponse);

        $this->assertEquals(500, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message500, $jsonResponse['message']);
        $this->assertEquals('REVIEWED', $jsonResponse['debug']);
    }


    public function testPeintureProjetHotspotsCountReviewedNotFound(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $isValidMavenKeyMock = $this->getMockBuilder(isValideMavenKey::class)
        ->disableOriginalConstructor()
        ->getMock();
        $hotspotsRepositoryMock = $this->getMockBuilder(HotspotsRepository::class)
        ->disableOriginalConstructor()
        ->getMock();

        $isValidMavenKeyMock->method('isValideInformation')
            ->willReturn(['code' => 200]);
        $client->getContainer()->set(static::$appServiceIsValidMavenKey, $isValidMavenKeyMock);

        $hotspotsRepositoryMock->method('countHotspotsStatus')
            ->with($this->anything())
            ->will($this->returnCallback(function ($args) {
                if (isset($args['maven_key']) && $args['maven_key'] === static::$leChat && isset($args['status']) && $args['status'] === 'TO_REVIEW') {
                    return ['code' => 200, 'nombre' => [
                        [
                            'to_review' => 0
                        ]]
                    ];
                } elseif (isset($args['maven_key']) && $args['maven_key'] === static::$leChat && isset($args['status']) && $args['status'] === 'REVIEWED') {
                    return ['code' => 200, 'nombre' => [
                        [
                            'reviewed' => 0
                        ]]
                    ];
                }
                return null;
            }));
        $client->getContainer()->set(static::$appRepositoryHotspotsRepository, $hotspotsRepositoryMock);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiPeintureProjetHotspots, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('note', $jsonResponse);

        $this->assertEquals(200, $jsonResponse['code']);
        $this->assertEquals('A', $jsonResponse['note']);
    }

    public function testPeintureProjetHotspotsNoteASuccess(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $isValidMavenKeyMock = $this->getMockBuilder(isValideMavenKey::class)
        ->disableOriginalConstructor()
        ->getMock();
        $hotspotsRepositoryMock = $this->getMockBuilder(HotspotsRepository::class)
        ->disableOriginalConstructor()
        ->getMock();

        $isValidMavenKeyMock->method('isValideInformation')
            ->willReturn(['code' => 200]);
        $client->getContainer()->set(static::$appServiceIsValidMavenKey, $isValidMavenKeyMock);

        $hotspotsRepositoryMock->method('countHotspotsStatus')
            ->with($this->anything())
            ->will($this->returnCallback(function ($args) {
                if (isset($args['maven_key']) && $args['maven_key'] === static::$leChat && isset($args['status']) && $args['status'] === 'TO_REVIEW') {
                    return ['code' => 200, 'nombre' => [
                        [
                            'to_review' => 1
                        ]]
                    ];
                } elseif (isset($args['maven_key']) && $args['maven_key'] === static::$leChat && isset($args['status']) && $args['status'] === 'REVIEWED') {
                    return ['code' => 200, 'nombre' => [
                        [
                            'reviewed' => 10
                        ]]
                    ];
                }
                return null;
            }));
        $client->getContainer()->set(static::$appRepositoryHotspotsRepository, $hotspotsRepositoryMock);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiPeintureProjetHotspots, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();
            // Vérifie que la réponse est un JSON valide
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('note', $jsonResponse);

        $this->assertEquals(200, $jsonResponse['code']);
        $this->assertEquals('A', $jsonResponse['note']);
    }

    public function testPeintureProjetHotspotsNoteBSuccess(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $isValidMavenKeyMock = $this->getMockBuilder(isValideMavenKey::class)
        ->disableOriginalConstructor()
        ->getMock();
        $hotspotsRepositoryMock = $this->getMockBuilder(HotspotsRepository::class)
        ->disableOriginalConstructor()
        ->getMock();

        $isValidMavenKeyMock->method('isValideInformation')
            ->willReturn(['code' => 200]);
        $client->getContainer()->set(static::$appServiceIsValidMavenKey, $isValidMavenKeyMock);

        $hotspotsRepositoryMock->method('countHotspotsStatus')
            ->with($this->anything())
            ->will($this->returnCallback(function ($args) {
                if (isset($args['maven_key']) && $args['maven_key'] === static::$leChat && isset($args['status']) && $args['status'] === 'TO_REVIEW') {
                    return ['code' => 200, 'nombre' => [
                        [
                            'to_review' => 7
                        ]]
                    ];
                } elseif (isset($args['maven_key']) && $args['maven_key'] === static::$leChat && isset($args['status']) && $args['status'] === 'REVIEWED') {
                    return ['code' => 200, 'nombre' => [
                        [
                            'reviewed' => 5
                        ]]
                    ];
                }
                return null;
            }));
        $client->getContainer()->set(static::$appRepositoryHotspotsRepository, $hotspotsRepositoryMock);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiPeintureProjetHotspots, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();
            // Vérifie que la réponse est un JSON valide
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('note', $jsonResponse);

        $this->assertEquals(200, $jsonResponse['code']);
        $this->assertEquals('B', $jsonResponse['note']);
    }

    public function testPeintureProjetHotspotsNoteCSuccess(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $isValidMavenKeyMock = $this->getMockBuilder(isValideMavenKey::class)
        ->disableOriginalConstructor()
        ->getMock();
        $hotspotsRepositoryMock = $this->getMockBuilder(HotspotsRepository::class)
        ->disableOriginalConstructor()
        ->getMock();

        $isValidMavenKeyMock->method('isValideInformation')
            ->willReturn(['code' => 200]);
        $client->getContainer()->set(static::$appServiceIsValidMavenKey, $isValidMavenKeyMock);

        $hotspotsRepositoryMock->method('countHotspotsStatus')
            ->with($this->anything())
            ->will($this->returnCallback(function ($args) {
                if (isset($args['maven_key']) && $args['maven_key'] === static::$leChat && isset($args['status']) && $args['status'] === 'TO_REVIEW') {
                    return ['code' => 200, 'nombre' => [
                        [
                            'to_review' => 3
                        ]]
                    ];
                } elseif (isset($args['maven_key']) && $args['maven_key'] === static::$leChat && isset($args['status']) && $args['status'] === 'REVIEWED') {
                    return ['code' => 200, 'nombre' => [
                        [
                            'reviewed' => 2
                        ]]
                    ];
                }
                return null;
            }));
        $client->getContainer()->set(static::$appRepositoryHotspotsRepository, $hotspotsRepositoryMock);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiPeintureProjetHotspots, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();
            // Vérifie que la réponse est un JSON valide
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('note', $jsonResponse);

        $this->assertEquals(200, $jsonResponse['code']);
        $this->assertEquals('C', $jsonResponse['note']);
    }

    public function testPeintureProjetHotspotsNoteDSuccess(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $isValidMavenKeyMock = $this->getMockBuilder(isValideMavenKey::class)
        ->disableOriginalConstructor()
        ->getMock();
        $hotspotsRepositoryMock = $this->getMockBuilder(HotspotsRepository::class)
        ->disableOriginalConstructor()
        ->getMock();

        $isValidMavenKeyMock->method('isValideInformation')
            ->willReturn(['code' => 200]);
        $client->getContainer()->set(static::$appServiceIsValidMavenKey, $isValidMavenKeyMock);

        $hotspotsRepositoryMock->method('countHotspotsStatus')
            ->with($this->anything())
            ->will($this->returnCallback(function ($args) {
                if (isset($args['maven_key']) && $args['maven_key'] === static::$leChat && isset($args['status']) && $args['status'] === 'TO_REVIEW') {
                    return ['code' => 200, 'nombre' => [
                        [
                            'to_review' => 6
                        ]]
                    ];
                } elseif (isset($args['maven_key']) && $args['maven_key'] === static::$leChat && isset($args['status']) && $args['status'] === 'REVIEWED') {
                    return ['code' => 200, 'nombre' => [
                        [
                            'reviewed' => 2
                        ]]
                    ];
                }
                return null;
            }));
        $client->getContainer()->set(static::$appRepositoryHotspotsRepository, $hotspotsRepositoryMock);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiPeintureProjetHotspots, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();
            // Vérifie que la réponse est un JSON valide
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('note', $jsonResponse);

        $this->assertEquals(200, $jsonResponse['code']);
        $this->assertEquals('D', $jsonResponse['note']);
    }

    public function testPeintureProjetHotspotsNoteESuccess(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $isValidMavenKeyMock = $this->getMockBuilder(isValideMavenKey::class)
        ->disableOriginalConstructor()
        ->getMock();
        $hotspotsRepositoryMock = $this->getMockBuilder(HotspotsRepository::class)
        ->disableOriginalConstructor()
        ->getMock();

        $isValidMavenKeyMock->method('isValideInformation')
            ->willReturn(['code' => 200]);
        $client->getContainer()->set(static::$appServiceIsValidMavenKey, $isValidMavenKeyMock);

        $hotspotsRepositoryMock->method('countHotspotsStatus')
            ->with($this->anything())
            ->will($this->returnCallback(function ($args) {
                if (isset($args['maven_key']) && $args['maven_key'] === static::$leChat && isset($args['status']) && $args['status'] === 'TO_REVIEW') {
                    return ['code' => 200, 'nombre' => [
                        [
                            'to_review' => 10
                        ]]
                    ];
                } elseif (isset($args['maven_key']) && $args['maven_key'] === static::$leChat && isset($args['status']) && $args['status'] === 'REVIEWED') {
                    return ['code' => 200, 'nombre' => [
                        [
                            'reviewed' => 1
                        ]]
                    ];
                }
                return null;
            }));
        $client->getContainer()->set(static::$appRepositoryHotspotsRepository, $hotspotsRepositoryMock);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiPeintureProjetHotspots, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();
            // Vérifie que la réponse est un JSON valide
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('note', $jsonResponse);

        $this->assertEquals(200, $jsonResponse['code']);
        $this->assertEquals('E', $jsonResponse['note']);
    }

    public function testPeintureProjetHotspotsDetailsDataNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Envoyer une requête POST avec un body null
        $client->request('POST', static::$apiPeintureProjetHotspotsDetails, [], [], [static::$contentType], null);

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
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

    public function testPeintureProjetHotspotsDetailsMavenKeyNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $data = ['some_key' => null ];

        // Envoyer une requête POST avec une clé différente de maven_key
        $client->request('POST', static::$apiPeintureProjetHotspotsDetails, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
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

    public function testPeintureProjetHotspotsDetailsMavenKeyNotFound(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Créer un mock pour isValidMavenKey
        $isValidMavenKeyMock = $this->getMockBuilder(isValideMavenKey::class)
        ->disableOriginalConstructor()
        ->getMock();

        // Configurer le mock pour retourner une réponse avec un code 404
        $isValidMavenKeyMock->method('isValideInformation')
            ->willReturn(['code' => 404]);
        $client->getContainer()->set(static::$appServiceIsValidMavenKey, $isValidMavenKeyMock);

        $data = ['maven_key' => 'non-existent-key6'];
        $client->request('POST', static::$apiPeintureProjetHotspotsDetails, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $jsonResponse = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(404, $jsonResponse['code']);
        $this->assertEquals('secondary', $jsonResponse['type']);
        $this->assertEquals(static::$message404, $jsonResponse['message']);
    }

    public function testPeintureProjetHotspotsDetailsToReviewError(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Création des mocks
        $isValidMavenKeyMock = $this->getMockBuilder(isValideMavenKey::class)
        ->disableOriginalConstructor()
        ->getMock();
        $hotspotsRepositoryMock = $this->getMockBuilder(HotspotsRepository::class)
        ->disableOriginalConstructor()
        ->getMock();

        $isValidMavenKeyMock->method('isValideInformation')
            ->willReturn(['code' => 200]);
        $client->getContainer()->set(static::$appServiceIsValidMavenKey, $isValidMavenKeyMock);

        $hotspotsRepositoryMock->method('selectHotspotsByNiveau')
            ->with($this->anything())
            ->will($this->returnCallback(function ($args) {
                if (isset($args['maven_key']) && $args['maven_key'] === static::$leChat && isset($args['status']) && $args['status'] === 'TO_REVIEW') {
                    return ['code' => 500, 'erreur' => static::$messageLight500, 'debug' => 'TO_REVIEW' ];
                } elseif (isset($args['maven_key']) && $args['maven_key'] === static::$leChat && isset($args['status']) && $args['status'] === 'REVIEWED') {
                    return ['code' => 500, 'erreur' => static::$messageLight500, 'debug' => 'REVIEWED'];
                }
                return null;
            }));
        $client->getContainer()->set(static::$appRepositoryHotspotsRepository, $hotspotsRepositoryMock);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiPeintureProjetHotspotsDetails, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);
        $this->assertArrayHasKey('debug', $jsonResponse);

        $this->assertEquals(500, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message500, $jsonResponse['message']);
        $this->assertEquals('TO_REVIEW', $jsonResponse['debug']);
    }

    public function testPeintureProjetHotspotsDetailsReviewedError(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Création des mocks
        $isValidMavenKeyMock = $this->getMockBuilder(isValideMavenKey::class)
        ->disableOriginalConstructor()
        ->getMock();
        $hotspotsRepositoryMock = $this->getMockBuilder(HotspotsRepository::class)
        ->disableOriginalConstructor()
        ->getMock();

        $isValidMavenKeyMock->method('isValideInformation')
            ->willReturn(['code' => 200]);
        $client->getContainer()->set(static::$appServiceIsValidMavenKey, $isValidMavenKeyMock);

        $hotspotsRepositoryMock->method('selectHotspotsByNiveau')
            ->with($this->anything())
            ->will($this->returnCallback(function ($args) {
                if (isset($args['maven_key']) && $args['maven_key'] === static::$leChat && isset($args['status']) && $args['status'] === 'TO_REVIEW') {
                    return ['code' => 200, 'liste' => [
                        ['niveau' => 1, 'hotspot' => 5],
                        ['niveau' => 2, 'hotspot' => 3],
                        ['niveau' => 3, 'hotspot' => 1]]
                    ];
                } elseif (isset($args['maven_key']) && $args['maven_key'] === static::$leChat && isset($args['status']) && $args['status'] === 'REVIEWED') {
                    return ['code' => 500, 'erreur' => static::$messageLight500, 'debug' => 'REVIEWED'];
                }
                return null;
            }));
        $client->getContainer()->set(static::$appRepositoryHotspotsRepository, $hotspotsRepositoryMock);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiPeintureProjetHotspotsDetails, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);
        $this->assertArrayHasKey('debug', $jsonResponse);

        $this->assertEquals(500, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message500, $jsonResponse['message']);
        $this->assertEquals('REVIEWED', $jsonResponse['debug']);
    }

    public function testPeintureProjetHotspotsDetailsSuccess(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Création des mocks
        $isValidMavenKeyMock = $this->getMockBuilder(isValideMavenKey::class)
        ->disableOriginalConstructor()
        ->getMock();
        $hotspotsRepositoryMock = $this->getMockBuilder(HotspotsRepository::class)
        ->disableOriginalConstructor()
        ->getMock();

        $isValidMavenKeyMock->method('isValideInformation')
            ->willReturn(['code' => 200]);
        $client->getContainer()->set(static::$appServiceIsValidMavenKey, $isValidMavenKeyMock);

        $hotspotsRepositoryMock->method('selectHotspotsByNiveau')
        ->with($this->anything())
        ->will($this->returnCallback(function ($args) {
            if (isset($args['maven_key']) && $args['maven_key'] === static::$leChat && isset($args['status']) && $args['status'] === 'TO_REVIEW') {
                return ['code' => 200, 'liste' => [
                    ['niveau' => 1, 'hotspot' => 5],
                    ['niveau' => 2, 'hotspot' => 3],
                    ['niveau' => 3, 'hotspot' => 1]]
                ];
            } elseif (isset($args['maven_key']) && $args['maven_key'] === static::$leChat && isset($args['status']) && $args['status'] === 'REVIEWED') {
                return ['code' => 200, 'liste' => [
                    ['niveau' => 1, 'hotspot' => 3],
                    ['niveau' => 2, 'hotspot' => 4],
                    ['niveau' => 3, 'hotspot' => 0]]
                ];
            }
            return null;
        }));
        $client->getContainer()->set(static::$appRepositoryHotspotsRepository, $hotspotsRepositoryMock);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiPeintureProjetHotspotsDetails, [], [], [static::$contentType], json_encode($data));
        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $jsonResponse = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('to_review_total', $jsonResponse);
        $this->assertArrayHasKey('to_review_high', $jsonResponse);
        $this->assertArrayHasKey('to_review_medium', $jsonResponse);
        $this->assertArrayHasKey('to_review_low', $jsonResponse);
        $this->assertArrayHasKey('to_review_low', $jsonResponse);
        $this->assertArrayHasKey('reviewed_total', $jsonResponse);
        $this->assertArrayHasKey('reviewed_high', $jsonResponse);
        $this->assertArrayHasKey('reviewed_medium', $jsonResponse);
        $this->assertArrayHasKey('reviewed_low', $jsonResponse);

        $this->assertEquals(200, $jsonResponse['code']);
        $this->assertEquals(9, $jsonResponse['to_review_total']);
        $this->assertEquals(5, $jsonResponse['to_review_high']);
        $this->assertEquals(3, $jsonResponse['to_review_medium']);
        $this->assertEquals(1, $jsonResponse['to_review_low']);
        $this->assertEquals(7, $jsonResponse['reviewed_total']);
        $this->assertEquals(3, $jsonResponse['reviewed_high']);
        $this->assertEquals(4, $jsonResponse['reviewed_medium']);
        $this->assertEquals(0, $jsonResponse['reviewed_low']);
    }

    public function testPeintureProjetNoSonarDataNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Envoyer une requête POST avec un body null
        $client->request('POST', static::$apiPeintureProjetNoSonar, [], [], [static::$contentType], null);

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
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

    public function testPeintureProjetNoSonarMavenKeyNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $data = ['some_key' => null ];

        // Envoyer une requête POST avec une clé différente de maven_key
        $client->request('POST', static::$apiPeintureProjetNoSonar, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);
        $this->assertArrayNotHasKey('maven_key', $jsonResponse['data']);

        $this->assertEquals(400, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message400, $jsonResponse['message']);
    }

    public function testPeintureProjetNoSonarMavenKeyNotFound(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Créer un mock pour isValidMavenKey
        $isValidMavenKeyMock = $this->getMockBuilder(isValideMavenKey::class)
        ->disableOriginalConstructor()
        ->getMock();

        // Configurer le mock pour retourner une réponse avec un code 404
        $isValidMavenKeyMock->method('isValideInformation')
            ->willReturn(['code' => 404]);
        $client->getContainer()->set(static::$appServiceIsValidMavenKey, $isValidMavenKeyMock);

        $data = ['maven_key' => 'non-existent-key7'];
        $client->request('POST', static::$apiPeintureProjetNoSonar, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $jsonResponse = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(404, $jsonResponse['code']);
        $this->assertEquals('secondary', $jsonResponse['type']);
        $this->assertEquals(static::$message404, $jsonResponse['message']);
    }

    public function testPeintureProjetNoSonarError(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $isValidMavenKeyMock = $this->getMockBuilder(isValideMavenKey::class)
        ->disableOriginalConstructor()
        ->getMock();
        $noSonarRepositoryMock = $this->getMockBuilder(NoSonarRepository::class)
        ->disableOriginalConstructor()
        ->getMock();

        $isValidMavenKeyMock->method('isValideInformation')
            ->willReturn(['code' => 200]);
        $client->getContainer()->set(static::$appServiceIsValidMavenKey, $isValidMavenKeyMock);

        $noSonarRepositoryMock->method('selectNoSonarRuleGroupByRule')
            ->willReturn(['code' => 500, 'type' => 'alert', 'erreur' => static::$messageLight500]);
        $client->getContainer()->set(static::$appRepositoryNoSonarRepository, $noSonarRepositoryMock);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiPeintureProjetNoSonar, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(500, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message500, $jsonResponse['message']);
    }

    public function testPeintureProjetNoSonarSuccess(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $isValidMavenKeyMock = $this->getMockBuilder(isValideMavenKey::class)
        ->disableOriginalConstructor()
        ->getMock();
        $noSonarRepositoryMock = $this->getMockBuilder(NoSonarRepository::class)
        ->disableOriginalConstructor()
        ->getMock();

        $isValidMavenKeyMock->method('isValideInformation')
            ->willReturn(['code' => 200]);
        $client->getContainer()->set(static::$appServiceIsValidMavenKey, $isValidMavenKeyMock);

        $noSonarRepositoryMock->method('selectNoSonarRuleGroupByRule')
            ->willReturn(['code' => 200, 'liste' =>
                [
                    ['rule' => 'java:NoSonar', 'total' => 23],
                    ['rule' => 'java:S1309', 'total' => 218]
                ]
            ]);
        $client->getContainer()->set(static::$appRepositoryNoSonarRepository, $noSonarRepositoryMock);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiPeintureProjetNoSonar, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('total', $jsonResponse);
        $this->assertArrayHasKey('s1309', $jsonResponse);
        $this->assertArrayHasKey('nosonar', $jsonResponse);

        $this->assertEquals(200, $jsonResponse['code']);
        $this->assertEquals(241, $jsonResponse['total']);
        $this->assertEquals(23, $jsonResponse['nosonar']);
        $this->assertEquals(218, $jsonResponse['s1309']);
    }

    public function testPeintureProjetTodoDataNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Envoyer une requête POST avec un body null
        $client->request('POST', static::$apiPeintureProjetTodo, [], [], [static::$contentType], null);

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
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

    public function testPeintureProjetTodoMavenKeyNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $data = ['some_key' => null ];

        // Envoyer une requête POST avec une clé différente de maven_key
        $client->request('POST', static::$apiPeintureProjetTodo, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);
        $this->assertArrayNotHasKey('maven_key', $jsonResponse['data']);

        $this->assertEquals(400, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message400, $jsonResponse['message']);
    }

    public function testPeintureProjetTodoMavenKeyNotFound(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Créer un mock pour isValidMavenKey
        $isValidMavenKeyMock = $this->getMockBuilder(isValideMavenKey::class)
        ->disableOriginalConstructor()
        ->getMock();

        // Configurer le mock pour retourner une réponse avec un code 404
        $isValidMavenKeyMock->method('isValideInformation')
            ->willReturn(['code' => 404]);
        $client->getContainer()->set(static::$appServiceIsValidMavenKey, $isValidMavenKeyMock);

        $data = ['maven_key' => 'non-existent-key9'];
        $client->request('POST', static::$apiPeintureProjetTodo, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $jsonResponse = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(404, $jsonResponse['code']);
        $this->assertEquals('secondary', $jsonResponse['type']);
        $this->assertEquals(static::$message404, $jsonResponse['message']);
    }

    public function testPeintureProjetTodoError(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $isValidMavenKeyMock = $this->getMockBuilder(isValideMavenKey::class)
        ->disableOriginalConstructor()
        ->getMock();
        $todoRepositoryMock = $this->getMockBuilder(TodoRepository::class)
        ->disableOriginalConstructor()
        ->getMock();

        $isValidMavenKeyMock->method('isValideInformation')
            ->willReturn(['code' => 200]);
        $client->getContainer()->set(static::$appServiceIsValidMavenKey, $isValidMavenKeyMock);

        $todoRepositoryMock->method('selectTodoRuleGroupByRule')
            ->willReturn(['code' => 500, 'type' => 'alert', 'erreur' => static::$messageLight500]);
        $client->getContainer()->set(static::$appRepositoryTodoRepository, $todoRepositoryMock);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiPeintureProjetTodo, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(500, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message500, $jsonResponse['message']);
    }

    public function testPeintureProjetTodoComponentError(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $isValidMavenKeyMock = $this->getMockBuilder(isValideMavenKey::class)
        ->disableOriginalConstructor()
        ->getMock();
        $todoRepositoryMock = $this->getMockBuilder(TodoRepository::class)
        ->disableOriginalConstructor()
        ->getMock();

        $isValidMavenKeyMock->method('isValideInformation')
            ->willReturn(['code' => 200]);
        $client->getContainer()->set(static::$appServiceIsValidMavenKey, $isValidMavenKeyMock);

        $todoRepositoryMock->method('selectTodoRuleGroupByRule')
            ->willReturn(['code' => 200, 'liste' =>
                [
                    ['rule' => static::$webS1135, 'total' => 6],
                    ['rule' => static::$tsS1135, 'total' => 7],
                    ['rule' => static::$javaS1135, 'total' => 2],
                    ['rule' => static::$xmlS1135, 'total' => 4],
                ]
            ]);

        $todoRepositoryMock->method('selectTodoComponentOrderByRule')
            ->willReturn(['code' => 500, 'type' => 'alert', 'erreur' => static::$messageLight500]);
        $client->getContainer()->set(static::$appRepositoryTodoRepository, $todoRepositoryMock);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiPeintureProjetTodo, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(500, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message500, $jsonResponse['message']);
    }

    public function testPeintureProjetTodoSuccess(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $isValidMavenKeyMock = $this->getMockBuilder(isValideMavenKey::class)
        ->disableOriginalConstructor()
        ->getMock();
        $todoRepositoryMock = $this->getMockBuilder(TodoRepository::class)
        ->disableOriginalConstructor()
        ->getMock();

        $isValidMavenKeyMock->method('isValideInformation')
            ->willReturn(['code' => 200]);
        $client->getContainer()->set(static::$appServiceIsValidMavenKey, $isValidMavenKeyMock);

        $todoRepositoryMock->method('selectTodoRuleGroupByRule')
            ->willReturn(['code' => 200, 'liste' =>
                [
                    ['rule' => static::$webS1135, 'total' => 6],
                    ['rule' => static::$tsS1135, 'total' => 7],
                    ['rule' => static::$javaS1135, 'total' => 2],
                    ['rule' => static::$xmlS1135, 'total' => 4],
                    ['rule' => static::$jsS1135, 'total' => 1]
                ]
            ]);

        $todoRepositoryMock->method('selectTodoComponentOrderByRule')
            ->willReturn(['code' => 200, 'liste' =>
                [
                    ['rule' => static::$javaS1135, 'component' => 'fr.ma-petite-entreprise:le-chat/src/main/java/fr.ma-petite-entreprise/le-chat/src/miaou/prompt.java', 'line' => 15],
                    ['rule' => static::$tsS1135, 'component' => 'fr.ma-petite-entreprise:le-chat/angular/src/app/prompt.java', 'line' => 131],
                    ['rule' => static::$webS1135, 'component' => 'fr.ma-petite-entreprise:le-chat/angular/src/prompt.component.html', 'line' => 38],
                    ['rule' => static::$xmlS1135, 'component' => 'fr.ma-petite-entreprise:le-chat/src/main/ressources/log4j2.dev.xml', 'line' => 24],
                    ['rule' => static::$jsS1135, 'component' => 'fr.ma-petite-entreprise:le-chat/src/main/ressources/static/jquery.min.js', 'line' => 1642]
                    ]
        ]);
        $client->getContainer()->set(static::$appRepositoryTodoRepository, $todoRepositoryMock);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiPeintureProjetTodo, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $jsonResponse = json_decode($response->getContent(), true);

        // Vérification des clés principales
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('todo', $jsonResponse);
        $this->assertArrayHasKey('java', $jsonResponse);
        $this->assertArrayHasKey('javascript', $jsonResponse);
        $this->assertArrayHasKey('typescript', $jsonResponse);
        $this->assertArrayHasKey('html', $jsonResponse);
        $this->assertArrayHasKey('xml', $jsonResponse);

        $this->assertEquals(200, $jsonResponse['code']);
        $this->assertEquals(20, $jsonResponse['todo']);
        $this->assertEquals(2, $jsonResponse['java']);
        $this->assertEquals(1, $jsonResponse['javascript']);
        $this->assertEquals(7, $jsonResponse['typescript']);
        $this->assertEquals(6, $jsonResponse['html']);
        $this->assertEquals(4, $jsonResponse['xml']);

        // Vérification de la clé "details"
        $this->assertArrayHasKey('details', $jsonResponse);
        $this->assertArrayHasKey('code', $jsonResponse['details']);
        $this->assertArrayHasKey('liste', $jsonResponse['details']);

        $this->assertEquals(200, $jsonResponse['details']['code']);
        $this->assertCount(5, $jsonResponse['details']['liste']);

        // Vérification des éléments de "liste"
        $this->assertEquals(static::$javaS1135, $jsonResponse['details']['liste'][0]['rule']);
        $this->assertEquals('fr.ma-petite-entreprise:le-chat/src/main/java/fr.ma-petite-entreprise/le-chat/src/miaou/prompt.java', $jsonResponse['details']['liste'][0]['component']);
        $this->assertEquals(15, $jsonResponse['details']['liste'][0]['line']);

        $this->assertEquals(static::$tsS1135, $jsonResponse['details']['liste'][1]['rule']);
        $this->assertEquals('fr.ma-petite-entreprise:le-chat/angular/src/app/prompt.java', $jsonResponse['details']['liste'][1]['component']);
        $this->assertEquals(131, $jsonResponse['details']['liste'][1]['line']);

        $this->assertEquals(static::$webS1135, $jsonResponse['details']['liste'][2]['rule']);
        $this->assertEquals('fr.ma-petite-entreprise:le-chat/angular/src/prompt.component.html', $jsonResponse['details']['liste'][2]['component']);
        $this->assertEquals(38, $jsonResponse['details']['liste'][2]['line']);

        $this->assertEquals(static::$xmlS1135, $jsonResponse['details']['liste'][3]['rule']);
        $this->assertEquals('fr.ma-petite-entreprise:le-chat/src/main/ressources/log4j2.dev.xml', $jsonResponse['details']['liste'][3]['component']);
        $this->assertEquals(24, $jsonResponse['details']['liste'][3]['line']);

        $this->assertEquals(static::$jsS1135, $jsonResponse['details']['liste'][4]['rule']);
        $this->assertEquals('fr.ma-petite-entreprise:le-chat/src/main/ressources/static/jquery.min.js', $jsonResponse['details']['liste'][4]['component']);
        $this->assertEquals(1642, $jsonResponse['details']['liste'][4]['line']);
    }

    public function testPeintureProjetLoggerMavenKeyNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $data = ['some_key' => null ];

        // Envoyer une requête POST avec une clé différente de maven_key
        $client->request('POST', static::$apiPeintureProjetLogger, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);
        $this->assertArrayNotHasKey('maven_key', $jsonResponse['data']);

        $this->assertEquals(400, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message400, $jsonResponse['message']);
    }

    public function testPeintureProjetLoggerMavenKeyNotFound(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Créer un mock pour isValidMavenKey
        $isValidMavenKeyMock = $this->getMockBuilder(isValideMavenKey::class)
        ->disableOriginalConstructor()
        ->getMock();

        // Configurer le mock pour retourner une réponse avec un code 404
        $isValidMavenKeyMock->method('isValideInformation')
            ->willReturn(['code' => 404]);
        $client->getContainer()->set(static::$appServiceIsValidMavenKey, $isValidMavenKeyMock);

        $data = ['maven_key' => 'non-existent-key9'];
        $client->request('POST', static::$apiPeintureProjetLogger, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $jsonResponse = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(404, $jsonResponse['code']);
        $this->assertEquals('secondary', $jsonResponse['type']);
        $this->assertEquals(static::$message404, $jsonResponse['message']);
    }

    public function testPeintureProjetLoggerError(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $isValidMavenKeyMock = $this->getMockBuilder(isValideMavenKey::class)
        ->disableOriginalConstructor()
        ->getMock();
        $loggerRepositoryMock = $this->getMockBuilder(LoggerRepository::class)
        ->disableOriginalConstructor()
        ->getMock();

        $isValidMavenKeyMock->method('isValideInformation')
            ->willReturn(['code' => 200]);
        $client->getContainer()->set(static::$appServiceIsValidMavenKey, $isValidMavenKeyMock);

        $loggerRepositoryMock->method('selectLogger')
            ->willReturn(['code' => 500, 'type' => 'alert', 'erreur' => static::$messageLight500]);
        $client->getContainer()->set(static::$appRepositoryLoggerRepository, $loggerRepositoryMock);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiPeintureProjetLogger, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('type', $jsonResponse);
        $this->assertArrayHasKey('message', $jsonResponse);

        $this->assertEquals(500, $jsonResponse['code']);
        $this->assertEquals('alert', $jsonResponse['type']);
        $this->assertEquals(static::$message500, $jsonResponse['message']);
    }

    public function testPeintureProjetLoggerSuccess(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $isValidMavenKeyMock = $this->getMockBuilder(isValideMavenKey::class)
        ->disableOriginalConstructor()
        ->getMock();
        $loggerRepositoryMock = $this->getMockBuilder(LoggerRepository::class)
        ->disableOriginalConstructor()
        ->getMock();

        $isValidMavenKeyMock->method('isValideInformation')
            ->willReturn(['code' => 200]);
        $client->getContainer()->set(static::$appServiceIsValidMavenKey, $isValidMavenKeyMock);

        $loggerRepositoryMock->method('selectLogger')
            ->willReturn(['code' => 200, 'liste' =>
                [
                    [ 'logger_info' => 55],
                    [ 'logger_warn' => 18],
                    [ 'logger_error' => 47],
                    [ 'logger_debug' => 59]
                ]]);
        $client->getContainer()->set(static::$appRepositoryLoggerRepository, $loggerRepositoryMock);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiPeintureProjetLogger, [], [], [static::$contentType], json_encode($data));

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $jsonResponse = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('total', $jsonResponse);
        $this->assertArrayHasKey('logger_info', $jsonResponse);
        $this->assertArrayHasKey('logger_warn', $jsonResponse);
        $this->assertArrayHasKey('logger_error', $jsonResponse);
        $this->assertArrayHasKey('logger_debug', $jsonResponse);

        $this->assertEquals(200, $jsonResponse['code']);
        $this->assertEquals(179, $jsonResponse['total']);
        $this->assertEquals(55, $jsonResponse['logger_info']);
        $this->assertEquals(18, $jsonResponse['logger_warn']);
        $this->assertEquals(47, $jsonResponse['logger_error']);
        $this->assertEquals(59, $jsonResponse['logger_debug']);

    }
}
