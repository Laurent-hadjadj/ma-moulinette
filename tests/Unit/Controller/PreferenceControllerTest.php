<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2026
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\PreferenceController;
use App\Entity\Utilisateur;
use App\Service\UserAgent\UserAgentTrackingFacade;
use Doctrine\DBAL\{Connection, Statement};
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
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;

#[AllowMockObjectsWithoutExpectations]
class PreferenceControllerTest extends TestCase
{
    private const COURRIEL = 'user@ma-moulinette.fr';

    /** @var EntityManagerInterface&MockObject */     private MockObject $em;
    /** @var ParameterBagInterface&MockObject */      private MockObject $params;
    /** @var TokenStorageInterface&MockObject */      private MockObject $tokenStorage;
    /** @var TokenInterface&MockObject */             private MockObject $token;
    /** @var LoggerInterface&MockObject */            private MockObject $logger;
    /** @var Environment&MockObject */                private MockObject $twig;
    /** @var Connection&MockObject */                 private MockObject $connection;
    /** @var Statement&MockObject */                  private MockObject $statement;
    /** @var AuthorizationCheckerInterface&MockObject */ private MockObject $authChecker;

    /** @var Utilisateur&MockObject */                private MockObject $user;

    /** @var UserAgentTrackingFacade&MockObject */    private MockObject $tracking;

