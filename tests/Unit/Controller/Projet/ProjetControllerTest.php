<?php

namespace App\Tests\Unit\Controller\Projet;

use App\Entity\Utilisateur;
use App\Service\MesProjets;
use App\Repository\HistoriqueRepository;
use App\Repository\UtilisateurRepository;
use App\Controller\Projet\ProjetController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * [Description projetControllerTest]
 */
class ProjetControllerTest extends WebTestCase
{

    private static $josh = 'josh.liberman@ma-moulinette.fr';
    private static $leChat = 'fr.ma-petite-entreprise:le-chat';
    private static $maMoulinette = 'fr.ma-petite-entreprise:ma-moulinette';
    private static $erreur406 = 'Erreur 406: Not Acceptable';

    public function testProjet(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        // Mock du service de sécurité
        $securityMock = $this->createMock(Security::class);
        $userMock = $this->createMock(Utilisateur::class);
        $preferenceMock = [
            'statut' => ['bookmark' => true],
            'bookmark' => [static::$leChat]
        ];
        $userMock->method('getPreference')->willReturn($preferenceMock);
        $securityMock->method('getUser')->willReturn($userMock);
        $container->set(Security::class, $securityMock);

        // Connexion de l'utilisateur
        $userRepository = $container->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$josh);
        $client->loginUser($testUser);

        // Envoyer une requête GET
        $client->request('GET', '/projet');

        $response = $client->getResponse();

        // Vérifie que la réponse est un succès
        $this->assertEquals(200, $response->getStatusCode());

