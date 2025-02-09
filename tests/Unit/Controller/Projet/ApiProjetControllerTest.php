<?php

namespace App\Tests\Unit\Controller\Projet;

use App\Repository\UtilisateurRepository;
use App\Repository\ListeProjetRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * [Description ApiProjetControllerTest]
 */
class ApiProjetControllerTest extends WebTestCase
{
    private static $contentType = 'application/json';
    private static $aurelie = 'aurelie.petit-coeur@ma-moulinette.fr';
    private static $leChat = 'fr.ma-petite-entreprise:le-chat';

    private static $apiFavori = '/api/favori';
    private static $apiFavoriCheck = '/api/favori/check';
    private static $apiProjetListe = '/api/projet/liste';

    private static $message400 = '<strong>[Projet]</strong> La requête est incorrecte (Erreur 400).';
    private static $preferenceErreur400 = ' Format des préférences invalide (Erreur 400).';
    private static $message404 = "<strong>[Projet]</strong> Vous devez être rattaché à une équipe (Erreur 404).";
    private static $message406 = "<strong>[Projet]</strong> Je n'ai pas trouvé de projets pour ton équipe. ".
    "Vérifiez le nom du tag utilisé dans SonarQube (Erreur 406).";
    private static $message500 = "<strong>[Projet]</strong> Je n'ai pas trouvé d'analyse (Erreur 500).";
    private static $messageLight500 = "Je n'ai pas trouvé d'analyse (Erreur 500).";

    public function testProjetFavoriDataNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Envoyer une requête POST avec un body null
        $client->request('POST', static::$apiFavori, [], [], [static::$contentType], null);

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

    public function testProjetFavoriMavenKeyNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $data = ['some_key' => null ];

        // Envoyer une requête POST avec une clé différente de maven_key
        $client->request('POST', static::$apiFavori, [], [], [static::$contentType], json_encode($data));

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

    public function testProjetFavoriUpdatePreferenceError(): void
    {
      $client = static::createClient();
      $container = static::getContainer();

      $entityManager = $container->get('doctrine')->getManager();

      $userRepository = static::getContainer()->get(UtilisateurRepository::class);
      $testUser = $userRepository->findOneByCourriel(static::$aurelie);

      // Sauvegarde des préférences actuelles
      $preferencesOriginales = $testUser->getPreference();

      // 🔴 Créer un cas invalide pour casser JSON (ex : valeur non sérialisable)
      $preferencesTest = [
        'statut' => [],
        'suivi_projet' => [],
        'favori_projet' => (object) ['key' => 'invalid'], // ❌ Objet au lieu d’un tableau
        'favori_version' => [],
        'bookmark' => []
      ];

      $testUser->setPreference($preferencesTest);
      $entityManager->flush();

      /** On se connecte */
      $client->loginUser($testUser);

      $data = ['maven_key' => static::$leChat];
      $client->request('POST', static::$apiFavori, [], [], [static::$contentType], json_encode($data));

      // 🔄 **Restaurer les préférences originales après le test**
      $testUser->setPreference($preferencesOriginales);
      $entityManager->flush();

      $response = $client->getResponse();

      // Vérifie que la réponse est un JSON valide
      $jsonResponse = json_decode($response->getContent(), true);

      $this->assertArrayHasKey('code', $jsonResponse);
      $this->assertArrayHasKey('type', $jsonResponse);
      $this->assertArrayHasKey('message', $jsonResponse);

      // Vérifie que l'erreur 400 est bien renvoyée
      $this->assertEquals(400, $jsonResponse['code']);
      $this->assertEquals('alert', $jsonResponse['type']);
      $this->assertStringContainsString(static::$preferenceErreur400, $jsonResponse['message']);
    }

