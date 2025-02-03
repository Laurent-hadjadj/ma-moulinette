<?php

namespace App\Tests\Unit\Controller\Projet;

use App\Entity\Historique;
use App\Repository\HistoriqueRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * [Description ApiEnregistrementControllerTest]
 */
class ApiEnregistrementControllerTest extends WebTestCase
{
    private static $contentType = 'application/json';
    private static $erreur400 = 'La requête est incorrecte (Erreur 400).';
    private static $reference = "<strong>[Enregistrement]</strong> ";
    private static $josh = 'josh.liberman@ma-moulinette.fr';
    private static $aurelie = 'aurelie.petit-coeur@ma-moulinette.fr';
    private static $apiEnregistrement = '/api/enregistrement';

    public function testEnregistrementAvecDataNull(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        //Envoyer une requête avec un body vide
        $client->request('PUT', static::$apiEnregistrement, [], [], ['CONTENT_TYPE' => static::$contentType], null);
        $response = $client->getResponse();

        $this->assertNotEmpty($response->getContent(), 'La réponse de l\'API est vide.');
        $jsonResponse = json_decode($response->getContent(), true);

        $this->assertNotNull($jsonResponse, 'La réponse JSON est null.');
        $this->assertEquals(200, $response->getStatusCode(), 'Le code HTTP attendu est 200.');
        $this->assertNull($jsonResponse['data'], 'Le champ "data" devrait être null.');
        $this->assertEquals(400, $jsonResponse['code'], 'Le code attendu est 400.');
        $this->assertEquals('alert', $jsonResponse['type'], 'Le type attendu est "alert".');
        $expectedMessage = static::$reference . static::$erreur400;
        $this->assertStringContainsString($expectedMessage, $jsonResponse['message']);
    }

