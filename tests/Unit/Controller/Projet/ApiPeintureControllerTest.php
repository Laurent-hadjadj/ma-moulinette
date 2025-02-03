<?php

namespace App\Tests\Unit\Controller\Projet;

use App\Service\IsValideMavenKey;
use App\Repository\AnomalieRepository;
use App\Repository\UtilisateurRepository;
use App\Repository\InformationProjetRepository;
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
    private static $appServiceIsValidMavenKey = 'App\Service\IsValideMavenKey';
    private static $appRepositoryInformationProjectRepository = 'App\Repository\InformationProjetRepository';
    private static $message400 = '<strong>[Peinture]</strong> La requête est incorrecte (Erreur 400).';
    private static $message404 = "<strong>[Peinture]</strong> Je n'ai pas trouvé les données. Vous devez lancer une collecte (Erreur 404).";
    private static $message500 = "<strong>[Peinture]</strong> Je n'ai pas trouvé d'analyse. (Erreur 500).";

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
        $this->assertEmpty($jsonResponse['data'], 'data est vide');
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
                'fr.ma-petite-entreprise:le-chat',
                static::$trackerLogger
            ],
            'favori_version' => [
                static::$maMoulinette => ['1.0.0-RELEASE', '1.5.0-RELEASE', '2.0.0-RELEASE'],
                'fr.ma-petite-entreprise:le-chat' => ['2.1.1-RELEASE']
            ],
            'bookmark' => ['fr.x:le-chat']
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
        $this->assertContains('fr.ma-petite-entreprise:le-chat', $projetKeys);
        $this->assertEquals('le-chat', $projets[1]['name']);
        $this->assertEquals(true, $projets[1]['favori']);

        // Vérification du troisième projet (qui n'est pas favori)
        $this->assertContains(static::$trackerLogger, $projetKeys);
        $this->assertEquals('tracker-logger', $projets[2]['name']);
        $this->assertEquals(false, $projets[2]['favori']);

        // 🔄 **Restaurer les préférences originales après le test **
        $testUser->setPreference($preferencesOriginales);
        $entityManager->flush();
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
        $this->assertEmpty($jsonResponse['data'], 'data est vide');
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
        $this->assertArrayNotHasKey('maven_key', $jsonResponse['data'], 'Pas de clé maven_key');
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
        $client->getContainer()->set('isValidMavenKey', $isValidMavenKeyMock);

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
            if (isset($args['maven_key']) && $args['maven_key'] === 'fr.ma-petite-entreprise:le-chat' && isset($args['type']) && $args['type'] === 'RELEASE') {
                return ['code' => 200, 'nombre' => [['total' => 5]]];
            } elseif (isset($args['maven_key']) && $args['maven_key'] === 'fr.ma-petite-entreprise:le-chat' && isset($args['type']) && $args['type'] === 'SNAPSHOT') {
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
}
