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
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;

#[AllowMockObjectsWithoutExpectations]
class PreferenceControllerTest extends TestCase
{
    private const COURRIEL = 'user@acme.fr';

    /** @var EntityManagerInterface&MockObject */     private MockObject $em;
    /** @var ParameterBagInterface&MockObject */      private MockObject $params;
    /** @var Security&MockObject */                   private MockObject $security;
    /** @var ClientService&MockObject */              private MockObject $client;
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
        $this->security = $this->createMock(Security::class);
        $this->client = $this->createMock(ClientService::class);
        $this->tracking = $this->createMock(UserAgentTrackingFacade::class);
        $this->twig = $this->createMock(Environment::class);

        $this->connection = $this->createMock(Connection::class);
        $this->statement = $this->createMock(Statement::class);
        $this->em->method('getConnection')->willReturn($this->connection);
        $this->connection->method('prepare')->willReturn($this->statement);

        $this->user = $this->createMock(Utilisateur::class);
        $this->user->method('getCourriel')->willReturn(self::COURRIEL);
        $this->security->method('getUser')->willReturn($this->user);

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
        ]);
        $container->method('get')->willReturnMap([
            ['twig', 1, $this->twig],
        ]);

        $this->controller = new PreferenceController(
            $this->em,
            $this->params,
            $this->security,
            $this->client,
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

        // Capture le SQL + params pour vérifier l'utilisation des bind parameters
        $capturedSql = null;
        $capturedParams = null;
        $this->connection->expects($this->once())
            ->method('executeStatement')
            ->with(
                $this->callback(function ($sql) use (&$capturedSql) {
                    $capturedSql = $sql;
                    return true;
                }),
                $this->callback(function ($params) use (&$capturedParams) {
                    $capturedParams = $params;
                    return true;
                })
            )
            ->willReturn(1);

        $request = $this->jsonRequest(['statut' => 'ON', 'categorie' => 'bookmark']);

        $payload = json_decode($this->controller->apiPreferenceStatut($request)->getContent(), true);

        $this->assertSame('bookmark', $payload['categorie']);
        // Nouveau statut reflété
        $this->assertSame('ON', $payload['statut']['bookmark']);
        // Autres statuts inchangés
        $this->assertSame('off', $payload['statut']['suivi_projet']);
        // SQL utilise des placeholders (pas d'interpolation)
        $this->assertStringContainsString(':preference', $capturedSql);
        $this->assertStringContainsString(':courriel', $capturedSql);
        // Params contiennent le courriel + le JSON des préférences
        $this->assertSame(self::COURRIEL, $capturedParams['courriel']);
        $this->assertStringContainsString('"bookmark":"ON"', $capturedParams['preference']);
    }

    // ═════════════════════ apiPreferenceFavoriDelete ═══════════════════════

    public function testApiPreferenceFavoriDeleteRemovesMavenKeyFromFavoris(): void
    {
        $prefs = $this->buildBasePreferences();
        $prefs['favori'] = ['com.acme:a', 'com.acme:b', 'com.acme:c'];
        $this->user->expects($this->atLeastOnce())->method('getPreference')->willReturn($prefs);

        // On capture les params pour vérifier que le favori ciblé n'est plus présent
        $capturedParams = null;
        $this->connection->expects($this->once())
            ->method('executeStatement')
            ->with(
                $this->stringContains(':preference'),
                $this->callback(function ($params) use (&$capturedParams) {
                    $capturedParams = $params;
                    return true;
                })
            )
            ->willReturn(1);

        $response = $this->controller->apiPreferenceFavoriDelete(
            $this->jsonRequest(['mavenKey' => 'com.acme:b'])
        );

        $this->assertSame(200, $response->getStatusCode());
        // Le JSON sérialisé doit contenir les 2 favoris restants mais pas celui supprimé
        $this->assertStringContainsString('com.acme:a', $capturedParams['preference']);
        $this->assertStringContainsString('com.acme:c', $capturedParams['preference']);
        $this->assertStringNotContainsString('"com.acme:b"', $capturedParams['preference']);
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

        $capturedParams = null;
        $this->connection->expects($this->once())
            ->method('executeStatement')
            ->with(
                $this->stringContains(':preference'),
                $this->callback(function ($params) use (&$capturedParams) {
                    $capturedParams = $params;
                    return true;
                })
            )
            ->willReturn(1);

        $response = $this->controller->apiPreferenceVersionDelete($this->jsonRequest([
            'index' => 0,
            'mavenKey' => 'com.acme:a',
            'version' => '1.1',
        ]));

        $payload = json_decode($response->getContent(), true);
        $this->assertSame(200, $payload['code']);

        $json = $capturedParams['preference'];
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

    // ═════════════════════ helpers ═════════════════════════════════════════

    private function jsonRequest(array $body): Request
    {
        return new Request([], [], [], [], [], [], json_encode($body, JSON_FORCE_OBJECT));
    }

    private function buildBasePreferences(): array
    {
        return [
            'statut' => [
                'suivi_projet' => 'off',
                'favori_projet' => 'on',
                'favori_version' => 'off',
                'bookmark' => 'off',
            ],
            'projet' => [],
            'favori' => [],
            'version' => [],
            'bookmark' => [],
        ];
    }
}