    private PreferenceController $controller;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->params = $this->createMock(ParameterBagInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->token = $this->createMock(TokenInterface::class);
        $this->tokenStorage->method('getToken')->willReturn($this->token);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->twig = $this->createMock(Environment::class);

        $this->connection = $this->createMock(Connection::class);
        $this->statement = $this->createMock(Statement::class);
        $this->em->method('getConnection')->willReturn($this->connection);
        $this->connection->method('prepare')->willReturn($this->statement);

        $this->user = $this->createMock(Utilisateur::class);
        $this->user->method('getCourriel')->willReturn(self::COURRIEL);
        $this->token->method('getUser')->willReturn($this->user);
        $this->tracking = $this->createMock(UserAgentTrackingFacade::class);
        $this->authChecker = $this->createMock(AuthorizationCheckerInterface::class);

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
            ['security.authorization_checker', true],
        ]);
        $container->method('get')->willReturnMap([
            ['twig', 1, $this->twig],
            ['security.token_storage', 1, $this->tokenStorage],
            ['security.authorization_checker', 1, $this->authChecker],
        ]);

        $this->controller = new PreferenceController(
            $this->em,
            $this->logger,
            $this->params,
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
        /* MODIF 2026-05-07 : init '' / [] selon le type capturé (intelephense by-ref). */
        $capturedSql = '';
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

        $request = $this->jsonRequest(['statut' => 'ON', 'category' => 'favori_projet']);

        $payload = json_decode($this->controller->apiPreferenceStatut($request)->getContent(), true);

        $this->assertSame('favori_projet', $payload['category']);
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
        $prefs['favori_projet'] = ['fr.ma-moulinette:projet-a', 'fr.ma-moulinette:projet-b', 'fr.ma-moulinette:projet-c'];
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
            $this->jsonRequest(['mavenKey' => 'fr.ma-moulinette:projet-b'])
        );

        $this->assertSame(200, $response->getStatusCode());
        // Le JSON sérialisé doit contenir les 2 favoris restants mais pas celui supprimé
        $this->assertStringContainsString('fr.ma-moulinette:projet-a', $capturedBinds['preference']);
        $this->assertStringContainsString('fr.ma-moulinette:projet-c', $capturedBinds['preference']);
        $this->assertStringNotContainsString('"fr.ma-moulinette:projet-b"', $capturedBinds['preference']);
    }

    // ═════════════════════ apiPreferenceVersionDelete ══════════════════════

    public function testApiPreferenceVersionDeleteRemovesTargetedVersion(): void
    {
        $prefs = $this->buildBasePreferences();
        $prefs['favori_version'] = [
            ['fr.ma-moulinette:projet-a' => ['1.0', '1.1', '1.2']], // index 0
            ['fr.ma-moulinette:projet-b' => ['2.0']],              // index 1
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
            'mavenKey' => 'fr.ma-moulinette:projet-a',
            'version' => '1.1',
        ]));

        $payload = json_decode($response->getContent(), true);
        $this->assertSame(200, $payload['code']);

        $json = $capturedBinds['preference'];
        // La version 1.1 doit être retirée, les autres conservées
        $this->assertStringContainsString('1.0', $json);
        $this->assertStringContainsString('1.2', $json);
        // 1.1 ne doit plus apparaître parmi les versions de fr.ma-moulinette:projet-a
        $this->assertStringNotContainsString('"1.1"', $json);
        // L'entrée fr.ma-moulinette:projet-b/2.0 reste intacte
        $this->assertStringContainsString('2.0', $json);
    }

    // ═════════════════════ apiPreferenceCategory ═══════════════════════════

    public function testApiPreferenceCategoryReturnsStatutAndCategoryData(): void
    {
        $prefs = $this->buildBasePreferences();
        $prefs['suivi_projet'] = ['fr.ma-moulinette:projet-a', 'fr.ma-moulinette:projet-b'];
        $this->user->expects($this->atLeastOnce())->method('getPreference')->willReturn($prefs);

        $request = new Request(['category' => 'suivi_projet']);
        $payload = json_decode($this->controller->apiPreferenceCategory($request)->getContent(), true);

        $this->assertSame(200, $payload['code']);
        $this->assertSame(['fr.ma-moulinette:projet-a', 'fr.ma-moulinette:projet-b'], $payload['suivi_projet']);
        $this->assertSame($prefs['statut'], $payload['statut']);
    }

    // ═════════════════════ index ═══════════════════════════════════════════

    public function testIndexBuildsRenderContextAndTracks(): void
    {
        $this->authChecker->method('isGranted')->willReturn(false);
        $prefs = $this->buildBasePreferences();
        $this->user->method('getPreference')->willReturn($prefs);
        $this->user->method('getPrenom')->willReturn('Alice');
        $this->user->method('getNom')->willReturn('Doe');
        $this->user->method('getAvatar')->willReturn('avatar.png');
        $this->user->method('getRoles')->willReturn(['ROLE_USER']);
        $this->user->method('getGroupeUtilisateur')->willReturn('Groupe-A');
        $this->user->method('getListeGroupeFonctionnel')->willReturn(['equipe-1']);

        /* MODIF 2026-05-07 : init [] (intelephense by-ref). */
        $capturedCtx = [];
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
        // Régression : la clé de contexte doit être 'groupeUtilisateur' (singulier), pas 'groupesUtilisateur' — c'est le nom lu par le template.
        $this->assertSame('Groupe-A', $capturedCtx['groupeUtilisateur']);
        $this->assertSame(['equipe-1'], $capturedCtx['groupesFonctionnel']);
        $this->assertArrayHasKey('suivi_projet', $capturedCtx['preferences']);
        $this->assertArrayHasKey('favori_projet', $capturedCtx['preferences']);
        $this->assertFalse($capturedCtx['peut_voir_actuator']);
    }

    public function testIndexExposesActuatorLinkWhenRoleGranted(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->user->method('getPreference')->willReturn($this->buildBasePreferences());
        $this->user->method('getPrenom')->willReturn('Alice');
        $this->user->method('getNom')->willReturn('Doe');
        $this->user->method('getAvatar')->willReturn('avatar.png');
        $this->user->method('getRoles')->willReturn(['ROLE_ACTUATOR']);
        $this->user->method('getListeGroupeFonctionnel')->willReturn(['equipe-1']);

        $capturedCtx = [];
        $this->twig->expects($this->once())
            ->method('render')
            ->with('preference/index.html.twig', $this->callback(function (array $ctx) use (&$capturedCtx) {
                $capturedCtx = $ctx;
                return true;
            }))
            ->willReturn('<html></html>');

        $this->controller->index();

        $this->assertTrue($capturedCtx['peut_voir_actuator']);
    }

    public function testIndexFillsGroupesWithSentinelNullWhenEmpty(): void
    {
        $this->authChecker->method('isGranted')->willReturn(false);
        $this->user->method('getPreference')->willReturn($this->buildBasePreferences());
        $this->user->method('getPrenom')->willReturn('A');
        $this->user->method('getNom')->willReturn('B');
        $this->user->method('getAvatar')->willReturn('a.png');
        $this->user->method('getRoles')->willReturn([]);
        $this->user->method('getGroupeUtilisateur')->willReturn(null);
        $this->user->method('getListeGroupeFonctionnel')->willReturn([]); // vide

        /* MODIF 2026-05-07 : init [] (intelephense by-ref). */
        $capturedCtx = [];
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

        // L'implémentation positionne groupesFonctionnel[0]='null' quand la liste est vide
        $this->assertSame(['null'], $capturedCtx['groupesFonctionnel']);
        // et 'Aucun' pour le groupe utilisateur (singulier) quand il est absent
        $this->assertSame('Aucun', $capturedCtx['groupeUtilisateur']);
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

    public function testApiPreferenceStatutReturns400OnUnknownCategorie(): void
    {
        // Régression : les anciennes clés courtes ('favori'/'projet'/'version') ne sont plus acceptées,
        // seules les vraies clés partagées avec le reste de l'application le sont.
        $this->user->method('getPreference')->willReturn($this->buildBasePreferences());

        $response = $this->controller->apiPreferenceStatut(
            $this->jsonRequest(['statut' => true, 'category' => 'favori'])
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
            $this->jsonRequest(['statut' => 'ON', 'category' => 'favori_projet'])
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
        $prefs['favori_version'] = []; // pas d'entrée à l'index 0
        $this->user->method('getPreference')->willReturn($prefs);

        $response = $this->controller->apiPreferenceVersionDelete($this->jsonRequest([
            'index' => 0, 'mavenKey' => 'fr.ma-moulinette:projet-inconnu', 'version' => '1.0',
        ]));
        $payload = json_decode($response->getContent(), true);

        $this->assertSame(404, $payload['code']);
        $this->assertSame('warning', $payload['type']);
    }

    public function testApiPreferenceCategoryReturns400OnUnknownCategory(): void
    {
        $request = new Request(['category' => 'bogus']); // pas dans CATEGORIES_AUTORISEES
        $response = $this->controller->apiPreferenceCategory($request);
        $payload = json_decode($response->getContent(), true);

        $this->assertSame(400, $payload['code']);
    }

    public function testApiPreferenceCategoryReturns400WhenMissing(): void
    {
        $response = $this->controller->apiPreferenceCategory(new Request());
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
        // Clés alignées sur le schéma réel utilisé partout ailleurs (CustomAuthenticator, UtilisateurRepository...).
        return [
            'statut' => [
                'suivi_projet' => 'off',
                'favori_projet' => 'on',
                'favori_version' => 'off',
            ],
            'suivi_projet' => [],
            'favori_projet' => [],
            'favori_version' => [],
        ];
    }
}
