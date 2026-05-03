<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\PreferenceController;
use App\Entity\Utilisateur;
use App\Service\ClientService;
use App\Service\UserAgentTrackingFacade;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Twig\Environment;

#[AllowMockObjectsWithoutExpectations]
class PreferenceControllerTest extends TestCase
{
    private const COURRIEL = 'user@acme.fr';

    /** @var EntityManagerInterface&MockObject */     private MockObject $em;
    /** @var ParameterBagInterface&MockObject */      private MockObject $params;
    /** @var TokenStorageInterface&MockObject */      private MockObject $tokenStorage;
    /** @var TokenInterface&MockObject */             private MockObject $token;
    /** @var ClientService&MockObject */              private MockObject $client;
    /** @var LoggerInterface&MockObject */            private MockObject $logger;
    /** @var UserAgentTrackingFacade&MockObject */    private MockObject $tracking;
    /** @var Environment&MockObject */                private MockObject $twig;
    /** @var Connection&MockObject */                 private MockObject $connection;
    /** @var Statement&MockObject */                  private MockObject $statement;

    /** @var Utilisateur&MockObject */                private MockObject $user;

    private PreferenceController $controller;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->params = $this->createMock(ParameterBagInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->token = $this->createMock(TokenInterface::class);
        $this->tokenStorage->method('getToken')->willReturn($this->token);
        $this->client = $this->createMock(ClientService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->tracking = $this->createMock(UserAgentTrackingFacade::class);
        $this->twig = $this->createMock(Environment::class);

        $this->connection = $this->createMock(Connection::class);
        $this->statement = $this->createMock(Statement::class);
        $this->em->method('getConnection')->willReturn($this->connection);
        $this->connection->method('prepare')->willReturn($this->statement);

        $this->user = $this->createMock(Utilisateur::class);
        $this->user->method('getCourriel')->willReturn(self::COURRIEL);
        $this->token->method('getUser')->willReturn($this->user);

        // Params (constructeur + getParameter)
        $this->params->method('get')->willReturnMap([
            ['logo.entreprise', 'logo.png'],
            ['marque.entreprise.short', 'MM'],
            ['marque.entreprise.long', 'Ma Moulinette'],
            ['environnement', 'DEV'],
            ['version', '2.0.0'],
        ]);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnMap([
            ['twig', true],
            ['parameter_bag', false],
            ['serializer', false],
            ['security.token_storage', true],
        ]);
        $container->method('get')->willReturnMap([
            ['twig', 1, $this->twig],
            ['security.token_storage', 1, $this->tokenStorage],
        ]);

        $this->controller = new PreferenceController(
            $this->em,
            $this->params,
            $this->client,
            $this->logger,
            $this->tracking
        );
        $this->controller->setContainer($container);
    }

    // ═════════════════════ apiPreferenceStatut ═════════════════════════════

    public function testApiPreferenceStatutUpdatesStatutAndPersistsViaSql(): void
    {
        $this->user->expects($this->atLeastOnce())
            ->method('getPreference')
            ->willReturn($this->buildBasePreferences());

        // Capture le SQL via prepare(), les binds via bindValue() — pattern prepared statement
        $capturedSql = null;
        $capturedBinds = [];
        $this->connection->expects($this->once())
            ->method('prepare')
            ->with($this->callback(function ($sql) use (&$capturedSql) {
                $capturedSql = $sql;
                return true;
            }))
            ->willReturn($this->statement);
        $this->statement->method('bindValue')->willReturnCallback(function ($key, $value) use (&$capturedBinds) {
            $capturedBinds[$key] = $value;
            return true;
        });
        $this->statement->expects($this->once())->method('executeStatement')->willReturn(1);

        $request = $this->jsonRequest(['statut' => 'ON', 'categorie' => 'favori_projet']);

        $payload = json_decode($this->controller->apiPreferenceStatut($request)->getContent(), true);

        $this->assertSame('favori_projet', $payload['categorie']);
        // Nouveau statut reflété
        $this->assertSame('ON', $payload['statut']['favori_projet']);
        // Autres statuts inchangés
        $this->assertSame('off', $payload['statut']['suivi_projet']);
        // SQL utilise des placeholders (pas d'interpolation)
        $this->assertStringContainsString(':preference', $capturedSql);
        $this->assertStringContainsString(':courriel', $capturedSql);
        // Binds contiennent le courriel + le JSON des préférences
        $this->assertSame(self::COURRIEL, $capturedBinds['courriel']);
        $this->assertStringContainsString('"favori_projet":"ON"', $capturedBinds['preference']);
    }

    // ═════════════════════ apiPreferenceFavoriDelete ═══════════════════════

    public function testApiPreferenceFavoriDeleteRemovesMavenKeyFromFavoris(): void
    {
        $prefs = $this->buildBasePreferences();
        $prefs['favori'] = ['com.acme:a', 'com.acme:b', 'com.acme:c'];
        $this->user->expects($this->atLeastOnce())->method('getPreference')->willReturn($prefs);

        // Capture des binds via prepared statement
        $capturedBinds = [];
        $this->connection->expects($this->once())->method('prepare')->willReturn($this->statement);
        $this->statement->method('bindValue')->willReturnCallback(function ($key, $value) use (&$capturedBinds) {
            $capturedBinds[$key] = $value;
            return true;
        });
        $this->statement->expects($this->once())->method('executeStatement')->willReturn(1);

        $response = $this->controller->apiPreferenceFavoriDelete(
            $this->jsonRequest(['mavenKey' => 'com.acme:b'])
        );

        $this->assertSame(200, $response->getStatusCode());
        // Le JSON sérialisé doit contenir les 2 favoris restants mais pas celui supprimé
        $this->assertStringContainsString('com.acme:a', $capturedBinds['preference']);
        $this->assertStringContainsString('com.acme:c', $capturedBinds['preference']);
        $this->assertStringNotContainsString('"com.acme:b"', $capturedBinds['preference']);
    }

    // ═════════════════════ apiPreferenceVersionDelete ══════════════════════

    public function testApiPreferenceVersionDeleteRemovesTargetedVersion(): void
    {
        $prefs = $this->buildBasePreferences();
        $prefs['version'] = [
            ['com.acme:a' => ['1.0', '1.1', '1.2']], // index 0
            ['com.acme:b' => ['2.0']],              // index 1
        ];
        $this->user->expects($this->atLeastOnce())->method('getPreference')->willReturn($prefs);

        $capturedBinds = [];
        $this->connection->expects($this->once())->method('prepare')->willReturn($this->statement);
        $this->statement->method('bindValue')->willReturnCallback(function ($key, $value) use (&$capturedBinds) {
            $capturedBinds[$key] = $value;
            return true;
        });
        $this->statement->expects($this->once())->method('executeStatement')->willReturn(1);

        $response = $this->controller->apiPreferenceVersionDelete($this->jsonRequest([
            'index' => 0,
            'mavenKey' => 'com.acme:a',
            'version' => '1.1',
        ]));

        $payload = json_decode($response->getContent(), true);
        $this->assertSame(200, $payload['code']);

        $json = $capturedBinds['preference'];
        // La version 1.1 doit être retirée, les autres conservées
        $this->assertStringContainsString('1.0', $json);
        $this->assertStringContainsString('1.2', $json);
        // 1.1 ne doit plus apparaître parmi les versions de com.acme:a
        $this->assertStringNotContainsString('"1.1"', $json);
        // L'entrée com.acme:b/2.0 reste intacte
        $this->assertStringContainsString('2.0', $json);
    }

    // ═════════════════════ apiPreferenceCategorie ══════════════════════════

    public function testApiPreferenceCategorieReturnsStatutAndCategorieData(): void
    {
        $prefs = $this->buildBasePreferences();
        $prefs['projet'] = ['com.acme:a', 'com.acme:b'];
        $this->user->expects($this->atLeastOnce())->method('getPreference')->willReturn($prefs);

        $request = new Request(['categorie' => 'projet']);
        $payload = json_decode($this->controller->apiPreferenceCategorie($request)->getContent(), true);

        $this->assertSame(200, $payload['code']);
        $this->assertSame(['com.acme:a', 'com.acme:b'], $payload['projet']);
        $this->assertSame($prefs['statut'], $payload['statut']);
    }

    // ═════════════════════ index ═══════════════════════════════════════════

    public function testIndexBuildsRenderContextAndTracks(): void
    {
        $prefs = $this->buildBasePreferences();
        $this->user->method('getPreference')->willReturn($prefs);
        $this->user->method('getPrenom')->willReturn('Alice');
        $this->user->method('getNom')->willReturn('Doe');
        $this->user->method('getAvatar')->willReturn('avatar.png');
        $this->user->method('getRoles')->willReturn(['ROLE_USER']);
        $this->user->method('getListeGroupeFonctionnel')->willReturn(['equipe-1']);

        $this->tracking->expects($this->once())->method('track')->with('PREFERENCE');

        $capturedCtx = null;
        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                'preference/index.html.twig',
                $this->callback(function (array $ctx) use (&$capturedCtx) {
                    $capturedCtx = $ctx;
                    return true;
                })
            )
            ->willReturn('<html>prefs</html>');

        $response = $this->controller->index();

        $this->assertSame('<html>prefs</html>', $response->getContent());
        $this->assertSame('Alice', $capturedCtx['prenom']);
        $this->assertSame('Doe', $capturedCtx['nom']);
        $this->assertSame(['ROLE_USER'], $capturedCtx['roles']);
        $this->assertSame(['equipe-1'], $capturedCtx['groupes']);
        $this->assertArrayHasKey('suivi_projet', $capturedCtx['preferences']);
        $this->assertArrayHasKey('favori_projet', $capturedCtx['preferences']);
    }