    public function testProjetFavoriUpdatePreferenceSuccess(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

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
              static::$leChat,
          ],
          'favori_version' => [
              static::$leChat => ['2.1.1-RELEASE']
          ],
          'bookmark' => [static::$leChat]
        ];

        /** On met à jour les préférences */
        $testUser->setPreference($preferencesTest);
        $entityManager->flush();

        /** On se connecte */
        $client->loginUser($testUser);

        $data = ['maven_key' => static::$leChat];
        $client->request('POST', static::$apiFavori, [], [], [static::$contentType], json_encode($data));

        // 🔄 **Restaurer les préférences originales après le test **
        $testUser->setPreference($preferencesOriginales);
        $entityManager->flush();

        $response = $client->getResponse();

        // Vérifie que la réponse est un JSON valide
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('statut', $jsonResponse);

        $this->assertEquals(200, $jsonResponse['code']);
        $this->assertEquals(0, $jsonResponse['statut']);
    }

    public function testProjetFavoriCheckDataNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Envoyer une requête POST avec un body null
        $client->request('POST', static::$apiFavoriCheck, [], [], [static::$contentType], null);

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

    public function testProjetFavoriCheckMavenKeyNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $data = ['some_key' => null ];

        // Envoyer une requête POST avec une clé différente de maven_key
        $client->request('POST', static::$apiFavoriCheck, [], [], [static::$contentType], json_encode($data));

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

    public function testProjetFavoriCheckSuccess(): void
    {
      $client = static::createClient();
      $container = static::getContainer();

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
            static::$leChat,
        ],
        'favori_version' => [
            static::$leChat => ['2.1.1-RELEASE']
        ],
        'bookmark' => [static::$leChat]
      ];

      /** On met à jour les préférences */
      $testUser->setPreference($preferencesTest);
      $entityManager->flush();

      /** On se connecte */
      $client->loginUser($testUser);

      $data = ['maven_key' => static::$leChat];
      $client->request('POST', static::$apiFavoriCheck, [], [], [static::$contentType], json_encode($data));

      // 🔄 **Restaurer les préférences originales après le test **
      $testUser->setPreference($preferencesOriginales);
      $entityManager->flush();

      $response = $client->getResponse();

      // Vérifie que la réponse est un JSON valide
      $jsonResponse = json_decode($response->getContent(), true);

      $this->assertArrayHasKey('code', $jsonResponse);
      $this->assertArrayHasKey('favori', $jsonResponse);

      $this->assertEquals(200, $jsonResponse['code']);
      $this->assertTrue($jsonResponse['favori']);
    }

    public function testProjetListeGroupeNull(): void
    {
      $client = static::createClient();
      $container = static::getContainer();

      $entityManager = $container->get('doctrine')->getManager();

      $userRepository = static::getContainer()->get(UtilisateurRepository::class);
      $testUser = $userRepository->findOneByCourriel(static::$aurelie);

      // Sauvegarde du groupe actuel
      $groupeActuel = $testUser->getEquipe();

      // Modification du groupe
      $groupeTest = [];

      /** On met à jour les préférences */
      $testUser->setEquipe($groupeTest);
      $entityManager->flush();

      /** On se connecte */
      $client->loginUser($testUser);

      // Envoyer une requête POST
      $client->request('POST', static::$apiProjetListe, [], [], [static::$contentType]);

      // 🔄 **Restaure le groupe après le test **
      $testUser->setEquipe($groupeActuel);
      $entityManager->flush();

      $response = $client->getResponse();

      // Vérifie que la réponse est un JSON valide
      $jsonResponse = json_decode($response->getContent(), true);

      $this->assertArrayHasKey('code', $jsonResponse);
      $this->assertArrayHasKey('type', $jsonResponse);
      $this->assertArrayHasKey('message', $jsonResponse);

      $this->assertEquals(404, $jsonResponse['code']);
      $this->assertEquals('alert', $jsonResponse['type']);
      $this->assertEquals(static::$message404, $jsonResponse['message']);
    }

    public function testProjetListeEquipe500Error(): void
    {
      $client = static::createClient();
      $container = static::getContainer();

      $listeProjetRepositoryMock = $this->getMockBuilder(ListeProjetRepository::class)
        ->disableOriginalConstructor()
        ->getMock();

      $listeProjetRepositoryMock->method('selectListeProjetByEquipe')
        ->willReturn(['code' => 500, 'type' => 'alert', 'erreur' => static::$messageLight500]);
      $container->set(ListeProjetRepository::class, $listeProjetRepositoryMock);

      $entityManager = $container->get('doctrine')->getManager();

      $userRepository = static::getContainer()->get(UtilisateurRepository::class);
      $testUser = $userRepository->findOneByCourriel(static::$aurelie);

      // Sauvegarde du groupe actuel
      $groupeActuel = $testUser->getEquipe();

      // Modification du groupe
      $groupeTest = ['le-chat'];

      /** On met à jour les préférences */
      $testUser->setEquipe($groupeTest);
      $entityManager->flush();

      /** On se connecte */
      $client->loginUser($testUser);

      // Envoyer une requête POST
      $client->request('POST', static::$apiProjetListe, [], [], [static::$contentType]);

      // 🔄 **Restaure le groupe après le test **
      $testUser->setEquipe($groupeActuel);
      $entityManager->flush();

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

    public function testProjetListeEquipe406Error(): void
    {
      $client = static::createClient();
      $container = static::getContainer();

      $listeProjetRepositoryMock = $this->getMockBuilder(ListeProjetRepository::class)
        ->disableOriginalConstructor()
        ->getMock();

      $listeProjetRepositoryMock->method('selectListeProjetByEquipe')
        ->willReturn(['code' => 200, 'liste' => []]);
      $container->set(ListeProjetRepository::class, $listeProjetRepositoryMock);

      $entityManager = $container->get('doctrine')->getManager();

      $userRepository = static::getContainer()->get(UtilisateurRepository::class);
      $testUser = $userRepository->findOneByCourriel(static::$aurelie);

      // Sauvegarde du groupe actuel
      $groupeActuel = $testUser->getEquipe();

      // Modification du groupe
      $groupeTest = ['le-chat'];

      /** On met à jour les préférences */
      $testUser->setEquipe($groupeTest);
      $entityManager->flush();

      /** On se connecte */
      $client->loginUser($testUser);

      // Envoyer une requête POST
      $client->request('POST', static::$apiProjetListe, [], [], [static::$contentType]);

      // 🔄 **Restaure le groupe après le test **
      $testUser->setEquipe($groupeActuel);
      $entityManager->flush();

      $response = $client->getResponse();

      // Vérifie que la réponse est un JSON valide
      $jsonResponse = json_decode($response->getContent(), true);

      $this->assertArrayHasKey('code', $jsonResponse);
      $this->assertArrayHasKey('type', $jsonResponse);
      $this->assertArrayHasKey('message', $jsonResponse);

      $this->assertEquals(406, $jsonResponse['code']);
      $this->assertEquals('warning', $jsonResponse['type']);
      $this->assertEquals(static::$message406, $jsonResponse['message']);
    }

    public function testProjetListeEquipeSuccess(): void
    {
      $client = static::createClient();
      $container = static::getContainer();

      $listeProjetRepositoryMock = $this->getMockBuilder(ListeProjetRepository::class)
        ->disableOriginalConstructor()
        ->getMock();

      $listeProjetRepositoryMock->method('selectListeProjetByEquipe')
        ->willReturn(['code' => 200, 'liste' =>
                        [
                          ['id' => static::$leChat, 'text' => 'le-chat']
                        ]
                      ]);
      $container->set(ListeProjetRepository::class, $listeProjetRepositoryMock);

      $entityManager = $container->get('doctrine')->getManager();

      $userRepository = static::getContainer()->get(UtilisateurRepository::class);
      $testUser = $userRepository->findOneByCourriel(static::$aurelie);

      // Sauvegarde du groupe actuel
      $groupeActuel = $testUser->getEquipe();

      // Modification du groupe
      $groupeTest = ['le-chat'];

      /** On met à jour les préférences */
      $testUser->setEquipe($groupeTest);
      $entityManager->flush();

      /** On se connecte */
      $client->loginUser($testUser);

      // Envoyer une requête POST
      $client->request('POST', static::$apiProjetListe, [], [], [static::$contentType]);

      // 🔄 **Restaure le groupe après le test **
      $testUser->setEquipe($groupeActuel);
      $entityManager->flush();

      $response = $client->getResponse();

      // Vérifie que la réponse est un JSON valide
      $jsonResponse = json_decode($response->getContent(), true);

      $this->assertArrayHasKey('code', $jsonResponse);
      $this->assertArrayHasKey('projet', $jsonResponse);

      $this->assertEquals(200, $jsonResponse['code']);
      $this->assertEquals(static::$leChat, $jsonResponse['projet'][0]['id']);
      $this->assertEquals('le-chat', $jsonResponse['projet'][0]['text']);
    }
  }