        // Vérifie que la réponse contient les bookmarks attendus
        $this->assertStringContainsString(static::$leChat, $response->getContent());
    }

    public function testMesProjets(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        // Mock du service de sécurité
        $securityMock = $this->createMock(Security::class);
        $userMock = $this->createMock(Utilisateur::class);
        $teamsMock = ['team1', 'team2'];
        $userMock->method('getEquipe')->willReturn($teamsMock);
        $securityMock->method('getUser')->willReturn($userMock);
        $container->set(Security::class, $securityMock);

        // Mock du service MesProjetsService
        $mesProjetsMock = $this->createMock(MesProjets::class);
        $mesProjetsMock->method('liste')->willReturn([
            'code' => 200,
            'projets' =>
              [
                ['id' => static::$leChat],
                ['id' => static::$maMoulinette]
              ]
        ]);
        $container->set(MesProjets::class, $mesProjetsMock);

        // Mock du repository HistoriqueRepository
        $historiqueRepositoryMock = $this->createMock(HistoriqueRepository::class);
        $historiqueRepositoryMock->method('selectHistoriqueIndicateurs')->willReturn([
            'code' => 200,
            'indicateur' =>
            [
              [
                "nom_projet" => static::$leChat,
                "version" => "2.2.1-RELEASE",
                "suppress_warning" => 8,
                "no_sonar" => 0,
                "todo" => 17,
                "nombre_ligne" => 15893,
                "nombre_ligne_code" => 8350,
                "tests" => 55,
                "violations" => 144,
                "nombre_bug" => 0,
                "nombre_vulnerability" => 0,
                "nombre_code_smell" => 144,
                "frontend" => 32,
                "backend" => 26,
                "autre" => 0,
                "inconnue" => null,
                "note_reliability" => "A",
                "note_security" => "A",
                "note_sqale" => "A",
                "note_hotspot" => "A",
                "logger_info" => 15,
                "logger_warn" => 0,
                "logger_error" => 15,
                "logger_debug" => 8
              ],
              [
                "nom_projet" => static::$maMoulinette,
                "version" => "1.1.0-RELEASE",
                "suppress_warning" => 0,
                "no_sonar" => 0,
                "todo" => 0,
                "nombre_ligne" => 1404,
                "nombre_ligne_code" => 1200,
                "tests" => 1,
                "violations" => 701,
                "nombre_bug" => 29,
                "nombre_vulnerability" => 2,
                "nombre_code_smell" => 670,
                "frontend" => 0,
                "backend" => 0,
                "autre" => 0,
                "inconnue" => null,
                "note_reliability" => "C",
                "note_security" => "C",
                "note_sqale" => "D",
                "note_hotspot" => "A",
                "logger_info" => -1,
                "logger_warn" => -1,
                "logger_error" => -1,
                "logger_debug" => -1
              ]
            ]
        ]);
        $container->set(HistoriqueRepository::class, $historiqueRepositoryMock);

        // Connexion de l'utilisateur
        $userRepository = $container->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$josh);
        $client->loginUser($testUser);

        // Envoyer une requête GET
        $client->request('GET', '/projet/mes-projets');

        $response = $client->getResponse();

        // Vérifie que la réponse est un succès
        $this->assertEquals(200, $response->getStatusCode());

        // Vérifie que la réponse contient les indicateurs attendus
        $this->assertStringContainsString(static::$leChat, $response->getContent());
        $this->assertStringContainsString(static::$maMoulinette, $response->getContent());
    }

    public function testMesProjetsWithoutTeam(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        // Mock du service de sécurité
        $securityMock = $this->createMock(Security::class);
        $userMock = $this->createMock(Utilisateur::class);
        $userMock->method('getEquipe')->willReturn([]);
        $securityMock->method('getUser')->willReturn($userMock);
        $container->set(Security::class, $securityMock);

        // Connexion de l'utilisateur
        $userRepository = $container->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$josh);
        $client->loginUser($testUser);

        // Envoyer une requête GET
        $crawler = $client->request('GET', '/projet/mes-projets');

        $response = $client->getResponse();

        // Vérifie que la réponse est un succès
        $this->assertEquals(200, $response->getStatusCode());

        // Vérifie que la réponse contient le message d'erreur attendu
        $this->assertStringContainsString(ProjetController::$erreur404, $response->getContent());

        // Vérifie que le message flash est correctement rendu dans la page
        $this->assertCount(1, $crawler->filter('div.callout.alert-callout-border.warning'));
        $this->assertStringContainsString(ProjetController::$erreur404, $crawler->filter('div.callout.alert-callout-border.warning p.callout-message')->text());
    }

    public function testMesProjetsWith406Code(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        // Mock du service de sécurité
        $securityMock = $this->createMock(Security::class);
        $userMock = $this->createMock(Utilisateur::class);
        $teamsMock = ['team1', 'team2'];
        $userMock->method('getEquipe')->willReturn($teamsMock);
        $securityMock->method('getUser')->willReturn($userMock);
        $container->set(Security::class, $securityMock);

        // Mock du service MesProjetsService
        $mesProjetsServiceMock = $this->createMock(MesProjets::class);
        $mesProjetsServiceMock->method('liste')->willReturn([
            'code' => 406,
            'message' => static::$erreur406,
            'erreur' => 'Erreur détaillée'
        ]);
        $container->set(MesProjets::class, $mesProjetsServiceMock);

        // Connexion de l'utilisateur
        $userRepository = $container->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$josh);
        $client->loginUser($testUser);

        // Envoyer une requête GET
        $crawler = $client->request('GET', '/projet/mes-projets');

        $response = $client->getResponse();

        // Vérifie que la réponse est un succès
        $this->assertEquals(200, $response->getStatusCode());

        // Vérifie que la réponse contient le message d'erreur attendu
        $this->assertStringContainsString(static::$erreur406, $response->getContent());

        // Vérifie que le message flash est correctement rendu dans la page
        $this->assertCount(1, $crawler->filter('div.callout.alert-callout-border.warning'));
        $this->assertStringContainsString(static::$erreur406, $crawler->filter('div.callout.alert-callout-border.warning p.callout-message')->text());
    }

    public function testMesProjetsWithHistoriqueError(): void
    {
        $client = static::createClient();
        $container = static::getContainer();

        // Mock du service de sécurité
        $securityMock = $this->createMock(Security::class);
        $userMock = $this->createMock(Utilisateur::class);
        $teamsMock = ['team1', 'team2'];
        $userMock->method('getEquipe')->willReturn($teamsMock);
        $securityMock->method('getUser')->willReturn($userMock);
        $container->set(Security::class, $securityMock);

        // Mock du service MesProjetsService
        $mesProjetsServiceMock = $this->createMock(MesProjets::class);
        $mesProjetsServiceMock->method('liste')->willReturn([
            'code' => 200,
            'projets' => [['id' => 1], ['id' => 2]]
        ]);
        $container->set(MesProjets::class, $mesProjetsServiceMock);

        // Mock du repository HistoriqueRepository
        $historiqueRepositoryMock = $this->createMock(HistoriqueRepository::class);
        $historiqueRepositoryMock->method('selectHistoriqueIndicateurs')->willReturn([
            'code' => 404,
            'erreur' => 'Erreur détaillée'
        ]);
        $container->set(HistoriqueRepository::class, $historiqueRepositoryMock);

        // Connexion de l'utilisateur
        $userRepository = $container->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$josh);
        $client->loginUser($testUser);

        // Envoyer une requête GET
        $crawler = $client->request('GET', '/projet/mes-projets');

        $response = $client->getResponse();

        // Vérifie que la réponse est un succès
        $this->assertEquals(200, $response->getStatusCode());

        // Vérifie que la réponse contient le message d'erreur attendu
        $this->assertStringContainsString(ProjetController::$erreur404, $response->getContent());

        // Vérifie que le message flash est correctement rendu dans la page
        $this->assertCount(1, $crawler->filter('div.callout.alert-callout-border.erreur'));
        $this->assertStringContainsString(ProjetController::$erreur404, $crawler->filter('div.callout.alert-callout-border.erreur p.callout-message')->text());
    }
}