    public function testEnregistrement(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        /** On supprime la maven_key avant de l'insérer */
        $historiqueRepository = static::getContainer()->get(HistoriqueRepository::class);
        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');

        $historique = $historiqueRepository->findOneBy(['mavenKey' => 'example_key', 'analyseKey' => 'example_key']);
        if ($historique) {
            $entityManager->remove($historique);
            $entityManager->flush();
        }

        $data = [
            'maven_key' => 'example_key', 'analyse_key' => 'example_key', 'version' => '1.0.1',
            'date_version' => '2023-10-02', 'nom_projet' => 'Example Project2', 'version_release' => 2,
            'version_snapshot' => 4, 'version_autre' => 0, 'suppress_warning' => 10, 'no_sonar' => 140,
            'todo' => 13, 'logger_info' => 45, 'logger_warn' => 5, 'logger_error' => 12,
            'logger_debug' => 23, 'nombre_ligne' => 1000, 'nombre_ligne_code' => 800, 'coverage' => 90.7,
            'files' => 45, 'classes' => 120, 'functions' => 342, 'duplicated_lines_density' => 5,
            'sqale_debt_ratio' => 0.5, 'tests' => 50, 'violations' => 10, 'dette' => 20, 'nombre_bug' => 5,
            'nombre_vulnerability' => 2, 'nombre_code_smell' => 3, 'bug_blocker' => 1, 'bug_critical' => 1,
            'bug_major' => 1, 'bug_minor' => 1, 'bug_info' => 1, 'vulnerability_blocker' => 1,
            'vulnerability_critical' => 1, 'vulnerability_major' => 1, 'vulnerability_minor' => 1,
            'vulnerability_info' => 1, 'code_smell_blocker' => 1, 'code_smell_critical' => 1,
            'code_smell_major' => 1, 'code_smell_minor' => 1, 'code_smell_info' => 1,
            'frontend' => 159, 'backend' => 30, 'autre' => 12, 'inconnue' => 0,
            'nombre_anomalie_bloquant' => 1, 'nombre_anomalie_critique' => 1, 'nombre_anomalie_majeur' => 1,
            'nombre_anomalie_mineur' => 1, 'nombre_anomalie_info' => 1, 'note_reliability' => 1,
            'note_security' => 2, 'note_sqale' => 3, 'note_hotspot' => 4, 'nombre_hotspot' => 10,
            'hotspot_high' => 5, 'hotspot_medium' => 3, 'hotspot_low' => 2,
        ];

        $client->request('PUT', static::$apiEnregistrement, [], [], ['CONTENT_TYPE' => static::$contentType], json_encode($data));

        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);
        $this->assertEquals(200, $jsonResponse['code']);
    }

    public function testEnregistrementHistoriqueAlreadySave(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        $data = [
            'maven_key' => 'example_key', 'analyse_key' => 'example_key', 'version' => '1.0.0',
            'date_version' => '2023-10-02', 'nom_projet' => 'Exemple Project', 'version_release' => 2,
            'version_snapshot' => 4, 'version_autre' => 0, 'suppress_warning' => 10, 'no_sonar' => 140,
            'todo' => 13, 'logger_info' => 45, 'logger_warn' => 5, 'logger_error' => 12,
            'logger_debug' => 23, 'nombre_ligne' => 1000, 'nombre_ligne_code' => 800, 'coverage' => 90.7,
            'files' => 45, 'classes' => 120, 'functions' => 342, 'duplicated_lines_density' => 5,
            'sqale_debt_ratio' => 0.5, 'tests' => 50, 'violations' => 10, 'dette' => 20, 'nombre_bug' => 5,
            'nombre_vulnerability' => 2, 'nombre_code_smell' => 3, 'bug_blocker' => 1, 'bug_critical' => 1,
            'bug_major' => 1, 'bug_minor' => 1, 'bug_info' => 1, 'vulnerability_blocker' => 1,
            'vulnerability_critical' => 1, 'vulnerability_major' => 1, 'vulnerability_minor' => 1,
            'vulnerability_info' => 1, 'code_smell_blocker' => 1, 'code_smell_critical' => 1,
            'code_smell_major' => 1, 'code_smell_minor' => 1, 'code_smell_info' => 1,
            'frontend' => 159, 'backend' => 30, 'autre' => 12, 'inconnue' => 0,
            'nombre_anomalie_bloquant' => 1, 'nombre_anomalie_critique' => 1, 'nombre_anomalie_majeur' => 1,
            'nombre_anomalie_mineur' => 1, 'nombre_anomalie_info' => 1, 'note_reliability' => 1,
            'note_security' => 2, 'note_sqale' => 3, 'note_hotspot' => 4, 'nombre_hotspot' => 10,
            'hotspot_high' => 5, 'hotspot_medium' => 3, 'hotspot_low' => 2,
        ];

        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
        $historiqueRepository = static::getContainer()->get(HistoriqueRepository::class);
        $historique = $historiqueRepository->findOneBy(['mavenKey' => 'example_key', 'version' => '1.0.0', 'dateVersion' => '2023-10-02']);

        /** Si la table contient un enregistrement, on le supprime */
        if ($historique){
            $entityManager->remove($historique);
            $entityManager->flush();
        }

        /** Si la table est vide on ajoute un enregistrement */
        $historique = new Historique();
            $historique->setMavenKey('example_key');
            $historique->setAnalyseKey('example_key');
            $historique->setVersion('1.0.0');
            $historique->setDateVersion('2023-10-02');
            $historique->setNomProjet('Example Project');
            $historique->setVersionRelease(2);
            $historique->setVersionSnapshot(4);
            $historique->setVersionAutre(0);
            $historique->setSuppressWarning(10);
            $historique->setNoSonar(140);
            $historique->setTodo(13);
            $historique->setLoggerInfo(45);
            $historique->setLoggerWarn(5);
            $historique->setLoggerError(12);
            $historique->setLoggerDebug(23);
            $historique->setNombreLigne(1000);
            $historique->setNombreLigneCode(800);
            $historique->setCoverage(90.7);
            $historique->setFiles(45);
            $historique->setClasses(120);
            $historique->setFunctions(342);
            $historique->setDuplicatedLinesDensity(5);
            $historique->setSqaleDebtRatio(0.5);
            $historique->setTests(50);
            $historique->setViolations(10);
            $historique->setDette(20);
            $historique->setNombreBug(5);
            $historique->setNombreVulnerability(2);
            $historique->setNombreCodeSmell(3);
            $historique->setBugBlocker(1);
            $historique->setBugCritical(1);
            $historique->setBugMajor(1);
            $historique->setBugMinor(1);
            $historique->setBugInfo(1);
            $historique->setVulnerabilityBlocker(1);
            $historique->setVulnerabilityCritical(1);
            $historique->setVulnerabilityMajor(1);
            $historique->setVulnerabilityMinor(1);
            $historique->setVulnerabilityInfo(1);
            $historique->setCodeSmellBlocker(1);
            $historique->setCodeSmellCritical(1);
            $historique->setCodeSmellMajor(1);
            $historique->setCodeSmellMinor(1);
            $historique->setCodeSmellInfo(1);
            $historique->setFrontend(159);
            $historique->setBackend(30);
            $historique->setAutre(12);
            $historique->setInconnue(0);
            $historique->setNombreAnomalieBloquant(1);
            $historique->setNombreAnomalieCritique(1);
            $historique->setNombreAnomalieMajeur(1);
            $historique->setNombreAnomalieMineur(1);
            $historique->setNombreAnomalieInfo(1);
            $historique->setNoteReliability(1);
            $historique->setNoteSecurity(2);
            $historique->setNoteSqale(3);
            $historique->setNoteHotspot(4);
            $historique->setNombreHotspot(10);
            $historique->setHotspotHigh(5);
            $historique->setHotspotMedium(3);
            $historique->setHotspotLow(2);
            $historique->setInitial(false);
            $historique->setModeCollecte('COLLECTE');
            $historique->setUtilisateurCollecte('Laurent.Hadjadj@ma-petite-entreprise.fr');
            $historique->setDateEnregistrement(new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')));
        $entityManager->persist($historique);
        $entityManager->flush();

        $client->request('PUT', static::$apiEnregistrement, [], [], ['CONTENT_TYPE' => static::$contentType], json_encode($data));

        $response = $client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);
        $this->assertEquals(23505, $jsonResponse['code']);
        $this->assertEquals('Les informations existent déjà.', $jsonResponse['erreur']);
    }

    public function testEnregistrementSansRoleCollecte(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$josh);
        $client->loginUser($testUser);

        $client->request('PUT', static::$apiEnregistrement, [], [], ['CONTENT_TYPE' => static::$contentType], '{}');

        $response = $client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $jsonResponse = json_decode($response->getContent(), true);
        $this->assertEquals(403, $jsonResponse['code']);
    }

    public function testEnregistrementHistoriqueServerError(): void
    {
        $client = static::createClient();
        $userRepository = static::getContainer()->get(UtilisateurRepository::class);
        $testUser = $userRepository->findOneByCourriel(static::$aurelie);
        $client->loginUser($testUser);

        // Données mal formattées (ex: valeur non attendue)
        $data = [
            'maven_key' => null, 'analyse_key' => 'example_key', 'version' => '1.0.0',
            'date_version' => '2023-10-01', 'nom_projet' => 'Example Project', 'version_release' => 2,
            'version_snapshot' => 4, 'version_autre' => 0, 'suppress_warning' => 10, 'no_sonar' => 140,
            'todo' => 13, 'logger_info' => 45, 'logger_warn' => 5, 'logger_error' => 12,
            'logger_debug' => 23, 'nombre_ligne' => 1000, 'nombre_ligne_code' => 800, 'coverage' => 90.7,
            'files' => 45, 'classes' => 120, 'functions' => 342, 'duplicated_lines_density' => 5,
            'sqale_debt_ratio' => 0.5, 'tests' => 50, 'violations' => 10, 'dette' => 20, 'nombre_bug' => 5,
            'nombre_vulnerability' => 2, 'nombre_code_smell' => 3, 'bug_blocker' => 1, 'bug_critical' => 1,
            'bug_major' => 1, 'bug_minor' => 1, 'bug_info' => 1, 'vulnerability_blocker' => 1,
            'vulnerability_critical' => 1, 'vulnerability_major' => 1, 'vulnerability_minor' => 1,
            'vulnerability_info' => 1, 'code_smell_blocker' => 1, 'code_smell_critical' => 1,
            'code_smell_major' => 1, 'code_smell_minor' => 1, 'code_smell_info' => 1,
            'frontend' => 159, 'backend' => 30, 'autre' => 12, 'inconnue' => 0,
            'nombre_anomalie_bloquant' => 1, 'nombre_anomalie_critique' => 1, 'nombre_anomalie_majeur' => 1,
            'nombre_anomalie_mineur' => 1, 'nombre_anomalie_info' => 1, 'note_reliability' => 1,
            'note_security' => 2, 'note_sqale' => 3, 'note_hotspot' => 4, 'nombre_hotspot' => 10,
            'hotspot_high' => 5, 'hotspot_medium' => 3, 'hotspot_low' => 2,
        ];

        $client->request('PUT', static::$apiEnregistrement, [], [], ['CONTENT_TYPE' => static::$contentType], json_encode($data));

        $response = $client->getResponse();
        $responseContent = $response->getContent();
        $this->assertNotEmpty($responseContent, 'La réponse de l\'API est vide.');

        $jsonResponse = json_decode($response->getContent(), true);
        $this->assertNotNull($jsonResponse, 'La réponse JSON est null. L\'API renvoie bien un JSON valide ?');
        $this->assertArrayHasKey('code', $jsonResponse);
        $this->assertArrayHasKey('erreur', $jsonResponse);
        $this->assertNotEquals(200, $jsonResponse['code']);
        $this->assertNotEquals(23505, $jsonResponse['code']);
        $this->assertEquals(500, $jsonResponse['code']);
        $this->assertStringContainsString('An exception occurred while executing a query: SQLSTATE[23502]: Not null violation: 7', $jsonResponse['erreur']);
    }

}
