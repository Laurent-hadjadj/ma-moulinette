<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Suivi;

use App\Controller\Suivi\ApiSuiviController;
use App\Entity\Historique;
use App\Entity\InformationProjet;
use App\Entity\Utilisateur;
use App\Repository\HistoriqueRepository;
use App\Repository\InformationProjetRepository;
use App\Repository\UtilisateurRepository;
use App\Service\ClientService;
use App\Service\CommandRebuildHistorique\BuildMapHistoryService;
use App\Service\ExtractName;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[AllowMockObjectsWithoutExpectations]
class ApiSuiviControllerTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */            private MockObject $em;
    /** @var ClientService&MockObject */                     private MockObject $client;
    /** @var Security&MockObject */                          private MockObject $security;
    /** @var LoggerInterface&MockObject */                   private MockObject $logger;
    private BuildMapHistoryService $buildMap;
    /** @var HistoriqueRepository&MockObject */              private MockObject $historiqueRepo;
    /** @var InformationProjetRepository&MockObject */       private MockObject $informationRepo;
    /** @var UtilisateurRepository&MockObject */             private MockObject $utilisateurRepo;
    /** @var ParameterBagInterface&MockObject */             private MockObject $params;
    /** @var AuthorizationCheckerInterface&MockObject */     private MockObject $authChecker;
    /** @var TokenStorageInterface&MockObject */             private MockObject $tokenStorage;

    private ApiSuiviController $controller;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->client = $this->createMock(ClientService::class);
        $this->security = $this->createMock(Security::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        // Vrai service utilisé : on teste l'intégration metricsKey + metricsRebuild
        // (typage, conversion ratings 1-5 → A-E, ratios cyclomatique/cognitif, etc.).
        $this->buildMap = new BuildMapHistoryService(new ExtractName());
        $this->historiqueRepo = $this->createMock(HistoriqueRepository::class);
        $this->informationRepo = $this->createMock(InformationProjetRepository::class);
        $this->utilisateurRepo = $this->createMock(UtilisateurRepository::class);
        $this->params = $this->createMock(ParameterBagInterface::class);
        $this->authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);

        $this->params->method('get')->willReturn('https://sonar.example.com');

        $this->em->method('getRepository')->willReturnMap([
            [Historique::class, $this->historiqueRepo],
            [InformationProjet::class, $this->informationRepo],
            [Utilisateur::class, $this->utilisateurRepo],
        ]);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(
            fn(string $id): bool => in_array($id, [
                'security.authorization_checker', 'security.token_storage', 'parameter_bag',
            ], true)
        );
        $container->method('get')->willReturnMap([
            ['security.authorization_checker', 1, $this->authChecker],
            ['security.token_storage', 1, $this->tokenStorage],
            ['parameter_bag', 1, $this->params],
        ]);

        // appUser() trait → getUser() → tokenStorage->getToken()->getUser()
        $defaultUser = new Utilisateur();
        $defaultUser->setCourriel('test@example.com');
        $defaultUser->setPreference([
            'statut' => ['favori_projet' => false, 'favori_version' => false],
            'favori_projet' => [], 'favori_version' => [],
        ]);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($defaultUser);
        $this->tokenStorage->method('getToken')->willReturn($token);

        $this->controller = new ApiSuiviController($this->em, $this->client, $this->security, $this->logger, $this->buildMap);
        $this->controller->setContainer($container);
    }

    /* ============ listeVersionV1 ============ */

    public function testListeVersionV1Returns400WhenMavenKeyMissing(): void
    {
        $response = $this->controller->listeVersionV1($this->jsonRequest([]));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(400, $data['code']);
    }

    public function testListeVersionV1ReturnsErrorWhenRepoFails(): void
    {
        $this->informationRepo->expects($this->once())
            ->method('selectInformationProjetVersion')
            ->willReturn(['code' => 500, 'erreur' => 'db']);

        $response = $this->controller->listeVersionV1($this->jsonRequest(['maven_key' => 'k']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(500, $data['code']);
    }

    public function testListeVersionV1HappyPath(): void
    {
        $this->informationRepo->method('selectInformationProjetVersion')->willReturn([
            'code' => 200,
            'versions' => [
                ['version' => '1.0', 'date' => '2026-04-10 10:00:00'],
                ['version' => '1.1', 'date' => '2026-04-11 10:00:00'],
            ],
        ]);

        $response = $this->controller->listeVersionV1($this->jsonRequest(['maven_key' => 'k']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
        $this->assertCount(2, $data['liste']);
        $this->assertStringContainsString('1.0', $data['liste'][0]['text']);
    }

    /* ============ listeVersionV2 ============ */

    public function testListeVersionV2Returns400WhenMavenKeyMissing(): void
    {
        $response = $this->controller->listeVersionV2($this->jsonRequest([]));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(400, $data['code']);
    }

    public function testListeVersionV2ReturnsErrorWhenSonarFails(): void
    {
        $this->client->expects($this->once())
            ->method('httpSonarQube')
            ->willReturn(['code' => 503, 'erreur' => 'down']);

        $response = $this->controller->listeVersionV2($this->jsonRequest(['maven_key' => 'k']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(503, $data['code']);
    }

    public function testListeVersionV2HappyPath(): void
    {
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 200,
            'json' => [
                'analyses' => [
                    ['projectVersion' => '1.0', 'date' => '2026-04-10 10:00:00'],
                ],
            ],
        ]);

        $response = $this->controller->listeVersionV2($this->jsonRequest(['maven_key' => 'k']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
        $this->assertCount(1, $data['liste']);
    }

    /* ============ suiviMiseAJour ============ */

    public function testSuiviMiseAJourReturns400WhenMavenKeyMissing(): void
    {
        $response = $this->controller->suiviMiseAJour($this->jsonRequest([]));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(400, $data['code']);
    }

    public function testSuiviMiseAJourReturnsErrorWhenInsertFails(): void
    {
        $user = new Utilisateur();
        $user->setCourriel('u@x');
        $this->security->method('getUser')->willReturn($user);

        $this->historiqueRepo->expects($this->once())
            ->method('insertHistoriqueAjoutProjet')
            ->willReturn(['code' => 500, 'erreur' => 'insert fail']);

        $response = $this->controller->suiviMiseAJour($this->jsonRequest($this->buildSuiviPayload()));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(500, $data['code']);
    }

    public function testSuiviMiseAJourHappyPath(): void
    {
        $user = new Utilisateur();
        $user->setCourriel('u@x');
        $this->security->method('getUser')->willReturn($user);

        $this->historiqueRepo->expects($this->once())
            ->method('insertHistoriqueAjoutProjet')
            ->willReturn(['code' => 200]);

        $response = $this->controller->suiviMiseAJour($this->jsonRequest($this->buildSuiviPayload()));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
    }

    /* ============ suiviVersionListe ============ */

    public function testSuiviVersionListeReturns400WhenMavenKeyMissing(): void
    {
        $response = $this->controller->suiviVersionListe($this->jsonRequest([]));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(400, $data['code']);
    }

    public function testSuiviVersionListeReturnsErrorWhenRepoFails(): void
    {
        $this->historiqueRepo->expects($this->once())
            ->method('selectHistoriqueProjetByDate')
            ->willReturn(['code' => 500, 'erreur' => 'fail']);

        $response = $this->controller->suiviVersionListe($this->jsonRequest(['maven_key' => 'k']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(500, $data['code']);
    }

    public function testSuiviVersionListeHappyPath(): void
    {
        $user = new Utilisateur();
        $user->setPreference(['favori_version' => false]);
        $this->security->method('getUser')->willReturn($user);
        $this->stubTokenStorageUser($user);

        $this->historiqueRepo->method('selectHistoriqueProjetByDate')->willReturn([
            'code' => 200,
            'version' => [
                ['maven_key' => 'k', 'version' => '1.0'],
                ['maven_key' => 'k', 'version' => '1.1'],
            ],
        ]);

        $response = $this->controller->suiviVersionListe($this->jsonRequest(['maven_key' => 'k']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
        $this->assertCount(2, $data['versions']);
        $this->assertFalse($data['versions'][0]['favori']);
    }

    /* ============ suiviVersionFavori ============ */

    public function testSuiviVersionFavoriReturns400OnInvalidPayload(): void
    {
        $response = $this->controller->suiviVersionFavori($this->jsonRequest([]));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(400, $data['code']);
    }

    public function testSuiviVersionFavoriHappyPath(): void
    {
        $user = new Utilisateur();
        $user->setCourriel('u@x');
        $user->setPreference(['favori_version' => []]);
        $this->security->method('getUser')->willReturn($user);
        $this->stubTokenStorageUser($user);

        $this->utilisateurRepo->expects($this->once())
            ->method('updateUtilisateurFavoriVersion')
            ->willReturn(['code' => 200]);

        $response = $this->controller->suiviVersionFavori($this->jsonRequest([
            'favori' => true,
            'courriel' => 'u@x',
            'maven_key' => 'k',
            'version' => '1.0',
            'date_version' => '2026-04-10',
        ]));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
    }

    /**
     * Régression 2026-05-03 : le payload UI n'envoie pas 'courriel' (déjà
     * connu côté serveur via appUser()). Le check property_exists 'courriel'
     * faisait retourner 400 et empêchait le toggle favori côté UI.
     */
    public function testSuiviVersionFavoriAcceptsPayloadWithoutCourriel(): void
    {
        $user = new Utilisateur();
        $user->setCourriel('u@x');
        $user->setPreference(['favori_version' => []]);
        $this->stubTokenStorageUser($user);

        $this->utilisateurRepo->expects($this->once())
            ->method('updateUtilisateurFavoriVersion')
            ->willReturn(['code' => 200]);

        // payload SANS courriel (comme le JS en envoie actuellement)
        $response = $this->controller->suiviVersionFavori($this->jsonRequest([
            'favori' => true,
            'maven_key' => 'k',
            'version' => '1.0',
            'date_version' => '2026-04-10',
        ]));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
    }

    /* ============ suiviVersionReference ============ */

    public function testSuiviVersionReferenceReturns400OnMissingFields(): void
    {
        $response = $this->controller->suiviVersionReference($this->jsonRequest(['maven_key' => 'k']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(400, $data['code']);
    }

    public function testSuiviVersionReferenceReturns403WithoutRole(): void
    {
        $this->security->method('isGranted')->willReturn(false);

        $response = $this->controller->suiviVersionReference($this->jsonRequest([
            'maven_key' => 'k', 'initial' => 1, 'version' => '1.0', 'date_version' => '2026-04-10',
        ]));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(403, $data['code']);
    }

    public function testSuiviVersionReferenceHappyPath(): void
    {
        $this->security->method('isGranted')->willReturn(true);

        $this->historiqueRepo->expects($this->once())
            ->method('updateHistoriqueReference')
            ->willReturn(['code' => 200]);

        $response = $this->controller->suiviVersionReference($this->jsonRequest([
            'maven_key' => 'k', 'initial' => 1, 'version' => '1.0', 'date_version' => '2026-04-10',
        ]));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
    }

    /**
     * Régression 2026-05-03 : la branche d'erreur lisait $result['code'] alors
     * que la variable de retour s'appelait $request → null silencieux côté UI,
     * la response sortait avec code=null et le switch UI restait incohérent.
     */
    public function testSuiviVersionReferencePropagatesRepoErrorCode(): void
    {
        $this->security->method('isGranted')->willReturn(true);

        $this->historiqueRepo->expects($this->once())
            ->method('updateHistoriqueReference')
            ->willReturn(['code' => 500, 'erreur' => 'db down']);

        $response = $this->controller->suiviVersionReference($this->jsonRequest([
            'maven_key' => 'k', 'initial' => 1, 'version' => '1.0', 'date_version' => '2026-04-10',
        ]));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(500, $data['code'], 'Le code repo doit remonter (avant le fix : null)');
        $this->assertSame('db down', $data['trace']);
    }

    /* ============ suiviVersionPoubelle ============ */

    public function testSuiviVersionPoubelleReturns400OnMissingFields(): void
    {
        $response = $this->controller->suiviVersionPoubelle($this->jsonRequest(['maven_key' => 'k']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(400, $data['code']);
    }

    public function testSuiviVersionPoubelleReturns403WithoutRole(): void
    {
        $this->security->method('isGranted')->willReturn(false);

        $response = $this->controller->suiviVersionPoubelle($this->jsonRequest([
            'maven_key' => 'k', 'version' => '1.0', 'date_version' => '2026-04-10',
        ]));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(403, $data['code']);
    }

    public function testSuiviVersionPoubelleHappyPath(): void
    {
        $this->security->method('isGranted')->willReturn(true);
        $user = new Utilisateur();
        $user->setCourriel('u@x');
        $user->setPreference(['favori_version' => []]);
        $this->security->method('getUser')->willReturn($user);

        $this->historiqueRepo->expects($this->once())
            ->method('deleteHistoriqueProjet')
            ->willReturn(['code' => 200]);

        $response = $this->controller->suiviVersionPoubelle($this->jsonRequest([
            'maven_key' => 'k', 'version' => '1.0', 'date_version' => '2026-04-10',
        ]));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
    }

    /* ============ getVersion ============ */

    public function testGetVersionReturns400WhenMavenKeyMissing(): void
    {
        $response = $this->controller->getVersion($this->jsonRequest(['other' => 'x']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(400, $data['code']);
    }

    public function testGetVersionReturns400WhenDateMissing(): void
    {
        $response = $this->controller->getVersion($this->jsonRequest(['maven_key' => 'k']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(400, $data['code']);
    }

    public function testGetVersionReturns400OnInvalidJson(): void
    {
        $response = $this->controller->getVersion($this->jsonRequest('garbage'));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(400, $data['code']);
    }

    public function testGetVersionHappyPathAggregatesAllMetrics(): void
    {
        // Tous les 6 appels SonarQube renvoient le même json avec quelques mesures par groupe
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 200,
            'json' => [
                'measures' => [
                    ['metric' => 'coverage', 'history' => [['value' => '80.5']]],
                    ['metric' => 'tests', 'history' => [['value' => '42']]],
                    ['metric' => 'violations', 'history' => [['value' => '7']]],
                    ['metric' => 'lines', 'history' => [['value' => '1500']]],
                    ['metric' => 'ncloc', 'history' => [['value' => '1200']]],
                    ['metric' => 'code_smells', 'history' => [['value' => '5']]],
                    ['metric' => 'sqale_rating', 'history' => [['value' => '1']]],
                    ['metric' => 'bugs', 'history' => [['value' => '2']]],
                    ['metric' => 'reliability_rating', 'history' => [['value' => '1']]],
                    ['metric' => 'vulnerabilities', 'history' => [['value' => '0']]],
                    ['metric' => 'security_rating', 'history' => [['value' => '1']]],
                ],
            ],
        ]);

        $response = $this->controller->getVersion($this->jsonRequest([
            'maven_key' => 'acme:app',
            'date' => '28-06-2024 20:48:20',
        ]));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
        $this->assertSame(80.5, $data['data']['coverage']);
        $this->assertSame(42, $data['data']['tests']);
        $this->assertSame(7, $data['data']['violations']);
        $this->assertNull($data['data']['skipped_tests']); // absent → null (helper JS displayMetric → '-')
    }

    /**
     * Régression 2026-05-03 : SonarQube renvoie parfois des entrées history sans
     * la clé 'value' (métrique non calculée pour cette analyse). Le code lisait
     * $history['value'] directement → Warning "Undefined array key" et 500.
     */
    public function testGetVersionSkipsHistoryEntriesWithoutValueKey(): void
    {
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 200,
            'json' => [
                'measures' => [
                    // Mix : une mesure avec valeur, une autre dont l'history est sans 'value'
                    ['metric' => 'coverage', 'history' => [['date' => '2026-04-10']]], // pas de 'value'
                    ['metric' => 'tests', 'history' => [['value' => '42']]],
                ],
            ],
        ]);

        $response = $this->controller->getVersion($this->jsonRequest([
            'maven_key' => 'acme:app',
            'date' => '28-06-2024 20:48:20',
        ]));
        $data = json_decode($response->getContent(), true);

        // Le call ne doit PAS crasher (avant le fix : Warning + 500)
        $this->assertSame(200, $data['code']);
        // coverage absent → null (helper JS affiche '-'), tests présent → 42
        $this->assertNull($data['data']['coverage']);
        $this->assertSame(42, $data['data']['tests']);
    }

    /* ============ helpers ============ */

    private function stubTokenStorageUser(Utilisateur $user): void
    {
        $token = new UsernamePasswordToken($user, 'main', ['ROLE_USER']);
        $this->tokenStorage->method('getToken')->willReturn($token);
    }

    private function jsonRequest(array|string $body): Request
    {
        if (is_string($body)) {
            $content = $body;
        } elseif ($body === []) {
            $content = '{}';
        } else {
            $content = json_encode($body);
        }
        return new Request([], [], [], [], [], [], $content);
    }

    private function buildSuiviPayload(): array
    {
        // Payload aligné sur les clés SonarQube (= colonnes DB historique).
        return [
            'maven_key' => 'k', 'version' => '1.0', 'date_version' => '2026-04-10',
            'project_name' => 'App', 'initial' => 0,
            'coverage' => 80, 'duplicated_lines_density' => 1, 'tests' => 10,
            'skipped_tests' => 0, 'test_errors' => 0, 'test_failures' => 0,
            'violations' => 5, 'classes' => 10, 'comment_lines' => 100,
            'comment_lines_density' => 20, 'files' => 5, 'lines' => 1000, 'ncloc' => 800,
            'ncloc_language_distribution' => 'java=800',
            'functions' => 20, 'sqale_debt_ratio' => 2, 'sqale_index' => 100,
            'sqale_rating' => 1.0, 'code_smells' => 3,
            'bugs' => 1, 'reliability_rating' => 2.0,
            'vulnerabilities' => 0, 'security_rating' => 1.0,
            'security_review_rating' => 1.0, 'security_hotspots' => 0,
        ];
    }
}