    public function testIndexFillsGroupesWithSentinelNullWhenEmpty(): void
    {
        $this->user->method('getPreference')->willReturn($this->buildBasePreferences());
        $this->user->method('getPrenom')->willReturn('A');
        $this->user->method('getNom')->willReturn('B');
        $this->user->method('getAvatar')->willReturn('a.png');
        $this->user->method('getRoles')->willReturn([]);
        $this->user->method('getListeGroupeFonctionnel')->willReturn([]); // vide

        $capturedCtx = null;
        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                'preference/index.html.twig',
                $this->callback(function (array $ctx) use (&$capturedCtx) {
                    $capturedCtx = $ctx;
                    return true;
                })
            )
            ->willReturn('<html></html>');

        $this->controller->index();

        // L'implémentation positionne groupes[0]='null' quand la liste est vide
        $this->assertSame(['null'], $capturedCtx['groupes']);
    }

    // ═════════════════════ chemins d'erreur (validation, 4xx, 5xx) ═════════

    public function testApiPreferenceStatutReturns400OnMissingFields(): void
    {
        $response = $this->controller->apiPreferenceStatut(
            $this->jsonRequest(['statut' => 'ON']) // 'categorie' manquante
        );
        $payload = json_decode($response->getContent(), true);

        $this->assertSame(400, $payload['code']);
        $this->assertSame('error', $payload['type']);
    }

    public function testApiPreferenceStatutReturns500WhenUpdateFails(): void
    {
        $this->user->method('getPreference')->willReturn($this->buildBasePreferences());
        $this->connection->method('prepare')->willReturn($this->statement);
        // Simule un échec d'écriture SQL (Throwable attrapé par updatePreference → return false)
        $this->statement->method('executeStatement')->willThrowException(new \RuntimeException('db down'));

        $response = $this->controller->apiPreferenceStatut(
            $this->jsonRequest(['statut' => 'ON', 'categorie' => 'favori_projet'])
        );
        $payload = json_decode($response->getContent(), true);

        $this->assertSame(500, $payload['code']);
        $this->assertSame('error', $payload['type']);
    }

    public function testApiPreferenceFavoriDeleteReturns400OnMissingMavenKey(): void
    {
        $response = $this->controller->apiPreferenceFavoriDelete(
            $this->jsonRequest([]) // 'mavenKey' manquante
        );
        $payload = json_decode($response->getContent(), true);

        $this->assertSame(400, $payload['code']);
    }

    public function testApiPreferenceVersionDeleteReturns400OnMissingFields(): void
    {
        $response = $this->controller->apiPreferenceVersionDelete(
            $this->jsonRequest(['mavenKey' => 'k']) // 'index' et 'version' manquants
        );
        $payload = json_decode($response->getContent(), true);

        $this->assertSame(400, $payload['code']);
    }

    public function testApiPreferenceVersionDeleteReturns404WhenIndexNotFound(): void
    {
        $prefs = $this->buildBasePreferences();
        $prefs['version'] = []; // pas d'entrée à l'index 0
        $this->user->method('getPreference')->willReturn($prefs);

        $response = $this->controller->apiPreferenceVersionDelete($this->jsonRequest([
            'index' => 0, 'mavenKey' => 'unknown:app', 'version' => '1.0',
        ]));
        $payload = json_decode($response->getContent(), true);

        $this->assertSame(404, $payload['code']);
        $this->assertSame('warning', $payload['type']);
    }

    public function testApiPreferenceCategorieReturns400OnUnknownCategorie(): void
    {
        $request = new Request(['categorie' => 'bogus']); // pas dans CATEGORIES_AUTORISEES
        $response = $this->controller->apiPreferenceCategorie($request);
        $payload = json_decode($response->getContent(), true);

        $this->assertSame(400, $payload['code']);
    }

    public function testApiPreferenceCategorieReturns400WhenMissing(): void
    {
        $response = $this->controller->apiPreferenceCategorie(new Request());
        $payload = json_decode($response->getContent(), true);

        $this->assertSame(400, $payload['code']);
    }

    // ═════════════════════ helpers ═════════════════════════════════════════

    private function jsonRequest(array $body): Request
    {
        return new Request([], [], [], [], [], [], json_encode($body, JSON_FORCE_OBJECT));
    }

    private function buildBasePreferences(): array
    {
        // Note : 'bookmark' retiré des préférences (refacto 2026-04, impact entité Utilisateur + fixtures)
        return [
            'statut' => [
                'suivi_projet' => 'off',
                'favori_projet' => 'on',
                'favori_version' => 'off',
            ],
            'projet' => [],
            'favori' => [],
            'version' => [],
        ];
    }
}
